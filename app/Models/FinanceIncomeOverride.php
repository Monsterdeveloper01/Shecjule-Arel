<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FinanceIncomeOverride extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'override_date',
        'title',
        'amount',
        'is_extra',
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
            'override_date' => 'date',
            'amount' => 'decimal:2',
            'is_extra' => 'boolean',
        ];
    }

    /**
     * Scope for a specific date.
     */
    public function scopeForDate(Builder $query, Carbon|string $date): Builder
    {
        $d = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();

        return $query->whereDate('override_date', $d);
    }

    /**
     * Scope for a specific month.
     */
    public function scopeForMonth(Builder $query, int $year, int $month): Builder
    {
        return $query->whereYear('override_date', $year)
            ->whereMonth('override_date', $month);
    }
}
