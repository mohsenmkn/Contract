<?php

namespace Modules\Contract\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Amendment\App\Models\Amendment;
use Modules\Party\App\Models\Party;
use Modules\Auth\App\Models\User;
use Modules\Organization\App\Models\OrganizationalUnit;

class Contract extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'contract_number',
        'subject',
        'party_id',
        'type',
        'status',
        'start_date',
        'end_date',
        'initial_amount',
        'current_amount',
        'currency',
        'description',
        'content',
        'pages_count',
        'content_version',
        'requires_guarantee',
        'guarantee_type',
        'guarantee_instrument_type',
        'requires_board_signature',
        'unit_id',
        'requesting_user_id',
        'parent_contract_id',
        'created_by',
        'approved_by',
        'approved_at',
        'notified_at',
        'notification_letter_data',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'notified_at' => 'datetime',
        'notification_letter_data' => 'array',
        'initial_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
        'requires_guarantee' => 'boolean',
        'requires_board_signature' => 'boolean',
    ];

    // ═══════════════════════════════════════════════════════
    // وضعیت‌های مجاز و برچسب‌های فارسی
    // ═══════════════════════════════════════════════════════
    const STATUSES = [
        'pending_contract_review' => 'در انتظار بررسی واحد قراردادها',
        'draft' => 'پیش‌نویس',
        'pending_legal_review' => 'در انتظار بررسی حقوقی',
        'legal_draft' => 'پیش‌نویس حقوقی',
        'rejected_by_legal' => 'رد شده توسط حقوقی',
        'pending_contractor_sign' => 'در انتظار امضای پیمانکار',
        'pending_guarantee' => 'در انتظار دریافت ضمانت',
        'pending_unit_sign' => 'در انتظار امضای واحد متقاضی',
        'pending_legal_sign' => 'در انتظار امضای مدیر حقوقی',
        'pending_executive_sign' => 'در انتظار امضای مدیران ارشد',
        'pending_notification' => 'در انتظار ابلاغ',
        'active' => 'فعال',
        'expiring' => 'در حال اتمام',
        'completed' => 'خاتمه یافته',
        'terminated' => 'فسخ شده',
    ];

    // ═══════════════════════════════════════════════════════
    // قوانین ترنزیشن (چه وضعیتی به چه وضعیتی می‌تواند برود)
    // ═══════════════════════════════════════════════════════
    const TRANSITIONS = [
        'draft' => ['pending_contract_review', 'pending_legal_review', 'terminated'],
        'pending_contract_review' => ['pending_legal_review', 'draft', 'terminated'],
        'pending_legal_review' => ['legal_draft', 'draft', 'rejected_by_legal', 'terminated'],
        'legal_draft' => ['pending_contractor_sign', 'pending_legal_review', 'draft', 'rejected_by_legal', 'terminated'],
        'rejected_by_legal' => ['pending_contract_review', 'draft', 'terminated'],
        // پس از امضای پیمانکار گردش ادامه پیدا می‌کند؛ ضمانت فقط پیش‌شرط ابلاغ نهایی است.
        'pending_contractor_sign' => ['pending_unit_sign', 'legal_draft', 'terminated'],
        'pending_guarantee' => ['pending_unit_sign', 'legal_draft', 'terminated'],
        'pending_unit_sign' => ['pending_legal_sign', 'legal_draft', 'terminated'],
        'pending_legal_sign' => ['pending_executive_sign', 'pending_unit_sign', 'legal_draft', 'terminated'],
        'pending_executive_sign' => ['pending_notification', 'pending_legal_sign', 'legal_draft', 'terminated'],
        'pending_notification' => ['active', 'pending_executive_sign', 'terminated'],
        'active' => ['expiring', 'completed', 'terminated'],
        'expiring' => ['completed', 'terminated', 'active'],
        'completed' => [],
        'terminated' => [],
    ];

    // فقط در این وضعیت‌ها متن، آیتم‌ها و مبلغ قابل ویرایش است
    const EDITABLE_STATUSES = ['draft', 'pending_contract_review', 'pending_legal_review', 'legal_draft', 'rejected_by_legal'];

    public function isContentEditable(): bool
    {
        return in_array($this->status, self::EDITABLE_STATUSES, true);
    }

    /**
     * آیا ترنزیشن به وضعیت جدید مجاز است؟
     */
    public function canTransitionTo(string $newStatus): bool
    {
        $allowed = self::TRANSITIONS[$this->status] ?? [];
        return in_array($newStatus, $allowed);
    }

    /**
     * تغییر وضعیت با ثبت تاریخچه
     */
    public function changeStatus(string $newStatus, ?User $user = null, ?string $comments = null): self
    {
        DB::transaction(function () use ($newStatus, $user, $comments) {
            // قفل ردیف تا دو درخواست همزمان وضعیت را دوبار عوض نکنند
            $locked = static::query()->lockForUpdate()->findOrFail($this->id);

            if (!$locked->canTransitionTo($newStatus)) {
                throw new \Exception(
                    "ترنزیشن از '{$locked->status}' به '{$newStatus}' مجاز نیست."
                );
            }

            $oldStatus = $locked->status;

            ContractStatusHistory::create([
                'contract_id' => $locked->id,
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'user_id' => $user?->id,
                'comments' => $comments,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            $locked->update(['status' => $newStatus]);

            // ساخت اسلات‌های فاز جدید یا ابطال امضاها در برگشت به عقب
            app(\Modules\Signature\App\Services\SigningService::class)
                ->onStatusChanged($locked, $newStatus);
        });

        return $this->refresh();
    }

    /**
     * برچسب فارسی وضعیت فعلی
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * رنگ وضعیت برای نمایش
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'secondary',
            'pending_contract_review' => 'warn',
            'pending_legal_review' => 'warn',
            'legal_draft' => 'info',
            'rejected_by_legal' => 'danger',
            'pending_contractor_sign' => 'warn',
            'pending_guarantee' => 'warn',
            'pending_unit_sign' => 'warn',
            'pending_legal_sign' => 'warn',
            'pending_executive_sign' => 'warn',
            'pending_notification' => 'info',
            'active' => 'success',
            'expiring' => 'warn',
            'completed' => 'success',
            'terminated' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * آیکون وضعیت
     */
    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'pi pi-file',
            'pending_legal_review' => 'pi pi-clock',
            'legal_draft' => 'pi pi-file-edit',
            'rejected_by_legal' => 'pi pi-times-circle',
            'pending_contractor_sign' => 'pi pi-signature',
            'pending_guarantee' => 'pi pi-shield',
            'pending_unit_sign' => 'pi pi-users',
            'pending_legal_sign' => 'pi pi-briefcase',
            'pending_executive_sign' => 'pi pi-user-tie',
            'pending_notification' => 'pi pi-send',
            'active' => 'pi pi-check-circle',
            'expiring' => 'pi pi-exclamation-triangle',
            'completed' => 'pi pi-check',
            'terminated' => 'pi pi-times-circle',
            default => 'pi pi-file',
        };
    }

    // ═══════════════════════════════════════════════════════
    // Relationships
    // ═══════════════════════════════════════════════════════

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class);
    }

    public function requestingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requesting_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function parentContract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'parent_contract_id');
    }

    public function childContracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'parent_contract_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ContractItem::class);
    }

    public function amendments(): HasMany
    {
        return $this->hasMany(Amendment::class);
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(\Modules\Signature\App\Models\Signature::class, 'signable_id')
            ->where('signable_type', self::class);
    }

    public function executiveSignatures(): HasMany
    {
        return $this->hasMany(\Modules\Signature\App\Models\ExecutiveSignature::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(\Modules\Invoice\App\Models\Invoice::class);
    }

    public function guarantees(): HasMany
    {
        return $this->hasMany(\Modules\Guarantee\App\Models\Guarantee::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(\Modules\Alert\App\Models\ContractAlert::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ContractStatusHistory::class)->orderByDesc('created_at');
    }

    // ═══════════════════════════════════════════════════════
    // Scopes
    // ═══════════════════════════════════════════════════════

    public function scopeForUnit($query, $unitId)
    {
        return $query->where('unit_id', $unitId);
    }

    public function scopePendingLegalReview($query)
    {
        return $query->where('status', 'pending_legal_review');
    }

    public function scopePendingExecutiveSign($query)
    {
        return $query->where('status', 'pending_executive_sign');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('status', 'active')
            ->where('end_date', '<=', now()->addDays($days))
            ->where('end_date', '>=', now());
    }

    // ══════════════════════════════════════════════════════
    // Helper Methods
    // ═══════════════════════════════════════════════════════

    public function isContractorContract(): bool
    {
        return $this->type === 'downstream';
    }

    public function hasContent(): bool
    {
        return trim(strip_tags((string) $this->content)) !== '';
    }

    public function calculatePagesCount(): int
    {
        if (!$this->hasContent()) {
            return 0;
        }

        return max(1, (int) ceil(mb_strlen(strip_tags((string) $this->content), 'UTF-8') / 1500));
    }

    public function getContentSummary(int $limit = 200): string
    {
        $text = trim(preg_replace('/\\s+/u', ' ', strip_tags((string) $this->content)) ?? '');

        return mb_strlen($text, 'UTF-8') > $limit
            ? mb_substr($text, 0, $limit, 'UTF-8') . '…'
            : $text;
    }

    public function isExpiring(int $days = 30): bool
    {
        if ($this->status === 'expiring') {
            return true;
        }

        if ($this->status !== 'active' || !$this->end_date) {
            return false;
        }

        $endDate = $this->end_date->copy()->startOfDay();

        return $endDate->greaterThanOrEqualTo(today())
            && $endDate->lessThanOrEqualTo(today()->addDays($days));
    }

    public function isEmployerContract(): bool
    {
        return $this->type === 'upstream';
    }

    public function isChildContract(): bool
    {
        return !is_null($this->parent_contract_id);
    }

    public function getAmountInToman(): float
    {
        return (float) $this->current_amount / 10;
    }

}
