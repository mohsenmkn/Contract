<?php

namespace Modules\Signature\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SignatureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $roleLabel = $this->role
            ? (config('signing.role_labels.' . $this->role) ?? $this->role)
            : null;

        return [
            'id' => $this->id,
            'signable_type' => $this->signable_type,
            'signable_id' => $this->signable_id,
            'signer_id' => $this->signer_id,
            'signer_type' => $this->signer_type,
            'group' => $this->group,
            'role' => $this->role,
            'role_label' => $roleLabel,
            'kind' => $this->kind,
            'signer_type_label' => $this->signer_type === 'internal' ? 'داخلی' : 'پیمانکار',
            'status' => $this->status,
            'status_label' => match($this->status) {
                'pending' => 'در انتظار',
                'signed' => 'امضا شده',
                'rejected' => 'رد شده',
                default => $this->status,
            },
            'comments' => $this->comments,
            'signature_image' => $this->status === 'signed'
                ? app(\Modules\Signature\App\Services\SignatureImageService::class)->dataUri($this->resource)
                : null,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'signed_at' => $this->signed_at?->format('Y-m-d H:i:s'),
            'signer' => new \Modules\Contract\App\Http\Resources\UserResource($this->whenLoaded('signer')),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
