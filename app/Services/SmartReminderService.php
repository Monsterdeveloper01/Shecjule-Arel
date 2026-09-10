<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SmartReminderService
{
    public function __construct(
        protected DeadlineRiskEngine $riskEngine,
        protected FreeTimeService $freeTimeService,
        protected WebPushService $pushService,
    ) {}

    /**
     * Evaluate active tasks and send smart contextual push reminders.
     * Prevents duplicate spam by caching alert signatures.
     */
    public function dispatchSmartReminders(): int
    {
        $candidates = $this->getPendingSmartReminders();
        $sentCount = 0;

        foreach ($candidates as $reminder) {
            $cacheKey = "smart_reminder_task_{$reminder['task']->id}_{$reminder['risk_level']}";

            if (Cache::has($cacheKey)) {
                continue;
            }

            $success = $this->pushService->sendNotificationToAll(
                title: $reminder['title'],
                body: $reminder['body'],
                data: [
                    'url' => '/tasks',
                    'task_id' => $reminder['task']->id,
                    'type' => 'smart_reminder',
                ],
            );

            if ($success > 0) {
                // Anti-spam cooldown: 6 hours before re-alerting for the same state
                Cache::put($cacheKey, true, now()->addHours(6));
                $sentCount++;
            }
        }

        return $sentCount;
    }

    /**
     * Generate context-aware in-app smart reminders for active tasks.
     *
     * @return Collection<int, array{task: Task, title: string, body: string, risk_level: string}>
     */
    public function getPendingSmartReminders(): Collection
    {
        $todayFreeMinutes = $this->freeTimeService->getTotalFreeMinutesForDate(now(), fromNowIfToday: true);
        $todayFreeHours = round($todayFreeMinutes / 60, 1);

        $highRiskTasks = Task::where('status', '!=', 'completed')
            ->whereIn('risk_level', ['KRITIS', 'TINGGI'])
            ->orderByDesc('risk_score')
            ->get();

        $reminders = collect();

        foreach ($highRiskTasks as $task) {
            $remainingHours = round($task->remaining_duration / 60, 1);

            if ($task->isOverdue()) {
                $title = "🚨 Overdue: {$task->title}";
                $body = "Tenggat waktu sudah lewat! Masih ada sisa {$remainingHours} jam kerja.";
            } elseif ($todayFreeMinutes > 0 && $remainingHours > 0) {
                $title = "💡 Rekomendasi Hari Ini: {$task->title}";
                $body = "Risiko {$task->risk_level}. Kamu punya {$todayFreeHours} jam waktu luang hari ini untuk mencicil sisa {$remainingHours} jam.";
            } else {
                $title = "⚠️ Risiko Deadline: {$task->title}";
                $body = "Tingkat risiko {$task->risk_level}. Sisa pengerjaan {$remainingHours} jam butuh perhatian segera!";
            }

            $reminders->push([
                'task' => $task,
                'title' => $title,
                'body' => $body,
                'risk_level' => $task->risk_level ?? 'TINGGI',
            ]);
        }

        return $reminders;
    }
}
