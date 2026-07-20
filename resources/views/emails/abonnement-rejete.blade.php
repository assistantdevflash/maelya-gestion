<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande non validée</title>
</head>
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f4f4f7;color:#333;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;">
<tr><td align="center" style="padding:32px 16px;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);max-width:600px;">

    {{-- ══ HEADER ══ --}}
    <tr>
        <td bgcolor="#dc2626" style="background-color:#dc2626;background:linear-gradient(135deg,#dc2626,#f97316);padding:36px 32px;text-align:center;">
            <div style="width:56px;height:56px;background:rgba(255,255,255,0.2);border-radius:16px;display:inline-block;text-align:center;line-height:56px;margin-bottom:16px;font-size:28px;">❌</div>
            <h1 style="color:#ffffff !important;font-size:22px;font-weight:700;margin:0 0 6px;">Demande non validée</h1>
            <p style="color:rgba(255,255,255,0.85) !important;font-size:14px;margin:0;">Bonjour {{ $abonnement->user->prenom ?? $abonnement->user->name }},</p>
        </td>
    </tr>

    {{-- ══ BODY ══ --}}
    <tr>
        <td style="padding:32px;">

            <p style="font-size:14px;color:#374151;margin:0 0 20px;line-height:1.6;">
                Nous avons examiné votre demande d'abonnement <strong>{{ $abonnement->plan?->nom ?? '' }}</strong> et nous ne sommes malheureusement pas en mesure de la valider pour le moment.
            </p>

            @if($abonnement->notes_admin)
            <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;padding:16px 18px;margin-bottom:24px;">
                <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#dc2626;margin:0 0 8px;">Motif communiqué</p>
                <p style="font-size:14px;color:#7f1d1d;margin:0;line-height:1.5;">{{ $abonnement->notes_admin }}</p>
            </div>
            @endif

            <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:16px 18px;margin-bottom:28px;">
                <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#6b7280;margin:0 0 10px;">Votre demande</p>
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="font-size:13px;color:#6b7280;padding:4px 0;">Plan</td>
                        <td style="font-size:13px;color:#111827;font-weight:600;text-align:right;">{{ $abonnement->plan?->nom ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td style="font-size:13px;color:#6b7280;padding:4px 0;">Montant</td>
                        <td style="font-size:13px;color:#111827;font-weight:600;text-align:right;">{{ number_format($abonnement->montant, 2, ',', ' ') }} €</td>
                    </tr>
                    <tr>
                        <td style="font-size:13px;color:#6b7280;padding:4px 0;">Date de demande</td>
                        <td style="font-size:13px;color:#111827;font-weight:600;text-align:right;">{{ $abonnement->created_at->format('d/m/Y') }}</td>
                    </tr>
                </table>
            </div>

            <p style="font-size:14px;color:#374151;margin:0 0 24px;line-height:1.6;">
                Si vous pensez qu'il s'agit d'une erreur ou souhaitez plus d'informations, n'hésitez pas à nous contacter ou à soumettre une nouvelle demande depuis votre espace.
            </p>

            {{-- CTA --}}
            <div style="text-align:center;margin:28px 0 8px;">
                <a href="{{ config('app.url') }}/abonnement/plans"
                   style="display:inline-block;padding:14px 36px;background:linear-gradient(135deg,#9333ea,#ec4899);color:#fff;text-decoration:none;border-radius:10px;font-weight:700;font-size:15px;">
                    Voir les plans disponibles
                </a>
            </div>

        </td>
    </tr>

    {{-- ══ FOOTER ══ --}}
    <tr>
        <td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:20px 32px;text-align:center;font-size:12px;color:#9ca3af;">
            <p style="margin:0;">© {{ date('Y') }} {{ config('app.name') }} · Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>
