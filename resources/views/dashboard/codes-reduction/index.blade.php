<x-dashboard-layout>
    @php
        // Liens de tri et de filtre : on conserve les paramètres courants.
        $parametres = request()->except(['tri', 'dir', 'page']);

        $lienTri = function (string $colonne) use ($tri, $sens, $parametres) {
            $sensSuivant = ($tri === $colonne && $sens === 'asc') ? 'desc' : 'asc';

            return route('dashboard.codes-reduction.index', array_merge($parametres, [
                'tri' => $colonne,
                'dir' => $sensSuivant,
            ]));
        };

        $fleche = fn (string $colonne) => $tri === $colonne ? ($sens === 'asc' ? '▲' : '▼') : '';

        $lienFiltre = function (array $surcharges) use ($parametres) {
            $fusion = array_merge($parametres, $surcharges);
            $fusion = array_filter($fusion, fn ($valeur) => $valeur !== null && $valeur !== '');

            return route('dashboard.codes-reduction.index', $fusion);
        };

        // Données des codes de la page, lues par Alpine via un bloc JSON.
        // Évite d'injecter du JSON dans un attribut HTML.
        $js = fn ($valeur) => json_encode($valeur, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
        $codesDonnees = $codes->mapWithKeys(fn ($code) => [$code->id => [
            'id' => $code->id,
            'code' => $code->code,
            'description' => $code->description ?? '',
            'type' => $code->type,
            'valeur' => $code->valeur,
            'plafond_remise' => $code->plafond_remise ?? '',
            'montant_minimum' => $code->montant_minimum ?? '',
            'date_debut' => $code->date_debut?->toDateString() ?? '',
            'date_fin' => $code->date_fin?->toDateString() ?? '',
            'limite_utilisation' => $code->limite_utilisation ?? '',
            'client_id' => $code->client_id ?? '',
        ]])->all();
    @endphp

    <script type="application/json" id="codes-donnees">{!! json_encode($codesDonnees, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>

    <div class="space-y-5"
         x-data="remisesAvoirs()"
         @keydown.escape.window="fermerTout()">

        {{-- ═══ En-tête ═══ --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-display font-bold text-gray-900 tracking-tight dark:text-slate-100">Remises & Avoirs</h1>
                <p class="text-sm text-gray-500 mt-1">Codes promo, avoirs clients et historique d'utilisation.</p>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('dashboard.codes-reduction.export') }}"
                   class="btn btn-outline text-xs py-2 px-3" title="Exporter les codes au format CSV">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span class="hidden sm:inline">Exporter</span>
                </a>
                <button type="button" @click="ouvrirLot()" x-show="onglet === 'codes'"
                        class="btn btn-outline text-xs py-2 px-3" title="Générer plusieurs codes d'un coup">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span class="hidden sm:inline">Générer en lot</span>
                </button>
                <button type="button" @click="ouvrirCreation()" x-show="onglet === 'codes'" class="btn-primary text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Créer un code
                </button>
            </div>
        </div>

        {{-- ═══ Statistiques ═══ --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <a href="{{ $lienFiltre(['statut' => null, 'page' => null]) }}"
               class="card p-4 sm:p-5 flex items-center gap-3 hover:border-primary-300 hover:shadow-md transition-all {{ $filtreStatut === '' ? 'ring-1 ring-primary-300' : '' }}">
                <div class="w-10 h-10 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-gray-500 dark:text-slate-400">Total codes</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white leading-tight">{{ $stats['total'] }}</p>
                </div>
            </a>

            <a href="{{ $lienFiltre(['statut' => $filtreStatut === 'actif' ? null : 'actif', 'page' => null]) }}"
               class="card p-4 sm:p-5 flex items-center gap-3 hover:border-emerald-300 hover:shadow-md transition-all {{ $filtreStatut === 'actif' ? 'ring-1 ring-emerald-300' : '' }}">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-gray-500 dark:text-slate-400">Codes actifs</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white leading-tight">{{ $stats['actifs'] }}</p>
                </div>
            </a>

            <div class="card p-4 sm:p-5 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-gray-500 dark:text-slate-400">Utilisations</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white leading-tight">{{ $stats['utilisations'] }}</p>
                </div>
            </div>

            <div class="card p-4 sm:p-5">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-xs text-gray-500 dark:text-slate-400">Remise accordée</p>
                        <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white leading-tight">
                            {{ number_format($stats['remise_totale'], 0, ',', ' ') }} <span class="text-sm font-medium text-gray-400">F</span>
                        </p>
                        <p class="text-[11px] text-gray-400 mt-0.5">
                            {{ number_format($stats['remise_30_jours'], 0, ',', ' ') }} F sur 30 jours
                        </p>
                    </div>
                    {{-- Courbe des utilisations sur 30 jours --}}
                    <svg viewBox="0 0 120 32" class="hidden xl:block w-[110px] h-8 flex-shrink-0" preserveAspectRatio="none" aria-hidden="true">
                        @php $pic = max(1, max($sparkline)); @endphp
                        @foreach($sparkline as $i => $valeur)
                            <rect x="{{ $i * 4 }}" y="{{ 32 - max(1, round($valeur / $pic * 30)) }}"
                                  width="3" height="{{ max(1, round($valeur / $pic * 30)) }}"
                                  rx="1" fill="#9333ea" opacity="{{ $valeur > 0 ? '0.75' : '0.2' }}"></rect>
                        @endforeach
                    </svg>
                </div>
            </div>
        </div>

        {{-- ═══ Onglets + recherche ═══ --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex gap-1 border-b border-gray-200 dark:border-slate-700 flex-1">
                <button @click="onglet = 'codes'"
                        :class="onglet === 'codes' ? 'border-b-2 border-primary-600 text-primary-700 dark:text-primary-400 font-semibold' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700'"
                        class="px-4 py-2 text-sm transition-colors">
                    Codes de réduction
                </button>
                <button @click="onglet = 'avoirs'"
                        :class="onglet === 'avoirs' ? 'border-b-2 border-primary-600 text-primary-700 dark:text-primary-400 font-semibold' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700'"
                        class="px-4 py-2 text-sm transition-colors">
                    Avoirs
                    @if($avoirs->total() > 0)
                        <span class="ml-1 inline-flex items-center justify-center min-w-5 h-5 px-1 text-xs font-bold rounded-full bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-300">{{ $avoirs->total() }}</span>
                    @endif
                </button>
            </div>

            {{-- Recherche instantanée --}}
            <form method="GET" action="{{ route('dashboard.codes-reduction.index') }}"
                  x-data x-ref="recherche" class="relative flex-shrink-0">
                @if($onglet !== 'codes')<input type="hidden" name="tab" value="avoirs">@endif
                @if($filtreStatut !== '')<input type="hidden" name="statut" value="{{ $filtreStatut }}">@endif
                @if($filtreType !== '')<input type="hidden" name="type" value="{{ $filtreType }}">@endif
                @if($tri !== 'recent')<input type="hidden" name="tri" value="{{ $tri }}"><input type="hidden" name="dir" value="{{ $sens }}">@endif

                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher code, client, motif…"
                       @input.debounce.400ms="$refs.recherche.submit()"
                       class="w-full sm:w-64 pl-9 pr-8 py-2 text-xs rounded-lg border border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-1 focus:ring-primary-400">
                @if(request('q'))
                <a href="{{ $lienFiltre(['q' => null]) }}"
                   class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center justify-center w-5 h-5 rounded text-gray-400 hover:text-red-500 transition-colors"
                   title="Effacer la recherche">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </a>
                @endif
            </form>
        </div>

        {{-- ═══ Codes de réduction ═══ --}}
        <div x-show="onglet === 'codes'" class="space-y-3">

            {{-- Filtres rapides --}}
            <div class="flex items-center gap-1.5 flex-wrap">
                @foreach([
                    ''          => ['Tous', $stats['total'], ''],
                    'actif'     => ['Actifs', $repartition['actif'], 'emerald'],
                    'programme' => ['Programmés', $repartition['programme'], 'blue'],
                    'epuise'    => ['Épuisés', $repartition['epuise'], 'amber'],
                    'expire'    => ['Expirés', $repartition['expire'], 'red'],
                    'inactif'   => ['Inactifs', $repartition['inactif'], 'gray'],
                ] as $valeur => [$libelle, $nombre, $couleur])
                    <a href="{{ $lienFiltre(['statut' => $valeur === '' ? null : $valeur, 'page' => null]) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border transition-colors
                              {{ $filtreStatut === $valeur
                                    ? 'bg-primary-600 text-white border-primary-600'
                                    : 'bg-white dark:bg-slate-800 text-gray-600 dark:text-slate-300 border-gray-200 dark:border-slate-700 hover:border-primary-300' }}">
                        {{ $libelle }}
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full {{ $filtreStatut === $valeur ? 'bg-white/20' : 'bg-gray-100 dark:bg-slate-700' }}">{{ $nombre }}</span>
                    </a>
                @endforeach

                <div class="ml-auto flex items-center gap-1.5">
                    <a href="{{ $lienFiltre(['type' => $filtreType === 'pourcentage' ? null : 'pourcentage', 'page' => null]) }}"
                       class="px-3 py-1.5 rounded-full text-xs font-semibold border transition-colors {{ $filtreType === 'pourcentage' ? 'bg-primary-600 text-white border-primary-600' : 'bg-white dark:bg-slate-800 text-gray-600 dark:text-slate-300 border-gray-200 dark:border-slate-700 hover:border-primary-300' }}">%</a>
                    <a href="{{ $lienFiltre(['type' => $filtreType === 'montant_fixe' ? null : 'montant_fixe', 'page' => null]) }}"
                       class="px-3 py-1.5 rounded-full text-xs font-semibold border transition-colors {{ $filtreType === 'montant_fixe' ? 'bg-primary-600 text-white border-primary-600' : 'bg-white dark:bg-slate-800 text-gray-600 dark:text-slate-300 border-gray-200 dark:border-slate-700 hover:border-primary-300' }}">Montant</a>
                </div>
            </div>

            @if($codes->count() > 0)
            <div class="card overflow-hidden">
                {{-- Desktop : tableau triable --}}
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-800">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-600 dark:text-slate-300 uppercase tracking-wide">
                                    <a href="{{ $lienTri('code') }}" class="hover:text-primary-600">Code {{ $fleche('code') }}</a>
                                </th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-600 dark:text-slate-300 uppercase tracking-wide">
                                    <a href="{{ $lienTri('valeur') }}" class="hover:text-primary-600">Remise {{ $fleche('valeur') }}</a>
                                </th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-600 dark:text-slate-300 uppercase tracking-wide">Portée</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-600 dark:text-slate-300 uppercase tracking-wide">
                                    <a href="{{ $lienTri('expiration') }}" class="hover:text-primary-600">Validité {{ $fleche('expiration') }}</a>
                                </th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-600 dark:text-slate-300 uppercase tracking-wide">
                                    <a href="{{ $lienTri('utilisation') }}" class="hover:text-primary-600">Utilisation {{ $fleche('utilisation') }}</a>
                                </th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-600 dark:text-slate-300 uppercase tracking-wide">Statut</th>
                                <th class="px-4 py-2.5 text-right text-[11px] font-semibold text-gray-600 dark:text-slate-300 uppercase tracking-wide">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                            @foreach($codes as $code)
                            @php $statut = $code->statut(); @endphp
                            <tr class="hover:bg-gray-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="px-4 py-3">
                                    <p class="font-bold font-mono tracking-wide text-sm text-gray-900 dark:text-slate-100">{{ $code->code }}</p>
                                    @if($code->description)
                                        <p class="text-[11px] text-gray-400 truncate max-w-[16rem]">{{ $code->description }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center text-xs font-bold px-2 py-0.5 rounded-full bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">
                                        {{ $code->libelleRemise() }}
                                    </span>
                                    @if($code->montant_minimum)
                                        <p class="text-[11px] text-gray-400 mt-0.5">dès {{ number_format($code->montant_minimum, 0, ',', ' ') }} F</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($code->client)
                                        <a href="{{ route('dashboard.clients.show', $code->client) }}" class="inline-flex items-center gap-1 text-xs text-primary-600 hover:underline">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            {{ $code->client->prenom }} {{ $code->client->nom }}
                                        </a>
                                    @else
                                        <span class="text-xs text-gray-500 dark:text-slate-400">Tous les clients</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600 dark:text-slate-400">
                                    @if($code->date_fin)
                                        <span class="{{ $code->estExpire() ? 'text-red-500' : '' }}">jusqu'au {{ $code->date_fin->format('d/m/Y') }}</span>
                                    @else
                                        <span class="text-gray-400">sans limite</span>
                                    @endif
                                    @if($code->date_debut && $code->date_debut->isFuture())
                                        <p class="text-[11px] text-blue-500">à partir du {{ $code->date_debut->format('d/m/Y') }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $utilisations = (int) $code->nb_utilisations;
                                        $limite = $code->limite_utilisation;
                                        $pourcentage = $limite ? min(100, round($utilisations / max(1, $limite) * 100)) : ($utilisations > 0 ? 100 : 0);
                                    @endphp
                                    <div class="flex items-center gap-2 min-w-[92px]">
                                        <div class="flex-1 h-1.5 rounded-full bg-gray-100 dark:bg-slate-700 overflow-hidden">
                                            <div class="h-full rounded-full {{ $limite && $utilisations >= $limite ? 'bg-amber-500' : 'bg-primary-500' }}" style="width: {{ $limite ? $pourcentage : min(100, $utilisations * 10) }}%"></div>
                                        </div>
                                        <span class="text-[11px] font-semibold text-gray-600 dark:text-slate-300 whitespace-nowrap">
                                            {{ $utilisations }}@if($limite)/{{ $limite }}@endif
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    @include('dashboard.codes-reduction.partials.badge-statut', ['statut' => $statut])
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('dashboard.codes-reduction.utilisations', $code) }}"
                                           @click.prevent="ouvrirHistorique('{{ $code->code }}', '{{ route('dashboard.codes-reduction.utilisations', $code) }}')"
                                           class="btn-icon text-gray-400 hover:text-blue-600" title="Historique d'utilisation" aria-label="Historique d'utilisation de {{ $code->code }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </a>
                                        @if($code->client && $statut === 'actif')
                                        <a href="{{ route('dashboard.caisse') }}?client={{ $code->client_id }}&code={{ $code->code }}"
                                           class="btn-icon text-gray-400 hover:text-emerald-500" title="Utiliser ce code à la caisse" aria-label="Utiliser ce code à la caisse">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m1.6 8a2 2 0 100 4 2 2 0 000-4zm10 0a2 2 0 100 4 2 2 0 000-4z"/></svg>
                                        </a>
                                        @endif
                                        <button type="button" @click="copier('{{ $code->code }}')"
                                                class="btn-icon text-gray-400 hover:text-primary-600" title="Copier le code" aria-label="Copier le code {{ $code->code }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        </button>
                                        <button type="button" @click="ouvrirEdition('{{ $code->id }}')"
                                                class="btn-icon text-gray-400 hover:text-primary-600" title="Modifier" aria-label="Modifier le code {{ $code->code }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <form method="POST" action="{{ route('dashboard.codes-reduction.dupliquer', $code) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="btn-icon text-gray-400 hover:text-primary-600" title="Dupliquer" aria-label="Dupliquer le code {{ $code->code }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 8h-8a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-8a2 2 0 00-2-2zM4 4h8v2H6v6H4V4z"/></svg>
                                            </button>
                                        </form>
                                        <a href="{{ route('dashboard.codes-reduction.print', $code) }}" target="_blank"
                                           class="btn-icon text-gray-400 hover:text-primary-600" title="Imprimer" aria-label="Imprimer le code {{ $code->code }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        </a>
                                        <form method="POST" action="{{ route('dashboard.codes-reduction.toggle', $code) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn-icon {{ $code->actif ? 'text-emerald-500 hover:text-red-400' : 'text-gray-300 hover:text-emerald-500' }}"
                                                    title="{{ $code->actif ? 'Désactiver' : 'Activer' }}" aria-label="{{ $code->actif ? 'Désactiver' : 'Activer' }} ce code">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 5.636a9 9 0 1012.728 0M12 3v9"/></svg>
                                            </button>
                                        </form>
                                        <form id="del-code-{{ $code->id }}" method="POST" action="{{ route('dashboard.codes-reduction.destroy', $code) }}">
                                            @csrf @method('DELETE')
                                        </form>
                                        <button type="button" @click="$dispatch('confirm-delete', { formId: 'del-code-{{ $code->id }}', title: 'Supprimer ce code ?', message: 'Le code {{ $code->code }} sera définitivement supprimé.' })"
                                                class="btn-icon text-red-400 hover:text-red-600" title="Supprimer" aria-label="Supprimer le code {{ $code->code }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile : cartes --}}
                <div class="md:hidden divide-y divide-gray-100 dark:divide-slate-800">
                    @foreach($codes as $code)
                    @php $statut = $code->statut(); @endphp
                    <div class="p-4 space-y-2">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-bold font-mono text-sm text-gray-900 dark:text-slate-100 truncate">{{ $code->code }}</p>
                                <p class="text-[11px] text-gray-400 truncate">{{ $code->description ?: ($code->client ? $code->client->prenom.' '.$code->client->nom : 'Tous les clients') }}</p>
                            </div>
                            @include('dashboard.codes-reduction.partials.badge-statut', ['statut' => $statut])
                        </div>
                        <div class="flex items-center gap-2 flex-wrap text-[11px] text-gray-500 dark:text-slate-400">
                            <span class="font-bold text-primary-600 dark:text-primary-400">{{ $code->libelleRemise() }}</span>
                            <span>·</span>
                            <span>{{ \App\Helpers\TexteHelper::pluriel((int) $code->nb_utilisations, 'utilisation', 'utilisations') }}@if($code->limite_utilisation)/{{ $code->limite_utilisation }}@endif</span>
                            @if($code->date_fin)<span>·</span><span>{{ $code->date_fin->format('d/m/Y') }}</span>@endif
                        </div>
                        <div class="flex items-center gap-1 pt-1">
                            <button type="button" @click="copier('{{ $code->code }}')" class="btn-icon text-gray-400" aria-label="Copier">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            </button>
                            <a href="{{ route('dashboard.codes-reduction.utilisations', $code) }}" @click.prevent="ouvrirHistorique('{{ $code->code }}', '{{ route('dashboard.codes-reduction.utilisations', $code) }}')" class="btn-icon text-gray-400" aria-label="Historique">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </a>
                            <button type="button" @click="ouvrirEdition('{{ $code->id }}')" class="btn-icon text-gray-400" aria-label="Modifier">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <a href="{{ route('dashboard.codes-reduction.print', $code) }}" target="_blank" class="btn-icon text-gray-400" aria-label="Imprimer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>

                @if($codes->hasPages())
                <div class="px-5 py-3 border-t border-gray-100 dark:border-slate-800">
                    {{ $codes->links() }}
                </div>
                @endif
            </div>
            @else
            <div class="card p-12 text-center">
                <div class="w-14 h-14 rounded-2xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                </div>
                @if(request('q') || $filtreStatut || $filtreType)
                    <p class="font-semibold text-gray-900 dark:text-white mb-1">Aucun code ne correspond</p>
                    <p class="text-sm text-gray-500 mb-4">Modifiez la recherche ou retirez les filtres.</p>
                    <a href="{{ route('dashboard.codes-reduction.index') }}" class="btn-outline">Tout afficher</a>
                @else
                    <p class="font-semibold text-gray-900 dark:text-white mb-1">Aucun code de réduction</p>
                    <p class="text-sm text-gray-500 mb-4">Créez des codes promo pour fidéliser vos clients.</p>
                    <button type="button" @click="ouvrirCreation()" class="btn-primary">Créer un code</button>
                @endif
            </div>
            @endif
        </div>

        {{-- ═══ Avoirs ═══ --}}
        <div x-show="onglet === 'avoirs'" class="card overflow-hidden">
            <div class="flex items-center justify-between gap-2 px-4 py-3 border-b border-gray-100 dark:border-slate-800">
                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ \App\Helpers\TexteHelper::pluriel($avoirs->total(), 'avoir', 'avoirs') }}</p>
                <a href="{{ route('dashboard.avoirs.export') }}" class="btn btn-outline text-xs py-1.5 px-3">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Exporter
                </a>
            </div>

            {{-- Desktop --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-800">
                        <tr>
                            <th class="px-4 py-2 text-left text-[11px] font-semibold text-gray-600 dark:text-slate-300 uppercase tracking-wide">Numéro</th>
                            <th class="px-4 py-2 text-left text-[11px] font-semibold text-gray-600 dark:text-slate-300 uppercase tracking-wide">Date</th>
                            <th class="px-4 py-2 text-left text-[11px] font-semibold text-gray-600 dark:text-slate-300 uppercase tracking-wide">Client</th>
                            <th class="px-4 py-2 text-left text-[11px] font-semibold text-gray-600 dark:text-slate-300 uppercase tracking-wide">Vente</th>
                            <th class="px-4 py-2 text-right text-[11px] font-semibold text-gray-600 dark:text-slate-300 uppercase tracking-wide">Montant</th>
                            <th class="px-4 py-2 text-left text-[11px] font-semibold text-gray-600 dark:text-slate-300 uppercase tracking-wide">Code</th>
                            <th class="px-4 py-2 text-left text-[11px] font-semibold text-gray-600 dark:text-slate-300 uppercase tracking-wide">Statut</th>
                            <th class="px-4 py-2 text-right text-[11px] font-semibold text-gray-600 dark:text-slate-300 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                        @forelse($avoirs as $avoir)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/30">
                            <td class="px-4 py-3 text-sm font-mono text-gray-900 dark:text-slate-100">{{ $avoir->numero }}</td>
                            <td class="px-4 py-3 text-xs text-gray-500 dark:text-slate-400">{{ $avoir->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-slate-300">
                                @if($avoir->client)
                                    <a href="{{ route('dashboard.clients.show', $avoir->client) }}" class="hover:text-primary-600 hover:underline">{{ $avoir->client->prenom }} {{ $avoir->client->nom }}</a>
                                @else
                                    <span class="text-gray-400">Client de passage</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs font-mono text-gray-500 dark:text-slate-400">
                                @if($avoir->vente)
                                    <a href="{{ route('dashboard.ventes.show', $avoir->vente) }}" class="hover:text-primary-600 hover:underline">{{ $avoir->vente->numero ?? '#' . substr($avoir->vente->id, 0, 8) }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-bold text-gray-900 dark:text-slate-100">{{ number_format($avoir->montant, 0, ',', ' ') }} F</td>
                            <td class="px-4 py-3 text-xs font-mono text-purple-700 dark:text-purple-300">{{ $avoir->codeReduction->code ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="badge text-xs {{ $avoir->estUtilise() ? 'badge-warning' : 'badge-success' }}">
                                    {{ $avoir->estUtilise() ? 'Utilisé' : ucfirst($avoir->statutEffectif()) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    @if($avoir->client && $avoir->codeReduction && $avoir->codeReduction->statut() === 'actif')
                                    <a href="{{ route('dashboard.caisse') }}?client={{ $avoir->client_id }}&code={{ $avoir->codeReduction->code }}"
                                       class="btn-icon text-gray-400 hover:text-emerald-500" title="Utiliser à la caisse" aria-label="Utiliser à la caisse">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m1.6 8a2 2 0 100 4 2 2 0 000-4zm10 0a2 2 0 100 4 2 2 0 000-4z"/></svg>
                                    </a>
                                    @endif
                                    @if(! $avoir->estUtilise())
                                    <form method="POST" action="{{ route('dashboard.avoirs.marquer-utilise', $avoir) }}" class="inline">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn-icon text-gray-400 hover:text-amber-500" title="Marquer comme utilisé" aria-label="Marquer comme utilisé">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-sm text-gray-400 dark:text-slate-500">Aucun avoir pour le moment.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile : cartes --}}
            <div class="md:hidden divide-y divide-gray-100 dark:divide-slate-800">
                @forelse($avoirs as $avoir)
                <div class="p-4 space-y-1.5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="font-mono text-sm font-bold text-gray-900 dark:text-slate-100">{{ $avoir->numero }}</p>
                            <p class="text-[11px] text-gray-400">{{ $avoir->created_at?->format('d/m/Y H:i') }}</p>
                        </div>
                        <span class="badge text-xs {{ $avoir->estUtilise() ? 'badge-warning' : 'badge-success' }}">
                            {{ $avoir->estUtilise() ? 'Utilisé' : ucfirst($avoir->statutEffectif()) }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-700 dark:text-slate-300 truncate">
                        {{ $avoir->client ? $avoir->client->prenom . ' ' . $avoir->client->nom : 'Client de passage' }}
                    </p>
                    <p class="text-base font-bold text-gray-900 dark:text-white">{{ number_format($avoir->montant, 0, ',', ' ') }} F</p>
                    @if($avoir->motif)
                        <p class="text-[11px] text-gray-400 truncate">{{ $avoir->motif }}</p>
                    @endif
                </div>
                @empty
                <p class="p-10 text-center text-sm text-gray-400">Aucun avoir pour le moment.</p>
                @endforelse
            </div>

            @if($avoirs->hasPages())
            <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-800">
                {{ $avoirs->links() }}
            </div>
            @endif
        </div>

        {{-- ═══ Modale création / édition ═══ --}}
        <div x-show="modal === 'code'" x-cloak class="modal-backdrop" @click.self="modal = null">
            <div class="modal max-w-xl" x-transition>
                <div class="modal-header">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-primary-50 dark:bg-primary-900/30">
                            <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        </div>
                        <h3 class="modal-title" x-text="form.id ? 'Modifier le code' : 'Nouveau code de réduction'"></h3>
                    </div>
                    <button type="button" @click="modal = null" class="btn-icon" aria-label="Fermer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="modal-body">
                    <form method="POST"
                          action="{{ route('dashboard.codes-reduction.store') }}"
                          :action="form.id ? '{{ url('dashboard/codes-reduction') }}/' + form.id : '{{ route('dashboard.codes-reduction.store') }}'"
                          class="space-y-4">
                        @csrf
                        <input type="hidden" name="_method" :value="form.id ? 'PUT' : 'POST'">
                        <input type="hidden" name="_id" :value="form.id || ''">

                        {{-- Modèles prêts à l'emploi --}}
                        <div class="flex items-center gap-1.5 flex-wrap" x-show="! form.id">
                            <span class="text-[11px] text-gray-400">Modèles :</span>
                            @foreach([
                                ['BIENVENUE10', '10 % de bienvenue', 'pourcentage', 10],
                                ['FIDELITE15', '15 % clients fidèles', 'pourcentage', 15],
                                ['VIP5000', '5 000 F offerts', 'montant_fixe', 5000],
                            ] as [$modeleCode, $modeleDescription, $modeleType, $modeleValeur])
                            <button type="button"
                                    @click="form.code = '{{ $modeleCode }}'; form.description = '{{ $modeleDescription }}'; form.type = '{{ $modeleType }}'; form.valeur = {{ $modeleValeur }};"
                                    class="px-2 py-1 rounded-lg text-[11px] font-semibold bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 hover:bg-primary-50 hover:text-primary-700 transition-colors">
                                {{ $modeleCode }}
                            </button>
                            @endforeach
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="form-group mb-0">
                                <label class="form-label">Code *</label>
                                <div class="flex gap-2">
                                    <input type="text" name="code" x-model="form.code" required maxlength="50"
                                           class="form-input font-mono uppercase tracking-widest flex-1"
                                           placeholder="Ex : BIENVENUE10"
                                           @input="form.code = form.code.toUpperCase()">
                                    <button type="button" @click="form.code = genererCode()"
                                            class="px-3 py-2 rounded-xl border-2 border-gray-200 dark:border-slate-600 hover:border-primary-400 hover:bg-primary-50 text-gray-500 hover:text-primary-600 transition-all flex-shrink-0"
                                            title="Générer un code aléatoire" aria-label="Générer un code aléatoire">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    </button>
                                </div>
                                @error('code')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">Type *</label>
                                <div class="flex gap-2 mt-0.5">
                                    <button type="button" @click="form.type = 'pourcentage'"
                                            :class="form.type === 'pourcentage' ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' : 'border-gray-200 dark:border-slate-600 text-gray-500'"
                                            class="flex-1 py-2 px-2 rounded-xl text-xs font-semibold border-2 transition-all text-center">% Pourcent.</button>
                                    <button type="button" @click="form.type = 'montant_fixe'"
                                            :class="form.type === 'montant_fixe' ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' : 'border-gray-200 dark:border-slate-600 text-gray-500'"
                                            class="flex-1 py-2 px-2 rounded-xl text-xs font-semibold border-2 transition-all text-center">Fixe (F)</button>
                                </div>
                                <input type="hidden" name="type" :value="form.type">
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div class="form-group mb-0">
                                <label class="form-label">Valeur *</label>
                                <input type="number" name="valeur" x-model.number="form.valeur" required min="1"
                                       :max="form.type === 'pourcentage' ? 100 : 9999999" class="form-input" placeholder="Ex : 15">
                                @error('valeur')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div class="form-group mb-0" x-show="form.type === 'pourcentage'">
                                <label class="form-label">Plafond <span class="text-gray-400 font-normal text-xs">(F)</span></label>
                                <input type="number" name="plafond_remise" x-model.number="form.plafond_remise" min="1" class="form-input" placeholder="Ex : 10000">
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">Panier min. <span class="text-gray-400 font-normal text-xs">(F)</span></label>
                                <input type="number" name="montant_minimum" x-model.number="form.montant_minimum" min="0" step="500" class="form-input" placeholder="Ex : 5000">
                            </div>
                        </div>

                        {{-- Aperçu de la remise --}}
                        <div class="rounded-xl bg-gray-50 dark:bg-slate-800/60 border border-gray-100 dark:border-slate-700 p-3">
                            <div class="flex items-center gap-3">
                                <label class="text-xs font-semibold text-gray-600 dark:text-slate-300 whitespace-nowrap">Panier de</label>
                                <input type="number" x-model.number="panier" min="0" step="500"
                                       class="form-input text-sm w-28" aria-label="Montant du panier de démonstration">
                                <span class="text-xs text-gray-500">F</span>
                                <span class="ml-auto text-sm font-bold text-emerald-600">− <span x-text="formatPrix(remiseApercu)"></span> F</span>
                            </div>
                            <p class="text-[11px] text-gray-400 mt-1.5">
                                Le client paierait <span class="font-semibold text-gray-600 dark:text-slate-300" x-text="formatPrix(Math.max(0, panier - remiseApercu))"></span> F.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="form-group mb-0">
                                <label class="form-label">Début <span class="text-gray-400 font-normal text-xs">(optionnel)</span></label>
                                <input type="date" name="date_debut" x-model="form.date_debut" class="form-input">
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">Fin <span class="text-gray-400 font-normal text-xs">(optionnel)</span></label>
                                <input type="date" name="date_fin" x-model="form.date_fin" :min="form.date_debut" class="form-input">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="form-group mb-0">
                                <label class="form-label">Client <span class="text-gray-400 font-normal text-xs">(optionnel)</span></label>
                                <select name="client_id" x-model="form.client_id" class="form-select">
                                    <option value="">Tous les clients</option>
                                    @foreach($clients as $c)
                                        <option value="{{ $c->id }}">{{ $c->prenom }} {{ $c->nom }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">Limite <span class="text-gray-400 font-normal text-xs">(optionnel)</span></label>
                                <input type="number" name="limite_utilisation" x-model.number="form.limite_utilisation" min="1" class="form-input" placeholder="Illimité si vide">
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <label class="form-label">Description <span class="text-gray-400 font-normal text-xs">(optionnel)</span></label>
                            <input type="text" name="description" x-model="form.description" maxlength="255" class="form-input"
                                   placeholder="Ex : Réduction de bienvenue pour nouveaux clients">
                        </div>

                        <div class="flex gap-3 pt-1">
                            <button type="button" @click="modal = null" class="btn btn-outline flex-1 justify-center">Annuler</button>
                            <button type="submit" class="btn-primary flex-1 justify-center" x-text="form.id ? 'Enregistrer' : 'Créer le code'"></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ═══ Modale génération en lot ═══ --}}
        <div x-show="modal === 'lot'" x-cloak class="modal-backdrop" @click.self="modal = null">
            <div class="modal max-w-lg" x-transition>
                <div class="modal-header">
                    <h3 class="modal-title">Générer des codes en lot</h3>
                    <button type="button" @click="modal = null" class="btn-icon" aria-label="Fermer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="{{ route('dashboard.codes-reduction.generer') }}" class="space-y-4">
                        @csrf
                        <p class="text-xs text-gray-500 dark:text-slate-400">
                            Chaque code est unique et utilisable une seule fois par défaut. Idéal pour une opération, un flyer ou des cartes à distribuer.
                        </p>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="form-group mb-0">
                                <label class="form-label">Nombre de codes *</label>
                                <input type="number" name="quantite" value="{{ old('quantite', 10) }}" min="1" max="200" required class="form-input">
                                @error('quantite')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">Préfixe</label>
                                <input type="text" name="prefixe" value="{{ old('prefixe', 'PROMO') }}" maxlength="12"
                                       class="form-input font-mono uppercase" placeholder="PROMO"
                                       oninput="this.value = this.value.toUpperCase()">
                                @error('prefixe')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="form-group mb-0">
                                <label class="form-label">Type *</label>
                                <select name="type" class="form-select">
                                    <option value="pourcentage" @selected(old('type') === 'pourcentage')>Pourcentage</option>
                                    <option value="montant_fixe" @selected(old('type') === 'montant_fixe')>Montant fixe</option>
                                </select>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">Valeur *</label>
                                <input type="number" name="valeur" value="{{ old('valeur', 10) }}" min="1" required class="form-input">
                                @error('valeur')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div class="form-group mb-0">
                                <label class="form-label">Plafond (F)</label>
                                <input type="number" name="plafond_remise" value="{{ old('plafond_remise') }}" min="1" class="form-input" placeholder="—">
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">Panier min. (F)</label>
                                <input type="number" name="montant_minimum" value="{{ old('montant_minimum') }}" min="0" step="500" class="form-input" placeholder="—">
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">Valable jusqu'au</label>
                                <input type="date" name="date_fin" class="form-input" min="{{ now()->toDateString() }}">
                            </div>
                        </div>

                        <input type="hidden" name="limite_utilisation" value="1">

                        <div class="flex gap-3 pt-1">
                            <button type="button" @click="modal = null" class="btn btn-outline flex-1 justify-center">Annuler</button>
                            <button type="submit" class="btn-primary flex-1 justify-center">Générer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ═══ Panneau historique ═══ --}}
        <div x-show="historique.ouvert" x-cloak class="fixed inset-0 z-50 flex justify-end" @click.self="historique.ouvert = false">
            <div class="absolute inset-0 bg-black/40" @click="historique.ouvert = false"></div>
            <aside class="relative w-full sm:w-96 h-full bg-white dark:bg-slate-900 shadow-2xl overflow-y-auto"
                   x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">
                <div class="sticky top-0 bg-white dark:bg-slate-900 border-b border-gray-100 dark:border-slate-800 px-5 py-4 flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide">Historique</p>
                        <p class="font-bold font-mono text-gray-900 dark:text-white" x-text="historique.code"></p>
                    </div>
                    <button type="button" @click="historique.ouvert = false" class="btn-icon" aria-label="Fermer l'historique">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-5 space-y-3">
                    <template x-if="historique.chargement">
                        <p class="text-sm text-gray-400">Chargement…</p>
                    </template>

                    <template x-if="! historique.chargement && historique.lignes && historique.lignes.length === 0">
                        <div class="text-center py-10">
                            <p class="text-sm text-gray-500">Ce code n'a pas encore été utilisé.</p>
                        </div>
                    </template>

                    <template x-for="(ligne, i) in (historique.lignes || [])" :key="i">
                        <div class="flex items-start justify-between gap-3 pb-3 border-b border-gray-100 dark:border-slate-800 last:border-0">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate" x-text="ligne.client"></p>
                                <p class="text-[11px] text-gray-400">
                                    <span x-text="ligne.date"></span> · vente <span x-text="ligne.vente"></span>
                                </p>
                            </div>
                            <span class="text-sm font-bold text-emerald-600 whitespace-nowrap" x-text="'− ' + ligne.remise"></span>
                        </div>
                    </template>
                </div>
            </aside>
        </div>

        {{-- ═══ Toast ═══ --}}
        <div x-show="toast.visible" x-cloak x-transition
             class="fixed bottom-5 left-1/2 -translate-x-1/2 z-[60] px-4 py-2.5 rounded-xl bg-gray-900 text-white text-sm font-medium shadow-2xl">
            <span x-text="toast.message"></span>
        </div>
    </div>

    @push('scripts')
    <script>
        function remisesAvoirs() {
            return {
                onglet: '{{ $onglet ?? 'codes' }}',
                modal: null,
                panier: 10000,
                donnees: {},
                toast: { visible: false, message: '' },
                historique: { ouvert: false, chargement: false, code: '', lignes: [] },
                form: {},

                init() {
                    this.form = this.formVide();

                    const bloc = document.getElementById('codes-donnees');
                    try {
                        this.donnees = bloc ? JSON.parse(bloc.textContent || '{}') : {};
                    } catch (e) {
                        this.donnees = {};
                    }

                    @if($errors->any())
                    // Retour de validation : on rouvre la modale avec les valeurs saisies.
                    this.form = Object.assign(this.formVide(), {
                        id: {{ $js(old('_id')) }},
                        code: {{ $js(old('code', '')) }},
                        description: {{ $js(old('description', '')) }},
                        type: {{ $js(old('type', 'pourcentage')) }},
                        valeur: {{ $js(old('valeur', 10)) }},
                        plafond_remise: {{ $js(old('plafond_remise', '')) }},
                        montant_minimum: {{ $js(old('montant_minimum', '')) }},
                        date_debut: {{ $js(old('date_debut', '')) }},
                        date_fin: {{ $js(old('date_fin', '')) }},
                        limite_utilisation: {{ $js(old('limite_utilisation', '')) }},
                        client_id: {{ $js(old('client_id', '')) }},
                    });
                    this.modal = 'code';
                    @endif
                },

                formVide() {
                    return {
                        id: null, code: '', description: '', type: 'pourcentage',
                        valeur: 10, plafond_remise: '', montant_minimum: '',
                        date_debut: '', date_fin: '', limite_utilisation: '', client_id: '',
                    };
                },

                ouvrirCreation() {
                    this.form = this.formVide();
                    this.modal = 'code';
                },

                ouvrirEdition(id) {
                    this.form = Object.assign(this.formVide(), this.donnees[id] || {});
                    this.modal = 'code';
                },

                ouvrirLot() {
                    this.modal = 'lot';
                },

                fermerTout() {
                    this.modal = null;
                    this.historique.ouvert = false;
                },

                genererCode() {
                    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
                    let resultat = '';
                    for (let i = 0; i < 8; i++) {
                        resultat += chars.charAt(Math.floor(Math.random() * chars.length));
                    }
                    return resultat;
                },

                formatPrix(valeur) {
                    return new Intl.NumberFormat('fr-FR').format(Math.max(0, Math.round(valeur || 0)));
                },

                get remiseApercu() {
                    const panier = Number(this.panier) || 0;
                    const valeur = Number(this.form.valeur) || 0;

                    let remise = this.form.type === 'pourcentage'
                        ? Math.round(panier * valeur / 100)
                        : Math.min(valeur, panier);

                    if (this.form.type === 'pourcentage' && Number(this.form.plafond_remise) > 0) {
                        remise = Math.min(remise, Number(this.form.plafond_remise));
                    }

                    return Math.max(0, Math.min(remise, panier));
                },

                copier(code) {
                    const confirmer = () => this.afficherToast(code + ' copié');

                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(code).then(confirmer).catch(confirmer);
                    } else {
                        const champ = document.createElement('textarea');
                        champ.value = code;
                        document.body.appendChild(champ);
                        champ.select();
                        try { document.execCommand('copy'); } catch (e) {}
                        document.body.removeChild(champ);
                        confirmer();
                    }
                },

                afficherToast(message) {
                    this.toast = { visible: true, message: message };
                    clearTimeout(this._toast);
                    this._toast = setTimeout(() => { this.toast.visible = false; }, 2000);
                },

                ouvrirHistorique(code, url) {
                    this.historique = { ouvert: true, chargement: true, code: code, lignes: [] };

                    fetch(url, { headers: { 'Accept': 'application/json' } })
                        .then(r => r.json())
                        .then(d => {
                            this.historique.chargement = false;
                            this.historique.lignes = d.lignes || [];
                        })
                        .catch(() => {
                            this.historique.chargement = false;
                            this.historique.lignes = [];
                        });
                },
            };
        }
    </script>
    @endpush
</x-dashboard-layout>
