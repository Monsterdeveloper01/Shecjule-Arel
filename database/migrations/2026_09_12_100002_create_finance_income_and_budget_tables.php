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
        Schema::create('finance_income_schedules', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['daily_variable', 'monthly'])->default('daily_variable');
            $table->json('weekday_amounts')->nullable();
            $table->decimal('monthly_amount', 14, 2)->default(0.00);
            $table->unsignedInteger('monthly_due_day')->nullable()->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('finance_income_overrides', function (Blueprint $table) {
            $table->id();
            $table->date('override_date');
            $table->string('title', 150);
            $table->decimal('amount', 14, 2);
            $table->boolean('is_extra')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('override_date');
        });

        Schema::create('finance_essential_budgets', function (Blueprint $table) {
            $table->id();
            $table->decimal('food', 14, 2)->default(0.00);
            $table->decimal('transport', 14, 2)->default(0.00);
            $table->decimal('snack', 14, 2)->default(0.00);
            $table->decimal('other', 14, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_essential_budgets');
        Schema::dropIfExists('finance_income_overrides');
        Schema::dropIfExists('finance_income_schedules');
    }
};
