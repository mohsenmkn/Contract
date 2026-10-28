<?php

namespace Modules\Contract\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Contract\App\Models\Contract;
use Modules\Contract\App\Events\ContractStatusChanged;
use Modules\Auth\App\Models\User;
use Exception;

class ContractWorkflowService
{
    /**
     * ارسال قرارداد از واحد درخواست‌کننده به واحد حقوقی
     */
    public function submitToLegal(Contract $contract, User $user): Contract
    {
        return DB::transaction(function () use ($contract, $user) {
            if ($contract->status !== 'draft') {
                throw new Exception('فقط قراردادهای پیش‌نویس قابل ارسال به حقوقی هستند.');
            }

            // بررسی اینکه کاربر نماینده واحد مربوطه است
            if ($contract->requesting_user_id !== $user->id) {
                throw new Exception('شما مجاز به ارسال این قرارداد نیستید.');
            }

            $contract->update(['status' => 'pending_legal_review']);

            event(new ContractStatusChanged($contract, 'pending_legal_review', $user));

            return $contract->fresh();
        });
    }

    /**
     * تنظیم پیش‌نویس حقوقی توسط کارشناس حقوقی
     */
    public function createLegalDraft(Contract $contract, User $user, string $content): Contract
    {
        return DB::transaction(function () use ($contract, $user, $content) {
            if ($contract->status !== 'pending_legal_review') {
                throw new Exception('قرارداد در وضعیت بررسی حقوقی نیست.');
            }

            $contract->update([
                'status' => 'legal_draft',
                'content' => $content,
                'content_version' => '1.0', // یا منطق نسخه‌بندی
                'pages_count' => $this->calculatePages($content),
            ]);

            event(new ContractStatusChanged($contract, 'legal_draft', $user));

            return $contract->fresh();
        });
    }

    /**
     * ارسال قرارداد تنظیم شده به پیمانکار برای امضا
     */
    public function sendToContractor(Contract $contract, User $user): Contract
    {
        return DB::transaction(function () use ($contract, $user) {
            if ($contract->status !== 'legal_draft') {
                throw new Exception('فقط قراردادهای تنظیم شده توسط حقوقی قابل ارسال به پیمانکار هستند.');
            }

            // بررسی اینکه متن قرارداد پر شده است
            if (empty($contract->content)) {
                throw new Exception('متن قرارداد باید قبل از ارسال به پیمانکار تنظیم شود.');
            }

            $contract->update(['status' => 'pending_contractor_sign']);

            event(new ContractStatusChanged($contract, 'pending_contractor_sign', $user));

            return $contract->fresh();
        });
    }

    /**
     * تغییر وضعیت پس از امضای پیمانکار به "در انتظار امضای اجرایی"
     * این متد معمولاً پس از موفقیت‌آمیز بودن فرآیند OTP پیمانکار فراخوانی می‌شود
     */
    public function markContractorSigned(Contract $contract): Contract
    {
        return DB::transaction(function () use ($contract) {
            if ($contract->status !== 'pending_contractor_sign') {
                throw new Exception('قرارداد در وضعیت امضای پیمانکار نیست.');
            }

            $contract->update(['status' => 'pending_executive_sign']);

            event(new ContractStatusChanged($contract, 'pending_executive_sign', null));

            return $contract->fresh();
        });
    }

    /**
     * ابلاغ قرارداد پس از امضای اجرایی
     */
    public function notifyContractor(Contract $contract, User $user): Contract
    {
        return DB::transaction(function () use ($contract, $user) {
            if ($contract->status !== 'pending_notification') {
                throw new Exception('قرارداد در وضعیت ابلاغ نیست.');
            }

            $contract->update(['status' => 'active']);

            event(new ContractStatusChanged($contract, 'active', $user));

            return $contract->fresh();
        });
    }

    /**
     * محاسبه تقریبی تعداد صفحات
     */
    private function calculatePages(string $html): int
    {
        $text = strip_tags($html);
        return max(1, (int) ceil(mb_strlen($text, 'UTF-8') / 1500));
    }
}
