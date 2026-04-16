@extends('layouts.admin')
@section('page-title', 'Point financier')

@section('content')
<div class="space-y-6" x-data="{ tab: 'overview' }">

    {{-- En-tête + Filtres --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Point financier</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Vue d'ensemble des revenus et performances des instituts</p>
        </div>
        <form method="GET" class="flex items-center gap-2">
            <select name="annee" onchange="this.form.submit()" class="form-input text-sm py-1.5 w-auto">
                @foreach($anneesDisponibles as $a)
                    <option value="{{ $a }}" {{ $annee == $a ? 'selected' : '' }}>{{ $a }}</option>
                @endforeach
            </select>
            <select name="mois" onchange="this.form.submit()" class="form-input text-sm py-1.5 w-auto">
                <option value="">Tous les mois</option>
                @foreach($moisLabels as $i => $label)
                    <option value="{{ $i + 1 }}" {{ $moisFiltre == $i + 1 ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- Onglets --}}
    <div class="flex items-center gap-1 bg-gray-100 dark:bg-slate-800 rounded-xl p-1">
        <button @click="tab = 'overview'" :class="tab === 'overview' ? 'bg-white dark:bg-slate-700 shadow-sm text-gray-900 dark:text-white font-semibold' : 'text-gray-500 hover:text-gray-700'" class="flex-1 px-4 py-2 rounded-lg text-sm transition-all">
            Vue d'ensemble
        </button>
        <button @click="tab = 'instituts'" :class="tab === 'instituts' ? 'bg-white dark:bg-slate-700 shadow-sm text-gray-900 dark:text-white font-semibold' : 'text-gray-500 hover:text-gray-700'" class="flex-1 px-4 py-2 rounded-lg text-sm transition-all">
            Instituts
        </button>
        <button @click="tab = 'classements'" :class="tab === 'classements' ? 'bg-white dark:bg-slate-700 shadow-sm text-gray-900 dark:text-white font-semibold' : 'text-gray-500 hover:text-gray-700'" class="flex-1 px-4 py-2 rounded-lg text-sm transition-all">
            Classements
        </button>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- TAB 1 : VUE D'ENSEMBLE --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'overview'" x-transition>

        {{-- KPI Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {{-- Revenu total --}}
            <div class="card p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Revenus {{ $annee }}</span>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($revenuTotal, 0, ',', ' ') }}</p>
                <p class="text-xs text-gray-400 mt-1">FCFA</p>
            </div>

            {{-- Revenu mois courant --}}
            <div class="card p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Ce mois</span>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($revenuMoisCourant, 0, ',', ' ') }}</p>
                <p class="text-xs mt-1 {{ $progressionRevenu >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                    {{ $progressionRevenu >= 0 ? '+' : '' }}{{ $progressionRevenu }}% vs mois précédent
                </p>
            </div>

            {{-- Abonnements actifs --}}
            <div class="card p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Abo. actifs</span>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $abonnementsActifs }}</p>
                <p class="text-xs text-gray-400 mt-1">Taux conversion : {{ $tauxConversion }}%</p>
            </div>

            {{-- Panier moyen --}}
            <div class="card p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Panier moyen</span>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($panierMoyen, 0, ',', ' ') }}</p>
                <p class="text-xs text-gray-400 mt-1">FCFA / abonnement</p>
            </div>
        </div>

        {{-- Graphique revenus par mois --}}
        <div class="card p-6 mb-6">
            <h2 class="font-bold text-gray-900 dark:text-white mb-4">Revenus mensuels {{ $annee }}</h2>
            <div class="h-64">
                <canvas id="chartRevenus"></canvas>
            </div>
            <div class="flex items-center gap-6 mt-4 text-xs text-gray-500">
                @if($moisMax)
                <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    Meilleur mois : <strong class="text-emerald-600">{{ $moisLabels[$moisMax - 1] }}</strong> ({{ number_format($revenusData[$moisMax - 1], 0, ',', ' ') }} FCFA)
                </div>
                @endif
                @if($moisMin)
                <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-red-400"></span>
                    Mois le plus faible : <strong class="text-red-500">{{ $moisLabels[$moisMin - 1] }}</strong> ({{ number_format($revenusData[$moisMin - 1], 0, ',', ' ') }} FCFA)
                </div>
                @endif
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-6">
            {{-- Répartition par plan --}}
            <div class="card p-6">
                <h2 class="font-bold text-gray-900 dark:text-white mb-4">Répartition par plan</h2>
                @if($revenusParPlan->isEmpty())
                    <p class="text-sm text-gray-400 text-center py-8">Aucune donnée</p>
                @else
                    <div class="space-y-3">
                        @foreach($revenusParPlan as $rp)
                        @php $pct = $revenuTotal > 0 ? round($rp->total / $revenuTotal * 100, 1) : 0; @endphp
                        <div>
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $rp->plan }}</span>
                                <span class="text-gray-500">{{ number_format($rp->total, 0, ',', ' ') }} FCFA <span class="text-gray-400">({{ $rp->nb }}×)</span></span>
                            </div>
                            <div class="h-2 bg-gray-100 dark:bg-slate-700 rounded-full overflow-hidden">
                                <div class="h-full rounded-full" style="width: {{ $pct }}%; background: linear-gradient(90deg, #9333ea, #ec4899);"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Répartition par période --}}
            <div class="card p-6">
                <h2 class="font-bold text-gray-900 dark:text-white mb-4">Répartition par période</h2>
                @if($revenusParPeriode->isEmpty())
                    <p class="text-sm text-gray-400 text-center py-8">Aucune donnée</p>
                @else
                    <div class="space-y-3">
                        @php
                            $periodeLabels = ['mensuel' => 'Mensuel', 'annuel' => 'Annuel (1 an)', 'triennal' => 'Triennal (3 ans)'];
                            $periodeColors = ['mensuel' => '#3b82f6', 'annuel' => '#10b981', 'triennal' => '#f59e0b'];
                        @endphp
                        @foreach($revenusParPeriode as $rp)
                        @php $pct = $revenuTotal > 0 ? round($rp->total / $revenuTotal * 100, 1) : 0; @endphp
                        <div>
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $periodeLabels[$rp->periode] ?? ucfirst($rp->periode) }}</span>
                                <span class="text-gray-500">{{ number_format($rp->total, 0, ',', ' ') }} FCFA <span class="text-gray-400">({{ $rp->nb }}×)</span></span>
                            </div>
                            <div class="h-2 bg-gray-100 dark:bg-slate-700 rounded-full overflow-hidden">
                                <div class="h-full rounded-full" style="width: {{ $pct }}%; background: {{ $periodeColors[$rp->periode] ?? '#6b7280' }};"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Résumé visuel --}}
                    <div class="mt-6 pt-4 border-t border-gray-100 dark:border-slate-700">
                        <div class="flex items-center gap-4 justify-center">
                            @foreach($revenusParPeriode as $rp)
                            <div class="text-center">
                                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $rp->nb }}</p>
                                <p class="text-[10px] text-gray-400 uppercase font-medium">{{ $periodeLabels[$rp->periode] ?? $rp->periode }}</p>
                            </div>
                            @if(!$loop->last)
                            <div class="w-px h-8 bg-gray-200 dark:bg-slate-600"></div>
                            @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- TAB 2 : INSTITUTS (CA, progression, détails) --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'instituts'" x-cloak x-transition>
        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-700 flex items-center justify-between">
                <h2 class="font-bold text-gray-900 dark:text-white">Performance des instituts — {{ $annee }}</h2>
                <span class="text-xs text-gray-400">{{ $instituts->count() }} instituts</span>
            </div>

            @if($instituts->isEmpty())
                <div class="text-center py-12">
                    <p class="text-gray-400 text-sm">Aucun institut actif.</p>
                </div>
            @else
            <div class="overflow-x-auto">
                <table class="table-auto w-full">
                    <thead>
                        <tr>
                            <th class="text-left">Institut</th>
                            <th class="text-right">CA {{ $annee }}</th>
                            <th class="text-right">CA ce mois</th>
                            <th class="text-right">Progression</th>
                            <th class="text-right">Ventes</th>
                            <th class="text-right">Dépenses</th>
                            <th class="text-right">Bénéfice</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($instituts as $inst)
                        @php
                            $ca = $inst->ca_total ?? 0;
                            $caMois = $inst->ca_mois_courant ?? 0;
                            $caPrev = $caMoisPrecedent[$inst->id] ?? 0;
                            $depenses = $depensesParInstitut[$inst->id] ?? 0;
                            $benefice = $ca - $depenses;
                            $progression = $caPrev > 0 ? round(($caMois - $caPrev) / $caPrev * 100, 1) : ($caMois > 0 ? 100 : 0);
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50">
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0">
                                        <span class="text-xs font-bold text-primary-700 dark:text-primary-400">{{ strtoupper(substr($inst->nom, 0, 2)) }}</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $inst->nom }}</p>
                                        <p class="text-xs text-gray-400">{{ $inst->ville ?? '' }} · {{ $inst->proprietaire->nom_complet ?? $inst->proprietaire->name ?? '—' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="text-right text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($ca, 0, ',', ' ') }}</td>
                            <td class="text-right text-sm font-medium text-gray-700 dark:text-gray-300">{{ number_format($caMois, 0, ',', ' ') }}</td>
                            <td class="text-right">
                                @if($caMois > 0 || $caPrev > 0)
                                <span class="inline-flex items-center gap-0.5 text-xs font-semibold {{ $progression >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                                    @if($progression >= 0)
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                    @else
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                                    @endif
                                    {{ $progression >= 0 ? '+' : '' }}{{ $progression }}%
                                </span>
                                @else
                                <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="text-right text-sm text-gray-600 dark:text-gray-400">{{ $inst->nb_ventes ?? 0 }}</td>
                            <td class="text-right text-sm text-red-500">{{ number_format($depenses, 0, ',', ' ') }}</td>
                            <td class="text-right text-sm font-semibold {{ $benefice >= 0 ? 'text-emerald-600' : 'text-red-500' }}">{{ number_format($benefice, 0, ',', ' ') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t-2 border-gray-200 dark:border-slate-600">
                        <tr class="font-bold">
                            <td class="text-sm text-gray-900 dark:text-white">Total</td>
                            <td class="text-right text-sm text-gray-900 dark:text-white">{{ number_format($instituts->sum('ca_total'), 0, ',', ' ') }}</td>
                            <td class="text-right text-sm text-gray-700 dark:text-gray-300">{{ number_format($instituts->sum('ca_mois_courant'), 0, ',', ' ') }}</td>
                            <td></td>
                            <td class="text-right text-sm text-gray-600 dark:text-gray-400">{{ $instituts->sum('nb_ventes') }}</td>
                            <td class="text-right text-sm text-red-500">{{ number_format($depensesParInstitut->sum(), 0, ',', ' ') }}</td>
                            <td class="text-right text-sm text-emerald-600">{{ number_format($instituts->sum('ca_total') - $depensesParInstitut->sum(), 0, ',', ' ') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- TAB 3 : CLASSEMENTS --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'classements'" x-cloak x-transition>
        <div class="grid lg:grid-cols-2 gap-6">

            {{-- Top CA --}}
            <div class="card p-6">
                <h2 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span class="text-lg">🏆</span> Top CA — {{ $annee }}
                </h2>
                @if($instituts->isEmpty())
                    <p class="text-sm text-gray-400 text-center py-6">Aucune donnée</p>
                @else
                <div class="space-y-3">
                    @foreach($instituts->take(5) as $index => $inst)
                    @php
                        $medals = ['🥇', '🥈', '🥉'];
                        $maxCa = $instituts->first()->ca_total ?: 1;
                        $pct = round(($inst->ca_total ?? 0) / $maxCa * 100);
                    @endphp
                    <div class="flex items-center gap-3">
                        <span class="text-lg w-6 text-center flex-shrink-0">{{ $medals[$index] ?? ($index + 1) . '.' }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="font-medium text-gray-900 dark:text-white truncate">{{ $inst->nom }}</span>
                                <span class="text-gray-500 font-semibold flex-shrink-0 ml-2">{{ number_format($inst->ca_total ?? 0, 0, ',', ' ') }} FCFA</span>
                            </div>
                            <div class="h-1.5 bg-gray-100 dark:bg-slate-700 rounded-full overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r from-amber-400 to-amber-600" style="width: {{ $pct }}%;"></div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Les + constants --}}
            <div class="card p-6">
                <h2 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span class="text-lg">📊</span> Les plus constants — {{ $annee }}
                </h2>
                <p class="text-xs text-gray-400 mb-4">Instituts avec le plus de mois d'activité</p>
                @if($institutsConstants->isEmpty())
                    <p class="text-sm text-gray-400 text-center py-6">Aucune donnée</p>
                @else
                <div class="space-y-3">
                    @foreach($institutsConstants as $ic)
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                            <span class="text-xs font-bold text-blue-700 dark:text-blue-400">{{ $ic->mois_actifs }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $ic->institut->nom }}</p>
                            <p class="text-xs text-gray-400">{{ $ic->mois_actifs }} mois actifs · {{ number_format($ic->ca, 0, ',', ' ') }} FCFA</p>
                        </div>
                        <div class="flex gap-0.5 flex-shrink-0">
                            @for($m = 1; $m <= 12; $m++)
                            <div class="w-2 h-4 rounded-sm {{ $m <= $ic->mois_actifs ? 'bg-blue-500' : 'bg-gray-200 dark:bg-slate-600' }}"></div>
                            @endfor
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Top dépensiers --}}
            <div class="card p-6">
                <h2 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span class="text-lg">💸</span> Plus gros dépensiers — {{ $annee }}
                </h2>
                @if($topDepensiers->isEmpty())
                    <p class="text-sm text-gray-400 text-center py-6">Aucune donnée</p>
                @else
                <div class="space-y-3">
                    @foreach($topDepensiers as $td)
                    @php $maxDep = $topDepensiers->first()->total ?: 1; $pct = round($td->total / $maxDep * 100); @endphp
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="font-medium text-gray-900 dark:text-white">{{ $td->institut->nom }}</span>
                            <span class="text-red-500 font-semibold">{{ number_format($td->total, 0, ',', ' ') }} FCFA</span>
                        </div>
                        <div class="h-1.5 bg-gray-100 dark:bg-slate-700 rounded-full overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-red-400 to-red-600" style="width: {{ $pct }}%;"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Nombre d'abonnements par mois --}}
            <div class="card p-6">
                <h2 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span class="text-lg">📈</span> Abonnements validés / mois
                </h2>
                <div class="h-48">
                    <canvas id="chartAbonnements"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const moisLabels = @json($moisLabels);
    const revenusData = @json($revenusData);
    const nbData = @json($nbData);

    // Graphique revenus
    const ctxR = document.getElementById('chartRevenus');
    if (ctxR) {
        new Chart(ctxR, {
            type: 'bar',
            data: {
                labels: moisLabels,
                datasets: [{
                    label: 'Revenus (FCFA)',
                    data: revenusData,
                    backgroundColor: revenusData.map((v, i) => {
                        const max = Math.max(...revenusData);
                        const min = Math.min(...revenusData.filter(x => x > 0));
                        if (v === max) return 'rgba(16, 185, 129, 0.8)';
                        if (v === min && v > 0) return 'rgba(239, 68, 68, 0.6)';
                        return 'rgba(147, 51, 234, 0.6)';
                    }),
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => new Intl.NumberFormat('fr-FR').format(ctx.raw) + ' FCFA'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: v => new Intl.NumberFormat('fr-FR', { notation: 'compact' }).format(v)
                        },
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // Graphique abonnements
    const ctxA = document.getElementById('chartAbonnements');
    if (ctxA) {
        new Chart(ctxA, {
            type: 'line',
            data: {
                labels: moisLabels,
                datasets: [{
                    label: 'Abonnements',
                    data: nbData,
                    borderColor: '#9333ea',
                    backgroundColor: 'rgba(147, 51, 234, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#9333ea',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 },
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }
});
</script>
@endpush
@endsection
