<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Facture {{ $facture->numero }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; padding: 30px 35px; }
    .header { display: table; width: 100%; margin-bottom: 24px; }
    .header-left, .header-right { display: table-cell; vertical-align: top; }
    .header-right { text-align: right; }
    .institut-nom { font-size: 18px; font-weight: bold; color: #059669; margin-bottom: 4px; }
    .institut-meta { font-size: 10px; color: #6b7280; line-height: 1.5; }
    .badge { display: inline-block; background: {{ $facture->estPayee ? '#059669' : '#8B5CF6' }}; color: #fff; padding: 6px 14px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 8px; }
    .numero { font-size: 14px; font-weight: bold; color: #111827; }
    .date-info { font-size: 10px; color: #6b7280; margin-top: 4px; }
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
    table.items tbody td { padding: 8px 6px; font-size: 11px; border-bottom: 1px solid #f3f4f6; }
    table.items tbody td.num { text-align: center; color: #9ca3af; }
    table.items tbody td.right { text-align: right; }
    .totals { width: 50%; margin-left: 50%; margin-top: 16px; border-collapse: collapse; }
    .totals td { padding: 6px 8px; font-size: 11px; }
    .totals td.label { color: #6b7280; text-align: right; }
    .totals td.value { text-align: right; font-weight: bold; color: #111827; }
    .totals tr.total td { background: #ecfdf5; border-top: 2px solid #059669; border-bottom: 2px solid #059669; font-size: 13px; padding: 10px 8px; color: #059669; }
    .paiement-info { margin-top: 16px; padding: 10px 14px; background: #f9fafb; border-left: 3px solid #059669; font-size: 10px; color: #4b5563; }
    .mentions { margin-top: 28px; padding-top: 14px; border-top: 1px dashed #d1d5db; font-size: 8.5px; color: #6b7280; line-height: 1.6; }
    .footer { position: fixed; bottom: 15px; left: 35px; right: 35px; text-align: center; font-size: 8.5px; color: #9ca3af; padding-top: 8px; border-top: 1px solid #e5e7eb; }
</style>
</head>
<body>
<div class="header">
    <div class="header-left">
        <div class="institut-nom">
            @if($institut->logo ?? null)
                <img src="{{ storage_path('app/public/' . $institut->logo) }}" style="max-height: 50px; max-width: 140px; margin-bottom: 4px; display: block;">
            @endif
            {{ $institut->nom ?? config('app.name') }}
        </div>
        <div class="institut-meta">
            @if($institut->ville ?? null){{ $institut->ville }}<br>@endif
            @if($institut->telephone ?? null)Tél : {{ $institut->telephone }}<br>@endif
            @if($institut->email ?? null){{ $institut->email }}@endif
            @if($institut->rccm ?? null)<br>RCCM : {{ $institut->rccm }}@endif
            @if($institut->numero_fiscal ?? null)<br>N° Fiscal : {{ $institut->numero_fiscal }}@endif
        </div>
    </div>
    <div class="header-right">
        <div class="badge">{{ $facture->estPayee ? 'PAYÉE' : 'FACTURE' }}</div>
        <div class="numero">N° {{ $facture->numero }}</div>
        <div class="date-info">Émise le {{ $facture->date_emission->format('d/m/Y') }} · Échéance {{ $facture->date_echeance->format('d/m/Y') }}</div>
    </div>
</div>
<div class="parties">
    <div class="partie">
        <div class="partie-label">Émetteur</div>
        <div class="partie-nom">{{ $institut->nom ?? config('app.name') }}</div>
        <div class="partie-meta">@if($institut->adresse ?? null){{ $institut->adresse }}<br>@endif@if($institut->ville ?? null){{ $institut->ville }}<br>@endif@if($institut->telephone ?? null)Tél : {{ $institut->telephone }}<br>@endif@if($institut->email ?? null){{ $institut->email }}@endif</div>
    </div>
    <div class="partie">
        <div class="partie-label">Client</div>
        @php $client = $facture->client; @endphp
        @if($client && $client->isEntreprise())
            <div class="partie-nom">{{ $client->raison_sociale ?: $client->nom_complet }}</div>
            <div class="partie-meta">@if($client->numero_registre_commerce)RC : {{ $client->numero_registre_commerce }}<br>@endif@if($facture->client_telephone ?: $client->telephone ?? null)Tél : {{ $facture->client_telephone ?: $client->telephone }}<br>@endif@if($facture->client_email ?: $client->email ?? null){{ $facture->client_email ?: $client->email }}@endif</div>
        @else
            <div class="partie-nom">{{ $facture->client_nom_complet ?: ($client->nom_complet ?? '—') }}</div>
            <div class="partie-meta">@if($facture->client_telephone ?: $client->telephone ?? null){{ $facture->client_telephone ?: $client->telephone }}<br>@endif@if($facture->client_email ?: $client->email ?? null){{ $facture->client_email ?: $client->email }}@endif</div>
        @endif
    </div>
</div>
<table class="items">
    <thead><tr><th>Désignation</th><th class="num">Qté</th><th class="right">Prix unitaire</th><th class="right">Total</th></tr></thead>
    <tbody>@foreach($facture->items as $item)<tr>
        <td>{{ $item->designation }}</td><td class="num">{{ $item->quantite }}</td>
        <td class="right">{{ number_format($item->prix_unitaire, 0, ',', ' ') }} F</td>
        <td class="right"><strong>{{ number_format($item->total_ligne, 0, ',', ' ') }} F</strong></td>
    </tr>@endforeach</tbody>
</table>
<table class="totals">
    <tr><td class="label">Sous-total</td><td class="value">{{ number_format($facture->sous_total, 0, ',', ' ') }} F</td></tr>
    @if($facture->remise_globale > 0)
    <tr><td class="label">Remise @if($facture->remise_globale_type === 'pourcentage')({{ (int) $facture->remise_globale_valeur }}%)@endif</td><td class="value" style="color:#dc2626;">−{{ number_format($facture->remise_globale, 0, ',', ' ') }} F</td></tr>
    @endif
    <tr><td class="label">Total HT</td><td class="value">{{ number_format($facture->total_ht, 0, ',', ' ') }} F</td></tr>
    @if($facture->tva_applicable && $facture->tva_taux > 0)<tr><td class="label">TVA {{ $facture->tva_taux }}%</td><td class="value">{{ number_format($facture->total_tva, 0, ',', ' ') }} F</td></tr>@endif
    <tr class="total"><td class="label">Total TTC</td><td class="value">{{ number_format($facture->total_ttc, 0, ',', ' ') }} F</td></tr>
    @if($facture->montant_paye > 0)
    <tr><td class="label">Déjà payé</td><td class="value" style="color:#059669;">{{ number_format($facture->montant_paye, 0, ',', ' ') }} F</td></tr>
    <tr><td class="label" style="color:#dc2626;">Reste à payer</td><td class="value" style="color:#dc2626;">{{ number_format($facture->resteAPayer, 0, ',', ' ') }} F</td></tr>
    @endif
</table>
@if($facture->notes)<div class="paiement-info"><strong>Notes :</strong> {{ $facture->notes }}</div>@endif
<div class="mentions"><p>Merci de régler cette facture avant le {{ $facture->date_echeance->format('d/m/Y') }}.</p><p>Pour toute question : {{ $institut->telephone ?? '—' }}</p></div>
<div class="footer">{{ config('app.name') }} — Facture générée le {{ now()->format('d/m/Y à H:i') }}</div>
</body>
</html>
