<?php

namespace Modules\Guarantee\Console\Commands;

use Illuminate\Console\Command;
use Modules\Contract\App\Notifications\ContractExpiringNotification;
use Modules\Guarantee\App\Models\Guarantee;
use Modules\Alert\App\Models\ContractAlert;


class CheckExpiringGuaranteesCommand extends Command
{
    protected $signature = 'guarantees:check-expiring';
    protected $description = 'بررسی ضمانت‌نامه‌های در حال اتمام';

    public function handle(): int
    {
        $this->info('🔍 بررسی ضمانت‌نامه‌های در حال اتمام...');

        $alerts = ContractAlert::where('alert_type', 'guarantee')
            ->whereNull('notified_at')
            ->where('alert_date', '<=', now()->addDays(7))
            ->with(['contract.party', 'contract.creator'])
            ->get();

        if ($alerts->isEmpty()) {
            $this->info('✅ هیچ هشداری برای ارسال وجود ندارد.');
            return Command::SUCCESS;
        }

        $count = 0;
        foreach ($alerts as $alert) {
            if ($alert->contract && $alert->contract->creator) {
                $alert->contract->creator->notify(new ContractExpiringNotification($alert));
                $alert->update(['notified_at' => now()]);
                $count++;
            }
        }

        $this->info("✅ {$count} هشدار ضمانت‌نامه ارسال شد.");

        return Command::SUCCESS;
    }
}
