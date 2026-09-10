<?php

namespace App\Services;

use App\DataObjects\PriorityResult;
use App\Models\Task;
use Illuminate\Support\Collection;

class PriorityEngine
{
    /**
     * Default estimated duration in minutes when not provided by user.
     */
    private const DEFAULT_DURATION_MINUTES = 120;

    /**
     * Weight distribution for scoring factors (must sum to 100).
     */
    private const WEIGHT_DEADLINE = 40;

    private const WEIGHT_IMPORTANCE = 25;

    private const WEIGHT_PROGRESS = 20;

    private const WEIGHT_WORKLOAD = 15;

    /**
     * Map user-defined priority to importance score (0–100).
     *
     * @var array<string, int>
     */
    private const IMPORTANCE_MAP = [
        'urgent' => 100,
        'high' => 75,
        'medium' => 50,
        'low' => 25,
    ];

    /**
     * Calculate priority for a single task.
     */
    public function calculate(Task $task): PriorityResult
    {
        // Completed tasks always get score 0
        if ($task->status === 'completed') {
            return new PriorityResult(
                score: 0,
                level: 'RENDAH',
                reason: 'Tugas sudah selesai.',
                breakdown: ['deadline' => 0, 'importance' => 0, 'progress' => 0, 'workload' => 0],
            );
        }

        $deadlineScore = $this->calculateDeadlineScore($task);
        $importanceScore = $this->calculateImportanceScore($task);
        $progressScore = $this->calculateProgressScore($task);
        $workloadScore = $this->calculateWorkloadScore($task);

        $totalScore = (int) round(
            ($deadlineScore * self::WEIGHT_DEADLINE / 100)
            + ($importanceScore * self::WEIGHT_IMPORTANCE / 100)
            + ($progressScore * self::WEIGHT_PROGRESS / 100)
            + ($workloadScore * self::WEIGHT_WORKLOAD / 100)
        );

        // Overdue tasks are always CRITICAL (minimum 80)
        if ($task->isOverdue()) {
            $totalScore = max($totalScore, 80);
        }

        $totalScore = min(100, max(0, $totalScore));
        $level = PriorityResult::levelLabel($totalScore);
        $reason = $this->buildReason($task, $totalScore, $deadlineScore);

        return new PriorityResult(
            score: $totalScore,
            level: $level,
            reason: $reason,
            breakdown: [
                'deadline' => $deadlineScore,
                'importance' => $importanceScore,
                'progress' => $progressScore,
                'workload' => $workloadScore,
            ],
        );
    }

    /**
     * Calculate priority for all active (non-completed) tasks.
     *
     * @return Collection<int, array{task: Task, priority: PriorityResult}>
     */
    public function calculateAll(): Collection
    {
        $tasks = Task::where('status', '!=', 'completed')->get();

        return $tasks->map(fn (Task $task) => [
            'task' => $task,
            'priority' => $this->calculate($task),
        ])->sortByDesc(fn (array $item) => $item['priority']->score)->values();
    }

    /**
     * Calculate and persist priority score to database.
     */
    public function recalculateAndPersist(Task $task): PriorityResult
    {
        $result = $this->calculate($task);

        $task->updateQuietly([
            'priority_score' => $result->score,
            'priority_level' => $result->level,
        ]);

        return $result;
    }

    /**
     * Batch recalculate all active tasks and persist scores.
     */
    public function recalculateAllAndPersist(): int
    {
        $tasks = Task::where('status', '!=', 'completed')->get();
        $count = 0;

        foreach ($tasks as $task) {
            $this->recalculateAndPersist($task);
            $count++;
        }

        // Set completed tasks to score 0
        Task::where('status', 'completed')
            ->whereNot('priority_score', 0)
            ->update(['priority_score' => 0, 'priority_level' => 'RENDAH']);

        return $count;
    }

    /**
     * Deadline proximity score (0–100).
     * Closer deadline = higher score. Overdue = 100.
     */
    private function calculateDeadlineScore(Task $task): int
    {
        if (! $task->deadline) {
            return 30; // No deadline = moderate baseline
        }

        $hoursRemaining = now()->diffInHours($task->deadline, false);

        // Overdue
        if ($hoursRemaining < 0) {
            return 100;
        }

        // Scale: 0h = 100, 6h = 90, 24h = 75, 48h = 60, 72h = 45, 168h (7d) = 20, 336h (14d) = 5, 720h+ (30d) = 0
        return match (true) {
            $hoursRemaining <= 6 => 100 - (int) ($hoursRemaining * 1.67),  // 100→90
            $hoursRemaining <= 24 => 90 - (int) (($hoursRemaining - 6) * 0.83), // 90→75
            $hoursRemaining <= 48 => 75 - (int) (($hoursRemaining - 24) * 0.625), // 75→60
            $hoursRemaining <= 72 => 60 - (int) (($hoursRemaining - 48) * 0.625), // 60→45
            $hoursRemaining <= 168 => 45 - (int) (($hoursRemaining - 72) * 0.26), // 45→20
            $hoursRemaining <= 336 => 20 - (int) (($hoursRemaining - 168) * 0.089), // 20→5
            default => max(0, 5 - (int) (($hoursRemaining - 336) * 0.013)),
        };
    }

    /**
     * User-defined importance score (0–100).
     */
    private function calculateImportanceScore(Task $task): int
    {
        return self::IMPORTANCE_MAP[$task->priority] ?? 50;
    }

    /**
     * Progress score (0–100). Less progress = higher urgency.
     */
    private function calculateProgressScore(Task $task): int
    {
        $progress = $task->progress ?? 0;

        return max(0, 100 - $progress);
    }

    /**
     * Workload score based on remaining work vs available time.
     */
    private function calculateWorkloadScore(Task $task): int
    {
        $estimatedDuration = $task->estimated_duration ?? self::DEFAULT_DURATION_MINUTES;
        $progress = $task->progress ?? 0;
        $remainingMinutes = (int) ($estimatedDuration * (100 - $progress) / 100);

        if (! $task->deadline) {
            // No deadline: pure workload size score
            return match (true) {
                $remainingMinutes >= 480 => 80,  // 8h+
                $remainingMinutes >= 240 => 60,  // 4h+
                $remainingMinutes >= 120 => 40,  // 2h+
                $remainingMinutes >= 60 => 25,   // 1h+
                default => 10,
            };
        }

        $availableMinutes = max(0, now()->diffInMinutes($task->deadline, false));

        if ($availableMinutes <= 0) {
            return 100; // Overdue
        }

        // Ratio: remaining work / available time
        $ratio = $remainingMinutes / $availableMinutes;

        return match (true) {
            $ratio >= 1.0 => 100,  // Not enough time
            $ratio >= 0.75 => 85,  // Very tight
            $ratio >= 0.5 => 65,   // Tight
            $ratio >= 0.25 => 40,  // Comfortable
            default => 15,         // Plenty of time
        };
    }

    /**
     * Build a human-readable reason string in Indonesian.
     */
    private function buildReason(Task $task, int $totalScore, int $deadlineScore): string
    {
        $reasons = [];

        if ($task->isOverdue()) {
            $overdueDays = now()->diffInDays($task->deadline);
            $reasons[] = "Sudah lewat deadline {$overdueDays} hari lalu";
        } elseif ($task->deadline) {
            $hoursLeft = (int) now()->diffInHours($task->deadline, false);
            if ($hoursLeft <= 6) {
                $reasons[] = 'Deadline kurang dari 6 jam lagi';
            } elseif ($hoursLeft <= 24) {
                $reasons[] = 'Deadline besok';
            } elseif ($hoursLeft <= 48) {
                $reasons[] = 'Deadline lusa (2 hari lagi)';
            } elseif ($hoursLeft <= 72) {
                $reasons[] = 'Deadline 3 hari lagi';
            }
        }

        $progress = $task->progress ?? 0;
        if ($progress === 0 && $deadlineScore >= 60) {
            $reasons[] = 'Belum dimulai sama sekali';
        } elseif ($progress > 0 && $progress < 50) {
            $reasons[] = "Progress masih {$progress}%";
        }

        $importanceLabel = match ($task->priority) {
            'urgent' => 'Prioritas urgent',
            'high' => 'Prioritas tinggi',
            default => null,
        };
        if ($importanceLabel && $totalScore >= 55) {
            $reasons[] = $importanceLabel;
        }

        if (empty($reasons)) {
            return match (true) {
                $totalScore >= 80 => 'Perlu dikerjakan segera.',
                $totalScore >= 55 => 'Perlu perhatian.',
                $totalScore >= 30 => 'Masih bisa diatur.',
                default => 'Belum mendesak.',
            };
        }

        return implode('. ', $reasons).'.';
    }
}
