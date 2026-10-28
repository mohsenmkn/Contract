// Modules/Contract/Database/Migrations/2024_01_01_000002_create_contracts_table.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->unique();
            $table->string('subject');
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['upstream', 'downstream']);
            $table->enum('status', [
                'draft',
                'pending_internal_approval',
                'pending_contractor_sign',
                'active',
                'expiring',
                'completed',
                'terminated'
            ])->default('draft');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('initial_amount', 18, 2);
            $table->decimal('current_amount', 18, 2);
            $table->string('currency', 10)->default('IRR');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'status']);
            $table->index(['party_id', 'status']);
            $table->index('end_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
