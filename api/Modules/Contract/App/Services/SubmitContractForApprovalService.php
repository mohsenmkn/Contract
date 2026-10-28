<?php

namespace Modules\Contract\App\Services;

use Modules\Contract\App\Models\Contract;
use Modules\Signature\App\Services\SignatureRequestService;
use Modules\Contract\App\Events\ContractSubmittedForApproval;

class SubmitContractForApprovalService
{
    public function __construct(
        public SignatureRequestService $signatureService
    ) {}

    public function execute(Contract $contract): Contract
    {
        if ($contract->status !== 'draft') {
            throw new \Exception('فقط قراردادهای پیش‌نویس قابل ارسال هستند.');
        }

        $contract->update(['status' => 'pending_internal_approval']);

        event(new ContractSubmittedForApproval($contract));

        return $contract;
    }
}
