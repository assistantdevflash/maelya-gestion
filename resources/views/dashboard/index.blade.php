<x-dashboard-layout>
    <div class="space-y-6">

        {{-- ═══ EN-TÊTE ═══ --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-display font-bold text-gray-900 tracking-tight">Bonjour, {{ auth()->user()->prenom }} <span class="inline-block animate-bounce-sm">👋</span></h1>
                <p class="text-sm text-gray-500 mt-1">{{ now()->isoFormat('dddd D MMMM YYYY') }} — Voici vos performances</p>
            </div>
            <a href="{{ route('dashboard.caisse') }}" class="btn-primary group">
                <svg class="w-4 h-4 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Nouvelle vente
            </a>
        </div>

        {{-- Alerte abonnement --}}
        @if(isset($joursRestants) && $joursRestants <= 7)
            <div class="flex items-center gap-3 p-4 bg-gradient-to-r from-amber-50 to-orange-50 rounded-2xl ring-1 ring-amber-200/60">
                <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-amber-800">Votre abonnement expire dans {{ $joursRestants }} jour(s)</p>
                    <a href="{{ route('abonnement.plans') }}" class="text-xs font-bold text-amber-600 hover:text-amber-800 underline underline-offset-2">Renouveler maintenant →</a>
                </div>
            </div>
        @endif

        {{-- Bonus parrainage récent --}}
        @php
            $parrainagesRecents = auth()->user()->parrainagesEffectues()
                ->where('statut', 'valide')
                ->where('updated_at', '>=', now()->subDays(7))
                ->with('filleul')
                ->get();
        @endphp
        @if($parrainagesRecents->isNotEmpty())
        @php $lastParrainageKey = $parrainagesRecents->max('updated_at')->timestamp; @endphp
        <div x-data="{ show: localStorage.getItem('parrainage_dismissed') !== '{{ $lastParrainageKey }}' }" x-show="show" x-transition class="relative card p-4 bg-emerald-50 border-emerald-200 flex items-start gap-3">
            <button @click="show = false; localStorage.setItem('parrainage_dismissed', '{{ $lastParrainageKey }}')" class="absolute top-2 right-2 p-1 text-emerald-400 hover:text-emerald-600 rounded-lg hover:bg-emerald-100 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <div class="w-9 h-9 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600 flex-shrink-0 mt-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>
            </div>
            <div class="flex-1 pr-6">
                <p class="font-semibold text-emerald-900">🎉 Bonus parrainage crédité !</p>
                <p class="text-sm text-emerald-700 mt-0.5">
                    @foreach($parrainagesRecents as $pr)
                        <strong>{{ $pr->filleul->nom_complet ?? $pr->filleul->name }}</strong> a souscrit un abonnement — vous avez reçu <strong>+{{ $pr->jours_offerts_parrain }} jours</strong> gratuits.@if(!$loop->last)<br>@endif
                    @endforeach
                </p>
            </div>
        </div>
        @endif

        {{-- Carte abonnement (alerte si < 8 jours) --}}
        @if(isset($abonnement) && $abonnement && $abonnement->joursRestants() <= 8)
        <div class="card p-4 flex items-center gap-4 bg-gradient-to-r from-primary-50/50 to-secondary-50/50 border-primary-100/50">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <p class="font-semibold text-gray-900 text-sm">{{ $abonnement->plan->nom ?? 'Abonnement' }}</p>
                    <span class="badge badge-success text-[10px]">Actif</span>
                </div>
                <p class="text-xs text-gray-500 mt-0.5">
                    Expire le {{ $abonnement->expire_le->format('d/m/Y') }}
                    <span class="text-gray-400">·</span>
                    {{ $abonnement->joursRestants() }} jour(s) restant(s)
                    @if($abonnement->plan->max_employes)
                        <span class="text-gray-400">·</span>
                        {{ $abonnement->plan->max_employes }} employé(s) max
                    @endif
                </p>
            </div>
            <a href="{{ route('abonnement.plans') }}" class="btn-outline text-xs py-1.5 px-3 flex-shrink-0">
                Gérer
            </a>
        </div>
        @endif

        {{-- ═══ KPI CARDS ═══ --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- CA du jour --}}
            <div class="stat-card group">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, rgba(147,51,234,0.1), rgba(168,85,247,0.15));">
                        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    @if(isset($evolutionCa) && $evolutionCa >= 0)
                        <span class="badge-success text-[10px]">+{{ $evolutionCa }}%</span>
                    @endif
                </div>
                <p class="text-2xl font-display font-bold text-gray-900 tracking-tight">{{ number_format($caJour ?? 0, 0, ',', ' ') }}</p>
                <p class="text-xs text-gray-400 mt-1">FCFA aujourd'hui</p>
                <div class="mt-2 pt-2 border-t border-gray-100/80">
                    <p class="text-xs font-medium text-primary-600">{{ number_format($caMois ?? 0, 0, ',', ' ') }} F ce mois</p>
                </div>
            </div>

            {{-- Ventes du jour --}}
            <div class="stat-card group">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, rgba(236,72,153,0.1), rgba(244,114,182,0.15));">
                        <svg class="w-5 h-5 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-display font-bold text-gray-900 tracking-tight">{{ $ventesJour ?? 0 }}</p>
                <p class="text-xs text-gray-400 mt-1">Ventes aujourd'hui</p>
                <div class="mt-2 pt-2 border-t border-gray-100/80">
                    <p class="text-xs font-medium text-secondary-600">{{ $ventesMois ?? 0 }} ce mois</p>
                </div>
            </div>

            {{-- Clients --}}
            <div class="stat-card group">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-blue-50">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-display font-bold text-gray-900 tracking-tight">{{ $totalClients ?? 0 }}</p>
                <p class="text-xs text-gray-400 mt-1">Clients actifs</p>
                <div class="mt-2 pt-2 border-t border-gray-100/80">
                    <p class="text-xs font-medium text-blue-600">+{{ $nouveauxClientsJour ?? 0 }} aujourd'hui</p>
                </div>
            </div>

            {{-- Bénéfice --}}
            <div class="stat-card group">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center {{ ($beneficeMois ?? 0) >= 0 ? 'bg-emerald-50' : 'bg-red-50' }}">
                        <svg class="w-5 h-5 {{ ($beneficeMois ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-display font-bold text-gray-900 tracking-tight">{{ number_format(abs($beneficeMois ?? 0), 0, ',', ' ') }}</p>
                <p class="text-xs text-gray-400 mt-1">Bénéfice ce mois</p>
                <div class="mt-2 pt-2 border-t border-gray-100/80">
                    <p class="text-xs font-medium {{ ($beneficeMois ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600' }}">FCFA net</p>
                </div>
            </div>
        </div>

        {{-- ═══ GRAPHIQUE + SIDE PANELS ═══ --}}
        <div class="grid lg:grid-cols-3 gap-6">
            {{-- Graphique CA --}}
            <div class="lg:col-span-2 card">
                <div class="p-6 pb-0">
                    <div class="flex items-center justify-between mb-1">
                        <div>
                            <h2 class="font-display font-bold text-gray-900">Chiffre d'affaires</h2>
                            <p class="text-xs text-gray-400 mt-0.5">30 derniers jours</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full" style="background: linear-gradient(135deg, #9333ea, #ec4899);"></div>
                            <span class="text-xs text-gray-500">CA (FCFA)</span>
                        </div>
                    </div>
                </div>
                <div class="p-6 pt-4">
                    <canvas id="caChart" height="180"></canvas>
                </div>
            </div>

            {{-- Panels latéraux --}}
            <div class="space-y-4">
                {{-- Stock en alerte --}}
                <div class="card p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg bg-red-50 flex items-center justify-center">
                                <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 text-sm">Alertes stock</h3>
                        </div>
                        <a href="{{ route('dashboard.stock.index') }}" class="text-xs text-primary-600 hover:text-primary-700 font-medium">Voir →</a>
                    </div>
                    @if(isset($alertesStock) && count($alertesStock) > 0)
                        <div class="space-y-2.5">
                            @foreach($alertesStock->take(4) as $produit)
                                <div class="flex items-center justify-between group">
                                    <span class="text-sm text-gray-600 truncate group-hover:text-gray-900 transition-colors">{{ $produit->nom }}</span>
                                    <span class="badge-danger ml-2 flex-shrink-0">{{ $produit->stock }} {{ $produit->unite }}</span>
                                </div>
                            @endforeach
                        </div>
                        @if(count($alertesStock) > 4)
                            <p class="text-xs text-gray-400 mt-3 pt-2 border-t border-gray-100">+{{ count($alertesStock) - 4 }} autres produits</p>
                        @endif
                    @else
                        <div class="text-center py-4">
                            <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center mx-auto mb-2">
                                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <p class="text-xs text-gray-400">Tout va bien !</p>
                        </div>
                    @endif
                </div>

                {{-- Dernières ventes --}}
                <div class="card p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg bg-primary-50 flex items-center justify-center">
                                <svg class="w-3.5 h-3.5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 text-sm">Dernières ventes</h3>
                        </div>
                        <a href="{{ route('dashboard.ventes.index') }}" class="text-xs text-primary-600 hover:text-primary-700 font-medium">Tout →</a>
                    </div>
                    @if(isset($dernieresVentes) && count($dernieresVentes) > 0)
                        <div class="space-y-3">
                            @foreach($dernieresVentes->take(4) as $vente)
                                <div class="flex items-center justify-between group">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ number_format($vente->total, 0, ',', ' ') }} F</p>
                                        <p class="text-[11px] text-gray-400">{{ $vente->created_at->diffForHumans() }}</p>
                                    </div>
                                    <span class="badge {{ $vente->mode_paiement === 'mobile_money' ? 'badge-primary' : 'bg-gray-100 text-gray-600' }} text-[10px]">
                                        {{ $vente->mode_paiement === 'mobile_money' ? 'Mobile' : 'Cash' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-gray-400 text-center py-4">Aucune vente aujourd'hui</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- ═══ RÉSUMÉ BAS ═══ --}}
        <div class="grid sm:grid-cols-2 gap-4">
            {{-- Modes de paiement --}}
            <div class="card p-5">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-7 h-7 rounded-lg bg-indigo-50 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900 text-sm">Paiements ce mois</h3>
                </div>
                @php
                    $totalPaiements = ($paiementsCash ?? 0) + ($paiementsMobile ?? 0) + ($paiementsCarte ?? 0) + ($paiementsMixte ?? 0);
                    $pctCash   = $totalPaiements > 0 ? round(($paiementsCash   ?? 0) / $totalPaiements * 100) : 0;
                    $pctMobile = $totalPaiements > 0 ? round(($paiementsMobile ?? 0) / $totalPaiements * 100) : 0;
                    $pctCarte  = $totalPaiements > 0 ? round(($paiementsCarte  ?? 0) / $totalPaiements * 100) : 0;
                    $pctMixte  = $totalPaiements > 0 ? (100 - $pctCash - $pctMobile - $pctCarte) : 0;
                @endphp
                <div class="space-y-3">
                    @if($pctCash > 0)
                    <div>
                        <div class="flex justify-between text-xs mb-1.5">
                            <span class="text-gray-600 font-medium">💵 Espèces</span>
                            <span class="font-bold text-gray-900">{{ $pctCash }}%</span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700" style="width: {{ $pctCash }}%; background: linear-gradient(90deg, #9333ea, #a855f7);"></div>
                        </div>
                    </div>
                    @endif
                    @if($pctCarte > 0)
                    <div>
                        <div class="flex justify-between text-xs mb-1.5">
                            <span class="text-gray-600 font-medium">💳 Carte</span>
                            <span class="font-bold text-gray-900">{{ $pctCarte }}%</span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700" style="width: {{ $pctCarte }}%; background: linear-gradient(90deg, #2563eb, #3b82f6);"></div>
                        </div>
                    </div>
                    @endif
                    @if($pctMobile > 0)
                    <div>
                        <div class="flex justify-between text-xs mb-1.5">
                            <span class="text-gray-600 font-medium">📱 Mobile Money</span>
                            <span class="font-bold text-gray-900">{{ $pctMobile }}%</span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700" style="width: {{ $pctMobile }}%; background: linear-gradient(90deg, #db2777, #ec4899);"></div>
                        </div>
                    </div>
                    @endif
                    @if($pctMixte > 0)
                    <div>
                        <div class="flex justify-between text-xs mb-1.5">
                            <span class="text-gray-600 font-medium">💵+📱 Mixte</span>
                            <span class="font-bold text-gray-900">{{ $pctMixte }}%</span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700" style="width: {{ $pctMixte }}%; background: linear-gradient(90deg, #7c3aed, #ec4899);"></div>
                        </div>
                    </div>
                    @endif
                    @if($totalPaiements === 0)
                        <p class="text-xs text-gray-400 text-center py-2">Aucune vente ce mois</p>
                    @endif
                </div>
            </div>

            {{-- Résumé mensuel --}}
            <div class="card p-5">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900 text-sm">Résumé mensuel</h3>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Chiffre d'affaires</span>
                        <span class="text-sm font-bold text-emerald-600">{{ number_format($caMois ?? 0, 0, ',', ' ') }} F</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Dépenses</span>
                        <span class="text-sm font-bold text-red-500">-{{ number_format($depensesMois ?? 0, 0, ',', ' ') }} F</span>
                    </div>
                    <div class="border-t border-gray-100 pt-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-bold text-gray-900">Bénéfice net</span>
                            <span class="text-base font-display font-bold {{ ($beneficeMois ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ number_format($beneficeMois ?? 0, 0, ',', ' ') }} F
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-slot:scripts>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        const ctx = document.getElementById('caChart').getContext('2d');
        const chartData = @json($chartData ?? ['labels' => [], 'values' => []]);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'CA (FCFA)',
                    data: chartData.values,
                    fill: true,
                    backgroundColor: 'rgba(139, 92, 246, 0.08)',
                    borderColor: 'rgb(139, 92, 246)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgb(139, 92, 246)',
                    pointRadius: 3,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.04)' },
                        ticks: {
                            callback: v => new Intl.NumberFormat('fr-FR').format(v),
                            font: { size: 11 }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 } }
                    }
                }
            }
        });
    </script>
    </x-slot:scripts>
</x-dashboard-layout>
