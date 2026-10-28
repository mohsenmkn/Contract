<?php

namespace Modules\Contract\Console\Commands;

use Illuminate\Console\Command;
use Modules\Contract\App\Models\Contract;
use Modules\Invoice\App\Models\Invoice;
use Modules\Payment\App\Models\Payment;

class DailyReportCommand extends Command
{
    protected $signature = 'reports:daily';
    protected $description = 'گزارش روزانه وضعیت قراردادها';

    public function handle(): int
    {
        $this->info('📊 گزارش روزانه - ' . now()->format('Y-m-d'));
        $this->line(str_repeat('-', 50));

        // آمار قراردادها
        $totalContracts = Contract::count();
        $activeContracts = Contract::where('status', 'active')->count();
        $expiringContracts = Contract::expiringSoon(30)->count();

        $this->line("📋 کل قراردادها: {$totalContracts}");
        $this->line("✅ قراردادهای فعال: {$activeContracts}");
        $this->line("⚠️ قراردادهای در حال اتمام: {$expiringContracts}");

        $this->line('');

        // آمار صورت‌وضعیت‌ها
        $totalInvoices = Invoice::count();
        $pendingInvoices = Invoice::where('status', 'submitted')->count();
        $approvedInvoices = Invoice::where('status', 'approved')->count();

        $this->line("📄 کل صورت‌وضعیت‌ها: {$totalInvoices}");
        $this->line("⏳ در انتظار تایید: {$pendingInvoices}");
        $this->line("✓ تایید شده: {$approvedInvoices}");

        $this->line('');

        // آمار پرداخت‌ها
        $todayPayments = Payment::whereDate('payment_date', today())
            ->where('status', 'completed')
            ->sum('amount');

        $this->line("💰 پرداخت‌های امروز: " . number_format($todayPayments) . " ریال");

        $this->line(str_repeat('-', 50));
        $this->info('✅ گزارش روزانه تکمیل شد.');

        return Command::SUCCESS;
    }
}
