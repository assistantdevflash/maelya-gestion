@extends('layouts.admin')
@section('page-title', 'Dashboard Finance')

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="{ tab: new URLSearchParams(window.location.search).get('tab') || 'overview' }">

    {{-- En-tête + filtres --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="page-title">Dashboard Finance</h1>
            <p class="page-subtitle">Revenus, paiements, CA en ligne et performances des instituts</p>
        </div>
        <form method="GET" class="flex items-center gap-2">
            <input type="hidden" name="tab" :value="tab">
            <select name="annee" class="form-input text-sm w-auto" onchange="this.form.submit()">
                @foreach($anneesDisponibles as $a)
                    <option value="{{ $a }}" @selected($annee == $a)>{{ $a }}</option>
                @endforeach
            </select>
            <select name="mois" class="form-input text-sm w-auto" onchange="this.form.submit()">
                <option value="">Tous les mois</option>
                @foreach($moisLabels as $i => $label)
                    <option value="{{ $i + 1 }}" @selected($moisFiltre == $i + 1)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @if(session('success'))
    <div class="alert-success">{{ session('success') }}</div>
    @endif

    {{-- Onglets --}}
    <div class="flex items-center gap-1 bg-gray-100 dark:bg-slate-800 rounded-xl p-1 overflow-x-auto">
        <button @click="tab = 'overview'" :class="tab === 'overview' ? 'bg-white dark:bg-slate-700 shadow-sm text-gray-900 dark:text-white font-semibold' : 'text-gray-500 hover:text-gray-700'" class="flex-1 px-4 py-2 rounded-lg text-sm transition-all whitespace-nowrap">
            Vue d'ensemble
        </button>
        <button @click="tab = 'instituts'" :class="tab === 'instituts' ? 'bg-white dark:bg-slate-700 shadow-sm text-gray-900 dark:text-white font-semibold' : 'text-gray-500 hover:text-gray-700'" class="flex-1 px-4 py-2 rounded-lg text-sm transition-all whitespace-nowrap">
            Instituts
        </button>
        <button @click="tab = 'classements'" :class="tab === 'classements' ? 'bg-white dark:bg-slate-700 shadow-sm text-gray-900 dark:text-white font-semibold' : 'text-gray-500 hover:text-gray-700'" class="flex-1 px-4 py-2 rounded-lg text-sm transition-all whitespace-nowrap">
            Classements
        </button>
        <button @click="tab = 'transactions'" :class="tab === 'transactions' ? 'bg-white dark:bg-slate-700 shadow-sm text-gray-900 dark:text-white font-semibold' : 'text-gray-500 hover:text-gray-700'" class="flex-1 px-4 py-2 rounded-lg text-sm transition-all whitespace-nowrap">
            Transactions
        </button>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- TAB 1 : VUE D'ENSEMBLE --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'overview'" x-transition>

        {{-- KPIs abonnements --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="card p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Revenus {{ $annee }}</span>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($revenuTotal, 0, ',', ' ') }}</p>
                <p class="text-xs text-gray-400 mt-1">FCFA · {{ $nbAbonnements }} abonnements</p>
            </div>

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

        {{-- KPIs paiements en ligne --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="card p-5">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-gray-500 dark:text-slate-400 font-semibold uppercase tracking-wider">CA plateforme</p>
                    <div class="w-9 h-9 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 dark:text-purple-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                </div>
                <p class="text-2xl font-bold mt-3 text-gray-900 dark:text-white">{{ number_format($caPlateformeAnnee, 0, ',', ' ') }} F</p>
                <p class="text-xs mt-1 text-gray-500 dark:text-slate-400">{{ number_format($nbTransactionsAnnee, 0) }} transactions · {{ number_format($totalFrais, 0, ',', ' ') }} F de frais</p>
            </div>

            <div class="card p-5">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-gray-500 dark:text-slate-400 font-semibold uppercase tracking-wider">CA boutique en ligne</p>
                    <div class="w-9 h-9 rounded-xl bg-pink-100 dark:bg-pink-900/30 flex items-center justify-center text-pink-600 dark:text-pink-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    </div>
                </div>
                <p class="text-2xl font-bold mt-3 text-gray-900 dark:text-white">{{ number_format($caBoutiqueAnnee, 0, ',', ' ') }} F</p>
                <p class="text-xs mt-1 text-gray-500 dark:text-slate-400">{{ number_format($nbCommandesPayees, 0) }} commandes payées</p>
            </div>

            <div class="card p-5">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-gray-500 dark:text-slate-400 font-semibold uppercase tracking-wider">Taux de succès</p>
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <p class="text-2xl font-bold mt-3 text-gray-900 dark:text-white">{{ $tauxSucces }}%</p>
                <p class="text-xs mt-1 text-gray-500 dark:text-slate-400">{{ $nbReussies }} réussies / {{ $nbTotal }} totales</p>
            </div>

            <div class="card p-5">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-gray-500 dark:text-slate-400 font-semibold uppercase tracking-wider">Remboursés</p>
                    <div class="w-9 h-9 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                    </div>
                </div>
                <p class="text-2xl font-bold mt-3 text-gray-900 dark:text-white">{{ number_format($montantRembourse, 0, ',', ' ') }} F</p>
                <p class="text-xs mt-1 text-gray-500 dark:text-slate-400">{{ $nbRemboursements }} remboursements</p>
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-6 mb-6">
            {{-- Graphique revenus abonnements --}}
            <div class="card p-6">
                <h2 class="font-bold text-gray-900 dark:text-white mb-4">Revenus mensuels (abonnements) {{ $annee }}</h2>
                <div class="h-64">
                    <canvas id="chartRevenus"></canvas>
                </div>
                <div class="flex items-center gap-6 mt-4 text-xs text-gray-500 flex-wrap">
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

            {{-- Graphique CA en ligne (plateforme + boutique) --}}
            <div class="card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-bold text-gray-900 dark:text-white">CA en ligne {{ $annee }}</h2>
                    <div class="flex items-center gap-4 text-xs">
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-purple-500"></span> Plateforme</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-pink-400"></span> Boutique</span>
                    </div>
                </div>
                <div class="h-64">
                    <canvas id="chartCA"></canvas>
                </div>
            </div>
        </div>

        {{-- Répartitions --}}
        <div class="grid lg:grid-cols-2 gap-6">
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

            <div class="card p-6">
                <h2 class="font-bold text-gray-900 dark:text-white mb-4">Répartition par période</h2>
                @if($revenusParPeriode->isEmpty())
                    <p class="text-sm text-gray-400 text-center py-8">Aucune donnée</p>
                @else
                    @php
                        $periodeLabels = ['mensuel' => 'Mensuel', 'trimestre' => '3 mois', 'semestre' => '6 mois', 'annuel' => 'Annuel (1 an)', 'triennal' => 'Triennal (3 ans)'];
                        $periodeColors = ['mensuel' => '#3b82f6', 'trimestre' => '#f59e0b', 'semestre' => '#10b981', 'annuel' => '#06b6d4', 'triennal' => '#8b5cf6'];
                    @endphp
                    <div class="space-y-3">
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
                @endif
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-6">
            {{-- Par type de transaction --}}
            <div class="card p-6">
                <h2 class="font-bold text-gray-900 dark:text-white mb-4">Transactions par type</h2>
                @if($parType->count())
                <div class="space-y-4">
                    @php $parTypeMax = $parType->max('total') ?: 1; @endphp
                    @foreach($parType as $item)
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600 dark:text-slate-300 capitalize">{{ str_replace('_', ' ', $item->type) }}</span>
                            <span class="font-semibold">{{ number_format($item->total, 0, ',', ' ') }} F</span>
                        </div>
                        <div class="h-2 bg-gray-100 dark:bg-slate-700 rounded-full overflow-hidden">
                            <div class="h-full bg-purple-500 rounded-full" style="width: {{ round($item->total / $parTypeMax * 100) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $item->nb }} transaction(s)</p>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-sm text-gray-400">Aucune donnée pour {{ $annee }}.</p>
                @endif
            </div>

            {{-- Par méthode + remboursements --}}
            <div class="card p-6">
                <h2 class="font-bold text-gray-900 dark:text-white mb-4">Transactions par méthode</h2>
                @if($parMethode->count())
                <div class="space-y-4">
                    @php $parMethodeMax = $parMethode->max('total') ?: 1; @endphp
                    @foreach($parMethode as $item)
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600 dark:text-slate-300">{{ $item->payment_method_code === 'geniuspay' ? '🟣 GeniusPay' : '🏦 Virement bancaire' }}</span>
                            <span class="font-semibold">{{ number_format($item->total, 0, ',', ' ') }} F</span>
                        </div>
                        <div class="h-2 bg-gray-100 dark:bg-slate-700 rounded-full overflow-hidden">
                            <div class="h-full bg-pink-500 rounded-full" style="width: {{ round($item->total / $parMethodeMax * 100) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $item->nb }} transaction(s)</p>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-sm text-gray-400">Aucune donnée pour {{ $annee }}.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- TAB 2 : INSTITUTS --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'instituts'" x-cloak x-transition>
        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-700 flex items-center justify-between">
                <h2 class="font-bold text-gray-900 dark:text-white">Performance des instituts — {{ $moisFiltre ? $moisLabels[$moisFiltre - 1] . ' ' : '' }}{{ $annee }}</h2>
                <span class="text-xs text-gray-400">{{ $instituts->count() }} instituts</span>
            </div>

            @if($instituts->isEmpty())
                <div class="text-center py-12"><p class="text-gray-400 text-sm">Aucun institut actif.</p></div>
            @else

            {{-- Mobile : cartes --}}
            <div class="sm:hidden divide-y divide-gray-100 dark:divide-slate-700">
                @foreach($instituts as $inst)
                @php
                    $ca = $inst->ca_total ?? 0;
                    $caMois = $inst->ca_mois_courant ?? 0;
                    $caPrev = $caMoisPrecedent[$inst->id] ?? 0;
                    $depenses = $depensesParInstitut[$inst->id] ?? 0;
                    $benefice = $ca - $depenses;
                    $progression = $caPrev > 0 ? round(($caMois - $caPrev) / $caPrev * 100, 1) : ($caMois > 0 ? 100 : 0);
                @endphp
                <div class="p-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 rounded-lg bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0">
                            <span class="text-xs font-bold text-primary-700 dark:text-primary-400">{{ strtoupper(substr($inst->nom, 0, 2)) }}</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $inst->nom }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ $inst->ville ?? '' }} · {{ $inst->proprietaire->nom_complet ?? '—' }}</p>
                        </div>
                        @if($caMois > 0 || $caPrev > 0)
                        <span class="ml-auto text-xs font-semibold flex-shrink-0 {{ $progression >= 0 ? 'text-emerald-600' : 'text-red-500' }}">{{ $progression >= 0 ? '+' : '' }}{{ $progression }}%</span>
                        @endif
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="bg-gray-50 dark:bg-slate-800 rounded-xl p-2">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wide">CA {{ $annee }}</p>
                            <p class="text-xs font-bold text-gray-900 dark:text-white">{{ number_format($ca, 0, ',', ' ') }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-slate-800 rounded-xl p-2">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wide">Ce mois</p>
                            <p class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ number_format($caMois, 0, ',', ' ') }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-slate-800 rounded-xl p-2">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wide">Bénéfice</p>
                            <p class="text-xs font-bold {{ $benefice >= 0 ? 'text-emerald-600' : 'text-red-500' }}">{{ number_format($benefice, 0, ',', ' ') }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
                <div class="px-4 py-3 bg-gray-50 dark:bg-slate-800 border-t-2 border-gray-300 dark:border-slate-500 flex justify-between text-xs font-bold">
                    <span class="text-gray-700 dark:text-white uppercase tracking-wide">Total</span>
                    <span class="text-gray-900 dark:text-white">{{ number_format($instituts->sum('ca_total'), 0, ',', ' ') }} FCFA</span>
                </div>
            </div>

            {{-- Desktop : tableau --}}
            <div class="hidden sm:block overflow-x-auto">
                <table class="table-auto w-full">
                    <thead>
                        <tr>
                            <th class="text-left">Institut</th>
                            <th class="text-right">CA {{ $moisFiltre ? $moisLabels[$moisFiltre - 1] : $annee }}</th>
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
                    <tfoot>
                        <tr class="bg-gray-50 dark:bg-slate-800 border-t-2 border-gray-300 dark:border-slate-500">
                            <td class="!py-4"><span class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wide">Total</span></td>
                            <td class="text-right !py-4"><span class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($instituts->sum('ca_total'), 0, ',', "\u{202F}") }}</span></td>
                            <td class="text-right !py-4"><span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ number_format($instituts->sum('ca_mois_courant'), 0, ',', "\u{202F}") }}</span></td>
                            <td class="!py-4"></td>
                            <td class="text-right !py-4"><span class="text-sm font-bold text-gray-600 dark:text-gray-400">{{ $instituts->sum('nb_ventes') }}</span></td>
                            <td class="text-right !py-4"><span class="text-sm font-bold text-red-500">{{ number_format($depensesParInstitut->sum(), 0, ',', "\u{202F}") }}</span></td>
                            <td class="text-right !py-4">
                                @php $beneficeTotal = $instituts->sum('ca_total') - $depensesParInstitut->sum(); @endphp
                                <span class="text-sm font-bold {{ $beneficeTotal >= 0 ? 'text-emerald-600' : 'text-red-500' }}">{{ number_format($beneficeTotal, 0, ',', "\u{202F}") }}</span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endif
        </div>

        {{-- Top instituts boutique --}}
        <div class="card p-6 mt-6">
            <h2 class="font-bold text-lg text-gray-900 dark:text-white mb-4">🛍️ Top CA boutique en ligne</h2>
            @if($topInstitutsBoutique->count())
            <div class="space-y-3">
                @php $topMax = $topInstitutsBoutique->max('ca_en_ligne') ?: 1; @endphp
                @foreach($topInstitutsBoutique as $i => $inst)
                <div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-gray-800 dark:text-slate-200 truncate">
                            <span class="text-xs {{ $i === 0 ? 'text-amber-500' : 'text-gray-400' }}">#{{ $i + 1 }}</span> {{ $inst->nom }}
                        </span>
                        <span class="font-semibold">{{ number_format($inst->ca_en_ligne, 0, ',', ' ') }} F</span>
                    </div>
                    <div class="h-1.5 bg-gray-100 dark:bg-slate-700 rounded-full overflow-hidden mt-1">
                        <div class="h-full {{ $i === 0 ? 'bg-gradient-to-r from-amber-400 to-orange-500' : 'bg-purple-400' }} rounded-full" style="width: {{ round($inst->ca_en_ligne / $topMax * 100) }}%"></div>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $inst->nb_commandes }} commandes · {{ $inst->ville ?? '' }}</p>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-gray-400">Aucune commande payée en ligne.</p>
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- TAB 3 : CLASSEMENTS --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'classements'" x-cloak x-transition>
        <div class="grid lg:grid-cols-2 gap-6">
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

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- TAB 4 : TRANSACTIONS --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'transactions'" x-cloak x-transition>
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-slate-700">
                <h2 class="font-bold text-lg text-gray-900 dark:text-white">Transactions récentes</h2>
                <a href="{{ route('admin.payment-transactions.index') }}" class="text-xs font-semibold text-purple-600 dark:text-purple-400 hover:underline">Voir tout →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-slate-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Référence</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Client</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Montant</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                        @forelse($recentes as $tx)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="px-6 py-3 font-mono text-xs font-semibold">{{ $tx->reference }}</td>
                            <td class="px-6 py-3">
                                <span class="font-medium">{{ $tx->user?->name }}</span>
                                <span class="text-xs text-gray-400 block">{{ $tx->user?->email }}</span>
                            </td>
                            <td class="px-6 py-3 capitalize text-gray-600 dark:text-slate-300">{{ str_replace('_', ' ', $tx->type) }}</td>
                            <td class="px-6 py-3 text-right font-semibold">{{ number_format($tx->amount, 0, ',', ' ') }} F</td>
                            <td class="px-6 py-3">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold
                                    {{ $tx->status === 'completed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400' :
                                       ($tx->status === 'refunded' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400' :
                                       ($tx->status === 'pending' || $tx->status === 'processing' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400' :
                                       'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400')) }}">
                                    @if($tx->status === 'completed') ✓ @endif
                                    @if($tx->status === 'refunded') ↩ @endif
                                    {{ $tx->status === 'refunded' ? 'Remboursé' : ucfirst($tx->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-gray-600 dark:text-slate-400">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-slate-400">Aucune transaction.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dark = document.documentElement.classList.contains('dark');
    const gridColor = dark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
    const labelColor = dark ? '#94a3b8' : '#6b7280';
    const moisLabels = @json($moisLabels);

    // Chart 1 : revenus abonnements par mois
    const ctxR = document.getElementById('chartRevenus');
    if (ctxR) {
        const revenusData = @json($revenusData);
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
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: 'transparent' }, ticks: { color: labelColor, font: { size: 11 } } },
                    y: { grid: { color: gridColor }, ticks: { color: labelColor, font: { size: 11 }, callback: (v) => v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v } }
                }
            }
        });
    }

    // Chart 2 : CA en ligne (plateforme + boutique)
    const ctxCA = document.getElementById('chartCA');
    if (ctxCA) {
        new Chart(ctxCA, {
            type: 'bar',
            data: {
                labels: moisLabels,
                datasets: [
                    { label: 'Plateforme', data: @json($plateformeData), backgroundColor: '#9333ea', borderRadius: 6, stack: 'stack' },
                    { label: 'Boutique', data: @json($boutiqueData), backgroundColor: '#ec4899', borderRadius: 6, stack: 'stack' }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: 'transparent' }, ticks: { color: labelColor, font: { size: 11 } } },
                    y: { grid: { color: gridColor }, ticks: { color: labelColor, font: { size: 11 }, callback: (v) => v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v } }
                }
            }
        });
    }

    // Chart 3 : abonnements validés / mois
    const ctxA = document.getElementById('chartAbonnements');
    if (ctxA) {
        new Chart(ctxA, {
            type: 'line',
            data: {
                labels: moisLabels,
                datasets: [{
                    label: 'Abonnements',
                    data: @json($nbData),
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#8b5cf6'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: 'transparent' }, ticks: { color: labelColor, font: { size: 11 } } },
                    y: { grid: { color: gridColor }, ticks: { color: labelColor, font: { size: 11 }, precision: 0 } }
                }
            }
        });
    }
});
</script>
@endpush
