<?php

namespace App\Services;

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
     * Check tasks and events, sending deadline alerts if any.
     */
    public function checkAndSendDeadlineAlerts(): int
    {
        $today = now()->toDateString();
        $tasksToday = Task::forDate($today)->where('status', '!=', 'completed')->get();
        $overdueTasks = Task::overdue()->get();
        $eventsToday = Event::forDate($today)->get();

        $totalSent = 0;

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

        if ($overdueTasks->isNotEmpty()) {
            $count = $overdueTasks->count();
            $totalSent += $this->sendNotification(
                "🚨 {$count} Tugas Lewat Deadline (Overdue)!",
                'Ada tugas yang sudah melewati batas waktu dan belum selesai.',
                '/tasks',
                'tasks-overdue-'.date('Ymd')
            );
        }

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
