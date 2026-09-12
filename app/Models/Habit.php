<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Habit extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'icon',
        'color',
        'category',
        'cadence',
        'specific_days',
        'target_days_per_week',
        'order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'specific_days' => 'array',
            'target_days_per_week' => 'integer',
            'order' => 'integer',
        ];
    }

    /**
     * Daily logs for this habit.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(HabitLog::class);
    }

    /**
     * Check if the habit is scheduled for a given date.
     */
    public function isScheduledForDate(Carbon|string $date): bool
    {
        $d = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);

        if ($this->cadence === 'daily') {
            return true;
        }

        if ($this->cadence === 'specific_days') {
            $days = $this->specific_days ?? [];

            return in_array($d->dayOfWeekIso, $days, false);
        }

        if ($this->cadence === 'alternate_days') {
            // Check based on the most recent completed log
            $lastLog = $this->logs()
                ->where('status', 'completed')
                ->where('log_date', '<=', $d->toDateString())
                ->orderByDesc('log_date')
                ->first();

            if (! $lastLog) {
                // If no history yet, scheduled on first day
                return true;
            }

            $lastLogDate = Carbon::parse($lastLog->log_date)->startOfDay();
            $targetDate = $d->copy()->startOfDay();
            $diffDays = (int) $lastLogDate->diffInDays($targetDate);

            // Even difference (0 days = today completed, 2 days = alternate cycle active)
            // If diffDays is 1 -> yesterday was workout, so today is Rest Day (not workout day)
            // If diffDays is 2 (or 0) -> workout day
            return $diffDays % 2 === 0;
        }

        return true;
    }

    /**
     * Determine today's contextual status (especially for alternate_days workout vs rest day).
     *
     * @return array{is_scheduled: bool, is_completed: bool, is_rest_day: bool, label: string}
     */
    public function getStatusForDate(Carbon|string $date): array
    {
        $d = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);
        $dateStr = $d->toDateString();

        $log = $this->logs()->where('log_date', $dateStr)->first();
        $isCompleted = $log && $log->status === 'completed';

        if ($this->cadence === 'alternate_days') {
            $lastCompleted = $this->logs()
                ->where('status', 'completed')
                ->where('log_date', '<', $dateStr)
                ->orderByDesc('log_date')
                ->first();

            if ($lastCompleted) {
                $lastLogDate = Carbon::parse($lastCompleted->log_date)->startOfDay();
                $targetDate = $d->copy()->startOfDay();
                $diff = (int) $lastLogDate->diffInDays($targetDate);
                if ($diff === 1) {
                    // Yesterday was completed -> today is Rest Day
                    return [
                        'is_scheduled' => false,
                        'is_completed' => false,
                        'is_rest_day' => true,
                        'label' => 'Rest Day Terjadwal 🧘‍♂️',
                    ];
                }
            }

            return [
                'is_scheduled' => true,
                'is_completed' => $isCompleted,
                'is_rest_day' => false,
                'label' => $isCompleted ? 'Latihan Selesai 💪' : 'Jadwal Latihan Hari Ini! 💪',
            ];
        }

        $scheduled = $this->isScheduledForDate($d);

        return [
            'is_scheduled' => $scheduled,
            'is_completed' => $isCompleted,
            'is_rest_day' => ! $scheduled,
            'label' => $isCompleted ? 'Selesai ✨' : ($scheduled ? 'Jadwal Hari Ini' : 'Istirahat'),
        ];
    }

    /**
     * Calculate current consecutive active streak (taking rest days into account).
     */
    public function calculateStreak(): int
    {
        $today = now()->toDateString();
        $streak = 0;
        $currentDate = now();

        // If today is completed, start streak with 1
        $todayLog = $this->logs()->where('log_date', $today)->first();
        $todayStatus = $this->getStatusForDate($today);

        if ($todayLog && $todayLog->status === 'completed') {
            $streak++;
            $currentDate->subDay();
        } elseif ($todayStatus['is_rest_day']) {
            // If today is rest day, don't break streak, check yesterday
            $currentDate->subDay();
        } else {
            // Not yet completed today, check if yesterday was done
            $yesterday = now()->subDay()->toDateString();
            $yesterdayLog = $this->logs()->where('log_date', $yesterday)->first();
            $yesterdayStatus = $this->getStatusForDate($yesterday);

            if (($yesterdayLog && $yesterdayLog->status === 'completed') || $yesterdayStatus['is_rest_day']) {
                $currentDate = now()->subDay();
            } else {
                return 0;
            }
        }

        // Loop backwards up to 365 days
        for ($i = 0; $i < 365; $i++) {
            $dateStr = $currentDate->toDateString();
            $log = $this->logs()->where('log_date', $dateStr)->first();
            $status = $this->getStatusForDate($currentDate);

            if ($log && $log->status === 'completed') {
                $streak++;
                $currentDate->subDay();
            } elseif ($status['is_rest_day']) {
                // Scheduled rest day preserves the streak!
                $currentDate->subDay();
            } else {
                break;
            }
        }

        return $streak;
    }
}
