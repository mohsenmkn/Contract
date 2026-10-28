<?php

namespace Modules\Contract\App\Services;

use Modules\Contract\App\Models\Contract;

class ContractNumberGenerator
{
    public function generate(string $type): string
    {
        $prefix = $type === 'upstream' ? 'UP' : 'DN';
        $year = now()->format('y');

        $lastContract = Contract::whereYear('created_at', now()->year)
            ->where('contract_number', 'like', "{$prefix}-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastContract
            ? ((int) last(explode('-', $lastContract->contract_number))) + 1
            : 1;

        return sprintf('%s-%s-%04d', $prefix, $year, $sequence);
    }
}
