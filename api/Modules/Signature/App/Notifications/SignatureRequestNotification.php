<?php

namespace Modules\Signature\App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Signature\App\Models\Signature;

class SignatureRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Signature $signature
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $signable = $this->signature->signable;
        $documentType = $this->getDocumentType();

        return (new MailMessage)
            ->subject('درخواست امضای سند')
            ->greeting('سلام ' . $notifiable->name)
            ->line('لطفاً ' . $documentType . ' را بررسی و امضا نمایید.')
            ->action('مشاهده و امضا', url('/signatures/' . $this->signature->id))
            ->line('با تشکر از شما');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'signature_request',
            'signature_id' => $this->signature->id,
            'signable_type' => $this->signature->signable_type,
            'signable_id' => $this->signature->signable_id,
            'message' => 'درخواست امضای سند',
        ];
    }

    private function getDocumentType(): string
    {
        return match($this->signature->signable_type) {
            'Modules\Contract\Models\Contract' => 'قرارداد',
            'Modules\Amendment\Models\Amendment' => 'الحاقیه',
            default => 'سند',
        };
    }
}
