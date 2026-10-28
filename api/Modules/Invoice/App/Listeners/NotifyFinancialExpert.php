<?php

namespace Modules\Invoice\App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Auth\App\Models\User;
use Modules\Invoice\App\Events\InvoiceSubmitted;
use Modules\Invoice\App\Notifications\InvoiceSubmittedNotification;


class NotifyFinancialExpert implements ShouldQueue
{
    public function handle(InvoiceSubmitted $event): void
    {
        $invoice = $event->invoice;

        // پیدا کردن کاربران با نقش کارشناس مالی
        $financialExperts = User::role('financial_expert')->get();

        foreach ($financialExperts as $expert) {
            $expert->notify(new InvoiceSubmittedNotification($invoice));
        }
    }
}
