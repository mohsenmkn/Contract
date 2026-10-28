<?php

namespace Modules\Contract\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Contract\App\Models\Contract;
use Modules\Signature\App\Models\Signature;
use Modules\Contract\App\Events\ContractSentToContractor;
use Exception;

class SendContractToContractorService
{
    public function execute(Contract $contract): Contract
    {
        return DB::transaction(function () use ($contract) {
            if (!in_array($contract->status, ['draft', 'pending_internal_approval'])) {
                throw new Exception('قرارداد در وضعیت فعلی قابل ارسال به پیمانکار نیست.');
            }

            if ($contract->type !== 'downstream') {
                throw new Exception('فقط قراردادهای پایین‌دستی قابل ارسال به پیمانکار هستند.');
            }

            $party = $contract->party;
            if (!$party) {
                throw new Exception('طرف قرارداد مشخص نشده است.');
            }

            if (!$party->user_id) {
                throw new Exception('پیمانکار به حساب کاربری متصل نشده است.');
            }

            $existingSignature = Signature::where('signable_type', Contract::class)
                ->where('signable_id', $contract->id)
                ->where('signer_id', $party->user_id)
                ->where('signer_type', 'contractor')
                ->first();

            if ($existingSignature) {
                throw new Exception('قبلاً درخواست امضا ارسال شده است.');
            }

            $signature = Signature::create([
                'signable_type' => Contract::class,
                'signable_id' => $contract->id,
                'signer_id' => $party->user_id,
                'signer_type' => 'contractor',
                'status' => 'pending',
            ]);

            $contract->update(['status' => 'pending_contractor_sign']);

            event(new ContractSentToContractor($contract, $signature));

            return $contract->fresh();
        });
    }
}
