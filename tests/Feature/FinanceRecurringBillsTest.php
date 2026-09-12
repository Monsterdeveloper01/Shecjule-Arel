<?php

namespace Tests\Feature;

use App\Models\FinanceAccount;
use App\Models\FinanceInstallment;
use App\Services\InstallmentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceRecurringBillsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_recurring_bill_without_total_amount(): void
    {
        $response = $this->withSession(['authenticated' => true])
            ->postJson('/finance/installments', [
                'name' => 'WiFi Indihome Rumah',
                'type' => 'recurring_bill',
                'category' => 'internet',
                'monthly_amount' => 350000,
                'due_day' => 10,
                'start_date' => '2026-09-01',
                'notes' => 'Tagihan internet rumah',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('finance_installments', [
            'name' => 'WiFi Indihome Rumah',
            'type' => 'recurring_bill',
            'monthly_amount' => 350000.00,
            'total_amount' => null,
            'status' => 'active',
        ]);

        $bill = FinanceInstallment::where('name', 'WiFi Indihome Rumah')->first();
        $this->assertTrue($bill->isRecurringBill());
        $this->assertFalse($bill->isDebt());
        $this->assertNull($bill->remaining_total);
        $this->assertNull($bill->progress_percent);
    }

    public function test_debt_requires_total_amount_while_recurring_bill_does_not(): void
    {
        // Debt without total_amount should fail validation
        $responseDebt = $this->withSession(['authenticated' => true])
            ->postJson('/finance/installments', [
                'name' => 'Kredit Motor',
                'type' => 'debt',
                'category' => 'kendaraan',
                'monthly_amount' => 500000,
                'due_day' => 20,
                'start_date' => '2026-09-01',
            ]);

        $responseDebt->assertStatus(422)
            ->assertJsonValidationErrors(['total_amount']);

        // Recurring bill without total_amount should succeed
        $responseBill = $this->withSession(['authenticated' => true])
            ->postJson('/finance/installments', [
                'name' => 'Kost Bulanan',
                'type' => 'recurring_bill',
                'category' => 'kost',
                'monthly_amount' => 1200000,
                'due_day' => 5,
                'start_date' => '2026-09-01',
            ]);

        $responseBill->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_paying_recurring_bill_remains_active_for_future_months(): void
    {
        $account = FinanceAccount::create([
            'name' => 'Bank Mandiri',
            'type' => 'bank',
            'opening_balance' => 3000000.00,
            'current_balance' => 3000000.00,
        ]);

        $bill = FinanceInstallment::create([
            'name' => 'Listrik & Air Kos',
            'type' => 'recurring_bill',
            'category' => 'listrik',
            'monthly_amount' => 250000.00,
            'total_amount' => null,
            'due_day' => 15,
            'start_date' => '2026-09-01',
            'status' => 'active',
        ]);

        $installmentService = app(InstallmentService::class);

        // Reserve 100.000 first
        $installmentService->allocateReserve($bill->id, $account->id, 100000.00, 'Tabungan listrik harian', '2026-09-10');

        $account->refresh();
        $this->assertEquals(100000.00, (float) $account->installment_reserve);
        $this->assertEquals(3000000.00, (float) $account->current_balance);

        // Execute payment for current month
        $tx = $installmentService->payInstallment(
            $bill->id,
            $account->id,
            250000.00,
            '2026-09-15',
            'Bayar via m-banking'
        );

        $account->refresh();
        $bill->refresh();

        // Account balance decreased by 250.000
        $this->assertEquals(2750000.00, (float) $account->current_balance);
        // Reserve released back to 0
        $this->assertEquals(0.00, (float) $account->installment_reserve);

        // Transaction recorded with tagihan rutin label
        $this->assertDatabaseHas('finance_transactions', [
            'type' => 'installment_payment',
            'account_id' => $account->id,
            'amount' => 250000.00,
        ]);

        $this->assertStringContainsString('Pembayaran Tagihan Rutin', $tx->description);

        // CRITICAL CHECK: Recurring bill MUST stay 'active', NOT 'paid_off'
        $this->assertEquals('active', $bill->status);
    }

    public function test_paying_debt_off_marks_status_paid_off(): void
    {
        $account = FinanceAccount::create([
            'name' => 'BCA',
            'type' => 'bank',
            'opening_balance' => 5000000.00,
            'current_balance' => 5000000.00,
        ]);

        $debt = FinanceInstallment::create([
            'name' => 'Pinjaman Teman',
            'type' => 'debt',
            'category' => 'lainnya',
            'monthly_amount' => 500000.00,
            'total_amount' => 500000.00, // Remaining will reach 0 on first payment
            'due_day' => 25,
            'start_date' => '2026-09-01',
            'status' => 'active',
        ]);

        $installmentService = app(InstallmentService::class);

        $installmentService->payInstallment(
            $debt->id,
            $account->id,
            500000.00,
            '2026-09-20'
        );

        $debt->refresh();
        // Debt with remaining_total <= 0 is marked paid_off
        $this->assertEquals('paid_off', $debt->status);
    }

    public function test_recurring_bill_included_in_installment_summary_and_daily_allocation(): void
    {
        $bill = FinanceInstallment::create([
            'name' => 'Gym Membership',
            'type' => 'recurring_bill',
            'category' => 'gym',
            'monthly_amount' => 300000.00,
            'due_day' => 20,
            'start_date' => '2026-09-01',
            'status' => 'active',
        ]);

        $installmentService = app(InstallmentService::class);
        $summary = $installmentService->getInstallmentsSummary(Carbon::parse('2026-09-10'));

        $this->assertCount(1, $summary['items']);
        $item = $summary['items'][0];

        $this->assertEquals('recurring_bill', $item['type']);
        $this->assertEquals('Gym Membership', $item['name']);
        $this->assertEquals(300000.00, $item['monthly_amount']);
        $this->assertEquals(11, $item['days_remaining']); // Sep 10 to Sep 20 inclusive = 11 days
        $this->assertGreaterThan(0, $item['daily_required']);
        $this->assertNull($item['total_amount']);
    }
}
