<?php

namespace App\Services;

use App\Models\FocusSession;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FocusSessionService
{
    /**
     * Log a completed focus session and optionally update attached task.
     */
    public function logSession(
        ?int $taskId,
        string $type,
        int $durationMinutes,
        ?string $startedAt = null,
        ?string $endedAt = null,
        ?string $notes = null,
        bool $markTaskCompleted = false
    ): FocusSession {
        $now = now();
        $start = $startedAt ? Carbon::parse($startedAt) : $now->copy()->subMinutes($durationMinutes);
        $end = $endedAt ? Carbon::parse($endedAt) : $now;

        $session = FocusSession::create([
            'task_id' => $taskId,
            'type' => $type,
            'duration_minutes' => max(1, $durationMinutes),
            'started_at' => $start,
            'ended_at' => $end,
            'completed' => true,
            'notes' => $notes,
        ]);

        if ($taskId) {
            $task = Task::find($taskId);
            if ($task) {
                if ($markTaskCompleted) {
                    $task->status = 'completed';
                    $task->progress = 100;
                    $task->completed_at = $now;
                } else {
                    if ($task->status === 'pending') {
                        $task->status = 'in_progress';
                    }
                    // Add estimated progress increment based on duration
                    $estimated = max(30, $task->estimated_duration ?? 120);
                    $addedPercent = (int) round(($durationMinutes / $estimated) * 100);
                    $task->progress = min(95, ($task->progress ?? 0) + max(10, $addedPercent));
                }
                $task->save();
            }
        }

        return $session;
    }

    /**
     * Total focus minutes logged today.
     */
    public function getTotalFocusMinutesToday(): int
    {
        return (int) FocusSession::whereDate('started_at', now()->toDateString())
            ->where('completed', true)
            ->sum('duration_minutes');
    }

    /**
     * Total focus minutes logged in the past 7 days.
     */
    public function getTotalFocusMinutesWeek(): int
    {
        return (int) FocusSession::where('started_at', '>=', now()->subDays(7))
            ->where('completed', true)
            ->sum('duration_minutes');
    }

    /**
     * Get recent completed focus sessions.
     *
     * @return Collection<int, FocusSession>
     */
    public function getRecentSessions(int $limit = 10): Collection
    {
        return FocusSession::with('task')
            ->orderByDesc('started_at')
            ->limit($limit)
            ->get();
    }
}
