<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #1f2937; background: #fff; padding: 32px 40px; }

    /* ── Header ── */
    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; }
    .header-left h1 { font-size: 22px; font-weight: bold; color: #7c3aed; letter-spacing: -0.3px; }
    .header-left .subtitle { font-size: 9.5px; color: #6b7280; margin-top: 4px; }
    .header-right { text-align: right; }
    .period-badge { display: inline-block; background: #f3e8ff; color: #7c3aed; padding: 5px 14px; border-radius: 20px; font-size: 10px; font-weight: bold; margin-bottom: 6px; }
    .header-right .meta { font-size: 9px; color: #9ca3af; }
    .divider { border: none; border-top: 2px solid #8b5cf6; margin-bottom: 24px; }

    /* ── KPI ── */
    .kpi-grid { display: flex; gap: 10px; margin-bottom: 24px; }
    .kpi-box { flex: 1; border-radius: 8px; padding: 12px 14px; }
    .kpi-box.violet { background: #f5f3ff; border-left: 3px solid #7c3aed; }
    .kpi-box.red    { background: #fff5f5; border-left: 3px solid #dc2626; }
    .kpi-box.green  { background: #f0fdf4; border-left: 3px solid #059669; }
    .kpi-box.gray   { background: #f9fafb; border-left: 3px solid #9ca3af; }
    .kpi-label { font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; margin-bottom: 5px; }
    .kpi-value { font-size: 17px; font-weight: bold; }
    .kpi-box.violet .kpi-value { color: #7c3aed; }
    .kpi-box.red .kpi-value    { color: #dc2626; }
    .kpi-box.green .kpi-value  { color: #059669; }
    .kpi-box.gray .kpi-value   { color: #374151; }
    .kpi-unit { font-size: 9px; color: #9ca3af; font-weight: normal; margin-left: 2px; }

    /* ── Sections ── */
    .section-title { font-size: 11px; font-weight: bold; color: #374151; text-transform: uppercase; letter-spacing: 0.5px; margin: 22px 0 10px; padding-bottom: 5px; border-bottom: 1px solid #e5e7eb; }

    /* ── Tables ── */
    table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    thead th { background: #f8f7ff; color: #7c3aed; font-size: 8.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.4px; padding: 7px 10px; text-align: left; border-bottom: 2px solid #ddd6fe; }
    tbody tr:nth-child(even) { background: #fafafa; }
    tbody tr:last-child td { border-bottom: none; }
    tbody td { padding: 7px 10px; font-size: 10px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
    tfoot td { padding: 8px 10px; font-weight: bold; background: #f5f3ff; color: #7c3aed; font-size: 10px; border-top: 2px solid #ddd6fe; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .font-bold { font-weight: bold; }
    .text-green { color: #059669; }
    .text-red { color: #dc2626; }
    .text-muted { color: #9ca3af; }
    .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 8.5px; font-weight: bold; }
    .badge-cash   { background: #d1fae5; color: #065f46; }
    .badge-mobile { background: #dbeafe; color: #1e40af; }
    .badge-carte  { background: #fef3c7; color: #92400e; }
    .badge-mixte  { background: #ede9fe; color: #5b21b6; }

    /* ── Footer ── */
    .footer { margin-top: 40px; padding-top: 10px; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; font-size: 8.5px; color: #d1d5db; }
    .page-break { page-break-before: always; }
    .empty-row td { text-align: center; color: #9ca3af; font-style: italic; padding: 14px; }
</style>
</head>
<body>

{{-- ── En-tête ── --}}
<div class="header">
    <div class="header-left">
        <h1>Rapport financier</h1>
        <div class="subtitle">
            {{ $institut->nom ?? 'Maëlya Gestion' }}
            @if($institut->adresse ?? null) · {{ $institut->adresse }}@endif
        </div>
    </div>
    <div class="header-right">
        <div class="period-badge">{{ $dateDebut->format('d/m/Y') }} — {{ $dateFin->format('d/m/Y') }}</div>
        <div class="meta">Généré le {{ now()->format('d/m/Y à H:i') }}</div>
    </div>
</div>
<hr class="divider">

{{-- ── KPIs ── --}}
<div class="kpi-grid">
    <div class="kpi-box violet">
        <div class="kpi-label">Chiffre d'affaires</div>
        <div class="kpi-value">{{ number_format($ca, 0, ',', ' ') }}<span class="kpi-unit">FCFA</span></div>
    </div>
    <div class="kpi-box red">
        <div class="kpi-label">Total dépenses</div>
        <div class="kpi-value">{{ number_format($depenses_total, 0, ',', ' ') }}<span class="kpi-unit">FCFA</span></div>
    </div>
    <div class="kpi-box {{ $benefice >= 0 ? 'green' : 'red' }}">
        <div class="kpi-label">Bénéfice net</div>
        <div class="kpi-value">{{ number_format($benefice, 0, ',', ' ') }}<span class="kpi-unit">FCFA</span></div>
    </div>
    <div class="kpi-box gray">
        <div class="kpi-label">Nbre de ventes</div>
        <div class="kpi-value">{{ $nbVentes }}</div>
    </div>
</div>

{{-- ── Répartition paiement ── --}}
<div class="section-title">Répartition par mode de paiement</div>
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
        @forelse($repartitionPaiement as $mode => $data)
        <tr>
            <td class="font-bold">{{ ucfirst($mode) }}</td>
            <td class="text-right">{{ $data['count'] }}</td>
            <td class="text-right font-bold">{{ number_format($data['total'], 0, ',', ' ') }} FCFA</td>
            <td class="text-right">{{ $ca > 0 ? round($data['total'] / $ca * 100, 1) : 0 }}%</td>
        </tr>
        @empty
        <tr class="empty-row"><td colspan="4">Aucune donnée</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td>TOTAL</td>
            <td class="text-right">{{ $nbVentes }}</td>
            <td class="text-right">{{ number_format($ca, 0, ',', ' ') }} FCFA</td>
            <td class="text-right">100%</td>
        </tr>
    </tfoot>
</table>

{{-- ── Détail des ventes ── --}}
<div class="section-title">Détail des ventes ({{ $ventes->count() }})</div>
<table>
    <thead>
        <tr>
            <th>Commande</th>
            <th>Date</th>
            <th>Client</th>
            <th>Mode</th>
            <th class="text-right">Montant</th>
        </tr>
    </thead>
    <tbody>
        @forelse($ventes as $vente)
        <tr>
            <td class="font-bold text-muted">{{ $vente->numero }}</td>
            <td>{{ $vente->created_at->format('d/m/Y H:i') }}</td>
            <td>{{ $vente->client ? $vente->client->prenom . ' ' . $vente->client->nom : '—' }}</td>
            <td>
                @php $m = strtolower($vente->mode_paiement ?? ''); @endphp
                <span class="badge badge-{{ in_array($m, ['cash','mobile','carte','mixte']) ? $m : 'mixte' }}">
                    {{ ucfirst($vente->mode_paiement) }}
                </span>
            </td>
            <td class="text-right font-bold">{{ number_format($vente->total, 0, ',', ' ') }} FCFA</td>
        </tr>
        @empty
        <tr class="empty-row"><td colspan="5">Aucune vente sur cette période.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4">TOTAL VENTES</td>
            <td class="text-right">{{ number_format($ca, 0, ',', ' ') }} FCFA</td>
        </tr>
    </tfoot>
</table>

{{-- ── Dépenses ── --}}
@if($depenses->count() > 0)
<div class="page-break"></div>
<div class="section-title">Dépenses de la période ({{ $depenses->count() }})</div>
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
            <td class="text-muted">{{ $dep->categorie ? \App\Models\Depense::categorieLabel($dep->categorie) : '—' }}</td>
            <td class="text-right text-red font-bold">{{ number_format($dep->montant, 0, ',', ' ') }} FCFA</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3">TOTAL DÉPENSES</td>
            <td class="text-right">{{ number_format($depenses_total, 0, ',', ' ') }} FCFA</td>
        </tr>
    </tfoot>
</table>
@endif

{{-- ── Footer ── --}}
<div class="footer">
    <span>{{ $institut->nom ?? 'Maëlya Gestion' }}</span>
    <span>Maëlya Gestion © {{ date('Y') }} — Document généré automatiquement</span>
</div>

</body>
</html>
