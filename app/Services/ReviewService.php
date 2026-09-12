<?php

namespace App\Services;

use App\Models\CourseSchedule;
use App\Models\FocusSession;
use App\Models\Task;
use Carbon\Carbon;

class ReviewService
{
    public function __construct(
        protected HabitService $habitService,
        protected FocusSessionService $focusService
    ) {}

    /**
     * Build the Weekly Review scorecard and AI reflection.
     *
     * @return array<string, mixed>
     */
    public function getWeeklyReview(?Carbon $referenceDate = null): array
    {
        $ref = $referenceDate ? $referenceDate->copy() : now();
        $startOfWeek = $ref->copy()->startOfWeek();
        $endOfWeek = $ref->copy()->endOfWeek();

        // Tasks in week
        $tasksDueThisWeek = Task::whereBetween('deadline', [$startOfWeek, $endOfWeek])->count();
        $tasksCompletedThisWeek = Task::where('status', 'completed')
            ->whereBetween('updated_at', [$startOfWeek, $endOfWeek])
            ->count();

        // Focus minutes
        $focusMinutes = (int) FocusSession::whereBetween('started_at', [$startOfWeek, $endOfWeek])
            ->where('completed', true)
            ->sum('duration_minutes');
        $focusHours = round($focusMinutes / 60, 1);

        // Habit consistency
        $habitConsistency = $this->habitService->getConsistencyRate(7);

        // SKS & classes
        $totalSks = (int) CourseSchedule::sum('sks');

        // Evaluation badge
        $score = 0;
        if ($tasksDueThisWeek > 0) {
            $score += ($tasksCompletedThisWeek / $tasksDueThisWeek) * 40;
        } else {
            $score += 35;
        }
        $score += min(30, ($focusMinutes / 120) * 30); // 2 hours target
        $score += ($habitConsistency / 100) * 30;

        $badge = 'Stabil & Seimbang ⚖️';
        if ($score >= 75) {
            $badge = 'Sangat Produktif 🚀';
        } elseif ($score < 50) {
            $badge = 'Perlu Peningkatan 📈';
        }

        // Reflection narrative
        $reflection = $this->generateWeeklyReflection(
            $tasksCompletedThisWeek,
            $tasksDueThisWeek,
            $focusHours,
            $habitConsistency
        );

        return [
            'period_label' => $startOfWeek->translatedFormat('d M').' – '.$endOfWeek->translatedFormat('d M Y'),
            'tasks_due' => $tasksDueThisWeek,
            'tasks_completed' => $tasksCompletedThisWeek,
            'focus_hours' => $focusHours,
            'habit_consistency' => $habitConsistency,
            'total_sks' => $totalSks,
            'score' => round($score, 1),
            'badge' => $badge,
            'reflection' => $reflection,
        ];
    }

    /**
     * Build the Monthly Review retrospective.
     *
     * @return array<string, mixed>
     */
    public function getMonthlyReview(?Carbon $referenceDate = null): array
    {
        $ref = $referenceDate ? $referenceDate->copy() : now();
        $startOfMonth = $ref->copy()->startOfMonth();
        $endOfMonth = $ref->copy()->endOfMonth();

        $completedCount = Task::where('status', 'completed')
            ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
            ->count();

        $focusMinutes = (int) FocusSession::whereBetween('started_at', [$startOfMonth, $endOfMonth])
            ->where('completed', true)
            ->sum('duration_minutes');
        $focusHours = round($focusMinutes / 60, 1);

        $consistency = $this->habitService->getConsistencyRate(30);

        return [
            'month_label' => $ref->translatedFormat('F Y'),
            'completed_tasks' => $completedCount,
            'focus_hours' => $focusHours,
            'consistency' => $consistency,
            'recap' => "Sepanjang bulan {$ref->translatedFormat('F')}, kamu berhasil menyelesaikan {$completedCount} tugas dan menginvestasikan {$focusHours} jam fokus murni.",
        ];
    }

    /**
     * Generate weekly reflection text.
     */
    protected function generateWeeklyReflection(int $completed, int $due, float $focusHours, float $consistency): string
    {
        $parts = [];

        if ($completed >= $due && $due > 0) {
            $parts[] = "Luar biasa! Kamu menuntaskan seluruh ({$completed}/{$due}) tugas yang jatuh tempo pekan ini.";
        } elseif ($completed > 0) {
            $parts[] = "Kamu berhasil menyelesaikan {$completed} tugas pada minggu ini.";
        } else {
            $parts[] = 'Minggu ini cukup santai atau ada beberapa tugas yang tertunda.';
        }

        if ($focusHours >= 3.0) {
            $parts[] = "Catatan waktu fokus sangat solid dengan total {$focusHours} jam pengerjaan mendalam.";
        } elseif ($focusHours > 0) {
            $parts[] = "Kamu telah mengumpulkan {$focusHours} jam sesi fokus.";
        }

        if ($consistency >= 75) {
            $parts[] = "Disiplin rutinitas dan olahraga sangat baik (kepatuhan {$consistency}%). Pertahankan momentum ini!";
        } else {
            $parts[] = "Kepatuhan rutinitas berada di angka {$consistency}%. Luangkan waktu untuk istirahat dan atur kembali ritme mingguanmu.";
        }

        return implode(' ', $parts);
    }
}
