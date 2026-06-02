<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donnez-nous votre avis</title>
</head>
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f4f4f7;color:#333;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;">
<tr><td align="center" style="padding:32px 16px;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);max-width:600px;">

    {{-- HEADER --}}
    <tr>
        <td style="background:linear-gradient(135deg,#ec4899,#f43f5e);padding:36px 32px;text-align:center;">
            <div style="width:56px;height:56px;background:rgba(255,255,255,0.2);border-radius:16px;display:inline-block;text-align:center;line-height:56px;margin-bottom:16px;font-size:28px;">🌸</div>
            <h1 style="color:#fff;font-size:22px;font-weight:700;margin:0 0 6px;">Merci pour votre visite !</h1>
            <p style="color:rgba(255,255,255,0.85);font-size:14px;margin:0 0 4px;">Bonjour {{ $rdv->client_nom }}, votre avis compte beaucoup pour nous.</p>
            <p style="color:rgba(255,255,255,0.7);font-size:13px;margin:0;">{{ $rdv->institut?->nom ?? config('app.name') }}</p>
        </td>
    </tr>

    {{-- BODY --}}
    <tr>
        <td style="padding:32px;">
            <p style="font-size:15px;color:#374151;margin:0 0 24px;">
                Nous serions ravis de connaître votre avis sur votre dernier rendez-vous du <strong>{{ $rdv->debut_le->translatedFormat('d F Y') }}</strong>.<br>
                Cela ne prend qu'une minute !
            </p>

            <div style="text-align:center;margin:8px 0 28px;">
                <a href="{{ $lien }}"
                   style="display:inline-block;background:linear-gradient(135deg,#ec4899,#f43f5e);color:#fff;text-decoration:none;padding:14px 32px;border-radius:10px;font-weight:700;font-size:15px;">
                    ⭐ Donner mon avis
                </a>
            </div>

            <p style="font-size:13px;color:#9ca3af;text-align:center;margin:0;">Ce lien est personnel et à usage unique.</p>
        </td>
    </tr>

    {{-- FOOTER --}}
    <tr>
        <td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:20px 32px;text-align:center;font-size:12px;color:#9ca3af;">
            <p style="margin:0 0 4px;"><strong>{{ $rdv->institut?->nom ?? config('app.name') }}</strong></p>
            <p style="margin:0 0 4px;color:#d1d5db;">{{ config('app.name') }} · Gestion de salon de beauté</p>
            <p style="margin:0;">Cet e-mail a été envoyé automatiquement. Ne pas répondre directement.</p>
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>
