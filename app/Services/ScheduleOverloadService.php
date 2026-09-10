<?php

namespace App\Services;

use App\DataObjects\OverloadAnalysis;
use App\Models\CourseSchedule;
use App\Models\Event;
use App\Models\Task;
use Carbon\Carbon;

class ScheduleOverloadService
{
    public function __construct(
        protected FreeTimeService $freeTimeService
    ) {}

    /**
     * Analyze schedule overload for a given date.
     */
    public function analyzeDate(?Carbon $date = null): OverloadAnalysis
    {
        $targetDate = $date ?? now();
        $dayOfWeek = $targetDate->dayOfWeekIso;

        // 1. Classes on this day
        $classes = CourseSchedule::where('day_of_week', $dayOfWeek)->get();
        $classCount = $classes->count();
        $classMinutes = 0;
        foreach ($classes as $c) {
            $s = Carbon::createFromTimeString($c->start_time);
            $e = Carbon::createFromTimeString($c->end_time);
            $classMinutes += max(0, $s->diffInMinutes($e));
        }

        // 2. Events on this day
        $events = Event::forDate($targetDate->toDateString())->get();
        $eventCount = $events->count();
        $eventMinutes = 0;
        foreach ($events as $ev) {
            $eEnd = $ev->end_date ?? $ev->start_date->copy()->addHour();
            $eventMinutes += max(0, $ev->start_date->diffInMinutes($eEnd));
        }

        // 3. Deadlines due on this day
        $deadlines = Task::forDate($targetDate->toDateString())
            ->where('status', '!=', 'completed')
            ->get();
        $deadlineCount = $deadlines->count();
        $taskWorkloadMinutes = $deadlines->sum('remaining_duration');

        // 4. Free time on this day
        $freeMinutes = $this->freeTimeService->getTotalFreeMinutesForDate($targetDate);

        // Convert to hours
        $busyHours = round(($classMinutes + $eventMinutes) / 60, 1);
        $freeHours = round($freeMinutes / 60, 1);
        $workloadHours = round($taskWorkloadMinutes / 60, 1);

        // 5. Score calculation (0-100)
        // Total committed burden = busy hours + workload hours
        $totalBurdenHours = $busyHours + $workloadHours;

        // Normal active student day is 15h (07:00-22:00)
        $score = match (true) {
            $totalBurdenHours >= 12 || ($deadlineCount >= 3 && $busyHours >= 6) => 90,
            $totalBurdenHours >= 9 || ($deadlineCount >= 2 && $busyHours >= 5) => 75,
            $totalBurdenHours >= 6 || $busyHours >= 6 => 55,
            $totalBurdenHours >= 3 || $classCount >= 2 => 35,
            default => 15,
        };

        $level = OverloadAnalysis::levelLabel($score);

        // 6. Generate headline & recommendation
        $headline = match ($level) {
            'KRITIS' => 'Jadwal Hari Ini Sangat Padat & Berisiko Overload!',
            'TINGGI' => 'Hari Ini Cukup Padat, Kelola Waktu dengan Disiplin.',
            'MODERAT' => 'Kepadatan Hari Ini Seimbang.',
            default => 'Jadwal Hari Ini Santai & Terkendali.',
        };

        $recommendation = match ($level) {
            'KRITIS' => "Kamu memiliki {$classCount} matkul, {$eventCount} acara, dan {$deadlineCount} deadline. Prioritaskan tugas utama dan delegasikan hal non-esensial.",
            'TINGGI' => "Terdapat {$classCount} matkul dan {$deadlineCount} deadline ({$workloadHours}j beban tugas). Manfaatkan slot waktu luang {$freeHours} jam dengan fokus.",
            'MODERAT' => "Ada {$classCount} matkul dan waktu luang {$freeHours} jam. Waktu yang tepat untuk mencicil tugas sebelum deadline.",
            default => "Kamu punya banyak waktu luang ({$freeHours} jam). Bagus untuk istirahat atau mulai belajar materi ke depan.",
        };

        return new OverloadAnalysis(
            level: $level,
            score: $score,
            classCount: $classCount,
            eventCount: $eventCount,
            deadlinesCount: $deadlineCount,
            busyHours: $busyHours,
            freeHours: $freeHours,
            workloadHours: $workloadHours,
            headline: $headline,
            recommendation: $recommendation,
        );
    }
}
