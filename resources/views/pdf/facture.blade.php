<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Facture {{ $vente->numero_facture ?? $vente->numero }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; padding: 30px 35px; }
    .header { display: table; width: 100%; margin-bottom: 24px; }
    .header-left, .header-right { display: table-cell; vertical-align: top; }
    .header-right { text-align: right; }
    .institut-nom { font-size: 18px; font-weight: bold; color: #8B5CF6; margin-bottom: 4px; }
    .institut-meta { font-size: 10px; color: #6b7280; line-height: 1.5; }
    .facture-badge { display: inline-block; background: #8B5CF6; color: #fff; padding: 6px 14px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 8px; }
    .facture-numero { font-size: 14px; font-weight: bold; color: #111827; }
    .facture-date { font-size: 10px; color: #6b7280; margin-top: 4px; }

    .section { margin-top: 20px; }
    .section-title { font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em; color: #6b7280; font-weight: bold; margin-bottom: 6px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }

    .parties { display: table; width: 100%; margin-top: 16px; }
    .partie { display: table-cell; width: 50%; vertical-align: top; padding-right: 12px; }
    .partie:last-child { padding-right: 0; padding-left: 12px; }
    .partie-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af; font-weight: bold; margin-bottom: 4px; }
    .partie-nom { font-size: 12px; font-weight: bold; color: #111827; margin-bottom: 2px; }
    .partie-meta { font-size: 10px; color: #4b5563; line-height: 1.5; }

    table.items { width: 100%; border-collapse: collapse; margin-top: 18px; }
    table.items thead th { font-size: 10px; text-transform: uppercase; color: #6b7280; padding: 8px 6px; background: #f9fafb; border-bottom: 2px solid #e5e7eb; text-align: left; }
    table.items thead th.num { text-align: center; width: 50px; }
    table.items thead th.right { text-align: right; }
    table.items tbody td { padding: 8px 6px; font-size: 11px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
    table.items tbody td.num { text-align: center; color: #9ca3af; }
    table.items tbody td.right { text-align: right; }
    .item-type { font-size: 9px; color: #9ca3af; text-transform: uppercase; }

    .totals { width: 50%; margin-left: 50%; margin-top: 16px; border-collapse: collapse; }
    .totals td { padding: 6px 8px; font-size: 11px; }
    .totals td.label { color: #6b7280; text-align: right; }
    .totals td.value { text-align: right; font-weight: bold; color: #111827; }
    .totals tr.total td { background: #f5f3ff; border-top: 2px solid #8B5CF6; border-bottom: 2px solid #8B5CF6; font-size: 13px; padding: 10px 8px; color: #6d28d9; }

    .paiement { margin-top: 20px; padding: 10px 14px; background: #f9fafb; border-left: 3px solid #8B5CF6; font-size: 10px; color: #4b5563; }
    .paiement strong { color: #111827; }

    .mentions { margin-top: 28px; padding-top: 14px; border-top: 1px dashed #d1d5db; font-size: 8.5px; color: #6b7280; line-height: 1.6; }
    .mentions p { margin-bottom: 4px; }
    .mentions .titre { font-weight: bold; color: #4b5563; margin-bottom: 4px; font-size: 9px; text-transform: uppercase; letter-spacing: 0.05em; }

    .footer { position: fixed; bottom: 15px; left: 35px; right: 35px; text-align: center; font-size: 8.5px; color: #9ca3af; padding-top: 8px; border-top: 1px solid #e5e7eb; }
</style>
</head>
<body>

<div class="header">
    <div class="header-left">
        <div class="institut-nom">{{ $vente->institut->nom ?? config('app.name') }}</div>
        <div class="institut-meta">
            @if($vente->institut->ville ?? null){{ $vente->institut->ville }}<br>@endif
            @if($vente->institut->telephone ?? null)Tél : {{ $vente->institut->telephone }}<br>@endif
            @if($vente->institut->email ?? null){{ $vente->institut->email }}@endif
        </div>
    </div>
    <div class="header-right">
        <div class="facture-badge">Facture</div>
        <div class="facture-numero">N° {{ $vente->numero_facture ?? $vente->numero }}</div>
        <div class="facture-date">Émise le {{ \Carbon\Carbon::parse($vente->created_at)->format('d/m/Y à H:i') }}</div>
    </div>
</div>

<div class="parties">
    <div class="partie">
        <div class="partie-label">Émetteur</div>
        <div class="partie-nom">{{ $vente->institut->nom ?? config('app.name') }}</div>
        <div class="partie-meta">
            @if($vente->institut->ville ?? null){{ $vente->institut->ville }}<br>@endif
            @if($vente->institut->telephone ?? null){{ $vente->institut->telephone }}<br>@endif
            @if($vente->institut->email ?? null){{ $vente->institut->email }}@endif
        </div>
    </div>
    <div class="partie">
        <div class="partie-label">Client</div>
        @if($vente->client)
            @if($vente->client->isEntreprise())
                {{-- Client entreprise --}}
                <div class="partie-nom">{{ $vente->client->raison_sociale ?: ($vente->client->prenom . ' ' . $vente->client->nom) }}</div>
                <div class="partie-meta">
                    @if($vente->client->numero_registre_commerce)RC : {{ $vente->client->numero_registre_commerce }}<br>@endif
                    @if($vente->client->adresse_entreprise){{ $vente->client->adresse_entreprise }}<br>@endif
                    @if($vente->client->telephone)Tél : {{ $vente->client->telephone }}<br>@endif
                    @if($vente->client->email){{ $vente->client->email }}@endif
                </div>
            @else
                {{-- Personne physique --}}
                <div class="partie-nom">{{ $vente->client->prenom }} {{ $vente->client->nom }}</div>
                <div class="partie-meta">
                    @if($vente->client->est_patient)<span style="color:#8B5CF6;font-weight:bold;text-transform:uppercase;font-size:8px;">Patient</span><br>@endif
                    @if($vente->client->telephone){{ $vente->client->telephone }}<br>@endif
                    @if($vente->client->email){{ $vente->client->email }}@endif
                </div>
            @endif
        @else
            <div class="partie-nom">Client de passage</div>
            <div class="partie-meta">—</div>
        @endif
    </div>
</div>

<table class="items">
    <thead>
        <tr>
            <th class="num">#</th>
            <th>Désignation</th>
            <th class="num">Qté</th>
            <th class="right">PU</th>
            <th class="right">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($vente->items as $i => $item)
        <tr>
            <td class="num">{{ $i + 1 }}</td>
            <td>
                {{ $item->nom_snapshot }}
                <div class="item-type">{{ match($item->type) { 'prestation' => 'Prestation', 'produit' => 'Produit', 'libre' => 'Article libre', default => ucfirst($item->type) } }}</div>
            </td>
            <td class="num">{{ $item->quantite }}</td>
            <td class="right">{{ number_format($item->prix_snapshot, 0, ',', ' ') }}</td>
            <td class="right">{{ number_format($item->sous_total, 0, ',', ' ') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<table class="totals">
    @php $sousTotal = $vente->items->sum('sous_total'); @endphp
    <tr>
        <td class="label">Sous-total</td>
        <td class="value">{{ number_format($sousTotal, 0, ',', ' ') }} FCFA</td>
    </tr>
    @if($vente->remise > 0)
    <tr>
        <td class="label">Remise</td>
        <td class="value" style="color:#059669">- {{ number_format($vente->remise, 0, ',', ' ') }} FCFA</td>
    </tr>
    @endif
    <tr class="total">
        <td class="label">TOTAL TTC</td>
        <td class="value">{{ number_format($vente->total, 0, ',', ' ') }} FCFA</td>
    </tr>
    @if($vente->pourboire > 0)
    <tr>
        <td class="label">Pourboire</td>
        <td class="value">{{ number_format($vente->pourboire, 0, ',', ' ') }} FCFA</td>
    </tr>
    @endif
</table>

<div class="paiement">
    <strong>Mode de paiement :</strong>
    {{ match($vente->mode_paiement) { 'cash' => 'Espèces', 'carte' => 'Carte bancaire', 'mobile_money' => 'Mobile Money', 'mixte' => 'Paiement mixte', default => ucfirst($vente->mode_paiement) } }}
    @if($vente->reference_paiement)
        — Réf. {{ $vente->reference_paiement }}
    @endif
    @if($vente->mode_paiement === 'mixte')
        <br>
        <span style="font-size:9px;color:#6b7280">
            @if($vente->montant_cash > 0)Espèces : {{ number_format($vente->montant_cash, 0, ',', ' ') }} FCFA @endif
            @if($vente->montant_mobile > 0)— Mobile : {{ number_format($vente->montant_mobile, 0, ',', ' ') }} FCFA @endif
            @if($vente->montant_carte > 0)— Carte : {{ number_format($vente->montant_carte, 0, ',', ' ') }} FCFA @endif
        </span>
    @endif
</div>

<div class="footer">
    Facture générée le {{ now()->format('d/m/Y à H:i') }} — {{ $vente->institut->nom ?? config('app.name') }}
</div>

</body>
</html>
