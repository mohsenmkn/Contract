<?php

namespace Modules\Contract\App\Policies;

use Modules\Auth\App\Models\User;
use Modules\Contract\App\Models\Contract;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContractPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Contract $contract): bool
    {
        // پیمانکار فقط قراردادهای خودش را ببیند
        if ($user->isContractor()) {
            return $contract->party_id === $user->party->id;
        }

        return $user->can('read contracts');
    }

    public function create(User $user): bool
    {
        if ($user->isContractor()) {
            return false; // پیمانکار نمی‌تواند قرارداد ایجاد کند
        }

        return $user->can('create contracts');
    }

    public function update(User $user, Contract $contract): bool
    {
        if ($user->isContractor()) {
            return false;
        }

        if (!in_array($contract->status, ['draft', 'pending_internal_approval'])) {
            return false;
        }

        return $user->can('update contracts');
    }

    public function delete(User $user, Contract $contract): bool
    {
        if ($user->isContractor()) {
            return false;
        }

        if ($contract->status !== 'draft') {
            return false;
        }

        return $user->can('delete contracts');
    }

    public function submit(User $user, Contract $contract): bool
    {
        if ($user->isContractor()) {
            return false;
        }

        return $user->can('submit contracts');
    }

    public function approve(User $user, Contract $contract): bool
    {
        if ($user->isContractor()) {
            return false;
        }

        return $user->can('approve contracts');
    }
}
