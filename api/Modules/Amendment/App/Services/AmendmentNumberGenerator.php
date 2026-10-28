<?php

namespace Modules\Amendment\App\Services;

use Modules\Contract\App\Models\Contract;
use Modules\Amendment\App\Models\Amendment;

class AmendmentNumberGenerator
{
    public function generate(Contract $contract): string
    {
        $count = $contract->amendments()->count() + 1;
        return $contract->contract_number . '-A' . str_pad($count, 2, '0', STR_PAD_LEFT);
    }
}
