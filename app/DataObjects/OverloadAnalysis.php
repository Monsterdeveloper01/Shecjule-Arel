<?php

namespace App\DataObjects;

class OverloadAnalysis
{
    public function __construct(
        public string $level, // RENDAH / MODERAT / TINGGI / KRITIS
        public int $score,   // 0–100
        public int $classCount,
        public int $eventCount,
        public int $deadlinesCount,
        public float $busyHours,
        public float $freeHours,
        public float $workloadHours,
        public string $headline,
        public string $recommendation,
    ) {}

    /**
     * Map score to Indonesian level label.
     */
    public static function levelLabel(int $score): string
    {
        return match (true) {
            $score >= 80 => 'KRITIS',
            $score >= 60 => 'TINGGI',
            $score >= 35 => 'MODERAT',
            default => 'RENDAH',
        };
    }
}
