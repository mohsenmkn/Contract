<?php

namespace Modules\Contract\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Party\App\Models\Party;
use Modules\Amendment\App\Models\Amendment;
use Modules\Guarantee\App\Models\Guarantee;
use Modules\Invoice\App\Models\Invoice;
use Modules\Signature\App\Models\Signature;
use Modules\Alert\App\Models\ContractAlert;
use Modules\Document\App\Models\Document;
use Modules\Auth\App\Models\User;

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
        'content',
        'pages_count',
        'content_version',
        'initial_amount',
        'current_amount',
        'currency',
        'description',
        'unit_id',
        'requesting_user_id',
        'parent_contract_id',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'initial_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ContractItem::class);
    }

    public function amendments(): HasMany
    {
        return $this->hasMany(Amendment::class);
    }

    public function guarantees(): HasMany
    {
        return $this->hasMany(Guarantee::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function signatures(): MorphMany
    {
        return $this->morphMany(Signature::class, 'signable');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(ContractAlert::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('end_date', '<=', now()->addDays($days))
            ->where('end_date', '>=', now())
            ->where('status', 'active');
    }

    public function scopeUpstream($query)
    {
        return $query->where('type', 'upstream');
    }

    public function scopeDownstream($query)
    {
        return $query->where('type', 'downstream');
    }

    public function isExpiring(): bool
    {
        return $this->end_date->diffInDays(now()) <= 30 && $this->status === 'active';
    }

    /**
     * محاسبه خودکار تعداد صفحات بر اساس طول محتوا
     */
    public function calculatePagesCount(): int
    {
        if (empty($this->content)) {
            return 0;
        }

        // تبدیل HTML به متن ساده
        $text = strip_tags($this->content);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        // هر صفحه ≈ 1500 کاراکتر (تقریبی برای متن فارسی)
        $charsPerPage = 1500;
        $length = mb_strlen($text, 'UTF-8');

        return max(1, (int) ceil($length / $charsPerPage));
    }

    /**
     * آیا قرارداد متن دارد؟
     */
    public function hasContent(): bool
    {
        return !empty($this->content) && strlen(strip_tags($this->content)) > 50;
    }

    /**
     * خلاصه متن (برای نمایش در لیست)
     */
    public function getContentSummary(int $maxLength = 200): string
    {
        if (!$this->hasContent()) {
            return '';
        }

        $text = strip_tags($this->content);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);

        if (mb_strlen($text, 'UTF-8') <= $maxLength) {
            return $text;
        }

        return mb_substr($text, 0, $maxLength, 'UTF-8') . '...';
    }

    /**
     * واحد سازمانی درخواست‌کننده
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(\Modules\Organization\Models\OrganizationalUnit::class);
    }

    /**
     * کاربر درخواست‌دهنده
     */
    public function requestingUser(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'requesting_user_id');
    }

    /**
     * قرارداد مادر (کارفرما)
     */
    public function parentContract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'parent_contract_id');
    }

    /**
     * قراردادهای فرزند (پیمانکار)
     */
    public function childContracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'parent_contract_id');
    }

    /**
     * امضاهای اجرایی
     */
    public function executiveSignatures(): HasMany
    {
        return $this->hasMany(\Modules\Signature\App\Models\ExecutiveSignature::class);
    }

    /**
     * آیا قرارداد والد دارد؟
     */
    public function isChildContract(): bool
    {
        return !is_null($this->parent_contract_id);
    }

    /**
     * آیا قرارداد والد است؟
     */
    public function isParentContract(): bool
    {
        return $this->childContracts()->exists();
    }

    /**
     * مبلغ قرارداد به تومان (برای بررسی محدودیت امضا)
     */
    public function getAmountInToman(): float
    {
        return (float) $this->current_amount / 10;
    }


    /**
     * آیا نیاز به امضای مدیرعامل/هیئت مدیره دارد؟ (بالای 900 میلیون تومان)
     */
    public function requiresHighLevelSignature(): bool
    {
        return $this->getAmountInToman() >= 900_000_000;
    }

    /**
     * Scope: قراردادهای یک واحد
     */
    public function scopeForUnit($query, $unitId)
    {
        return $query->where('unit_id', $unitId);
    }

    /**
     * Scope: قراردادهای در انتظار امضای اجرایی
     */
    public function scopePendingExecutiveSign($query)
    {
        return $query->where('status', 'pending_executive_sign');
    }

}
