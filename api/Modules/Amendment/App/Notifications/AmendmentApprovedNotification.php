<?php

namespace Modules\Amendment\App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Amendment\App\Models\Amendment;

class AmendmentApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Amendment $amendment
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('الحاقیه تایید شد')
            ->greeting('سلام ' . $notifiable->name)
            ->line('الحاقیه ' . $this->amendment->amendment_number . ' تایید شد.')
            ->line('نوع: ' . $this->getAmendmentTypeLabel())
            ->action('مشاهده الحاقیه', url('/amendments/' . $this->amendment->id))
            ->line('با تشکر از شما');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'amendment_approved',
            'amendment_id' => $this->amendment->id,
            'amendment_number' => $this->amendment->amendment_number,
            'message' => 'الحاقیه تایید شد',
        ];
    }

    private function getAmendmentTypeLabel(): string
    {
        return match($this->amendment->type) {
            'amount' => 'تغییر مبلغ',
            'extension' => 'تمدید مدت',
            'scope' => 'تغییر دامنه',
            'termination' => 'فسخ',
            default => $this->amendment->type,
        };
    }
}
