<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre commande est en attente</title>
</head>
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f4f4f7;color:#333;">
@php
    $institut = $commande->institut;
    $cp = $institut?->couleur_primaire ?? '#7c3aed';
    $cs = $institut?->couleur_secondaire ?? '#a855f7';
@endphp
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;">
<tr><td align="center" style="padding:32px 16px;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);max-width:600px;">

    <tr>
        <td style="background-color:{{ $cp }};background:linear-gradient(135deg,{{ $cp }},{{ $cs }});padding:32px;text-align:center;">
            <h1 style="color:#ffffff !important;font-size:21px;font-weight:700;margin:0 0 6px;">Votre commande est en attente</h1>
            <p style="color:rgba(255,255,255,0.85) !important;font-size:13px;margin:0;">{{ $institut?->nom ?? config('app.name') }}</p>
        </td>
    </tr>

    <tr>
        <td style="padding:32px;">
            <p style="font-size:14px;color:#374151;line-height:1.7;margin:0 0 20px;">
                Bonjour {{ $commande->client_prenom }},<br><br>
                Nous avons bien reçu votre commande <strong>{{ $commande->numero }}</strong>,
                mais elle n'a pas encore été confirmée. Si vous souhaitez la maintenir,
                vous pouvez nous le signaler depuis votre lien de suivi.
            </p>

            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin-bottom:24px;">
                <tr>
                    <td style="padding:14px 18px;border-bottom:1px solid #f3f4f6;font-size:13px;color:#6b7280;">Commande</td>
                    <td style="padding:14px 18px;border-bottom:1px solid #f3f4f6;font-size:13px;font-weight:700;text-align:right;color:#111827;">{{ $commande->numero }}</td>
                </tr>
                <tr>
                    <td style="padding:14px 18px;border-bottom:1px solid #f3f4f6;font-size:13px;color:#6b7280;">Articles</td>
                    <td style="padding:14px 18px;border-bottom:1px solid #f3f4f6;font-size:13px;text-align:right;color:#111827;">{{ $commande->items->count() }}</td>
                </tr>
                <tr>
                    <td style="padding:14px 18px;font-size:13px;color:#6b7280;">Total</td>
                    <td style="padding:14px 18px;font-size:15px;font-weight:700;text-align:right;color:#111827;">{{ number_format($commande->total, 0, ',', ' ') }} F CFA</td>
                </tr>
            </table>

            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center">
                        <a href="{{ $lien }}" style="display:inline-block;background-color:{{ $cp }};color:#ffffff !important;text-decoration:none;font-size:14px;font-weight:600;padding:13px 26px;border-radius:10px;">
                            Suivre ma commande
                        </a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <tr>
        <td style="padding:20px 32px;background:#f9fafb;text-align:center;">
            <p style="font-size:11px;color:#9ca3af;margin:0;">
                {{ $institut?->nom }}{{ $institut?->telephone ? ' · ' . $institut->telephone : '' }}
            </p>
        </td>
    </tr>
</table>
</td></tr>
</table>
</body>
</html>
