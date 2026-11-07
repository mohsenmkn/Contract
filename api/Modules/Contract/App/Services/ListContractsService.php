<?php

namespace Modules\Contract\App\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Contract\App\Models\Contract;

class ListContractsService
{
    public function execute(array $filters): LengthAwarePaginator
    {
        $user = auth()->user();

        // اگر کاربر پیمانکار است، فقط قراردادهای خودش را ببیند
        if ($user->isContractor()) {
            $query = $user->getAccessibleContracts();
        } elseif ($user->hasAnyRole(['super-admin', 'contract_manager', 'legal_expert'])) {
            $query = Contract::query();
        } elseif ($user->hasAnyRole(['unit_contract_staff', 'unit_head', 'unit_manager', 'unit_deputy'])) {
            $unitId = \Illuminate\Support\Facades\DB::table('contract_unit_signers')
                ->where('user_id', $user->id)
                ->value('unit_id') ?? $user->getUnit()?->id;

            $query = Contract::where('unit_id', $unitId ?? 0);
        } elseif ($user->isUnitRepresentative()) {
            $query = Contract::where('unit_id', $user->getUnit()->id);
        } else {
            $query = Contract::query();
        }

        $query->with(['party', 'creator'])->withExists([
            'guarantees as has_guarantee' => fn ($guaranteeQuery) => $guaranteeQuery->where('status', 'active'),
        ]);

        // فیلترها
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['party_id']) && !$user->isContractor()) {
            $query->where('party_id', $filters['party_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('contract_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['expiring_soon'])) {
            $query->expiringSoon((int) $filters['expiring_soon']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $query->orderBy($sortBy, $sortDir)
            ->paginate($filters['per_page'] ?? 15);
    }
}
