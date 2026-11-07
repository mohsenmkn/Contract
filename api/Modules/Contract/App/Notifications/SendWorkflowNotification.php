<?php

namespace Modules\Contract\App\Notifications;
use Modules\Contract\App\Events\ContractStatusChanged;
use Modules\Contract\App\Models\Contract;
use Modules\Contract\App\Models\ContractStatusHistory;
use Illuminate\Support\Facades\Log;

class SendWorkflowNotification
{
    public function handle(ContractStatusChanged $event): void
    {
        $contract = $event->contract;
        $status = $event->newStatus;

        // منطق ارسال اعلان بر اساس وضعیت جدید
        switch ($status) {
            case 'pending_legal_review':
                // اعلان به تمام کاربران با نقش legal_officer
                $this->notifyRole('legal_officer', "قرارداد جدید {$contract->contract_number} برای بررسی حقوقی ارسال شد.");
                break;

            case 'legal_draft':
                // اعلان به نماینده واحد درخواست‌کننده (جهت اطلاع)
                if ($contract->requestingUser) {
                    $this->notifyUser($contract->requestingUser, "قرارداد {$contract->contract_number} توسط واحد حقوقی تنظیم شد.");
                }
                break;

            case 'rejected_by_legal':
                $requester = $contract->requestingUser;
                if ($requester) {
                    $comments = ContractStatusHistory::query()
                        ->where('contract_id', $contract->id)
                        ->where('to_status', 'rejected_by_legal')
                        ->latest('id')
                        ->value('comments');
                    $requester->notify(new ContractRejectedByLegalNotification($contract, $comments));
                }
                break;

            case 'pending_contractor_sign':
                // اعلان به پیمانکار (ایجاد رکورد در جدول Signatures توسط سرویس SendToContractor انجام می‌شود، اینجا فقط نوتیفیکیشن)
                Log::info("Notification sent to contractor for contract {$contract->id}");
                break;

            case 'pending_executive_sign':
                // مدیرعامل همیشه امضا می‌کند؛ عضو هیئت‌مدیره فقط در صورت نیاز قرارداد.
                $this->notifyRole('ceo', "قرارداد {$contract->contract_number} جهت امضا ارسال شد.");
                if ($contract->requires_board_signature) {
                    $this->notifyRole('board_member', "قرارداد {$contract->contract_number} جهت امضا ارسال شد.");
                }
                break;

            case 'pending_notification':
                // اعلان به واحد حقوقی برای ابلاغ نهایی
                $this->notifyRole('legal_officer', "قرارداد {$contract->contract_number} امضا شد و آماده ابلاغ است.");
                break;

            case 'active':
                // اعلان به همه ذینفعان
                if ($contract->requestingUser) {
                    $this->notifyUser($contract->requestingUser, "قرارداد {$contract->contract_number} فعال و ابلاغ شد.");
                }
                break;
        }
    }

    private function notifyRole(string $roleName, string $message): void
    {
        // پیاده‌سازی ارسال نوتیفیکیشن به کاربران دارای نقش مشخص
        // مثال: User::role($roleName)->each->notify(...)
        Log::info("Notifying role [{$roleName}]: {$message}");
    }

    private function notifyUser($user, string $message): void
    {
        // مثال: $user->notify(...)
        Log::info("Notifying user [{$user->name}]: {$message}");
    }
}
