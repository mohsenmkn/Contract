// database/migrations/2024_01_01_000012_add_party_id_to_users_table.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('party_id')
                ->nullable()
                ->after('employee_type')
                ->constrained('parties')
                ->nullOnDelete();

            $table->index(['employee_type', 'party_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['party_id']);
            $table->dropIndex(['employee_type', 'party_id']);
            $table->dropColumn('party_id');
        });
    }
};
