<?php

namespace Modules\Amendment\App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Amendment\App\Events\AmendmentApproved;
use Modules\Amendment\App\Notifications\AmendmentApprovedNotification;

class NotifyContractManager implements ShouldQueue
{
    public function handle(AmendmentApproved $event): void
    {
        $amendment = $event->amendment;
        $contract = $amendment->contract;

        // ارسال نوتیفیکیشن به مدیر قرارداد
        if ($contract->creator) {
            $contract->creator->notify(new AmendmentApprovedNotification($amendment));
        }
    }
}
