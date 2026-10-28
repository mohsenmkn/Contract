<?php

namespace Modules\Payment\App\Services;

use Modules\Payment\App\Models\Payment;

class PaymentNumberGenerator
{
    public function generate(): string
    {
        $year = now()->format('y');
        $lastPayment = Payment::whereYear('created_at', now()->year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastPayment
            ? ((int) last(explode('-', $lastPayment->payment_number))) + 1
            : 1;

        return sprintf('PAY-%s-%04d', $year, $sequence);
    }
}
