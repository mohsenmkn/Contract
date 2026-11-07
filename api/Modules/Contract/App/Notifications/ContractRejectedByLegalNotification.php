<?php

namespace Modules\Contract\App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Contract\App\Models\Contract;

class ContractRejectedByLegalNotification extends Notification
{
    use Queueable;

    public function __construct(public Contract $contract, public ?string $comments = null) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'contract_rejected_by_legal',
            'title' => 'قرارداد توسط واحد حقوقی رد شد',
            'message' => "قرارداد {$this->contract->contract_number} برای اصلاح به واحد شما بازگشت.",
            'comments' => $this->comments,
            'contract_id' => $this->contract->id,
            'contract_number' => $this->contract->contract_number,
            'subject' => $this->contract->subject,
            'url' => "/contracts/{$this->contract->id}",
        ];
    }
}
