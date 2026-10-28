// Modules/Party/Database/Migrations/2024_01_01_000001_create_parties_table.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('national_id', 11)->unique()->nullable();
            $table->string('registration_number')->nullable();
            $table->string('economic_code')->nullable();
            $table->enum('type', ['legal', 'real'])->default('legal');
            $table->enum('role', ['employer', 'contractor', 'both'])->default('contractor');
            $table->text('address')->nullable();
            $table->string('phone', 15)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('iban', 26)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['role', 'is_active']);
            $table->index('national_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parties');
    }
};
