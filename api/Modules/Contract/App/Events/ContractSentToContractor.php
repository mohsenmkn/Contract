<?php

namespace Modules\Contract\App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Contract\App\Models\Contract;
use Modules\Signature\App\Models\Signature;

class ContractSentToContractor
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Contract $contract,
        public Signature $signature
    ) {}
}
