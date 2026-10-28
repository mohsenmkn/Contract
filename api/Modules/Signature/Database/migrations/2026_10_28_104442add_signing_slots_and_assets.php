<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('signatures', function (Blueprint $table) {
            // اسلات امضا: گروه (unit|legal|contractor|executive)، نقش امضاکننده، نقش‌های مجاز
            $table->string('group', 32)->nullable()->after('signer_type');
            $table->string('role', 48)->nullable()->after('group');
            $table->json('allowed_roles')->nullable()->after('role');
            $table->string('kind', 16)->default('signature')->after('allowed_roles'); // signature|seal
            $table->unsignedSmallInteger('sign_order')->default(0)->after('kind');

            // امضا فقط برای همین نسخه‌ی قرارداد معتبر است
            $table->unsignedInteger('content_version')->nullable()->after('sign_order');

            // snapshot تصویر امضا در لحظه‌ی امضا
            $table->string('image_path')->nullable();
            $table->string('image_hash', 64)->nullable();

            $table->timestamp('invalidated_at')->nullable();
            $table->string('invalidated_reason')->nullable();

            $table->index(['signable_type', 'signable_id', 'group', 'status'], 'signatures_slot_lookup');
        });

        // ⚠️ این دو تغییر را با migration اصلی جدول signatures تطبیق بده:
        // - اسلات pending هنوز امضاکننده ندارد، پس signer_id باید nullable باشد.
        // - اگر status از نوع enum است، مقدار جدید «invalidated» را نمی‌پذیرد؛ به string تبدیل می‌شود.
        Schema::table('signatures', function (Blueprint $table) {
            $table->unsignedBigInteger('signer_id')->nullable()->change();
            $table->string('signer_type')->nullable()->change();
            $table->string('status', 20)->default('pending')->change();
        });

        Schema::create('signature_assets', function (Blueprint $table) {
            $table->id();
            $table->morphs('owner');                       // معمولاً User
            $table->string('kind', 16)->default('signature'); // signature|seal
            $table->string('path');
            $table->string('hash', 64);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['owner_type', 'owner_id', 'kind', 'is_active'], 'signature_assets_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signature_assets');

        Schema::table('signatures', function (Blueprint $table) {
            $table->dropIndex('signatures_slot_lookup');
            $table->dropColumn([
                'group', 'role', 'allowed_roles', 'kind', 'sign_order', 'content_version',
                'image_path', 'image_hash', 'invalidated_at', 'invalidated_reason',
            ]);
        });
    }
};
