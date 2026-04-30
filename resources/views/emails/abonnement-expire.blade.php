<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abonnement expiré</title>
</head>
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f4f4f7;color:#333;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;">
<tr><td align="center" style="padding:32px 16px;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);max-width:600px;">

    {{-- ══ HEADER ══ --}}
    <tr>
        <td style="background:linear-gradient(135deg,#ef4444,#f97316);padding:36px 32px;text-align:center;">
            <div style="width:56px;height:56px;background:rgba(255,255,255,0.2);border-radius:16px;display:inline-block;text-align:center;line-height:56px;margin-bottom:16px;font-size:28px;">⏰</div>
            <h1 style="color:#fff;font-size:22px;font-weight:700;margin:0 0 6px;">Votre abonnement a expiré</h1>
            <p style="color:rgba(255,255,255,0.85);font-size:14px;margin:0;">Bonjour {{ $abonnement->user->prenom ?? $abonnement->user->name }}, votre accès est désormais en lecture seule.</p>
        </td>
    </tr>

    {{-- ══ BODY ══ --}}
    <tr>
        <td style="padding:32px;">

            {{-- Alerte --}}
            <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;padding:14px 16px;margin-bottom:28px;font-size:14px;color:#7f1d1d;">
                ⚠️&nbsp; Votre abonnement <strong>{{ $abonnement->plan->nom ?? 'Premium' }}</strong> a expiré le <strong>{{ $abonnement->expire_le?->format('d/m/Y') }}</strong>.
                Votre espace est maintenant en <strong>mode lecture seule</strong> : vous pouvez consulter vos données mais plus les modifier.
            </div>

            {{-- Détails abonnement --}}
            <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#ef4444;margin-bottom:12px;">Abonnement expiré</p>
            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin-bottom:28px;">
                <tr style="background:#f9fafb;">
                    <td style="font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;padding:10px 16px;">Plan</td>
                    <td style="font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;padding:10px 16px;">Expiré le</td>
                </tr>
                <tr>
                    <td style="font-size:14px;color:#111827;padding:14px 16px;font-weight:600;">{{ $abonnement->plan->nom ?? '—' }}</td>
                    <td style="font-size:14px;color:#ef4444;padding:14px 16px;font-weight:700;">{{ $abonnement->expire_le?->format('d/m/Y') ?? '—' }}</td>
                </tr>
            </table>

            {{-- Ce qui est bloqué --}}
            <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#6b7280;margin-bottom:12px;">Ce qui est restreint</p>
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
                @foreach(['Enregistrement de nouvelles ventes', 'Ajout et modification de clients', 'Gestion des stocks et prestations', 'Création de codes de réduction'] as $item)
                <tr>
                    <td style="padding:5px 0;font-size:14px;color:#374151;">
                        <span style="display:inline-block;width:20px;height:20px;background:#fee2e2;border-radius:50%;text-align:center;line-height:20px;font-size:11px;margin-right:10px;vertical-align:middle;">✕</span>
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
            <p style="text-align:center;font-size:12px;color:#9ca3af;margin-top:12px;">
                Vos données sont conservées intégralement. La réactivation est instantanée après validation.
            </p>

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
