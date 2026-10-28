<?php

namespace App\Traits;

trait HasContractorAccess
{
    public function isContractor(): bool
    {
        return $this->hasRole('contractor')
            && $this->party
            && $this->party->role === 'contractor';
    }

    public function getAccessibleContracts()
    {
        if ($this->isContractor()) {
            return $this->party->contracts();
        }

        return \Modules\Contract\App\Models\Contract::query();
    }

    public function canAccessContract($contract): bool
    {
        if ($this->isContractor()) {
            return $contract->party_id === $this->party->id;
        }

        return true;
    }
}
