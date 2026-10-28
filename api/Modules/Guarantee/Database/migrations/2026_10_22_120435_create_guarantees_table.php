// Modules/Guarantee/Database/Migrations/2024_01_01_000005_create_guarantees_table.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guarantees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('guarantee_number')->unique();
            $table->enum('type', ['performance', 'advance_payment', 'bid', 'retention']);
            $table->decimal('amount', 18, 2);
            $table->string('currency', 10)->default('IRR');
            $table->string('bank_name');
            $table->string('bank_branch')->nullable();
            $table->date('issue_date');
            $table->date('expiry_date');
            $table->enum('status', ['active', 'expired', 'released', 'called'])->default('active');
            $table->text('description')->nullable();
            $table->string('file_path')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['contract_id', 'status']);
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guarantees');
    }
};
