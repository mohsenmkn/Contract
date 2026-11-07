<?php

namespace Modules\Alert\App\Services;

use Modules\Alert\App\Models\ContractAlert;
use Modules\Contract\App\Models\Contract;
use Modules\Contract\App\Services\ContractSettings;

class AlertSchedulerService
{
    public function __construct(private ContractSettings $settings) {}

    public function schedule(Contract $contract, array $daysBefore = []): void
    {
        $days = $daysBefore ?: $this->settings->expiryAlertSettings()['days_before'];
        ContractAlert::where('contract_id', $contract->id)->where('alert_type', 'expiration')->whereNull('notified_at')->delete();

        foreach ($days as $daysBefore) {
            $alertDate = $contract->end_date->copy()->subDays((int) $daysBefore);
            if ($alertDate->lt(today())) {
                continue;
            }
            ContractAlert::create([
                'contract_id' => $contract->id,
                'alert_type' => 'expiration',
                'alert_date' => $alertDate,
                'days_before' => (int) $daysBefore,
                'message' => "قرارداد {$contract->contract_number} تا {$daysBefore} روز دیگر به پایان می‌رسد.",
                'is_active' => true,
            ]);
        }
    }

    public function rescheduleExpiringContracts(array $daysBefore): void
    {
        ContractAlert::where('alert_type', 'expiration')->whereNull('notified_at')->delete();
        Contract::query()->whereIn('status', ['active', 'expiring'])
            ->whereNotNull('end_date')->whereDate('end_date', '>=', today())
            ->chunkById(100, function ($contracts) use ($daysBefore) {
                foreach ($contracts as $contract) {
                    $this->schedule($contract, $daysBefore);
                }
            });
    }

    public function scheduleGuaranteeAlert($guarantee, array $daysBefore = []): void
    {
        $days = $daysBefore ?: [30, 60];
        foreach ($days as $daysBefore) {
            $alertDate = $guarantee->expiry_date->copy()->subDays($daysBefore);
            if ($alertDate->isPast()) {
                continue;
            }
            ContractAlert::create([
                'contract_id' => $guarantee->contract_id,
                'alert_type' => 'guarantee',
                'alert_date' => $alertDate,
                'days_before' => $daysBefore,
                'message' => "ضمانت‌نامه {$guarantee->guarantee_number} تا {$daysBefore} روز دیگر منقضی می‌شود.",
                'is_active' => true,
            ]);
        }
    }
}
