<?php

namespace App\Models;

use App\Models\Concerns\HasAttachment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CourseSchedule extends Model
{
    use HasAttachment;

    /**
     * Day names in Indonesian mapped to ISO-8601 day of week (1 = Monday, 7 = Sunday).
     *
     * @var array<int, string>
     */
    public const DAYS = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'course_name',
        'course_code',
        'sks',
        'class_code',
        'lecturer_name',
        'day_of_week',
        'start_time',
        'end_time',
        'room',
        'delivery_mode',
        'meeting_link',
        'color_tag',
        'notes',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'day_name',
        'start_time_formatted',
        'end_time_formatted',
        'time_range',
        'is_today',
        'is_ongoing_now',
        'remaining_minutes_now',
        'minutes_until_start',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sks' => 'integer',
            'day_of_week' => 'integer',
        ];
    }

    /**
     * Scope a query to only include schedules for today.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->where('day_of_week', now()->dayOfWeekIso);
    }

    /**
     * Scope a query to only include schedules for a specific day.
     */
    public function scopeForDay(Builder $query, int $day): Builder
    {
        return $query->where('day_of_week', $day);
    }

    /**
     * Scope a query to order schedules by start time.
     */
    public function scopeOrderByTime(Builder $query): Builder
    {
        return $query->orderBy('start_time');
    }

    /**
     * Get Indonesian day name for this schedule.
     */
    public function getDayNameAttribute(): string
    {
        return self::DAYS[$this->day_of_week] ?? 'Hari '.$this->day_of_week;
    }

    /**
     * Get start time formatted as H:i.
     */
    public function getStartTimeFormattedAttribute(): string
    {
        if (! $this->start_time) {
            return '';
        }

        return substr((string) $this->start_time, 0, 5);
    }

    /**
     * Get end time formatted as H:i.
     */
    public function getEndTimeFormattedAttribute(): string
    {
        if (! $this->end_time) {
            return '';
        }

        return substr((string) $this->end_time, 0, 5);
    }

    /**
     * Get formatted time range string (e.g. "07:30 - 10:30").
     */
    public function getTimeRangeAttribute(): string
    {
        return "{$this->start_time_formatted} - {$this->end_time_formatted}";
    }

    /**
     * Check if schedule day is today.
     */
    public function getIsTodayAttribute(): bool
    {
        return (int) $this->day_of_week === (int) now()->dayOfWeekIso;
    }

    /**
     * Check if the class is currently in session right now.
     */
    public function getIsOngoingNowAttribute(): bool
    {
        if (! $this->is_today) {
            return false;
        }

        $now = now()->format('H:i:s');
        $start = substr((string) $this->start_time, 0, 8);
        $end = substr((string) $this->end_time, 0, 8);

        return $now >= $start && $now <= $end;
    }

    /**
     * Get remaining minutes if class is ongoing now.
     */
    public function getRemainingMinutesNowAttribute(): ?int
    {
        if (! $this->is_ongoing_now) {
            return null;
        }

        $nowTime = Carbon::now();
        $endTime = Carbon::createFromTimeString(substr((string) $this->end_time, 0, 8));

        return max(0, (int) $nowTime->diffInMinutes($endTime, false));
    }

    /**
     * Get minutes until class starts if today and hasn't started yet.
     */
    public function getMinutesUntilStartAttribute(): ?int
    {
        if (! $this->is_today) {
            return null;
        }

        $now = now()->format('H:i:s');
        $start = substr((string) $this->start_time, 0, 8);

        if ($now >= $start) {
            return null;
        }

        $nowTime = Carbon::now();
        $startTime = Carbon::createFromTimeString($start);

        return max(0, (int) $nowTime->diffInMinutes($startTime, false));
    }

    /**
     * Calculate duration of class in minutes.
     */
    public function getDurationMinutesAttribute(): int
    {
        $startTime = Carbon::createFromTimeString(substr((string) $this->start_time, 0, 8));
        $endTime = Carbon::createFromTimeString(substr((string) $this->end_time, 0, 8));

        return (int) $startTime->diffInMinutes($endTime);
    }
}
