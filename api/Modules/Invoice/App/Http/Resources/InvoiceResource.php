<?php

namespace Modules\Invoice\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'invoice_period' => $this->invoice_period,
            'from_date' => $this->from_date->format('Y-m-d'),
            'to_date' => $this->to_date->format('Y-m-d'),
            'gross_amount' => (float) $this->gross_amount,
            'deductions' => (float) $this->deductions,
            'tax_amount' => (float) $this->tax_amount,
            'net_amount' => (float) $this->net_amount,
            'status' => $this->status,
            'status_label' => match($this->status) {
                'draft' => 'پیش‌نویس',
                'submitted' => 'ارسال شده',
                'approved' => 'تایید شده',
                'rejected' => 'رد شده',
                'paid' => 'پرداخت شده',
                default => $this->status,
            },
            'description' => $this->description,
            'contract' => new \Modules\Contract\App\Http\Resources\ContractResource($this->whenLoaded('contract')),
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'payments' => \Modules\Payment\App\Http\Resources\PaymentResource::collection($this->whenLoaded('payments')),
            'paid_amount' => $this->paid_amount,
            'remaining_amount' => $this->remaining_amount,
            'is_fully_paid' => $this->isFullyPaid(),
            'submitter' => new \Modules\Contract\App\Http\Resources\UserResource($this->whenLoaded('submitter')),
            'approver' => new \Modules\Contract\App\Http\Resources\UserResource($this->whenLoaded('approver')),
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
