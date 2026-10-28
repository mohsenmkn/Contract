<?php

namespace Modules\Alert\App\Services;

use Illuminate\Support\Collection;
use Modules\Alert\App\Models\ContractAlert;
use Modules\Alert\App\Notifications\ContractExpiringSoon;

class CheckExpiringContractsService
{
    public function execute(): Collection
    {
        $alerts = ContractAlert::pending()
            ->with(['contract.party', 'contract.creator'])
            ->get();

        $notified = collect();

        foreach ($alerts as $alert) {
            if (!$alert->contract || !$alert->contract->creator) {
                continue;
            }

            $alert->contract->creator->notify(new ContractExpiringSoon($alert));
            $alert->update(['notified_at' => now()]);
            $notified->push($alert);
        }

        return $notified;
    }
}
