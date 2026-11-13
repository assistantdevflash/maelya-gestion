<x-dashboard-layout>
    <x-slot name="title">Comparatif établissements</x-slot>

<div class="space-y-5">

    {{-- ═══ En-tête ═══ --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="page-title">Comparatif multi-établissements</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                {{ $periode['libelle'] }} · {{ $instituts->count() }} établissement{{ $instituts->count() > 1 ? 's' : '' }} actif{{ $instituts->count() > 1 ? 's' : '' }}
            </p>
        </div>
        <a href="{{ route('dashboard.comparatif.export', request()->query()) }}" class="btn-outline border self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Exporter
        </a>
    </div>

    {{-- ═══ Sélecteur de période ═══ --}}
    <div class="card p-4 space-y-3" x-data="{ perso: {{ request('periode') === 'perso' ? 'true' : 'false' }} }">
        <div class="flex flex-wrap gap-1.5">
            @php
                $periodes = [
                    'hier'         => 'Hier',
                    '7j'           => '7 jours',
                    '30j'          => '30 jours',
                    'mois'         => 'Ce mois',
                    'mois_dernier' => 'Mois dernier',
                    'annee'        => 'Cette année',
                ];
                $courante = request('periode', 'mois');
            @endphp
            @foreach($periodes as $code => $libelle)
                <a href="{{ route('dashboard.comparatif.index', array_merge(request()->except(['periode', 'du', 'au']), ['periode' => $code])) }}"
                   class="px-2.5 py-1 rounded-lg text-[11px] font-semibold transition-colors
                          {{ $courante === $code ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                    {{ $libelle }}
                </a>
            @endforeach
            <button type="button" @click="perso = !perso"
                    class="px-2.5 py-1 rounded-lg text-[11px] font-semibold transition-colors
                           {{ $courante === 'perso' ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                Période libre
            </button>
        </div>

        <form method="GET" action="{{ route('dashboard.comparatif.index') }}" x-show="perso" x-cloak
              class="flex flex-wrap items-end gap-3">
            <input type="hidden" name="periode" value="perso">
            @if(request('tri'))
                <input type="hidden" name="tri" value="{{ request('tri') }}">
            @endif
            <div class="flex flex-col gap-1">
                <label class="text-xs text-gray-500 font-medium">Du</label>
                <input type="date" name="du" value="{{ request('du') }}" class="form-input">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs text-gray-500 font-medium">Au</label>
                <input type="date" name="au" value="{{ request('au') }}" class="form-input">
            </div>
            <button type="submit" class="btn-primary py-2">Appliquer</button>
        </form>
    </div>

    {{-- ═══ Cartes de synthèse ═══ --}}
    @php
        $caPrecedentTotal = array_sum(array_column($stats, 'ca_precedent'));
        $evolutionTotale  = $caPrecedentTotal > 0
            ? round(($totalCa - $caPrecedentTotal) * 100 / $caPrecedentTotal, 1)
            : null;
    @endphp
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="card p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400">CA encaissé</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($totalCa, 0, ',', ' ') }} F</p>
            @if($evolutionTotale !== null)
                <p class="text-[11px] mt-0.5 font-semibold {{ $evolutionTotale >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500' }}">
                    {{ $evolutionTotale >= 0 ? '▲' : '▼' }} {{ abs($evolutionTotale) }} % vs période précédente
                </p>
            @else
                <p class="text-[11px] text-gray-400 mt-0.5">pas de référence précédente</p>
            @endif
        </div>
        <div class="card p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400">Ventes</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($totalVentes, 0, ',', ' ') }}</p>
            <p class="text-[11px] text-gray-400 mt-0.5">
                panier moyen {{ number_format($totalVentes > 0 ? round($totalCa / $totalVentes) : 0, 0, ',', ' ') }} F
            </p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400">Clients actifs</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($totalClients, 0, ',', ' ') }}</p>
            <p class="text-[11px] text-gray-400 mt-0.5">
                +{{ number_format(array_sum(array_column($stats, 'nb_nouveaux')), 0, ',', ' ') }} sur la période
            </p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400">Meilleur établissement</p>
            <p class="text-lg font-bold text-gray-900 dark:text-white mt-1 truncate">
                {{ $stats[0]['institut']->nom ?? '—' }}
            </p>
            <p class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-0.5 font-semibold">
                {{ number_format($stats[0]['ca'] ?? 0, 0, ',', ' ') }} F
            </p>
        </div>
    </div>

    @if(count($stats) <= 1)
        <div class="card p-6 text-center">
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Un seul établissement actif</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-md mx-auto">
                Le comparatif devient vraiment utile à partir de deux établissements.
                Créez-en un second depuis « Mes établissements » pour suivre vos boutiques côte à côte.
            </p>
            <a href="{{ route('dashboard.mes-instituts.index') }}" class="btn-primary text-sm mt-3 inline-flex">Gérer mes établissements</a>
        </div>
    @endif

    {{-- ═══ Graphique en barres ═══ --}}
    @if($totalCa > 0)
    <div class="card p-5">
        <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Répartition du chiffre d'affaires</h2>
        <div class="flex items-end gap-3 h-40">
            @foreach($stats as $ligne)
                @php
                    $hauteur = $totalCa > 0 ? max(4, round($ligne['ca'] * 100 / $totalCa)) : 4;
                @endphp
                <div class="flex-1 flex flex-col items-center justify-end h-full gap-1.5 min-w-0">
                    <span class="text-[10px] font-bold text-gray-700 dark:text-gray-300 whitespace-nowrap">
                        {{ number_format($ligne['ca'], 0, ',', ' ') }}
                    </span>
                    <div class="w-full rounded-t-lg transition-all"
                         style="height: {{ $hauteur }}%; {{ $loop->first ? 'background: linear-gradient(180deg, #9333ea, #ec4899);' : 'background: #c7d2fe;' }}"></div>
                    <span class="text-[10px] text-gray-500 dark:text-gray-400 truncate w-full text-center" title="{{ $ligne['institut']->nom }}">
                        {{ \Illuminate\Support\Str::limit($ligne['institut']->nom, 12) }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ═══ Tableau comparatif ═══ --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-100 dark:border-gray-700">
                    <tr>
                        @php
                            $entetes = [
                                'nom'       => 'Établissement',
                                'ca'        => 'CA encaissé',
                                'evolution' => 'Évolution',
                                'ventes'    => 'Ventes',
                                'panier'    => 'Panier moyen',
                                'clients'   => 'Clients',
                            ];
                            $maxCa = max(array_column($stats, 'ca') ?: [0]);
                        @endphp
                        @foreach($entetes as $code => $libelle)
                            <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                <a href="{{ route('dashboard.comparatif.index', array_merge(request()->all(), ['tri' => $code])) }}"
                                   class="hover:text-primary-600 {{ $tri === $code ? 'text-primary-600' : '' }}">
                                    {{ $libelle }}
                                    @if($tri === $code)
                                        <span class="text-[9px]">▼</span>
                                    @endif
                                </a>
                            </th>
                        @endforeach
                        <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-300 whitespace-nowrap">Qualité</th>
                        <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-300 whitespace-nowrap">Top prestations</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($stats as $ligne)
                        @php
                            $pct        = $totalCa > 0 ? round($ligne['ca'] * 100 / $totalCa, 1) : 0;
                            $meilleurCa = $ligne['ca'] > 0 && $ligne['ca'] === $maxCa;
                        @endphp
                        <tr class="{{ $meilleurCa ? 'bg-emerald-50/60 dark:bg-emerald-900/10' : '' }} hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors">
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $ligne['institut']->nom }}</span>
                                    @if($meilleurCa)
                                        <span class="badge-success text-[10px] flex-shrink-0">★ #1</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <div class="w-20 h-1 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full" style="width: {{ $pct }}%; background: linear-gradient(90deg, #9333ea, #ec4899);"></div>
                                    </div>
                                    <span class="text-[10px] text-gray-400">{{ $pct }} %</span>
                                </div>
                            </td>

                            <td class="px-3 py-3 text-right font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                {{ number_format($ligne['ca'], 0, ',', ' ') }} F
                            </td>

                            <td class="px-3 py-3 whitespace-nowrap">
                                @if($ligne['evolution'] === null)
                                    <span class="text-xs text-gray-400">—</span>
                                @else
                                    <span class="text-xs font-semibold {{ $ligne['evolution'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500' }}">
                                        {{ $ligne['evolution'] >= 0 ? '▲' : '▼' }} {{ abs($ligne['evolution']) }} %
                                    </span>
                                @endif
                            </td>

                            <td class="px-3 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ number_format($ligne['nb_ventes'], 0, ',', ' ') }}
                                @if($ligne['nb_annulees'] > 0)
                                    <span class="text-[10px] text-red-500 ml-1" title="Ventes annulées">−{{ $ligne['nb_annulees'] }}</span>
                                @endif
                            </td>

                            <td class="px-3 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ number_format($ligne['panier_moyen'], 0, ',', ' ') }} F
                            </td>

                            <td class="px-3 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ number_format($ligne['nb_clients'], 0, ',', ' ') }}
                                @if($ligne['nb_nouveaux'] > 0)
                                    <span class="text-[10px] text-emerald-600 dark:text-emerald-400 ml-1">+{{ $ligne['nb_nouveaux'] }}</span>
                                @endif
                            </td>

                            <td class="px-3 py-3">
                                <div class="space-y-0.5 text-[11px]">
                                    <div class="flex items-center gap-1">
                                        <span class="text-gray-400 w-16">Annulation</span>
                                        <span class="font-semibold {{ $ligne['taux_annulation'] > 5 ? 'text-red-500' : 'text-gray-700 dark:text-gray-300' }}">
                                            {{ $ligne['taux_annulation'] }} %
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <span class="text-gray-400 w-16">Remises</span>
                                        <span class="font-semibold text-gray-700 dark:text-gray-300">
                                            {{ number_format($ligne['remises'], 0, ',', ' ') }} F
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <span class="text-gray-400 w-16">Crédit</span>
                                        <span class="font-semibold {{ $ligne['part_credit'] > 30 ? 'text-amber-600' : 'text-gray-700 dark:text-gray-300' }}">
                                            {{ $ligne['part_credit'] }} %
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <span class="text-gray-400 w-16">Produits</span>
                                        <span class="font-semibold text-gray-700 dark:text-gray-300">
                                            {{ $ligne['produits_qte'] }} vendu{{ $ligne['produits_qte'] > 1 ? 's' : '' }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <td class="px-3 py-3">
                                @if($ligne['top_prestations']->isEmpty())
                                    <span class="text-xs text-gray-400">—</span>
                                @else
                                    <ul class="space-y-0.5 text-[11px]">
                                        @foreach($ligne['top_prestations'] as $top)
                                            <li class="flex items-center gap-1.5">
                                                <span class="text-gray-700 dark:text-gray-300 truncate max-w-[140px]" title="{{ $top->nom }}">
                                                    {{ $top->nom }}
                                                </span>
                                                <span class="text-gray-400 whitespace-nowrap">×{{ $top->qte }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Répartition des modes de paiement --}}
        <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/30">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 mb-2">Modes de paiement</p>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @php
                    $modesLibelles = ['cash' => 'Espèces', 'carte' => 'Carte', 'mobile_money' => 'Mobile', 'mixte' => 'Mixte', 'credit' => 'Crédit'];
                    $couleurs = ['cash' => '#10b981', 'carte' => '#3b82f6', 'mobile_money' => '#f59e0b', 'mixte' => '#8b5cf6', 'credit' => '#ec4899'];
                @endphp
                @foreach($stats as $ligne)
                    <div>
                        <p class="text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1 truncate">{{ $ligne['institut']->nom }}</p>
                        @if($ligne['modes']->isEmpty())
                            <p class="text-[10px] text-gray-400">Aucune vente</p>
                        @else
                            @php $totalModesLigne = $ligne['modes']->sum('nb'); @endphp
                            <div class="flex h-2 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-700">
                                @foreach($ligne['modes'] as $mode)
                                    <div style="width: {{ $totalModesLigne > 0 ? round($mode->nb * 100 / $totalModesLigne, 2) : 0 }}%; background: {{ $couleurs[$mode->mode_paiement] ?? '#9ca3af' }};"
                                         title="{{ $modesLibelles[$mode->mode_paiement] ?? $mode->mode_paiement }} : {{ $mode->nb }}"></div>
                                @endforeach
                            </div>
                            <div class="flex flex-wrap gap-x-2 gap-y-0.5 mt-1">
                                @foreach($ligne['modes'] as $mode)
                                    <span class="text-[9px] text-gray-500 dark:text-gray-400">
                                        <span class="inline-block w-1.5 h-1.5 rounded-full align-middle" style="background: {{ $couleurs[$mode->mode_paiement] ?? '#9ca3af' }}"></span>
                                        {{ $modesLibelles[$mode->mode_paiement] ?? $mode->mode_paiement }} {{ $mode->nb }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ═══ Note méthodologique ═══ --}}
    <p class="text-[11px] text-gray-400 dark:text-gray-500">
        Le CA correspond à l'argent réellement encaissé : pour une vente à crédit, seul le montant déjà versé est compté.
        Les ventes annulées sont exclues du chiffre d'affaires mais comptées dans le taux d'annulation.
    </p>
</div>
</x-dashboard-layout>
