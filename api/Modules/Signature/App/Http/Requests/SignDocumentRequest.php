<?php

namespace Modules\Signature\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SignDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization checked in service
    }

    public function rules(): array
    {
        return [
            'action' => 'required|in:sign,reject',
            'comments' => 'nullable|string|max:1000',
        ];
    }
}
