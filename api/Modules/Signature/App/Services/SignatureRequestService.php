<?php

namespace Modules\Signature\App\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Signature\App\Models\Signature;
use Modules\Party\App\Models\Party;

class SignatureRequestService
{
    public function requestFromContractor(Model $signable, Party $contractor): Signature
    {
        return Signature::create([
            'signable_type' => get_class($signable),
            'signable_id' => $signable->id,
            'signer_id' => $contractor->user_id,
            'signer_type' => 'contractor',
            'status' => 'pending',
        ]);
    }

    public function requestFromInternal(Model $signable, int $userId): Signature
    {
        return Signature::create([
            'signable_type' => get_class($signable),
            'signable_id' => $signable->id,
            'signer_id' => $userId,
            'signer_type' => 'internal',
            'status' => 'pending',
        ]);
    }
}
