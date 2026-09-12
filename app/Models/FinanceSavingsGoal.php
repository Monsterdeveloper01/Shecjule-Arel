<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceSavingsGoal extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'target_amount',
        'current_amount',
        'type',
        'target_date',
        'color',
        'icon',
        'status',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'current_amount' => 'decimal:2',
            'target_date' => 'date',
        ];
    }

    /**
     * Scope for active savings goals.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Allocations made towards this goal.
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(FinanceSavingsAllocation::class, 'savings_goal_id');
    }

    /**
     * Transactions tied to this goal (savings_allocation or goal expense).
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(FinanceTransaction::class, 'savings_goal_id');
    }

    /**
     * Remaining amount to reach target.
     */
    public function getRemainingAmountAttribute(): float
    {
        return max(0.0, round((float) $this->target_amount - (float) $this->current_amount, 2));
    }

    /**
     * Progress percentage (0 - 100%).
     */
    public function getProgressPercentAttribute(): float
    {
        $target = (float) $this->target_amount;
        if ($target <= 0.0) {
            return 100.0;
        }

        return min(100.0, round(((float) $this->current_amount / $target) * 100, 1));
    }

    /**
     * Calculate daily, weekly, and monthly required savings with zero-division protection and affordability status.
     *
     * @return array{
     *     target_date: ?string,
     *     days_remaining: ?int,
     *     remaining_amount: float,
     *     daily_required: float,
     *     weekly_required: float,
     *     monthly_required: float,
     *     is_due_today: bool,
     *     is_overdue: bool,
     *     is_achieved: bool,
     *     affordability: string
     * }
     */
    public function calculateDailyRequirement(Carbon $fromDate, float $estimatedDailySurplus = 0.0): array
    {
        $remaining = $this->remaining_amount;
        $isAchieved = $remaining <= 0.0 || $this->status === 'achieved';

        if ($isAchieved) {
            return [
                'target_date' => $this->target_date?->format('d M Y'),
                'days_remaining' => 0,
                'remaining_amount' => 0.0,
                'daily_required' => 0.0,
                'weekly_required' => 0.0,
                'monthly_required' => 0.0,
                'is_due_today' => false,
                'is_overdue' => false,
                'is_achieved' => true,
                'affordability' => 'achieved',
            ];
        }

        if ($this->type !== 'deadline' || ! $this->target_date) {
            return [
                'target_date' => null,
                'days_remaining' => null,
                'remaining_amount' => $remaining,
                'daily_required' => 0.0,
                'weekly_required' => 0.0,
                'monthly_required' => 0.0,
                'is_due_today' => false,
                'is_overdue' => false,
                'is_achieved' => false,
                'affordability' => 'flexible',
            ];
        }

        $curr = $fromDate->copy()->startOfDay();
        $target = $this->target_date->copy()->startOfDay();
        $diffDays = (int) $curr->diffInDays($target, false);

        $isDueToday = $diffDays === 0;
        $isOverdue = $diffDays < 0;

        if ($isOverdue || $isDueToday) {
            $daysRemaining = 1;
            $dailyRequired = $remaining;
        } else {
            $daysRemaining = max(1, $diffDays + 1);
            $dailyRequired = round($remaining / $daysRemaining, 2);
        }

        $weeklyRequired = round($dailyRequired * 7, 2);
        $monthlyRequired = round($dailyRequired * 30, 2);

        // Determine affordability against available surplus
        $affordability = 'neutral';
        if ($estimatedDailySurplus > 0.0) {
            if ($dailyRequired <= $estimatedDailySurplus * 0.7) {
                $affordability = 'achievable';
            } elseif ($dailyRequired <= $estimatedDailySurplus) {
                $affordability = 'demanding';
            } else {
                $affordability = 'unrealistic';
            }
        }

        return [
            'target_date' => $this->target_date->format('d M Y'),
            'days_remaining' => $daysRemaining,
            'remaining_amount' => $remaining,
            'daily_required' => $dailyRequired,
            'weekly_required' => $weeklyRequired,
            'monthly_required' => $monthlyRequired,
            'is_due_today' => $isDueToday,
            'is_overdue' => $isOverdue,
            'is_achieved' => false,
            'affordability' => $affordability,
        ];
    }
}
