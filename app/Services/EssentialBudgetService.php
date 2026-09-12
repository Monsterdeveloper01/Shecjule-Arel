<?php

namespace App\Services;

use App\Models\FinanceEssentialBudget;
use Carbon\Carbon;

class EssentialBudgetService
{
    /**
     * Ensure default essential budget exists.
     */
    public function ensureDefaultBudget(): FinanceEssentialBudget
    {
        $budget = FinanceEssentialBudget::first();

        if (! $budget) {
            $budget = FinanceEssentialBudget::create([
                'food' => 20000.00,
                'transport' => 10000.00,
                'snack' => 0.00,
                'other' => 5000.00,
                'notes' => 'Kebutuhan pokok harian minimum standar mahasiswa',
            ]);
        }

        return $budget;
    }

    /**
     * Get current essential budget.
     */
    public function getBudget(): FinanceEssentialBudget
    {
        return $this->ensureDefaultBudget();
    }

    /**
     * Get daily minimum essential requirement.
     */
    public function getDailyRequirement(): float
    {
        return (float) $this->ensureDefaultBudget()->total_daily_minimum;
    }

    /**
     * Update essential budget values.
     *
     * @param  array{food?: float, transport?: float, snack?: float, other?: float, notes?: string|null}  $data
     */
    public function updateBudget(array $data): FinanceEssentialBudget
    {
        $budget = $this->ensureDefaultBudget();

        $budget->update([
            'food' => isset($data['food']) ? round((float) $data['food'], 2) : $budget->food,
            'transport' => isset($data['transport']) ? round((float) $data['transport'], 2) : $budget->transport,
            'snack' => isset($data['snack']) ? round((float) $data['snack'], 2) : $budget->snack,
            'other' => isset($data['other']) ? round((float) $data['other'], 2) : $budget->other,
            'notes' => $data['notes'] ?? $budget->notes,
        ]);

        return $budget;
    }

    /**
     * Calculate total essential requirement for the entire month.
     */
    public function getMonthlyRequirement(int $year, int $month): float
    {
        $budget = $this->ensureDefaultBudget();
        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;

        return round($budget->total_daily_minimum * $daysInMonth, 2);
    }

    /**
     * Calculate remaining essential budget required for the rest of the month starting from a date.
     */
    public function getRemainingMonthlyRequirement(Carbon $fromDate): float
    {
        $budget = $this->ensureDefaultBudget();
        $daysInMonth = $fromDate->daysInMonth;
        $remainingDays = max(1, ($daysInMonth - $fromDate->day + 1));

        return round($budget->total_daily_minimum * $remainingDays, 2);
    }
}
