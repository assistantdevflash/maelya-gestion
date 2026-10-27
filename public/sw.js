// ⚠️ Incrémenter cette valeur à chaque déploiement qui modifie la liste
// PRECACHE_ASSETS ou la stratégie de cache : l'activation supprime alors tous
// les anciens caches et les utilisateurs repartent sur des fichiers frais.
const CACHE_VERSION  = 'maelya-v8';
const OFFLINE_URL    = '/offline.html';

// Assets statiques à mettre en cache au premier chargement
const PRECACHE_ASSETS = [
    OFFLINE_URL,
];

// ── Installation ──────────────────────────────────────────────────
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_VERSION).then(cache => {
            // Précache la page hors-ligne. `reload` évite de récupérer une
            // version périmée depuis le cache HTTP.
            return cache.add(new Request(OFFLINE_URL, { cache: 'reload' })).catch(() => {
                // Si offline.html est indisponible, le SW reste fonctionnel
                // (offlineFallback() génère un HTML de secours).
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

    // Ne jamais intercepter le Service Worker lui-même
    if (url.pathname === '/sw.js') return;

    // Pages HTML (navigations) → Network-first, cache de secours, puis offline.
    // ⚠️ C'est LE point critique : si cette branche renvoyait une réponse vide,
    // le navigateur afficherait une page blanche.
    const accept = request.headers.get('Accept') || '';
    if (request.mode === 'navigate' || accept.includes('text/html')) {
        event.respondWith(pageNetworkFirst(request));
        return;
    }

    // Assets statiques (JS, CSS, images, fonts) → Cache-first
    if (isStaticAsset(url.pathname)) {
        event.respondWith(cacheFirst(request));
        return;
    }

    // API / JSON / Livewire → jamais mis en cache (données toujours fraîches)
    if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/livewire/')) {
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

/**
 * Navigation de page : réseau d'abord (contenu frais), puis dernière version
 * connue, puis page hors-ligne. Ne renvoie JAMAIS un corps vide.
 */
async function pageNetworkFirst(request) {
    try {
        const response = await fetch(request);
        if (response && response.ok) {
            const cache = await caches.open(CACHE_VERSION);
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        const cached = await caches.match(request);
        if (cached) return cached;
        return offlineFallback();
    }
}

async function offlineFallback() {
    const cached = await caches.match(OFFLINE_URL);
    if (cached) return cached;

    // Filet de sécurité : HTML minimal, jamais une page blanche.
    return new Response(
        '<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8">' +
        '<meta name="viewport" content="width=device-width,initial-scale=1">' +
        '<title>Hors-ligne — Maëlya Gestion</title></head>' +
        '<body style="font-family:system-ui,-apple-system,sans-serif;padding:2rem;text-align:center;color:#1f2937">' +
        '<h1 style="font-size:1.25rem;margin-bottom:.75rem">Connexion indisponible</h1>' +
        '<p style="color:#6b7280;margin-bottom:1.25rem">Vérifiez votre connexion internet, puis réessayez.</p>' +
        '<button onclick="location.reload()" style="background:#9333ea;color:#fff;border:none;' +
        'padding:.6rem 1.2rem;border-radius:.5rem;font:inherit;font-weight:600;cursor:pointer">Réessayer</button>' +
        '</body></html>',
        { status: 200, headers: { 'Content-Type': 'text/html; charset=utf-8' } }
    );
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
        // Un asset manquant laisse la page utilisable (contrairement à une
        // navigation vide). On renvoie un code explicite plutôt que du vide.
        return new Response('', { status: 504, statusText: 'Hors-ligne' });
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
        if (cached) return cached;
        return new Response('', { status: 504, statusText: 'Hors-ligne' });
    }
}

// ── Notifications Push ──────────────────────────────────────────────────────
self.addEventListener('push', event => {
    let data = { title: 'Maëlya Gestion', body: 'Nouvelle notification', url: '/', icon: '/icons/icon-192.png?v=4' };
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
