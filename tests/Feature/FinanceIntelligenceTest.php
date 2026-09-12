<?php

namespace Tests\Feature;

use App\Models\FinanceAccount;
use App\Models\FinanceEssentialBudget;
use App\Models\FinanceIncomeSchedule;
use App\Models\FinanceInstallment;
use App\Models\FinanceSavingsGoal;
use App\Services\FinanceIntelligenceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_safe_to_spend_calculation_logic(): void
    {
        $evalDate = Carbon::parse('2026-09-15'); // Tuesday

        FinanceAccount::create([
            'name' => 'Dompet Kas Utama',
            'type' => 'cash',
            'opening_balance' => 500000.00,
            'current_balance' => 500000.00,
            'installment_reserve' => 0.00,
            'savings_reserve' => 0.00,
            'is_primary' => true,
        ]);

        FinanceIncomeSchedule::create([
            'type' => 'daily_variable',
            'weekday_amounts' => [
                '1' => 50000.00,
                '2' => 80000.00, // Tuesday = 80.000
                '3' => 50000.00,
                '4' => 30000.00,
                '5' => 50000.00,
                '6' => 0.00,
                '7' => 0.00,
            ],
            'is_active' => true,
        ]);

        FinanceEssentialBudget::create([
            'food' => 20000.00,
            'transport' => 10000.00,
            'snack' => 0.00,
            'other' => 0.00,
            'notes' => 'Kebutuhan pokok minimum',
        ]);

        // Installment due on Sep 24 (10 eligible days from Sep 15 up to and including Sep 24)
        FinanceInstallment::create([
            'name' => 'Cicilan Smartphone',
            'total_amount' => 1200000.00,
            'monthly_amount' => 200000.00,
            'due_day' => 24,
            'start_date' => '2026-09-01',
            'status' => 'active',
        ]);

        /** @var FinanceIntelligenceService $intelligenceService */
        $intelligenceService = app(FinanceIntelligenceService::class);
        $result = $intelligenceService->calculateSafeToSpend($evalDate);

        $this->assertEquals('2026-09-15', $result['date']);
        $this->assertEquals(80000.00, $result['expected_income_today']);
        $this->assertEquals(30000.00, $result['essential_allowance_today']);
        // 200.000 remaining due in 10 days = 20.000/day
        $this->assertEquals(20000.00, $result['installment_daily_today']);
        $this->assertEquals(50000.00, $result['total_commitments_today']);

        // Safe to spend = 80.000 - 50.000 = 30.000
        $this->assertEquals(30000.00, $result['safe_to_spend_base']);
        $this->assertEquals(30000.00, $result['safe_to_spend_today']);
        $this->assertEquals(500000.00, $result['available_cash']);
        $this->assertEquals('positive', $result['status_tone']);
    }

    public function test_deterministic_financial_status_rules(): void
    {
        FinanceAccount::create([
            'name' => 'Rekening Tabungan',
            'type' => 'bank',
            'opening_balance' => 1000000.00,
            'current_balance' => 1000000.00,
            'is_primary' => true,
        ]);

        // Default recurring income: Sep 2026 = 1.000.000 exactly
        FinanceIncomeSchedule::create([
            'type' => 'daily_variable',
            'weekday_amounts' => [
                '1' => 50000.00,
                '2' => 30000.00,
                '3' => 50000.00,
                '4' => 20000.00,
                '5' => 50000.00,
                '6' => 30000.00,
                '7' => 0.00,
            ],
            'is_active' => true,
        ]);

        // Daily essential = 20.000 => monthly requirement = 20.000 * 30 = 600.000
        FinanceEssentialBudget::create([
            'food' => 20000.00,
            'transport' => 0.00,
            'snack' => 0.00,
            'other' => 0.00,
        ]);

        /** @var FinanceIntelligenceService $intelligenceService */
        $intelligenceService = app(FinanceIntelligenceService::class);

        // Case 1: Obligations = 600.000 (buffer = 400.000 = 40% >= 20%) -> HEALTHY
        $statusHealthy = $intelligenceService->getMonthlyForecastAndStatus(2026, 9);
        $this->assertEquals('HEALTHY', $statusHealthy['status']);
        $this->assertGreaterThanOrEqual(20.0, $statusHealthy['buffer_percent']);
        $this->assertNotEmpty($statusHealthy['insights']);

        // Case 2: Add installment 250.000 -> obligations = 850.000 (buffer = 150.000 = 15.0% <= 20% and > 5%) -> ATTENTION
        $installment = FinanceInstallment::create([
            'name' => 'Cicilan Ringan',
            'total_amount' => 2500000.00,
            'monthly_amount' => 250000.00,
            'due_day' => 25,
            'start_date' => '2026-09-01',
            'status' => 'active',
        ]);

        $statusAttention = $intelligenceService->getMonthlyForecastAndStatus(2026, 9);
        $this->assertEquals('ATTENTION', $statusAttention['status']);

        // Case 3: Add deadline savings 120.000 -> obligations = 970.000 (buffer = 30.000 = 3.0% <= 5% and >= 0%) -> TIGHT
        FinanceSavingsGoal::create([
            'name' => 'Target Nabung Ketat',
            'target_amount' => 120000.00,
            'current_amount' => 0.00,
            'type' => 'deadline',
            'target_date' => '2026-09-30',
            'status' => 'active',
        ]);

        $statusTight = $intelligenceService->getMonthlyForecastAndStatus(2026, 9);
        $this->assertEquals('TIGHT', $statusTight['status']);

        // Case 4: Increase installment to 450.000 -> obligations = 1.170.000 (buffer = -170.000 < 0) -> DEFICIT
        $installment->update(['monthly_amount' => 450000.00]);

        $statusDeficit = $intelligenceService->getMonthlyForecastAndStatus(2026, 9);
        $this->assertEquals('DEFICIT', $statusDeficit['status']);
        $this->assertLessThan(0.0, $statusDeficit['projected_buffer']);
    }

    public function test_calendar_data_returns_finances(): void
    {
        FinanceAccount::create([
            'name' => 'BCA',
            'type' => 'bank',
            'opening_balance' => 500000.00,
            'current_balance' => 500000.00,
            'is_primary' => true,
        ]);

        FinanceInstallment::create([
            'name' => 'Tagihan Listrik & WiFi',
            'total_amount' => 450000.00,
            'monthly_amount' => 450000.00,
            'due_day' => 20,
            'start_date' => '2026-09-01',
            'status' => 'active',
        ]);

        FinanceSavingsGoal::create([
            'name' => 'Target Liburan',
            'target_amount' => 1000000.00,
            'current_amount' => 200000.00,
            'type' => 'deadline',
            'target_date' => '2026-09-28',
            'status' => 'active',
        ]);

        $response = $this->withSession(['authenticated' => true])
            ->getJson('/calendar-data?year=2026&month=9');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'tasks',
            'events',
            'notes',
            'finances',
        ]);

        $json = $response->json();
        $this->assertArrayHasKey('2026-09-20', $json['finances']);
        $this->assertEquals('Jatuh Tempo: Tagihan Listrik & WiFi', $json['finances']['2026-09-20'][0]['title']);

        $this->assertArrayHasKey('2026-09-28', $json['finances']);
        $this->assertEquals('Target: Target Liburan', $json['finances']['2026-09-28'][0]['title']);
    }

    public function test_dashboard_renders_finance_overview_widget(): void
    {
        FinanceAccount::create([
            'name' => 'Dompet',
            'type' => 'cash',
            'opening_balance' => 350000.00,
            'current_balance' => 350000.00,
            'is_primary' => true,
        ]);

        $response = $this->withSession(['authenticated' => true])
            ->get('/');

        $response->assertStatus(200);
        $response->assertViewHas('financeOverview');
        $response->assertSee('Safe to Spend Hari Ini:');
        $response->assertSee('Buku Kas');
    }

    public function test_safe_to_spend_api_endpoint(): void
    {
        FinanceAccount::create([
            'name' => 'BCA Debit',
            'type' => 'bank',
            'opening_balance' => 750000.00,
            'current_balance' => 750000.00,
            'is_primary' => true,
        ]);

        $response = $this->withSession(['authenticated' => true])
            ->postJson('/finance/safe-to-spend', [
                'date' => '2026-09-22',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'date',
                'safe_to_spend_today',
                'expected_income_today',
                'essential_allowance_today',
                'installment_daily_today',
                'savings_daily_today',
                'available_cash',
                'status_tone',
            ],
        ]);
    }
}
