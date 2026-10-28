<?php

namespace Modules\Contract\Console\Commands;

use Illuminate\Console\Command;
use Modules\Contract\App\Models\Contract;

class UpdateContractStatusCommand extends Command
{
    protected $signature = 'contracts:update-status';
    protected $description = 'بروزرسانی وضعیت قراردادهای منقضی شده';

    public function handle(): int
    {
        $this->info('🔄 بروزرسانی وضعیت قراردادها...');

        // قراردادهای منقضی شده
        $expired = Contract::where('status', 'active')
            ->where('end_date', '<', now())
            ->get();

        foreach ($expired as $contract) {
            $contract->update(['status' => 'completed']);
            $this->line("  ✓ قرارداد {$contract->contract_number} خاتمه یافت");
        }

        // قراردادهای در حال اتمام
        $expiring = Contract::where('status', 'active')
            ->where('end_date', '<=', now()->addDays(30))
            ->where('end_date', '>=', now())
            ->get();

        foreach ($expiring as $contract) {
            $contract->update(['status' => 'expiring']);
            $this->line("  ⚠ قرارداد {$contract->contract_number} در حال اتمام است");
        }

        $this->info('✅ بروزرسانی وضعیت قراردادها انجام شد.');

        return Command::SUCCESS;
    }
}
