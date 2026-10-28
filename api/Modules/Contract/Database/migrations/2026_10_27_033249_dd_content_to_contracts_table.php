// Modules/Contract/Database/Migrations/2026_10_27_000001_add_content_to_contracts_table.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // متن کامل قرارداد (HTML از Quill Editor)
            $table->longText('content')->nullable()->after('description');

            // تعداد صفحات تقریبی (برای نمایش)
            $table->integer('pages_count')->nullable()->after('content');

            // نسخه متن قرارداد (برای track کردن تغییرات)
            $table->string('content_version', 20)->default('1.0')->after('pages_count');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['content', 'pages_count', 'content_version']);
        });
    }
};
