<?php

namespace Modules\Guarantee\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGuaranteeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('guarantees.create');
    }

    public function rules(): array
    {
        return [
            'contract_id' => 'required|exists:contracts,id',
            'type' => 'required|in:performance,advance_payment,bid,retention',
            'amount' => 'required|numeric|min:0',
            'bank_name' => 'required|string|max:100',
            'bank_branch' => 'nullable|string|max:100',
            'issue_date' => 'required|date',
            'expiry_date' => 'required|date|after:issue_date',
            'currency' => 'nullable|string|max:10',
            'description' => 'nullable|string',
        ];
    }
}
