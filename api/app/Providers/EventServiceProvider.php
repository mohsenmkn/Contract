<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Contract\App\Events\ContractCreated;
use Modules\Contract\App\Events\ContractSentToContractor;
use Modules\Contract\App\Events\ContractStatusChanged;
use Modules\Contract\App\Events\ContractSubmittedForApproval;
use Modules\Contract\App\Listeners\NotifyContractorAboutSignature;
use Modules\Contract\App\Listeners\SendContractCreatedNotification;
use Modules\Contract\App\Listeners\RequestLegalApproval;
use Modules\Amendment\App\Events\AmendmentApproved;
use Modules\Amendment\App\Listeners\NotifyContractManager;
use Modules\Invoice\App\Events\InvoiceSubmitted;
use Modules\Invoice\App\Listeners\NotifyFinancialExpert;
use Modules\Signature\App\Events\DocumentSigned;
use Modules\Signature\App\Listeners\UpdateContractStatus;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ContractCreated::class => [
            SendContractCreatedNotification::class,
        ],

        ContractSubmittedForApproval::class => [
            RequestLegalApproval::class,
        ],

        AmendmentApproved::class => [
            NotifyContractManager::class,
        ],

        InvoiceSubmitted::class => [
            NotifyFinancialExpert::class,
        ],

        DocumentSigned::class => [
            UpdateContractStatus::class,
        ],
        ContractSentToContractor::class => [
            NotifyContractorAboutSignature::class,
        ],
        ContractStatusChanged::class => [
            \Modules\Contract\App\Notifications\SendWorkflowNotification::class,
        ],
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
