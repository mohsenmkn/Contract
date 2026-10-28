<?php

namespace Modules\Signature\App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Signature\App\Models\OtpCode;
use Modules\Auth\App\Models\User;
use Exception;

class OtpService
{
    public function __construct(
        private SmsService $smsService
    ) {}

    /**
     * ایجاد و ارسال کد OTP
     */
    public function generateAndSend(Model $signable, User $user, int $templateId = 23490): OtpCode
    {
        return DB::transaction(function () use ($signable, $user, $templateId) {
            // غیرفعال کردن OTP های قبلی pending برای همین سند
            OtpCode::where('otpable_type', get_class($signable))
                ->where('otpable_id', $signable->id)
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->update(['status' => 'expired']);

            // تولید کد جدید
            $code = OtpCode::generateCode();
            $expiryMinutes = 5;

            $otp = OtpCode::create([
                'otpable_type' => get_class($signable),
                'otpable_id' => $signable->id,
                'user_id' => $user->id,
                'code' => $code,
                'purpose' => 'signature',
                'status' => 'pending',
                'attempts' => 0,
                'max_attempts' => 3,
                'expires_at' => now()->addMinutes($expiryMinutes),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // ارسال پیامک
            $mobile = $user->mobile;
            if (empty($mobile)) {
                throw new Exception('شماره موبایل کاربر ثبت نشده است.');
            }

            $result = $this->smsService->sendOtpCode($mobile, $code, $templateId);

            if (!$result['success']) {
                // اگر ارسال پیامک ناموفق بود، OTP را حذف کن
                $otp->update(['status' => 'expired']);
                throw new Exception($result['message']);
            }

            return $otp;
        });
    }

    /**
     * اعتبارسنجی کد OTP
     */
    public function verify(OtpCode $otp, string $code): bool
    {
        if (!$otp->isValid()) {
            return false;
        }

        if ($otp->code !== $code) {
            $otp->recordFailedAttempt();
            return false;
        }

        $otp->markAsVerified();
        return true;
    }

    /**
     * دریافت آخرین OTP معتبر برای یک سند و کاربر
     */
    public function getLatestPending(Model $signable, User $user): ?OtpCode
    {
        return OtpCode::where('otpable_type', get_class($signable))
            ->where('otpable_id', $signable->id)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }
}
