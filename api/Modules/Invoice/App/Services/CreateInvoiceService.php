<?php

namespace Modules\Invoice\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Invoice\App\Models\Invoice;
use Modules\Invoice\App\DTOs\CreateInvoiceDTO;
use Modules\Invoice\App\Services\InvoiceNumberGenerator;

class CreateInvoiceService
{
    public function __construct(
        private InvoiceNumberGenerator $numberGenerator
    ) {}

    public function execute(CreateInvoiceDTO $dto): Invoice
    {
        return DB::transaction(function () use ($dto) {
            $invoice = Invoice::create([
                'contract_id' => $dto->contractId,
                'invoice_number' => $this->numberGenerator->generate(),
                'invoice_period' => $dto->period,
                'from_date' => $dto->fromDate,
                'to_date' => $dto->toDate,
                'gross_amount' => $dto->grossAmount,
                'deductions' => $dto->deductions ?? 0,
                'tax_amount' => $dto->taxAmount ?? 0,
                'net_amount' => $dto->grossAmount - ($dto->deductions ?? 0) + ($dto->taxAmount ?? 0),
                'description' => $dto->description,
                'status' => 'draft',
                'submitted_by' => auth()->id(),
            ]);

            if (!empty($dto->items)) {
                foreach ($dto->items as $item) {
                    $quantity = (float) $item['quantity'];
                    $unitPrice = (float) $item['unit_price'];
                    $invoice->items()->create([
                        'contract_item_id' => $item['contract_item_id'] ?? null,
                        'description' => $item['description'],
                        'unit' => $item['unit'],
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $quantity * $unitPrice,
                    ]);
                }
            }

            return $invoice->load(['contract', 'items']);
        });
    }
}
