<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE contracts MODIFY COLUMN status ENUM(
            'draft', 'pending_contract_review', 'pending_legal_review', 'legal_draft',
            'pending_contractor_sign', 'pending_guarantee', 'pending_unit_sign',
            'pending_legal_sign', 'pending_executive_sign', 'pending_notification',
            'active', 'expiring', 'completed', 'terminated'
        ) NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("UPDATE contracts SET status = 'draft' WHERE status = 'pending_contract_review'");
        DB::statement("ALTER TABLE contracts MODIFY COLUMN status ENUM(
            'draft', 'pending_legal_review', 'legal_draft', 'pending_contractor_sign',
            'pending_guarantee', 'pending_unit_sign', 'pending_legal_sign',
            'pending_executive_sign', 'pending_notification', 'active', 'expiring',
            'completed', 'terminated'
        ) NOT NULL DEFAULT 'draft'");
    }
};
