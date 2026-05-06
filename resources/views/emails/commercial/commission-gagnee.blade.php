<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle commission générée</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f7;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:#333;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;">
<tr><td align="center" style="padding:32px 16px;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);max-width:600px;">

    {{-- HEADER --}}
    <tr>
        <td style="background:linear-gradient(135deg,#9333ea,#ec4899);padding:36px 32px;text-align:center;">
            <div style="width:56px;height:56px;background:rgba(255,255,255,0.2);border-radius:16px;display:inline-block;text-align:center;line-height:56px;margin-bottom:16px;font-size:28px;">💰</div>
            <h1 style="color:#fff;font-size:22px;font-weight:700;margin:0 0 6px;">Nouvelle commission générée !</h1>
            <p style="color:rgba(255,255,255,0.85);font-size:14px;margin:0;">Un filleul a souscrit à un abonnement</p>
        </td>
    </tr>

    {{-- BODY --}}
    <tr>
        <td style="padding:32px;">
            <p style="font-size:16px;margin:0 0 8px;">Bonjour <strong>{{ $commercial->prenom }}</strong>,</p>
            <p style="font-size:14px;color:#6b7280;margin:0 0 24px;">L'un de vos filleuls vient de souscrire à un abonnement payant. Une commission a été générée pour vous.</p>

            {{-- Détail commission --}}
            <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:20px 24px;margin-bottom:24px;">
                <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#9333ea;margin:0 0 12px;">Détail de la commission</p>
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="font-size:13px;color:#6b7280;padding:4px 0;">Filleul</td>
                        <td style="font-size:13px;color:#111827;font-weight:600;text-align:right;">
                            {{ $commission->abonnement->user->prenom ?? '' }} {{ $commission->abonnement->user->nom_famille ?? '' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:13px;color:#6b7280;padding:4px 0;">Plan souscrit</td>
                        <td style="font-size:13px;color:#111827;font-weight:600;text-align:right;">
                            {{ $commission->abonnement->plan->nom ?? 'N/A' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:13px;color:#6b7280;padding:4px 0;">Montant de l'abonnement</td>
                        <td style="font-size:13px;color:#111827;font-weight:600;text-align:right;">
                            {{ number_format($commission->montant_base, 2, ',', ' ') }} €
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:13px;color:#6b7280;padding:4px 0;">Taux de commission</td>
                        <td style="font-size:13px;color:#111827;font-weight:600;text-align:right;">{{ $commission->taux }} %</td>
                    </tr>
                    <tr>
                        <td style="font-size:13px;color:#6b7280;padding:6px 0 0;">Votre commission</td>
                        <td style="padding:6px 0 0;text-align:right;">
                            <span style="font-size:20px;font-weight:800;color:#9333ea;">{{ number_format($commission->montant, 2, ',', ' ') }} €</span>
                        </td>
                    </tr>
                </table>
            </div>

            <div style="background:#fef9c3;border:1px solid #fde68a;border-radius:10px;padding:14px 16px;font-size:14px;color:#713f12;margin-bottom:24px;">
                ⏳ Cette commission est en attente de versement. Vous serez notifié dès qu'elle sera payée.
            </div>

            <div style="text-align:center;margin:28px 0 8px;">
                <a href="{{ config('app.url') }}/commercial"
                   style="display:inline-block;padding:14px 36px;background:linear-gradient(135deg,#9333ea,#ec4899);color:#fff;text-decoration:none;border-radius:10px;font-weight:700;font-size:15px;">
                    Voir mes commissions
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
