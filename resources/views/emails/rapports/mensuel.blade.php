@php
    $institut = $rapport['institut'];
    $actuel   = $rapport['actuel'];
    $precedent = $rapport['precedent'];
    $evo      = $rapport['evolutions'];
    $top      = $rapport['top'];
    $conseil  = $rapport['conseil'];

    $f = fn ($v) => number_format((int) $v, 0, ',', ' ');

    // Variation : couleur + flèche + valeur, ou tiret si non comparable
    $variation = function (?float $pct, bool $inverser = false) {
        if ($pct === null) {
            return ['texte' => '—', 'couleur' => '#9ca3af', 'fleche' => ''];
        }
        $positif = $pct > 0;
        if ($pct == 0.0) {
            return ['texte' => 'stable', 'couleur' => '#6b7280', 'fleche' => ''];
        }
        // Pour les dépenses, une hausse n'est pas une bonne nouvelle
        $bon = $inverser ? !$positif : $positif;

        return [
            'texte'   => ($positif ? '+' : '') . number_format($pct, 1, ',', ' ') . ' %',
            'couleur' => $bon ? '#059669' : '#dc2626',
            'fleche'  => $positif ? '▲' : '▼',
        ];
    };

    $couleurSante = $rapport['sante']['code'] === 'positive'
        ? '#059669'
        : ($rapport['sante']['code'] === 'negative' ? '#dc2626' : '#6b7280');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilan de {{ $rapport['label'] }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f7;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;color:#333;">
<div style="max-width:600px;margin:32px auto;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

    {{-- En-tête --}}
    <div style="background:linear-gradient(135deg,#9333ea 0%,#ec4899 100%);padding:36px 32px;text-align:center;">
        <p style="margin:0;color:#ffffff;font-size:22px;font-weight:900;letter-spacing:-0.5px;">{{ $institut->nom }}</p>
        <p style="margin:6px 0 0;color:rgba(255,255,255,0.85);font-size:13px;">Bilan financier · {{ $rapport['label'] }}</p>
        <div style="display:inline-block;margin-top:16px;background:rgba(255,255,255,0.16);border-radius:12px;padding:10px 18px;">
            <span style="color:#ffffff;font-size:13px;font-weight:700;">{{ $rapport['sante']['label'] }}</span>
        </div>
    </div>

    <div style="padding:32px;">

        <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#374151;">
            Bonjour {{ $destinataire->prenom ?: $destinataire->nom }}, voici le point complet sur votre activité
            de {{ mb_strtolower($rapport['label']) }}.
        </p>

        {{-- Chiffres clés --}}
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
            <tr>
                <td width="50%" style="padding:0 6px 12px 0;vertical-align:top;">
                    <div style="background:#faf5ff;border:1px solid #e9d5ff;border-radius:12px;padding:16px;">
                        <p style="margin:0 0 6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#9333ea;">Chiffre d'affaires</p>
                        <p style="margin:0;font-size:22px;font-weight:800;color:#111827;">{{ $f($actuel['ca']) }} F</p>
                        @php $v = $variation($evo['ca']); @endphp
                        <p style="margin:6px 0 0;font-size:12px;color:{{ $v['couleur'] }};font-weight:600;">
                            {{ $v['fleche'] }} {{ $v['texte'] }} <span style="color:#9ca3af;font-weight:400;">vs {{ $rapport['label_precedent'] }}</span>
                        </p>
                    </div>
                </td>
                <td width="50%" style="padding:0 0 12px 6px;vertical-align:top;">
                    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:16px;">
                        <p style="margin:0 0 6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#dc2626;">Dépenses</p>
                        <p style="margin:0;font-size:22px;font-weight:800;color:#111827;">{{ $f($actuel['depenses']) }} F</p>
                        @php $v = $variation($evo['depenses'], true); @endphp
                        <p style="margin:6px 0 0;font-size:12px;color:{{ $v['couleur'] }};font-weight:600;">
                            {{ $v['fleche'] }} {{ $v['texte'] }} <span style="color:#9ca3af;font-weight:400;">vs {{ $rapport['label_precedent'] }}</span>
                        </p>
                    </div>
                </td>
            </tr>
            <tr>
                <td width="50%" style="padding:0 6px 0 0;vertical-align:top;">
                    <div style="background:{{ $actuel['benefice'] >= 0 ? '#ecfdf5' : '#fef2f2' }};border:1px solid {{ $actuel['benefice'] >= 0 ? '#a7f3d0' : '#fecaca' }};border-radius:12px;padding:16px;">
                        <p style="margin:0 0 6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:{{ $actuel['benefice'] >= 0 ? '#059669' : '#dc2626' }};">Bénéfice net</p>
                        <p style="margin:0;font-size:22px;font-weight:800;color:#111827;">{{ $f($actuel['benefice']) }} F</p>
                        @php $v = $variation($evo['benefice']); @endphp
                        <p style="margin:6px 0 0;font-size:12px;color:{{ $v['couleur'] }};font-weight:600;">
                            {{ $v['fleche'] }} {{ $v['texte'] }} <span style="color:#9ca3af;font-weight:400;">· marge {{ $actuel['marge'] }} %</span>
                        </p>
                    </div>
                </td>
                <td width="50%" style="padding:0 0 0 6px;vertical-align:top;">
                    <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:16px;">
                        <p style="margin:0 0 6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#6b7280;">Ventes</p>
                        <p style="margin:0;font-size:22px;font-weight:800;color:#111827;">{{ $actuel['nb_ventes'] }}</p>
                        <p style="margin:6px 0 0;font-size:12px;color:#6b7280;">
                            Panier moyen {{ $f($actuel['panier_moyen']) }} F
                        </p>
                    </div>
                </td>
            </tr>
        </table>

        {{-- Activité du mois --}}
        <p style="margin:0 0 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#9333ea;">Votre activité du mois</p>
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;border-collapse:collapse;">
            <tr>
                <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#6b7280;">Clients servis</td>
                <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:14px;font-weight:700;color:#111827;text-align:right;">{{ $actuel['clients_servis'] }}</td>
            </tr>
            <tr>
                <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#6b7280;">Nouveaux clients</td>
                <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:14px;font-weight:700;color:#111827;text-align:right;">{{ $actuel['nouveaux_clients'] }}</td>
            </tr>
            <tr>
                <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#6b7280;">Rendez-vous pris</td>
                <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:14px;font-weight:700;color:#111827;text-align:right;">{{ $actuel['rdv'] }}</td>
            </tr>
            <tr>
                <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#6b7280;">Rendez-vous honorés</td>
                <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:14px;font-weight:700;color:#111827;text-align:right;">{{ $actuel['rdv_termines'] }}</td>
            </tr>
            <tr>
                <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#6b7280;">Factures émises</td>
                <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:14px;font-weight:700;color:#111827;text-align:right;">{{ $actuel['factures'] }}</td>
            </tr>
            <tr>
                <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#6b7280;">Factures réglées</td>
                <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:14px;font-weight:700;color:#111827;text-align:right;">{{ $actuel['factures_reglees'] }}</td>
            </tr>
            <tr>
                <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#6b7280;">Commandes en ligne</td>
                <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:14px;font-weight:700;color:#111827;text-align:right;">{{ $actuel['commandes'] }}</td>
            </tr>
            <tr>
                <td style="padding:10px 0;font-size:14px;color:#6b7280;">Commandes livrées et payées</td>
                <td style="padding:10px 0;font-size:14px;font-weight:700;color:#111827;text-align:right;">{{ $actuel['commandes_livrees_payees'] }}</td>
            </tr>
        </table>

        {{-- Top prestations --}}
        @if(!empty($top['prestations']))
        <p style="margin:0 0 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#9333ea;">Vos meilleures prestations</p>
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;border-collapse:collapse;">
            @foreach($top['prestations'] as $i => $ligne)
            <tr>
                <td style="padding:9px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#374151;">
                    <span style="color:#9ca3af;font-weight:700;margin-right:8px;">{{ $i + 1 }}.</span>{{ $ligne['nom'] }}
                    <span style="color:#9ca3af;font-size:12px;"> × {{ $ligne['quantite'] }}</span>
                </td>
                <td style="padding:9px 0;border-bottom:1px solid #f3f4f6;font-size:14px;font-weight:700;color:#111827;text-align:right;white-space:nowrap;">{{ $f($ligne['ca']) }} F</td>
            </tr>
            @endforeach
        </table>
        @endif

        {{-- Top produits --}}
        @if(!empty($top['produits']))
        <p style="margin:0 0 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#9333ea;">Vos meilleurs produits</p>
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;border-collapse:collapse;">
            @foreach($top['produits'] as $i => $ligne)
            <tr>
                <td style="padding:9px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#374151;">
                    <span style="color:#9ca3af;font-weight:700;margin-right:8px;">{{ $i + 1 }}.</span>{{ $ligne['nom'] }}
                    <span style="color:#9ca3af;font-size:12px;"> × {{ $ligne['quantite'] }}</span>
                </td>
                <td style="padding:9px 0;border-bottom:1px solid #f3f4f6;font-size:14px;font-weight:700;color:#111827;text-align:right;white-space:nowrap;">{{ $f($ligne['ca']) }} F</td>
            </tr>
            @endforeach
        </table>
        @endif

        {{-- Dépenses par catégorie --}}
        @if(!empty($rapport['depenses_categories']))
        <p style="margin:0 0 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#9333ea;">Répartition des dépenses</p>
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;border-collapse:collapse;">
            @foreach($rapport['depenses_categories'] as $cat)
            <tr>
                <td style="padding:9px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#374151;">{{ $cat['label'] }}</td>
                <td style="padding:9px 0;border-bottom:1px solid #f3f4f6;font-size:13px;color:#9ca3af;text-align:right;width:60px;">{{ $cat['part'] }} %</td>
                <td style="padding:9px 0;border-bottom:1px solid #f3f4f6;font-size:14px;font-weight:700;color:#111827;text-align:right;white-space:nowrap;width:110px;">{{ $f($cat['total']) }} F</td>
            </tr>
            @endforeach
        </table>
        @endif

        {{-- Conseil marketing --}}
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
            <tr>
                <td style="background:#faf5ff;border:1px solid #e9d5ff;border-left:4px solid #9333ea;border-radius:12px;padding:20px;">
                    <p style="margin:0 0 8px;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:0.08em;color:#9333ea;">💡 Conseiller du mois</p>
                    <p style="margin:0 0 10px;font-size:16px;font-weight:800;color:#111827;">{{ $conseil['titre'] }}</p>
                    <p style="margin:0 0 12px;font-size:14px;line-height:1.65;color:#4b5563;">{{ $conseil['texte'] }}</p>
                    <p style="margin:0;font-size:14px;line-height:1.65;color:#7e22ce;font-weight:600;">{{ $conseil['action'] }}</p>
                </td>
            </tr>
        </table>

        <div style="text-align:center;margin:28px 0 8px;">
            <a href="{{ url('/dashboard/finances') }}"
               style="display:inline-block;padding:14px 34px;background:linear-gradient(135deg,#9333ea,#ec4899);color:#ffffff;text-decoration:none;border-radius:12px;font-weight:700;font-size:14px;">
                Voir mes finances
            </a>
        </div>

        <p style="margin:24px 0 0;font-size:12px;line-height:1.6;color:#9ca3af;text-align:center;">
            Bilan calculé sur la période du {{ $rapport['debut']->format('d/m/Y') }} au {{ $rapport['fin']->format('d/m/Y') }}.<br>
            Les ventes à crédit ne sont comptabilisées qu'à hauteur des sommes encaissées.
        </p>
    </div>

    <div style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:18px 32px;text-align:center;font-size:12px;color:#9ca3af;">
        © {{ date('Y') }} {{ config('app.name') }} — Rapport mensuel automatique.
    </div>
</div>
</body>
</html>
