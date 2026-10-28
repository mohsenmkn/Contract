<?php

namespace Modules\Signature\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\App\Models\User;

class OtpCode extends Model
{
    protected $fillable = [
        'otpable_type',
        'otpable_id',
        'user_id',
        'code',
        'purpose',
        'status',
        'attempts',
        'max_attempts',
        'expires_at',
        'verified_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function otpable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * آیا کد منقضی شده؟
     */
    public function isExpired(): bool
    {
        return now()->greaterThanOrEqualTo($this->expires_at);
    }

    /**
     * آیا کد معتبر است؟
     */
    public function isValid(): bool
    {
        return $this->status === 'pending'
            && !$this->isExpired()
            && $this->attempts < $this->max_attempts;
    }

    /**
     * آیا تمام تلاش‌ها مصرف شده؟
     */
    public function isAttemptsExhausted(): bool
    {
        return $this->attempts >= $this->max_attempts;
    }

    /**
     * ثبت یک تلاش ناموفق
     */
    public function recordFailedAttempt(): void
    {
        $this->increment('attempts');

        if ($this->isAttemptsExhausted()) {
            $this->update(['status' => 'failed']);
        }
    }

    /**
     * تایید کد
     */
    public function markAsVerified(): void
    {
        $this->update([
            'status' => 'verified',
            'verified_at' => now(),
        ]);
    }

    /**
     * تولید کد 6 رقمی
     */
    public static function generateCode(): string
    {
        return str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }
}
