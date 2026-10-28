<?php

namespace Modules\Amendment\App\DTOs;

use Modules\Contract\App\Models\Contract;

class CreateAmendmentDTO
{
    public function __construct(
        public readonly Contract $contract,
        public readonly string $type,
        public readonly ?float $newAmount = null,
        public readonly ?string $newEndDate = null,
        public readonly ?string $description = null,
    ) {}

    public static function fromArray(array $data, Contract $contract): self
    {
        return new self(
            contract: $contract,
            type: $data['type'],
            newAmount: isset($data['new_amount']) ? (float) $data['new_amount'] : null,
            newEndDate: $data['new_end_date'] ?? null,
            description: $data['description'] ?? null,
        );
    }
}
