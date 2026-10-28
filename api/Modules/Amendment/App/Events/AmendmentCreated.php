<?php

namespace Modules\Amendment\App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Amendment\App\Models\Amendment;

class AmendmentCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Amendment $amendment
    ) {}
}
