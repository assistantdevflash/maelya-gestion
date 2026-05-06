const CACHE_VERSION = 'maelya-v1';

// Assets statiques à mettre en cache au premier chargement
const PRECACHE_ASSETS = [
    '/offline.html',
];

// ── Installation ────────────────────────────────────────────────────────────
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_VERSION).then(cache => {
            // On précache uniquement la page offline
            return cache.addAll(PRECACHE_ASSETS).catch(() => {
                // Si offline.html n'existe pas encore, on ignore
            });
        }).then(() => self.skipWaiting())
    );
});

// ── Activation (nettoyage anciens caches) ───────────────────────────────────
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys
                    .filter(key => key !== CACHE_VERSION)
                    .map(key => caches.delete(key))
            )
        ).then(() => self.clients.claim())
    );
});

// ── Stratégie de fetch ──────────────────────────────────────────────────────
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    // Ignorer les requêtes non-GET et les autres origines
    if (request.method !== 'GET') return;
    if (url.origin !== location.origin) return;

    // Assets statiques (JS, CSS, images, fonts) → Cache-first
    if (isStaticAsset(url.pathname)) {
        event.respondWith(cacheFirst(request));
        return;
    }

    // Pages HTML (dashboard, etc.) → Network-first avec fallback offline
    if (request.headers.get('Accept')?.includes('text/html')) {
        event.respondWith(networkFirstWithOfflineFallback(request));
        return;
    }

    // API / JSON → Network-only (données toujours fraîches)
    if (url.pathname.startsWith('/api/')) {
        return; // pas d'interception
    }

    // Reste → Network-first
    event.respondWith(networkFirst(request));
});

// ── Helpers ─────────────────────────────────────────────────────────────────
function isStaticAsset(pathname) {
    return pathname.startsWith('/build/') ||
           pathname.startsWith('/icons/') ||
           /\.(png|jpg|jpeg|svg|ico|woff2?|ttf)$/i.test(pathname);
}

async function cacheFirst(request) {
    const cached = await caches.match(request);
    if (cached) return cached;

    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_VERSION);
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        return new Response('', { status: 503 });
    }
}

async function networkFirst(request) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_VERSION);
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        const cached = await caches.match(request);
        return cached ?? new Response('', { status: 503 });
    }
}

async function networkFirstWithOfflineFallback(request) {
    try {
        const response = await fetch(request);
        return response;
    } catch {
        const cached = await caches.match(request);
        if (cached) return cached;

        const offline = await caches.match('/offline.html');
        return offline ?? new Response('<h1>Hors-ligne</h1>', {
            headers: { 'Content-Type': 'text/html' },
        });
    }
}

// ── Notifications Push ──────────────────────────────────────────────────────
self.addEventListener('push', event => {
    let data = { title: 'Maëlya Gestion', body: 'Nouvelle notification', url: '/', icon: '/icons/icon-192.png' };
    try {
        if (event.data) data = { ...data, ...event.data.json() };
    } catch (e) {}

    event.waitUntil(
        self.registration.showNotification(data.title, {
            body:    data.body,
            icon:    data.icon,
            badge:   data.badge || '/icons/badge-72.svg',
            data:    { url: data.url },
            vibrate: [100, 50, 100],
            tag:     'maelya-' + Date.now(),
        })
    );
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    const rawUrl = event.notification.data?.url || '/';
    // Résoudre les chemins relatifs en URL absolue (nécessaire pour clients.openWindow)
    const url = rawUrl.startsWith('http') ? rawUrl : (self.location.origin + rawUrl);
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clientList => {
            for (const client of clientList) {
                if (client.url.startsWith(self.location.origin) && 'focus' in client) {
                    client.navigate(url);
                    return client.focus();
                }
            }
            if (clients.openWindow) return clients.openWindow(url);
        })
    );
});
