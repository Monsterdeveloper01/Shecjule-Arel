<?php

namespace App\Services;

use App\DataObjects\FreeTimeSlot;
use App\DataObjects\OverloadAnalysis;
use App\DataObjects\ScheduleRecommendation;
use App\Models\CourseSchedule;
use App\Models\Event;
use App\Models\Task;
use Illuminate\Support\Collection;

class TodayIntelligenceService
{
    public function __construct(
        protected FreeTimeService $freeTimeService,
        protected ScheduleRecommendationService $recommendationService,
        protected ScheduleOverloadService $overloadService,
        protected DeadlineRiskEngine $riskEngine,
        protected PriorityEngine $priorityEngine,
    ) {}

    /**
     * Build the complete Today Intelligence Hub bundle for the dashboard.
     *
     * @return array{
     *     overload: OverloadAnalysis,
     *     nextUp: ?array{type: string, title: string, time: string, location: ?string, is_ongoing: bool, countdown: string},
     *     freeSlots: Collection<int, FreeTimeSlot>,
     *     totalFreeHoursToday: float,
     *     recommendation: ?ScheduleRecommendation,
     *     highRiskTasks: Collection<int, Task>,
     *     focusTask: ?Task
     * }
     */
    public function getTodayHub(): array
    {
        $now = now();
        $today = $now->toDateString();

        // 1. Overload Analysis
        $overload = $this->overloadService->analyzeDate($now);

        // 2. Next Up Item (Class or Event)
        $nextUp = $this->resolveNextUp();

        // 3. Free Slots for Today (from now forward)
        $freeSlots = $this->freeTimeService->getFreeSlotsForDate($now, minDurationMinutes: 30, fromNowIfToday: true);
        $totalFreeMinutes = $this->freeTimeService->getTotalFreeMinutesForDate($now, fromNowIfToday: true);
        $totalFreeHoursToday = round($totalFreeMinutes / 60, 1);

        // 4. Smart Schedule Recommendation
        $recommendation = $this->recommendationService->getPrimaryRecommendationToday();

        // 5. High Risk & Critical Tasks
        $highRiskTasks = $this->riskEngine->getHighRiskTasks();

        // 6. Focus Task (Highest priority active task)
        $focusTask = Task::where('status', '!=', 'completed')
            ->orderByDesc('priority_score')
            ->first();

        return [
            'overload' => $overload,
            'nextUp' => $nextUp,
            'freeSlots' => $freeSlots,
            'totalFreeHoursToday' => $totalFreeHoursToday,
            'recommendation' => $recommendation,
            'highRiskTasks' => $highRiskTasks,
            'focusTask' => $focusTask,
        ];
    }

    /**
     * Determine the next immediate event or lecture today.
     *
     * @return ?array{type: string, title: string, time: string, location: ?string, is_ongoing: bool, countdown: string}
     */
    private function resolveNextUp(): ?array
    {
        $now = now();
        $candidates = collect();

        // Today's classes
        $classes = CourseSchedule::today()->orderBy('start_time')->get();
        foreach ($classes as $c) {
            $start = $now->copy()->setTimeFromTimeString($c->start_time);
            $end = $now->copy()->setTimeFromTimeString($c->end_time);

            if ($end >= $now) {
                $isOngoing = $now->between($start, $end);
                $countdown = $isOngoing
                    ? 'Sedang berlangsung (selesai '.$end->diffForHumans().')'
                    : 'Mulai '.$start->diffForHumans();

                $candidates->push([
                    'type' => 'Kuliah',
                    'title' => $c->course_name,
                    'time' => $c->start_time_formatted.' – '.$c->end_time_formatted,
                    'location' => $c->room,
                    'is_ongoing' => $isOngoing,
                    'countdown' => $countdown,
                    'sort_key' => $start->timestamp,
                ]);
            }
        }

        // Today's events
        $events = Event::forDate($now->toDateString())->orderBy('start_date')->get();
        foreach ($events as $ev) {
            $start = $ev->start_date;
            $end = $ev->end_date ?? $start->copy()->addHour();

            if ($end >= $now) {
                $isOngoing = $now->between($start, $end);
                $countdown = $isOngoing
                    ? 'Sedang berlangsung'
                    : 'Mulai '.$start->diffForHumans();

                $candidates->push([
                    'type' => 'Acara',
                    'title' => $ev->title,
                    'time' => $start->format('H:i').' – '.$end->format('H:i'),
                    'location' => $ev->location,
                    'is_ongoing' => $isOngoing,
                    'countdown' => $countdown,
                    'sort_key' => $start->timestamp,
                ]);
            }
        }

        $sorted = $candidates->sortBy('sort_key')->values();

        return $sorted->first();
    }
}
