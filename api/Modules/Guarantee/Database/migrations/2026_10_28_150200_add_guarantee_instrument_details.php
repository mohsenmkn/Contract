<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guarantees', function (Blueprint $table) {
            $table->string('instrument_type', 24)->default('bank_guarantee')->after('type');
            $table->string('instrument_number', 100)->nullable()->after('instrument_type');
        });
    }

    public function down(): void
    {
        Schema::table('guarantees', function (Blueprint $table) {
            $table->dropColumn(['instrument_type', 'instrument_number']);
        });
    }
};
