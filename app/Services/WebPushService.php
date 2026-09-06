<?php

namespace App\Services;

use App\Models\CourseSchedule;
use App\Models\Event;
use App\Models\PushSubscription;
use App\Models\Task;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    /**
     * Get VAPID public key.
     */
    public function getPublicKey(): string
    {
        return config('webpush.vapid.public_key');
    }

    /**
     * Create WebPush instance.
     */
    protected function getWebPushInstance(): WebPush
    {
        $auth = [
            'VAPID' => [
                'subject' => config('webpush.vapid.subject'),
                'publicKey' => config('webpush.vapid.public_key'),
                'privateKey' => config('webpush.vapid.private_key'),
            ],
        ];

        return new WebPush($auth);
    }

    /**
     * Send push notification to all active subscribers.
     */
    public function sendNotification(string $title, string $body, ?string $url = '/', ?string $tag = null): int
    {
        $subscriptions = PushSubscription::all();

        if ($subscriptions->isEmpty()) {
            return 0;
        }

        $webPush = $this->getWebPushInstance();

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url ?? '/',
            'tag' => $tag ?? 'schedule-alert-'.time(),
            'icon' => '/favicon.ico',
            'badge' => '/favicon.ico',
        ]);

        foreach ($subscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub->endpoint,
                'publicKey' => $sub->public_key,
                'authToken' => $sub->auth_token,
                'contentEncoding' => $sub->content_encoding ?? 'aesgcm',
            ]);

            $webPush->queueNotification($subscription, $payload);
        }

        $sentCount = 0;
        $expiredIds = [];

        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();

            if ($report->isSuccess()) {
                $sentCount++;
            } else {
                // Subscription is expired / unregistered
                if ($report->isSubscriptionExpired()) {
                    $matched = $subscriptions->firstWhere('endpoint', $endpoint);
                    if ($matched) {
                        $expiredIds[] = $matched->id;
                    }
                }
            }
        }

        if (! empty($expiredIds)) {
            PushSubscription::whereIn('id', $expiredIds)->delete();
        }

        return $sentCount;
    }

    /**
     * Build formatted alert data for a specific day's course schedule.
     *
     * @return array{day_of_week: int, day_name: string, count: int, total_sks: int, title: string, body: string}|null
     */
    public function formatScheduleAlert(int $dayOfWeek, string $context = 'tomorrow'): ?array
    {
        $courses = CourseSchedule::where('day_of_week', $dayOfWeek)
            ->orderBy('start_time')
            ->get();

        if ($courses->isEmpty()) {
            return null;
        }

        $dayName = CourseSchedule::DAYS[$dayOfWeek] ?? 'Hari Ini';
        $count = $courses->count();
        $totalSks = (int) $courses->sum('sks');

        $lines = [];
        foreach ($courses as $idx => $course) {
            $num = $idx + 1;
            $room = $course->room ? " [📍 {$course->room}]" : '';
            $mode = $course->delivery_mode === 'online' ? ' 🌐 Online' : ($course->delivery_mode === 'hybrid' ? ' 🔀 Hybrid' : '');
            $lines[] = "{$num}. {$course->course_name} ({$course->start_time_formatted} - {$course->end_time_formatted} WIB){$room}{$mode}";
        }

        if ($context === 'tomorrow') {
            $title = "📚 Pengingat Kuliah Besok: {$dayName} ({$count} Matkul)";
            $body = implode("\n", $lines);
        } else {
            $title = "☀️ Jadwal Kuliah Hari Ini: {$dayName} ({$count} Matkul)";
            $body = implode("\n", $lines);
        }

        return [
            'day_of_week' => $dayOfWeek,
            'day_name' => $dayName,
            'count' => $count,
            'total_sks' => $totalSks,
            'title' => $title,
            'body' => $body,
        ];
    }

    /**
     * Send push notification alert for tomorrow's course schedule.
     * If multiple courses exist on that day, lists all courses with their respective hours.
     */
    public function checkAndSendTomorrowScheduleAlert(?int $targetDay = null): int
    {
        $dayOfWeek = $targetDay ?? now()->addDay()->dayOfWeekIso;
        $alertData = $this->formatScheduleAlert($dayOfWeek, 'tomorrow');

        if (! $alertData) {
            return 0;
        }

        return $this->sendNotification(
            $alertData['title'],
            $alertData['body'],
            '/schedules',
            'schedule-tomorrow-'.$dayOfWeek.'-'.date('Ymd')
        );
    }

    /**
     * Send push notification alert for today's course schedule.
     */
    public function checkAndSendTodayScheduleAlert(): int
    {
        $today = now()->dayOfWeekIso;
        $alertData = $this->formatScheduleAlert($today, 'today');

        if (! $alertData) {
            return 0;
        }

        return $this->sendNotification(
            $alertData['title'],
            $alertData['body'],
            '/schedules',
            'schedule-today-'.date('Ymd')
        );
    }

    /**
     * Check tasks, events, and course schedules, sending alerts if any.
     */
    public function checkAndSendDeadlineAlerts(): int
    {
        $today = now()->toDateString();
        $tasksToday = Task::forDate($today)->where('status', '!=', 'completed')->get();
        $overdueTasks = Task::overdue()->get();
        $eventsToday = Event::forDate($today)->get();

        $totalSent = 0;

        // 1. Course Schedule Alerts (Tomorrow & Today)
        $totalSent += $this->checkAndSendTomorrowScheduleAlert();

        // 2. Tasks Today
        if ($tasksToday->isNotEmpty()) {
            $count = $tasksToday->count();
            $taskNames = $tasksToday->take(2)->pluck('title')->implode(', ');
            $more = $count > 2 ? ' +'.($count - 2).' lainnya' : '';

            $totalSent += $this->sendNotification(
                "⏰ {$count} Tugas Deadline Hari Ini!",
                "{$taskNames}{$more}. Jangan lupa diselesaikan ya!",
                '/tasks',
                'tasks-today-'.date('Ymd')
            );
        }

        // 3. Overdue Tasks
        if ($overdueTasks->isNotEmpty()) {
            $count = $overdueTasks->count();
            $totalSent += $this->sendNotification(
                "🚨 {$count} Tugas Lewat Deadline (Overdue)!",
                'Ada tugas yang sudah melewati batas waktu dan belum selesai.',
                '/tasks',
                'tasks-overdue-'.date('Ymd')
            );
        }

        // 4. Events Today
        if ($eventsToday->isNotEmpty()) {
            $count = $eventsToday->count();
            $firstEvent = $eventsToday->first();
            $totalSent += $this->sendNotification(
                "🗓️ Jadwal Hari Ini: {$firstEvent->title}",
                'Mulai jam '.$firstEvent->start_date->format('H:i').($firstEvent->location ? ' di '.$firstEvent->location : ''),
                '/events',
                'events-today-'.date('Ymd')
            );
        }

        return $totalSent;
    }
}
