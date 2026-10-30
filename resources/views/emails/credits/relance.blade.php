<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rappel de paiement</title>
</head>
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f4f4f7;color:#333;">
@php
    $client   = $credit->client;
    $institut = $credit->institut;
    $cp = $institut?->couleur_primaire ?? '#7c3aed';
    $cs = $institut?->couleur_secondaire ?? '#a855f7';
@endphp
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;">
<tr><td align="center" style="padding:32px 16px;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);max-width:600px;">

    <tr>
        <td style="background-color:{{ $cp }};background:linear-gradient(135deg,{{ $cp }},{{ $cs }});padding:32px;text-align:center;">
            <h1 style="color:#ffffff !important;font-size:21px;font-weight:700;margin:0 0 6px;">Rappel de paiement</h1>
            <p style="color:rgba(255,255,255,0.85) !important;font-size:13px;margin:0;">{{ $institut?->nom ?? config('app.name') }}</p>
        </td>
    </tr>

    <tr>
        <td style="padding:32px;">
            <p style="font-size:14px;color:#374151;line-height:1.7;white-space:pre-line;margin:0 0 24px;">{{ $message }}</p>

            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin-bottom:24px;">
                <tr style="background:#f9fafb;">
                    <td style="padding:10px 14px;font-size:12px;color:#6b7280;">Montant total</td>
                    <td style="padding:10px 14px;font-size:13px;font-weight:700;text-align:right;">{{ number_format((int) $credit->montant_total, 0, ',', ' ') }} FCFA</td>
                </tr>
                <tr>
                    <td style="padding:10px 14px;font-size:12px;color:#6b7280;">Reste à payer</td>
                    <td style="padding:10px 14px;font-size:15px;font-weight:700;text-align:right;color:#dc2626;">{{ number_format((int) $credit->reste_a_payer, 0, ',', ' ') }} FCFA</td>
                </tr>
                @if($credit->date_fin_prevue)
                <tr style="background:#f9fafb;">
                    <td style="padding:10px 14px;font-size:12px;color:#6b7280;">Fin prévue</td>
                    <td style="padding:10px 14px;font-size:13px;font-weight:600;text-align:right;">{{ $credit->date_fin_prevue->format('d/m/Y') }}</td>
                </tr>
                @endif
            </table>

            <div style="text-align:center;margin-bottom:8px;">
                <a href="{{ $suivi }}" style="display:inline-block;background:{{ $cp }};color:#fff !important;text-decoration:none;font-weight:600;font-size:14px;padding:13px 26px;border-radius:10px;">
                    Voir le détail de mon crédit
                </a>
            </div>
        </td>
    </tr>

    <tr>
        <td style="padding:20px 32px;border-top:1px solid #e5e7eb;text-align:center;">
            <p style="font-size:11px;color:#9ca3af;margin:0;">
                Si vous avez déjà effectué ce paiement, ignorez simplement ce message.
            </p>
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>
