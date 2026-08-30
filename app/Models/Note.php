<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'content',
        'color',
        'is_pinned',
        'note_date',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'note_date' => 'date',
        ];
    }

    /**
     * Scope: pinned notes first.
     */
    public function scopePinned(Builder $query): Builder
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope: notes for a specific date.
     */
    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('note_date', $date);
    }
}
