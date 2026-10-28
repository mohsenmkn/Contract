<?php

namespace Modules\Payment\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_number' => $this->payment_number,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'payment_date' => $this->payment_date->format('Y-m-d'),
            'method' => $this->method,
            'method_label' => match($this->method) {
                'bank_transfer' => 'حواله بانکی',
                'check' => 'چک',
                'cash' => 'نقدی',
                'online' => 'آنلاین',
                default => $this->method,
            },
            'reference_number' => $this->reference_number,
            'check_number' => $this->check_number,
            'check_due_date' => $this->check_due_date?->format('Y-m-d'),
            'status' => $this->status,
            'status_label' => match($this->status) {
                'pending' => 'در انتظار',
                'completed' => 'تکمیل شده',
                'cancelled' => 'لغو شده',
                'bounced' => 'برگشتی',
                default => $this->status,
            },
            'description' => $this->description,
            'invoice' => new InvoiceResource($this->whenLoaded('invoice')),
            'creator' => new \Modules\Contract\App\Http\Resources\UserResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
