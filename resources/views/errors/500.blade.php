<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur serveur — Maëlya Gestion</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; background: #030712; color: #f5f5f5; min-height: 100vh; display: flex; flex-direction: column; margin: 0; }
    </style>
</head>
<body>

    <div style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:2rem 1rem; text-align:center;">

        {{-- Icône --}}
        <div style="width:5rem; height:5rem; border-radius:1rem; display:flex; align-items:center; justify-content:center; margin-bottom:1.5rem; background:linear-gradient(135deg,#ef4444,#dc2626); box-shadow:0 4px 24px rgba(239,68,68,.3);">
            <svg style="width:2.5rem; height:2.5rem; color:#fff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>

        {{-- Code --}}
        <p style="font-size:4rem; font-weight:900; margin:0 0 .5rem; background:linear-gradient(135deg,#ef4444,#f97316); -webkit-background-clip:text; -webkit-text-fill-color:transparent;">
            500
        </p>

        {{-- Titre --}}
        <h1 style="font-size:1.5rem; font-weight:700; color:#fff; margin:0 0 .75rem;">
            Une erreur est survenue
        </h1>

        {{-- Description --}}
        <p style="color:#9ca3af; font-size:.875rem; max-width:24rem; margin:0 0 2rem;">
            Quelque chose s'est mal passé côté serveur. Réessayez dans quelques instants. Si le problème persiste, contactez le support.
        </p>

        {{-- Actions --}}
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:center; gap:.75rem;">
            <button onclick="location.reload()"
                    style="display:inline-flex; align-items:center; gap:.5rem; padding:.625rem 1.25rem; border-radius:.75rem; font-size:.875rem; font-weight:600; color:#fff; background:linear-gradient(135deg,#ef4444,#dc2626); border:none; cursor:pointer; box-shadow:0 2px 12px rgba(239,68,68,.3); transition:opacity .15s;"
                    onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                <svg style="width:1rem; height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Réessayer
            </button>

            <button onclick="history.back()"
                    style="display:inline-flex; align-items:center; gap:.5rem; padding:.625rem 1.25rem; border-radius:.75rem; font-size:.875rem; font-weight:600; color:#d1d5db; background:#1f2937; border:1px solid rgba(255,255,255,.1); cursor:pointer; transition:background .15s;"
                    onmouseover="this.style.background='#374151'" onmouseout="this.style.background='#1f2937'">
                <svg style="width:1rem; height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Retour
            </button>

            <a href="{{ url('/') }}"
               style="display:inline-flex; align-items:center; gap:.5rem; padding:.625rem 1.25rem; border-radius:.75rem; font-size:.875rem; font-weight:600; color:#d1d5db; background:#1f2937; border:1px solid rgba(255,255,255,.1); text-decoration:none; transition:background .15s;"
               onmouseover="this.style.background='#374151'" onmouseout="this.style.background='#1f2937'">
                <svg style="width:1rem; height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Accueil
            </button>
        </div>
    </div>

    {{-- Footer --}}
    <footer style="padding:1.25rem 0; text-align:center; font-size:.75rem; color:#4b5563; border-top:1px solid rgba(255,255,255,.05);">
        Propulsé par
        <span style="font-weight:600; background:linear-gradient(135deg,#9333ea,#ec4899); -webkit-background-clip:text; -webkit-text-fill-color:transparent;">
            Maëlya Gestion
        </span>
    </footer>

</body>
</html>
