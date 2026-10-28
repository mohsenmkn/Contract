<?php

namespace Modules\Contract\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('contracts.create');
    }

    public function rules(): array
    {
        return [
            'subject' => 'required|string|max:255',
            'party_id' => 'required|exists:parties,id',
            'type' => 'required|in:upstream,downstream',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'description' => 'nullable|string',
            'content' => 'nullable|string',  // ✅ جدید - متن قرارداد
            'items' => 'nullable|array',
            'items.*.item_code' => 'required_with:items|string|max:50',
            'items.*.description' => 'required_with:items|string',
            'items.*.unit' => 'required_with:items|string|max:50',
            'items.*.quantity' => 'required_with:items|numeric|min:0',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'موضوع قرارداد الزامی است.',
            'party_id.required' => 'طرف قرارداد الزامی است.',
            'party_id.exists' => 'طرف قرارداد نامعتبر است.',
            'type.in' => 'نوع قرارداد باید upstream یا downstream باشد.',
            'end_date.after' => 'تاریخ پایان باید بعد از تاریخ شروع باشد.',
            'amount.min' => 'مبلغ قرارداد نمی‌تواند منفی باشد.',
        ];
    }
}
