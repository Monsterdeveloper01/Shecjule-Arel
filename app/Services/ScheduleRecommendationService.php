<?php

namespace App\Services;

use App\DataObjects\FreeTimeSlot;
use App\DataObjects\ScheduleRecommendation;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ScheduleRecommendationService
{
    public function __construct(
        protected FreeTimeService $freeTimeService
    ) {}

    /**
     * Generate smart recommendations for a specific date (default: today).
     *
     * @return Collection<int, ScheduleRecommendation>
     */
    public function getRecommendationsForDate(?Carbon $date = null): Collection
    {
        $targetDate = $date ?? now();
        $freeSlots = $this->freeTimeService->getFreeSlotsForDate($targetDate, minDurationMinutes: 30, fromNowIfToday: true);

        if ($freeSlots->isEmpty()) {
            return collect();
        }

        // Active tasks sorted by priority & risk
        $candidateTasks = Task::where('status', '!=', 'completed')
            ->orderByDesc('risk_score')
            ->orderByDesc('priority_score')
            ->get();

        if ($candidateTasks->isEmpty()) {
            return collect();
        }

        $recommendations = collect();
        $assignedTaskIds = [];

        foreach ($freeSlots as $slot) {
            $bestTask = null;
            $bestScore = -1;
            $bestReason = '';

            foreach ($candidateTasks as $task) {
                if (in_array($task->id, $assignedTaskIds, true)) {
                    continue;
                }

                $remainingMinutes = $task->remaining_duration;
                if ($remainingMinutes <= 0) {
                    continue;
                }

                // Match calculation:
                // Base score from priority and risk (0-100 each)
                $priorityWeight = ($task->priority_score ?? 50) * 0.45;
                $riskWeight = ($task->risk_score ?? 30) * 0.40;

                // Duration fit weight (0-15 points)
                // Ideal: task remaining work is close to or slightly less than slot duration
                $durationDiff = abs($slot->durationMinutes - $remainingMinutes);
                $fitWeight = max(0, 15 - ($durationDiff / 20));

                $totalMatchScore = (int) round($priorityWeight + $riskWeight + $fitWeight);

                if ($totalMatchScore > $bestScore) {
                    $bestScore = $totalMatchScore;
                    $bestTask = $task;

                    $slotDurationFormatted = FreeTimeSlot::formatMinutes($slot->durationMinutes);
                    if ($task->risk_level === 'KRITIS' || $task->risk_level === 'TINGGI') {
                        $bestReason = "Prioritas tinggi & berisiko mepet. Manfaatkan slot {$slotDurationFormatted} ini.";
                    } elseif ($remainingMinutes <= $slot->durationMinutes) {
                        $bestReason = "Beban kerja cukup diselesaikan penuh dalam slot {$slotDurationFormatted} ini.";
                    } else {
                        $bestReason = 'Cocok untuk mencicil sebagian besar pengerjaan tugas.';
                    }
                }
            }

            if ($bestTask) {
                $recommendedDuration = min($slot->durationMinutes, $bestTask->remaining_duration);

                $recommendations->push(new ScheduleRecommendation(
                    task: $bestTask,
                    slot: $slot,
                    recommendedDurationMinutes: $recommendedDuration,
                    reason: $bestReason,
                    matchScore: min(100, $bestScore),
                ));

                $assignedTaskIds[] = $bestTask->id;
            }
        }

        return $recommendations;
    }

    /**
     * Get the single primary recommendation for right now or the next free slot today.
     */
    public function getPrimaryRecommendationToday(): ?ScheduleRecommendation
    {
        return $this->getRecommendationsForDate(now())->first();
    }
}
