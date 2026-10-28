<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // بررسی قراردادهای در حال اتمام - هر روز ساعت 8 صبح
        $schedule->command('contracts:check-expiring')
            ->dailyAt('08:00');

        // بررسی ضمانت‌نامه‌های در حال اتمام - هر روز ساعت 8 صبح
        $schedule->command('guarantees:check-expiring')
            ->dailyAt('08:00');

        // بروزرسانی وضعیت قراردادها - هر روز ساعت 23:59
        $schedule->command('contracts:update-status')
            ->dailyAt('23:59');

        // بروزرسانی وضعیت ضمانت‌نامه‌ها - هر روز ساعت 23:59
        $schedule->command('guarantees:update-status')
            ->dailyAt('23:59');

        // گزارش روزانه - هر روز ساعت 20
        $schedule->command('reports:daily')
            ->dailyAt('20:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
