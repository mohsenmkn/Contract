<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // تغییر enum status به وضعیت‌های جدید
        DB::statement("
            ALTER TABLE contracts
            MODIFY COLUMN status ENUM(
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
            ) DEFAULT 'draft'
        ");

        // ایجاد جدول تاریخچه وضعیت‌ها
        Schema::create('contract_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('comments')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['contract_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_status_history');

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['requesting_user_id']);
            $table->dropForeign(['parent_contract_id']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'unit_id',
                'requesting_user_id',
                'parent_contract_id',
                'approved_by',
                'approved_at',
            ]);
        });

        // بازگرداندن enum قبلی
        DB::statement("
            ALTER TABLE contracts
            MODIFY COLUMN status ENUM(
                'draft',
                'pending_internal_approval',
                'pending_contractor_sign',
                'active',
                'expiring',
                'completed',
                'terminated'
            ) DEFAULT 'draft'
        ");
    }
};
