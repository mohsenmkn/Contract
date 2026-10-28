<?php

namespace Modules\Amendment\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AmendmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amendment_number' => $this->amendment_number,
            'type' => $this->type,
            'type_label' => match($this->type) {
                'amount' => 'تغییر مبلغ',
                'extension' => 'تمدید مدت',
                'scope' => 'تغییر دامنه',
                'termination' => 'فسخ',
                default => $this->type,
            },
            'old_amount' => $this->old_amount ? (float) $this->old_amount : null,
            'new_amount' => $this->new_amount ? (float) $this->new_amount : null,
            'old_end_date' => $this->old_end_date?->format('Y-m-d'),
            'new_end_date' => $this->new_end_date?->format('Y-m-d'),
            'amount_change' => $this->amount_change,
            'days_change' => $this->days_change,
            'description' => $this->description,
            'status' => $this->status,
            'status_label' => match($this->status) {
                'draft' => 'پیش‌نویس',
                'pending' => 'در انتظار تایید',
                'approved' => 'تایید شده',
                'rejected' => 'رد شده',
                default => $this->status,
            },
            'contract' => new \Modules\Contract\App\Http\Resources\ContractResource($this->whenLoaded('contract')),
            'creator' => new \Modules\Contract\App\Http\Resources\UserResource($this->whenLoaded('creator')),
            'approver' => new \Modules\Contract\App\Http\Resources\UserResource($this->whenLoaded('approver')),
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
