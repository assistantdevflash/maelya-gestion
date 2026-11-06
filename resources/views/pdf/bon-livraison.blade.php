@php
    $institut = $commande->institut;
    $cp = $institut->couleur_primaire ?? '#9333ea';
    $cs = $institut->couleur_secondaire ?? '#ec4899';
    $modeLabels = [
        'cash'         => 'Espèces à la livraison',
        'mobile_money' => 'Mobile Money',
        'en_ligne'     => 'Payé en ligne',
        'mixte'        => 'Mixte',
    ];
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Bon de livraison {{ $commande->numero }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; padding: 30px 35px; }
    .header { display: table; width: 100%; margin-bottom: 24px; }
    .header-left, .header-right { display: table-cell; vertical-align: top; }
    .header-right { text-align: right; }
    .institut-logo img { max-height: 48px; max-width: 140px; display: block; margin-bottom: 6px; }
    .institut-nom { font-size: 18px; font-weight: bold; color: {{ $cp }}; margin-bottom: 4px; }
    .institut-meta { font-size: 10px; color: #6b7280; line-height: 1.5; }
    .badge { display: inline-block; background: {{ $cp }}; color: #fff; padding: 6px 14px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 8px; }
    .numero { font-size: 14px; font-weight: bold; color: #111827; }
    .date-info { font-size: 10px; color: #6b7280; margin-top: 4px; }
    .parties { display: table; width: 100%; margin-bottom: 22px; }
    .partie { display: table-cell; width: 50%; padding-right: 18px; vertical-align: top; }
    .partie-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af; margin-bottom: 4px; }
    .partie-nom { font-size: 12px; font-weight: bold; color: #111827; margin-bottom: 3px; }
    .partie-meta { font-size: 10px; color: #4b5563; line-height: 1.6; }
    table.items { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
    table.items th { background: #f9fafb; border-bottom: 2px solid {{ $cp }}; padding: 8px 6px; font-size: 9px; text-transform: uppercase; letter-spacing: 0.06em; color: #6b7280; text-align: left; }
    table.items td { padding: 8px 6px; border-bottom: 1px solid #f3f4f6; font-size: 10.5px; }
    table.items td.right, table.items th.right { text-align: right; }
    table.items td.center, table.items th.center { text-align: center; }
    .ligne-remise { margin-top: 18px; padding: 10px 14px; background: #f9fafb; border-left: 3px solid {{ $cp }}; font-size: 10px; color: #4b5563; }
    table.signature { width: 100%; margin-top: 36px; border-collapse: collapse; }
    table.signature td { width: 50%; padding: 24px 24px 0 0; font-size: 9px; color: #9ca3af; vertical-align: bottom; }
    .signature-line { border-top: 1px solid #d1d5db; padding-top: 6px; }
    .mentions { margin-top: 26px; padding-top: 12px; border-top: 1px dashed #d1d5db; font-size: 8.5px; color: #6b7280; line-height: 1.6; }
</style>
</head>
<body>
<div class="header">
    <div class="header-left">
        @if($institut->logo ?? null)
            <div class="institut-logo"><img src="{{ storage_path('app/public/' . $institut->logo) }}"></div>
        @endif
        <div class="institut-nom">{{ $institut->nom ?? config('app.name') }}</div>
        <div class="institut-meta">
            @if($institut->ville ?? null){{ $institut->ville }}<br>@endif
            @if($institut->telephone ?? null)Tél : {{ $institut->telephone }}<br>@endif
            @if($institut->email ?? null){{ $institut->email }}@endif
        </div>
    </div>
    <div class="header-right">
        <div class="badge">Bon de livraison</div>
        <div class="numero">N° {{ $commande->numero }}</div>
        <div class="date-info">
            Commande du {{ $commande->created_at->format('d/m/Y à H:i') }}
            @if($commande->date_livraison_prevue)
                <br>Livraison prévue le {{ $commande->date_livraison_prevue->format('d/m/Y') }}
                @if($commande->creneau_livraison) ({{ $commande->creneau_livraison }})@endif
            @endif
        </div>
    </div>
</div>

<div class="parties">
    <div class="partie">
        <div class="partie-label">Livrer à</div>
        <div class="partie-nom">{{ $commande->client_prenom }} {{ $commande->client_nom }}</div>
        <div class="partie-meta">
            @if($commande->client_telephone){{ $commande->client_telephone }}<br>@endif
            @if($commande->client_email){{ $commande->client_email }}<br>@endif
            {{ $commande->client_adresse }}
        </div>
    </div>
    <div class="partie">
        <div class="partie-label">Paiement</div>
        <div class="partie-nom">{{ $commande->payee ? 'Déjà réglée' : 'À encaisser à la livraison' }}</div>
        <div class="partie-meta">
            Mode : {{ $modeLabels[$commande->mode_paiement] ?? $commande->mode_paiement }}<br>
            Montant : {{ number_format($commande->total, 0, ',', ' ') }} F CFA
        </div>
    </div>
</div>

<table class="items">
    <thead>
        <tr>
            <th>Article</th>
            <th class="center">Qté</th>
            <th class="right">Prix unitaire</th>
            <th class="right">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($commande->items as $item)
        <tr>
            <td>{{ $item->nom_snapshot }}</td>
            <td class="center">{{ $item->quantite }}</td>
            <td class="right">{{ number_format($item->prix_snapshot, 0, ',', ' ') }} F</td>
            <td class="right">{{ number_format($item->sous_total, 0, ',', ' ') }} F</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="ligne-remise">
    Sous-total : <strong>{{ number_format($commande->sous_total, 0, ',', ' ') }} F</strong>
    @if($commande->frais_livraison > 0)
        · Livraison : <strong>{{ number_format($commande->frais_livraison, 0, ',', ' ') }} F</strong>
    @endif
    · <strong>Total : {{ number_format($commande->total, 0, ',', ' ') }} F CFA</strong>
</div>

@if($commande->notes_client)
<div class="ligne-remise">Note du client : {{ $commande->notes_client }}</div>
@endif

<table class="signature">
    <tr>
        <td><div class="signature-line">Signature du livreur</div></td>
        <td><div class="signature-line">Signature du client (réception conforme)</div></td>
    </tr>
</table>

<div class="mentions">
    Ce bon de livraison accompagne la commande {{ $commande->numero }}.
    Merci de vérifier la conformité des articles à la réception.
</div>
</body>
</html>
