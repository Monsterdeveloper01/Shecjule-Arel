<?php

namespace Tests\Feature;

use App\Models\FinanceAccount;
use App\Models\FinanceSavingsGoal;
use App\Services\SavingsGoalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceSavingsGoalTest extends TestCase
{
    use RefreshDatabase;

    public function test_savings_goal_deadline_calculation_and_affordability(): void
    {
        $evalDate = Carbon::parse('2026-09-01');

        $goal = FinanceSavingsGoal::create([
            'name' => 'Beli Laptop Baru',
            'target_amount' => 3000000.00,
            'current_amount' => 0.00,
            'type' => 'deadline',
            'target_date' => '2026-09-30', // 30 eligible days
            'status' => 'active',
        ]);

        // Scenario 1: Estimated surplus = 150.000/day
        // Daily required = 3.000.000 / 30 = 100.000
        // 100.000 <= 150.000 * 0.7 (105.000) => 'achievable'
        $calc1 = $goal->calculateDailyRequirement($evalDate, 150000.00);
        $this->assertEquals(30, $calc1['days_remaining']);
        $this->assertEquals(100000.00, $calc1['daily_required']);
        $this->assertEquals(700000.00, $calc1['weekly_required']);
        $this->assertEquals('achievable', $calc1['affordability']);

        // Scenario 2: Estimated surplus = 120.000/day
        // 100.000 > 84.000 but <= 120.000 => 'demanding'
        $calc2 = $goal->calculateDailyRequirement($evalDate, 120000.00);
        $this->assertEquals('demanding', $calc2['affordability']);

        // Scenario 3: Estimated surplus = 50.000/day
        // 100.000 > 50.000 => 'unrealistic'
        $calc3 = $goal->calculateDailyRequirement($evalDate, 50000.00);
        $this->assertEquals('unrealistic', $calc3['affordability']);
    }

    public function test_open_ended_savings_goal_flexibility(): void
    {
        $goal = FinanceSavingsGoal::create([
            'name' => 'Dana Darurat Tanpa Deadline',
            'target_amount' => 10000000.00,
            'current_amount' => 2500000.00,
            'type' => 'open_ended',
            'target_date' => null,
            'status' => 'active',
        ]);

        $calc = $goal->calculateDailyRequirement(Carbon::parse('2026-09-12'), 100000.00);

        $this->assertNull($calc['target_date']);
        $this->assertNull($calc['days_remaining']);
        $this->assertEquals(0.00, $calc['daily_required']);
        $this->assertEquals('flexible', $calc['affordability']);
        $this->assertEquals(25.0, $goal->progress_percent);
        $this->assertEquals(7500000.00, $goal->remaining_amount);
    }

    public function test_allocate_savings_locks_cash_in_reserve_without_reducing_actual_balance(): void
    {
        $account = FinanceAccount::create([
            'name' => 'BCA Utama',
            'type' => 'bank',
            'opening_balance' => 5000000.00,
            'current_balance' => 5000000.00,
            'installment_reserve' => 0.00,
            'savings_reserve' => 0.00,
        ]);

        $goal = FinanceSavingsGoal::create([
            'name' => 'Liburan Akhir Tahun',
            'target_amount' => 2000000.00,
            'current_amount' => 0.00,
            'type' => 'deadline',
            'target_date' => '2026-12-31',
            'status' => 'active',
        ]);

        $service = app(SavingsGoalService::class);

        // Allocate 500.000 to goal
        $allocation = $service->allocateSavings(
            $goal->id,
            $account->id,
            500000.00,
            '2026-09-12',
            'Tabungan bulan ini'
        );

        $account->refresh();
        $goal->refresh();

        // 1. Actual balance in bank remains 5.000.000 (No false consumption expense!)
        $this->assertEquals(5000000.00, (float) $account->current_balance);

        // 2. Savings reserve in account is locked to 500.000
        $this->assertEquals(5000000.00 - 500000.00, (float) $account->available_spending);
        $this->assertEquals(500000.00, (float) $account->savings_reserve);

        // 3. Goal current amount increased
        $this->assertEquals(500000.00, (float) $goal->current_amount);
        $this->assertEquals(25.0, $goal->progress_percent);

        // 4. Ledger transaction is recorded as savings_allocation
        $this->assertDatabaseHas('finance_transactions', [
            'account_id' => $account->id,
            'type' => 'savings_allocation',
            'amount' => 500000.00,
            'savings_goal_id' => $goal->id,
        ]);

        // 5. Allocation record exists
        $this->assertDatabaseHas('finance_savings_allocations', [
            'id' => $allocation->id,
            'savings_goal_id' => $goal->id,
            'account_id' => $account->id,
            'amount' => 500000.00,
        ]);
    }

    public function test_savings_goal_auto_achieved_and_withdrawal(): void
    {
        $account = FinanceAccount::create([
            'name' => 'Mandiri Tabungan',
            'type' => 'bank',
            'opening_balance' => 3000000.00,
            'current_balance' => 3000000.00,
        ]);

        $goal = FinanceSavingsGoal::create([
            'name' => 'Kamera Vlog',
            'target_amount' => 1000000.00,
            'current_amount' => 0.00,
            'type' => 'open_ended',
            'status' => 'active',
        ]);

        $service = app(SavingsGoalService::class);

        // Save 1.000.000 (full target)
        $service->allocateSavings($goal->id, $account->id, 1000000.00);
        $goal->refresh();

        // 1. Goal status is automatically 'achieved'
        $this->assertEquals('achieved', $goal->status);
        $this->assertEquals(100.0, $goal->progress_percent);

        // 2. Withdraw as real expense (buying the camera!)
        $service->withdrawSavings(
            $goal->id,
            $account->id,
            1000000.00,
            true, // isExpense
            'Beli kamera vlog di toko'
        );

        $account->refresh();
        $goal->refresh();

        // 3. Bank balance decreased by 1.000.000 (real expense)
        $this->assertEquals(2000000.00, (float) $account->current_balance);

        // 4. Savings reserve released back to 0
        $this->assertEquals(0.00, (float) $account->savings_reserve);

        // 5. Expense transaction logged on ledger
        $this->assertDatabaseHas('finance_transactions', [
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 1000000.00,
            'savings_goal_id' => $goal->id,
        ]);
    }

    public function test_savings_http_endpoints_and_dashboard_view(): void
    {
        $account = FinanceAccount::create([
            'name' => 'Dompet E-Wallet',
            'type' => 'ewallet',
            'opening_balance' => 2000000.00,
            'current_balance' => 2000000.00,
        ]);

        // 1. Create Goal
        $storeRes = $this->withSession(['authenticated' => true])
            ->postJson('/finance/savings', [
                'name' => 'Beli Sepeda Lipat',
                'target_amount' => 2500000,
                'type' => 'deadline',
                'target_date' => '2026-11-30',
                'icon' => '🚲',
                'color' => '#06b6d4',
                'notes' => 'Untuk olahraga pagi',
            ]);
        $storeRes->assertOk()->assertJsonPath('success', true);
        $goalId = $storeRes->json('goal.id');

        // 2. Allocate savings
        $allocRes = $this->withSession(['authenticated' => true])
            ->postJson("/finance/savings/{$goalId}/allocate", [
                'account_id' => $account->id,
                'amount' => 500000,
                'notes' => 'Alokasi pertama',
            ]);
        $allocRes->assertOk()->assertJsonPath('success', true);

        // 3. View on Dashboard
        $viewRes = $this->withSession(['authenticated' => true])
            ->get('/finance');
        $viewRes->assertOk();
        $viewRes->assertSee('Beli Sepeda Lipat');
        $viewRes->assertSee('tabBtnSavings');
        $viewRes->assertSee('Target Tabungan');

        // 4. Withdraw savings back to flexible spending (not expense)
        $withRes = $this->withSession(['authenticated' => true])
            ->postJson("/finance/savings/{$goalId}/withdraw", [
                'account_id' => $account->id,
                'amount' => 200000,
                'is_expense' => false,
                'notes' => 'Perlu dana mendadak',
            ]);
        $withRes->assertOk()->assertJsonPath('success', true);

        $account->refresh();
        $this->assertEquals(300000.00, (float) $account->savings_reserve);

        // 5. Delete Goal
        $delRes = $this->withSession(['authenticated' => true])
            ->deleteJson("/finance/savings/{$goalId}");
        $delRes->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('finance_savings_goals', ['id' => $goalId]);
    }
}
