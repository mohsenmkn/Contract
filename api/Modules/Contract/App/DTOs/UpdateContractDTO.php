<?php

namespace Modules\Contract\App\DTOs;

class UpdateContractDTO
{
    public function __construct(
        public readonly ?string $subject = null,
        public readonly ?int $partyId = null,
        public readonly ?string $startDate = null,
        public readonly ?string $endDate = null,
        public readonly ?array $items = null,
        public readonly ?string $description = null,
        public readonly ?string $type = null,
        public readonly ?string $content = null,
        public readonly ?float $amount = null,
        public readonly ?int $parentContractId = null,
        public readonly bool $parentContractSpecified = false,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            subject: $data['subject'] ?? null,
            partyId: isset($data['party_id']) ? (int) $data['party_id'] : null,
            startDate: $data['start_date'] ?? null,
            endDate: $data['end_date'] ?? null,
            items: $data['items'] ?? null,
            description: $data['description'] ?? null,
            type: $data['type'] ?? null,
            content: $data['content'] ?? null,
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
            parentContractId: isset($data['parent_contract_id']) ? (int) $data['parent_contract_id'] : null,
            parentContractSpecified: array_key_exists('parent_contract_id', $data),
        );
    }
}
