<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Services\WebPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    /**
     * Get VAPID Public Key for client subscription.
     */
    public function vapidPublicKey(WebPushService $pushService): JsonResponse
    {
        return response()->json([
            'publicKey' => $pushService->getPublicKey(),
        ]);
    }

    /**
     * Store or update a push subscription from browser.
     */
    public function subscribe(Request $request): JsonResponse
    {
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
            'message' => 'Berhasil berlangganan notifikasi!',
            'subscription_id' => $sub->id,
        ]);
    }

    /**
     * Remove a push subscription.
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
        ]);

        PushSubscription::where('endpoint', $validated['endpoint'])->delete();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil berhenti berlangganan notifikasi.',
        ]);
    }

    /**
     * Send an immediate test notification to all subscribed devices.
     */
    public function sendTest(WebPushService $pushService): JsonResponse
    {
        $count = $pushService->sendNotification(
            '🔔 Tes Notifikasi Schedule Berhasil!',
            'Notifikasi push di HP kamu sudah aktif dan siap mengirimkan pengingat deadline.',
            '/',
            'test-'.time()
        );

        return response()->json([
            'success' => true,
            'sent_count' => $count,
            'message' => $count > 0
                ? "Notifikasi berhasil dikirim ke {$count} perangkat!"
                : 'Belum ada perangkat yang terdaftar atau aktif.',
        ]);
    }

    /**
     * Trigger manual check for deadlines and dispatch notifications.
     */
    public function checkDeadlines(WebPushService $pushService): JsonResponse
    {
        $sentCount = $pushService->checkAndSendDeadlineAlerts();

        return response()->json([
            'success' => true,
            'sent_count' => $sentCount,
            'message' => "Pemeriksaan selesai. {$sentCount} notifikasi terkirim.",
        ]);
    }
}
