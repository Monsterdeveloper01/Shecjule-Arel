<?php

namespace App\Models;

use App\Models\Concerns\HasAttachment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasAttachment;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'location',
        'category',
        'start_date',
        'end_date',
        'file_path',
        'file_name',
        'file_size',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'file_url',
        'formatted_file_size',
        'file_extension',
        'file_type',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'file_size' => 'integer',
        ];
    }

    /**
     * Scope: events by category.
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: upcoming events.
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('start_date', '>=', now())
            ->orderBy('start_date');
    }

    /**
     * Scope: events for a specific date.
     */
    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('start_date', $date);
    }

    /**
     * Scope: events within a month.
     */
    public function scopeForMonth(Builder $query, int $year, int $month): Builder
    {
        return $query->whereYear('start_date', $year)
            ->whereMonth('start_date', $month);
    }
}
