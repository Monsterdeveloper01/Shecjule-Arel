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
        Schema::create('finance_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->enum('type', ['cash', 'bank', 'ewallet', 'savings'])->default('cash');
            $table->decimal('opening_balance', 14, 2)->default(0.00);
            $table->decimal('current_balance', 14, 2)->default(0.00);
            $table->decimal('installment_reserve', 14, 2)->default(0.00);
            $table->decimal('savings_reserve', 14, 2)->default(0.00);
            $table->boolean('is_primary')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('finance_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->enum('type', ['income', 'expense']);
            $table->string('icon', 20)->default('💰');
            $table->string('color', 20)->default('#ef4444');
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('finance_accounts')->cascadeOnDelete();
            $table->foreignId('destination_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->enum('type', ['income', 'expense', 'transfer', 'savings_allocation', 'installment_payment']);
            $table->foreignId('category_id')->nullable()->constrained('finance_categories')->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->date('transaction_date');
            $table->unsignedBigInteger('installment_id')->nullable();
            $table->unsignedBigInteger('savings_goal_id')->nullable();
            $table->string('description', 255);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('transaction_date');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_transactions');
        Schema::dropIfExists('finance_categories');
        Schema::dropIfExists('finance_accounts');
    }
};
