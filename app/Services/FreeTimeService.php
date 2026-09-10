<?php

namespace App\Services;

use App\DataObjects\FreeTimeSlot;
use App\Models\CourseSchedule;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FreeTimeService
{
    /**
     * Active daily study window.
     */
    public const WORK_WINDOW_START_HOUR = 7;

    public const WORK_WINDOW_END_HOUR = 22;

    /**
     * Minimum duration in minutes for a gap to be considered a viable free slot.
     */
    public const DEFAULT_MIN_SLOT_MINUTES = 30;

    /**
     * Detect all free time slots for a specific date.
     *
     * @return Collection<int, FreeTimeSlot>
     */
    public function getFreeSlotsForDate(
        Carbon $date,
        int $minDurationMinutes = self::DEFAULT_MIN_SLOT_MINUTES,
        bool $fromNowIfToday = false
    ): Collection {
        $windowStart = $date->copy()->setTime(self::WORK_WINDOW_START_HOUR, 0, 0);
        $windowEnd = $date->copy()->setTime(self::WORK_WINDOW_END_HOUR, 0, 0);

        if ($fromNowIfToday && $date->isToday() && now() > $windowStart) {
            $windowStart = now()->copy();
        }

        if ($windowStart >= $windowEnd) {
            return collect();
        }

        // 1. Gather busy intervals from CourseSchedule
        $busyIntervals = [];
        $dayOfWeek = $date->dayOfWeekIso;
        $classes = CourseSchedule::where('day_of_week', $dayOfWeek)->get();

        foreach ($classes as $class) {
            $classStart = $date->copy()->setTimeFromTimeString($class->start_time);
            $classEnd = $date->copy()->setTimeFromTimeString($class->end_time);

            if ($classStart < $windowEnd && $classEnd > $windowStart) {
                $busyIntervals[] = [
                    'start' => max($windowStart->timestamp, $classStart->timestamp),
                    'end' => min($windowEnd->timestamp, $classEnd->timestamp),
                ];
            }
        }

        // 2. Gather busy intervals from Events
        $events = Event::forDate($date->toDateString())->get();
        foreach ($events as $event) {
            $evStart = $event->start_date;
            $evEnd = $event->end_date ?? $event->start_date->copy()->addHour();

            if ($evStart < $windowEnd && $evEnd > $windowStart) {
                $busyIntervals[] = [
                    'start' => max($windowStart->timestamp, $evStart->timestamp),
                    'end' => min($windowEnd->timestamp, $evEnd->timestamp),
                ];
            }
        }

        // 3. Merge overlapping or contiguous busy intervals
        $mergedBusy = $this->mergeIntervals($busyIntervals);

        // 4. Invert busy intervals to find free gaps
        $slots = collect();
        $cursorTimestamp = $windowStart->timestamp;
        $windowEndTimestamp = $windowEnd->timestamp;

        foreach ($mergedBusy as $busy) {
            if ($busy['start'] > $cursorTimestamp) {
                $gapMinutes = (int) round(($busy['start'] - $cursorTimestamp) / 60);
                if ($gapMinutes >= $minDurationMinutes) {
                    $slotStart = Carbon::createFromTimestamp($cursorTimestamp);
                    $slotEnd = Carbon::createFromTimestamp($busy['start']);

                    $slots->push(new FreeTimeSlot(
                        start: $slotStart,
                        end: $slotEnd,
                        durationMinutes: $gapMinutes,
                        formattedRange: $slotStart->format('H:i').' – '.$slotEnd->format('H:i').' WIB',
                        formattedDuration: FreeTimeSlot::formatMinutes($gapMinutes),
                        isAvailableNow: $date->isToday() && now()->between($slotStart, $slotEnd),
                    ));
                }
            }
            $cursorTimestamp = max($cursorTimestamp, $busy['end']);
        }

        // Remaining time after last busy event until window end
        if ($cursorTimestamp < $windowEndTimestamp) {
            $gapMinutes = (int) round(($windowEndTimestamp - $cursorTimestamp) / 60);
            if ($gapMinutes >= $minDurationMinutes) {
                $slotStart = Carbon::createFromTimestamp($cursorTimestamp);
                $slotEnd = Carbon::createFromTimestamp($windowEndTimestamp);

                $slots->push(new FreeTimeSlot(
                    start: $slotStart,
                    end: $slotEnd,
                    durationMinutes: $gapMinutes,
                    formattedRange: $slotStart->format('H:i').' – '.$slotEnd->format('H:i').' WIB',
                    formattedDuration: FreeTimeSlot::formatMinutes($gapMinutes),
                    isAvailableNow: $date->isToday() && now()->between($slotStart, $slotEnd),
                ));
            }
        }

        return $slots;
    }

    /**
     * Get total free working minutes for a date.
     */
    public function getTotalFreeMinutesForDate(Carbon $date, bool $fromNowIfToday = false): int
    {
        return $this->getFreeSlotsForDate($date, minDurationMinutes: 1, fromNowIfToday: $fromNowIfToday)
            ->sum('durationMinutes');
    }

    /**
     * Get the next upcoming free time slot today.
     */
    public function getNextFreeSlotToday(): ?FreeTimeSlot
    {
        return $this->getFreeSlotsForDate(now(), minDurationMinutes: 15, fromNowIfToday: true)
            ->first(fn (FreeTimeSlot $slot) => $slot->end > now());
    }

    /**
     * Merge intervals.
     *
     * @param  array<int, array{start: int, end: int}>  $intervals
     * @return array<int, array{start: int, end: int}>
     */
    private function mergeIntervals(array $intervals): array
    {
        if (empty($intervals)) {
            return [];
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

        return $merged;
    }
}
