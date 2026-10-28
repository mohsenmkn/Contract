<?php

namespace Modules\Organization\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,

            // نماینده واحد
            'representative' => $this->whenLoaded('representative', function () {
                $rep = $this->representative;
                if (!$rep) {
                    return null;
                }

                return [
                    'id' => $rep->id,
                    'user_id' => $rep->user_id,
                    'user' => $this->whenLoaded('representative.user', function () use ($rep) {
                        return [
                            'id' => $rep->user->id,
                            'name' => $rep->user->name,
                            'mobile' => $rep->user->mobile,
                            'email' => $rep->user->email,
                        ];
                    }),
                    'is_active' => (bool) $rep->is_active,
                    'assigned_at' => $rep->assigned_at?->format('Y-m-d H:i:s'),
                ];
            }),

            // تعداد قراردادهای واحد
            'contracts_count' => $this->whenCounted('contracts'),

            // وضعیت نماینده
            'has_representative' => $this->hasRepresentative(),
            'representative_name' => $this->getRepresentativeName(),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
