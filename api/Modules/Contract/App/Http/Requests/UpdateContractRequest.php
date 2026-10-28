<?php

namespace Modules\Contract\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('contracts.update');
    }

    public function rules(): array
    {
        return [
            'subject' => 'sometimes|string|max:255',
            'party_id' => 'sometimes|exists:parties,id',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'description' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.item_code' => 'required_with:items|string|max:50',
            'items.*.description' => 'required_with:items|string',
            'items.*.unit' => 'required_with:items|string|max:50',
            'items.*.quantity' => 'required_with:items|numeric|min:0',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
        ];
    }
}
