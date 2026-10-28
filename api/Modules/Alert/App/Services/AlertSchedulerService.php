<?php

namespace Modules\Alert\App\Services;

use Modules\Contract\App\Models\Contract;
use Modules\Alert\App\Models\ContractAlert;

class AlertSchedulerService
{
    private array $defaultDaysBefore = [30, 60, 90];

    public function schedule(Contract $contract, array $daysBefore = []): void
    {
        $days = !empty($daysBefore) ? $daysBefore : $this->defaultDaysBefore;

        foreach ($days as $daysBefore) {
            $alertDate = $contract->end_date->copy()->subDays($daysBefore);

            if ($alertDate->isPast()) {
                continue;
            }

            ContractAlert::create([
                'contract_id' => $contract->id,
                'alert_type' => 'expiration',
                'alert_date' => $alertDate,
                'days_before' => $daysBefore,
                'message' => "قرارداد {$contract->contract_number} تا {$daysBefore} روز دیگر به پایان می‌رسد.",
                'is_active' => true,
            ]);
        }
    }

    public function scheduleGuaranteeAlert($guarantee, array $daysBefore = []): void
    {
        $days = !empty($daysBefore) ? $daysBefore : [30, 60];

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
