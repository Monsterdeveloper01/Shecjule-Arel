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
        Schema::create('finance_savings_goals', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->decimal('target_amount', 14, 2);
            $table->decimal('current_amount', 14, 2)->default(0.00);
            $table->string('type', 20)->default('deadline'); // 'deadline' or 'open_ended'
            $table->date('target_date')->nullable();
            $table->string('color', 20)->default('#10b981');
            $table->string('icon', 20)->default('🎯');
            $table->string('status', 20)->default('active'); // 'active', 'achieved', 'paused'
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('finance_savings_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('savings_goal_id')->constrained('finance_savings_goals')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('finance_accounts')->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->date('allocation_date');
            $table->string('notes', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_savings_allocations');
        Schema::dropIfExists('finance_savings_goals');
    }
};
