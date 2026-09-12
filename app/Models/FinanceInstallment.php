<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceInstallment extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type',
        'total_amount',
        'monthly_amount',
        'due_day',
        'start_date',
        'end_date',
        'category',
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
            'total_amount' => 'decimal:2',
            'monthly_amount' => 'decimal:2',
            'due_day' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * Determine if this is a recurring monthly bill.
     */
    public function isRecurringBill(): bool
    {
        return $this->type === 'recurring_bill';
    }

    /**
     * Determine if this is a debt / loan installment.
     */
    public function isDebt(): bool
    {
        return $this->type !== 'recurring_bill';
    }

    /**
     * Scope for active installments.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Monthly allocations reserved for this installment.
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(FinanceInstallmentAllocation::class, 'installment_id');
    }

    /**
     * Actual payment transactions made for this installment.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(FinanceTransaction::class, 'installment_id')
            ->where('type', 'installment_payment');
    }

    /**
     * Total amount actually paid so far across all time.
     */
    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    /**
     * Remaining obligation for the whole item (null for recurring bills).
     */
    public function getRemainingTotalAttribute(): ?float
    {
        if ($this->isRecurringBill() || $this->total_amount === null) {
            return null;
        }

        return max(0.0, round((float) $this->total_amount - $this->total_paid, 2));
    }

    /**
     * Overall payoff progress percentage (null for recurring bills).
     */
    public function getProgressPercentAttribute(): ?float
    {
        if ($this->isRecurringBill() || $this->total_amount === null) {
            return null;
        }

        $total = (float) $this->total_amount;
        if ($total <= 0) {
            return 100.0;
        }

        return min(100.0, round(($this->total_paid / $total) * 100, 1));
    }

    /**
     * Calculate the specific due date for a given year and month (handling variable days in month).
     */
    public function getDueDateForMonth(int $year, int $month): Carbon
    {
        $d = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $clampedDay = min($d->daysInMonth, $this->due_day);

        return Carbon::createFromDate($year, $month, $clampedDay)->startOfDay();
    }

    /**
     * Calculate daily reserve required without ever dividing by zero.
     *
     * @return array{
     *     due_date: Carbon,
     *     days_remaining: int,
     *     already_reserved: float,
     *     remaining_obligation: float,
     *     daily_required: float,
     *     is_due_today: bool,
     *     is_overdue: bool,
     *     is_fully_reserved: bool,
     *     is_paid: bool
     * }
     */
    public function calculateDailyAllocation(Carbon $fromDate, float $alreadyReserved = 0.0, bool $isPaidThisMonth = false): array
    {
        $currDate = $fromDate->copy()->startOfDay();
        $dueDate = $this->getDueDateForMonth($currDate->year, $currDate->month);

        $monthlyAmount = (float) $this->monthly_amount;
        $remainingNeeded = max(0.0, round($monthlyAmount - $alreadyReserved, 2));
        $isFullyReserved = $remainingNeeded <= 0.0;

        if ($this->status !== 'active' || $isPaidThisMonth) {
            return [
                'due_date' => $dueDate,
                'days_remaining' => 0,
                'already_reserved' => round($alreadyReserved, 2),
                'remaining_obligation' => 0.0,
                'daily_required' => 0.0,
                'is_due_today' => false,
                'is_overdue' => false,
                'is_fully_reserved' => true,
                'is_paid' => $isPaidThisMonth,
            ];
        }

        $diffDays = (int) $currDate->diffInDays($dueDate, false);
        $isDueToday = $diffDays === 0;
        $isOverdue = $diffDays < 0;

        if ($isFullyReserved) {
            $dailyRequired = 0.0;
            $daysRemaining = max(0, $diffDays);
        } elseif ($isOverdue || $isDueToday) {
            // If overdue or due today, the entire remaining amount is needed immediately (1 day)
            $daysRemaining = 1;
            $dailyRequired = $remainingNeeded;
        } else {
            // Future date: eligible days include fromDate up to due date
            $daysRemaining = max(1, $diffDays + 1);
            $dailyRequired = round($remainingNeeded / $daysRemaining, 2);
        }

        return [
            'due_date' => $dueDate,
            'days_remaining' => $daysRemaining,
            'already_reserved' => round($alreadyReserved, 2),
            'remaining_obligation' => $remainingNeeded,
            'daily_required' => $dailyRequired,
            'is_due_today' => $isDueToday,
            'is_overdue' => $isOverdue,
            'is_fully_reserved' => $isFullyReserved,
            'is_paid' => false,
        ];
    }
}
