<?php

namespace App\Models;

use App\DataObjects\DeadlineRiskResult;
use App\DataObjects\PriorityResult;
use App\Models\Concerns\HasAttachment;
use App\Services\DeadlineRiskEngine;
use App\Services\PriorityEngine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasAttachment;

    /**
     * The relationships that should always be loaded.
     *
     * @var array<int, string>
     */
    protected $with = ['attachments'];

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
        'estimated_duration',
        'progress',
        'subtasks',
        'priority_score',
        'priority_level',
        'risk_score',
        'risk_level',
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
            'estimated_duration' => 'integer',
            'progress' => 'integer',
            'subtasks' => 'array',
            'priority_score' => 'integer',
            'risk_score' => 'integer',
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
     * Scope: sort by computed priority score descending.
     */
    public function scopeByComputedPriority(Builder $query): Builder
    {
        return $query->orderByDesc('priority_score');
    }

    /**
     * Scope: active tasks (not completed).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', 'completed');
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
        return (bool) ($this->deadline?->isPast() && $this->status !== 'completed');
    }

    /**
     * Get remaining work duration in minutes.
     */
    public function getRemainingDurationAttribute(): int
    {
        $estimated = $this->estimated_duration ?? 120;
        $progress = $this->progress ?? 0;

        return (int) ($estimated * (100 - $progress) / 100);
    }

    /**
     * Scope: filter tasks by risk level.
     */
    public function scopeByRisk(Builder $query, string $riskLevel): Builder
    {
        return $query->where('risk_level', $riskLevel);
    }

    /**
     * Scope: high or critical risk tasks.
     */
    public function scopeHighRisk(Builder $query): Builder
    {
        return $query->whereIn('risk_level', ['KRITIS', 'TINGGI'])
            ->where('status', '!=', 'completed')
            ->orderByDesc('risk_score');
    }

    /**
     * Calculate and return the PriorityResult for this task.
     */
    public function getPriorityResultAttribute(): PriorityResult
    {
        return app(PriorityEngine::class)->calculate($this);
    }

    /**
     * Calculate and return the DeadlineRiskResult for this task.
     */
    public function getRiskResultAttribute(): DeadlineRiskResult
    {
        return app(DeadlineRiskEngine::class)->calculate($this);
    }

    /**
     * Total number of subtasks.
     */
    public function getTotalSubtasksCountAttribute(): int
    {
        return is_array($this->subtasks) ? count($this->subtasks) : 0;
    }

    /**
     * Number of completed subtasks.
     */
    public function getCompletedSubtasksCountAttribute(): int
    {
        if (! is_array($this->subtasks)) {
            return 0;
        }

        return count(array_filter($this->subtasks, fn ($st) => ! empty($st['completed'])));
    }
}
