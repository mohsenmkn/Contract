<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('guarantee_type', 32)->nullable()->after('requires_guarantee');
            $table->string('guarantee_instrument_type', 24)->nullable()->after('guarantee_type');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['guarantee_type', 'guarantee_instrument_type']);
        });
    }
};
