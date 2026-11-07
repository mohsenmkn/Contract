<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $contractIds = DB::table('contracts')
                ->where('status', 'pending_guarantee')
                ->pluck('id');

            if ($contractIds->isEmpty()) {
                return;
            }

            $now = now();
            foreach ($contractIds->chunk(500) as $chunk) {
                DB::table('contract_status_history')->insert($chunk->map(fn ($contractId) => [
                    'contract_id' => $contractId,
                    'from_status' => 'pending_guarantee',
                    'to_status' => 'pending_unit_sign',
                    'user_id' => null,
                    'comments' => 'ادامه خودکار گردش کار؛ دریافت ضمانت دیگر مانع امضای واحدها نیست.',
                    'ip_address' => null,
                    'user_agent' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            }

            DB::table('contracts')
                ->whereIn('id', $contractIds)
                ->update(['status' => 'pending_unit_sign', 'updated_at' => $now]);
        });
    }

    public function down(): void
    {
        // عمدی: قراردادهایی که پس از این تغییر وارد گردش امضا شده‌اند نباید به مرحله ضمانت بازگردند.
    }
};
