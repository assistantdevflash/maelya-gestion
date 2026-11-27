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

    // ── Rechargement ──
    // `force` = clic explicite de l'utilisateur : on ne l'ignore jamais.
    // Sans ça, un rechargement automatique survenu moins de 15 s plus tôt
    // faisait que le bouton ne produisait STRICTEMENT rien (ni rechargement,
    // ni retour visuel) et la bannière restait affichée.
    function reloadPage(force) {
        if (!force) {
            try {
                var last = parseInt(sessionStorage.getItem(RELOAD_KEY) || '0', 10);
                if (Date.now() - last < 15000) return; // déjà rechargé à l'instant
            } catch (e) { /* mode privé : on recharge quand même */ }
        }
        try { sessionStorage.setItem(RELOAD_KEY, String(Date.now())); } catch (e) {}
        window.location.reload();
    }

    // ── Bannière « nouvelle version disponible » ──
    function showBanner() {
        if (document.getElementById('pwa-update-banner')) return;

        var box = document.createElement('div');
        box.id = 'pwa-update-banner';
        box.className = 'pwa-update';
        box.setAttribute('role', 'status');
        box.innerHTML =
            '<span class="pwa-update__texte">Une nouvelle version est disponible</span>' +
            '<button type="button" id="pwa-update-download" class="pwa-update__bouton">Télécharger</button>';

        document.body.appendChild(box);
        document.getElementById('pwa-update-download')
            .addEventListener('click', telechargerNouvelleVersion);
    }

    // ── Clic sur « Télécharger » ──
    // On réveille le Service Worker en attente, on lui laisse prendre la main,
    // puis on recharge. Un filet de sécurité recharge quand même si le worker
    // ne répond pas : le bouton ne doit jamais rester sans effet.
    function telechargerNouvelleVersion() {
        var bouton = document.getElementById('pwa-update-download');
        if (bouton) {
            bouton.disabled = true;
            bouton.textContent = 'Téléchargement…';
        }

        var dejaRelance = false;
        function relancer() {
            if (dejaRelance) return;
            dejaRelance = true;
            reloadPage(true);
        }

        navigator.serviceWorker.addEventListener('controllerchange', relancer, { once: true });

        navigator.serviceWorker.getRegistration().then(function (reg) {
            if (!reg) return;
            [reg.waiting, reg.installing].forEach(function (worker) {
                if (worker) {
                    try { worker.postMessage({ type: 'SKIP_WAITING' }); } catch (e) {}
                }
            });
            // Le worker est déjà actif : plus rien à activer, on recharge.
            if (!reg.waiting && !reg.installing) relancer();
        }).catch(function () {});

        // Filet de sécurité : ne jamais laisser l'utilisateur sans effet.
        setTimeout(relancer, 1500);
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
