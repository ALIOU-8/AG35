/*
 * Service worker de l'application AG35.
 * - Pages : toujours le réseau (contenu privé jamais mis en cache), page « hors ligne » en secours.
 * - Fichiers compilés /build/* : nom versionné, donc mis en cache durablement.
 */
const VERSION = 'ag35-v3';
const OFFLINE_URL = '/hors-ligne';
const PRECACHE = [OFFLINE_URL, '/icons/icon-192.png', '/icons/favicon-64.png', '/manifest.webmanifest'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(VERSION)
            .then((cache) => cache.addAll(PRECACHE))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== VERSION).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

// Notification push (application fermée ou en arrière-plan).
self.addEventListener('push', (event) => {
    const payload = event.data ? event.data.json() : {};

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windows) => {
            // Application visible : le bandeau et le son s'affichent déjà dans la page.
            if (windows.some((client) => client.visibilityState === 'visible')) {
                return undefined;
            }

            return self.registration.showNotification(payload.title ?? 'AG35', {
                body: payload.body,
                icon: payload.icon ?? '/icons/icon-192.png',
                badge: payload.badge ?? '/icons/icon-192.png',
                tag: payload.tag,
                renotify: Boolean(payload.tag),
                data: payload.data ?? {},
            });
        }),
    );
});

// Clic sur une notification : on réutilise l'onglet ouvert, sinon on en ouvre un.
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = event.notification.data?.url ?? '/messages';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windows) => {
            const existing = windows.find((client) => new URL(client.url).origin === self.location.origin);

            if (existing) {
                return existing.focus().then((client) => client.navigate(url)).catch(() => self.clients.openWindow(url));
            }

            return self.clients.openWindow(url);
        }),
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
        return;
    }

    if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/')) {
        event.respondWith(
            caches.match(request).then((cached) => cached ?? fetch(request).then((response) => {
                if (response.ok) {
                    const copy = response.clone();
                    caches.open(VERSION).then((cache) => cache.put(request, copy));
                }
                return response;
            })),
        );
    }
});
