<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'subject',
        'deadline',
        'priority',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'deadline' => 'datetime',
        ];
    }

    /**
     * Scope: overdue tasks (deadline passed, not completed).
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('deadline', '<', now())
            ->where('status', '!=', 'completed');
    }

    /**
     * Scope: upcoming tasks (deadline in the future, not completed).
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('deadline', '>=', now())
            ->where('status', '!=', 'completed')
            ->orderBy('deadline');
    }

    /**
     * Scope: tasks by priority.
     */
    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope: tasks for a specific date.
     */
    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('deadline', $date);
    }

    /**
     * Check if the task is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->deadline->isPast() && $this->status !== 'completed';
    }
}
