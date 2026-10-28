<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_representatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('organizational_units')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();

            // هر واحد فقط یک نماینده، هر کاربر فقط یک واحد
            $table->unique('unit_id');
            $table->unique('user_id');

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_representatives');
    }
};
