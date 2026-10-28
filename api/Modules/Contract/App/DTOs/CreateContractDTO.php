<?php

namespace Modules\Contract\App\DTOs;

class CreateContractDTO
{
    public function __construct(
        public readonly string $subject,
        public readonly int $partyId,
        public readonly string $type,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly float $amount,
        public readonly array $items,
        public readonly ?string $currency = 'IRR',
        public readonly ?string $description = null,
        public readonly ?string $content = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            subject: $data['subject'],
            partyId: (int) $data['party_id'],
            type: $data['type'],
            startDate: $data['start_date'],
            endDate: $data['end_date'],
            amount: (float) $data['amount'],
            items: $data['items'] ?? [],
            currency: $data['currency'] ?? 'IRR',
            description: $data['description'] ?? null,
            content: $data['content'] ?? null,
        );
    }
}
