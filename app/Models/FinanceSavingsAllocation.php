<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceSavingsAllocation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'savings_goal_id',
        'account_id',
        'amount',
        'allocation_date',
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
            'amount' => 'decimal:2',
            'allocation_date' => 'date',
        ];
    }

    /**
     * The goal this allocation belongs to.
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(FinanceSavingsGoal::class, 'savings_goal_id');
    }

    /**
     * The account this allocation was reserved from.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'account_id');
    }
}
