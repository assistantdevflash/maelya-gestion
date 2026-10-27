{{--
    Mise à jour du Service Worker.

    Objectif : quand une nouvelle version de l'application est déployée,
    l'utilisateur bascule dessus rapidement, sans vider son cache à la main.

    1. Le SW est enregistré avec `updateViaCache: 'none'` → le navigateur
       contourne le cache HTTP pour aller chercher /sw.js.
    2. Vérification d'une nouvelle version : toutes les 30 min, au retour sur
       l'onglet, au retour du réseau et au focus de la fenêtre.
    3. Si une nouvelle version est prête :
         - onglet en arrière-plan → rechargement silencieux ;
         - onglet visible      → bannière discrète « Nouvelle version ».
--}}
<script>
(function () {
    if (!('serviceWorker' in navigator)) return;

    var RELOAD_KEY     = 'pwa-last-reload';
    var CHECK_INTERVAL = 30 * 60 * 1000; // 30 minutes
    var updating       = false;

    // ── Réparation manuelle : /?reset-pwa=1 ──
    // Ouvre cette URL si l'application affiche une page blanche ou reste
    // coincée sur une ancienne version : on désinscrit le SW et on vide
    // tous les caches, puis on recharge proprement.
    if (new URLSearchParams(location.search).get('reset-pwa') === '1') {
        (async function () {
            try {
                var regs = await navigator.serviceWorker.getRegistrations();
                await Promise.all(regs.map(function (r) { return r.unregister(); }));
                var keys = await caches.keys();
                await Promise.all(keys.map(function (k) { return caches.delete(k); }));
            } catch (e) {
                console.warn('[PWA] Réinitialisation partielle :', e);
            }
            location.replace(location.origin + location.pathname);
        })();
        return;
    }

    // ── Rechargement protégé contre les boucles ──
    function reloadPage() {
        try {
            var last = parseInt(sessionStorage.getItem(RELOAD_KEY) || '0', 10);
            if (Date.now() - last < 15000) return; // déjà rechargé à l'instant
            sessionStorage.setItem(RELOAD_KEY, String(Date.now()));
        } catch (e) { /* mode privé : on recharge quand même */ }
        window.location.reload();
    }

    // ── Bannière « nouvelle version disponible » ──
    function showBanner() {
        if (document.getElementById('pwa-update-banner')) return;

        var box = document.createElement('div');
        box.id = 'pwa-update-banner';
        box.setAttribute('role', 'status');
        box.style.cssText =
            'position:fixed;left:1rem;bottom:1rem;z-index:10000;' +
            'display:flex;align-items:center;gap:.75rem;padding:.65rem .75rem .65rem 1rem;' +
            'background:#111827;color:#fff;border-radius:.75rem;' +
            'box-shadow:0 8px 28px rgba(0,0,0,.28);font-family:inherit;font-size:.8125rem;' +
            'max-width:calc(100vw - 2rem);';
        box.innerHTML =
            '<span style="font-weight:600;">Une nouvelle version est disponible</span>' +
            '<button type="button" id="pwa-update-reload" ' +
                'style="background:linear-gradient(135deg,#9333ea,#ec4899);color:#fff;' +
                'border:none;border-radius:.5rem;padding:.4rem .8rem;font:inherit;' +
                'font-weight:600;cursor:pointer;white-space:nowrap;">Recharger</button>';

        document.body.appendChild(box);
        document.getElementById('pwa-update-reload').addEventListener('click', reloadPage);
    }

    // ── Une nouvelle version est prête ──
    function nouvelleVersionPrete() {
        if (updating) return;
        updating = true;

        // Personne ne regarde l'écran → on recharge sans rien afficher.
        if (document.visibilityState === 'hidden') {
            reloadPage();
            return;
        }
        showBanner();
    }

    navigator.serviceWorker.register('/sw.js', {
        scope: '/',
        // Indispensable : sans ça le navigateur peut servir un /sw.js
        // vieux de plusieurs mois (Cache-Control immutable côté serveur).
        updateViaCache: 'none',
    }).then(function (reg) {
        // Une mise à jour attendait déjà au chargement de la page
        if (reg.waiting && navigator.serviceWorker.controller) {
            nouvelleVersionPrete();
        }

        reg.addEventListener('updatefound', function () {
            var nouveau = reg.installing;
            if (!nouveau) return;

            nouveau.addEventListener('statechange', function () {
                // « installed » + un contrôleur existant = mise à jour,
                // pas une première installation.
                if (nouveau.state === 'installed' && navigator.serviceWorker.controller) {
                    nouvelleVersionPrete();
                }
            });
        });

        // Vérifications régulières
        setInterval(function () { reg.update().catch(function () {}); }, CHECK_INTERVAL);
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') reg.update().catch(function () {});
        });
        window.addEventListener('online', function () { reg.update().catch(function () {}); });
        window.addEventListener('focus',  function () { reg.update().catch(function () {}); });
    }).catch(function (err) {
        console.warn('[PWA] Échec enregistrement SW :', err);
    });

    // Le nouveau SW prend le contrôle alors que la page tourne encore sur
    // l'ancien code → on recharge (jamais lors de la 1re installation).
    navigator.serviceWorker.addEventListener('controllerchange', function () {
        if (updating) reloadPage();
    });
})();
</script>
