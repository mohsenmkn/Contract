<?php

namespace Modules\Guarantee\App\Services;

use Illuminate\Support\Facades\DB;
use DomainException;
use Modules\Contract\App\Models\Contract;
use Modules\Contract\App\Services\GuaranteeGateService;
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
            $contract = Contract::whereKey($dto->contractId)->lockForUpdate()->firstOrFail();
            app(GuaranteeGateService::class)->assertCanManage($contract, auth()->user());
            if (!$contract->guarantee_instrument_type) {
                throw new DomainException('نوع ضمانت این قرارداد هنوز توسط واحد حقوقی و قراردادها مشخص نشده است.');
            }
            if ($dto->type !== $contract->guarantee_type || $dto->instrumentType !== $contract->guarantee_instrument_type) {
                throw new DomainException('نوع ضمانت و سند باید مطابق موارد تعیین‌شده در قرارداد باشند.');
            }

            $guarantee = Guarantee::create([
                'contract_id' => $dto->contractId,
                'guarantee_number' => $this->numberGenerator->generate(),
                'type' => $contract->guarantee_type,
                'instrument_type' => $contract->guarantee_instrument_type,
                'instrument_number' => $dto->instrumentNumber,
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
