<?php

namespace Modules\Contract\App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Contract\App\Models\Contract;
use Modules\Signature\App\Models\Signature;

class ContractSignatureRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Contract $contract,
        public Signature $signature
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'contract_signature_request',
            'contract_id' => $this->contract->id,
            'contract_number' => $this->contract->contract_number,
            'subject' => $this->contract->subject,
            'signature_id' => $this->signature->id,
            'message' => 'قرارداد جدید برای امضا ارسال شده است',
        ];
    }
}
