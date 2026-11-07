<?php


namespace Modules\Contract\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Contract\App\Models\Contract;
use Modules\Contract\App\Models\ContractItem;
use Modules\Contract\App\DTOs\CreateContractDTO;
use Modules\Alert\App\Services\AlertSchedulerService;
use Modules\Contract\App\Events\ContractCreated;

class CreateContractService
{
    public function __construct(
        private ContractNumberGenerator $numberGenerator,
        private AlertSchedulerService $alertScheduler,
        private EmployerFrameworkService $employerFramework
    ) {}

    public function execute(CreateContractDTO $dto): Contract
    {
        return DB::transaction(function () use ($dto) {
            $user = auth()->user();
            $unit = $user?->getUnit();
            if ($dto->type === 'upstream' && !$user?->can('contracts.review')) {
                throw new \DomainException('ثبت قراردادهای کارفرما فقط برای واحد حقوقی و قراردادها مجاز است.');
            }
            $unitId = $dto->unitId ?? $unit?->id;
            if ($dto->type === 'downstream' && !$unitId) {
                throw new \DomainException('حساب کاربری شما به یک واحد سازمانی متصل نیست.');
            }

            $this->employerFramework->validateAllocation(
                $dto->parentContractId,
                $dto->type,
                $dto->currency ?? 'IRR',
                $dto->items,
                null,
                $dto->amount,
            );

            $contract = Contract::create([
                'contract_number' => $this->numberGenerator->generate($dto->type),
                'subject' => $dto->subject,
                'party_id' => $dto->partyId,
                'type' => $dto->type,
                'parent_contract_id' => $dto->parentContractId,
                'start_date' => $dto->startDate,
                'end_date' => $dto->endDate,
                'initial_amount' => $dto->amount,
                'current_amount' => $dto->amount,
                'currency' => $dto->currency ?? 'IRR',
                'description' => $dto->description,
                'content' => $dto->content ?? null,
                'unit_id' => $unitId,
                'requesting_user_id' => $user->id,
                'requires_guarantee' => false,
                'created_by' => auth()->id(),
                'status' => $dto->type === 'upstream' ? 'active' : 'draft',
            ]);

            // محاسبه تعداد صفحات
            if ($contract->hasContent()) {
                $contract->update([
                    'pages_count' => $contract->calculatePagesCount(),
                ]);
            }

            if (!empty($dto->items)) {
                $this->createItems($contract, $dto->items);
            }

            $this->alertScheduler->schedule($contract);

            event(new ContractCreated($contract));

            return $contract->load(['party', 'items', 'alerts']);
        });
    }

    private function createItems(Contract $contract, array $items): void
    {
        foreach ($items as $item) {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];

            $contract->items()->create([
                'item_code' => $item['item_code'],
                'description' => $item['description'],
                'unit' => $item['unit'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $quantity * $unitPrice,
                'employer_contract_item_id' => $item['employer_contract_item_id'] ?? null,
                'is_active' => true,
            ]);
        }
    }
}
