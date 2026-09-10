<?php

namespace App\DataObjects;

class AiBreakdownResult
{
    /**
     * @param  array<int, array{title: string, phase: string, estimated_minutes: int}>  $steps
     */
    public function __construct(
        public string $goal,
        public array $steps,
        public int $totalEstimatedMinutes,
        public ?string $tips = null,
    ) {}

    /**
     * Convert to array for JSON responses.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'goal' => $this->goal,
            'steps' => $this->steps,
            'total_estimated_minutes' => $this->totalEstimatedMinutes,
            'tips' => $this->tips,
        ];
    }
}
