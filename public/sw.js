// Service worker de AgendaGo: red primero con caché de reserva
// y compatibilidad con notificaciones push.
self.addEventListener('install', (event) => self.skipWaiting());
self.addEventListener('activate', (event) => self.clients.claim());

self.addEventListener('fetch', (event) => {
    event.respondWith(
        fetch(event.request).catch(() => caches.match(event.request))
    );
});

self.addEventListener('push', (event) => {
    const payload = event.data ? event.data.json() : {};
    event.waitUntil(
        self.registration.showNotification(payload.title || 'AgendaGo', {
            body: payload.body || '',
        })
    );
});
