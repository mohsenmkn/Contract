<?php

namespace Modules\Invoice\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Invoice\App\Models\Invoice;
use Modules\Invoice\App\Events\InvoiceApproved;

class ApproveInvoiceService
{
    public function execute(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            if ($invoice->status !== 'submitted') {
                throw new \Exception('فقط صورت‌وضعیت‌های ارسالی قابل تایید هستند.');
            }

            $invoice->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            event(new InvoiceApproved($invoice));

            return $invoice;
        });
    }
}
