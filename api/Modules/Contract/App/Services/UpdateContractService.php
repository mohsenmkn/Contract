<?php

namespace Modules\Contract\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Contract\App\Models\Contract;
use Modules\Contract\App\DTOs\UpdateContractDTO;
use Modules\Contract\App\Events\ContractUpdated;

class UpdateContractService
{
    public function execute(Contract $contract, UpdateContractDTO $dto): Contract
    {
        return DB::transaction(function () use ($contract, $dto) {
            if (!in_array($contract->status, ['draft', 'pending_internal_approval'])) {
                throw new \Exception('قرارداد در وضعیت فعلی قابل ویرایش نیست.');
            }

            $contract->update([
                'subject' => $dto->subject ?? $contract->subject,
                'party_id' => $dto->partyId ?? $contract->party_id,
                'start_date' => $dto->startDate ?? $contract->start_date,
                'end_date' => $dto->endDate ?? $contract->end_date,
                'description' => $dto->description ?? $contract->description,
            ]);

            if ($dto->items !== null) {
                $contract->items()->delete();
                foreach ($dto->items as $item) {
                    $quantity = (float) $item['quantity'];
                    $unitPrice = (float) $item['unit_price'];
                    $contract->items()->create([
                        'item_code' => $item['item_code'],
                        'description' => $item['description'],
                        'unit' => $item['unit'],
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $quantity * $unitPrice,
                    ]);
                }

                $newAmount = $contract->items()->sum('total_price');
                $contract->update([
                    'initial_amount' => $newAmount,
                    'current_amount' => $newAmount,
                ]);
            }

            event(new ContractUpdated($contract));

            return $contract->fresh(['party', 'items']);
        });
    }
}
