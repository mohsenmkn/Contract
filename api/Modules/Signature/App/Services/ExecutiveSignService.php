<?php

namespace Modules\Signature\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Contract\App\Models\Contract;
use Modules\Signature\App\Models\ExecutiveSignature;
use Modules\Auth\App\Models\User;
use Exception;

class ExecutiveSignService
{
    // آستانه مبلغ به تومان (۹۰۰ میلیون)
    const HIGH_VALUE_THRESHOLD = 9000_000_000;

    /**
     * بررسی اینکه آیا کاربر مجاز به امضای این قرارداد خاص هست یا خیر
     */
    public function canSign(Contract $contract, User $user): bool
    {
        // ۱. کاربر باید یکی از نقش‌های اجرایی را داشته باشد
        $hasRole = $user->hasAnyRole(['ceo', 'deputy_ceo', 'board_member']);
        if (!$hasRole) {
            return false;
        }

        // ۲. بررسی محدودیت مبلغ برای معاون مالی
        $amountInToman = (float) $contract->current_amount / 10;

        if ($amountInToman >= self::HIGH_VALUE_THRESHOLD) {
            // برای مبالغ بالا، معاون مالی نمی‌تواند امضا کند
            if ($user->hasRole('deputy_ceo')) {
                return false;
            }
        }

        return true;
    }

    /**
     * ثبت امضای اجرایی
     */
    public function sign(Contract $contract, User $user, string $role, ?string $comments = null): Contract
    {
        return DB::transaction(function () use ($contract, $user, $role, $comments) {
            // اعتبارسنجی‌ها
            if ($contract->status !== 'pending_executive_sign') {
                throw new Exception('قرارداد در وضعیت امضای اجرایی نیست.');
            }

            if (!$this->canSign($contract, $user)) {
                throw new Exception('شما مجاز به امضای این قرارداد (با این مبلغ) نیستید.');
            }

            // جلوگیری از امضای تکراری
            $existing = ExecutiveSignature::where('contract_id', $contract->id)
                ->where('signer_id', $user->id)
                ->exists();

            if ($existing) {
                throw new Exception('شما قبلاً این قرارداد را امضا کرده‌اید.');
            }

            // ثبت رکورد امضا
            ExecutiveSignature::create([
                'contract_id' => $contract->id,
                'signer_id' => $user->id,
                'signer_role' => $role,
                'signed_at' => now(),
                'signature_hash' => hash('sha256', $contract->id . $user->id . now()->timestamp . $role),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'comments' => $comments,
            ]);

            // تغییر وضعیت به "در انتظار ابلاغ"
            $contract->update([
                'status' => 'pending_notification',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            // ارسال رویداد برای اطلاع به واحد حقوقی جهت ابلاغ
            event(new \Modules\Contract\Events\ContractStatusChanged($contract, 'pending_notification', $user));

            return $contract->fresh();
        });
    }
}
