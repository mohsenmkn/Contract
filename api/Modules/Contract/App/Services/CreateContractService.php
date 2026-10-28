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
        private AlertSchedulerService $alertScheduler
    ) {}

    public function execute(CreateContractDTO $dto): Contract
    {
        return DB::transaction(function () use ($dto) {
            $contract = Contract::create([
                'contract_number' => $this->numberGenerator->generate($dto->type),
                'subject' => $dto->subject,
                'party_id' => $dto->partyId,
                'type' => $dto->type,
                'start_date' => $dto->startDate,
                'end_date' => $dto->endDate,
                'initial_amount' => $dto->amount,
                'current_amount' => $dto->amount,
                'currency' => $dto->currency ?? 'IRR',
                'description' => $dto->description,
                'content' => $dto->content ?? null,
                'created_by' => auth()->id(),
                'status' => 'draft',
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
                'is_active' => true,
            ]);
        }
    }
}
