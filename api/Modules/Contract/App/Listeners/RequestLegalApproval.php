<?php

namespace Modules\Contract\App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Auth\App\Models\User;
use Modules\Contract\App\Events\ContractSubmittedForApproval;
use Modules\Signature\App\Services\SignatureRequestService;

class RequestLegalApproval implements ShouldQueue
{
    public function __construct(
        private SignatureRequestService $signatureService
    ) {}

    public function handle(ContractSubmittedForApproval $event): void
    {
        $contract = $event->contract;

        // پیدا کردن کاربران با نقش کارشناس حقوقی
        $legalExperts = User::role('legal_expert')->get();

        foreach ($legalExperts as $expert) {
            $this->signatureService->requestFromInternal($contract, $expert->id);
        }

        // تغییر وضعیت قرارداد
        $contract->update(['status' => 'pending_contractor_sign']);
    }
}
