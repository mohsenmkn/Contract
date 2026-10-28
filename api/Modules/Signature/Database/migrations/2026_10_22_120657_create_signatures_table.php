// Modules/Signature/Database/Migrations/2024_01_01_000009_create_signatures_table.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signatures', function (Blueprint $table) {
            $table->id();
            $table->morphs('signable');
            $table->foreignId('signer_id')->constrained('users')->cascadeOnDelete();
            $table->enum('signer_type', ['internal', 'contractor']);
            $table->enum('status', ['pending', 'signed', 'rejected'])->default('pending');
            $table->text('comments')->nullable();
            $table->string('signature_hash', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();

            $table->index(['signable_type', 'signable_id', 'status']);
            $table->index('signer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signatures');
    }
};
