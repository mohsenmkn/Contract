<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->boolean('requires_board_signature')->default(false)->after('guarantee_instrument_type');
        });

        DB::table('contract_settings')->where('key', 'executive_threshold_toman')->delete();
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn('requires_board_signature');
        });
    }
};
