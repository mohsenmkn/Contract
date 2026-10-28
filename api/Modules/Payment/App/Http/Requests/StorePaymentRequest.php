<?php

namespace Modules\Payment\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('payments.create');
    }

    public function rules(): array
    {
        return [
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'method' => 'required|in:bank_transfer,check,cash,online',
            'currency' => 'nullable|string|max:10',
            'reference_number' => 'nullable|string|max:100',
            'check_number' => 'nullable|string|max:50',
            'check_due_date' => 'nullable|date',
            'status' => 'nullable|in:pending,completed,cancelled,bounced',
            'description' => 'nullable|string',
        ];
    }
}
