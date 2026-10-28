<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // فیلدهای جدید
            $table->foreignId('unit_id')->nullable()->after('party_id')->constrained('organizational_units')->nullOnDelete();
            $table->foreignId('requesting_user_id')->nullable()->after('unit_id')->constrained('users')->nullOnDelete();
            $table->foreignId('parent_contract_id')->nullable()->after('type')->constrained('contracts')->nullOnDelete();

            // تغییر وضعیت‌ها
            $table->dropColumn('status');
        });

        // اضافه کردن enum جدید با وضعیت‌های workflow
        Schema::table('contracts', function (Blueprint $table) {
            $table->enum('status', [
                'draft',
                'pending_legal_review',
                'legal_draft',
                'pending_contractor_sign',
                'pending_executive_sign',
                'pending_notification',
                'active',
                'expiring',
                'completed',
                'terminated'
            ])->default('draft')->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['requesting_user_id']);
            $table->dropForeign(['parent_contract_id']);
            $table->dropColumn(['unit_id', 'requesting_user_id', 'parent_contract_id']);

            $table->dropColumn('status');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->enum('status', [
                'draft',
                'pending_internal_approval',
                'pending_contractor_sign',
                'active',
                'expiring',
                'completed',
                'terminated'
            ])->default('draft')->after('type');
        });
    }
};
