<?php

namespace Modules\Guarantee\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuaranteeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'guarantee_number' => $this->guarantee_number,
            'type' => $this->type,
            'type_label' => match($this->type) {
                'performance' => 'حسن انجام کار',
                'advance_payment' => 'پیش‌پرداخت',
                'bid' => 'شرکت در مناقصه',
                'retention' => 'کسورات',
                default => $this->type,
            },
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'bank_name' => $this->bank_name,
            'bank_branch' => $this->bank_branch,
            'issue_date' => $this->issue_date->format('Y-m-d'),
            'expiry_date' => $this->expiry_date->format('Y-m-d'),
            'status' => $this->status,
            'status_label' => match($this->status) {
                'active' => 'فعال',
                'expired' => 'منقضی شده',
                'released' => 'آزاد شده',
                'called' => 'ضبط شده',
                default => $this->status,
            },
            'description' => $this->description,
            'file_path' => $this->file_path,
            'contract' => new \Modules\Contract\App\Http\Resources\ContractResource($this->whenLoaded('contract')),
            'creator' => new \Modules\Contract\App\Http\Resources\UserResource($this->whenLoaded('creator')),
            'is_expiring' => $this->isExpiring(),
            'days_remaining' => $this->status === 'active' ? $this->expiry_date->diffInDays(now(), false) : null,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
