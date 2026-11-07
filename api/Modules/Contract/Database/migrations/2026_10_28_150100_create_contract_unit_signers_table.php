<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_unit_signers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('organizational_units')->cascadeOnDelete();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('unit_role', 32);
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['unit_id', 'user_id']);
            $table->index(['unit_id', 'unit_role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_unit_signers');
    }
};
