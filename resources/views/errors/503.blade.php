<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Maintenance — Maëlya Gestion</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <meta http-equiv="refresh" content="30">
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; background: #030712; color: #f5f5f5; min-height: 100vh; display: flex; flex-direction: column; margin: 0; }
    </style>
</head>
<body>

    <div style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:2rem 1rem; text-align:center;">

        <div style="width:5rem; height:5rem; border-radius:1rem; display:flex; align-items:center; justify-content:center; margin-bottom:1.5rem; background:linear-gradient(135deg,#f59e0b,#d97706); box-shadow:0 4px 24px rgba(245,158,11,.3);">
            <svg style="width:2.5rem; height:2.5rem; color:#fff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>

        <p style="font-size:4rem; font-weight:900; margin:0 0 .5rem; background:linear-gradient(135deg,#f59e0b,#f97316); -webkit-background-clip:text; -webkit-text-fill-color:transparent;">503</p>

        <h1 style="font-size:1.5rem; font-weight:700; color:#fff; margin:0 0 .75rem;">Maintenance en cours</h1>

        <p style="color:#9ca3af; font-size:.875rem; max-width:24rem; margin:0 0 2rem;">
            Nous effectuons une mise à jour. L'application sera de nouveau disponible dans quelques instants.
        </p>

        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:center; gap:.75rem;">
            <button onclick="location.reload()"
                    style="display:inline-flex; align-items:center; gap:.5rem; padding:.625rem 1.25rem; border-radius:.75rem; font-size:.875rem; font-weight:600; color:#fff; background:linear-gradient(135deg,#f59e0b,#d97706); border:none; cursor:pointer; box-shadow:0 2px 12px rgba(245,158,11,.3);">
                <svg style="width:1rem; height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Actualiser
            </button>
        </div>
    </div>

    <footer style="padding:1.25rem 0; text-align:center; font-size:.75rem; color:#4b5563; border-top:1px solid rgba(255,255,255,.05);">
        <span style="font-weight:600; background:linear-gradient(135deg,#9333ea,#ec4899); -webkit-background-clip:text; -webkit-text-fill-color:transparent;">Maëlya Gestion</span>
    </footer>

</body>
</html>
