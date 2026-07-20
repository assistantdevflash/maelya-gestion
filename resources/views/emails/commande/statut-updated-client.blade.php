<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande {{ $commande->numero }} — mise à jour</title>
</head>
@php
    $statutLabels = [
        'nouvelle' => '📦 Nouvelle commande',
        'acceptee' => '✅ Acceptée',
        'en_preparation' => '👨‍🍳 En préparation',
        'en_livraison' => '🚚 En cours de livraison',
        'livree' => '✅ Livrée',
        'annulee' => '❌ Annulée',
        'refusee' => '❌ Refusée',
    ];
    $statutLabel = $statutLabels[$commande->statut] ?? $commande->statut;

    $gradient = match($commande->statut) {
        'livree' => 'linear-gradient(135deg,#10b981,#34d399)',
        'en_livraison' => 'linear-gradient(135deg,#3b82f6,#6366f1)',
        'annulee', 'refusee' => 'linear-gradient(135deg,#ef4444,#f97316)',
        default => 'linear-gradient(135deg,#9333ea,#ec4899)',
    };
    $icon = match($commande->statut) {
        'livree' => '✅',
        'en_livraison' => '🚚',
        'annulee', 'refusee' => '❌',
        'en_preparation' => '👨‍🍳',
        'acceptee' => '✅',
        default => '📦',
    };
@endphp
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f4f4f7;color:#333;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;">
<tr><td align="center" style="padding:32px 16px;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);max-width:600px;">

    {{-- HEADER --}}
    <tr>
        <td style="background:{{ $gradient }};padding:36px 32px;text-align:center;">
            <div style="width:56px;height:56px;background:rgba(255,255,255,0.2);border-radius:16px;display:inline-block;text-align:center;line-height:56px;margin-bottom:16px;font-size:28px;">{{ $icon }}</div>
            <h1 style="color:#fff;font-size:22px;font-weight:700;margin:0 0 6px;">{{ $statutLabel }}</h1>
            <p style="color:rgba(255,255,255,0.85);font-size:14px;margin:0 0 4px;">Bonjour {{ $commande->client_prenom }} {{ $commande->client_nom }}, votre commande <strong>{{ $commande->numero }}</strong> a été mise à jour.</p>
            <p style="color:rgba(255,255,255,0.7);font-size:13px;margin:0;">{{ $commande->institut->nom }}</p>
        </td>
    </tr>

    {{-- BODY --}}
    <tr>
        <td style="padding:32px;">
            {{-- Message contextuel selon le statut --}}
            @if($commande->statut === 'en_livraison')
            <div style="background:#eff6ff;border:1px solid #93c5fd;border-radius:10px;padding:14px 16px;margin-bottom:24px;font-size:14px;color:#1e40af;">
                🚚 Votre commande est en route ! Vous devriez la recevoir très bientôt.
            </div>
            @elseif($commande->statut === 'livree')
            <div style="background:#ecfdf5;border:1px solid #6ee7b7;border-radius:10px;padding:14px 16px;margin-bottom:24px;font-size:14px;color:#065f46;">
                ✅ Votre commande a été livrée. Merci de votre confiance !
            </div>
            @elseif($commande->statut === 'annulee' || $commande->statut === 'refusee')
            <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;padding:14px 16px;margin-bottom:24px;font-size:14px;color:#991b1b;">
                ❌ Nous sommes désolés, votre commande a été {{ $commande->statut === 'annulee' ? 'annulée' : 'refusée' }}.
            </div>
            @else
            <div style="background:#faf5ff;border:1px solid #e9d5ff;border-radius:10px;padding:14px 16px;margin-bottom:24px;font-size:14px;color:#6b21a8;">
                Votre commande passe à l'étape suivante — <strong>{{ $statutLabel }}</strong>.
            </div>
            @endif

            {{-- Résumé commande --}}
            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin-bottom:28px;">
                <tr style="background:#f9fafb;">
                    <td style="font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;padding:10px 16px;">Commande</td>
                    <td style="font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;padding:10px 16px;text-align:right;">Total</td>
                </tr>
                <tr>
                    <td style="font-size:14px;color:#111827;padding:14px 16px;font-weight:600;">{{ $commande->numero }}</td>
                    <td style="font-size:18px;color:#9333ea;padding:14px 16px;text-align:right;font-weight:800;">{{ number_format($commande->total, 0, ',', ' ') }} FCFA</td>
                </tr>
            </table>

            {{-- Bouton suivi --}}
            <div style="text-align:center;margin:32px 0 8px;">
                <a href="{{ route('shop.suivi', ['slug' => $commande->institut->slug, 'numero' => $commande->numero]) }}"
                   style="display:inline-block;padding:14px 40px;background:{{ $gradient }};color:#fff;text-decoration:none;border-radius:10px;font-weight:700;font-size:15px;">
                    📦 Voir ma commande
                </a>
            </div>

            <p style="font-size:13px;color:#6b7280;text-align:center;margin-top:8px;">Pour toute question, contactez directement l'établissement.</p>
        </td>
    </tr>

    {{-- FOOTER --}}
    <tr>
        <td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:20px 32px;text-align:center;font-size:12px;color:#9ca3af;">
            <p style="margin:0 0 4px;"><strong style="color:#6b7280;">{{ $commande->institut->nom }}</strong></p>
            <p style="margin:0 0 4px;color:#9ca3af;">
                @if($commande->institut->telephone)📞 {{ $commande->institut->telephone }} &nbsp;·&nbsp; @endif
                @if($commande->institut->email)✉️ {{ $commande->institut->email }} &nbsp;·&nbsp; @endif
                @if($commande->institut->ville)📍 {{ $commande->institut->ville }}@endif
            </p>
            <p style="margin:0;font-size:10px;color:#c4c4c4;">Cet e-mail a été envoyé automatiquement par Maëlya Gestion.</p>
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>
