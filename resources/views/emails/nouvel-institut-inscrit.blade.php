<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvel établissement inscrit</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f4f4f7; color: #333; }
        .wrapper { max-width: 600px; margin: 32px auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #9333ea, #ec4899); padding: 36px 32px; text-align: center; }
        .header-icon { width: 56px; height: 56px; background: rgba(255,255,255,0.2); border-radius: 16px; display: inline-block; text-align: center; line-height: 56px; margin-bottom: 16px; font-size: 28px; }
        .header h1 { color: #fff; font-size: 22px; font-weight: 700; line-height: 1.3; }
        .header p { color: rgba(255,255,255,0.85); font-size: 14px; margin-top: 6px; }
        .body { padding: 32px; }
        .alert { background: #dcfce7; border: 1px solid #86efac; border-radius: 10px; padding: 14px 16px; margin-bottom: 24px; font-size: 14px; color: #14532d; }
        .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #9333ea; margin-bottom: 12px; }
        .card { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .row { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 10px; }
        .row:last-child { margin-bottom: 0; }
        .label { font-size: 12px; color: #6b7280; font-weight: 500; min-width: 140px; flex-shrink: 0; }
        .value { font-size: 14px; color: #111827; font-weight: 600; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
        .badge-essai { background: #dbeafe; color: #1e40af; }
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 24px 0; }
        .cta { text-align: center; margin: 28px 0 8px; }
        .btn { display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #9333ea, #ec4899); color: #fff !important; text-decoration: none; border-radius: 10px; font-weight: 700; font-size: 15px; }
        .footer { background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 20px 32px; text-align: center; font-size: 12px; color: #9ca3af; }
        .footer a { color: #9333ea; text-decoration: none; }
    </style>
</head>
<body>
<div class="wrapper">

    {{-- Header --}}
    <div class="header">
        <div class="header-icon">🏪</div>
        <h1>Nouvel établissement inscrit</h1>
        <p>Un nouvel établissement vient de rejoindre {{ config('app.name') }}.</p>
    </div>

    <div class="body">

        {{-- Alerte succès --}}
        <div class="alert">
            ✅ &nbsp;L'inscription a été effectuée avec succès. L'essai gratuit de 14 jours est activé.
        </div>

        {{-- Informations établissement --}}
        <div class="section-title">Établissement</div>
        <div class="card">
            <div class="row">
                <span class="label">Nom</span>
                <span class="value">{{ $institut->nom }}</span>
            </div>
            <div class="row">
                <span class="label">Type</span>
                <span class="value">{{ ucfirst(str_replace('_', ' ', $institut->type)) }}</span>
            </div>
            <div class="row">
                <span class="label">Ville</span>
                <span class="value">{{ $institut->ville ?? '—' }}</span>
            </div>
            @if($institut->telephone)
            <div class="row">
                <span class="label">Téléphone</span>
                <span class="value">{{ $institut->telephone }}</span>
            </div>
            @endif
        </div>

        {{-- Informations propriétaire --}}
        <div class="section-title">Propriétaire du compte</div>
        <div class="card">
            <div class="row">
                <span class="label">Nom complet</span>
                <span class="value">{{ $newUser->nom_complet }}</span>
            </div>
            <div class="row">
                <span class="label">Email (login)</span>
                <span class="value">{{ $newUser->email }}</span>
            </div>
            @if($newUser->telephone)
            <div class="row">
                <span class="label">Téléphone</span>
                <span class="value">{{ $newUser->telephone }}</span>
            </div>
            @endif
            <div class="row">
                <span class="label">Inscrit le</span>
                <span class="value">{{ $newUser->created_at->format('d/m/Y à H:i') }}</span>
            </div>
            <div class="row">
                <span class="label">Plan actuel</span>
                <span class="badge badge-essai">Essai 14 jours</span>
            </div>
            @if($newUser->parraine_par)
            <div class="row">
                <span class="label">Parrainé par</span>
                <span class="value">{{ optional($newUser->parrain)->nom_complet ?? 'ID '.$newUser->parraine_par }}</span>
            </div>
            @endif
        </div>

        <hr class="divider">

        {{-- CTA --}}
        <div class="cta">
            <a href="{{ url('/admin/instituts') }}" class="btn">
                Voir l'établissement dans l'administration →
            </a>
        </div>

    </div>

    <div class="footer">
        Cet email a été envoyé automatiquement par <a href="{{ url('/') }}">{{ config('app.name') }}</a>.<br>
        Vous le recevez car vous êtes super-administrateur.
    </div>
</div>
</body>
</html>
