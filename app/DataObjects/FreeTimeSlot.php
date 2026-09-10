<?php

namespace App\DataObjects;

use Carbon\Carbon;

class FreeTimeSlot
{
    public function __construct(
        public Carbon $start,
        public Carbon $end,
        public int $durationMinutes,
        public string $formattedRange,
        public string $formattedDuration,
        public bool $isAvailableNow = false,
    ) {}

    /**
     * Helper to format minutes into human Indonesian string (e.g. "1 jam 30 mnt" or "45 mnt").
     */
    public static function formatMinutes(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $rem = $minutes % 60;

        if ($hours > 0 && $rem > 0) {
            return "{$hours}j {$rem}m";
        } elseif ($hours > 0) {
            return "{$hours} jam";
        }

        return "{$rem} menit";
    }
}
