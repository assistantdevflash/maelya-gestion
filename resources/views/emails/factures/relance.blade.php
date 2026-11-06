<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relance de facture</title>
</head>
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f4f4f7;color:#333;">
@php
    $institut = $facture->institut;
    $cp = $institut?->couleur_primaire ?? '#7c3aed';
    $cs = $institut?->couleur_secondaire ?? '#a855f7';
    $jours = $facture->jours_retard;
@endphp
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;">
<tr><td align="center" style="padding:32px 16px;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);max-width:600px;">

    <tr>
        <td style="background-color:{{ $cp }};background:linear-gradient(135deg,{{ $cp }},{{ $cs }});padding:32px;text-align:center;">
            <h1 style="color:#ffffff !important;font-size:21px;font-weight:700;margin:0 0 6px;">Rappel de facture</h1>
            <p style="color:rgba(255,255,255,0.85) !important;font-size:13px;margin:0;">{{ $institut?->nom ?? config('app.name') }}</p>
        </td>
    </tr>

    <tr>
        <td style="padding:32px;">
            <p style="font-size:14px;color:#374151;line-height:1.7;white-space:pre-line;margin:0 0 24px;">{{ $message }}</p>

            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin-bottom:24px;">
                <tr>
                    <td style="padding:14px 18px;border-bottom:1px solid #f3f4f6;font-size:13px;color:#6b7280;">Facture</td>
                    <td style="padding:14px 18px;border-bottom:1px solid #f3f4f6;font-size:13px;font-weight:700;text-align:right;color:#111827;">{{ $facture->numero }}</td>
                </tr>
                <tr>
                    <td style="padding:14px 18px;border-bottom:1px solid #f3f4f6;font-size:13px;color:#6b7280;">Montant total</td>
                    <td style="padding:14px 18px;border-bottom:1px solid #f3f4f6;font-size:13px;text-align:right;color:#111827;">{{ number_format($facture->total_ttc, 0, ',', ' ') }} F CFA</td>
                </tr>
                <tr>
                    <td style="padding:14px 18px;border-bottom:1px solid #f3f4f6;font-size:13px;color:#6b7280;">Déjà réglé</td>
                    <td style="padding:14px 18px;border-bottom:1px solid #f3f4f6;font-size:13px;text-align:right;color:#059669;">{{ number_format($facture->montant_paye, 0, ',', ' ') }} F CFA</td>
                </tr>
                <tr>
                    <td style="padding:14px 18px;font-size:13px;color:#6b7280;">Reste à payer</td>
                    <td style="padding:14px 18px;font-size:15px;font-weight:700;text-align:right;color:#dc2626;">{{ number_format($facture->resteAPayer, 0, ',', ' ') }} F CFA</td>
                </tr>
            </table>

            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:8px;">
                <tr>
                    <td align="center">
                        <a href="{{ $lien }}" style="display:inline-block;background-color:{{ $cp }};color:#ffffff !important;text-decoration:none;font-size:14px;font-weight:600;padding:13px 26px;border-radius:10px;">
                            Télécharger la facture (PDF)
                        </a>
                    </td>
                </tr>
            </table>

            @if($jours > 0)
            <p style="font-size:12px;color:#b91c1c;text-align:center;margin:16px 0 0;">
                Échéance dépassée depuis {{ \App\Helpers\TexteHelper::pluriel($jours, 'jour', 'jours') }}.
            </p>
            @else
            <p style="font-size:12px;color:#6b7280;text-align:center;margin:16px 0 0;">
                Échéance : {{ $facture->date_echeance?->format('d/m/Y') }}
            </p>
            @endif
        </td>
    </tr>

    <tr>
        <td style="padding:20px 32px;background:#f9fafb;text-align:center;">
            <p style="font-size:11px;color:#9ca3af;margin:0;">
                {{ $institut?->nom }}{{ $institut?->telephone ? ' · ' . $institut->telephone : '' }}{{ $institut?->email ? ' · ' . $institut->email : '' }}
            </p>
        </td>
    </tr>
</table>
</td></tr>
</table>
</body>
</html>
