<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle demande d'abonnement</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f4f4f7; color: #333; }
        .wrapper { max-width: 600px; margin: 32px auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #9333ea, #ec4899); padding: 36px 32px; text-align: center; }
        .header-icon { width: 56px; height: 56px; background: rgba(255,255,255,0.2); border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px; font-size: 28px; }
        .header h1 { color: #fff; font-size: 22px; font-weight: 700; line-height: 1.3; }
        .header p { color: rgba(255,255,255,0.85); font-size: 14px; margin-top: 6px; }
        .body { padding: 32px; }
        .alert { background: #fef3c7; border: 1px solid #fcd34d; border-radius: 10px; padding: 14px 16px; margin-bottom: 24px; font-size: 14px; color: #92400e; }
        .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #9333ea; margin-bottom: 12px; }
        .card { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .row { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 10px; }
        .row:last-child { margin-bottom: 0; }
        .label { font-size: 12px; color: #6b7280; font-weight: 500; min-width: 140px; flex-shrink: 0; }
        .value { font-size: 14px; color: #111827; font-weight: 600; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
        .badge-attente { background: #fef9c3; color: #713f12; }
        .badge-mensuel { background: #ede9fe; color: #5b21b6; }
        .badge-annuel  { background: #dbeafe; color: #1e40af; }
        .badge-triennal { background: #dcfce7; color: #14532d; }
        .amount { font-size: 26px; font-weight: 800; background: linear-gradient(135deg, #9333ea, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 24px 0; }
        .proof-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 14px 16px; font-size: 13px; color: #166534; margin-bottom: 20px; }
        .no-proof-box { background: #fff7ed; border: 1px solid #fed7aa; border-radius: 10px; padding: 14px 16px; font-size: 13px; color: #9a3412; margin-bottom: 20px; }
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
        <div class="header-icon">🔔</div>
        <h1>Nouvelle demande d'abonnement</h1>
        <p>Une demande vient d'être soumise et attend votre validation.</p>
    </div>

    <div class="body">

        {{-- Alerte statut --}}
        <div class="alert">
            ⏳ &nbsp;Cette demande est <strong>en attente de validation</strong>. Connectez-vous au panneau d'administration pour l'approuver ou la rejeter.
        </div>

        {{-- Informations client --}}
        <div class="section-title">Informations du client</div>
        <div class="card">
            <div class="row">
                <span class="label">Nom complet</span>
                <span class="value">{{ $abonnement->user->nom_complet }}</span>
            </div>
            <div class="row">
                <span class="label">Email</span>
                <span class="value">{{ $abonnement->user->email }}</span>
            </div>
            @if($abonnement->user->telephone)
            <div class="row">
                <span class="label">Téléphone</span>
                <span class="value">{{ $abonnement->user->telephone }}</span>
            </div>
            @endif
            <div class="row">
                <span class="label">Inscrit le</span>
                <span class="value">{{ $abonnement->user->created_at->format('d/m/Y') }}</span>
            </div>
        </div>

        {{-- Détails de l'abonnement --}}
        <div class="section-title">Détails de l'abonnement</div>
        <div class="card">
            <div class="row">
                <span class="label">Plan</span>
                <span class="value">{{ $abonnement->plan->nom }}</span>
            </div>
            <div class="row">
                <span class="label">Période</span>
                <span class="value">
                    @php
                        $periodeLabels = ['mensuel' => 'Mensuel', 'annuel' => 'Annuel (−10%)', 'triennal' => '3 ans (−20%)'];
                        $periodeBadge  = ['mensuel' => 'badge-mensuel', 'annuel' => 'badge-annuel', 'triennal' => 'badge-triennal'];
                    @endphp
                    <span class="badge {{ $periodeBadge[$abonnement->periode] ?? '' }}">
                        {{ $periodeLabels[$abonnement->periode] ?? $abonnement->periode }}
                    </span>
                </span>
            </div>
            <div class="row">
                <span class="label">Montant payé</span>
                <span class="amount">{{ number_format($abonnement->montant, 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="row">
                <span class="label">Statut</span>
                <span class="badge badge-attente">En attente</span>
            </div>
            <div class="row">
                <span class="label">Demande reçue le</span>
                <span class="value">{{ $abonnement->created_at->format('d/m/Y à H:i') }}</span>
            </div>
        </div>

        <hr class="divider">

        {{-- Preuve de paiement --}}
        @if($abonnement->reference_transfert)
        <div class="section-title">Référence de transfert</div>
        <div class="proof-box">
            ✅ &nbsp;Référence : <strong>{{ $abonnement->reference_transfert }}</strong>
        </div>
        @endif

        @if($abonnement->preuve_paiement)
        <div class="section-title">Reçu de paiement</div>
        <div class="proof-box">
            📎 &nbsp;Un fichier reçu a été joint à la demande. Consultez-le depuis le panneau d'administration.
        </div>
        @elseif(!$abonnement->reference_transfert)
        <div class="no-proof-box">
            ⚠️ &nbsp;Aucune preuve de paiement ni référence fournie. Vérifiez avant de valider.
        </div>
        @endif

        {{-- CTA --}}
        <div class="cta">
            <a href="{{ url('/admin/abonnements') }}" class="btn">
                Voir la demande dans l'administration →
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
