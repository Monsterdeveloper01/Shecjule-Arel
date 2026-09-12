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
        Schema::create('finance_installments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->decimal('total_amount', 14, 2);
            $table->decimal('monthly_amount', 14, 2);
            $table->unsignedInteger('due_day');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('category', 50)->default('elektronik');
            $table->enum('status', ['active', 'paid_off', 'paused'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('due_day');
        });

        Schema::create('finance_installment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installment_id')->constrained('finance_installments')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('finance_accounts')->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->date('allocation_date');
            $table->unsignedInteger('month');
            $table->unsignedInteger('year');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_installment_allocations');
        Schema::dropIfExists('finance_installments');
    }
};
