<?php

namespace Modules\Contract\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employer_contract_item_id' => $this->employer_contract_item_id,
            'employer_contract_item' => $this->whenLoaded('employerContractItem', fn () => $this->employerContractItem ? [
                'id' => $this->employerContractItem->id,
                'item_code' => $this->employerContractItem->item_code,
                'description' => $this->employerContractItem->description,
                'unit' => $this->employerContractItem->unit,
                'quantity' => (float) $this->employerContractItem->quantity,
                'unit_price' => (float) $this->employerContractItem->unit_price,
            ] : null),
            'remaining_framework_quantity' => $this->when(
                $this->resource->getAttribute('remaining_framework_quantity') !== null,
                fn () => (float) $this->resource->getAttribute('remaining_framework_quantity'),
            ),
            'item_code' => $this->item_code,
            'description' => $this->description,
            'unit' => $this->unit,
            'quantity' => (float) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'total_price' => (float) $this->total_price,
            'is_active' => $this->is_active,
        ];
    }
}
