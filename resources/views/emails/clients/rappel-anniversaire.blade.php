<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rappel anniversaires clients</title>
</head>
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f4f4f7;color:#333;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;">
<tr><td align="center" style="padding:32px 16px;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);max-width:600px;">

    {{-- HEADER --}}
    <tr>
        <td style="background:linear-gradient(135deg,#a855f7,#ec4899);padding:36px 32px;text-align:center;">
            <div style="width:56px;height:56px;background:rgba(255,255,255,0.2);border-radius:16px;display:inline-block;text-align:center;line-height:56px;margin-bottom:16px;font-size:28px;">🎂</div>
            <h1 style="color:#fff;font-size:22px;font-weight:700;margin:0 0 6px;">
                {{ $clients->count() }} anniversaire{{ $clients->count() > 1 ? 's' : '' }} dans {{ $jours }} jours
            </h1>
            <p style="color:rgba(255,255,255,0.85);font-size:14px;margin:0 0 4px;">
                Pensez à chouchouter vos client{{ $clients->count() > 1 ? 's' : '' }} ce jour-là !
            </p>
            <p style="color:rgba(255,255,255,0.7);font-size:13px;margin:0;">{{ $institutNom }}</p>
        </td>
    </tr>

    {{-- BODY --}}
    <tr>
        <td style="padding:32px;">

            <p style="font-size:15px;color:#374151;margin:0 0 20px;">
                Bonjour,<br><br>
                Voici la liste de vos clients dont l'anniversaire est dans <strong>{{ $jours }} jours</strong> :
            </p>

            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin-bottom:28px;">
                <tr style="background:#faf5ff;">
                    <td style="font-size:11px;font-weight:700;text-transform:uppercase;color:#7c3aed;padding:10px 16px;">Client</td>
                    <td style="font-size:11px;font-weight:700;text-transform:uppercase;color:#7c3aed;padding:10px 16px;">Téléphone</td>
                    <td style="font-size:11px;font-weight:700;text-transform:uppercase;color:#7c3aed;padding:10px 16px;text-align:right;">Points fidélité</td>
                </tr>
                @foreach($clients as $client)
                <tr style="{{ $loop->even ? 'background:#faf5ff;' : '' }}">
                    <td style="font-size:14px;color:#111827;padding:12px 16px;font-weight:600;">
                        {{ trim($client->prenom . ' ' . $client->nom) }}
                    </td>
                    <td style="font-size:14px;color:#6b7280;padding:12px 16px;">
                        {{ $client->telephone ?? '—' }}
                    </td>
                    <td style="font-size:14px;color:#7c3aed;padding:12px 16px;text-align:right;font-weight:700;">
                        {{ $client->points_fidelite }} pts
                    </td>
                </tr>
                @endforeach
            </table>

            <div style="background:#fdf4ff;border:1px solid #e9d5ff;border-radius:10px;padding:14px 16px;margin-bottom:24px;font-size:14px;color:#6b21a8;">
                💡 &nbsp;Idée : envoyez-leur un <strong>code de réduction spécial anniversaire</strong> depuis votre espace Maëlya → Remises &amp; Avoirs.
            </div>

            <div style="text-align:center;margin-top:8px;">
                <a href="{{ config('app.url') }}/dashboard/clients?mois_anniv={{ now()->addDays($jours)->format('m') }}"
                   style="display:inline-block;background:linear-gradient(135deg,#a855f7,#ec4899);color:#fff;text-decoration:none;padding:12px 28px;border-radius:10px;font-weight:700;font-size:14px;">
                    Voir mes clients du mois →
                </a>
            </div>

        </td>
    </tr>

    {{-- FOOTER --}}
    <tr>
        <td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:20px 32px;text-align:center;font-size:12px;color:#9ca3af;">
            <p style="margin:0 0 4px;"><strong>{{ $institutNom }}</strong></p>
            <p style="margin:0 0 4px;color:#d1d5db;">{{ config('app.name') }} · Gestion de salon de beauté</p>
            <p style="margin:0;">Cet e-mail a été envoyé automatiquement. Ne pas répondre directement.</p>
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>
