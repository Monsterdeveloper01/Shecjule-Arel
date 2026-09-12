<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceEssentialBudget extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'food',
        'transport',
        'snack',
        'other',
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
            'food' => 'decimal:2',
            'transport' => 'decimal:2',
            'snack' => 'decimal:2',
            'other' => 'decimal:2',
        ];
    }

    /**
     * Total daily minimum allowance for essentials.
     */
    public function getTotalDailyMinimumAttribute(): float
    {
        return round((float) $this->food + (float) $this->transport + (float) $this->snack + (float) $this->other, 2);
    }
}
