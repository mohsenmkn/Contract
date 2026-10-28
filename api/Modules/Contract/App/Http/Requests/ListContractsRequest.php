<?php

namespace Modules\Contract\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListContractsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('contracts.read');
    }

    public function rules(): array
    {
        return [
            'type' => 'nullable|in:upstream,downstream',
            'status' => 'nullable|in:draft,pending_internal_approval,pending_contractor_sign,active,expiring,completed,terminated',
            'party_id' => 'nullable|exists:parties,id',
            'search' => 'nullable|string|max:100',
            'expiring_soon' => 'nullable|integer|min:1|max:365',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'sort_by' => 'nullable|in:contract_number,subject,start_date,end_date,created_at',
            'sort_dir' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
