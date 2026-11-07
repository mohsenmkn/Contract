<?php

namespace Modules\Contract\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth()->user();
        return $user?->can('contracts.create')
            && ($this->input('type') !== 'upstream' || $user->can('contracts.review'));
    }

    public function rules(): array
    {
        return [
            'subject' => 'required|string|max:255',
            'party_id' => 'required|exists:parties,id',
            'unit_id' => 'nullable|exists:organizational_units,id',
            'parent_contract_id' => 'nullable|integer|exists:contracts,id',
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
            'items.*.employer_contract_item_id' => 'nullable|integer|exists:contract_items,id',
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'موضوع قرارداد الزامی است.',
            'party_id.required' => 'طرف قرارداد الزامی است.',
            'party_id.exists' => 'طرف قرارداد نامعتبر است.',
            'type.in' => 'نوع قرارداد باید کارفرمایی یا پیمانکاری باشد.',
            'end_date.after' => 'تاریخ پایان باید بعد از تاریخ شروع باشد.',
            'amount.min' => 'مبلغ قرارداد نمی‌تواند منفی باشد.',
            'items.*.item_code' => 'کد ایتم الزامیست',
        ];
    }
}
