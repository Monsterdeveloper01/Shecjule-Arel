<?php

namespace Tests\Feature;

use App\Models\FinanceAccount;
use App\Models\FinanceCategory;
use App\Services\FinanceLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_index_screen_loads_and_ensures_defaults_exist(): void
    {
        $response = $this->withSession(['authenticated' => true])
            ->get('/finance');

        $response->assertStatus(200);
        $response->assertSee('Manajemen Kas');
        $response->assertSee('Dompet Kas Utama');

        $this->assertDatabaseHas('finance_accounts', [
            'name' => 'Dompet Kas Utama',
            'is_primary' => true,
        ]);

        $this->assertDatabaseHas('finance_categories', [
            'name' => 'Makanan & Minuman',
            'type' => 'expense',
        ]);
    }

    public function test_record_income_increases_actual_balance(): void
    {
        $account = FinanceAccount::create([
            'name' => 'Dompet Fisik',
            'type' => 'cash',
            'opening_balance' => 100000.00,
            'current_balance' => 100000.00,
        ]);

        $category = FinanceCategory::create([
            'name' => 'Uang Saku',
            'type' => 'income',
        ]);

        $service = app(FinanceLedgerService::class);
        $service->recordTransaction([
            'account_id' => $account->id,
            'type' => 'income',
            'category_id' => $category->id,
            'amount' => 50000.00,
            'transaction_date' => now()->toDateString(),
            'description' => 'Uang saku mingguan',
        ]);

        $account->refresh();
        $this->assertEquals(150000.00, (float) $account->current_balance);
        $this->assertEquals(150000.00, $account->available_spending);

        $this->assertDatabaseHas('finance_transactions', [
            'account_id' => $account->id,
            'type' => 'income',
            'amount' => 50000.00,
        ]);
    }

    public function test_record_expense_decreases_actual_balance(): void
    {
        $account = FinanceAccount::create([
            'name' => 'Rekening BCA',
            'type' => 'bank',
            'opening_balance' => 200000.00,
            'current_balance' => 200000.00,
        ]);

        $service = app(FinanceLedgerService::class);
        $service->recordTransaction([
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 45000.00,
            'transaction_date' => now()->toDateString(),
            'description' => 'Makan malam ayam geprek',
        ]);

        $account->refresh();
        $this->assertEquals(155000.00, (float) $account->current_balance);
        $this->assertEquals(155000.00, $account->available_spending);
    }

    public function test_transfer_between_accounts_maintains_total_cash(): void
    {
        $accA = FinanceAccount::create([
            'name' => 'Rekening Bank',
            'type' => 'bank',
            'opening_balance' => 300000.00,
            'current_balance' => 300000.00,
        ]);

        $accB = FinanceAccount::create([
            'name' => 'Dompet Fisik',
            'type' => 'cash',
            'opening_balance' => 50000.00,
            'current_balance' => 50000.00,
        ]);

        $service = app(FinanceLedgerService::class);
        $service->recordTransaction([
            'account_id' => $accA->id,
            'destination_account_id' => $accB->id,
            'type' => 'transfer',
            'amount' => 100000.00,
            'transaction_date' => now()->toDateString(),
            'description' => 'Tarik tunai ATM',
        ]);

        $accA->refresh();
        $accB->refresh();

        $this->assertEquals(200000.00, (float) $accA->current_balance);
        $this->assertEquals(150000.00, (float) $accB->current_balance);

        $buckets = $service->getBalanceBuckets();
        $this->assertEquals(350000.00, $buckets['total_balance']);
    }

    public function test_savings_allocation_locks_reserve_without_treating_as_consumption_expense(): void
    {
        $account = FinanceAccount::create([
            'name' => 'Dompet Kas',
            'type' => 'cash',
            'opening_balance' => 500000.00,
            'current_balance' => 500000.00,
            'savings_reserve' => 0.00,
            'installment_reserve' => 0.00,
        ]);

        $service = app(FinanceLedgerService::class);
        $service->recordTransaction([
            'account_id' => $account->id,
            'type' => 'savings_allocation',
            'amount' => 120000.00,
            'transaction_date' => now()->toDateString(),
            'description' => 'Kunci dana untuk target tabungan laptop',
        ]);

        $account->refresh();

        // Total cash is still 500k (not lost/spent)
        $this->assertEquals(500000.00, (float) $account->current_balance);
        // But savings reserve is now 120k
        $this->assertEquals(120000.00, (float) $account->savings_reserve);
        // And available spending is reduced to 380k
        $this->assertEquals(380000.00, $account->available_spending);
    }

    public function test_deleting_transaction_recalculates_ledger_balance(): void
    {
        $account = FinanceAccount::create([
            'name' => 'Kas Saku',
            'type' => 'cash',
            'opening_balance' => 100000.00,
            'current_balance' => 100000.00,
        ]);

        $service = app(FinanceLedgerService::class);
        $tx = $service->recordTransaction([
            'account_id' => $account->id,
            'type' => 'income',
            'amount' => 50000.00,
            'transaction_date' => now()->toDateString(),
            'description' => 'Salah catat pemasukan',
        ]);

        $account->refresh();
        $this->assertEquals(150000.00, (float) $account->current_balance);

        $response = $this->withSession(['authenticated' => true])
            ->deleteJson("/finance/transactions/{$tx->id}");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $account->refresh();
        $this->assertEquals(100000.00, (float) $account->current_balance);
    }

    public function test_unauthenticated_user_cannot_access_finance(): void
    {
        $response = $this->get('/finance');
        $response->assertRedirect('/login');
    }
}
