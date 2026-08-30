<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('status');
            $table->string('file_name')->nullable()->after('file_path');
            $table->unsignedBigInteger('file_size')->nullable()->after('file_name');
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('note_date');
            $table->string('file_name')->nullable()->after('file_path');
            $table->unsignedBigInteger('file_size')->nullable()->after('file_name');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('end_date');
            $table->string('file_name')->nullable()->after('file_path');
            $table->unsignedBigInteger('file_size')->nullable()->after('file_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['file_path', 'file_name', 'file_size']);
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->dropColumn(['file_path', 'file_name', 'file_size']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['file_path', 'file_name', 'file_size']);
        });
    }
};
