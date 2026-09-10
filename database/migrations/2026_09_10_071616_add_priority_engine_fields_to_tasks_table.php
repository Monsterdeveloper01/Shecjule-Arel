<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add priority engine fields to tasks table.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedInteger('estimated_duration')->nullable()->after('status'); // minutes
            $table->unsignedTinyInteger('progress')->default(0)->after('estimated_duration'); // 0–100
            $table->unsignedTinyInteger('priority_score')->nullable()->after('progress'); // 0–100
            $table->string('priority_level', 20)->nullable()->after('priority_score'); // KRITIS/TINGGI/SEDANG/RENDAH
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['estimated_duration', 'progress', 'priority_score', 'priority_level']);
        });
    }
};
