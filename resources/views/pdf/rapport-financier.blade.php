<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; padding: 30px 40px; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid #8B5CF6; }
    .header h1 { font-size: 20px; font-weight: bold; color: #8B5CF6; }
    .header p { font-size: 9px; color: #6b7280; margin-top: 3px; }
    .period-badge { background: #f3e8ff; color: #7c3aed; padding: 4px 12px; border-radius: 20px; font-size: 10px; font-weight: bold; }
    h2 { font-size: 12px; font-weight: bold; color: #374151; margin: 20px 0 10px; padding-bottom: 4px; border-bottom: 1px solid #e5e7eb; }
    .kpi-grid { display: flex; gap: 12px; margin-bottom: 20px; }
    .kpi-box { flex: 1; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 14px; }
    .kpi-box .label { font-size: 9px; color: #9ca3af; text-transform: uppercase; margin-bottom: 4px; }
    .kpi-box .value { font-size: 16px; font-weight: bold; }
    .kpi-box.green .value { color: #059669; }
    .kpi-box.red .value { color: #dc2626; }
    .kpi-box.violet .value { color: #7c3aed; }
    .kpi-box .unit { font-size: 9px; color: #9ca3af; margin-left: 3px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    thead th { background: #f3f4f6; font-size: 9px; text-transform: uppercase; color: #9ca3af; padding: 6px 8px; text-align: left; }
    tbody tr:nth-child(even) { background: #f9fafb; }
    tbody td { padding: 6px 8px; font-size: 10px; border-bottom: 1px solid #f3f4f6; }
    .text-right { text-align: right; }
    .font-bold { font-weight: bold; }
    .text-green { color: #059669; }
    .text-red { color: #dc2626; }
    .total-footer { background: #f3e8ff; }
    .total-footer td { font-weight: bold; color: #7c3aed; padding: 8px; }
    .footer { margin-top: 36px; padding-top: 12px; border-top: 1px solid #e5e7eb; font-size: 9px; color: #9ca3af; text-align: center; }
    .page-break { page-break-before: always; }
</style>
</head>
<body>

{{-- En-tête --}}
<div class="header">
    <div>
        <h1>Rapport financier</h1>
        <p>{{ $institut->nom ?? 'Maëlya Gestion' }}</p>
        @if($institut->adresse ?? null)
            <p>{{ $institut->adresse }}</p>
        @endif
        <p>Généré le {{ now()->format('d/m/Y à H:i') }}</p>
    </div>
    <div>
        <span class="period-badge">{{ $dateDebut->format('d/m/Y') }} — {{ $dateFin->format('d/m/Y') }}</span>
    </div>
</div>

{{-- KPI --}}
<h2>Résumé de la période</h2>
<div class="kpi-grid">
    <div class="kpi-box violet">
        <div class="label">Chiffre d'affaires</div>
        <div class="value">{{ number_format($ca, 0, ',', ' ') }}<span class="unit">FCFA</span></div>
    </div>
    <div class="kpi-box red">
        <div class="label">Dépenses</div>
        <div class="value">{{ number_format($depenses_total, 0, ',', ' ') }}<span class="unit">FCFA</span></div>
    </div>
    <div class="kpi-box green">
        <div class="label">Bénéfice net</div>
        <div class="value">{{ number_format($benefice, 0, ',', ' ') }}<span class="unit">FCFA</span></div>
    </div>
    <div class="kpi-box">
        <div class="label">Nbre de ventes</div>
        <div class="value">{{ $nbVentes }}</div>
    </div>
</div>

{{-- Répartition paiement --}}
<h2>Répartition par mode de paiement</h2>
<table>
    <thead>
    <tr>
        <th>Mode de paiement</th>
        <th class="text-right">Nb ventes</th>
        <th class="text-right">Total</th>
        <th class="text-right">Part</th>
    </tr>
    </thead>
    <tbody>
    @foreach($repartitionPaiement as $mode => $data)
    <tr>
        <td class="font-bold">{{ ucfirst($mode) }}</td>
        <td class="text-right">{{ $data['count'] }}</td>
        <td class="text-right">{{ number_format($data['total'], 0, ',', ' ') }} FCFA</td>
        <td class="text-right">{{ $ca > 0 ? round($data['total'] / $ca * 100, 1) : 0 }}%</td>
    </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr class="total-footer">
        <td>TOTAL</td>
        <td class="text-right">{{ $nbVentes }}</td>
        <td class="text-right">{{ number_format($ca, 0, ',', ' ') }} FCFA</td>
        <td class="text-right">100%</td>
    </tr>
    </tfoot>
</table>

{{-- Détail ventes --}}
<h2>Détail des ventes ({{ $ventes->count() }})</h2>
<table>
    <thead>
    <tr>
        <th>N°</th>
        <th>Date</th>
        <th>Client</th>
        <th>Mode</th>
        <th class="text-right">Montant</th>
    </tr>
    </thead>
    <tbody>
    @foreach($ventes as $vente)
    <tr>
        <td>#{{ str_pad($vente->id, 4, '0', STR_PAD_LEFT) }}</td>
        <td>{{ $vente->created_at->format('d/m/Y') }}</td>
        <td>{{ $vente->client ? $vente->client->prenom . ' ' . $vente->client->nom : 'Client passager' }}</td>
        <td>{{ ucfirst($vente->mode_paiement) }}</td>
        <td class="text-right font-bold">{{ number_format($vente->total, 0, ',', ' ') }}</td>
    </tr>
    @endforeach
    </tbody>
</table>

{{-- Dépenses --}}
@if($depenses->count() > 0)
<div class="page-break"></div>
<h2>Dépenses de la période ({{ $depenses->count() }})</h2>
<table>
    <thead>
    <tr>
        <th>Date</th>
        <th>Libellé</th>
        <th>Catégorie</th>
        <th class="text-right">Montant</th>
    </tr>
    </thead>
    <tbody>
    @foreach($depenses as $dep)
    <tr>
        <td>{{ $dep->date->format('d/m/Y') }}</td>
        <td>{{ $dep->description }}</td>
        <td>{{ $dep->categorie ? \App\Models\Depense::categorieLabel($dep->categorie) : '—' }}</td>
        <td class="text-right text-red font-bold">{{ number_format($dep->montant, 0, ',', ' ') }}</td>
    </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr class="total-footer">
        <td colspan="3">TOTAL DÉPENSES</td>
        <td class="text-right">{{ number_format($depenses_total, 0, ',', ' ') }} FCFA</td>
    </tr>
    </tfoot>
</table>
@endif

<div class="footer">
    <p>{{ $institut->nom ?? 'Maëlya Gestion' }} — Rapport généré automatiquement — Maëlya Gestion © {{ date('Y') }}</p>
</div>

</body>
</html>
