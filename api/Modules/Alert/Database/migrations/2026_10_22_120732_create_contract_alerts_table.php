// Modules/Alert/Database/Migrations/2024_01_01_000010_create_contract_alerts_table.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->enum('alert_type', ['expiration', 'guarantee', 'payment', 'custom']);
            $table->date('alert_date');
            $table->integer('days_before')->default(30);
            $table->text('message')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['alert_date', 'is_active', 'notified_at']);
            $table->index('contract_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_alerts');
    }
};
