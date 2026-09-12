<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceAccount extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type',
        'opening_balance',
        'current_balance',
        'installment_reserve',
        'savings_reserve',
        'is_primary',
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
            'opening_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'installment_reserve' => 'decimal:2',
            'savings_reserve' => 'decimal:2',
            'is_primary' => 'boolean',
        ];
    }

    /**
     * Transactions belonging to this account.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(FinanceTransaction::class, 'account_id');
    }

    /**
     * Actual available spending money after dedicated reserves are subtracted.
     * Available Spending = Current Balance - Installment Reserve - Savings Reserve.
     */
    public function getAvailableSpendingAttribute(): float
    {
        $available = (float) $this->current_balance - (float) $this->installment_reserve - (float) $this->savings_reserve;

        return max(0.0, round($available, 2));
    }

    /**
     * Recalculate and persist the current balance strictly from opening balance and the ledger.
     */
    public function recalculateBalance(): void
    {
        $incomes = (float) $this->transactions()
            ->where('type', 'income')
            ->sum('amount');

        $transfersIn = (float) FinanceTransaction::where('destination_account_id', $this->id)
            ->where('type', 'transfer')
            ->sum('amount');

        $expenses = (float) $this->transactions()
            ->whereIn('type', ['expense', 'installment_payment'])
            ->sum('amount');

        $transfersOut = (float) $this->transactions()
            ->where('type', 'transfer')
            ->sum('amount');

        $newBalance = (float) $this->opening_balance + $incomes + $transfersIn - $expenses - $transfersOut;

        $this->current_balance = round($newBalance, 2);
        $this->save();
    }
}
