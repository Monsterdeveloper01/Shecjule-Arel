<?php

namespace App\Services;

use App\Models\Habit;
use App\Models\HabitLog;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class HabitService
{
    /**
     * Get all habits with today's status & streak populated.
     */
    public function getHabitsForToday(?Carbon $date = null): Collection
    {
        $ref = $date ? $date->copy() : now();
        $this->seedDefaultHabitsIfEmpty();

        $habits = Habit::orderBy('order')->orderBy('id')->get();

        return $habits->map(function (Habit $h) use ($ref) {
            $status = $h->getStatusForDate($ref);
            $streak = $h->calculateStreak();

            return [
                'id' => $h->id,
                'title' => $h->title,
                'description' => $h->description,
                'icon' => $h->icon,
                'color' => $h->color,
                'category' => $h->category,
                'cadence' => $h->cadence,
                'specific_days' => $h->specific_days,
                'is_scheduled' => $status['is_scheduled'],
                'is_completed' => $status['is_completed'],
                'is_rest_day' => $status['is_rest_day'],
                'status_label' => $status['label'],
                'streak' => $streak,
            ];
        });
    }

    /**
     * Toggle habit completion status for a given date.
     */
    public function toggleHabit(Habit $habit, Carbon|string $date): HabitLog
    {
        $d = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();

        $log = $habit->logs()->where('log_date', $d)->first();

        if ($log && $log->status === 'completed') {
            $log->delete();

            return new HabitLog([
                'habit_id' => $habit->id,
                'log_date' => $d,
                'status' => 'uncompleted',
            ]);
        }

        return $habit->logs()->updateOrCreate(
            ['log_date' => $d],
            ['status' => 'completed']
        );
    }

    /**
     * Calculate habit consistency rate for the past N days.
     */
    public function getConsistencyRate(int $days = 7): float
    {
        $habits = Habit::all();
        if ($habits->isEmpty()) {
            return 100.0;
        }

        $totalScheduled = 0;
        $totalCompleted = 0;

        for ($i = 0; $i < $days; $i++) {
            $targetDate = now()->subDays($i);
            $dateStr = $targetDate->toDateString();

            foreach ($habits as $h) {
                $status = $h->getStatusForDate($targetDate);
                if ($status['is_scheduled']) {
                    $totalScheduled++;
                    if ($status['is_completed']) {
                        $totalCompleted++;
                    }
                } elseif ($status['is_rest_day']) {
                    // Scheduled rest days count positively towards consistency
                    $totalScheduled++;
                    $totalCompleted++;
                }
            }
        }

        if ($totalScheduled === 0) {
            return 100.0;
        }

        return round(($totalCompleted / $totalScheduled) * 100, 1);
    }

    /**
     * Seed initial default routine habits if the table is currently empty.
     */
    public function seedDefaultHabitsIfEmpty(): void
    {
        if (Habit::count() > 0) {
            return;
        }

        Habit::create([
            'title' => 'Latihan Gym / Olahraga',
            'description' => 'Selang-seling: 1 hari latihan beban/kardio, 1 hari istirahat pemulihan otot.',
            'icon' => '🏋️',
            'color' => '#f97316',
            'category' => 'kesehatan',
            'cadence' => 'alternate_days',
            'order' => 1,
        ]);

        Habit::create([
            'title' => 'Review & Cicil Materi Kuliah',
            'description' => 'Baca ulang slide kuliah hari ini atau cicil bab tugas minimal 25 menit.',
            'icon' => '📚',
            'color' => '#6366f1',
            'category' => 'akademik',
            'cadence' => 'daily',
            'order' => 2,
        ]);

        Habit::create([
            'title' => 'Tidur Teratur & Istirahat Cukup',
            'description' => 'Tidur minimal 7 jam untuk menjaga fokus dan energi keesokan harinya.',
            'icon' => '🌙',
            'color' => '#8b5cf6',
            'category' => 'kesehatan',
            'cadence' => 'daily',
            'order' => 3,
        ]);
    }
}
