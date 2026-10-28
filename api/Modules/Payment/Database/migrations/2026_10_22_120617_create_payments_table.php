// Modules/Payment/Database/Migrations/2024_01_01_000008_create_payments_table.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('payment_number')->unique();
            $table->decimal('amount', 18, 2);
            $table->string('currency', 10)->default('IRR');
            $table->date('payment_date');
            $table->enum('method', ['bank_transfer', 'check', 'cash', 'online'])->default('bank_transfer');
            $table->string('reference_number')->nullable();
            $table->string('check_number')->nullable();
            $table->date('check_due_date')->nullable();
            $table->enum('status', ['pending', 'completed', 'cancelled', 'bounced'])->default('pending');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['invoice_id', 'status']);
            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
