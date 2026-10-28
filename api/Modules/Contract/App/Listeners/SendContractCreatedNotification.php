<?php

namespace Modules\Contract\App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Contract\App\Events\ContractCreated;
use Modules\Contract\App\Notifications\ContractCreatedNotification;

class SendContractCreatedNotification implements ShouldQueue
{
    public function handle(ContractCreated $event): void
    {
        $contract = $event->contract;

        // ارسال نوتیفیکیشن به مدیر قرارداد
        if ($contract->creator) {
            $contract->creator->notify(new ContractCreatedNotification($contract));
        }
    }
}
