<?php

namespace Modules\Invoice\App\Services;

use Modules\Invoice\App\Models\Invoice;
use Modules\Invoice\App\Events\InvoiceSubmitted;

class SubmitInvoiceService
{
    public function execute(Invoice $invoice): Invoice
    {
        if ($invoice->status !== 'draft') {
            throw new \Exception('فقط صورت‌وضعیت‌های پیش‌نویس قابل ارسال هستند.');
        }

        $invoice->update([
            'status' => 'submitted',
            'submitted_by' => auth()->id(),
        ]);

        event(new InvoiceSubmitted($invoice));

        return $invoice;
    }
}
