<?php

namespace App\Services;

use App\DataObjects\DeadlineRiskResult;
use App\Models\CourseSchedule;
use App\Models\Event;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DeadlineRiskEngine
{
    /**
     * Active daily window for student study/work.
     * Starts at 07:00 and ends at 22:00 (15 active hours per day).
     */
    public const WORK_WINDOW_START_HOUR = 7;

    public const WORK_WINDOW_END_HOUR = 22;

    /**
     * Default estimated duration in minutes if not set by user.
     */
    public const DEFAULT_DURATION_MINUTES = 120;

    /**
     * Calculate deadline risk for a single task.
     */
    public function calculate(Task $task): DeadlineRiskResult
    {
        // Completed task is always SAFE
        if ($task->status === 'completed') {
            return new DeadlineRiskResult(
                score: 0,
                level: 'AMAN',
                reason: 'Tugas sudah selesai dikerjakan.',
                remainingWorkHours: 0.0,
                availableHours: 0.0,
                feasibilityRatio: 0.0,
            );
        }

        // Overdue task is always CRITICAL
        if ($task->isOverdue()) {
            $overdueDays = now()->diffInDays($task->deadline);

            return new DeadlineRiskResult(
                score: 100,
                level: 'KRITIS',
                reason: $overdueDays > 0
                    ? "Sudah melewati deadline {$overdueDays} hari lalu!"
                    : 'Sudah melewati batas waktu deadline!',
                remainingWorkHours: round($task->remaining_duration / 60, 1),
                availableHours: 0.0,
                feasibilityRatio: 999.0,
            );
        }

        // Task without deadline
        if (! $task->deadline) {
            return new DeadlineRiskResult(
                score: 10,
                level: 'AMAN',
                reason: 'Tidak ada tenggat waktu deadline.',
                remainingWorkHours: round($task->remaining_duration / 60, 1),
                availableHours: 999.0,
                feasibilityRatio: 0.0,
            );
        }

        // Task with 100% progress
        if (($task->progress ?? 0) >= 100) {
            return new DeadlineRiskResult(
                score: 0,
                level: 'AMAN',
                reason: 'Progres pengerjaan sudah 100%.',
                remainingWorkHours: 0.0,
                availableHours: 0.0,
                feasibilityRatio: 0.0,
            );
        }

        $remainingMinutes = $this->calculateRemainingWorkMinutes($task);
        $availableMinutes = $this->calculateAvailableWorkMinutes(now(), $task->deadline);

        $remainingHours = round($remainingMinutes / 60, 1);
        $availableHours = round($availableMinutes / 60, 1);

        if ($availableMinutes <= 0) {
            return new DeadlineRiskResult(
                score: 100,
                level: 'KRITIS',
                reason: "Tidak ada waktu luang tersisa sebelum deadline (butuh {$remainingHours} jam)!",
                remainingWorkHours: $remainingHours,
                availableHours: 0.0,
                feasibilityRatio: 999.0,
            );
        }

        $ratio = $remainingMinutes / $availableMinutes;
        $score = $this->ratioToScore($ratio);
        $level = DeadlineRiskResult::levelLabel($score);
        $reason = $this->buildReason($remainingHours, $availableHours, $ratio, $task);

        return new DeadlineRiskResult(
            score: $score,
            level: $level,
            reason: $reason,
            remainingWorkHours: $remainingHours,
            availableHours: $availableHours,
            feasibilityRatio: round($ratio, 2),
        );
    }

    /**
     * Calculate and persist risk result to the task.
     */
    public function recalculateAndPersist(Task $task): DeadlineRiskResult
    {
        $result = $this->calculate($task);

        $task->updateQuietly([
            'risk_score' => $result->score,
            'risk_level' => $result->level,
        ]);

        return $result;
    }

    /**
     * Batch recalculate risk for all active tasks.
     */
    public function recalculateAllAndPersist(): int
    {
        $tasks = Task::where('status', '!=', 'completed')->get();
        $count = 0;

        foreach ($tasks as $task) {
            $this->recalculateAndPersist($task);
            $count++;
        }

        // Ensure completed tasks are set to AMAN / 0
        Task::where('status', 'completed')
            ->whereNot('risk_score', 0)
            ->update(['risk_score' => 0, 'risk_level' => 'AMAN']);

        return $count;
    }

    /**
     * Retrieve all active tasks categorized as high or critical risk.
     *
     * @return Collection<int, Task>
     */
    public function getHighRiskTasks(): Collection
    {
        return Task::where('status', '!=', 'completed')
            ->whereIn('risk_level', ['KRITIS', 'TINGGI'])
            ->orderByDesc('risk_score')
            ->get();
    }

    /**
     * Calculate remaining work duration in minutes.
     */
    public function calculateRemainingWorkMinutes(Task $task): int
    {
        $estimated = $task->estimated_duration ?? self::DEFAULT_DURATION_MINUTES;
        $progress = $task->progress ?? 0;

        return (int) max(0, round($estimated * (100 - $progress) / 100));
    }

    /**
     * Calculate available working minutes between two dates considering active study window,
     * weekly recurring classes (CourseSchedule), and calendar events (Event).
     */
    public function calculateAvailableWorkMinutes(Carbon $start, Carbon $end): int
    {
        if ($start >= $end) {
            return 0;
        }

        // Cache schedules and events in the range to optimize query count
        $schedulesByDay = CourseSchedule::all()->groupBy('day_of_week');
        $events = Event::where('start_date', '<=', $end)
            ->where(function ($query) use ($start) {
                $query->where('end_date', '>=', $start)
                    ->orWhere('start_date', '>=', $start);
            })
            ->get();

        $totalAvailableMinutes = 0;
        $currentDate = $start->copy()->startOfDay();
        $endDate = $end->copy()->startOfDay();

        while ($currentDate <= $endDate) {
            $windowStart = $currentDate->copy()->setTime(self::WORK_WINDOW_START_HOUR, 0);
            $windowEnd = $currentDate->copy()->setTime(self::WORK_WINDOW_END_HOUR, 0);

            // Clamp window to actual start and end
            if ($windowStart < $start) {
                $windowStart = $start->copy();
            }
            if ($windowEnd > $end) {
                $windowEnd = $end->copy();
            }

            if ($windowStart < $windowEnd) {
                $dayMinutes = $windowStart->diffInMinutes($windowEnd);

                // Subtract overlapping class schedules for this day of week
                $daySchedules = $schedulesByDay->get($currentDate->dayOfWeekIso, collect());
                $busyIntervals = [];

                foreach ($daySchedules as $schedule) {
                    $classStart = $currentDate->copy()->setTimeFromTimeString($schedule->start_time);
                    $classEnd = $currentDate->copy()->setTimeFromTimeString($schedule->end_time);

                    $overlap = $this->calculateOverlapMinutes($windowStart, $windowEnd, $classStart, $classEnd);
                    if ($overlap > 0) {
                        $busyIntervals[] = [
                            'start' => max($windowStart->timestamp, $classStart->timestamp),
                            'end' => min($windowEnd->timestamp, $classEnd->timestamp),
                        ];
                    }
                }

                // Subtract overlapping events
                foreach ($events as $event) {
                    $evStart = $event->start_date;
                    $evEnd = $event->end_date ?? $event->start_date->copy()->addHour();

                    $overlap = $this->calculateOverlapMinutes($windowStart, $windowEnd, $evStart, $evEnd);
                    if ($overlap > 0) {
                        $busyIntervals[] = [
                            'start' => max($windowStart->timestamp, $evStart->timestamp),
                            'end' => min($windowEnd->timestamp, $evEnd->timestamp),
                        ];
                    }
                }

                // Merge overlapping busy intervals to avoid double subtraction
                $busyMinutes = $this->mergeAndSumIntervals($busyIntervals);
                $netAvailable = max(0, $dayMinutes - $busyMinutes);
                $totalAvailableMinutes += $netAvailable;
            }

            $currentDate->addDay();
        }

        return $totalAvailableMinutes;
    }

    /**
     * Compute overlap in minutes between two time windows.
     */
    private function calculateOverlapMinutes(Carbon $winStart, Carbon $winEnd, Carbon $itemStart, Carbon $itemEnd): int
    {
        $start = max($winStart->timestamp, $itemStart->timestamp);
        $end = min($winEnd->timestamp, $itemEnd->timestamp);

        return max(0, (int) round(($end - $start) / 60));
    }

    /**
     * Merge intervals and sum duration in minutes.
     *
     * @param  array<int, array{start: int, end: int}>  $intervals
     */
    private function mergeAndSumIntervals(array $intervals): int
    {
        if (empty($intervals)) {
            return 0;
        }

        usort($intervals, fn ($a, $b) => $a['start'] <=> $b['start']);

        $merged = [];
        $current = $intervals[0];

        for ($i = 1; $i < count($intervals); $i++) {
            if ($intervals[$i]['start'] <= $current['end']) {
                $current['end'] = max($current['end'], $intervals[$i]['end']);
            } else {
                $merged[] = $current;
                $current = $intervals[$i];
            }
        }
        $merged[] = $current;

        $totalSeconds = 0;
        foreach ($merged as $interval) {
            $totalSeconds += ($interval['end'] - $interval['start']);
        }

        return (int) round($totalSeconds / 60);
    }

    /**
     * Map ratio (remaining work / available time) to 0–100 risk score.
     */
    private function ratioToScore(float $ratio): int
    {
        return match (true) {
            $ratio >= 1.5 => 100,
            $ratio >= 1.0 => 85 + (int) min(14, round(($ratio - 1.0) * 28)),
            $ratio >= 0.75 => 70 + (int) round(($ratio - 0.75) * 60),
            $ratio >= 0.45 => 40 + (int) round(($ratio - 0.45) * 100),
            $ratio >= 0.20 => 20 + (int) round(($ratio - 0.20) * 80),
            default => max(0, (int) round($ratio * 100)),
        };
    }

    /**
     * Build human-readable reason in Indonesian.
     */
    private function buildReason(float $remainingHours, float $availableHours, float $ratio, Task $task): string
    {
        if ($ratio >= 1.2) {
            return "Sisa kerja ({$remainingHours} jam) jauh melebihi waktu luang yang ada ({$availableHours} jam).";
        }

        if ($ratio >= 1.0) {
            return "Sisa kerja ({$remainingHours} jam) melebihi waktu luang tersedia ({$availableHours} jam).";
        }

        if ($ratio >= 0.75) {
            return "Waktu luang sangat sempit ({$availableHours} jam tersedia untuk sisa {$remainingHours} jam kerja).";
        }

        if ($ratio >= 0.45) {
            return "Waktu luang cukup ({$availableHours} jam tersedia untuk sisa {$remainingHours} jam kerja), disarankan mulai mencicil.";
        }

        if ($ratio >= 0.20) {
            return "Waktu luang aman ({$availableHours} jam tersedia untuk sisa {$remainingHours} jam kerja).";
        }

        return "Waktu luang sangat melimpah ({$availableHours} jam tersedia untuk sisa {$remainingHours} jam kerja).";
    }
}
