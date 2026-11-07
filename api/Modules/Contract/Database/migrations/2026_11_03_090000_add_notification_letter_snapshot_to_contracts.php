<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('contracts', 'notification_letter_data')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->json('notification_letter_data')->nullable()->after('notified_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('contracts', 'notification_letter_data')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->dropColumn('notification_letter_data');
            });
        }
    }
};
