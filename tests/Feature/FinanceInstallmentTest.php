<?php

namespace Tests\Feature;

use App\Models\FinanceAccount;
use App\Models\FinanceInstallment;
use App\Models\FinanceTransaction;
use App\Services\InstallmentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceInstallmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_installment_daily_allocation_math(): void
    {
        // Monthly amount: 500.000, Due day: 24th
        $installment = FinanceInstallment::create([
            'name' => 'Laptop ASUS ROG',
            'total_amount' => 12000000.00,
            'monthly_amount' => 500000.00,
            'due_day' => 24,
            'start_date' => '2026-09-01',
            'status' => 'active',
        ]);

        // Evaluate on 2026-09-15 (10 eligible days from Sep 15 up to and including Sep 24)
        $evalDate = Carbon::parse('2026-09-15');
        $alreadyReserved = 100000.00; // 400.000 remaining needed

        $calc = $installment->calculateDailyAllocation($evalDate, $alreadyReserved, false);

        $this->assertEquals(400000.00, $calc['remaining_obligation']);
        $this->assertEquals(10, $calc['days_remaining']);
        $this->assertEquals(40000.00, $calc['daily_required']); // 400k / 10 days = 40k/day
        $this->assertFalse($calc['is_due_today']);
        $this->assertFalse($calc['is_overdue']);
        $this->assertFalse($calc['is_fully_reserved']);
    }

    public function test_installment_edge_cases_and_zero_division_guard(): void
    {
        $installment = FinanceInstallment::create([
            'name' => 'Cicilan Motor Beat',
            'total_amount' => 15000000.00,
            'monthly_amount' => 600000.00,
            'due_day' => 20,
            'start_date' => '2026-09-01',
            'status' => 'active',
        ]);

        // Case A: Due today (Sep 20)
        $dueTodayCalc = $installment->calculateDailyAllocation(Carbon::parse('2026-09-20'), 100000.00, false);
        $this->assertTrue($dueTodayCalc['is_due_today']);
        $this->assertFalse($dueTodayCalc['is_overdue']);
        $this->assertEquals(1, $dueTodayCalc['days_remaining']);
        $this->assertEquals(500000.00, $dueTodayCalc['daily_required']);

        // Case B: Overdue (Sep 22)
        $overdueCalc = $installment->calculateDailyAllocation(Carbon::parse('2026-09-22'), 100000.00, false);
        $this->assertFalse($overdueCalc['is_due_today']);
        $this->assertTrue($overdueCalc['is_overdue']);
        $this->assertEquals(1, $overdueCalc['days_remaining']);
        $this->assertEquals(500000.00, $overdueCalc['daily_required']);

        // Case C: Fully reserved
        $fullyReservedCalc = $installment->calculateDailyAllocation(Carbon::parse('2026-09-10'), 600000.00, false);
        $this->assertTrue($fullyReservedCalc['is_fully_reserved']);
        $this->assertEquals(0.00, $fullyReservedCalc['remaining_obligation']);
        $this->assertEquals(0.00, $fullyReservedCalc['daily_required']);

        // Case D: Already paid this month
        $paidCalc = $installment->calculateDailyAllocation(Carbon::parse('2026-09-10'), 0.00, true);
        $this->assertTrue($paidCalc['is_paid']);
        $this->assertEquals(0.00, $paidCalc['daily_required']);
    }

    public function test_reserve_allocation_locks_cash_without_consumption_expense(): void
    {
        $account = FinanceAccount::create([
            'name' => 'BCA Utama',
            'type' => 'bank',
            'opening_balance' => 1000000.00,
            'current_balance' => 1000000.00,
        ]);

        $installment = FinanceInstallment::create([
            'name' => 'iPhone 15',
            'total_amount' => 10000000.00,
            'monthly_amount' => 1000000.00,
            'due_day' => 25,
            'start_date' => '2026-09-01',
            'status' => 'active',
        ]);

        $installmentService = app(InstallmentService::class);

        // Reserve 250.000 into installment reserve
        $allocation = $installmentService->allocateReserve(
            $installment->id,
            $account->id,
            250000.00,
            'Cicilan harian 1',
            '2026-09-12'
        );

        $account->refresh();

        // 1. Actual balance MUST remain 1.000.000 (No false expense!)
        $this->assertEquals(1000000.00, (float) $account->current_balance);

        // 2. Installment reserve locked
        $this->assertEquals(250000.00, (float) $account->installment_reserve);

        // 3. Available spending reduced to 750.000
        $this->assertEquals(750000.00, (float) $account->available_spending);

        // 4. No consumption expense in ledger
        $this->assertEquals(0, FinanceTransaction::count());

        // 5. Allocation record exists
        $this->assertDatabaseHas('finance_installment_allocations', [
            'id' => $allocation->id,
            'installment_id' => $installment->id,
            'account_id' => $account->id,
            'amount' => 250000.00,
        ]);
    }

    public function test_pay_installment_records_expense_and_releases_reserve(): void
    {
        $account = FinanceAccount::create([
            'name' => 'Mandiri',
            'type' => 'bank',
            'opening_balance' => 2000000.00,
            'current_balance' => 2000000.00,
        ]);

        $installment = FinanceInstallment::create([
            'name' => 'Tablet Tab S9',
            'total_amount' => 500000.00, // Small amount to test auto-payoff
            'monthly_amount' => 500000.00,
            'due_day' => 24,
            'start_date' => '2026-09-01',
            'status' => 'active',
        ]);

        $installmentService = app(InstallmentService::class);

        // First reserve 500.000
        $installmentService->allocateReserve($installment->id, $account->id, 500000.00);
        $account->refresh();
        $this->assertEquals(500000.00, (float) $account->installment_reserve);

        // Now pay installment
        $tx = $installmentService->payInstallment(
            $installment->id,
            $account->id,
            500000.00,
            '2026-09-24',
            'Lunas lunas'
        );

        $account->refresh();
        $installment->refresh();

        // 1. Actual balance decreased by 500.000
        $this->assertEquals(1500000.00, (float) $account->current_balance);

        // 2. Reserve released back to 0
        $this->assertEquals(0.00, (float) $account->installment_reserve);

        // 3. Available spending is 1.500.000
        $this->assertEquals(1500000.00, (float) $account->available_spending);

        // 4. Ledger transaction created as installment_payment
        $this->assertEquals('installment_payment', $tx->type);
        $this->assertEquals(500000.00, (float) $tx->amount);

        // 5. Total debt satisfied, status updated to paid_off
        $this->assertEquals(500000.00, $installment->total_paid);
        $this->assertEquals(0.00, $installment->remaining_total);
        $this->assertEquals('paid_off', $installment->status);
    }

    public function test_installment_http_crud_and_dashboard_view(): void
    {
        $account = FinanceAccount::create([
            'name' => 'Dompet Cash',
            'type' => 'cash',
            'opening_balance' => 1500000.00,
            'current_balance' => 1500000.00,
        ]);

        // 1. Store installment
        $storeRes = $this->withSession(['authenticated' => true])
            ->postJson('/finance/installments', [
                'name' => 'Kamera Sony A7',
                'total_amount' => 15000000,
                'monthly_amount' => 1500000,
                'due_day' => 15,
                'start_date' => '2026-09-01',
                'category' => 'elektronik',
                'notes' => 'Kamera kerja',
            ]);
        $storeRes->assertOk()->assertJsonPath('success', true);
        $installmentId = $storeRes->json('installment.id');

        // 2. Reserve funds
        $reserveRes = $this->withSession(['authenticated' => true])
            ->postJson("/finance/installments/{$installmentId}/reserve", [
                'account_id' => $account->id,
                'amount' => 200000,
                'notes' => 'Tabungan harian',
            ]);
        $reserveRes->assertOk()->assertJsonPath('success', true);

        // 3. Check dashboard view
        $viewRes = $this->withSession(['authenticated' => true])
            ->get('/finance');
        $viewRes->assertOk();
        $viewRes->assertSee('Kamera Sony A7');
        $viewRes->assertSee('tabBtnInstallments');
        $viewRes->assertSee('Cicilan & Kewajiban', false);

        // 4. Pay installment
        $payRes = $this->withSession(['authenticated' => true])
            ->postJson("/finance/installments/{$installmentId}/pay", [
                'account_id' => $account->id,
                'amount' => 1500000,
                'transaction_date' => '2026-09-15',
            ]);
        $payRes->assertOk()->assertJsonPath('success', true);

        // 5. Delete installment
        $delRes = $this->withSession(['authenticated' => true])
            ->deleteJson("/finance/installments/{$installmentId}");
        $delRes->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('finance_installments', ['id' => $installmentId]);
    }
}
