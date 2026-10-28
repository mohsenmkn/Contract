<?php

namespace Modules\Amendment\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAmendmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('amendments.create');
    }

    public function rules(): array
    {
        return [
            'contract_id' => 'required|exists:contracts,id',
            'type' => 'required|in:amount,extension,scope,termination',
            'new_amount' => 'required_if:type,amount|nullable|numeric|min:0',
            'new_end_date' => 'required_if:type,extension|nullable|date',
            'description' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'new_amount.required_if' => 'برای الحاقیه مبلغ، مبلغ جدید الزامی است.',
            'new_end_date.required_if' => 'برای الحاقیه تمدید، تاریخ جدید الزامی است.',
        ];
    }
}
