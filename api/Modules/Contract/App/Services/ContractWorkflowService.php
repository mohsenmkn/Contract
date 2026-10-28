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
     * 1. ارسال قرارداد از واحد به حقوقی
     */
    public function submitToLegal(Contract $contract, User $user): Contract
    {
        return DB::transaction(function () use ($contract, $user) {
            if ($contract->status !== 'draft') {
                throw new Exception('فقط قراردادهای پیش‌نویس قابل ارسال به حقوقی هستند.');
            }

            if ($contract->requesting_user_id !== $user->id && !$user->can('contracts.review')) {
                throw new Exception('شما مجاز به ارسال این قرارداد نیستید.');
            }

            $contract->changeStatus('pending_legal_review', $user, 'ارسال به واحد حقوقی برای بررسی');

            event(new ContractStatusChanged($contract, 'pending_legal_review', $user));

            return $contract->fresh();
        });
    }

    /**
     * 2. تنظیم پیش‌نویس حقوقی (تکمیل متن و جزئیات)
     */
    public function createLegalDraft(Contract $contract, User $user, array $data): Contract
    {
        return DB::transaction(function () use ($contract, $user, $data) {
            if ($contract->status !== 'pending_legal_review') {
                throw new Exception('قرارداد در وضعیت بررسی حقوقی نیست.');
            }

            if (empty($data['content'])) {
                throw new Exception('متن قرارداد الزامی است.');
            }

            // بروزرسانی فیلدهای قرارداد
            $updateData = [
                'content' => $data['content'],
                'content_version' => '1.0',
                'pages_count' => $this->calculatePages($data['content']),
            ];

            // اگر فیلدهای دیگری هم ارسال شده
            if (isset($data['subject'])) $updateData['subject'] = $data['subject'];
            if (isset($data['start_date'])) $updateData['start_date'] = $data['start_date'];
            if (isset($data['end_date'])) $updateData['end_date'] = $data['end_date'];
            if (isset($data['amount'])) $updateData['current_amount'] = $data['amount'];
            if (isset($data['description'])) $updateData['description'] = $data['description'];

            $contract->update($updateData);

            // تغییر وضعیت
            $contract->changeStatus('legal_draft', $user, 'تنظیم پیش‌نویس حقوقی');

            event(new ContractStatusChanged($contract, 'legal_draft', $user));

            return $contract->fresh(['items', 'party']);
        });
    }

    /**
     * 3. ارسال به پیمانکار برای امضا
     */
    public function sendToContractor(Contract $contract, User $user): Contract
    {
        return DB::transaction(function () use ($contract, $user) {
            if ($contract->status !== 'legal_draft') {
                throw new Exception('فقط قراردادهای با پیش‌نویس حقوقی تکمیل‌شده قابل ارسال به پیمانکار هستند.');
            }

            if (empty($contract->content)) {
                throw new Exception('متن قرارداد باید قبل از ارسال به پیمانکار تنظیم شود.');
            }

            if (!$contract->party_id) {
                throw new Exception('طرف قرارداد مشخص نشده است.');
            }

            // ایجاد Signature برای پیمانکار
            $this->createContractorSignature($contract);

            // تغییر وضعیت
            $contract->changeStatus('pending_contractor_sign', $user, 'ارسال به پیمانکار برای امضا');

            event(new ContractStatusChanged($contract, 'pending_contractor_sign', $user));

            return $contract->fresh();
        });
    }

    /**
     * 4. ثبت امضای پیمانکار (پس از OTP)
     */
    public function markContractorSigned(Contract $contract, User $contractorUser): Contract
    {
        return DB::transaction(function () use ($contract, $contractorUser) {
            if ($contract->status !== 'pending_contractor_sign') {
                throw new Exception('قرارداد در وضعیت امضای پیمانکار نیست.');
            }

            // بررسی اینکه پیمانکار واقعاً امضا کرده
            $signature = $contract->signatures()
                ->where('signer_id', $contractorUser->id)
                ->where('signer_type', 'contractor')
                ->where('status', 'signed')
                ->first();

            if (!$signature) {
                throw new Exception('امضای پیمانکار ثبت نشده است.');
            }

            // تغییر وضعیت
            $contract->changeStatus('pending_executive_sign', null, 'امضای پیمانکار ثبت شد');

            event(new ContractStatusChanged($contract, 'pending_executive_sign', $contractorUser));

            return $contract->fresh();
        });
    }

    /**
     * 5. ثبت امضای مدیرعامل/هیئت مدیره
     */
    public function markExecutiveSigned(Contract $contract, User $user, string $role): Contract
    {
        return DB::transaction(function () use ($contract, $user, $role) {
            if ($contract->status !== 'pending_executive_sign') {
                throw new Exception('قرارداد در وضعیت امضای اجرایی نیست.');
            }

            // ثبت در executive_signatures
            \Modules\Signature\App\Models\ExecutiveSignature::create([
                'contract_id' => $contract->id,
                'signer_id' => $user->id,
                'signer_role' => $role,
                'signed_at' => now(),
                'signature_hash' => hash('sha256', $contract->id . $user->id . now()->timestamp . $role),
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            // تغییر وضعیت
            $contract->update([
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            $contract->changeStatus('pending_notification', $user, "امضای {$role} ثبت شد");

            event(new ContractStatusChanged($contract, 'pending_notification', $user));

            return $contract->fresh();
        });
    }

    /**
     * 6. ابلاغ به پیمانکار
     */
    public function notifyContractor(Contract $contract, User $user): Contract
    {
        return DB::transaction(function () use ($contract, $user) {
            if ($contract->status !== 'pending_notification') {
                throw new Exception('قرارداد در وضعیت ابلاغ نیست.');
            }

            $contract->update([
                'notified_at' => now(),
            ]);

            $contract->changeStatus('active', $user, 'ابلاغ به پیمانکار');

            event(new ContractStatusChanged($contract, 'active', $user));

            return $contract->fresh();
        });
    }

    /**
     * ایجاد Signature برای پیمانکار
     */
    private function createContractorSignature(Contract $contract): void
    {
        $party = $contract->party;
        if (!$party || !$party->user_id) {
            throw new Exception('پیمانکار به حساب کاربری متصل نیست.');
        }

        // بررسی تکراری نبودن
        $exists = \Modules\Signature\App\Models\Signature::where('signable_type', Contract::class)
            ->where('signable_id', $contract->id)
            ->where('signer_id', $party->user_id)
            ->where('signer_type', 'contractor')
            ->where('status', 'pending')
            ->exists();

        if (!$exists) {
            \Modules\Signature\App\Models\Signature::create([
                'signable_type' => Contract::class,
                'signable_id' => $contract->id,
                'signer_id' => $party->user_id,
                'signer_type' => 'contractor',
                'status' => 'pending',
            ]);
        }
    }

    /**
     * محاسبه تعداد صفحات
     */
    private function calculatePages(string $html): int
    {
        $text = strip_tags($html);
        return max(1, (int) ceil(mb_strlen($text, 'UTF-8') / 1500));
    }
}
