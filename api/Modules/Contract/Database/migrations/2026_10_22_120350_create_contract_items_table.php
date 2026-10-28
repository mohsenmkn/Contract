// Modules/Contract/Database/Migrations/2024_01_01_000003_create_contract_items_table.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('item_code');
            $table->text('description');
            $table->string('unit', 50);
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_price', 18, 2);
            $table->decimal('total_price', 18, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('contract_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_items');
    }
};
