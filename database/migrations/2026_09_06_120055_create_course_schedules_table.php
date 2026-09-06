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
        Schema::create('course_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('course_name');
            $table->string('course_code')->nullable();
            $table->unsignedTinyInteger('sks')->default(3);
            $table->string('class_code')->nullable();
            $table->string('lecturer_name')->nullable();
            $table->unsignedTinyInteger('day_of_week'); // 1 = Monday ... 7 = Sunday
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room')->nullable();
            $table->string('delivery_mode', 20)->default('offline'); // offline, online, hybrid
            $table->string('meeting_link', 500)->nullable();
            $table->string('color_tag', 30)->default('red');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['day_of_week', 'start_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_schedules');
    }
};
