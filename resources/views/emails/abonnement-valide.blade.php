<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abonnement activé</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f4f4f7; color: #333; }
        .wrapper { max-width: 600px; margin: 32px auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #9333ea, #ec4899); padding: 36px 32px; text-align: center; }
        .header-icon { width: 56px; height: 56px; background: rgba(255,255,255,0.2); border-radius: 16px; display: inline-block; text-align: center; line-height: 56px; margin-bottom: 16px; font-size: 28px; }
        .header h1 { color: #fff; font-size: 22px; font-weight: 700; line-height: 1.3; }
        .header p { color: rgba(255,255,255,0.85); font-size: 14px; margin-top: 6px; }
        .body { padding: 32px; }
        .alert-success { background: #f0fdf4; border: 1px solid #86efac; border-radius: 10px; padding: 14px 16px; margin-bottom: 24px; font-size: 14px; color: #14532d; }
        .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #9333ea; margin-bottom: 12px; }
        .card { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .row { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 10px; }
        .row:last-child { margin-bottom: 0; }
        .label { font-size: 12px; color: #6b7280; font-weight: 500; min-width: 160px; flex-shrink: 0; }
        .value { font-size: 14px; color: #111827; font-weight: 600; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
        .badge-actif { background: #dcfce7; color: #14532d; }
        .badge-mensuel { background: #ede9fe; color: #5b21b6; }
        .badge-annuel  { background: #dbeafe; color: #1e40af; }
        .badge-triennal { background: #dcfce7; color: #14532d; }
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 24px 0; }

        /* ── Facture ── */
        .invoice { border: 2px solid #e5e7eb; border-radius: 14px; overflow: hidden; margin-bottom: 24px; }
        .invoice-header { background: linear-gradient(135deg, #9333ea, #ec4899); padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; }
        .invoice-header-left { color: #fff; }
        .invoice-header-left .logo { font-size: 18px; font-weight: 800; letter-spacing: -0.5px; }
        .invoice-header-left .tagline { font-size: 11px; color: rgba(255,255,255,0.8); margin-top: 2px; }
        .invoice-header-right { text-align: right; color: #fff; }
        .invoice-header-right .facture-label { font-size: 11px; color: rgba(255,255,255,0.75); text-transform: uppercase; letter-spacing: 0.08em; }
        .invoice-header-right .facture-num { font-size: 16px; font-weight: 800; margin-top: 2px; }
        .invoice-header-right .facture-date { font-size: 12px; color: rgba(255,255,255,0.8); margin-top: 2px; }
        .invoice-body { padding: 24px; }
        .invoice-parties { display: flex; gap: 24px; margin-bottom: 20px; }
        .invoice-party { flex: 1; }
        .invoice-party-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #9333ea; margin-bottom: 8px; }
        .invoice-party-name { font-size: 15px; font-weight: 700; color: #111827; }
        .invoice-party-detail { font-size: 12px; color: #6b7280; margin-top: 3px; line-height: 1.5; }
        .invoice-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .invoice-table th { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; border-bottom: 1px solid #e5e7eb; padding: 8px 12px; text-align: left; }
        .invoice-table td { font-size: 13px; color: #111827; padding: 10px 12px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        .invoice-table td.amount { font-weight: 700; color: #9333ea; text-align: right; }
        .invoice-table th.right { text-align: right; }
        .invoice-total-row { display: flex; justify-content: flex-end; }
        .invoice-total { background: linear-gradient(135deg, #9333ea, #ec4899); border-radius: 10px; padding: 14px 20px; color: #fff; text-align: right; min-width: 200px; }
        .invoice-total .total-label { font-size: 11px; color: rgba(255,255,255,0.8); text-transform: uppercase; letter-spacing: 0.08em; }
        .invoice-total .total-amount { font-size: 24px; font-weight: 800; margin-top: 4px; }
        .invoice-total .total-currency { font-size: 14px; font-weight: 600; margin-left: 4px; }
        .invoice-footer { background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 12px 24px; font-size: 11px; color: #9ca3af; text-align: center; }

        /* ── Validité ── */
        .validity-box { background: linear-gradient(135deg, #f0fdf4, #dcfce7); border: 1px solid #86efac; border-radius: 12px; padding: 18px 20px; margin-bottom: 20px; }
        .validity-dates { display: flex; gap: 32px; margin-top: 10px; }
        .validity-date { flex: 1; }
        .validity-date-label { font-size: 11px; color: #16a34a; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; }
        .validity-date-value { font-size: 15px; font-weight: 800; color: #14532d; margin-top: 3px; }

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
        <div class="header-icon">✅</div>
        <h1>Votre abonnement est activé !</h1>
        <p>Bienvenue dans {{ config('app.name') }}, {{ $abonnement->user->prenom ?? $abonnement->user->name }}.</p>
    </div>

    <div class="body">

        {{-- Confirmation --}}
        <div class="alert-success">
            🎉 &nbsp;Votre paiement a été <strong>validé</strong>. Votre espace est désormais pleinement actif.
        </div>

        {{-- Validité --}}
        <div class="section-title">Période d'abonnement</div>
        <div class="validity-box">
            <div>
                <span class="badge badge-actif">Actif</span>
                &nbsp;
                @php
                    $periodeLabels = ['mensuel' => 'Mensuel', 'annuel' => 'Annuel', 'triennal' => '3 ans'];
                    $periodeBadgeClass = ['mensuel' => 'badge-mensuel', 'annuel' => 'badge-annuel', 'triennal' => 'badge-triennal'];
                @endphp
                <span class="badge {{ $periodeBadgeClass[$abonnement->periode] ?? '' }}">
                    {{ $periodeLabels[$abonnement->periode] ?? $abonnement->periode }}
                </span>
                &nbsp;
                <strong style="font-size:14px;">{{ $abonnement->plan->nom }}</strong>
            </div>
            <div class="validity-dates">
                <div class="validity-date">
                    <div class="validity-date-label">Début</div>
                    <div class="validity-date-value">{{ $abonnement->debut_le->format('d/m/Y') }}</div>
                </div>
                <div class="validity-date">
                    <div class="validity-date-label">Expiration</div>
                    <div class="validity-date-value">{{ $abonnement->expire_le->format('d/m/Y') }}</div>
                </div>
            </div>
        </div>

        <hr class="divider">

        {{-- ── FACTURE ── --}}
        <div class="section-title">Facture</div>
        <div class="invoice">
            <div class="invoice-header">
                <div class="invoice-header-left">
                    <div class="logo">{{ config('app.name') }}</div>
                    <div class="tagline">Logiciel de gestion pour instituts</div>
                </div>
                <div class="invoice-header-right">
                    <div class="facture-label">Facture</div>
                    <div class="facture-num">FCT-{{ str_pad($abonnement->id, 6, '0', STR_PAD_LEFT) }}</div>
                    <div class="facture-date">{{ $abonnement->debut_le->format('d/m/Y') }}</div>
                </div>
            </div>

            <div class="invoice-body">

                {{-- Parties --}}
                <div class="invoice-parties">
                    <div class="invoice-party">
                        <div class="invoice-party-label">Émetteur</div>
                        <div class="invoice-party-name">{{ config('app.name') }}</div>
                        <div class="invoice-party-detail">
                            {{ config('app.url') }}
                        </div>
                    </div>
                    <div class="invoice-party">
                        <div class="invoice-party-label">Client</div>
                        <div class="invoice-party-name">{{ $abonnement->user->nom_complet }}</div>
                        <div class="invoice-party-detail">
                            {{ $abonnement->user->email }}<br>
                            @if($abonnement->user->telephone)
                                {{ $abonnement->user->telephone }}<br>
                            @endif
                            @if($abonnement->user->institut)
                                {{ $abonnement->user->institut->nom }}
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Lignes --}}
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Période</th>
                            <th>Validité</th>
                            <th class="right">Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong>Abonnement {{ $abonnement->plan->nom }}</strong><br>
                                <span style="font-size:12px;color:#6b7280;">{{ config('app.name') }} – accès complet</span>
                            </td>
                            <td>{{ $periodeLabels[$abonnement->periode] ?? $abonnement->periode }}</td>
                            <td style="font-size:12px;">
                                {{ $abonnement->debut_le->format('d/m/Y') }}<br>
                                → {{ $abonnement->expire_le->format('d/m/Y') }}
                            </td>
                            <td class="amount">{{ number_format($abonnement->montant, 0, ',', ' ') }} FCFA</td>
                        </tr>
                    </tbody>
                </table>

                {{-- Total --}}
                <div class="invoice-total-row">
                    <div class="invoice-total">
                        <div class="total-label">Total réglé</div>
                        <div class="total-amount">
                            {{ number_format($abonnement->montant, 0, ',', ' ') }}<span class="total-currency">FCFA</span>
                        </div>
                    </div>
                </div>

                @if($abonnement->reference_transfert)
                <p style="margin-top:16px;font-size:12px;color:#6b7280;">
                    Référence de paiement : <strong style="color:#111827;">{{ $abonnement->reference_transfert }}</strong>
                </p>
                @endif

            </div>

            <div class="invoice-footer">
                Facture générée automatiquement le {{ now()->format('d/m/Y à H:i') }} — {{ config('app.name') }}
            </div>
        </div>

        {{-- CTA --}}
        <div class="cta">
            <a href="{{ url('/dashboard') }}" class="btn">
                Accéder à mon espace →
            </a>
        </div>

    </div>

    <div class="footer">
        Cet email vous a été envoyé par <a href="{{ url('/') }}">{{ config('app.name') }}</a>.<br>
        Pour toute question, répondez à cet email ou contactez notre support.
    </div>
</div>
</body>
</html>
