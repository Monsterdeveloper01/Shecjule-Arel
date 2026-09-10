<?php

namespace App\DataObjects;

class DeadlineRiskResult
{
    public function __construct(
        public int $score,
        public string $level,
        public string $reason,
        public float $remainingWorkHours = 0.0,
        public float $availableHours = 0.0,
        public float $feasibilityRatio = 0.0,
    ) {}

    /**
     * Map score to Indonesian level label.
     */
    public static function levelLabel(int $score): string
    {
        return match (true) {
            $score >= 85 => 'KRITIS',
            $score >= 70 => 'TINGGI',
            $score >= 40 => 'SEDANG',
            $score >= 20 => 'RENDAH',
            default => 'AMAN',
        };
    }

    /**
     * Check if risk is dangerous (TINGGI or KRITIS).
     */
    public function isDangerous(): bool
    {
        return in_array($this->level, ['TINGGI', 'KRITIS'], true);
    }
}
