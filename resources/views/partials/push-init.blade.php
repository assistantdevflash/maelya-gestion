@auth
<script>
(function () {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;

    const VAPID_PUBLIC_KEY = '{{ config("app.vapid_public_key") }}';
    const SUBSCRIBE_URL    = '{{ route("push.subscribe") }}';
    const CSRF_TOKEN       = '{{ csrf_token() }}';

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64  = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        return Uint8Array.from([...rawData].map(c => c.charCodeAt(0)));
    }

    async function subscribePush() {
        try {
            const reg   = await navigator.serviceWorker.ready;
            const existing = await reg.pushManager.getSubscription();
            if (existing) {
                await sendSubscriptionToServer(existing);
                return;
            }

            const permission = await Notification.requestPermission();
            if (permission !== 'granted') return;

            const sub = await reg.pushManager.subscribe({
                userVisibleOnly:      true,
                applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY),
            });

            await sendSubscriptionToServer(sub);
        } catch (e) {
            console.warn('[Push] Abonnement échoué :', e);
        }
    }

    async function sendSubscriptionToServer(sub) {
        const key   = sub.getKey('p256dh');
        const token = sub.getKey('auth');
        await fetch(SUBSCRIBE_URL, {
            method:  'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
            body: JSON.stringify({
                endpoint:   sub.endpoint,
                public_key: key   ? btoa(String.fromCharCode(...new Uint8Array(key)))   : null,
                auth_token: token ? btoa(String.fromCharCode(...new Uint8Array(token))) : null,
            }),
        });
    }

    // Lancer l'abonnement au chargement (après que le SW soit prêt)
    navigator.serviceWorker.ready.then(() => {
        // Délai de 3s pour ne pas bloquer le rendu
        setTimeout(subscribePush, 3000);
    });
})();
</script>
@endauth
