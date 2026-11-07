<?php

namespace Modules\Contract\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Contract\App\Models\Contract;
use Modules\Contract\App\Events\ContractStatusChanged;
use Modules\Contract\App\Events\ContractUpdated;
use Modules\Auth\App\Models\User;
use Exception;

class ContractWorkflowService
{
    public function submitToContractReview(Contract $contract, User $user): Contract
    {
        return DB::transaction(function () use ($contract, $user) {
            if (!$user->can('contracts.submit_to_contract_unit')) {
                throw new Exception('شما مجاز به ارسال قرارداد به واحد قراردادها نیستید.');
            }
            if (!in_array($contract->status, ['draft', 'rejected_by_legal'], true)) {
                throw new Exception('فقط پیش‌نویس‌ها قابل ارسال به واحد قراردادها هستند.');
            }
            if ((int) $contract->requesting_user_id !== (int) $user->id || !$user->canAccessContract($contract)) {
                throw new Exception('شما مجاز به ارسال این قرارداد نیستید.');
            }

            $contract->changeStatus('pending_contract_review', $user, 'ارسال به واحد قراردادها برای بررسی');
            event(new ContractStatusChanged($contract, 'pending_contract_review', $user));

            return $contract->fresh();
        });
    }

    /**
     * 1. ارسال قرارداد از واحد به حقوقی
     */
    public function submitToLegal(Contract $contract, User $user): Contract
    {
        return DB::transaction(function () use ($contract, $user) {
            if (!$user->can('contracts.submit_to_legal')) {
                throw new Exception('شما مجاز به ارسال قرارداد به واحد حقوقی نیستید.');
            }
            $canSubmitDraft = $contract->status === 'draft';
            $canSendReviewed = $contract->status === 'pending_contract_review' && $user->can('contracts.review');
            if (!$canSubmitDraft && !$canSendReviewed) {
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
            if (!$user->can('contracts.draft')) {
                throw new Exception('فقط اعضای مجاز واحد حقوقی می‌توانند متن قرارداد را تنظیم کنند.');
            }
            $isFirstDraft = $contract->status === 'pending_legal_review';
            if (!$isFirstDraft && $contract->status !== 'legal_draft') {
                throw new Exception('قرارداد در وضعیت بررسی حقوقی نیست.');
            }

            if (empty($data['content'])) {
                throw new Exception('متن قرارداد الزامی است.');
            }

            // بروزرسانی فیلدهای قرارداد
            $updateData = [
                'content' => $data['content'],
                'content_version' => max(1, (int) $contract->content_version + 1),
                'pages_count' => $this->calculatePages($data['content']),
            ];

            // اگر فیلدهای دیگری هم ارسال شده
            if (isset($data['subject'])) $updateData['subject'] = $data['subject'];
            if (isset($data['start_date'])) $updateData['start_date'] = $data['start_date'];
            if (isset($data['end_date'])) $updateData['end_date'] = $data['end_date'];
            if (isset($data['amount'])) $updateData['current_amount'] = $data['amount'];
            if (isset($data['description'])) $updateData['description'] = $data['description'];

            $contract->update($updateData);

            // بار اول وارد وضعیت پیش‌نویس می‌شود؛ ذخیره‌های بعدی همین وضعیت را حفظ می‌کنند.
            if ($isFirstDraft) {
                $contract->changeStatus('legal_draft', $user, 'تنظیم پیش‌نویس حقوقی');
                event(new ContractStatusChanged($contract, 'legal_draft', $user));
            }

            event(new ContractUpdated($contract));

            return $contract->fresh(['items', 'party']);
        });
    }

    /**
     * 3. ارسال به پیمانکار برای امضا
     */
    public function returnToRequester(Contract $contract, User $user, string $comments): Contract
    {
        return DB::transaction(function () use ($contract, $user, $comments) {
            if (!$user->can('contracts.draft')) {
                throw new Exception('شما مجاز به بازگرداندن قرارداد به واحد درخواست‌کننده نیستید.');
            }
            if (!in_array($contract->status, ['pending_legal_review', 'legal_draft'], true)) {
                throw new Exception('قرارداد در مرحله بررسی حقوقی نیست.');
            }

            $contract->changeStatus('rejected_by_legal', $user, $comments);
            event(new ContractStatusChanged($contract, 'rejected_by_legal', $user));

            return $contract->fresh(['items', 'party']);
        });
    }

    public function updateGuaranteeRequirement(Contract $contract, User $user, bool $requiresGuarantee, ?string $guaranteeType, ?string $instrumentType, bool $requiresBoardSignature): Contract
    {
        return DB::transaction(function () use ($contract, $user, $requiresGuarantee, $guaranteeType, $instrumentType, $requiresBoardSignature) {
            $canReviewInContracts = $contract->status === 'pending_contract_review'
                && $user->can('contracts.review');
            $canReviewInLegal = in_array($contract->status, ['pending_legal_review', 'legal_draft'], true)
                && $user->can('contracts.draft');
            if (!$canReviewInContracts && !$canReviewInLegal) {
                throw new Exception('تغییر نیاز به ضمانت فقط در مرحله بررسی واحد قراردادها یا حقوقی مجاز است.');
            }

            $contract->update([
                'requires_guarantee' => $requiresGuarantee,
                'guarantee_type' => $requiresGuarantee ? $guaranteeType : null,
                'guarantee_instrument_type' => $requiresGuarantee ? $instrumentType : null,
                'requires_board_signature' => $requiresBoardSignature,
            ]);
            event(new ContractUpdated($contract));

            return $contract->fresh(['items', 'party']);
        });
    }

    public function sendToContractor(Contract $contract, User $user): Contract
    {
        return DB::transaction(function () use ($contract, $user) {
            if (!$user->can('contracts.send_to_contractor')) {
                throw new Exception('شما مجوز ارسال قرارداد برای پیمانکار را ندارید.');
            }
            if ($contract->status !== 'legal_draft') {
                throw new Exception('فقط قراردادهای با پیش‌نویس حقوقی تکمیل‌شده قابل ارسال به پیمانکار هستند.');
            }

            if (empty($contract->content)) {
                throw new Exception('متن قرارداد باید قبل از ارسال به پیمانکار تنظیم شود.');
            }

            if (!$contract->party_id) {
                throw new Exception('طرف قرارداد مشخص نشده است.');
            }

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

            // امضای پیمانکار توسط slot های مرحله‌ای ثبت می‌شود؛ انتقال وضعیت در SigningService انجام می‌شود.
            $contract->changeStatus('pending_unit_sign', $contractorUser, 'امضای پیمانکار ثبت شد');

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
    public function notifyContractor(Contract $contract, User $user, string $letterBody): Contract
    {
        return DB::transaction(function () use ($contract, $user, $letterBody) {
            if (!$user->can('contracts.notify')) {
                throw new Exception('شما مجوز ابلاغ قرارداد را ندارید.');
            }
            if ($contract->status !== 'pending_notification') {
                throw new Exception('قرارداد در وضعیت ابلاغ نیست.');
            }
            if ($contract->requires_guarantee && !$contract->guarantees()->where('status', 'active')->exists()) {
                throw new Exception('ابلاغ امکان‌پذیر نیست؛ واحد مالی هنوز ضمانت دریافت‌شده از پیمانکار را در سامانه ثبت نکرده است.');
            }

            $contract->loadMissing('party');
            $notifiedAt = now();
            $contractorName = $contract->party?->name ?: 'پیمانکار';
            $employerName = config('app.contract_employer_name', config('app.name'));
            $letterBody = $this->sanitizeNotificationLetterBody($letterBody);
            if (trim(strip_tags($letterBody)) === '') {
                throw new Exception('متن ابلاغیه نمی‌تواند خالی باشد.');
            }
            $contract->update([
                'notified_at' => $notifiedAt,
                'notification_letter_data' => [
                    'template_version' => 1,
                    'contract_number' => $contract->contract_number,
                    'subject' => $contract->subject,
                    'contractor_name' => $contractorName,
                    'employer_name' => $employerName,
                    'body' => $letterBody,
                    'initial_amount' => (string) $contract->initial_amount,
                    'current_amount' => (string) $contract->current_amount,
                    'currency' => $contract->currency,
                    'start_date' => $contract->start_date?->format('Y-m-d'),
                    'end_date' => $contract->end_date?->format('Y-m-d'),
                    'notified_at' => $notifiedAt->toDateTimeString(),
                    'generated_by' => $user->name,
                ],
            ]);

            $contract->changeStatus('active', $user, 'ابلاغ به پیمانکار');

            event(new ContractStatusChanged($contract, 'active', $user));

            return $contract->fresh();
        });
    }

    private function sanitizeNotificationLetterBody(string $html): string
    {
        $html = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $html) ?? '';
        $html = preg_replace('/<!--[\s\S]*?-->/', '', $html) ?? '';
        $html = strip_tags($html, '<p><br><strong><b><em><i><u><s><ol><ul><li><blockquote><h1><h2><h3>');

        // متن ویرایشگر را فقط با تگ‌های ساختاری و بدون attribute ذخیره می‌کنیم.
        return preg_replace_callback('/<(\/?)([a-z][a-z0-9]*)\b[^>]*>/i', function ($match) {
            return '<' . $match[1] . strtolower($match[2]) . '>';
        }, $html) ?? '';
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
