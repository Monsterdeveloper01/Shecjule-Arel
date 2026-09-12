<?php

namespace App\Services;

use App\Models\FinanceIncomeOverride;
use App\Models\FinanceIncomeSchedule;
use App\Models\FinanceTransaction;
use Carbon\Carbon;

class IncomeForecastService
{
    /**
     * Ensure a default recurring weekday income schedule exists.
     */
    public function ensureDefaultSchedule(): FinanceIncomeSchedule
    {
        $schedule = FinanceIncomeSchedule::first();

        if (! $schedule) {
            $schedule = FinanceIncomeSchedule::create([
                'type' => 'daily_variable',
                'weekday_amounts' => [
                    '1' => 50000.00, // Senin
                    '2' => 30000.00, // Selasa
                    '3' => 50000.00, // Rabu
                    '4' => 20000.00, // Kamis
                    '5' => 50000.00, // Jumat
                    '6' => 30000.00, // Sabtu
                    '7' => 0.00,     // Minggu
                ],
                'monthly_amount' => 0.00,
                'is_active' => true,
            ]);
        }

        return $schedule;
    }

    /**
     * Resolve expected and actual income for a specific date using exact calendar arithmetic and priority hierarchy.
     * Hierarchy: Actual Transaction -> Date Override -> Weekday Recurring Pattern -> 0.
     * Extra incomes (freelance, one-time) are added to expected.
     *
     * @return array{
     *     date: string,
     *     day_name: string,
     *     expected_base: float,
     *     extra_income: float,
     *     total_expected: float,
     *     actual_amount: float,
     *     has_actual: bool,
     *     has_override: bool,
     *     variance: float
     * }
     */
    public function resolveIncomeForDate(Carbon|string $date): array
    {
        $d = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);
        $dateStr = $d->toDateString();
        $schedule = $this->ensureDefaultSchedule();

        // 1. Check actual transactions from ledger
        $actual = (float) FinanceTransaction::income()
            ->whereDate('transaction_date', $dateStr)
            ->sum('amount');
        $hasActual = $actual > 0;

        // 2. Check date override
        $override = FinanceIncomeOverride::forDate($dateStr)
            ->where('is_extra', false)
            ->first();

        // 3. Check extra income (one-time freelance/bonus)
        $extra = (float) FinanceIncomeOverride::forDate($dateStr)
            ->where('is_extra', true)
            ->sum('amount');

        // Resolve expected base
        if ($override) {
            $expectedBase = (float) $override->amount;
            $hasOverride = true;
        } elseif ($schedule->is_active && $schedule->type === 'daily_variable') {
            $expectedBase = $schedule->getAmountForIsoDay($d->dayOfWeekIso);
            $hasOverride = false;
        } elseif ($schedule->is_active && $schedule->type === 'monthly' && $d->day === $schedule->monthly_due_day) {
            $expectedBase = (float) $schedule->monthly_amount;
            $hasOverride = false;
        } else {
            $expectedBase = 0.0;
            $hasOverride = false;
        }

        $totalExpected = round($expectedBase + $extra, 2);
        $variance = $hasActual ? round($actual - $totalExpected, 2) : 0.0;

        return [
            'date' => $dateStr,
            'day_name' => $d->translatedFormat('l'),
            'expected_base' => round($expectedBase, 2),
            'extra_income' => round($extra, 2),
            'total_expected' => $totalExpected,
            'actual_amount' => round($actual, 2),
            'has_actual' => $hasActual,
            'has_override' => $hasOverride,
            'variance' => $variance,
        ];
    }

    /**
     * Calculate monthly forecast based strictly on actual calendar arithmetic (counting exact weekdays in the month).
     *
     * @return array{
     *     year: int,
     *     month: int,
     *     month_name: string,
     *     total_days: int,
     *     total_expected_income: float,
     *     actual_income_to_date: float,
     *     remaining_expected_income: float,
     *     extra_income_this_month: float,
     *     total_variance_to_date: float,
     *     daily_forecasts: array<int, array<string, mixed>>
     * }
     */
    public function getForecastForMonth(int $year, int $month): array
    {
        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $daysInMonth = $startOfMonth->daysInMonth;
        $todayStr = now()->toDateString();

        $totalExpected = 0.0;
        $actualToDate = 0.0;
        $remainingExpected = 0.0;
        $extraIncomeTotal = 0.0;
        $varianceToDate = 0.0;
        $dailyForecasts = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = Carbon::createFromDate($year, $month, $day)->startOfDay();
            $resolved = $this->resolveIncomeForDate($currentDate);

            $totalExpected += $resolved['total_expected'];
            $extraIncomeTotal += $resolved['extra_income'];

            if ($resolved['has_actual']) {
                $actualToDate += $resolved['actual_amount'];
                $varianceToDate += $resolved['variance'];
            }

            // If date is in the future relative to today, add to remaining expected income
            if ($resolved['date'] > $todayStr) {
                $remainingExpected += $resolved['total_expected'];
            } elseif ($resolved['date'] === $todayStr && ! $resolved['has_actual']) {
                // If today has not received income yet, it's still expected remaining today
                $remainingExpected += $resolved['total_expected'];
            }

            $dailyForecasts[] = $resolved;
        }

        return [
            'year' => $year,
            'month' => $month,
            'month_name' => $startOfMonth->translatedFormat('F Y'),
            'total_days' => $daysInMonth,
            'total_expected_income' => round($totalExpected, 2),
            'actual_income_to_date' => round($actualToDate, 2),
            'remaining_expected_income' => round($remainingExpected, 2),
            'extra_income_this_month' => round($extraIncomeTotal, 2),
            'total_variance_to_date' => round($varianceToDate, 2),
            'daily_forecasts' => $dailyForecasts,
        ];
    }
}
