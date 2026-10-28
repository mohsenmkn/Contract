<?php

namespace Modules\Guarantee\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Guarantee\App\Models\Guarantee;
use Modules\Guarantee\App\DTOs\CreateGuaranteeDTO;
use Modules\Guarantee\App\Services\GuaranteeNumberGenerator;
use Modules\Alert\App\Services\AlertSchedulerService;

class CreateGuaranteeService
{
    public function __construct(
        private GuaranteeNumberGenerator $numberGenerator,
        private AlertSchedulerService $alertScheduler
    ) {}

    public function execute(CreateGuaranteeDTO $dto): Guarantee
    {
        return DB::transaction(function () use ($dto) {
            $guarantee = Guarantee::create([
                'contract_id' => $dto->contractId,
                'guarantee_number' => $this->numberGenerator->generate(),
                'type' => $dto->type,
                'amount' => $dto->amount,
                'currency' => $dto->currency ?? 'IRR',
                'bank_name' => $dto->bankName,
                'bank_branch' => $dto->bankBranch,
                'issue_date' => $dto->issueDate,
                'expiry_date' => $dto->expiryDate,
                'status' => 'active',
                'description' => $dto->description,
                'created_by' => auth()->id(),
            ]);

            $this->alertScheduler->scheduleGuaranteeAlert($guarantee);

            return $guarantee;
        });
    }
}
