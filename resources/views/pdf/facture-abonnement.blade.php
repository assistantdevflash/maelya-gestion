@php
    $cp = '#9333ea';
    $cs = '#ec4899';
    $estPayee = in_array($invoice['statut_label'], ['Payé', 'Actif'], true);
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Facture {{ $invoice['numero'] }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; padding: 30px 35px; }
    .header { display: table; width: 100%; margin-bottom: 24px; }
    .header-left, .header-right { display: table-cell; vertical-align: top; }
    .header-right { text-align: right; }
    .plateforme-nom { font-size: 20px; font-weight: bold; color: {{ $cp }}; margin-bottom: 4px; }
    .plateforme-meta { font-size: 10px; color: #6b7280; line-height: 1.6; }
    .badge { display: inline-block; background: {{ $estPayee ? $cp : $cs }}; color: #fff; padding: 6px 14px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 8px; }
    .numero { font-size: 14px; font-weight: bold; color: #111827; }
    .date-info { font-size: 10px; color: #6b7280; margin-top: 4px; }
    .parties { display: table; width: 100%; margin-top: 16px; }
    .partie { display: table-cell; width: 50%; vertical-align: top; padding-right: 12px; }
    .partie:last-child { padding-right: 0; padding-left: 12px; }
    .partie-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af; font-weight: bold; margin-bottom: 4px; }
    .partie-nom { font-size: 12px; font-weight: bold; color: #111827; margin-bottom: 2px; }
    .partie-nom.emetteur { color: {{ $cp }}; }
    .partie-meta { font-size: 10px; color: #4b5563; line-height: 1.5; }
    table.items { width: 100%; border-collapse: collapse; margin-top: 18px; }
    table.items thead th { font-size: 10px; text-transform: uppercase; color: #6b7280; padding: 8px 6px; background: #f9fafb; border-bottom: 2px solid #e5e7eb; text-align: left; }
    table.items thead th.right { text-align: right; }
    table.items tbody td { padding: 8px 6px; font-size: 11px; border-bottom: 1px solid #f3f4f6; }
    table.items tbody td.right { text-align: right; }
    .totals { width: 50%; margin-left: 50%; margin-top: 16px; border-collapse: collapse; }
    .totals td { padding: 6px 8px; font-size: 11px; }
    .totals td.label { color: #6b7280; text-align: right; }
    .totals td.value { text-align: right; font-weight: bold; color: #111827; }
    .totals tr.total td { background: {{ $cp }}14; border-top: 2px solid {{ $cp }}; border-bottom: 2px solid {{ $cp }}; font-size: 13px; padding: 10px 8px; color: {{ $cp }}; }
    .details { margin-top: 20px; border-collapse: collapse; width: 100%; }
    .details td { padding: 6px 8px; font-size: 10px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
    .details td.label { width: 190px; color: #6b7280; font-weight: bold; }
    .details td.value { color: #111827; }
    .mentions { margin-top: 28px; padding-top: 14px; border-top: 1px dashed #d1d5db; font-size: 8.5px; color: #6b7280; line-height: 1.6; }
    .footer { position: fixed; bottom: 15px; left: 35px; right: 35px; text-align: center; font-size: 8.5px; color: #9ca3af; padding-top: 8px; border-top: 1px solid #e5e7eb; }
</style>
</head>
<body>
<div class="header">
    <div class="header-left">
        <div class="plateforme-nom">Maëlya Gestion</div>
        <div class="plateforme-meta">
            Plateforme de gestion pour salons & instituts de beauté<br>
            maelyagestion.com<br>
            contact@maelyagestion.com
        </div>
    </div>
    <div class="header-right">
        <div class="badge">{{ $estPayee ? 'FACTURE ACQUITTÉE' : 'FACTURE' }}</div>
        <div class="numero">N° {{ $invoice['numero'] }}</div>
        <div class="date-info">Émise le {{ $invoice['date_emission']->format('d/m/Y à H:i') }}</div>
    </div>
</div>

<div class="parties">
    <div class="partie">
        <div class="partie-label">Émetteur</div>
        <div class="partie-nom emetteur">Maëlya Gestion</div>
        <div class="partie-meta">
            maelyagestion.com<br>
            contact@maelyagestion.com
        </div>
    </div>
    <div class="partie">
        <div class="partie-label">Client</div>
        <div class="partie-nom">{{ $invoice['client']['nom'] }}</div>
        <div class="partie-meta">
            @if($invoice['client']['institut'] ?? null){{ $invoice['client']['institut'] }}<br>@endif
            @if($invoice['client']['email'] ?? null){{ $invoice['client']['email'] }}<br>@endif
            @if($invoice['client']['telephone'] ?? null)Tél : {{ $invoice['client']['telephone'] }}@endif
        </div>
    </div>
</div>

<table class="items">
    <thead><tr><th>Désignation</th><th class="right">Montant</th></tr></thead>
    <tbody>
        <tr>
            <td>
                <strong>{{ $invoice['designation'] }}</strong>
                <div style="font-size: 9px; color: #6b7280; margin-top: 2px;">{{ $invoice['type_label'] }}</div>
            </td>
            <td class="right"><strong>{{ number_format($invoice['montant'], 0, ',', ' ') }} {{ $invoice['devise'] }}</strong></td>
        </tr>
    </tbody>
</table>

<table class="totals">
    <tr class="total">
        <td class="label">Total à payer</td>
        <td class="value">{{ number_format($invoice['montant'], 0, ',', ' ') }} {{ $invoice['devise'] }}</td>
    </tr>
</table>

<table class="details">
    @foreach($invoice['details'] as $detail)
    <tr>
        <td class="label">{{ $detail['label'] }}</td>
        <td class="value">{{ $detail['value'] }}</td>
    </tr>
    @endforeach
</table>

<div class="mentions">
    <p>Cette facture atteste du paiement effectué sur la plateforme Maëlya Gestion. Elle fait foi des informations relatives à la transaction ci-dessus.</p>
    <p>Pour toute question, contactez-nous à l'adresse contact@maelyagestion.com.</p>
</div>

<div class="footer">Maëlya Gestion — Facture générée le {{ now()->format('d/m/Y à H:i') }}</div>
</body>
</html>
