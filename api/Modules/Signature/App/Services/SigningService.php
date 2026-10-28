<?php

namespace Modules\Signature\App\Services;

use DomainException;
use InvalidArgumentException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Auth\App\Models\User;
use Modules\Contract\App\Models\Contract;
use Modules\Contract\App\Services\ContractSettings;
use Modules\Signature\App\Models\Signature;

/**
 * مدیریت امضای قرارداد بر پایه‌ی «اسلات»:
 *  - با ورود قرارداد به هر فاز، اسلات‌های امضای آن فاز ساخته می‌شود (prepareSlots)
 *  - بعد از تأیید OTP، complete() اسلات را امضا می‌کند (snapshot تصویر + hash + قفل رکورد)
 *  - با تغییر متن/آیتم/مبلغ یا برگشت قرارداد به عقب، امضاهای مربوطه باطل می‌شوند
 *
 * تنظیمات (فازها، نقش‌ها، آستانه‌ی مبلغ) در config/signing.php است.
 */
class SigningService
{
    public function __construct(
        private SignatureImageService $images,
        private ContractSettings $settings,
    ) {
    }

    // ═══════════════════════════════════════════════════════
    // هماهنگی با تغییر وضعیت قرارداد (از Contract::changeStatus صدا زده می‌شود)
    // ═══════════════════════════════════════════════════════

    public function onStatusChanged(Contract $contract, string $newStatus): void
    {
        $phases = array_keys(config('signing.phases'));

        if (in_array($newStatus, config('signing.reset_all_statuses', []), true)) {
            $this->invalidate($contract, $phases, "بازگشت قرارداد به وضعیت {$newStatus}");
            return;
        }

        $phase = $this->phaseByStatus($newStatus);
        if ($phase === null) {
            return; // pending_notification, active, ... → امضاها دست‌نخورده می‌مانند
        }

        // ورود (یا بازگشت) به یک فاز: امضای این فاز و فازهای بعدی از نو
        $from = array_search($phase, $phases, true);
        $this->invalidate($contract, array_slice($phases, $from), "ورود مجدد به فاز {$phase}");
        $this->prepareSlots($contract, $phase);
    }

    // ═══════════════════════════════════════════════════════
    // ساخت اسلات‌ها
    // ═══════════════════════════════════════════════════════

    public function prepareSlots(Contract $contract, string $phase): Collection
    {
        $cfg = config("signing.phases.{$phase}");
        if ($cfg === null) {
            throw new InvalidArgumentException("فاز امضا نامعتبر است: {$phase}");
        }

        return DB::transaction(function () use ($contract, $cfg) {
            $slots = collect();
            $order = 0;

            foreach ($cfg['groups'] as $group) {
                $exists = $this->openQuery($contract)
                    ->where('group', $group)
                    ->whereIn('status', ['pending', 'signed'])
                    ->exists();

                if ($exists) {
                    continue; // idempotent
                }

                foreach ($this->roleSets($contract, $group) as $roles) {
                    $slots->push(Signature::create([
                        'signable_type' => Contract::class,
                        'signable_id' => $contract->id,
                        'group' => $group,
                        'allowed_roles' => $roles,
                        'kind' => 'signature',
                        'sign_order' => ++$order,
                        'content_version' => (int) $contract->content_version,
                        'status' => 'pending',
                    ]));
                }
            }

            return $slots;
        });
    }

    // ═══════════════════════════════════════════════════════
    // امضا کردن (بعد از موفقیت OTP صدا زده شود)
    // ═══════════════════════════════════════════════════════

    public function complete(Signature $slot, User $signer, ?string $comments = null): Contract
    {
        return DB::transaction(function () use ($slot, $signer, $comments) {
            /** @var Signature $slot */
            $slot = Signature::whereKey($slot->id)->lockForUpdate()->firstOrFail();
            /** @var Contract $contract */
            $contract = Contract::whereKey($slot->signable_id)->lockForUpdate()->firstOrFail();

            $this->assertCanSign($slot, $contract, $signer);

            $asset = $this->images->activeFor($signer, $slot->kind ?: 'signature');
            $snapshot = $this->images->snapshot($asset, $slot);

            $slot->fill([
                'status' => 'signed',
                'signer_id' => $signer->id,
                'role' => $this->resolveRole($slot, $signer),
                'image_path' => $snapshot['path'],
                'image_hash' => $snapshot['hash'],
                'comments' => $comments,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'signed_at' => now(),
            ]);
            $slot->signature_hash = $this->computeHash($contract, $slot);
            $slot->save();

            $this->advanceIfPhaseComplete($contract, $slot->group, $signer);

            return $contract->fresh();
        });
    }

    /** آیا hash ثبت‌شده هنوز با محتوای فعلی قرارداد می‌خواند؟ (برای صفحه‌ی راستی‌آزمایی) */
    public function verify(Signature $slot): bool
    {
        if (!$slot->isSigned() || !$slot->signature_hash) {
            return false;
        }

        $contract = Contract::find($slot->signable_id);
        if (!$contract || (int) $contract->content_version !== (int) $slot->content_version) {
            return false;
        }

        return hash_equals($slot->signature_hash, $this->computeHash($contract, $slot));
    }

    // ═══════════════════════════════════════════════════════
    // نسخه‌بندی و ابطال
    // ═══════════════════════════════════════════════════════

    /**
     * هنگام تغییر متن / آیتم‌ها / مبلغ صدا بزنید (UpdateContractService و legalDraft).
     * نسخه را بالا می‌برد و همه‌ی امضاهای قبلی را باطل می‌کند.
     */
    public function bumpContentVersion(Contract $contract, string $reason): Contract
    {
        return DB::transaction(function () use ($contract, $reason) {
            $contract->forceFill(['content_version' => ((int) $contract->content_version) + 1])->save();
            $this->invalidate($contract, array_keys(config('signing.phases')), $reason);

            return $contract->refresh();
        });
    }

    public function invalidate(Contract $contract, array $phaseNames, string $reason): int
    {
        $groups = collect($phaseNames)
            ->flatMap(fn ($phase) => config("signing.phases.{$phase}.groups", []))
            ->all();

        if (empty($groups)) {
            return 0;
        }

        return Signature::where('signable_type', Contract::class)
            ->where('signable_id', $contract->id)
            ->whereIn('group', $groups)
            ->whereIn('status', ['pending', 'signed'])
            ->update([
                'status' => 'invalidated',
                'invalidated_at' => now(),
                'invalidated_reason' => $reason,
            ]);
    }

    // ═══════════════════════════════════════════════════════
    // داخلی
    // ═══════════════════════════════════════════════════════

    private function advanceIfPhaseComplete(Contract $contract, string $group, User $actor): void
    {
        $phase = $this->phaseByGroup($group);
        if ($phase === null) {
            return;
        }

        $cfg = config("signing.phases.{$phase}");

        $stillOpen = $this->openQuery($contract)
            ->whereIn('group', $cfg['groups'])
            ->where('status', 'pending')
            ->exists();

        if ($stillOpen) {
            return;
        }

        $next = $cfg['next_status'];
        if (!empty($cfg['next_status_if_guarantee']) && $contract->requires_guarantee) {
            $next = $cfg['next_status_if_guarantee'];
        }

        $contract->changeStatus($next, $actor, "تکمیل امضاهای فاز {$phase}");
    }

    private function assertCanSign(Signature $slot, Contract $contract, User $signer): void
    {
        if ($slot->status !== 'pending') {
            throw new DomainException('این امضا دیگر در انتظار نیست.');
        }

        if ((int) $slot->content_version !== (int) $contract->content_version) {
            throw new DomainException('متن قرارداد تغییر کرده و این امضا باطل شده است.');
        }

        $phase = $this->phaseByGroup($slot->group);
        if ($phase === null || $contract->status !== config("signing.phases.{$phase}.status")) {
            throw new DomainException('وضعیت فعلی قرارداد امکان این امضا را نمی‌دهد.');
        }

        if (!$signer->hasAnyRole($slot->allowed_roles ?? [])) {
            throw new DomainException('شما نقش لازم برای این امضا را ندارید.');
        }

        $groupCfg = config("signing.groups.{$slot->group}", []);

        if (!empty($groupCfg['same_unit']) && (int) $signer->unit_id !== (int) $contract->unit_id) {
            throw new DomainException('فقط اعضای واحد درخواست‌دهنده می‌توانند این قرارداد را امضا کنند.');
        }

        if (!empty($groupCfg['same_party']) && (int) $signer->party_id !== (int) $contract->party_id) {
            throw new DomainException('شما نماینده‌ی طرف این قرارداد نیستید.');
        }

        $alreadySigned = $this->openQuery($contract)
            ->where('status', 'signed')
            ->where('signer_id', $signer->id)
            ->exists();

        if ($alreadySigned) {
            throw new DomainException('یک نفر نمی‌تواند بیش از یک امضا در یک قرارداد ثبت کند.');
        }
    }

    private function resolveRole(Signature $slot, User $signer): ?string
    {
        return collect($slot->allowed_roles ?? [])->first(fn ($role) => $signer->hasRole($role));
    }

    /** hash روی متن، آیتم‌ها، مبلغ، نسخه، تصویر و هویت امضاکننده */
    private function computeHash(Contract $contract, Signature $slot): string
    {
        $items = $contract->items()
            ->orderBy('id')
            ->get(['item_code', 'description', 'unit', 'quantity', 'unit_price', 'total_price'])
            ->toJson();

        return hash('sha256', json_encode([
            'contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'content_version' => (int) $contract->content_version,
            'content_sha256' => hash('sha256', (string) $contract->content),
            'items_sha256' => hash('sha256', $items),
            'amount' => (string) $contract->current_amount,
            'currency' => $contract->currency,
            'slot_id' => $slot->id,
            'group' => $slot->group,
            'role' => $slot->role,
            'signer_id' => $slot->signer_id,
            'image_sha256' => $slot->image_hash,
            'signed_at' => $slot->signed_at?->toIso8601String(),
        ], JSON_UNESCAPED_UNICODE));
    }

    /** @return array<int, array<int, string>> هر عضو = نقش‌های مجاز برای یک اسلات */
    private function roleSets(Contract $contract, string $group): array
    {
        $cfg = config("signing.groups.{$group}");
        if ($cfg === null) {
            throw new InvalidArgumentException("گروه امضا نامعتبر است: {$group}");
        }

        $roles = $cfg['roles'];
        if (isset($cfg['roles_high_amount']) && $this->requiresHighLevel($contract)) {
            $roles = $cfg['roles_high_amount'];
        }

        return ($cfg['mode'] ?? 'any') === 'all'
            ? array_map(fn ($role) => [$role], $roles)
            : [$roles];
    }

    /** مبلغ در دیتابیس ریال است (مطابق Contract::getAmountInToman) */
    private function requiresHighLevel(Contract $contract): bool
    {
        $rial = (int) strtok((string) $contract->current_amount, '.');

        return $rial >= $this->settings->executiveThresholdToman() * 10;
    }

    private function openQuery(Contract $contract)
    {
        return Signature::where('signable_type', Contract::class)
            ->where('signable_id', $contract->id)
            ->where('content_version', (int) $contract->content_version);
    }

    private function phaseByStatus(string $status): ?string
    {
        foreach (config('signing.phases') as $name => $cfg) {
            if ($cfg['status'] === $status) {
                return $name;
            }
        }

        return null;
    }

    private function phaseByGroup(?string $group): ?string
    {
        foreach (config('signing.phases') as $name => $cfg) {
            if (in_array($group, $cfg['groups'], true)) {
                return $name;
            }
        }

        return null;
    }
}
