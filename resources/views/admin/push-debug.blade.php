@extends('layouts.admin')

@section('title', 'Debug Push Notifications')

@section('content')
<div class="max-w-3xl mx-auto py-8 px-4 space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">🔔 Debug Push Notifications</h1>

    {{-- ── Serveur ──────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl shadow p-6 space-y-3">
        <h2 class="font-semibold text-gray-700 text-lg">1. Config serveur</h2>
        <ul class="text-sm space-y-2">
            <li class="flex items-center gap-2">
                @if($vapidPublic)
                    <span class="text-green-600 font-bold">✓</span>
                    <span class="text-green-700">VAPID_PUBLIC_KEY définie
                        <code class="text-xs bg-gray-100 px-1 rounded break-all">{{ Str::limit($vapidPublic, 40) }}</code>
                    </span>
                @else
                    <span class="text-red-600 font-bold">✗</span>
                    <span class="text-red-600">VAPID_PUBLIC_KEY <strong>manquante</strong> dans .env</span>
                @endif
            </li>
            <li class="flex items-center gap-2">
                @if($vapidPrivate)
                    <span class="text-green-600 font-bold">✓</span>
                    <span class="text-green-700">VAPID_PRIVATE_KEY définie</span>
                @else
                    <span class="text-red-600 font-bold">✗</span>
                    <span class="text-red-600">VAPID_PRIVATE_KEY <strong>manquante</strong> dans .env</span>
                @endif
            </li>
            <li class="flex items-center gap-2">
                @if($webpushInstalled)
                    <span class="text-green-600 font-bold">✓</span>
                    <span class="text-green-700">minishlink/web-push installé</span>
                @else
                    <span class="text-red-600 font-bold">✗</span>
                    <span class="text-red-600">minishlink/web-push <strong>non trouvé</strong></span>
                @endif
            </li>
            <li class="flex items-center gap-2">
                <span class="text-blue-600 font-bold">ℹ</span>
                <span class="text-gray-600">Abonnements en base pour votre compte : <strong>{{ $subCount }}</strong></span>
            </li>
        </ul>
    </div>

    {{-- ── Navigateur ───────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl shadow p-6 space-y-4">
        <h2 class="font-semibold text-gray-700 text-lg">2. Capacités du navigateur</h2>
        <div id="browser-checks" class="text-sm space-y-2 text-gray-500 italic">Cliquez sur "Tester" pour lancer l'analyse…</div>
    </div>

    {{-- ── Test manuel ──────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl shadow p-6 space-y-4">
        <h2 class="font-semibold text-gray-700 text-lg">3. Test complet étape par étape</h2>
        <button id="btn-test" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition">
            ▶ Lancer le test
        </button>
        <div id="steps" class="text-sm space-y-2 font-mono"></div>
    </div>
    {{-- ── Notification de test ─────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl shadow p-6 space-y-3">
        <h2 class="font-semibold text-gray-700 text-lg">4. Envoyer une notification de test</h2>
        <p class="text-sm text-gray-500">Envoie une vraie notification push à tous vos abonnements depuis le serveur.</p>
        <button id="btn-send-test" class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition">
            📨 Envoyer une notification test
        </button>
        <div id="send-result" class="text-sm font-mono"></div>
    </div>
    {{-- ── Tous les abonnements ─────────────────────────────────────────── --}}
    @if($allSubs->count())
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="font-semibold text-gray-700 text-lg mb-3">4. Abonnements enregistrés (tous utilisateurs)</h2>
        <table class="text-xs w-full border-collapse">
            <thead><tr class="bg-gray-50 text-left">
                <th class="p-2 border">User</th>
                <th class="p-2 border">User Agent</th>
                <th class="p-2 border">Endpoint (début)</th>
                <th class="p-2 border">Créé</th>
            </tr></thead>
            <tbody>
            @foreach($allSubs as $sub)
            <tr class="border-t">
                <td class="p-2 border">{{ $sub->user?->name ?? $sub->user_id }}</td>
                <td class="p-2 border">{{ Str::limit($sub->user_agent ?? '—', 30) }}</td>
                <td class="p-2 border break-all">{{ Str::limit($sub->endpoint, 50) }}</td>
                <td class="p-2 border whitespace-nowrap">{{ $sub->created_at?->diffForHumans() }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-sm text-yellow-800">
        <strong>Aucun abonnement en base.</strong> Soit la table est vide, soit le POST /push/subscribe échoue.
    </div>
    @endif
</div>

@push('scripts')
<script>
const VAPID_KEY   = @json(config('app.vapid_public_key'));
const SUBSCRIBE_URL = '{{ route("push.subscribe") }}';
const CSRF        = '{{ csrf_token() }}';

function log(text, ok = null) {
    const el = document.createElement('div');
    const icon = ok === true ? '✅' : ok === false ? '❌' : '🔹';
    el.className = 'py-1 border-b border-gray-100';
    el.innerHTML = `<span>${icon} ${text}</span>`;
    document.getElementById('steps').appendChild(el);
}

// ── Vérifications immédiates ─────────────────────────────────────────────────
(function checkBrowser() {
    const div = document.getElementById('browser-checks');
    div.innerHTML = '';
    const rows = [
        ['ServiceWorker dans navigator', 'serviceWorker' in navigator],
        ['PushManager dans window', 'PushManager' in window],
        ['Notification API disponible', 'Notification' in window],
        ['Permission actuelle : ' + (('Notification' in window) ? Notification.permission : 'N/A'), null],
        ['HTTPS ou localhost', location.protocol === 'https:' || location.hostname === 'localhost'],
    ];
    rows.forEach(([label, ok]) => {
        const icon = ok === true ? '<span class="text-green-600 font-bold">✓</span>' :
                     ok === false ? '<span class="text-red-600 font-bold">✗</span>' :
                     '<span class="text-blue-500">ℹ</span>';
        div.innerHTML += `<div class="flex items-center gap-2 py-1 border-b border-gray-50">${icon} <span>${label}</span></div>`;
    });
})();

// ── Test étape par étape ─────────────────────────────────────────────────────
document.getElementById('btn-test').addEventListener('click', async () => {
    document.getElementById('steps').innerHTML = '';
    document.getElementById('btn-test').disabled = true;

    try {
        // Étape 1 : APIs
        if (!('serviceWorker' in navigator)) { log('ServiceWorker non supporté', false); return; }
        if (!('PushManager' in window))      { log('PushManager non supporté (navigateur trop vieux ?)', false); return; }
        log('APIs ServiceWorker + PushManager présentes', true);

        // Étape 2 : Clé VAPID
        if (!VAPID_KEY) { log('VAPID_PUBLIC_KEY vide côté serveur', false); return; }
        log('Clé VAPID côté serveur : ' + VAPID_KEY.substring(0, 20) + '…', true);

        // Étape 3 : Service Worker
        log('Enregistrement du Service Worker…');
        let reg;
        try {
            reg = await navigator.serviceWorker.register('/sw.js');
            log('SW enregistré, scope = ' + reg.scope, true);
        } catch(e) {
            log('Échec enregistrement SW : ' + e.message, false); return;
        }

        // Étape 4 : SW ready
        log('Attente navigator.serviceWorker.ready…');
        try {
            reg = await Promise.race([
                navigator.serviceWorker.ready,
                new Promise((_, rej) => setTimeout(() => rej(new Error('Timeout 10s')), 10000))
            ]);
            log('SW prêt, état = ' + (reg.active?.state ?? 'inconnu'), true);
        } catch(e) {
            log('serviceWorker.ready timeout / erreur : ' + e.message, false); return;
        }

        // Étape 5 : Permission
        log('Permission actuelle : ' + Notification.permission);
        if (Notification.permission === 'denied') {
            log('Permission REFUSÉE dans le navigateur — allez dans Paramètres du site pour réinitialiser', false);
            return;
        }
        if (Notification.permission !== 'granted') {
            log('Demande de permission au navigateur…');
            const perm = await Notification.requestPermission();
            if (perm !== 'granted') { log('Permission refusée par l\'utilisateur : ' + perm, false); return; }
        }
        log('Permission accordée', true);

        // Étape 6 : Abonnement push
        log('Abonnement pushManager…');
        let sub;
        try {
            const existing = await reg.pushManager.getSubscription();
            if (existing) {
                log('Abonnement existant trouvé, réutilisation', true);
                sub = existing;
            } else {
                function urlBase64ToUint8Array(b) {
                    const pad = '='.repeat((4 - b.length % 4) % 4);
                    const raw = atob((b + pad).replace(/-/g, '+').replace(/_/g, '/'));
                    return Uint8Array.from([...raw].map(c => c.charCodeAt(0)));
                }
                sub = await reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(VAPID_KEY),
                });
                log('Nouvel abonnement créé', true);
            }
            log('Endpoint : ' + sub.endpoint.substring(0, 60) + '…', true);
        } catch(e) {
            log('Échec création abonnement : ' + e.message, false); return;
        }

        // Étape 7 : Envoi au serveur
        log('Envoi au serveur (' + SUBSCRIBE_URL + ')…');
        try {
            const key   = sub.getKey('p256dh');
            const token = sub.getKey('auth');
            const resp  = await fetch(SUBSCRIBE_URL, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({
                    endpoint:   sub.endpoint,
                    public_key: key   ? btoa(String.fromCharCode(...new Uint8Array(key)))   : null,
                    auth_token: token ? btoa(String.fromCharCode(...new Uint8Array(token))) : null,
                }),
            });
            const text = await resp.text();
            if (resp.ok) {
                log('Serveur a répondu ' + resp.status + ' — abonnement sauvegardé ✓', true);
            } else {
                log('Serveur a répondu ' + resp.status + ' : ' + text.substring(0, 200), false);
            }
        } catch(e) {
            log('Erreur fetch vers serveur : ' + e.message, false);
        }

        log('─── Test terminé. Rechargez la page pour voir l\'abonnement en base. ───');
    } finally {
        document.getElementById('btn-test').disabled = false;
    }
});

// ── Envoi test depuis serveur ────────────────────────────────────────────────
document.getElementById('btn-send-test').addEventListener('click', async () => {
    const btn = document.getElementById('btn-send-test');
    const res = document.getElementById('send-result');
    btn.disabled = true;
    res.innerHTML = '⏳ Envoi en cours…';
    try {
        const resp = await fetch('{{ route("admin.push.debug.test") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        });
        const data = await resp.json();
        res.innerHTML = resp.ok
            ? `<span class="text-green-600">✅ ${data.message}</span>`
            : `<span class="text-red-600">❌ ${data.message}</span>`;
    } catch(e) {
        res.innerHTML = `<span class="text-red-600">❌ Erreur réseau : ${e.message}</span>`;
    } finally {
        btn.disabled = false;
    }
});
</script>
@endpush
@endsection
