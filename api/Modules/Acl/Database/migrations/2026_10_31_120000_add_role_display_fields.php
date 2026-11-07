<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('name');
            $table->text('description')->nullable()->after('display_name');
        });

        // Legacy roles keep a usable display label until an administrator localizes them.
        \Illuminate\Support\Facades\DB::table('roles')
            ->whereNull('display_name')
            ->update(['display_name' => \Illuminate\Support\Facades\DB::raw('name')]);
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['display_name', 'description']);
        });
    }
};
