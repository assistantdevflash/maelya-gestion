<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau paiement reçu</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f4f4f7; color: #333; }
        .wrapper { max-width: 600px; margin: 32px auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .header { background-color:#9333ea; background: linear-gradient(135deg, #9333ea, #ec4899); padding: 36px 32px; text-align: center; }
        .header-icon { width: 56px; height: 56px; background: rgba(255,255,255,0.2); border-radius: 16px; display: inline-block; text-align: center; line-height: 56px; margin-bottom: 16px; font-size: 28px; }
        .header h1 { color: #ffffff !important; font-size: 22px; font-weight: 700; line-height: 1.3; }
        .header p { color: rgba(255,255,255,0.85) !important; font-size: 14px; margin-top: 6px; }
        .body { padding: 32px; }
        .alert { background: #ecfdf5; border: 1px solid #6ee7b7; border-radius: 10px; padding: 14px 16px; margin-bottom: 24px; font-size: 14px; color: #065f46; }
        .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #9333ea; margin-bottom: 12px; }
        .card { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .row { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 10px; }
        .row:last-child { margin-bottom: 0; }
        .label { font-size: 12px; color: #6b7280; font-weight: 500; min-width: 140px; flex-shrink: 0; }
        .value { font-size: 14px; color: #111827; font-weight: 600; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
        .badge-boutique { background: #f5f3ff; color: #6d28d9; }
        .badge-abonnement { background: #ede9fe; color: #5b21b6; }
        .badge-geniuspay { background: #dbeafe; color: #1e40af; }
        .amount { font-size: 26px; font-weight: 800; background: linear-gradient(135deg, #9333ea, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
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
        <div class="header-icon">💳</div>
        <h1>Nouveau paiement reçu</h1>
        <p>Un paiement vient d'être encaissé et le service a été activé automatiquement.</p>
    </div>

    <div class="body">

        {{-- Confirmation --}}
        <div class="alert">
            ✅ &nbsp;Le service a été <strong>activé automatiquement</strong>. Aucune action requise.
        </div>

        {{-- Informations client --}}
        <div class="section-title">Informations du client</div>
        <div class="card">
            <div class="row">
                <span class="label">Nom complet</span>
                <span class="value">{{ $transaction->user?->nom_complet ?? '—' }}</span>
            </div>
            <div class="row">
                <span class="label">Email</span>
                <span class="value">{{ $transaction->user?->email ?? '—' }}</span>
            </div>
            @if($transaction->user?->telephone)
            <div class="row">
                <span class="label">Téléphone</span>
                <span class="value">{{ $transaction->user->telephone }}</span>
            </div>
            @endif
            @if($transaction->user?->institut?->nom)
            <div class="row">
                <span class="label">Établissement</span>
                <span class="value">{{ $transaction->user->institut->nom }}</span>
            </div>
            @endif
        </div>

        {{-- Détails du paiement --}}
        <div class="section-title">Détails du paiement</div>
        <div class="card">
            <div class="row">
                <span class="label">Type</span>
                <span class="badge {{ str_starts_with($transaction->type, 'boutique') ? 'badge-boutique' : 'badge-abonnement' }}">
                    {{ \App\Mail\NouveauPaiementRecu::typeLabel($transaction->type) }}
                </span>
            </div>
            <div class="row">
                <span class="label">Référence</span>
                <span class="value">{{ $transaction->reference }}</span>
            </div>
            <div class="row">
                <span class="label">Moyen de paiement</span>
                <span class="badge badge-geniuspay">GeniusPay</span>
            </div>
            <div class="row">
                <span class="label">Montant</span>
                <span class="amount">{{ number_format($transaction->amount, 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="row">
                <span class="label">Payé le</span>
                <span class="value">{{ $transaction->paid_at?->format('d/m/Y à H:i') ?? now()->format('d/m/Y à H:i') }}</span>
            </div>
            @if($transaction->abonnement?->plan)
            <div class="row">
                <span class="label">Plan</span>
                <span class="value">{{ $transaction->abonnement->plan->nom }}</span>
            </div>
            @endif
        </div>

        <hr class="divider">

        {{-- CTA --}}
        <div class="cta">
            <a href="{{ url('/admin/payment-transactions') }}" class="btn">
                Voir la transaction dans l'administration →
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
