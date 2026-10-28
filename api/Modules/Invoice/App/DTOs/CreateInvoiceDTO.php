<?php

namespace Modules\Invoice\App\DTOs;

class CreateInvoiceDTO
{
    public function __construct(
        public readonly int $contractId,
        public readonly int $period,
        public readonly string $fromDate,
        public readonly string $toDate,
        public readonly float $grossAmount,
        public readonly array $items,
        public readonly ?float $deductions = 0,
        public readonly ?float $taxAmount = 0,
        public readonly ?string $description = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            contractId: (int) $data['contract_id'],
            period: (int) $data['period'],
            fromDate: $data['from_date'],
            toDate: $data['to_date'],
            grossAmount: (float) $data['gross_amount'],
            items: $data['items'] ?? [],
            deductions: isset($data['deductions']) ? (float) $data['deductions'] : 0,
            taxAmount: isset($data['tax_amount']) ? (float) $data['tax_amount'] : 0,
            description: $data['description'] ?? null,
        );
    }
}
