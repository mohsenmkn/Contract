<?php

namespace Modules\Contract\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Contract\App\Models\Contract;
use Modules\Contract\App\DTOs\UpdateContractDTO;
use Modules\Contract\App\Events\ContractUpdated;

class UpdateContractService
{
    public function __construct(private EmployerFrameworkService $employerFramework)
    {
    }

    public function execute(Contract $contract, UpdateContractDTO $dto): Contract
    {
        return DB::transaction(function () use ($contract, $dto) {
            $user = auth()->user();
            if ((int) $contract->requesting_user_id !== (int) $user?->id && !$user?->can('contracts.review')) {
                throw new \DomainException('فقط ثبت‌کننده درخواست یا واحد قراردادها می‌تواند پیش‌نویس را ویرایش کند.');
            }
            $isDraft = in_array($contract->status, ['draft', 'rejected_by_legal'], true)
                && (int) $contract->requesting_user_id === (int) $user?->id;
            $isContractReview = $contract->status === 'pending_contract_review' && $user?->can('contracts.review');
            if (!$isDraft && !$isContractReview) {
                throw new \Exception('قرارداد در وضعیت فعلی قابل ویرایش نیست.');
            }

            $parentContractId = $dto->parentContractSpecified
                ? $dto->parentContractId
                : $contract->parent_contract_id;
            $itemsForValidation = $dto->items ?? $contract->items()->get()->map(fn ($item) => [
                'item_code' => $item->item_code,
                'description' => $item->description,
                'unit' => $item->unit,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'employer_contract_item_id' => $dto->parentContractSpecified && !$parentContractId
                    ? null
                    : $item->employer_contract_item_id,
            ])->all();

            $this->employerFramework->validateAllocation(
                $parentContractId ? (int) $parentContractId : null,
                $dto->type ?? $contract->type,
                $contract->currency,
                $itemsForValidation,
                $contract->id,
                $dto->amount,
            );

            $contract->update([
                'subject' => $dto->subject ?? $contract->subject,
                'type' => $dto->type ?? $contract->type,
                'parent_contract_id' => $parentContractId,
                'party_id' => $dto->partyId ?? $contract->party_id,
                'start_date' => $dto->startDate ?? $contract->start_date,
                'end_date' => $dto->endDate ?? $contract->end_date,
                'description' => $dto->description ?? $contract->description,
                'content' => $dto->content ?? $contract->content,
                'content_version' => $dto->content !== null && $dto->content !== $contract->content
                    ? (string) (max(1, (int) $contract->content_version) + 1)
                    : $contract->content_version,
                'pages_count' => $dto->content !== null
                    ? (trim(strip_tags($dto->content)) === '' ? 0 : max(1, (int) ceil(mb_strlen(strip_tags($dto->content), 'UTF-8') / 1500)))
                    : $contract->pages_count,
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
                        'employer_contract_item_id' => $item['employer_contract_item_id'] ?? null,
                    ]);
                }

                $newAmount = $contract->items()->sum('total_price');
                $contract->update([
                    'initial_amount' => $newAmount,
                    'current_amount' => $newAmount,
                ]);
            } elseif ($dto->parentContractSpecified && !$parentContractId) {
                $contract->items()->update(['employer_contract_item_id' => null]);
            } elseif ($dto->amount !== null) {
                $contract->update([
                    'initial_amount' => $dto->amount,
                    'current_amount' => $dto->amount,
                ]);
            }

            event(new ContractUpdated($contract));

            return $contract->fresh(['party', 'items']);
        });
    }
}
