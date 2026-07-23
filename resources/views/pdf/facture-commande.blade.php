<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Facture {{ $commande->numero }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; padding: 30px 35px; }
    .header { display: table; width: 100%; margin-bottom: 24px; }
    .header-left, .header-right { display: table-cell; vertical-align: top; }
    .header-right { text-align: right; }
    .institut-nom { font-size: 18px; font-weight: bold; color: #9333ea; margin-bottom: 4px; }
    .institut-meta { font-size: 10px; color: #6b7280; line-height: 1.5; }
    .facture-badge { display: inline-block; background: #9333ea; color: #fff; padding: 6px 14px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 8px; }
    .facture-numero { font-size: 14px; font-weight: bold; color: #111827; }
    .facture-date { font-size: 10px; color: #6b7280; margin-top: 4px; }

    .section-title { font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em; color: #6b7280; font-weight: bold; margin-top: 20px; margin-bottom: 6px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }

    .parties { display: table; width: 100%; margin-top: 12px; }
    .partie { display: table-cell; width: 50%; vertical-align: top; padding-right: 12px; }
    .partie:last-child { padding-right: 0; padding-left: 12px; }
    .partie-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af; font-weight: bold; margin-bottom: 4px; }
    .partie-nom { font-size: 12px; font-weight: bold; color: #111827; margin-bottom: 2px; }
    .partie-meta { font-size: 10px; color: #4b5563; line-height: 1.5; }

    table.items { width: 100%; border-collapse: collapse; margin-top: 18px; }
    table.items thead th { font-size: 10px; text-transform: uppercase; color: #6b7280; padding: 8px 6px; background: #f9fafb; border-bottom: 2px solid #e5e7eb; text-align: left; }
    table.items thead th.right { text-align: right; }
    table.items thead th.center { text-align: center; width: 60px; }
    table.items tbody td { padding: 8px 6px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
    table.items tbody td.right { text-align: right; }
    table.items tbody td.center { text-align: center; }
    table.items .item-nom { font-weight: 600; color: #111827; }

    .totaux { margin-top: 10px; width: 280px; margin-left: auto; }
    .totaux table { width: 100%; }
    .totaux td { padding: 5px 0; font-size: 10px; }
    .totaux td:last-child { text-align: right; }
    .totaux .total-line td { font-size: 13px; font-weight: bold; border-top: 2px solid #1f2937; padding-top: 8px; color: #9333ea; }

    .footer { margin-top: 40px; text-align: center; font-size: 9px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 12px; }

    .statut-badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
    .statut-payee { background: #d1fae5; color: #065f46; }
    .statut-non-payee { background: #fee2e2; color: #991b1b; }
</style>
</head>
<body>

<div class="header">
    <div class="header-left">
        <div class="institut-nom">
            @if($commande->institut->logo ?? null)
                <img src="{{ storage_path('app/public/' . $commande->institut->logo) }}" style="max-height: 50px; max-width: 140px; margin-bottom: 4px; display: block;">
            @endif
            {{ $commande->institut->nom ?? 'Institut' }}
        </div>
        @if($commande->institut->adresse ?? null)
            <div class="institut-meta">{{ $commande->institut->adresse }}</div>
        @endif
        @if($commande->institut->telephone ?? null)
            <div class="institut-meta">Tél : {{ $commande->institut->telephone }}</div>
        @endif
    </div>
    <div class="header-right">
        <div class="facture-badge">Facture</div>
        <div class="facture-numero">{{ $commande->numero }}</div>
        <div class="facture-date">Date : {{ $commande->created_at->format('d/m/Y à H:i') }}</div>
    </div>
</div>

<div class="section-title">Informations</div>
<div class="parties">
    <div class="partie">
        <div class="partie-label">Client</div>
        <div class="partie-nom">{{ $commande->client_prenom }} {{ $commande->client_nom }}</div>
        <div class="partie-meta">Tél : {{ $commande->client_telephone }}</div>
        @if($commande->client_email)
            <div class="partie-meta">{{ $commande->client_email }}</div>
        @endif
    </div>
    <div class="partie">
        <div class="partie-label">Livraison</div>
        <div class="partie-meta">{{ $commande->adresse_livraison }}</div>
        <div class="partie-meta" style="margin-top:6px;">
            Mode de paiement : {{ $commande->mode_paiement == 'livraison' ? 'À la livraison' : ucfirst($commande->mode_paiement) }}
        </div>
        <div class="partie-meta">
            Statut : {{ ucfirst(str_replace('_', ' ', $commande->statut)) }}
            &nbsp;|&nbsp;
            @if($commande->payee)
                <span class="statut-badge statut-payee">Payée</span>
            @else
                <span class="statut-badge statut-non-payee">Non payée</span>
            @endif
        </div>
    </div>
</div>

<div class="section-title">Produits commandés</div>
<table class="items">
    <thead>
        <tr>
            <th>Produit</th>
            <th class="center">Qté</th>
            <th class="right">Prix unitaire</th>
            <th class="right">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($commande->items as $item)
        <tr>
            <td class="item-nom">{{ $item->nom_snapshot }}</td>
            <td class="center">{{ $item->quantite }}</td>
            <td class="right">{{ number_format($item->prix_snapshot, 0, ',', ' ') }} F</td>
            <td class="right">{{ number_format($item->sous_total, 0, ',', ' ') }} F</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="totaux">
    <table cellspacing="0" cellpadding="0">
        <tr>
            <td>Sous-total</td>
            <td>{{ number_format($commande->sous_total, 0, ',', ' ') }} F</td>
        </tr>
        <tr>
            <td>Frais de livraison</td>
            <td>{{ number_format($commande->frais_livraison, 0, ',', ' ') }} F</td>
        </tr>
        <tr class="total-line">
            <td>Total</td>
            <td>{{ number_format($commande->total, 0, ',', ' ') }} F</td>
        </tr>
    </table>
</div>

@if($commande->notes_client)
<div class="section-title">Notes du client</div>
<p style="font-size:10px; color:#4b5563;">{{ $commande->notes_client }}</p>
@endif

@if($commande->notes_admin)
<div class="section-title">Notes internes</div>
<p style="font-size:10px; color:#4b5563;">{{ $commande->notes_admin }}</p>
@endif

<div class="footer">
    Facture générée le {{ now()->format('d/m/Y à H:i') }} — {{ config('app.name') ?? 'Maelya Gestion' }}
</div>

</body>
</html>
