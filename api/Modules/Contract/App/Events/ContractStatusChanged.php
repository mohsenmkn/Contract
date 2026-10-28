<?php

namespace Modules\Contract\App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Contract\App\Models\Contract;
use Modules\Auth\App\Models\User;

class ContractStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Contract $contract,
        public string $newStatus,
        public ?User $actor // کاربری که تغییر را انجام داده (ممکن است null باشد برای تغییرات سیستمی)
    ) {}
}
