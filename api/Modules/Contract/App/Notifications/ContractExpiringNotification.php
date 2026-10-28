<?php

namespace Modules\Contract\App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Alert\App\Models\ContractAlert;

class ContractExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ContractAlert $alert
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $contract = $this->alert->contract;
        $daysRemaining = $contract->end_date->diffInDays(now());

        return (new MailMessage)
            ->subject('هشدار: قرارداد در حال اتمام است')
            ->greeting('سلام ' . $notifiable->name)
            ->line('قرارداد ' . $contract->contract_number . ' تا ' . $daysRemaining . ' روز دیگر به پایان می‌رسد.')
            ->line('موضوع: ' . $contract->subject)
            ->line('تاریخ پایان: ' . $contract->end_date->format('Y-m-d'))
            ->action('مشاهده قرارداد', url('/contracts/' . $contract->id))
            ->line('لطفاً اقدامات لازم را انجام دهید.');
    }

    public function toArray($notifiable): array
    {
        $contract = $this->alert->contract;
        $daysRemaining = $contract->end_date->diffInDays(now());

        return [
            'type' => 'contract_expiring',
            'contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'days_remaining' => $daysRemaining,
            'message' => "قرارداد تا {$daysRemaining} روز دیگر به پایان می‌رسد",
        ];
    }
}
