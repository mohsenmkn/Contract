<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('contracts', 'notified_at')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->timestamp('notified_at')->nullable()->after('approved_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('contracts', 'notified_at')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->dropColumn('notified_at');
            });
        }
    }
};
