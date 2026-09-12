<?php

namespace App\Services;

use App\Models\FinanceAccount;
use App\Models\FinanceSavingsAllocation;
use App\Models\FinanceSavingsGoal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SavingsGoalService
{
    public function __construct(
        protected FinanceLedgerService $ledgerService
    ) {}

    /**
     * Get a comprehensive summary of all savings goals (deadline vs open-ended), requirements, and progress.
     *
     * @return array{
     *     total_target: float,
     *     total_current: float,
     *     total_remaining: float,
     *     overall_progress: float,
     *     total_daily_required_today: float,
     *     active_count: int,
     *     achieved_count: int,
     *     items: array<int, array<string, mixed>>
     * }
     */
    public function getSavingsGoalsSummary(Carbon|string $date, float $estimatedDailySurplus = 0.0): array
    {
        $d = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);

        $goals = FinanceSavingsGoal::orderBy('status')
            ->orderByRaw('target_date IS NULL, target_date ASC')
            ->orderBy('created_at')
            ->get();

        $totalTarget = 0.0;
        $totalCurrent = 0.0;
        $totalDailyRequiredToday = 0.0;
        $activeCount = 0;
        $achievedCount = 0;
        $items = [];

        foreach ($goals as $goal) {
            $calc = $goal->calculateDailyRequirement($d, $estimatedDailySurplus);

            if ($goal->status === 'achieved') {
                $achievedCount++;
            } elseif ($goal->status === 'active') {
                $activeCount++;
                $totalTarget += (float) $goal->target_amount;
                $totalCurrent += (float) $goal->current_amount;
                $totalDailyRequiredToday += $calc['daily_required'];
            }

            $items[] = [
                'id' => $goal->id,
                'name' => $goal->name,
                'type' => $goal->type,
                'icon' => $goal->icon ?? '🎯',
                'color' => $goal->color ?? '#10b981',
                'status' => $goal->status,
                'target_amount' => (float) $goal->target_amount,
                'current_amount' => (float) $goal->current_amount,
                'remaining_amount' => $goal->remaining_amount,
                'progress_percent' => $goal->progress_percent,
                'target_date' => $calc['target_date'],
                'days_remaining' => $calc['days_remaining'],
                'daily_required' => $calc['daily_required'],
                'weekly_required' => $calc['weekly_required'],
                'monthly_required' => $calc['monthly_required'],
                'is_due_today' => $calc['is_due_today'],
                'is_overdue' => $calc['is_overdue'],
                'is_achieved' => $calc['is_achieved'],
                'affordability' => $calc['affordability'],
                'notes' => $goal->notes,
            ];
        }

        $totalRemaining = max(0.0, round($totalTarget - $totalCurrent, 2));
        $overallProgress = $totalTarget > 0.0 ? min(100.0, round(($totalCurrent / $totalTarget) * 100, 1)) : 0.0;

        return [
            'total_target' => round($totalTarget, 2),
            'total_current' => round($totalCurrent, 2),
            'total_remaining' => $totalRemaining,
            'overall_progress' => $overallProgress,
            'total_daily_required_today' => round($totalDailyRequiredToday, 2),
            'active_count' => $activeCount,
            'achieved_count' => $achievedCount,
            'items' => $items,
        ];
    }

    /**
     * Allocate funds into a savings goal without spending it (internal transfer into savings reserve).
     */
    public function allocateSavings(
        int $goalId,
        int $accountId,
        float $amount,
        ?string $date = null,
        ?string $notes = null
    ): FinanceSavingsAllocation {
        return DB::transaction(function () use ($goalId, $accountId, $amount, $date, $notes) {
            $goal = FinanceSavingsGoal::findOrFail($goalId);
            $account = FinanceAccount::findOrFail($accountId);
            $d = $date ? Carbon::parse($date) : now();

            $allocation = FinanceSavingsAllocation::create([
                'savings_goal_id' => $goal->id,
                'account_id' => $account->id,
                'amount' => round($amount, 2),
                'allocation_date' => $d->toDateString(),
                'notes' => $notes,
            ]);

            // Update goal current amount
            $goal->current_amount = round((float) $goal->current_amount + $amount, 2);
            if ($goal->current_amount >= (float) $goal->target_amount) {
                $goal->status = 'achieved';
            }
            $goal->save();

            // Record transaction in ledger as savings_allocation (which updates account savings_reserve)
            $this->ledgerService->recordTransaction([
                'account_id' => $account->id,
                'type' => 'savings_allocation',
                'amount' => $amount,
                'transaction_date' => $d->toDateString(),
                'description' => "Alokasi Tabungan: {$goal->name}",
                'savings_goal_id' => $goal->id,
                'notes' => $notes,
            ]);

            return $allocation;
        });
    }

    /**
     * Withdraw or release funds from savings goal (either back to flexible spending or spent on goal).
     */
    public function withdrawSavings(
        int $goalId,
        int $accountId,
        float $amount,
        bool $isExpense = false,
        ?string $notes = null
    ): FinanceSavingsGoal {
        return DB::transaction(function () use ($goalId, $accountId, $amount, $isExpense, $notes) {
            $goal = FinanceSavingsGoal::findOrFail($goalId);
            $account = FinanceAccount::findOrFail($accountId);

            // Release from account savings reserve
            $account->refresh();
            $account->savings_reserve = max(0.0, round((float) $account->savings_reserve - $amount, 2));
            $account->save();

            if ($isExpense) {
                // Real expense on ledger
                $this->ledgerService->recordTransaction([
                    'account_id' => $account->id,
                    'type' => 'expense',
                    'amount' => $amount,
                    'transaction_date' => now()->toDateString(),
                    'description' => "Penggunaan Tabungan: {$goal->name}",
                    'savings_goal_id' => $goal->id,
                    'notes' => $notes,
                ]);
            }

            // Adjust goal current amount
            $goal->current_amount = max(0.0, round((float) $goal->current_amount - $amount, 2));
            if ($goal->status === 'achieved' && $goal->current_amount < (float) $goal->target_amount) {
                $goal->status = 'active';
            }
            $goal->save();

            return $goal;
        });
    }
}
