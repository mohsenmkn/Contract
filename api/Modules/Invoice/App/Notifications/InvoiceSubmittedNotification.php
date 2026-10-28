<?php

namespace Modules\Invoice\App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Invoice\App\Models\Invoice;

class InvoiceSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Invoice $invoice
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('صورت‌وضعیت جدید ارسال شد')
            ->greeting('سلام ' . $notifiable->name)
            ->line('صورت‌وضعیت ' . $this->invoice->invoice_number . ' ارسال شد.')
            ->line('مبلغ خالص: ' . number_format($this->invoice->net_amount) . ' ریال')
            ->action('مشاهده صورت‌وضعیت', url('/invoices/' . $this->invoice->id))
            ->line('لطفاً بررسی و تایید نمایید.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'invoice_submitted',
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'net_amount' => $this->invoice->net_amount,
            'message' => 'صورت‌وضعیت جدید ارسال شد',
        ];
    }
}
