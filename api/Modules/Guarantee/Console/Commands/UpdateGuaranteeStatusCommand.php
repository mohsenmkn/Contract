<?php

namespace Modules\Guarantee\Console\Commands;

use Illuminate\Console\Command;
use Modules\Guarantee\App\Models\Guarantee;

class UpdateGuaranteeStatusCommand extends Command
{
    protected $signature = 'guarantees:update-status';
    protected $description = 'بروزرسانی وضعیت ضمانت‌نامه‌های منقضی شده';

    public function handle(): int
    {
        $this->info('🔄 بروزرسانی وضعیت ضمانت‌نامه‌ها...');

        $expired = Guarantee::where('status', 'active')
            ->where('expiry_date', '<', now())
            ->get();

        foreach ($expired as $guarantee) {
            $guarantee->update(['status' => 'expired']);
            $this->line("  ✓ ضمانت‌نامه {$guarantee->guarantee_number} منقضی شد");
        }

        $this->info('✅ بروزرسانی وضعیت ضمانت‌نامه‌ها انجام شد.');

        return Command::SUCCESS;
    }
}
