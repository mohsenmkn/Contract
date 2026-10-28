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
        } else {
            $query = Contract::query();
        }

        $query->with(['party', 'creator']);

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
