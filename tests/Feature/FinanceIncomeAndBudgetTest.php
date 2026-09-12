<?php

namespace Tests\Feature;

use App\Models\FinanceAccount;
use App\Models\FinanceIncomeOverride;
use App\Models\FinanceIncomeSchedule;
use App\Services\EssentialBudgetService;
use App\Services\FinanceLedgerService;
use App\Services\IncomeForecastService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceIncomeAndBudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_exact_calendar_weekday_calculation_for_month(): void
    {
        $forecastService = app(IncomeForecastService::class);
        $schedule = $forecastService->ensureDefaultSchedule();

        // Pattern: Mon=50k, Tue=30k, Wed=50k, Thu=20k, Fri=50k, Sat=30k, Sun=0.
        // For September 2026 (30 days):
        // 4 Mon (200k), 5 Tue (150k), 5 Wed (250k), 4 Thu (80k), 4 Fri (200k), 4 Sat (120k), 4 Sun (0k)
        // Total = 1.000.000
        $forecast = $forecastService->getForecastForMonth(2026, 9);

        $this->assertEquals(30, $forecast['total_days']);
        $this->assertEquals(1000000.00, $forecast['total_expected_income']);
        $this->assertCount(30, $forecast['daily_forecasts']);
    }

    public function test_date_override_precedence_over_recurring_weekday_pattern(): void
    {
        $forecastService = app(IncomeForecastService::class);
        $forecastService->ensureDefaultSchedule();

        // Sep 7, 2026 is Monday (normally 50.000)
        $mondayDate = '2026-09-07';
        $regularIncome = $forecastService->resolveIncomeForDate($mondayDate);
        $this->assertEquals(50000.00, $regularIncome['total_expected']);
        $this->assertFalse($regularIncome['has_override']);

        // Set date override for Sep 7 to 120.000
        FinanceIncomeOverride::create([
            'override_date' => $mondayDate,
            'title' => 'Uang saku khusus ujian',
            'amount' => 120000.00,
            'is_extra' => false,
        ]);

        $overriddenIncome = $forecastService->resolveIncomeForDate($mondayDate);
        $this->assertEquals(120000.00, $overriddenIncome['total_expected']);
        $this->assertTrue($overriddenIncome['has_override']);

        // Next Monday (Sep 14) still adheres to standard recurring pattern (50.000)
        $nextMonday = $forecastService->resolveIncomeForDate('2026-09-14');
        $this->assertEquals(50000.00, $nextMonday['total_expected']);
        $this->assertFalse($nextMonday['has_override']);
    }

    public function test_extra_income_adds_on_top_of_regular_income(): void
    {
        $forecastService = app(IncomeForecastService::class);
        $forecastService->ensureDefaultSchedule();

        // Sep 18, 2026 is Friday (regular 50.000)
        $fridayDate = '2026-09-18';

        FinanceIncomeOverride::create([
            'override_date' => $fridayDate,
            'title' => 'Freelance Website Proyek',
            'amount' => 500000.00,
            'is_extra' => true,
        ]);

        $resolved = $forecastService->resolveIncomeForDate($fridayDate);
        $this->assertEquals(50000.00, $resolved['expected_base']);
        $this->assertEquals(500000.00, $resolved['extra_income']);
        $this->assertEquals(550000.00, $resolved['total_expected']);
    }

    public function test_actual_income_does_not_overwrite_recurring_pattern_and_tracks_variance(): void
    {
        $forecastService = app(IncomeForecastService::class);
        $ledgerService = app(FinanceLedgerService::class);
        $forecastService->ensureDefaultSchedule();
        $ledgerService->ensureDefaultsExist();

        $account = FinanceAccount::first();

        // Expected Monday is 50.000
        $date = '2026-09-07';

        // Actual transaction entered is 30.000
        $ledgerService->recordTransaction([
            'account_id' => $account->id,
            'type' => 'income',
            'amount' => 30000.00,
            'transaction_date' => $date,
            'description' => 'Uang saku aktual diterima lebih sedikit',
        ]);

        $resolved = $forecastService->resolveIncomeForDate($date);

        $this->assertTrue($resolved['has_actual']);
        $this->assertEquals(50000.00, $resolved['total_expected']);
        $this->assertEquals(30000.00, $resolved['actual_amount']);
        $this->assertEquals(-20000.00, $resolved['variance']);

        // Recurring master schedule remains untouched
        $schedule = FinanceIncomeSchedule::first();
        $this->assertEquals(50000.00, $schedule->getAmountForIsoDay(1));
    }

    public function test_essential_daily_budget_calculation(): void
    {
        $budgetService = app(EssentialBudgetService::class);

        $budgetService->updateBudget([
            'food' => 25000.00,
            'transport' => 10000.00,
            'snack' => 5000.00,
            'other' => 5000.00,
        ]);

        $budget = $budgetService->getBudget();
        $this->assertEquals(45000.00, $budget->total_daily_minimum);

        // 30 days in September 2026 -> 45.000 * 30 = 1.350.000
        $monthlyRequirement = $budgetService->getMonthlyRequirement(2026, 9);
        $this->assertEquals(1350000.00, $monthlyRequirement);

        // From Sep 21 to Sep 30 (10 days remaining) -> 45.000 * 10 = 450.000
        $remaining = $budgetService->getRemainingMonthlyRequirement(Carbon::parse('2026-09-21'));
        $this->assertEquals(450000.00, $remaining);
    }

    public function test_api_endpoints_for_income_and_budget(): void
    {
        // 1. Update weekday income schedule
        $responseSchedule = $this->withSession(['authenticated' => true])
            ->postJson('/finance/income/schedule', [
                'type' => 'daily_variable',
                'weekday_amounts' => [
                    '1' => 60000,
                    '2' => 40000,
                    '3' => 60000,
                    '4' => 30000,
                    '5' => 60000,
                    '6' => 40000,
                    '7' => 10000,
                ],
            ]);

        $responseSchedule->assertStatus(200);
        $responseSchedule->assertJson(['success' => true]);

        // 2. Add income override
        $responseOverride = $this->withSession(['authenticated' => true])
            ->postJson('/finance/income/overrides', [
                'override_date' => '2026-09-18',
                'title' => 'Freelance App Mockup',
                'amount' => 750000,
                'is_extra' => true,
            ]);

        $responseOverride->assertStatus(200);
        $responseOverride->assertJson(['success' => true]);

        $this->assertDatabaseHas('finance_income_overrides', [
            'title' => 'Freelance App Mockup',
            'amount' => 750000.00,
        ]);

        // 3. Update essential budget
        $responseBudget = $this->withSession(['authenticated' => true])
            ->postJson('/finance/budget', [
                'food' => 22000,
                'transport' => 8000,
                'snack' => 2000,
                'other' => 3000,
            ]);

        $responseBudget->assertStatus(200);
        $responseBudget->assertJson(['success' => true]);

        $this->assertDatabaseHas('finance_essential_budgets', [
            'food' => 22000.00,
            'transport' => 8000.00,
        ]);
    }
}
