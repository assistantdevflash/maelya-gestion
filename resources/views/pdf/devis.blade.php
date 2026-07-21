<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Devis {{ $devis->numero }}</title>
<style>
body{font-family:'DejaVu Sans',sans-serif;font-size:12px;color:#333;margin:40px}
.header{border-bottom:3px solid #6F49B8;padding-bottom:20px;margin-bottom:30px;display:flex;justify-content:space-between;align-items:start}
.header h1{font-size:24px;color:#6F49B8;margin:0}.header p{margin:4px 0;color:#555}
.devis-badge{display:inline-block;padding:6px 16px;border-radius:4px;font-weight:bold;font-size:14px;color:#6F49B8;border:2px solid #6F49B8}
.info{display:flex;gap:40px;margin-bottom:30px}.info>div{flex:1}
.info h3{font-size:12px;text-transform:uppercase;color:#999;margin:0 0 8px}.info p{margin:2px 0;font-size:13px}
table{width:100%;border-collapse:collapse;margin:20px 0}
th{background:#6F49B8;color:#fff;padding:10px;font-size:11px;text-align:left;text-transform:uppercase}
td{padding:10px;border-bottom:1px solid #eee}.t-right{text-align:right}.t-center{text-align:center}
.totals{width:280px;margin-left:auto}.totals div{display:flex;justify-content:space-between;padding:6px 0;font-size:13px}.totals .total{border-top:2px solid #6F49B8;font-size:16px;font-weight:bold;padding-top:10px;margin-top:6px}
.footer{position:fixed;bottom:40px;left:40px;right:40px;text-align:center;font-size:10px;color:#aaa;border-top:1px solid #eee;padding-top:12px}
.notes{margin-top:30px;padding:12px;background:#f8f4ff;border-radius:6px;font-size:11px}
</style></head><body>
<div class="header">
    <div>
        <h1>{{ $institut->nom ?? config('app.name') }}</h1>
        <p>{{ $institut->adresse ?? '' }}</p>
        <p>Tél : {{ $institut->telephone ?? '' }} | Email : {{ $institut->email ?? '' }}</p>
        @if($institut->rccm ?? null)<p>RCCM : {{ $institut->rccm }}</p>@endif
    </div>
    <div><span class="devis-badge">DEVIS</span><p style="font-size:16px;font-weight:bold;margin-top:8px">{{ $devis->numero }}</p></div>
</div>
<div class="info">
    <div><h3>Client</h3><p style="font-weight:bold">{{ $devis->client_nom_complet ?: ($devis->client->nom_complet ?? '—') }}</p>
        @if($devis->client_telephone)<p>Tél : {{ $devis->client_telephone }}</p>@endif
        @if($devis->client_adresse)<p>{{ $devis->client_adresse }}</p>@endif
    </div>
    <div><h3>Dates</h3><p>Date : {{ $devis->date_creation->format('d/m/Y') }}</p><p>Expiration : {{ $devis->date_expiration->format('d/m/Y') }}</p></div>
</div>
<table>
    <thead><tr><th>Désignation</th><th class="t-center w-60">Qté</th><th class="t-right w-100">Prix unitaire</th><th class="t-right w-100">Total</th></tr></thead>
    <tbody>@foreach($devis->items as $item)<tr>
        <td>{{ $item->designation }}</td><td class="t-center">{{ $item->quantite }}</td>
        <td class="t-right">{{ number_format($item->prix_unitaire, 0, ',', ' ') }} F</td>
        <td class="t-right"><strong>{{ number_format($item->total_ligne, 0, ',', ' ') }} F</strong></td>
    </tr>@endforeach</tbody>
</table>
<div class="totals">
    <div><span>Total HT</span><span>{{ number_format($devis->total_ht, 0, ',', ' ') }} F</span></div>
    @if($devis->remise_globale > 0)<div><span>Remise</span><span>−{{ number_format($devis->remise_globale, 0, ',', ' ') }} F</span></div>@endif
    @if($devis->tva_taux > 0)<div><span>TVA {{ $devis->tva_taux }}%</span><span>{{ number_format($devis->total_tva, 0, ',', ' ') }} F</span></div>@endif
    <div class="total"><span>Total TTC</span><span>{{ number_format($devis->total_ttc, 0, ',', ' ') }} F</span></div>
</div>
@if($devis->notes)<div class="notes"><strong>Notes :</strong> {{ $devis->notes }}</div>@endif
<div class="footer">{{ config('app.name') }} — Devis généré le {{ now()->format('d/m/Y à H:i') }}</div>
</body></html>
