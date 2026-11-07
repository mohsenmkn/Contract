<?php

namespace Modules\Contract\App\Services;

use Illuminate\Validation\ValidationException;
use Modules\Contract\App\Models\Contract;
use Modules\Contract\App\Models\ContractItem;

class EmployerFrameworkService
{
    /** Validate a downstream contract and reserve its items against an active employer contract. */
    public function validateAllocation(
        ?int $employerContractId,
        string $contractType,
        string $currency,
        array $items,
        ?int $currentContractId = null,
        ?float $declaredAmount = null,
    ): void {
        $mappedItemIds = array_filter(array_column($items, 'employer_contract_item_id'));

        if (!$employerContractId) {
            if ($contractType === 'upstream') {
                if ($mappedItemIds) {
                    throw ValidationException::withMessages([
                        'items' => 'ردیف‌های قرارداد کارفرمایی نمی‌توانند به ردیف دیگری متصل شوند.',
                    ]);
                }
                return;
            }

            if ($mappedItemIds) {
                throw ValidationException::withMessages([
                    'parent_contract_id' => 'برای اتصال ردیف‌ها، ابتدا قرارداد کارفرمایی را انتخاب کنید.',
                ]);
            }

            return;
        }

        if ($contractType !== 'downstream') {
            throw ValidationException::withMessages([
                'parent_contract_id' => 'فقط قرارداد پیمانکاری می‌تواند در چهارچوب قرارداد کارفرمایی ثبت شود.',
            ]);
        }

        if (!$items) {
            throw ValidationException::withMessages([
                'items' => 'برای قرارداد پیمانکاری در چهارچوب، دست‌کم یک ردیف الزامی است.',
            ]);
        }

        $employer = Contract::query()->lockForUpdate()->find($employerContractId);
        if (!$employer || $employer->type !== 'upstream' || !in_array($employer->status, ['active', 'expiring'], true)) {
            throw ValidationException::withMessages([
                'parent_contract_id' => 'قرارداد کارفرمایی انتخاب‌شده معتبر یا فعال نیست.',
            ]);
        }

        if ($employer->currency !== $currency) {
            throw ValidationException::withMessages([
                'parent_contract_id' => 'واحد پول قرارداد پیمانکاری باید با قرارداد کارفرمایی یکسان باشد.',
            ]);
        }

        if (count($mappedItemIds) !== count($items)) {
            throw ValidationException::withMessages([
                'items' => 'تمام ردیف‌های قرارداد پیمانکاری باید به یک ردیف از قرارداد کارفرمایی متصل شوند.',
            ]);
        }

        $employerItems = $employer->items()
            ->whereIn('id', array_unique($mappedItemIds))
            ->where('is_active', true)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($employerItems->count() !== count(array_unique($mappedItemIds))) {
            throw ValidationException::withMessages([
                'items' => 'یکی از ردیف‌های انتخاب‌شده متعلق به قرارداد کارفرمایی انتخابی نیست یا فعال نیست.',
            ]);
        }

        $remainingByItem = $this->remainingQuantities($employer, $currentContractId);
        $requestedByItem = [];
        $requestedAmount = 0.0;

        foreach ($items as $index => $item) {
            $sourceId = (int) $item['employer_contract_item_id'];
            $sourceItem = $employerItems->get($sourceId);
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];

            if (mb_strtolower(trim((string) $item['unit']), 'UTF-8') !== mb_strtolower(trim((string) $sourceItem->unit), 'UTF-8')) {
                throw ValidationException::withMessages([
                    "items.{$index}.unit" => "واحد ردیف باید با ردیف متناظر کارفرمایی ({$sourceItem->unit}) یکسان باشد.",
                ]);
            }

            if (round($unitPrice, 2) > round((float) $sourceItem->unit_price, 2)) {
                throw ValidationException::withMessages([
                    "items.{$index}.unit_price" => 'بهای واحد پیمانکاری نمی‌تواند از بهای واحد قرارداد کارفرمایی بیشتر باشد.',
                ]);
            }

            $requestedByItem[$sourceId] = ($requestedByItem[$sourceId] ?? 0) + $quantity;
            $requestedAmount += $quantity * $unitPrice;
        }

        foreach ($requestedByItem as $sourceId => $quantity) {
            if ($quantity > ($remainingByItem[$sourceId] ?? 0) + 0.000001) {
                $sourceItem = $employerItems->get($sourceId);
                throw ValidationException::withMessages([
                    'items' => "مقدار انتخاب‌شده برای ردیف «{$sourceItem->description}» از مقدار باقیمانده قرارداد کارفرمایی بیشتر است.",
                ]);
            }
        }

        if ($declaredAmount !== null && abs($requestedAmount - $declaredAmount) > 0.01) {
            throw ValidationException::withMessages([
                'amount' => 'مبلغ کل قرارداد باید با جمع مبالغ ردیف‌های متصل به قرارداد کارفرمایی برابر باشد.',
            ]);
        }

        $requestedAmount = $declaredAmount ?? $requestedAmount;
        $remainingContractAmount = $this->remainingAmount($employer, $currentContractId);
        if ($requestedAmount > $remainingContractAmount + 0.01) {
            throw ValidationException::withMessages([
                'items' => 'مبلغ کل قرارداد پیمانکاری از مبلغ باقیمانده قرارداد کارفرمایی بیشتر است.',
            ]);
        }
    }

    /** @return array<int, float> remaining quantity keyed by employer contract item ID */
    public function remainingQuantities(Contract $employer, ?int $excludeContractId = null): array
    {
        $itemIds = $employer->items()->pluck('id');
        if ($itemIds->isEmpty()) {
            return [];
        }

        $allocated = ContractItem::query()
            ->whereIn('employer_contract_item_id', $itemIds)
            ->whereHas('contract', function ($query) use ($employer, $excludeContractId) {
                $query->where('parent_contract_id', $employer->id)
                    ->where('type', 'downstream')
                    ->where('status', '!=', 'terminated')
                    ->when($excludeContractId, fn ($q) => $q->where('id', '!=', $excludeContractId));
            })
            ->selectRaw('employer_contract_item_id, SUM(quantity) as allocated_quantity')
            ->groupBy('employer_contract_item_id')
            ->pluck('allocated_quantity', 'employer_contract_item_id');

        $remaining = [];
        foreach ($employer->items as $item) {
            $remaining[$item->id] = max(0, (float) $item->quantity - (float) ($allocated[$item->id] ?? 0));
        }

        return $remaining;
    }

    public function remainingAmount(Contract $employer, ?int $excludeContractId = null): float
    {
        $allocatedAmount = Contract::query()
            ->where('parent_contract_id', $employer->id)
            ->where('type', 'downstream')
            ->where('status', '!=', 'terminated')
            ->when($excludeContractId, fn ($q) => $q->where('id', '!=', $excludeContractId))
            ->sum('current_amount');

        return max(0, (float) $employer->current_amount - (float) $allocatedAmount);
    }
}
