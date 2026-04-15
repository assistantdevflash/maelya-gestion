<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; width: 280px; margin: 0 auto; padding: 12px; }
    .center { text-align: center; }
    .bold { font-weight: bold; }
    .divider { border-top: 1px dashed #9ca3af; margin: 8px 0; }
    .logo-zone { padding: 8px 0 4px; }
    .logo-zone h1 { font-size: 16px; font-weight: bold; color: #8B5CF6; }
    .logo-zone p { font-size: 9px; color: #6b7280; margin-top: 2px; }
    .vente-num { font-size: 13px; font-weight: bold; margin: 4px 0; }
    .meta { font-size: 9px; color: #6b7280; }
    table { width: 100%; border-collapse: collapse; margin: 6px 0; }
    table thead th { font-size: 9px; text-transform: uppercase; color: #9ca3af; padding: 3px 0; border-bottom: 1px solid #e5e7eb; text-align: left; }
    table thead th:last-child { text-align: right; }
    table tbody td { padding: 4px 0; font-size: 10px; vertical-align: top; }
    table tbody td:last-child { text-align: right; }
    .item-name { font-weight: 600; }
    .item-qty { color: #9ca3af; font-size: 9px; }
    .total-row { font-size: 13px; font-weight: bold; }
    .total-row td { padding: 6px 0 4px; border-top: 2px solid #1f2937; }
    .mode-badge { display: inline-block; background: #f3e8ff; color: #7c3aed; padding: 2px 8px; border-radius: 12px; font-size: 9px; font-weight: bold; text-transform: uppercase; }
    .footer { font-size: 9px; color: #9ca3af; margin-top: 10px; padding-top: 6px; border-top: 1px dashed #e5e7eb; }
</style>
</head>
<body>
<div class="logo-zone center">
    <h1>{{ $vente->institut->nom ?? 'Maëlya Gestion' }}</h1>
    @if($vente->institut->telephone ?? null)
        <p>Tél : {{ $vente->institut->telephone }}</p>
    @endif
</div>

<div class="divider"></div>

<div class="center">
    <p class="vente-num">Ticket #{{ $vente->numero }}</p>
    <p class="meta">{{ \Carbon\Carbon::parse($vente->created_at)->format('d/m/Y à H:i') }}</p>
    @if($vente->client)
        <p class="meta">Client : <strong>{{ $vente->client->prenom }} {{ $vente->client->nom }}</strong></p>
    @endif
    @if($vente->user)
        <p class="meta">Vendeur : {{ $vente->user->prenom }} {{ $vente->user->nom_famille }}</p>
    @endif
</div>

<div class="divider"></div>

<table>
    <thead>
    <tr>
        <th>Désignation</th>
        <th style="text-align:center">Qté</th>
        <th>PU</th>
        <th>Total</th>
    </tr>
    </thead>
    <tbody>
    @foreach($vente->items as $item)
    <tr>
        <td>
            <div class="item-name">{{ $item->nom_snapshot }}</div>
            <div class="item-qty">{{ ucfirst($item->type) }}</div>
        </td>
        <td style="text-align:center">{{ $item->quantite }}</td>
        <td>{{ number_format($item->prix_snapshot, 0, ',', ' ') }}</td>
        <td>{{ number_format($item->sous_total, 0, ',', ' ') }}</td>
    </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr class="total-row">
        <td colspan="3">TOTAL</td>
        <td>{{ number_format($vente->total, 0, ',', ' ') }} FCFA</td>
    </tr>
    </tfoot>
</table>

<div class="center" style="margin-top:8px;">
    <span class="mode-badge">{{ $vente->mode_paiement }}</span>
</div>

<div class="footer center">
    <p>Merci de votre visite !</p>
    <p>{{ $vente->institut->nom ?? 'Maëlya Gestion' }} — Conservez ce ticket</p>
</div>
</body>
</html>
