<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de rendez-vous reçue</title>
</head>
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f4f4f7;color:#333;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;">
<tr><td align="center" style="padding:32px 16px;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);max-width:600px;">

    {{-- HEADER --}}
    <tr>
        <td style="background:linear-gradient(135deg,#9333ea,#ec4899);padding:36px 32px;text-align:center;">
            <div style="width:56px;height:56px;background:rgba(255,255,255,0.2);border-radius:16px;display:inline-block;text-align:center;line-height:56px;margin-bottom:16px;font-size:28px;">📅</div>
            <h1 style="color:#fff;font-size:22px;font-weight:700;margin:0 0 6px;">Demande reçue !</h1>
            <p style="color:rgba(255,255,255,0.85);font-size:14px;margin:0 0 4px;">Bonjour {{ $rdv->client_nom }}, votre demande a bien été enregistrée.</p>
            <p style="color:rgba(255,255,255,0.7);font-size:13px;margin:0;">{{ $rdv->institut?->nom ?? config('app.name') }} vous recontactera pour confirmer.</p>
        </td>
    </tr>

    {{-- BODY --}}
    <tr>
        <td style="padding:32px;">
            {{-- Bandeau "en attente" --}}
            <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:10px;padding:12px 16px;margin-bottom:24px;font-size:13px;color:#92400e;">
                ⏳ <strong>Votre demande est en attente de confirmation.</strong> L'établissement vous contactera bientôt par téléphone ou par e-mail.
            </div>

            <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#9333ea;margin-bottom:12px;">Détails de votre demande</p>
            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin-bottom:28px;">
                <tr style="background:#f9fafb;">
                    <td style="font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;padding:10px 16px;">Date</td>
                    <td style="font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;padding:10px 16px;">Heure</td>
                    <td style="font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;padding:10px 16px;">Durée</td>
                </tr>
                <tr>
                    <td style="font-size:14px;color:#111827;padding:14px 16px;font-weight:600;">{{ $rdv->debut_le->translatedFormat('l d F Y') }}</td>
                    <td style="font-size:14px;color:#9333ea;padding:14px 16px;font-weight:700;">{{ $rdv->debut_le->format('H\hi') }}</td>
                    <td style="font-size:14px;color:#111827;padding:14px 16px;">{{ $rdv->duree_minutes }} min</td>
                </tr>
            </table>

            @if($rdv->prestations->isNotEmpty())
            <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#6b7280;margin-bottom:12px;">Prestation(s) demandée(s)</p>
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
                @foreach($rdv->prestations as $p)
                <tr>
                    <td style="padding:5px 0;font-size:14px;color:#374151;">
                        <span style="display:inline-block;width:8px;height:8px;background:linear-gradient(135deg,#9333ea,#ec4899);border-radius:50%;margin-right:10px;vertical-align:middle;"></span>
                        {{ $p->nom }}@if($p->duree) &nbsp;<span style="color:#9ca3af;font-size:12px;">· {{ $p->duree }} min</span>@endif
                    </td>
                </tr>
                @endforeach
            </table>
            @endif

            @if($rdv->notes)
            <div style="background:#f9fafb;border-radius:10px;padding:14px 16px;margin-bottom:28px;font-size:13px;color:#6b7280;">
                <strong style="color:#374151;">Votre message :</strong> {{ $rdv->notes }}
            </div>
            @endif

            <p style="font-size:13px;color:#6b7280;margin-bottom:8px;">En cas d'empêchement ou pour modifier votre demande, contactez directement l'établissement.</p>
        </td>
    </tr>

    {{-- FOOTER --}}
    <tr>
        <td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:20px 32px;text-align:center;font-size:12px;color:#9ca3af;">
            <p style="margin:0 0 4px;"><strong>{{ $rdv->institut?->nom ?? config('app.name') }}</strong>@if($rdv->institut?->telephone) · {{ $rdv->institut->telephone }}@endif</p>
            <p style="margin:0 0 4px;">
                @if($rdv->institut?->telephone)
                    📞 {{ $rdv->institut->telephone }} &nbsp;·&nbsp;
                @endif
                @if($rdv->institut?->email)
                    ✉️ {{ $rdv->institut->email }}
                @endif
                @if($rdv->institut?->ville)
                    &nbsp;·&nbsp; 📍 {{ $rdv->institut->ville }}
                @endif
            </p>
            <p style="margin:0;">Cet e-mail a été envoyé automatiquement suite à votre demande en ligne.</p>
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>
