<?php

namespace App\DataObjects;

class PriorityResult
{
    /**
     * @param  array{deadline: int, importance: int, progress: int, workload: int}  $breakdown
     */
    public function __construct(
        public int $score,
        public string $level,
        public string $reason,
        public array $breakdown = [],
    ) {}

    /**
     * Map level to Indonesian label.
     */
    public static function levelLabel(int $score): string
    {
        return match (true) {
            $score >= 80 => 'KRITIS',
            $score >= 55 => 'TINGGI',
            $score >= 30 => 'SEDANG',
            default => 'RENDAH',
        };
    }

    /**
     * Map level to CSS class / color key.
     */
    public static function levelColor(string $level): string
    {
        return match ($level) {
            'KRITIS' => 'critical',
            'TINGGI' => 'high',
            'SEDANG' => 'medium',
            'RENDAH' => 'low',
            default => 'low',
        };
    }
}
