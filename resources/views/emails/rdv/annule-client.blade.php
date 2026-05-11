<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rendez-vous annulé</title>
</head>
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f4f4f7;color:#333;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;">
<tr><td align="center" style="padding:32px 16px;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);max-width:600px;">

    {{-- HEADER --}}
    <tr>
        <td style="background:linear-gradient(135deg,#ef4444,#f97316);padding:36px 32px;text-align:center;">
            <div style="width:56px;height:56px;background:rgba(255,255,255,0.2);border-radius:16px;display:inline-block;text-align:center;line-height:56px;margin-bottom:16px;font-size:28px;">❌</div>
            <h1 style="color:#fff;font-size:22px;font-weight:700;margin:0 0 6px;">Rendez-vous annulé</h1>
            <p style="color:rgba(255,255,255,0.85);font-size:14px;margin:0;">Bonjour {{ $rdv->client_nom }}, votre rendez-vous a été annulé.</p>
        </td>
    </tr>

    {{-- BODY --}}
    <tr>
        <td style="padding:32px;">
            <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;padding:14px 16px;margin-bottom:28px;font-size:14px;color:#991b1b;">
                ❌&nbsp; Votre rendez-vous du <strong>{{ $rdv->debut_le->translatedFormat('l d F Y') }} à {{ $rdv->debut_le->format('H\hi') }}</strong> a été annulé.
            </div>

            @if($rdv->prestations->isNotEmpty())
            <p style="font-size:13px;color:#374151;margin-bottom:8px;"><strong>Prestation(s) concernée(s) :</strong></p>
            @foreach($rdv->prestations as $p)
            <p style="margin:4px 0;font-size:13px;color:#6b7280;">· {{ $p->nom }}</p>
            @endforeach
            @elseif($rdv->prestation_libre)
            <p style="font-size:13px;color:#374151;"><strong>Prestation :</strong> {{ $rdv->prestation_libre }}</p>
            @endif

            <p style="font-size:13px;color:#6b7280;margin-top:24px;">N'hésitez pas à nous contacter pour reprendre rendez-vous à une autre date.</p>
        </td>
    </tr>

    {{-- FOOTER --}}
    <tr>
        <td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:20px 32px;text-align:center;font-size:12px;color:#9ca3af;">
            <p style="margin:0 0 4px;">{{ config('app.name') }} · Gestion de salon de beauté</p>
            <p style="margin:0;">Cet e-mail a été envoyé automatiquement. Ne pas répondre directement.</p>
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>
