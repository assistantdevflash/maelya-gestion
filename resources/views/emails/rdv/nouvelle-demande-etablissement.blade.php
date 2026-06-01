<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle demande de RDV</title>
</head>
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f4f4f7;color:#333;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;">
<tr><td align="center" style="padding:32px 16px;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);max-width:600px;">

    {{-- HEADER --}}
    <tr>
        <td style="background:linear-gradient(135deg,#1d4ed8,#9333ea);padding:36px 32px;text-align:center;">
            <div style="width:56px;height:56px;background:rgba(255,255,255,0.2);border-radius:16px;display:inline-block;text-align:center;line-height:56px;margin-bottom:16px;font-size:28px;">🔔</div>
            <h1 style="color:#fff;font-size:22px;font-weight:700;margin:0 0 6px;">Nouvelle demande de RDV</h1>
            <p style="color:rgba(255,255,255,0.85);font-size:14px;margin:0 0 4px;">Reçue via votre page vitrine publique</p>
            <p style="color:rgba(255,255,255,0.7);font-size:13px;margin:0;">{{ $rdv->institut?->nom ?? config('app.name') }}</p>
        </td>
    </tr>

    {{-- BODY --}}
    <tr>
        <td style="padding:32px;">

            {{-- Client --}}
            <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#1d4ed8;margin-bottom:12px;">Informations du client</p>
            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin-bottom:28px;">
                <tr>
                    <td style="font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;padding:10px 16px;background:#f9fafb;width:35%;">Nom</td>
                    <td style="font-size:14px;color:#111827;padding:10px 16px;font-weight:600;">{{ $rdv->client_nom }}</td>
                </tr>
                <tr style="border-top:1px solid #e5e7eb;">
                    <td style="font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;padding:10px 16px;background:#f9fafb;">Téléphone</td>
                    <td style="font-size:14px;color:#111827;padding:10px 16px;font-weight:600;">
                        <a href="tel:{{ $rdv->client_telephone }}" style="color:#9333ea;text-decoration:none;">{{ $rdv->client_telephone }}</a>
                    </td>
                </tr>
                @if($rdv->client_email)
                <tr style="border-top:1px solid #e5e7eb;">
                    <td style="font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;padding:10px 16px;background:#f9fafb;">E-mail</td>
                    <td style="font-size:14px;color:#111827;padding:10px 16px;">
                        <a href="mailto:{{ $rdv->client_email }}" style="color:#9333ea;text-decoration:none;">{{ $rdv->client_email }}</a>
                    </td>
                </tr>
                @endif
            </table>

            {{-- RDV --}}
            <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#6b7280;margin-bottom:12px;">Détails du rendez-vous demandé</p>
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
            <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#6b7280;margin-bottom:12px;">Prestation(s) souhaitée(s)</p>
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
                @foreach($rdv->prestations as $p)
                <tr>
                    <td style="padding:5px 0;font-size:14px;color:#374151;">
                        <span style="display:inline-block;width:8px;height:8px;background:linear-gradient(135deg,#9333ea,#ec4899);border-radius:50%;margin-right:10px;vertical-align:middle;"></span>
                        {{ $p->nom }}@if($p->duree) &nbsp;<span style="color:#9ca3af;font-size:12px;">· {{ $p->duree }} min</span>@endif
                        @if($p->prix) &nbsp;<span style="color:#9ca3af;font-size:12px;">· {{ number_format($p->prix, 0, ',', ' ') }} F</span>@endif
                    </td>
                </tr>
                @endforeach
            </table>
            @endif

            @if($rdv->notes)
            <div style="background:#f9fafb;border-radius:10px;padding:14px 16px;margin-bottom:28px;font-size:13px;color:#6b7280;">
                <strong style="color:#374151;">Message du client :</strong> {{ $rdv->notes }}
            </div>
            @endif

            {{-- CTA --}}
            <div style="text-align:center;margin-top:8px;">
                <a href="{{ config('app.url') }}/dashboard/rdv" style="display:inline-block;background:linear-gradient(135deg,#9333ea,#ec4899);color:#fff;font-size:14px;font-weight:700;padding:12px 28px;border-radius:10px;text-decoration:none;">
                    Voir dans le tableau de bord
                </a>
            </div>
        </td>
    </tr>

    {{-- FOOTER --}}
    <tr>
        <td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:20px 32px;text-align:center;font-size:12px;color:#9ca3af;">
            <p style="margin:0 0 4px;"><strong>{{ $rdv->institut?->nom ?? config('app.name') }}</strong></p>
            <p style="margin:0 0 4px;color:#d1d5db;">{{ config('app.name') }} · Gestion de salon de beauté</p>
            <p style="margin:0;">Demande reçue via la page vitrine publique.</p>
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>
