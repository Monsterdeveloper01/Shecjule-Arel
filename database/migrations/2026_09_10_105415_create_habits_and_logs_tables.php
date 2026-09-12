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
        Schema::create('habits', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('icon')->default('⚡');
            $table->string('color')->default('#6366f1');
            $table->string('category')->default('kesehatan'); // kesehatan, akademik, produktivitas, pribadi
            $table->string('cadence')->default('daily'); // daily, alternate_days, specific_days, weekly_target
            $table->json('specific_days')->nullable(); // e.g. [1, 3, 5] for Mon, Wed, Fri
            $table->integer('target_days_per_week')->default(7);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('habit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('habit_id')->constrained('habits')->cascadeOnDelete();
            $table->date('log_date');
            $table->string('status')->default('completed'); // completed, rest_day, skipped
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['habit_id', 'log_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('habit_logs');
        Schema::dropIfExists('habits');
    }
};
