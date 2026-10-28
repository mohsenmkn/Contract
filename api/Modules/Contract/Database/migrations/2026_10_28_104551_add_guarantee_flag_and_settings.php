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
            $table->boolean('requires_guarantee')->default(false);
        });

        Schema::create('contract_settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->json('value');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        DB::table('contract_settings')->insert([
            'key' => 'executive_threshold_toman',
            'value' => json_encode(1_000_000_000),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_settings');

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn('requires_guarantee');
        });
    }
};
