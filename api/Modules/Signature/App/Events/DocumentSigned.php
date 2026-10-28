<?php

namespace Modules\Signature\App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Signature\App\Models\Signature;

class DocumentSigned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Signature $signature
    ) {}
}
