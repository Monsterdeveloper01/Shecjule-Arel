<?php

namespace App\Services;

use App\Models\FinanceIncomeOverride;
use App\Models\FinanceInstallment;
use App\Models\FinanceSavingsGoal;
use App\Models\FinanceTransaction;
use Carbon\Carbon;

class FinanceIntelligenceService
{
    public function __construct(
        protected FinanceLedgerService $ledgerService,
        protected IncomeForecastService $forecastService,
        protected EssentialBudgetService $budgetService,
        protected InstallmentService $installmentService,
        protected SavingsGoalService $savingsService
    ) {}

    /**
     * Calculate Time-Aware Safe-to-Spend for a specific date (defaults to today).
     *
     * @return array{
     *     date: string,
     *     expected_income: float,
     *     essential_budget: float,
     *     installment_allocation: float,
     *     savings_allocation: float,
     *     base_safe_to_spend: float,
     *     actual_expense_today: float,
     *     remaining_safe_to_spend: float,
     *     available_balance: float,
     *     total_actual_balance: float
     * }
     */
    public function calculateSafeToSpend(?Carbon $date = null): array
    {
        $d = $date ? $date->copy() : now();
        $dateStr = $d->toDateString();

        // 1. Income for the day (resolved with hierarchy: Actual > Override > Recurring > 0)
        $todayIncome = $this->forecastService->resolveIncomeForDate($d);
        $expectedIncome = (float) ($todayIncome['total_expected'] ?? 0.0);

        // 2. Essential daily minimum
        $dailyBudget = (float) $this->budgetService->getDailyRequirement();

        // 3. Installment daily allocation required today
        $instSummary = $this->installmentService->getInstallmentsSummary($d);
        $dailyInstallment = (float) ($instSummary['total_daily_required_today'] ?? 0.0);

        // 4. Savings daily requirement today
        $estimatedSurplus = max(0.0, $expectedIncome - $dailyBudget - $dailyInstallment);
        $savingsSummary = $this->savingsService->getSavingsGoalsSummary($d, $estimatedSurplus);
        $dailySavings = (float) ($savingsSummary['total_daily_required_today'] ?? 0.0);

        // 5. Safe to Spend calculation
        $baseSafeToSpend = max(0.0, round($expectedIncome - $dailyBudget - $dailyInstallment - $dailySavings, 2));

        // 6. Actual expenses occurred today
        $actualExpense = (float) FinanceTransaction::whereDate('transaction_date', $dateStr)
            ->where('type', 'expense')
            ->sum('amount');

        $remainingSafeToSpend = max(0.0, round($baseSafeToSpend - $actualExpense, 2));

        // 7. Cash available balance from buckets
        $buckets = $this->ledgerService->getBalanceBuckets();

        $statusTone = 'positive';
        if ($remainingSafeToSpend <= 0.0 || $buckets['available_spending'] <= 0.0) {
            $statusTone = 'warning';
        } elseif ($remainingSafeToSpend < ($dailyBudget * 0.5)) {
            $statusTone = 'cautious';
        }

        return [
            'date' => $dateStr,
            'is_today' => $d->isToday(),
            'expected_income' => round($expectedIncome, 2),
            'expected_income_today' => round($expectedIncome, 2),
            'essential_budget' => round($dailyBudget, 2),
            'essential_allowance_today' => round($dailyBudget, 2),
            'installment_allocation' => round($dailyInstallment, 2),
            'installment_daily_today' => round($dailyInstallment, 2),
            'savings_allocation' => round($dailySavings, 2),
            'savings_daily_today' => round($dailySavings, 2),
            'total_commitments_today' => round($dailyBudget + $dailyInstallment + $dailySavings, 2),
            'base_safe_to_spend' => $baseSafeToSpend,
            'safe_to_spend_base' => $baseSafeToSpend,
            'actual_expense_today' => round($actualExpense, 2),
            'actual_spent_today' => round($actualExpense, 2),
            'remaining_safe_to_spend' => $remainingSafeToSpend,
            'safe_to_spend_today' => $remainingSafeToSpend,
            'available_balance' => round($buckets['available_spending'], 2),
            'available_cash' => round($buckets['available_spending'], 2),
            'total_actual_balance' => round($buckets['total_balance'], 2),
            'daily_hard_limit' => max(0.0, round($buckets['available_spending'] + $baseSafeToSpend, 2)),
            'status_tone' => $statusTone,
        ];
    }

    /**
     * Get monthly forecast, deterministic financial status (HEALTHY / ATTENTION / TIGHT / DEFICIT), and factual insights.
     *
     * @return array{
     *     year: int,
     *     month: int,
     *     total_days: int,
     *     monthly_expected_income: float,
     *     total_income: float,
     *     monthly_essential: float,
     *     total_essential: float,
     *     monthly_installments: float,
     *     total_installments: float,
     *     monthly_savings: float,
     *     total_savings_target: float,
     *     total_obligations: float,
     *     projected_net_buffer: float,
     *     projected_buffer: float,
     *     buffer_percent: float,
     *     status: string,
     *     status_label: string,
     *     status_color: string,
     *     status_description: string,
     *     insights: array<int, string>
     * }
     */
    public function getMonthlyForecastAndStatus(int $year, int $month): array
    {
        $d = Carbon::createFromDate($year, $month, 1);
        $forecast = $this->forecastService->getForecastForMonth($year, $month);
        $monthlyIncome = (float) $forecast['total_expected_income'];
        $monthlyEssential = (float) $this->budgetService->getMonthlyRequirement($year, $month);

        $instSummary = $this->installmentService->getInstallmentsSummary($d);
        $monthlyInstallments = (float) $instSummary['total_monthly_obligation'];

        $estimatedDailySurplus = $forecast['total_days'] > 0
            ? max(0.0, ($monthlyIncome - $monthlyEssential - $monthlyInstallments) / $forecast['total_days'])
            : 0.0;

        $savingsSummary = $this->savingsService->getSavingsGoalsSummary($d, $estimatedDailySurplus);
        $monthlySavings = 0.0;
        foreach ($savingsSummary['items'] as $it) {
            if ($it['type'] === 'deadline' && ! $it['is_achieved']) {
                $monthlySavings += (float) $it['monthly_required'];
            }
        }

        $projectedBuffer = round($monthlyIncome - $monthlyEssential - $monthlyInstallments - $monthlySavings, 2);
        $bufferPercent = $monthlyIncome > 0.0 ? round(($projectedBuffer / $monthlyIncome) * 100, 1) : 0.0;

        // Deterministic Status Rules (Rule 9 & Rule 10 strictly enforced)
        if ($projectedBuffer < 0.0) {
            $status = 'DEFICIT';
            $statusLabel = 'Defisit Finansial';
            $statusColor = '#ef4444';
            $statusDesc = 'Pengeluaran pokok dan komitmen cicilan melebihi proyeksi pemasukan bulan ini. Perlu penyesuaian belanja diskresioner atau penambahan pemasukan.';
        } elseif ($monthlyIncome > 0.0 && ($projectedBuffer / $monthlyIncome) <= 0.05) {
            $status = 'TIGHT';
            $statusLabel = 'Sangat Ketat';
            $statusColor = '#f97316';
            $statusDesc = 'Sisa buffer sangat tipis (di bawah 5% pemasukan). Hindari pengeluaran impulsif agar pos pokok dan cicilan tetap aman.';
        } elseif ($monthlyIncome > 0.0 && ($projectedBuffer / $monthlyIncome) <= 0.20) {
            $status = 'ATTENTION';
            $statusLabel = 'Perlu Perhatian';
            $statusColor = '#eab308';
            $statusDesc = 'Kondisi keuangan terkendali dengan buffer 5%–20%. Disarankan disiplin mencadangkan dana cicilan harian.';
        } else {
            $status = 'HEALTHY';
            $statusLabel = 'Sehat & Stabil';
            $statusColor = '#10b981';
            $statusDesc = 'Kondisi keuangan prima dengan buffer aman di atas 20% estimasi pemasukan bulanan.';
        }

        // Factual Empirical Insights
        $insights = [];
        if ($monthlyIncome > 0.0) {
            $essentialRatio = round(($monthlyEssential / $monthlyIncome) * 100);
            $insights[] = "Kebutuhan pokok harian menyerap {$essentialRatio}% dari estimasi pemasukan bulanan.";

            if ($monthlyInstallments > 0.0) {
                $instRatio = round(($monthlyInstallments / $monthlyIncome) * 100);
                $insights[] = "Kewajiban cicilan menyerap {$instRatio}% pemasukan bulanan ({$instSummary['active_count']} tagihan aktif).";
            }

            if ($projectedBuffer > 0.0) {
                $insights[] = 'Proyeksi sisa buffer kas akhir bulan diperkirakan Rp '.number_format($projectedBuffer, 0, ',', '.').'.';
            } else {
                $insights[] = 'Defisit bulanan diproyeksikan sebesar Rp '.number_format(abs($projectedBuffer), 0, ',', '.').'.';
            }
        } else {
            $insights[] = 'Pola pemasukan belum diatur. Atur pola pemasukan untuk menghasilkan analisis akurat.';
        }

        return [
            'year' => $year,
            'month' => $month,
            'total_days' => $forecast['total_days'],
            'monthly_expected_income' => round($monthlyIncome, 2),
            'total_income' => round($monthlyIncome, 2),
            'monthly_essential' => round($monthlyEssential, 2),
            'total_essential' => round($monthlyEssential, 2),
            'monthly_installments' => round($monthlyInstallments, 2),
            'total_installments' => round($monthlyInstallments, 2),
            'monthly_savings' => round($monthlySavings, 2),
            'total_savings_target' => round($monthlySavings, 2),
            'total_obligations' => round($monthlyEssential + $monthlyInstallments + $monthlySavings, 2),
            'projected_net_buffer' => $projectedBuffer,
            'projected_buffer' => $projectedBuffer,
            'buffer_percent' => $bufferPercent,
            'status' => $status,
            'status_label' => $statusLabel,
            'status_color' => $statusColor,
            'status_description' => $statusDesc,
            'insights' => $insights,
        ];
    }

    /**
     * Get financial events for a specific month formatted for Calendar integration.
     *
     * @return array<string, array<int, array{type: string, title: string, amount: float, badge_color: string, icon: string}>>
     */
    public function getCalendarFinanceEvents(int $year, int $month): array
    {
        $eventsByDate = [];

        // 1. Installments due dates in this month
        $installments = FinanceInstallment::where('status', 'active')->get();
        foreach ($installments as $inst) {
            $dueDate = $inst->getDueDateForMonth($year, $month)->toDateString();
            $eventsByDate[$dueDate][] = [
                'type' => 'installment',
                'title' => "Jatuh Tempo: {$inst->name}",
                'amount' => (float) $inst->monthly_amount,
                'badge_color' => '#f472b6',
                'icon' => '💳',
            ];
        }

        // 2. Savings goals deadlines in this month
        $savingsGoals = FinanceSavingsGoal::where('type', 'deadline')
            ->where('status', 'active')
            ->whereYear('target_date', $year)
            ->whereMonth('target_date', $month)
            ->get();

        foreach ($savingsGoals as $goal) {
            $dateStr = $goal->target_date->toDateString();
            $eventsByDate[$dateStr][] = [
                'type' => 'savings_deadline',
                'title' => "Target: {$goal->name}",
                'amount' => (float) $goal->target_amount,
                'badge_color' => '#38bdf8',
                'icon' => $goal->icon ?? '🎯',
            ];
        }

        // 3. Date overrides & extra incomes in this month
        $overrides = FinanceIncomeOverride::forMonth($year, $month)->get();
        foreach ($overrides as $ov) {
            $dateStr = $ov->override_date->toDateString();
            $eventsByDate[$dateStr][] = [
                'type' => $ov->is_extra ? 'extra_income' : 'income_override',
                'title' => $ov->title,
                'amount' => (float) $ov->amount,
                'badge_color' => '#4ade80',
                'icon' => $ov->is_extra ? '🌟' : '✏️',
            ];
        }

        return $eventsByDate;
    }

    /**
     * Get concise Today Dashboard widget payload.
     *
     * @return array{
     *     safe_to_spend: float,
     *     safe_to_spend_remaining: float,
     *     available_balance: float,
     *     status: string,
     *     status_label: string,
     *     status_color: string,
     *     upcoming_obligations: array<int, array{name: string, amount: float, due_date: string, days_remaining: int}>
     * }
     */
    public function getTodayDashboardWidgetData(): array
    {
        $now = now();
        $safeToSpend = $this->calculateSafeToSpend($now);
        $forecast = $this->getMonthlyForecastAndStatus($now->year, $now->month);

        // Upcoming obligations in next 7 days
        $upcoming = [];
        $instSummary = $this->installmentService->getInstallmentsSummary($now);
        foreach ($instSummary['items'] as $it) {
            if (! $it['is_paid'] && $it['status'] === 'active' && $it['days_remaining'] <= 7) {
                $upcoming[] = [
                    'name' => $it['name'],
                    'amount' => (float) $it['monthly_amount'],
                    'due_date' => $it['due_date'],
                    'days_remaining' => $it['days_remaining'],
                ];
            }
        }

        return [
            'safe_to_spend' => $safeToSpend['base_safe_to_spend'],
            'safe_to_spend_remaining' => $safeToSpend['remaining_safe_to_spend'],
            'available_balance' => $safeToSpend['available_balance'],
            'status' => $forecast['status'],
            'status_label' => $forecast['status_label'],
            'status_color' => $forecast['status_color'],
            'upcoming_obligations' => $upcoming,
        ];
    }
}
