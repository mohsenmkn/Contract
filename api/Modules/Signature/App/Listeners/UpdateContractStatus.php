<?php

namespace Modules\Signature\App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Signature\App\Events\DocumentSigned;
use Modules\Contract\App\Models\Contract;

class UpdateContractStatus implements ShouldQueue
{
    public function handle(DocumentSigned $event): void
    {
        $signature = $event->signature;

        if ($signature->signable instanceof Contract) {
            $contract = $signature->signable;

            // بررسی اینکه آیا تمام امضاها انجام شده است
            $pendingSignatures = $contract->signatures()
                ->where('status', 'pending')
                ->count();

            if ($pendingSignatures === 0 && $signature->status === 'signed') {
                $contract->update([
                    'status' => 'active',
                    'approved_by' => $signature->signer_id,
                    'approved_at' => now(),
                ]);
            }
        }
    }
}
