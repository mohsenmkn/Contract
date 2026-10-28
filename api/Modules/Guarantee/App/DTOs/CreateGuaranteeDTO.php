<?php

namespace Modules\Guarantee\App\DTOs;

class CreateGuaranteeDTO
{
    public function __construct(
        public readonly int $contractId,
        public readonly string $type,
        public readonly float $amount,
        public readonly string $bankName,
        public readonly string $issueDate,
        public readonly string $expiryDate,
        public readonly ?string $currency = 'IRR',
        public readonly ?string $bankBranch = null,
        public readonly ?string $description = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            contractId: (int) $data['contract_id'],
            type: $data['type'],
            amount: (float) $data['amount'],
            bankName: $data['bank_name'],
            issueDate: $data['issue_date'],
            expiryDate: $data['expiry_date'],
            currency: $data['currency'] ?? 'IRR',
            bankBranch: $data['bank_branch'] ?? null,
            description: $data['description'] ?? null,
        );
    }
}
