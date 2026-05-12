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

        /* Header */
        .header { background: linear-gradient(135deg, #9333ea, #ec4899); padding: 36px 32px; text-align: center; }
        .header-icon { width: 56px; height: 56px; background: rgba(255,255,255,0.2); border-radius: 16px; display: inline-block; text-align: center; line-height: 56px; margin-bottom: 16px; font-size: 28px; }
        .header h1 { color: #fff; font-size: 22px; font-weight: 700; line-height: 1.3; }
        .header p { color: rgba(255,255,255,0.85); font-size: 14px; margin-top: 6px; }

        /* Body */
        .body { padding: 32px; }
        .alert-success { background: #f0fdf4; border: 1px solid #86efac; border-radius: 10px; padding: 14px 16px; margin-bottom: 28px; font-size: 14px; color: #14532d; }
        .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #9333ea; margin-bottom: 12px; }
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 28px 0; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 700; }
        .badge-actif    { background: #dcfce7; color: #14532d; }
        .badge-mensuel  { background: #ede9fe; color: #5b21b6; }
        .badge-annuel   { background: #dbeafe; color: #1e40af; }
        .badge-triennal { background: #dcfce7; color: #14532d; }

        /* Validity */
        .validity-box { background: #f0fdf4; border: 1px solid #86efac; border-radius: 12px; padding: 20px 24px; margin-bottom: 20px; }
        .validity-badges { margin-bottom: 16px; }
        .validity-plan-name { font-size: 15px; font-weight: 700; color: #14532d; margin-left: 6px; vertical-align: middle; }

        /* Invoice */
        .invoice { border: 2px solid #e5e7eb; border-radius: 14px; overflow: hidden; margin-bottom: 24px; }
        .invoice-body { padding: 24px; }
        .invoice-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .invoice-table th { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; border-bottom: 2px solid #e5e7eb; padding: 10px 12px; text-align: left; background: #f9fafb; }
        .invoice-table td { font-size: 13px; color: #111827; padding: 12px 12px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        .invoice-footer { background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 12px 24px; font-size: 11px; color: #9ca3af; text-align: center; }

        /* CTA */
        .cta { text-align: center; margin: 28px 0 8px; }
        .btn { display: inline-block; padding: 14px 36px; background: linear-gradient(135deg, #9333ea, #ec4899); color: #fff !important; text-decoration: none; border-radius: 10px; font-weight: 700; font-size: 15px; }

        /* Footer */
        .footer { background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 20px 32px; text-align: center; font-size: 12px; color: #9ca3af; }
        .footer a { color: #9333ea; text-decoration: none; }
    </style>
</head>
<body>
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;">
<tr><td align="center" style="padding:32px 16px;">
<table class="wrapper" width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);max-width:600px;">

    {{-- ══ HEADER ══ --}}
    <tr>
        <td style="background:linear-gradient(135deg,#9333ea,#ec4899);padding:36px 32px;text-align:center;">
            <div style="width:56px;height:56px;background:rgba(255,255,255,0.2);border-radius:16px;display:inline-block;text-align:center;line-height:56px;margin-bottom:16px;font-size:28px;">✅</div>
            <h1 style="color:#fff;font-size:22px;font-weight:700;margin:0 0 6px;">Votre abonnement est activé !</h1>
            <p style="color:rgba(255,255,255,0.85);font-size:14px;margin:0;">Bienvenue dans {{ config('app.name') }}, {{ $abonnement->user->prenom ?? $abonnement->user->name }}.</p>
        </td>
    </tr>

    {{-- ══ BODY ══ --}}
    <tr>
        <td style="padding:32px;">

            {{-- Confirmation --}}
            <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:14px 16px;margin-bottom:28px;font-size:14px;color:#14532d;">
                🎉 &nbsp;Votre paiement a été <strong>validé</strong>. Votre espace est désormais pleinement actif.
            </div>

            @php
                $periodeLabels    = ['mensuel' => 'Mensuel', 'annuel' => 'Annuel', 'triennal' => '3 ans'];
                $periodeBadgeBg   = ['mensuel' => '#ede9fe', 'annuel' => '#dbeafe', 'triennal' => '#dcfce7'];
                $periodeBadgeCol  = ['mensuel' => '#5b21b6', 'annuel' => '#1e40af', 'triennal' => '#14532d'];
                $periodeLabel     = $periodeLabels[$abonnement->periode] ?? $abonnement->periode;
                $periodeBg        = $periodeBadgeBg[$abonnement->periode] ?? '#f3f4f6';
                $periodeCol       = $periodeBadgeCol[$abonnement->periode] ?? '#374151';
                $facturRef        = 'FCT-' . strtoupper(substr(str_replace('-', '', $abonnement->id), 0, 8));
            @endphp

            {{-- ── Période d'abonnement ── --}}
            <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#9333ea;margin:0 0 12px;">Période d'abonnement</p>
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0fdf4;border:1px solid #86efac;border-radius:12px;margin-bottom:8px;">
                <tr>
                    <td style="padding:20px 24px;">
                        {{-- Badges --}}
                        <div style="margin-bottom:16px;">
                            <span style="display:inline-block;padding:4px 12px;border-radius:999px;font-size:12px;font-weight:700;background:#dcfce7;color:#14532d;">Actif</span>
                            &nbsp;
                            <span style="display:inline-block;padding:4px 12px;border-radius:999px;font-size:12px;font-weight:700;background:{{ $periodeBg }};color:{{ $periodeCol }};">{{ $periodeLabel }}</span>
                            &nbsp;
                            <strong style="font-size:14px;color:#14532d;vertical-align:middle;">{{ $abonnement->plan->nom }}</strong>
                        </div>
                        {{-- Dates (table pour garantir 2 colonnes dans Gmail) --}}
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td width="50%" style="padding-right:16px;">
                                    <p style="font-size:11px;color:#16a34a;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;margin:0 0 4px;">Début</p>
                                    <p style="font-size:17px;font-weight:800;color:#14532d;margin:0;">{{ $abonnement->debut_le->format('d/m/Y') }}</p>
                                </td>
                                <td width="50%" style="padding-left:16px;border-left:2px solid #86efac;">
                                    <p style="font-size:11px;color:#16a34a;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;margin:0 0 4px;">Expiration</p>
                                    <p style="font-size:17px;font-weight:800;color:#14532d;margin:0;">{{ $abonnement->expire_le->format('d/m/Y') }}</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <div style="border:none;border-top:1px solid #e5e7eb;margin:28px 0;"></div>

            {{-- ── FACTURE ── --}}
            <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#9333ea;margin:0 0 12px;">Facture</p>
            <table width="100%" cellpadding="0" cellspacing="0" style="border:2px solid #e5e7eb;border-radius:14px;overflow:hidden;margin-bottom:24px;">

                {{-- En-tête facture --}}
                <tr>
                    <td style="background:linear-gradient(135deg,#9333ea,#ec4899);padding:20px 24px;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td>
                                    <p style="font-size:18px;font-weight:800;color:#fff;margin:0 0 3px;">{{ config('app.name') }}</p>
                                    <p style="font-size:11px;color:rgba(255,255,255,0.8);margin:0;">Logiciel de gestion d'établissements</p>
                                </td>
                                <td align="right">
                                    <p style="font-size:10px;color:rgba(255,255,255,0.75);text-transform:uppercase;letter-spacing:0.08em;margin:0 0 3px;">Facture</p>
                                    <p style="font-size:15px;font-weight:800;color:#fff;margin:0 0 3px;">{{ $facturRef }}</p>
                                    <p style="font-size:12px;color:rgba(255,255,255,0.8);margin:0;">{{ $abonnement->debut_le->format('d/m/Y') }}</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Émetteur / Client --}}
                <tr>
                    <td style="padding:24px 24px 0;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td width="50%" style="vertical-align:top;padding-right:20px;">
                                    <p style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#9333ea;margin:0 0 8px;">Émetteur</p>
                                    <p style="font-size:14px;font-weight:700;color:#111827;margin:0 0 4px;">{{ config('app.name') }}</p>
                                    <p style="font-size:12px;color:#6b7280;margin:0;">{{ config('app.url') }}</p>
                                </td>
                                <td width="50%" style="vertical-align:top;padding-left:20px;border-left:1px solid #e5e7eb;">
                                    <p style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#9333ea;margin:0 0 8px;">Client</p>
                                    <p style="font-size:14px;font-weight:700;color:#111827;margin:0 0 4px;">{{ $abonnement->user->nom_complet }}</p>
                                    <p style="font-size:12px;color:#6b7280;margin:0 0 2px;">{{ $abonnement->user->email }}</p>
                                    @if($abonnement->user->telephone)
                                    <p style="font-size:12px;color:#6b7280;margin:0 0 2px;">{{ $abonnement->user->telephone }}</p>
                                    @endif
                                    @if($abonnement->user->institut)
                                    <p style="font-size:12px;color:#6b7280;margin:0;">{{ $abonnement->user->institut->nom }}</p>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Séparateur --}}
                <tr>
                    <td style="padding:20px 24px 0;">
                        <div style="border-top:1px solid #e5e7eb;"></div>
                    </td>
                </tr>

                {{-- Tableau des lignes --}}
                <tr>
                    <td style="padding:0 24px;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <thead>
                                <tr style="background:#f9fafb;">
                                    <th style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#6b7280;border-bottom:2px solid #e5e7eb;padding:10px 10px;text-align:left;width:45%;">Description</th>
                                    <th style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#6b7280;border-bottom:2px solid #e5e7eb;padding:10px 10px;text-align:left;width:15%;">Période</th>
                                    <th style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#6b7280;border-bottom:2px solid #e5e7eb;padding:10px 10px;text-align:left;width:20%;">Validité</th>
                                    <th style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#6b7280;border-bottom:2px solid #e5e7eb;padding:10px 10px;text-align:right;width:20%;">Montant</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="font-size:13px;color:#111827;padding:14px 10px;vertical-align:top;">
                                        <strong>Abonnement {{ $abonnement->plan->nom }}</strong><br>
                                        <span style="font-size:12px;color:#6b7280;">{{ config('app.name') }} – accès complet</span>
                                    </td>
                                    <td style="font-size:13px;color:#111827;padding:14px 10px;vertical-align:top;">{{ $periodeLabel }}</td>
                                    <td style="font-size:12px;color:#374151;padding:14px 10px;vertical-align:top;line-height:1.6;">
                                        {{ $abonnement->debut_le->format('d/m/Y') }}<br>
                                        <span style="color:#9ca3af;">→</span> {{ $abonnement->expire_le->format('d/m/Y') }}
                                    </td>
                                    <td style="font-size:13px;font-weight:700;color:#9333ea;padding:14px 10px;vertical-align:top;text-align:right;">{{ number_format($abonnement->montant, 0, ',', ' ') }} FCFA</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>

                {{-- Total --}}
                <tr>
                    <td style="padding:16px 24px 20px;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td></td>
                                <td align="right" width="220">
                                    <div style="background:linear-gradient(135deg,#9333ea,#ec4899);border-radius:10px;padding:16px 20px;text-align:right;">
                                        <p style="font-size:11px;color:rgba(255,255,255,0.8);text-transform:uppercase;letter-spacing:0.08em;margin:0 0 5px;">Total réglé</p>
                                        <p style="font-size:24px;font-weight:800;color:#fff;margin:0;">{{ number_format($abonnement->montant, 0, ',', ' ') }} <span style="font-size:14px;font-weight:600;">FCFA</span></p>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                @if($abonnement->reference_transfert)
                <tr>
                    <td style="padding:0 24px 16px;">
                        <p style="font-size:12px;color:#6b7280;margin:0;">Référence de paiement : <strong style="color:#111827;">{{ $abonnement->reference_transfert }}</strong></p>
                    </td>
                </tr>
                @endif

                {{-- Pied de facture --}}
                <tr>
                    <td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:12px 24px;font-size:11px;color:#9ca3af;text-align:center;">
                        Facture générée automatiquement le {{ now()->format('d/m/Y à H:i') }} — {{ config('app.name') }}
                    </td>
                </tr>

            </table>

            {{-- CTA --}}
            <div style="text-align:center;margin:28px 0 8px;">
                <a href="{{ url('/dashboard') }}" style="display:inline-block;padding:14px 36px;background:linear-gradient(135deg,#9333ea,#ec4899);color:#fff;text-decoration:none;border-radius:10px;font-weight:700;font-size:15px;">
                    Accéder à mon espace →
                </a>
            </div>

        </td>
    </tr>

    {{-- ══ FOOTER ══ --}}
    <tr>
        <td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:20px 32px;text-align:center;font-size:12px;color:#9ca3af;">
            Cet email vous a été envoyé par <a href="{{ url('/') }}" style="color:#9333ea;text-decoration:none;">{{ config('app.name') }}</a>.<br>
            Pour toute question, répondez à cet email ou contactez notre support.
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>
