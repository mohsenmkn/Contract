<?php

namespace Modules\Invoice\App\Services;

use Modules\Invoice\App\Models\Invoice;

class InvoiceNumberGenerator
{
    public function generate(): string
    {
        $year = now()->format('y');
        $lastInvoice = Invoice::whereYear('created_at', now()->year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastInvoice
            ? ((int) last(explode('-', $lastInvoice->invoice_number))) + 1
            : 1;

        return sprintf('INV-%s-%04d', $year, $sequence);
    }
}
