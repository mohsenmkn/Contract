<?php

namespace Modules\Payment\App\DTOs;

use Modules\Invoice\App\Models\Invoice;

class CreatePaymentDTO
{
    public function __construct(
        public readonly Invoice $invoice,
        public readonly float $amount,
        public readonly string $paymentDate,
        public readonly string $method,
        public readonly ?string $currency = 'IRR',
        public readonly ?string $referenceNumber = null,
        public readonly ?string $checkNumber = null,
        public readonly ?string $checkDueDate = null,
        public readonly ?string $status = 'pending',
        public readonly ?string $description = null,
    ) {}

    public static function fromArray(array $data, Invoice $invoice): self
    {
        return new self(
            invoice: $invoice,
            amount: (float) $data['amount'],
            paymentDate: $data['payment_date'],
            method: $data['method'],
            currency: $data['currency'] ?? 'IRR',
            referenceNumber: $data['reference_number'] ?? null,
            checkNumber: $data['check_number'] ?? null,
            checkDueDate: $data['check_due_date'] ?? null,
            status: $data['status'] ?? 'pending',
            description: $data['description'] ?? null,
        );
    }
}
