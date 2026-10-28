<?php

namespace Modules\Contract\App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Contract\App\Models\Contract;

class ContractCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Contract $contract
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('قرارداد جدید ایجاد شد')
            ->greeting('سلام ' . $notifiable->name)
            ->line('قرارداد جدید با شماره ' . $this->contract->contract_number . ' ایجاد شد.')
            ->line('موضوع: ' . $this->contract->subject)
            ->line('مبلغ: ' . number_format($this->contract->current_amount) . ' ریال')
            ->action('مشاهده قرارداد', url('/contracts/' . $this->contract->id))
            ->line('با تشکر از شما');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'contract_created',
            'contract_id' => $this->contract->id,
            'contract_number' => $this->contract->contract_number,
            'subject' => $this->contract->subject,
            'message' => 'قرارداد جدید ایجاد شد',
        ];
    }
}
