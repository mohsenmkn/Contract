<?php

namespace Modules\Amendment\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Amendment\App\Models\Amendment;
use Modules\Amendment\App\Events\AmendmentApproved;

class ApproveAmendmentService
{
    public function execute(Amendment $amendment): Amendment
    {
        return DB::transaction(function () use ($amendment) {
            if ($amendment->status !== 'pending') {
                throw new \Exception('این الحاقیه در وضعیت قابل تایید نیست.');
            }

            $contract = $amendment->contract;

            $updateData = ['status' => 'active'];

            if ($amendment->type === 'amount' && $amendment->new_amount !== null) {
                $updateData['current_amount'] = $amendment->new_amount;
            }

            if ($amendment->type === 'extension' && $amendment->new_end_date) {
                $updateData['end_date'] = $amendment->new_end_date;
            }

            $contract->update($updateData);

            $amendment->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            event(new AmendmentApproved($amendment));

            return $amendment->fresh();
        });
    }
}
