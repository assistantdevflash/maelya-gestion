<x-dashboard-layout>
    <x-slot name="title">Crédits clients</x-slot>

    <div class="space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-display font-bold text-gray-900 tracking-tight">Crédits clients</h1>
                <p class="text-sm text-gray-500 mt-1">{{ \App\Helpers\TexteHelper::pluriel($credits->total(), 'crédit', 'crédits') }}</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('dashboard.credits.export-encours') }}" class="btn-outline text-sm" title="Exporter les encours (CSV)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span class="hidden sm:inline">Export CSV</span>
                </a>
                <a href="{{ route('dashboard.credits.parametres') }}" class="btn-outline text-sm" title="Paramètres du module">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="hidden sm:inline">Paramètres</span>
                </a>
                <a href="{{ route('dashboard.credits.create') }}" class="btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Nouveau crédit
                </a>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="card p-4">
                <p class="text-xs text-gray-500">Total crédits</p>
                <p class="text-2xl font-bold text-gray-900">{{ $totaux->total ?? 0 }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs text-gray-500">En cours</p>
                <p class="text-2xl font-bold text-blue-600">{{ $totaux->en_cours ?? 0 }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs text-gray-500">En retard</p>
                <p class="text-2xl font-bold text-red-600">{{ $totaux->en_retard ?? 0 }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs text-gray-500">Total dû</p>
                <p class="text-2xl font-bold text-amber-600">{{ number_format($totaux->total_du ?? 0, 0, ',', ' ') }} F</p>
            </div>
        </div>

        {{-- Balance âgée + indicateurs de recouvrement --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="card p-4 lg:col-span-2">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Balance âgée des créances</p>
                    <span class="text-[11px] text-gray-400">{{ $balance['nb'] }} échéance(s) non réglée(s)</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                    @php
                        $tranches = [
                            ['label' => 'À venir',  'valeur' => $balance['a_venir'],  'classe' => 'text-blue-600 border-blue-200 bg-blue-50 dark:bg-blue-950/30 dark:border-blue-800/40'],
                            ['label' => '0-30 j',   'valeur' => $balance['t0_30'],    'classe' => 'text-amber-600 border-amber-200 bg-amber-50 dark:bg-amber-950/30 dark:border-amber-800/40'],
                            ['label' => '31-60 j',  'valeur' => $balance['t31_60'],   'classe' => 'text-orange-600 border-orange-200 bg-orange-50 dark:bg-orange-950/30 dark:border-orange-800/40'],
                            ['label' => '61-90 j',  'valeur' => $balance['t61_90'],   'classe' => 'text-red-500 border-red-200 bg-red-50 dark:bg-red-950/30 dark:border-red-800/40'],
                            ['label' => '+90 j',    'valeur' => $balance['t90_plus'], 'classe' => 'text-red-700 border-red-300 bg-red-100 dark:bg-red-950/50 dark:border-red-800/60'],
                        ];
                    @endphp
                    @foreach($tranches as $t)
                        <div class="rounded-xl border {{ $t['classe'] }} px-3 py-2">
                            <p class="text-[10px] font-bold uppercase tracking-wide opacity-80">{{ $t['label'] }}</p>
                            <p class="text-sm font-bold mt-0.5 whitespace-nowrap">{{ number_format($t['valeur'], 0, ',', ' ') }} F</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card p-4 grid grid-cols-2 lg:grid-cols-1 gap-3">
                <div>
                    <p class="text-xs text-gray-500 dark:text-slate-400">Taux de recouvrement</p>
                    <p class="text-2xl font-bold text-emerald-600">{{ $taux !== null ? $taux . ' %' : '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-slate-400">Délai moyen d'encaissement</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $delaiMoyen !== null ? $delaiMoyen . ' j' : '—' }}
                    </p>
                    <p class="text-[10px] text-gray-400 mt-0.5">négatif = payé en avance</p>
                </div>
            </div>
        </div>

        {{-- Top débiteurs --}}
        @if($topDebiteurs->isNotEmpty())
        <div class="card p-4">
            <div class="flex items-center justify-between gap-3 mb-2.5">
                <div class="flex items-baseline gap-2 min-w-0">
                    <p class="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider truncate">Clients les plus endettés</p>
                    <span class="text-[10px] font-bold text-gray-400 dark:text-slate-500 whitespace-nowrap">Top {{ $topDebiteurs->count() }}</span>
                </div>
                <a href="#liste-credits" class="text-[11px] font-semibold text-primary-600 hover:text-primary-700 whitespace-nowrap flex-shrink-0">Voir la liste</a>
            </div>

            {{-- Mobile : une seule ligne défilable horizontalement (hauteur constante).
                 Desktop : retour à la ligne classique. --}}
            <div class="-mx-4 px-4 sm:mx-0 sm:px-0 overflow-x-auto sm:overflow-visible -webkit-overflow-scrolling:touch">
                <div class="flex gap-2 sm:flex-wrap min-w-max sm:min-w-0 snap-x snap-mandatory">
                    @foreach($topDebiteurs as $i => $d)
                        @php
                            $part = $balance['total'] > 0 ? round($d['du'] * 100 / $balance['total']) : 0;
                        @endphp
                        <a href="{{ route('dashboard.credits.index', ['q' => $d['client']?->nom]) }}"
                           class="snap-start flex-shrink-0 sm:flex-shrink w-[210px] sm:w-auto group
                                  rounded-xl border border-gray-100 dark:border-slate-700 bg-gray-50 dark:bg-slate-800
                                  px-3 py-2 hover:border-primary-200 dark:hover:border-primary-800 hover:bg-white dark:hover:bg-slate-700 transition-colors">
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 flex-shrink-0 rounded-md bg-gray-200 dark:bg-slate-700 text-gray-600 dark:text-slate-300
                                             text-[10px] font-bold flex items-center justify-center">{{ $i + 1 }}</span>
                                <span class="text-xs font-semibold text-gray-900 dark:text-white truncate">{{ $d['client']?->nom_complet }}</span>
                                @if($d['retards'] > 0)
                                    <span class="ml-auto flex-shrink-0 w-1.5 h-1.5 rounded-full bg-red-500" title="{{ $d['retards'] }} crédit(s) en retard"></span>
                                @endif
                            </div>
                            <div class="flex items-center justify-between mt-1 pl-7 gap-2">
                                <span class="text-xs font-bold text-red-600 whitespace-nowrap">{{ number_format($d['du'], 0, ',', ' ') }} F</span>
                                <span class="text-[10px] font-semibold text-gray-400 dark:text-slate-500 whitespace-nowrap">{{ $part }} %</span>
                            </div>
                            @if($d['retards'] > 0)
                                <p class="text-[10px] text-red-600 dark:text-red-400 font-semibold mt-0.5 pl-7">
                                    {{ \App\Helpers\TexteHelper::pluriel($d['retards'], 'crédit en retard', 'crédits en retard') }}
                                </p>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
            <p class="text-[10px] text-gray-400 dark:text-slate-500 mt-2 sm:hidden">Glissez vers la gauche pour voir les autres.</p>
        </div>
        @endif

        {{-- Filtres --}}
        <div class="card p-4" id="liste-credits">
            <div class="flex flex-wrap items-end gap-3">
                {{-- Boutons de filtre statut --}}
                <div class="flex gap-1">
                    @foreach(['tous' => 'Tous', 'en_cours' => 'En cours', 'retard' => 'En retard', 'solde' => 'Soldés'] as $val => $label)
                    <a href="{{ route('dashboard.credits.index', ['statut' => $val, 'q' => request('q')]) }}"
                       class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $filtre === $val ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        {{ $label }}
                    </a>
                    @endforeach
                </div>
                {{-- Recherche --}}
                <form method="GET" action="{{ route('dashboard.credits.index') }}" class="flex flex-1 gap-2 min-w-[200px]">
                    @if($filtre !== 'tous')
                        <input type="hidden" name="statut" value="{{ $filtre }}">
                    @endif
                    <div class="relative flex-1">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="search" name="q" value="{{ request('q') }}"
                               placeholder="Nom, prénom ou téléphone..."
                               class="form-input pl-9">
                    </div>
                    <button type="submit" class="btn-outline">Rechercher</button>
                    @if(request()->hasAny(['q','statut']))
                        <a href="{{ route('dashboard.credits.index') }}" class="btn btn-ghost">Effacer</a>
                    @endif
                </form>
            </div>
        </div>

        {{-- Liste --}}
        @if($credits->count() > 0)
        <div class="card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Client</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 hidden sm:table-cell">Articles</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Total</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600 hidden md:table-cell">Payé</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Reste</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($credits as $credit)
                    <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('dashboard.credits.show', $credit) }}'">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-900">{{ $credit->client?->nom_complet ?? '—' }}</p>
                            <p class="text-xs text-gray-400">{{ $credit->client?->telephone ?? '' }}</p>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-600 hidden sm:table-cell">
                            {{ $credit->vente?->items->pluck('nom_snapshot')->implode(', ') ?: '—' }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-xs">{{ number_format($credit->montant_total, 0, ',', ' ') }} F</td>
                        <td class="px-4 py-3 text-right font-mono text-xs text-emerald-600 hidden md:table-cell">{{ number_format($credit->montant_total - $credit->reste_a_payer, 0, ',', ' ') }} F</td>
                        <td class="px-4 py-3 text-right font-bold {{ $credit->reste_a_payer > 0 ? 'text-red-600' : 'text-gray-400' }}">
                            {{ number_format($credit->reste_a_payer, 0, ',', ' ') }} F
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($credit->statut === 'solde')
                                <span class="badge badge-success text-xs">Soldé</span>
                            @elseif($credit->statut === 'retard')
                                <span class="badge badge-danger text-xs">🔴 Retard</span>
                            @else
                                <span class="badge badge-info text-xs">En cours</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $credits->links() }}
            </div>
        </div>
        @else
        <div class="card p-12 text-center">
            <div class="text-4xl mb-3">📋</div>
            <p class="font-semibold text-gray-900 mb-1">Aucun crédit</p>
            <p class="text-sm text-gray-500">Les ventes à crédit apparaîtront ici.</p>
        </div>
        @endif
    </div>
</x-dashboard-layout>
