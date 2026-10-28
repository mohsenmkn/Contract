<?php

namespace Modules\Alert\Console\Commands;

use Illuminate\Console\Command;
use Modules\Alert\App\Services\CheckExpiringContractsService;

class CheckExpiringContractsCommand extends Command
{
    protected $signature = 'contracts:check-expiring';
    protected $description = 'بررسی قراردادهای در حال اتمام و ارسال هشدار';

    public function handle(CheckExpiringContractsService $service): int
    {
        $this->info('🔍 بررسی قراردادهای در حال اتمام...');

        $notified = $service->execute();

        if ($notified->isEmpty()) {
            $this->info('✅ هیچ هشداری برای ارسال وجود ندارد.');
        } else {
            $this->info("✅ {$notified->count()} هشدار ارسال شد.");

            foreach ($notified as $alert) {
                $this->line("  - قرارداد {$alert->contract->contract_number}: {$alert->message}");
            }
        }

        return Command::SUCCESS;
    }
}
