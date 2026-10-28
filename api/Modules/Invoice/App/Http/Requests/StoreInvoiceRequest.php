<?php

namespace Modules\Invoice\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('invoices.create');
    }

    public function rules(): array
    {
        return [
            'contract_id' => 'required|exists:contracts,id',
            'period' => 'required|integer|min:1',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after:from_date',
            'gross_amount' => 'required|numeric|min:0',
            'deductions' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.contract_item_id' => 'nullable|exists:contract_items,id',
            'items.*.description' => 'required_with:items|string',
            'items.*.unit' => 'required_with:items|string|max:50',
            'items.*.quantity' => 'required_with:items|numeric|min:0',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
        ];
    }
}
