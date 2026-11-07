<?php

namespace Modules\Contract\App\Services;

use DomainException;
use Illuminate\Support\Facades\DB;
use Modules\Auth\App\Models\User;
use Modules\Contract\App\Models\Contract;

/**
 * ثبت دریافت ضمانت توسط واحد مالی؛ این ثبت شرط ابلاغ است و گردش امضا را متوقف نمی‌کند.
 */
class GuaranteeGateService
{
    public const PERMISSION = 'guarantees.manage';

    public function assertCanManage(Contract $contract, User $user): void
    {
        if (!$user->can(self::PERMISSION)) {
            throw new DomainException('دسترسی لازم برای ثبت ضمانت را ندارید.');
        }

        if (!$contract->requires_guarantee) {
            throw new DomainException('این قرارداد نیاز به ضمانت ندارد.');
        }

        if (!in_array($contract->status, ['pending_notification', 'pending_guarantee'], true)) {
            throw new DomainException('ثبت ضمانت در این مرحله از قرارداد امکان‌پذیر نیست.');
        }
    }

    public function confirm(Contract $contract, User $user, ?string $comments = null): Contract
    {
        return DB::transaction(function () use ($contract, $user) {
            $locked = Contract::whereKey($contract->id)->lockForUpdate()->firstOrFail();

            $this->assertCanManage($locked, $user);

            if (!$locked->guarantees()->where('status', 'active')->exists()) {
                throw new DomainException('ابتدا حداقل یک ضمانت برای قرارداد ثبت کنید.');
            }

            // ثبت ضمانت توسط واحد مالی، خودِ تأیید دریافت فیزیکی است؛ وضعیت قرارداد تغییر نمی‌کند.
            return $locked->fresh();
        });
    }
}
