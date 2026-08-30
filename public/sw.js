// ===================================
// SCHEDULE — Service Worker (PWA & Push)
// ===================================

const CACHE_NAME = 'schedule-v1';
const STATIC_ASSETS = [
    '/',
    '/manifest.json',
];

// Install Event
self.addEventListener('install', (event) => {
    self.skipWaiting();
});

// Activate Event
self.addEventListener('activate', (event) => {
    event.waitUntil(clients.claim());
});

// Push Event: Received Web Push Notification from Server
self.addEventListener('push', (event) => {
    let data = {
        title: 'Schedule Reminder',
        body: 'Kamu punya deadline atau agenda kegiatan hari ini!',
        icon: '/favicon.ico',
        badge: '/favicon.ico',
        url: '/',
    };

    if (event.data) {
        try {
            data = Object.assign(data, event.data.json());
        } catch (e) {
            data.body = event.data.text();
        }
    }

    const options = {
        body: data.body,
        icon: data.icon || '/favicon.ico',
        badge: data.badge || '/favicon.ico',
        vibrate: [200, 100, 200, 100, 200],
        data: {
            url: data.url || '/',
        },
        actions: [
            { action: 'open', title: 'Buka Jadwal' },
            { action: 'close', title: 'Tutup' }
        ],
        tag: data.tag || 'schedule-reminder-' + Date.now(),
        renotify: true,
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Notification Click Event
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    if (event.action === 'close') {
        return;
    }

    const targetUrl = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if (client.url.includes(self.location.origin) && 'focus' in client) {
                    client.navigate(targetUrl);
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
