<?php

namespace Modules\Party\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'national_id' => $this->national_id,
            'registration_number' => $this->registration_number,
            'economic_code' => $this->economic_code,
            'type' => $this->type,
            'type_label' => $this->type === 'legal' ? 'حقوقی' : 'حقیقی',
            'role' => $this->role,
            'role_label' => match($this->role) {
                'employer' => 'کارفرما',
                'contractor' => 'پیمانکار',
                'both' => 'هر دو',
                default => $this->role,
            },
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'bank_name' => $this->bank_name,
            'bank_account' => $this->bank_account,
            'iban' => $this->iban,
            'is_active' => $this->is_active,
            'contracts_count' => $this->contracts_count ?? $this->contracts->count(),
        ];
    }
}
