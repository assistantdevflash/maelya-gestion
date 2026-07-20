<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de commande {{ $commande->numero }}</title>
</head>
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f4f4f7;color:#333;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;">
<tr><td align="center" style="padding:32px 16px;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);max-width:600px;">

    {{-- HEADER --}}
    <tr>
        <td bgcolor="#9333ea" style="background-color:#9333ea;background:linear-gradient(135deg,#9333ea,#ec4899);padding:36px 32px;text-align:center;">
            <div style="width:56px;height:56px;background:rgba(255,255,255,0.2);border-radius:16px;display:inline-block;text-align:center;line-height:56px;margin-bottom:16px;font-size:28px;">🛒</div>
            <h1 style="color:#ffffff !important;font-size:22px;font-weight:700;margin:0 0 6px;">Merci pour votre commande !</h1>
            <p style="color:rgba(255,255,255,0.85) !important;font-size:14px;margin:0 0 4px;">Bonjour {{ $commande->client_prenom }} {{ $commande->client_nom }}</p>
            <p style="color:rgba(255,255,255,0.7) !important;font-size:13px;margin:0;">{{ $institut->nom }}</p>
        </td>
    </tr>

    {{-- BODY --}}
    <tr>
        <td style="padding:32px;">
            {{-- Bandeau numéro de commande --}}
            <div style="background:#faf5ff;border:1px solid #e9d5ff;border-radius:10px;padding:12px 16px;margin-bottom:24px;font-size:14px;color:#6b21a8;">
                ✅ Commande <strong>{{ $commande->numero }}</strong> — en attente de traitement
            </div>

            {{-- Tableau produits --}}
            <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#9333ea;margin-bottom:12px;">Récapitulatif</p>
            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin-bottom:28px;">
                <tr style="background:#f9fafb;">
                    <td style="font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;padding:10px 16px;">Produit</td>
                    <td style="font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;padding:10px 16px;text-align:center;">Qté</td>
                    <td style="font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;padding:10px 16px;text-align:right;">Prix unit.</td>
                    <td style="font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;padding:10px 16px;text-align:right;">Sous-total</td>
                </tr>
                @foreach($commande->items as $item)
                <tr>
                    <td style="font-size:14px;color:#111827;padding:12px 16px;font-weight:500;">{{ $item->nom_snapshot }}</td>
                    <td style="font-size:14px;color:#111827;padding:12px 16px;text-align:center;">{{ $item->quantite }}</td>
                    <td style="font-size:14px;color:#6b7280;padding:12px 16px;text-align:right;">{{ number_format($item->prix_snapshot, 0, ',', ' ') }} FCFA</td>
                    <td style="font-size:14px;color:#111827;padding:12px 16px;text-align:right;font-weight:600;">{{ number_format($item->sous_total, 0, ',', ' ') }} FCFA</td>
                </tr>
                @endforeach
                <tr style="background:#faf5ff;">
                    <td colspan="3" style="font-size:13px;color:#6b21a8;padding:14px 16px;font-weight:600;text-align:right;">Sous-total produits</td>
                    <td style="font-size:14px;color:#6b21a8;padding:14px 16px;text-align:right;font-weight:700;">{{ number_format($commande->sous_total, 0, ',', ' ') }} FCFA</td>
                </tr>
                <tr>
                    <td colspan="3" style="font-size:13px;color:#6b7280;padding:10px 16px;text-align:right;">Frais de livraison</td>
                    <td style="font-size:14px;color:#6b7280;padding:10px 16px;text-align:right;">{{ number_format($commande->frais_livraison, 0, ',', ' ') }} FCFA</td>
                </tr>
                <tr style="background:#f9fafb;">
                    <td colspan="3" style="font-size:14px;color:#111827;padding:14px 16px;font-weight:700;text-align:right;">Total à payer</td>
                    <td style="font-size:18px;color:#9333ea;padding:14px 16px;text-align:right;font-weight:800;">{{ number_format($commande->total, 0, ',', ' ') }} FCFA</td>
                </tr>
            </table>

            {{-- Livraison --}}
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
                <tr>
                    <td style="width:50%;vertical-align:top;padding-right:16px;">
                        <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#9333ea;margin-bottom:8px;">📍 Adresse de livraison</p>
                        <p style="font-size:14px;color:#374151;margin:0;line-height:1.6;">{{ $commande->client_adresse }}</p>
                    </td>
                    <td style="width:50%;vertical-align:top;">
                        <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#9333ea;margin-bottom:8px;">💳 Mode de paiement</p>
                        <p style="font-size:14px;color:#374151;margin:0;">💵 <strong>Cash à la livraison</strong></p>
                    </td>
                </tr>
            </table>

            @if($commande->notes_client)
            <div style="background:#f9fafb;border-radius:10px;padding:14px 16px;margin-bottom:28px;font-size:13px;color:#6b7280;">
                <strong style="color:#374151;">Votre message :</strong> {{ $commande->notes_client }}
            </div>
            @endif

            {{-- Bouton suivi --}}
            <div style="text-align:center;margin:32px 0 8px;">
                <a href="{{ route('shop.suivi', ['slug' => $institut->slug, 'numero' => $commande->numero]) }}"
                   style="display:inline-block;padding:14px 40px;background:linear-gradient(135deg,#9333ea,#ec4899);color:#fff;text-decoration:none;border-radius:10px;font-weight:700;font-size:15px;">
                    📦 Suivre ma commande
                </a>
            </div>

            <p style="font-size:13px;color:#6b7280;text-align:center;margin-top:8px;">Vous recevrez un email dès que votre commande sera expédiée.</p>
        </td>
    </tr>

    {{-- FOOTER --}}
    <tr>
        <td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:20px 32px;text-align:center;font-size:12px;color:#9ca3af;">
            <p style="margin:0 0 4px;"><strong style="color:#6b7280;">{{ $institut->nom }}</strong></p>
            <p style="margin:0 0 4px;color:#9ca3af;">
                @if($institut->telephone)📞 {{ $institut->telephone }} &nbsp;·&nbsp; @endif
                @if($institut->email)✉️ {{ $institut->email }} &nbsp;·&nbsp; @endif
                @if($institut->ville)📍 {{ $institut->ville }}@endif
            </p>
            <p style="margin:0;font-size:10px;color:#c4c4c4;">Cet e-mail a été envoyé automatiquement par Maëlya Gestion.</p>
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>
