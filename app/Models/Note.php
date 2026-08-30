<?php

namespace App\Models;

use App\Models\Concerns\HasAttachment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasAttachment;

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
            'is_pinned' => 'boolean',
            'note_date' => 'date',
            'file_size' => 'integer',
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
