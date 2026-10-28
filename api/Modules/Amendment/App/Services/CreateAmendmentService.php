<?php

namespace Modules\Amendment\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Amendment\App\Models\Amendment;
use Modules\Amendment\App\DTOs\CreateAmendmentDTO;
use Modules\Amendment\App\Services\AmendmentNumberGenerator;

class CreateAmendmentService
{
    public function __construct(
        private AmendmentNumberGenerator $numberGenerator
    ) {}

    public function execute(CreateAmendmentDTO $dto): Amendment
    {
        return DB::transaction(function () use ($dto) {
            $contract = $dto->contract;

            if ($contract->status !== 'active') {
                throw new \Exception('فقط قراردادهای فعال قابل الحاقیه هستند.');
            }

            $amendment = Amendment::create([
                'contract_id' => $contract->id,
                'amendment_number' => $this->numberGenerator->generate($contract),
                'type' => $dto->type,
                'old_amount' => $dto->type === 'amount' ? $contract->current_amount : null,
                'new_amount' => $dto->newAmount,
                'old_end_date' => $dto->type === 'extension' ? $contract->end_date : null,
                'new_end_date' => $dto->newEndDate,
                'description' => $dto->description,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            return $amendment;
        });
    }
}
