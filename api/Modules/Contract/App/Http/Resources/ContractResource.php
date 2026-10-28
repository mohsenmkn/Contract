<?php

namespace Modules\Contract\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Alert\App\Http\Resources\AlertResource;
use Modules\Amendment\App\Http\Resources\AmendmentResource;
use Modules\Contract\App\Models\Contract;
use Modules\Guarantee\App\Http\Resources\GuaranteeResource;
use Modules\Invoice\App\Http\Resources\InvoiceResource;
use Modules\Party\App\Http\Resources\PartyResource;
use Modules\Signature\App\Http\Resources\SignatureResource;

class ContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contract_number' => $this->contract_number,
            'subject' => $this->subject,
            'type' => $this->type,
            'type_label' => $this->type === 'upstream' ? 'کارفرما' : 'پیمانکار',
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'initial_amount' => (float) $this->initial_amount,
            'current_amount' => (float) $this->current_amount,
            'currency' => $this->currency,
            'description' => $this->description,

            'content' => $this->content,
            'pages_count' => $this->pages_count ?? $this->calculatePagesCount(),
            'content_version' => $this->content_version ?? '1.0',
            'has_content' => $this->resource->hasContent(), // فراخوانی از مدل
            'content_summary' => $this->resource->getContentSummary(200), // فراخوانی از مدل


            'party' => new PartyResource($this->whenLoaded('party')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'approver' => new UserResource($this->whenLoaded('approver')),
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'items' => ContractItemResource::collection($this->whenLoaded('items')),
            'amendments' => AmendmentResource::collection($this->whenLoaded('amendments')),
            'signatures' => SignatureResource::collection($this->whenLoaded('signatures')),
            'alerts' => AlertResource::collection($this->whenLoaded('alerts')),
            'invoices' => InvoiceResource::collection($this->whenLoaded('invoices')),
            'guarantees' => GuaranteeResource::collection($this->whenLoaded('guarantees')),
            'amendments_count' => $this->amendments_count ?? $this->amendments?->count() ?? 0,
            'is_expiring' => $this->isExpiring(),
            'days_remaining' => $this->end_date?->diffInDays(now(), false),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),


            'status_color' => $this->status_color,
            'status_icon' => $this->status_icon,
            'unit' => new \Modules\Organization\App\Http\Resources\UnitResource($this->whenLoaded('unit')),
            'requesting_user' => new UserResource($this->whenLoaded('requestingUser')),
            'parent_contract' => new self($this->whenLoaded('parentContract')),
            'approved_by' => new UserResource($this->whenLoaded('approver')),
            'notified_at' => $this->notified_at?->format('Y-m-d H:i:s'),
            'can_transition_to' => $this->getAvailableTransitions(),



        ];
    }

    private function getStatusLabel(): string
    {
        return match($this->status) {
            'draft' => 'پیش‌نویس',
            'pending_internal_approval' => 'در انتظار تایید داخلی',
            'pending_contractor_sign' => 'در انتظار امضای پیمانکار',
            'active' => 'فعال',
            'expiring' => 'در حال اتمام',
            'completed' => 'خاتمه یافته',
            'terminated' => 'فسخ شده',
            default => $this->status,
        };
    }

    private function getAvailableTransitions(): array
    {
        $allowed = Contract::TRANSITIONS[$this->status] ?? [];
        return array_map(fn($s) => [
            'status' => $s,
            'label' => Contract::STATUSES[$s] ?? $s,
        ], $allowed);
    }
}
