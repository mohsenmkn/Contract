<?php

namespace Modules\Guarantee\App\Services;

use Modules\Guarantee\App\Models\Guarantee;

class GuaranteeNumberGenerator
{
    public function generate(): string
    {
        $year = now()->format('y');
        $last = Guarantee::whereYear('created_at', now()->year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $last
            ? ((int) last(explode('-', $last->guarantee_number))) + 1
            : 1;

        return sprintf('GRN-%s-%04d', $year, $sequence);
    }
}
