<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donnez-nous votre avis</title>
    <style type="text/css">
        body { background-color: #f4f4f7 !important; }
        .email-wrapper { background-color: #ffffff !important; }
        .text-dark { color: #1f2937 !important; }
        .text-gray { color: #6b7280 !important; }
        .text-light-gray { color: #9ca3af !important; }
    </style>
</head>
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;background-color:#f4f4f7;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f4f7;margin:0;padding:0;">
<tr><td align="center" style="padding:32px 16px;">
<table class="email-wrapper" width="600" cellpadding="0" cellspacing="0" border="0" style="background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);max-width:600px;">

    {{-- HEADER --}}
    <tr>
        <td style="background-color:#ec4899;padding:36px 32px;text-align:center;">
            <div style="width:56px;height:56px;background-color:rgba(255,255,255,0.2);border-radius:16px;display:inline-block;text-align:center;line-height:56px;margin-bottom:16px;font-size:28px;">🌸</div>
            <h1 style="color:#ffffff !important;font-size:22px;font-weight:700;margin:0 0 6px;mso-line-height-rule:exactly;line-height:28px;">Merci pour votre visite !</h1>
            @if($rdv)
                <p style="color:#ffffff !important;font-size:14px;margin:0 0 4px;mso-line-height-rule:exactly;line-height:20px;">Bonjour {{ $rdv->client_nom }}, votre avis compte beaucoup pour nous.</p>
                <p style="color:#ffffff !important;font-size:13px;margin:0;mso-line-height-rule:exactly;line-height:18px;">{{ $rdv->institut?->nom ?? config('app.name') }}</p>
            @elseif($vente)
                <p style="color:#ffffff !important;font-size:14px;margin:0 0 4px;mso-line-height-rule:exactly;line-height:20px;">Bonjour {{ $vente->client?->prenom ?? $vente->client?->nom ?? '' }}, votre avis compte beaucoup pour nous.</p>
                <p style="color:#ffffff !important;font-size:13px;margin:0;mso-line-height-rule:exactly;line-height:18px;">{{ $vente->institut?->nom ?? config('app.name') }}</p>
            @endif
        </td>
    </tr>

    {{-- BODY --}}
    <tr>
        <td style="background-color:#ffffff;padding:32px;">
            @if($rdv)
                <p class="text-dark" style="font-size:15px;color:#1f2937 !important;margin:0 0 24px;mso-line-height-rule:exactly;line-height:24px;">
                    Nous serions ravis de connaître votre avis sur votre dernier rendez-vous du <strong style="color:#1f2937 !important;">{{ $rdv->debut_le->translatedFormat('d F Y') }}</strong>.<br>
                    Cela ne prend qu'une minute !
                </p>
            @elseif($vente)
                <p class="text-dark" style="font-size:15px;color:#1f2937 !important;margin:0 0 24px;mso-line-height-rule:exactly;line-height:24px;">
                    Nous serions ravis de connaître votre avis sur votre achat du <strong style="color:#1f2937 !important;">{{ $vente->created_at->translatedFormat('d F Y') }}</strong>.<br>
                    Cela ne prend qu'une minute !
                </p>
            @endif

            <div style="text-align:center;margin:8px 0 28px;">
                <a href="{{ $lien }}"
                   style="display:inline-block;background-color:#ec4899;color:#ffffff !important;text-decoration:none;padding:14px 32px;border-radius:10px;font-weight:700;font-size:15px;mso-line-height-rule:exactly;line-height:20px;">
                    ⭐ Donner mon avis
                </a>
            </div>

            <p class="text-light-gray" style="font-size:13px;color:#6b7280 !important;text-align:center;margin:0;mso-line-height-rule:exactly;line-height:18px;">Ce lien est personnel et à usage unique.</p>
        </td>
    </tr>

    {{-- FOOTER --}}
    <tr>
        <td style="background-color:#f9fafb;border-top:1px solid #e5e7eb;padding:20px 32px;text-align:center;">
            @if($rdv)
                <p style="margin:0 0 4px;font-size:12px;color:#4b5563 !important;mso-line-height-rule:exactly;line-height:18px;"><strong style="color:#1f2937 !important;">{{ $rdv->institut?->nom ?? config('app.name') }}</strong></p>
            @elseif($vente)
                <p style="margin:0 0 4px;font-size:12px;color:#4b5563 !important;mso-line-height-rule:exactly;line-height:18px;"><strong style="color:#1f2937 !important;">{{ $vente->institut?->nom ?? config('app.name') }}</strong></p>
            @endif
            <p style="margin:0 0 4px;font-size:12px;color:#9ca3af !important;mso-line-height-rule:exactly;line-height:18px;">{{ config('app.name') }} · Gestion de salon de beauté</p>
            <p style="margin:0;font-size:12px;color:#9ca3af !important;mso-line-height-rule:exactly;line-height:18px;">Cet e-mail a été envoyé automatiquement. Ne pas répondre directement.</p>
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>
