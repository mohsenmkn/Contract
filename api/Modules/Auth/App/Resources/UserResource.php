<?php

namespace Modules\Auth\App\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'national_code' => $this->national_code,
            'personnel_code' => $this->personnel_code,
            'employee_type' => $this->employee_type,
            'employee_type_label' => $this->employee_type === 'contractor' ? 'پیمانکار' : 'پرسنل',
            'party_id' => $this->party_id,
            'party' => $this->whenLoaded('party', function () {
                return [
                    'id' => $this->party->id,
                    'name' => $this->party->name,
                    'national_id' => $this->party->national_id,
                    'role' => $this->party->role,
                ];
            }),
            'is_active' => (bool) $this->is_active,
            'avatar' => $this->avatar,
            'settings' => $this->settings,
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->pluck('name');
            }),
            'permissions' => $this->whenLoaded('roles', function () {
                return $this->getAllPermissions()->pluck('name')->unique()->values();
            }),
            'can_login' => $this->canLogin(),
            'is_contractor' => $this->isContractor(),
            'is_linked_contractor' => $this->isLinkedContractor(),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
