<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('executive_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('signer_id')->constrained('users')->cascadeOnDelete();
            $table->enum('signer_role', ['ceo', 'deputy_ceo', 'board_member']);
            $table->timestamp('signed_at')->nullable();
            $table->string('signature_hash', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->index(['contract_id', 'signer_role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('executive_signatures');
    }
};
