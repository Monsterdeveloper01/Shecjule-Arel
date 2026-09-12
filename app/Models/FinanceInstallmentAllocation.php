<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceInstallmentAllocation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'installment_id',
        'account_id',
        'amount',
        'allocation_date',
        'month',
        'year',
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
            'month' => 'integer',
            'year' => 'integer',
        ];
    }

    /**
     * The installment this reserve allocation is dedicated to.
     */
    public function installment(): BelongsTo
    {
        return $this->belongsTo(FinanceInstallment::class, 'installment_id');
    }

    /**
     * The cash/bank account where the money is reserved.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'account_id');
    }
}
