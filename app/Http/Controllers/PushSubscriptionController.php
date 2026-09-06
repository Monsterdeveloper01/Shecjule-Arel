<?php

namespace App\Http\Controllers;

use App\Models\CourseSchedule;
use App\Models\PushSubscription;
use App\Services\WebPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class PushSubscriptionController extends Controller
{
    /**
     * Get VAPID Public Key for client subscription.
     */
    public function vapidPublicKey(WebPushService $pushService): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'publicKey' => $pushService->getPublicKey(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store or update a push subscription from browser.
     */
    public function subscribe(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'endpoint' => 'required|string',
                'keys.p256dh' => 'nullable|string',
                'keys.auth' => 'nullable|string',
                'contentEncoding' => 'nullable|string',
            ]);

            $sub = PushSubscription::updateOrCreate(
                ['endpoint' => $validated['endpoint']],
                [
                    'public_key' => $validated['keys']['p256dh'] ?? null,
                    'auth_token' => $validated['keys']['auth'] ?? null,
                    'content_encoding' => $validated['contentEncoding'] ?? 'aesgcm',
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Berhasil mendaftarkan perangkat untuk notifikasi!',
                'subscription_id' => $sub->id,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan subscription: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a push subscription.
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'endpoint' => 'required|string',
            ]);

            PushSubscription::where('endpoint', $validated['endpoint'])->delete();

            return response()->json([
                'success' => true,
                'message' => 'Berhasil berhenti berlangganan notifikasi.',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send an immediate test notification to all subscribed devices.
     */
    public function sendTest(WebPushService $pushService): JsonResponse
    {
        try {
            $count = $pushService->sendNotification(
                '🔔 Tes Notifikasi Schedule Berhasil!',
                'Notifikasi push di HP kamu sudah aktif dan siap mengirimkan pengingat deadline & jadwal kuliah!',
                '/',
                'test-'.time()
            );

            $totalSubscribers = PushSubscription::count();

            if ($totalSubscribers === 0) {
                return response()->json([
                    'success' => true,
                    'sent_count' => 0,
                    'message' => 'Belum ada perangkat yang terdaftar. Klik "Aktifkan Notifikasi di Perangkat Ini" terlebih dahulu ya!',
                ]);
            }

            return response()->json([
                'success' => true,
                'sent_count' => $count,
                'message' => "Notifikasi berhasil dikirim ke {$count} dari {$totalSubscribers} perangkat terdaftar!",
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim notifikasi: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send a test schedule notification for tomorrow or a specific day (e.g. multi-course Monday).
     */
    public function sendScheduleTest(Request $request, WebPushService $pushService): JsonResponse
    {
        try {
            $dayOfWeek = $request->input('day_of_week') ? (int) $request->input('day_of_week') : now()->addDay()->dayOfWeekIso;

            // If selected day has no courses, fallback to the first active day (e.g. Monday)
            $preview = $pushService->formatScheduleAlert($dayOfWeek, 'tomorrow');
            if (! $preview) {
                $firstWithCourse = CourseSchedule::orderBy('day_of_week')->orderBy('start_time')->first();
                if ($firstWithCourse) {
                    $dayOfWeek = $firstWithCourse->day_of_week;
                    $preview = $pushService->formatScheduleAlert($dayOfWeek, 'tomorrow');
                }
            }

            if (! $preview) {
                return response()->json([
                    'success' => false,
                    'message' => 'Belum ada data jadwal kuliah di sistem. Tambahkan jadwal kuliah terlebih dahulu!',
                ], 404);
            }

            $count = $pushService->sendNotification(
                $preview['title'],
                $preview['body'],
                '/schedules',
                'test-schedule-'.time()
            );

            $totalSubscribers = PushSubscription::count();

            return response()->json([
                'success' => true,
                'sent_count' => $count,
                'total_subscribers' => $totalSubscribers,
                'title' => $preview['title'],
                'body' => $preview['body'],
                'message' => $totalSubscribers > 0
                    ? "Berhasil mengirim pengingat jadwal kuliah ({$preview['day_name']}) ke {$count} perangkat!"
                    : "Format Pengingat: '{$preview['title']}' siap! Aktifkan notifikasi perangkat ini untuk menerima push di HP.",
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim notifikasi jadwal: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Trigger manual check for deadlines and dispatch notifications.
     */
    public function checkDeadlines(WebPushService $pushService): JsonResponse
    {
        try {
            $sentCount = $pushService->checkAndSendDeadlineAlerts();

            return response()->json([
                'success' => true,
                'sent_count' => $sentCount,
                'message' => "Pemeriksaan selesai. {$sentCount} notifikasi terkirim.",
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 500);
        }
    }
}
