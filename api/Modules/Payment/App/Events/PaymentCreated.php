<?php

namespace Modules\Payment\App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Payment\App\Models\Payment;

class PaymentCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Payment $payment
    ) {}
}
