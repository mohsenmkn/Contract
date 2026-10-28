<?php

namespace Modules\Contract\App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Contract\App\Events\ContractSentToContractor;
use Modules\Contract\App\Notifications\ContractSignatureRequestNotification;

class NotifyContractorAboutSignature implements ShouldQueue
{
    public function handle(ContractSentToContractor $event): void
    {
        $contract = $event->contract;
        $signature = $event->signature;

        $contractor = $contract->party->user;

        if ($contractor) {
            $contractor->notify(new ContractSignatureRequestNotification($contract, $signature));
        }
    }
}
