<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rappel abonnement</title>
</head>
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f4f4f7;color:#333;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;">
<tr><td align="center" style="padding:32px 16px;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);max-width:600px;">

    {{-- ══ HEADER ══ --}}
    <tr>
        <td style="background:linear-gradient(135deg,#f59e0b,#f97316);padding:36px 32px;text-align:center;">
            <div style="width:56px;height:56px;background:rgba(255,255,255,0.2);border-radius:16px;display:inline-block;text-align:center;line-height:56px;margin-bottom:16px;font-size:28px;">⏳</div>
            <h1 style="color:#fff;font-size:22px;font-weight:700;margin:0 0 6px;">
                @if($joursRestants === 1)
                    Votre abonnement expire demain
                @else
                    Votre abonnement expire dans {{ $joursRestants }} jours
                @endif
            </h1>
            <p style="color:rgba(255,255,255,0.85);font-size:14px;margin:0;">Bonjour {{ $abonnement->user->prenom ?? $abonnement->user->name }}, pensez à renouveler pour ne pas interrompre votre activité.</p>
        </td>
    </tr>

    {{-- ══ BODY ══ --}}
    <tr>
        <td style="padding:32px;">

            {{-- Alerte --}}
            <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:10px;padding:14px 16px;margin-bottom:28px;font-size:14px;color:#78350f;">
                ⏳&nbsp; Votre abonnement <strong>{{ $abonnement->plan->nom ?? 'Premium' }}</strong> expire le <strong>{{ $abonnement->expire_le?->format('d/m/Y') }}</strong>.
                @if($joursRestants === 1)
                    C'est <strong>demain</strong> — renouvelez maintenant pour éviter toute interruption.
                @else
                    Il vous reste <strong>{{ $joursRestants }} jours</strong> pour renouveler.
                @endif
            </div>

            {{-- Détails abonnement --}}
            <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#f59e0b;margin-bottom:12px;">Abonnement en cours</p>
            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin-bottom:28px;">
                <tr style="background:#f9fafb;">
                    <td style="font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;padding:10px 16px;">Plan</td>
                    <td style="font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;padding:10px 16px;">Expire le</td>
                </tr>
                <tr>
                    <td style="font-size:14px;color:#111827;padding:14px 16px;font-weight:600;">{{ $abonnement->plan->nom ?? '—' }}</td>
                    <td style="font-size:14px;color:#f59e0b;padding:14px 16px;font-weight:700;">{{ $abonnement->expire_le?->format('d/m/Y') ?? '—' }}</td>
                </tr>
            </table>

            {{-- Ce qui se passe à l'expiration --}}
            <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#6b7280;margin-bottom:12px;">Si vous ne renouvelez pas</p>
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
                @foreach(['Votre espace passera en mode lecture seule', 'Plus de nouvelles ventes ni de modifications', 'Vos données restent conservées intégralement', 'La réactivation est instantanée après paiement'] as $item)
                <tr>
                    <td style="padding:5px 0;font-size:14px;color:#374151;">
                        <span style="display:inline-block;width:20px;height:20px;background:#fef3c7;border-radius:50%;text-align:center;line-height:20px;font-size:11px;margin-right:10px;vertical-align:middle;">!</span>
                        {{ $item }}
                    </td>
                </tr>
                @endforeach
            </table>

            {{-- CTA --}}
            <div style="text-align:center;margin:32px 0 8px;">
                <a href="{{ route('abonnement.plans') }}"
                   style="display:inline-block;padding:14px 40px;background:linear-gradient(135deg,#9333ea,#ec4899);color:#fff;text-decoration:none;border-radius:10px;font-weight:700;font-size:15px;">
                    Renouveler mon abonnement →
                </a>
            </div>

        </td>
    </tr>

    {{-- ══ FOOTER ══ --}}
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
