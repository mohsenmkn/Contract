<?php

namespace Modules\Invoice\App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Invoice\App\Models\Invoice;

class InvoiceSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Invoice $invoice
    ) {}
}
