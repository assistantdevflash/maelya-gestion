<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau RDV</title>
</head>
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f4f4f7;color:#333;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;">
<tr><td align="center" style="padding:32px 16px;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);max-width:600px;">

    {{-- HEADER --}}
    <tr>
        <td style="background:linear-gradient(135deg,#6366f1,#8b5cf6);padding:36px 32px;text-align:center;">
            <div style="width:56px;height:56px;background:rgba(255,255,255,0.2);border-radius:16px;display:inline-block;text-align:center;line-height:56px;margin-bottom:16px;font-size:28px;">📅</div>
            <h1 style="color:#fff;font-size:22px;font-weight:700;margin:0 0 6px;">Nouveau rendez-vous ajouté</h1>
            <p style="color:rgba(255,255,255,0.85);font-size:14px;margin:0;">{{ $rdv->debut_le->translatedFormat('l d F Y') }} à {{ $rdv->debut_le->format('H\hi') }}</p>
        </td>
    </tr>

    {{-- BODY --}}
    <tr>
        <td style="padding:32px;">
            <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#6366f1;margin-bottom:12px;">Client</p>
            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin-bottom:28px;">
                <tr>
                    <td style="font-size:14px;color:#111827;padding:14px 16px;font-weight:600;">{{ $rdv->client_nom }}</td>
                    <td style="font-size:13px;color:#6b7280;padding:14px 16px;text-align:right;">
                        @if($rdv->client_telephone) 📞 {{ $rdv->client_telephone }}@endif
                    </td>
                </tr>
            </table>

            <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#6b7280;margin-bottom:12px;">Détails</p>
            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin-bottom:28px;">
                <tr style="background:#f9fafb;">
                    <td style="font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;padding:10px 16px;">Date &amp; heure</td>
                    <td style="font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;padding:10px 16px;">Durée</td>
                    <td style="font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;padding:10px 16px;">Statut</td>
                </tr>
                <tr>
                    <td style="font-size:14px;color:#6366f1;padding:14px 16px;font-weight:700;">{{ $rdv->debut_le->format('d/m/Y à H\hi') }}</td>
                    <td style="font-size:14px;color:#111827;padding:14px 16px;">{{ $rdv->duree_minutes }} min</td>
                    <td style="font-size:13px;padding:14px 16px;">
                        <span style="background:#ede9fe;color:#6d28d9;padding:2px 10px;border-radius:9999px;font-size:11px;font-weight:700;">
                            {{ ucfirst(str_replace('_', ' ', $rdv->statut)) }}
                        </span>
                    </td>
                </tr>
            </table>

            @if($rdv->prestations->isNotEmpty())
            <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#6b7280;margin-bottom:12px;">Prestation(s)</p>
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
                @foreach($rdv->prestations as $p)
                <tr>
                    <td style="padding:4px 0;font-size:14px;color:#374151;">
                        · {{ $p->nom }}@if($p->duree) <span style="color:#9ca3af;">({{ $p->duree }} min)</span>@endif
                    </td>
                </tr>
                @endforeach
            </table>
            @elseif($rdv->prestation_libre)
            <p style="font-size:14px;color:#374151;margin-bottom:28px;"><strong>Prestation :</strong> {{ $rdv->prestation_libre }}</p>
            @endif

            @if($rdv->notes)
            <div style="background:#f9fafb;border-radius:10px;padding:14px 16px;margin-bottom:28px;font-size:13px;color:#6b7280;">
                <strong style="color:#374151;">Note :</strong> {{ $rdv->notes }}
            </div>
            @endif

            <div style="text-align:center;margin:32px 0 8px;">
                <a href="{{ url('/dashboard/rdv/' . $rdv->id) }}"
                   style="display:inline-block;padding:14px 40px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;text-decoration:none;border-radius:10px;font-weight:700;font-size:15px;">
                    Voir le rendez-vous →
                </a>
            </div>
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
