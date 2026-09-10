<?php

namespace App\DataObjects;

use App\Models\Task;

class ScheduleRecommendation
{
    public function __construct(
        public Task $task,
        public FreeTimeSlot $slot,
        public int $recommendedDurationMinutes,
        public string $reason,
        public int $matchScore = 100,
    ) {}
}
