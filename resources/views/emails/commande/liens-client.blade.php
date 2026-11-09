<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vos commandes</title>
</head>
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f4f4f7;color:#333;">
@php
    $cp = $institut->couleur_primaire ?? '#7c3aed';
    $cs = $institut->couleur_secondaire ?? '#a855f7';
    $libelles = [
        'nouvelle'       => 'Nouvelle',
        'acceptee'       => 'Acceptée',
        'en_preparation' => 'En préparation',
        'en_livraison'   => 'En livraison',
        'livree'         => 'Livrée',
        'annulee'        => 'Annulée',
    ];
@endphp
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;">
<tr><td align="center" style="padding:32px 16px;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);max-width:600px;">

    <tr>
        <td style="background-color:{{ $cp }};background:linear-gradient(135deg,{{ $cp }},{{ $cs }});padding:32px;text-align:center;">
            <h1 style="color:#ffffff !important;font-size:21px;font-weight:700;margin:0 0 6px;">Vos commandes</h1>
            <p style="color:rgba(255,255,255,0.85) !important;font-size:13px;margin:0;">{{ $institut->nom }}</p>
        </td>
    </tr>

    <tr>
        <td style="padding:32px;">
            <p style="font-size:14px;color:#374151;line-height:1.7;margin:0 0 24px;">
                Bonjour,<br><br>
                Voici les commandes enregistrées avec vos coordonnées. Chaque lien vous permet
                de suivre l'avancement, d'annuler une commande encore en attente ou de télécharger
                votre justificatif.
            </p>

            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin-bottom:24px;">
                @foreach($lignes as $i => $ligne)
                <tr>
                    <td style="padding:16px 18px;{{ $loop->last ? '' : 'border-bottom:1px solid #f3f4f6;' }}">
                        <p style="margin:0 0 4px;font-size:14px;font-weight:700;color:#111827;">
                            {{ $ligne['commande']->numero }}
                        </p>
                        <p style="margin:0 0 10px;font-size:12px;color:#6b7280;">
                            {{ $ligne['commande']->created_at?->format('d/m/Y') }}
                            · {{ $libelles[$ligne['commande']->statut] ?? $ligne['commande']->statut }}
                            · {{ number_format($ligne['commande']->total, 0, ',', ' ') }} F CFA
                        </p>
                        <a href="{{ $ligne['lien'] }}"
                           style="display:inline-block;background-color:{{ $cp }};color:#ffffff !important;text-decoration:none;font-size:13px;font-weight:600;padding:10px 20px;border-radius:8px;">
                            Suivre cette commande
                        </a>
                    </td>
                </tr>
                @endforeach
            </table>

            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center">
                        <a href="{{ $boutique }}"
                           style="display:inline-block;color:{{ $cp }};text-decoration:underline;font-size:13px;">
                            Retourner à la boutique
                        </a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <tr>
        <td style="padding:20px 32px;background:#f9fafb;text-align:center;">
            <p style="font-size:11px;color:#9ca3af;margin:0;">
                {{ $institut->nom }}{{ $institut->telephone ? ' · ' . $institut->telephone : '' }}
            </p>
            <p style="font-size:11px;color:#9ca3af;margin:6px 0 0;">
                Vous recevez cet email parce qu'une demande de récupération de liens a été faite
                depuis la boutique. Si vous n'êtes pas à l'origine de cette demande, ignorez ce message.
            </p>
        </td>
    </tr>
</table>
</td></tr>
</table>
</body>
</html>
