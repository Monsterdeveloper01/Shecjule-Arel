<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceIncomeSchedule extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'weekday_amounts',
        'monthly_amount',
        'monthly_due_day',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weekday_amounts' => 'array',
            'monthly_amount' => 'decimal:2',
            'monthly_due_day' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get planned amount for a given ISO weekday (1 = Monday, ..., 7 = Sunday).
     */
    public function getAmountForIsoDay(int $dayOfWeek): float
    {
        if ($this->type !== 'daily_variable' || ! is_array($this->weekday_amounts)) {
            return 0.0;
        }

        return (float) ($this->weekday_amounts[(string) $dayOfWeek] ?? $this->weekday_amounts[$dayOfWeek] ?? 0.0);
    }
}
