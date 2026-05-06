<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau filleul inscrit</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f7;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:#333;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;">
<tr><td align="center" style="padding:32px 16px;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);max-width:600px;">

    {{-- HEADER --}}
    <tr>
        <td style="background:linear-gradient(135deg,#9333ea,#ec4899);padding:36px 32px;text-align:center;">
            <div style="width:56px;height:56px;background:rgba(255,255,255,0.2);border-radius:16px;display:inline-block;text-align:center;line-height:56px;margin-bottom:16px;font-size:28px;">🎉</div>
            <h1 style="color:#fff;font-size:22px;font-weight:700;margin:0 0 6px;">Nouvel établissement inscrit !</h1>
            <p style="color:rgba(255,255,255,0.85);font-size:14px;margin:0;">Votre réseau s'agrandit</p>
        </td>
    </tr>

    {{-- BODY --}}
    <tr>
        <td style="padding:32px;">
            <p style="font-size:16px;margin:0 0 8px;">Bonjour <strong>{{ $commercial->prenom }}</strong>,</p>
            <p style="font-size:14px;color:#6b7280;margin:0 0 24px;">Un nouvel établissement vient de s'inscrire sur Maëlya Gestion en utilisant votre code de parrainage.</p>

            {{-- Info filleul --}}
            <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:20px 24px;margin-bottom:24px;">
                <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#9333ea;margin:0 0 12px;">Informations du filleul</p>
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="font-size:13px;color:#6b7280;padding:4px 0;">Établissement</td>
                        <td style="font-size:13px;color:#111827;font-weight:600;text-align:right;">
                            {{ $newUser->prenom }} {{ $newUser->nom_famille }}
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:13px;color:#6b7280;padding:4px 0;">Date d'inscription</td>
                        <td style="font-size:13px;color:#111827;font-weight:600;text-align:right;">
                            {{ now()->format('d/m/Y') }}
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:13px;color:#6b7280;padding:4px 0;">Code utilisé</td>
                        <td style="text-align:right;">
                            <span style="display:inline-block;padding:3px 10px;border-radius:999px;background:#ede9fe;color:#5b21b6;font-size:12px;font-weight:700;">{{ $parrainage->commercial->code ?? '' }}</span>
                        </td>
                    </tr>
                </table>
            </div>

            <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:14px 16px;font-size:14px;color:#14532d;margin-bottom:24px;">
                ✅ Lorsque cet établissement souscrira à un abonnement payant, vous recevrez automatiquement une commission.
            </div>

            <div style="text-align:center;margin:28px 0 8px;">
                <a href="{{ config('app.url') }}/commercial"
                   style="display:inline-block;padding:14px 36px;background:linear-gradient(135deg,#9333ea,#ec4899);color:#fff;text-decoration:none;border-radius:10px;font-weight:700;font-size:15px;">
                    Voir mon tableau de bord
                </a>
            </div>
        </td>
    </tr>

    {{-- FOOTER --}}
    <tr>
        <td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:20px 32px;text-align:center;font-size:12px;color:#9ca3af;">
            Maëlya Gestion &mdash; <a href="{{ config('app.url') }}" style="color:#9333ea;text-decoration:none;">{{ config('app.url') }}</a>
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>
