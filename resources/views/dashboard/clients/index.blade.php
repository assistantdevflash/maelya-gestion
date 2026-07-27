<x-dashboard-layout>
@php
    // Repopulation du modal édition après erreur de validation
    $editErrorClientId = ($errors->any() && old('_form') === 'edit') ? old('_client_id') : null;
    $editErrorClient   = $editErrorClientId ? $clients->firstWhere('id', $editErrorClientId) : null;
    if ($editErrorClientId && !$editErrorClient) {
        $editErrorClient = \App\Models\Client::find($editErrorClientId);
    }
@endphp
<div x-data="{
        onglet: '{{ request()->hasAny(['statut_avis']) ? 'avis' : 'clients' }}',
        createOpen: {{ ($errors->any() && old('_form') === 'create') ? 'true' : 'false' }},
        editOpen:   {{ ($editErrorClient ? 'true' : 'false') }},
        viewMode: 'grid',
        edit: {
            id:                       @js(old('_client_id', $editErrorClient?->id ?? '')),
            action:                   @js($editErrorClient ? route('dashboard.clients.update', $editErrorClient) : ''),
            type_client:              @js(old('type_client',              $editErrorClient?->type_client ?? 'personne_physique')),
            est_patient:              @js(old('est_patient',              $editErrorClient?->est_patient ? true : false)),
            prenom:                   @js(old('prenom',                   $editErrorClient?->prenom ?? '')),
            nom:                      @js(old('nom',                      $editErrorClient?->nom ?? '')),
            raison_sociale:           @js(old('raison_sociale',           $editErrorClient?->raison_sociale ?? '')),
            numero_registre_commerce: @js(old('numero_registre_commerce', $editErrorClient?->numero_registre_commerce ?? '')),
            adresse_entreprise:       @js(old('adresse_entreprise',       $editErrorClient?->adresse_entreprise ?? '')),
            telephone:                @js(old('telephone',                $editErrorClient?->telephone ?? '')),
            email:                    @js(old('email',                    $editErrorClient?->email ?? '')),
            date_naissance:           @js(old('date_naissance',           $editErrorClient?->date_naissance ?? '')),
            notes:                    @js(old('notes',                    $editErrorClient?->notes ?? '')),
            adresse:                  @js(old('adresse',                  $editErrorClient?->adresse ?? '')),
            piece_identite:           @js(old('piece_identite',           $editErrorClient?->piece_identite ?? '')),
        },
        openEdit(client) {
            this.edit = client;
            this.editOpen = true;
        }
     }" class="space-y-5">

    {{-- En-tête --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-display font-bold text-gray-900 tracking-tight">Clients</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $clients->total() }} client(s) au total</p>
        </div>
        <button @click="createOpen = true" x-show="onglet === 'clients'" class="btn-primary group">
            <svg class="w-4 h-4 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Nouveau client
        </button>
    </div>

    {{-- Flash --}}
    @if(session('success'))
    <div class="flex items-center gap-3 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-sm text-emerald-700">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Onglets --}}
    <div class="flex gap-1 border-b border-gray-200">
        <button @click="onglet = 'clients'"
                :class="onglet === 'clients' ? 'border-b-2 border-primary-600 text-primary-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-2 text-sm transition-colors">
            Clients
        </button>
        <button @click="onglet = 'avis'"
                :class="onglet === 'avis' ? 'border-b-2 border-primary-600 text-primary-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-2 text-sm transition-colors flex items-center gap-1.5">
            Avis clients
            @php $nbEnAttente = $avis->getCollection()->where('statut','en_attente')->count(); @endphp
            @if($nbEnAttente > 0)
                <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold rounded-full bg-amber-100 text-amber-700">{{ $nbEnAttente }}</span>
            @endif
        </button>
    </div>

    <div x-show="onglet === 'clients'">

    {{-- Bannières anniversaire --}}
    <x-banniere-anniversaire :clients="$anniversairesAujourdhui" />

    {{-- Recherche --}}
    <div class="card p-4">
        <form method="GET" action="{{ route('dashboard.clients.index') }}" class="space-y-3">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="search" name="q" value="{{ request('q') }}"
                           placeholder="Nom, prénom ou téléphone..."
                           class="form-input pl-9">
                </div>
                <button type="submit" class="btn-outline">Rechercher</button>
                @if(request()->hasAny(['q','segment','points_min','mois_anniv','inactif_jours']))
                    <a href="{{ route('dashboard.clients.index') }}" class="btn btn-ghost">Effacer</a>
                @endif
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <select name="segment" class="form-input" onchange="this.form.submit()">
                    <option value="">Tous les segments</option>
                    <option value="nouveau" @selected(request('segment')==='nouveau')>Nouveaux (30j)</option>
                    <option value="fidele" @selected(request('segment')==='fidele')>Fidèles (3-9 visites)</option>
                    <option value="vip" @selected(request('segment')==='vip')>VIP (10+ visites)</option>
                    <option value="inactif" @selected(request('segment')==='inactif')>Inactifs (90j)</option>
                </select>
                <input type="number" name="points_min" min="0" value="{{ request('points_min') }}"
                       placeholder="Points min." class="form-input"
                       onchange="this.form.submit()">
                <select name="mois_anniv" class="form-input" onchange="this.form.submit()">
                    <option value="">Mois d'anniversaire</option>
                    @foreach(['01'=>'Janvier','02'=>'Février','03'=>'Mars','04'=>'Avril','05'=>'Mai','06'=>'Juin','07'=>'Juillet','08'=>'Août','09'=>'Septembre','10'=>'Octobre','11'=>'Novembre','12'=>'Décembre'] as $v=>$l)
                        <option value="{{ $v }}" @selected(request('mois_anniv')===$v)>{{ $l }}</option>
                    @endforeach
                </select>
                <input type="number" name="inactif_jours" min="0" value="{{ request('inactif_jours') }}"
                       placeholder="Inactif depuis (jours)" class="form-input"
                       onchange="this.form.submit()">
            </div>
        </form>
    </div>

    {{-- ═══ KPI rapides ═══ --}}
    @php
        $vipCount = $clients->getCollection()->filter(fn($c) => ($c->ventes_count ?? $c->nombre_visites ?? 0) >= 10)->count();
        $inactifCount = $clients->getCollection()->filter(fn($c) => $c->derniere_visite && $c->derniere_visite->diffInDays(now()) > 90)->count();
        $annivMoisCount = $clients->getCollection()->filter(fn($c) => $c->date_naissance && substr($c->date_naissance,0,2) === now()->format('m'))->count();
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-gray-100 dark:border-slate-700 text-center">
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $clients->total() }}</p>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">Total</p>
        </div>
        <div class="bg-amber-50 dark:bg-amber-500/20 rounded-xl p-3 border border-amber-100 dark:border-amber-500/30 text-center">
            <p class="text-2xl font-bold text-amber-700 dark:text-amber-300">{{ $vipCount }}</p>
            <p class="text-[11px] text-amber-600 dark:text-amber-400">⭐ VIP (10+)</p>
        </div>
        <div class="bg-red-50 dark:bg-red-500/20 rounded-xl p-3 border border-red-100 dark:border-red-500/30 text-center">
            <p class="text-2xl font-bold text-red-600 dark:text-red-300">{{ $inactifCount }}</p>
            <p class="text-[11px] text-red-500 dark:text-red-400">Inactifs 90j+</p>
        </div>
        <div class="bg-pink-50 dark:bg-pink-500/20 rounded-xl p-3 border border-pink-100 dark:border-pink-500/30 text-center">
            <p class="text-2xl font-bold text-pink-600 dark:text-pink-300">{{ $annivMoisCount }}</p>
            <p class="text-[11px] text-pink-500 dark:text-pink-400">🎂 Ce mois</p>
        </div>
    </div>

    {{-- Liste --}}
    @if($clients->count() > 0)
    <div class="flex items-center justify-between mb-3">
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $clients->total() }} client(s)</p>
        <div class="flex rounded-lg bg-gray-100 dark:bg-slate-700 p-0.5">
            <button @click="viewMode='grid'" :class="viewMode==='grid'?'bg-white dark:bg-slate-500 shadow-sm text-gray-900 dark:text-white':'text-gray-500 dark:text-gray-400'" class="px-3 py-1.5 rounded-md text-xs font-semibold transition">
                <svg class="w-3.5 h-3.5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>Grille
            </button>
            <button @click="viewMode='table'" :class="viewMode==='table'?'bg-white dark:bg-slate-500 shadow-sm text-gray-900 dark:text-white':'text-gray-500 dark:text-gray-400'" class="px-3 py-1.5 rounded-md text-xs font-semibold transition">
                <svg class="w-3.5 h-3.5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>Tableau
            </button>
        </div>
    </div>
    {{-- ═══ VUE GRILLE ═══ --}}
    <div x-show="viewMode==='grid'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($clients as $client)
        @php
            $daysSince = $client->derniere_visite ? $client->derniere_visite->diffInDays(now()) : 999;
            $dotColor = $daysSince <= 7 ? 'bg-emerald-400' : ($daysSince <= 30 ? 'bg-amber-400' : 'bg-red-400');
            $visites = $client->ventes_count ?? $client->nombre_visites ?? 0;
            $isVip = $visites >= 10;
            $isBirthday = $client->date_naissance === now()->format('m-d');
        @endphp
        <div class="card p-4 hover:shadow-md transition-shadow group relative">
            <div class="absolute top-3 right-3 w-2.5 h-2.5 rounded-full {{ $dotColor }}" title="{{ $daysSince <= 7 ? 'Actif (< 7j)' : ($daysSince <= 30 ? 'Modéré (7-30j)' : 'Inactif (> 30j)') }}"></div>
            <div class="flex items-center gap-3 mb-3 pr-5">
                @if($client->isEntreprise())
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-400 to-pink-400 rounded-xl flex items-center justify-center text-white text-sm flex-shrink-0 shadow-sm">🏢</div>
                @else
                    <div class="w-10 h-10 bg-gradient-to-br from-primary-400 to-secondary-400 rounded-xl flex items-center justify-center text-white text-sm font-bold flex-shrink-0 shadow-sm">
                        {{ strtoupper(substr($client->prenom ?? '', 0, 1)) }}{{ strtoupper(substr($client->nom ?? '', 0, 1)) }}
                    </div>
                @endif
                <div class="min-w-0 flex-1">
                    <a href="{{ route('dashboard.clients.show', $client) }}" class="font-semibold text-sm text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 transition-colors line-clamp-1">
                        {{ $client->nom_affichage }}@if($isBirthday)<span class="ml-1">🎂</span>@endif
                    </a>
                    <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                        @if($client->est_patient)<span class="px-1 py-0.5 text-[9px] font-bold uppercase bg-purple-100 dark:bg-purple-500/20 text-purple-700 dark:text-purple-300 rounded">Patient</span>@endif
                        @if($isVip)<span class="px-1 py-0.5 text-[9px] font-bold uppercase bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300 rounded">⭐ VIP</span>@endif
                    </div>
                </div>
            </div>
            <div class="space-y-1.5 mb-3">
                @if($client->telephone)<p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1.5"><span class="w-4 h-4 rounded bg-emerald-100 dark:bg-emerald-500/20 flex items-center justify-center text-[10px]">📞</span>{{ $client->telephone }}</p>@endif
                @if($client->email)<p class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1.5 truncate"><span class="w-4 h-4 rounded bg-blue-100 dark:bg-blue-500/20 flex items-center justify-center text-[10px]">✉️</span>{{ $client->email }}</p>@endif
            </div>
            <div class="grid grid-cols-3 gap-2 mb-3">
                <div class="bg-gray-50 dark:bg-slate-700/50 rounded-lg p-2 text-center">
                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $visites }}</p>
                    <p class="text-[9px] text-gray-400 dark:text-gray-500">Visites</p>
                </div>
                <div class="bg-gray-50 dark:bg-slate-700/50 rounded-lg p-2 text-center">
                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($client->total_depense ?? 0, 0, ',', ' ') }}</p>
                    <p class="text-[9px] text-gray-400 dark:text-gray-500">FCFA</p>
                </div>
                <div class="bg-gray-50 dark:bg-slate-700/50 rounded-lg p-2 text-center">
                    <p class="text-xs font-bold {{ ($client->points_fidelite ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400 dark:text-gray-500' }}">{{ number_format($client->points_fidelite ?? 0, 0, ',', ' ') }}</p>
                    <p class="text-[9px] text-gray-400 dark:text-gray-500">Points</p>
                </div>
            </div>
            <p class="text-[11px] text-gray-400 dark:text-gray-500 mb-3">
                @if($client->derniere_visite) Dernière visite {{ $client->derniere_visite->diffForHumans(['parts'=>1]) }} @else Jamais venu @endif
            </p>
            <div class="flex items-center gap-1.5 pt-2 border-t border-gray-100 dark:border-slate-700">
                <a href="{{ route('dashboard.clients.show', $client) }}" class="flex-1 text-center text-xs font-medium py-1.5 rounded-lg bg-primary-50 dark:bg-primary-500/15 text-primary-700 dark:text-primary-300 hover:bg-primary-100 dark:hover:bg-primary-500/25 transition">👁 Voir</a>
                <button @click="openEdit({
                    id: @js($client->id), action: @js(route('dashboard.clients.update', $client)),
                    type_client: @js($client->type_client ?? 'personne_physique'), est_patient: @js($client->est_patient ? true : false),
                    prenom: @js($client->prenom ?? ''), nom: @js($client->nom ?? ''),
                    raison_sociale: @js($client->raison_sociale ?? ''), numero_registre_commerce: @js($client->numero_registre_commerce ?? ''),
                    adresse_entreprise: @js($client->adresse_entreprise ?? ''), telephone: @js($client->telephone ?? ''),
                    email: @js($client->email ?? ''), date_naissance: @js($client->date_naissance ?? ''),
                    notes: @js($client->notes ?? ''), adresse: @js($client->adresse ?? ''), piece_identite: @js($client->piece_identite ?? '')
                })" class="flex-1 text-center text-xs font-medium py-1.5 rounded-lg bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-600 transition">✏️ Modifier</button>
                <div class="flex gap-0.5">
                    <form id="form-archiver-{{ $client->id }}" method="POST" action="{{ route('dashboard.clients.archiver', $client) }}">@csrf
                        <button type="button" class="w-7 h-7 rounded-lg flex items-center justify-center text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-500/20 transition" title="{{ $client->actif ? 'Archiver' : 'Réactiver' }}"
                            onclick="window.dispatchEvent(new CustomEvent('confirm-action',{detail:{formId:'form-archiver-{{ $client->id }}',title:'{{ $client->actif ? 'Archiver' : 'Réactiver' }} ce client',message:'{{ $client->actif ? 'Ce client sera archivé.' : 'Ce client sera réactivé.' }}',confirmLabel:'{{ $client->actif ? 'Archiver' : 'Réactiver' }}',danger:{{ $client->actif ? 'true' : 'false' }}}}))">
                            @if($client->actif)<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8l1 12a2 2 0 002 2h8a2 2 0 002-2L19 8m-9 4v4m4-4v4"/></svg>
                            @else<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>@endif
                        </button>
                    </form>
                    <form id="form-delete-{{ $client->id }}" method="POST" action="{{ route('dashboard.clients.destroy', $client) }}">@csrf @method('DELETE')
                        <button type="button" class="w-7 h-7 rounded-lg flex items-center justify-center text-red-400 hover:bg-red-50 dark:hover:bg-red-500/20 transition" title="Supprimer"
                            onclick="window.dispatchEvent(new CustomEvent('confirm-action',{detail:{formId:'form-delete-{{ $client->id }}',title:'Supprimer ce client',message:'Cette action est irréversible.',confirmLabel:'Supprimer',danger:true}}))"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ═══ VUE TABLEAU ═══ --}}
    <div x-show="viewMode==='table'" class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-slate-800 border-b border-gray-100 dark:border-slate-700">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Client</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300 hidden sm:table-cell">Téléphone</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300 hidden md:table-cell">Visites</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300 hidden lg:table-cell">Total dépensé</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                    @foreach($clients as $client)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors group">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if($client->isEntreprise())<div class="w-8 h-8 bg-gradient-to-br from-purple-400 to-pink-400 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0">🏢</div>
                                @else<div class="w-8 h-8 bg-gradient-to-br from-primary-400 to-secondary-400 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0">{{ strtoupper(substr($client->prenom ?? '', 0, 1)) }}{{ strtoupper(substr($client->nom ?? '', 0, 1)) }}</div>@endif
                                <div>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('dashboard.clients.show', $client) }}" class="font-semibold text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 transition-colors">{{ $client->nom_affichage }}@if($client->date_naissance === now()->format('m-d')) 🎂@endif</a>
                                        @if($client->est_patient)<span class="px-1.5 py-0.5 text-[9px] font-bold uppercase bg-purple-100 dark:bg-purple-500/20 text-purple-700 dark:text-purple-300 rounded">Patient</span>@endif
                                    </div>
                                    @if($client->isEntreprise() && $client->prenom)<p class="text-xs text-gray-500 dark:text-gray-400">Contact : {{ $client->prenom }}</p>@endif
                                    @if($client->email)<p class="text-xs text-gray-400 dark:text-gray-500">{{ $client->email }}</p>@endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400 hidden sm:table-cell">{{ $client->telephone ?? '—' }}</td>
                        <td class="px-4 py-3 text-right hidden md:table-cell"><span class="badge badge-secondary">{{ $client->ventes_count ?? 0 }}</span></td>
                        <td class="px-4 py-3 text-right hidden lg:table-cell font-semibold text-gray-900 dark:text-white">{{ number_format($client->total_depense ?? 0, 0, ',', ' ') }} FCFA</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="{{ route('dashboard.clients.show', $client) }}" class="btn-icon" title="Voir fiche"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a>
                                <button type="button" title="Modifier"
                                    @click="openEdit({id:@js($client->id),action:@js(route('dashboard.clients.update',$client)),type_client:@js($client->type_client??'personne_physique'),est_patient:@js($client->est_patient?true:false),prenom:@js($client->prenom??''),nom:@js($client->nom??''),raison_sociale:@js($client->raison_sociale??''),numero_registre_commerce:@js($client->numero_registre_commerce??''),adresse_entreprise:@js($client->adresse_entreprise??''),telephone:@js($client->telephone??''),email:@js($client->email??''),date_naissance:@js($client->date_naissance??''),notes:@js($client->notes??''),adresse:@js($client->adresse??''),piece_identite:@js($client->piece_identite??'')})" class="btn-icon"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>
                                <form id="form-archiver-t-{{ $client->id }}" method="POST" action="{{ route('dashboard.clients.archiver', $client) }}">@csrf
                                    <button type="button" class="btn-icon text-amber-500 hover:text-amber-700" title="{{ $client->actif ? 'Archiver' : 'Réactiver' }}"
                                        onclick="window.dispatchEvent(new CustomEvent('confirm-action',{detail:{formId:'form-archiver-t-{{ $client->id }}',title:'{{ $client->actif ? 'Archiver' : 'Réactiver' }} ce client',message:'{{ $client->actif ? 'Ce client sera archivé et ne sera plus visible.' : 'Ce client sera réactivé.' }}',confirmLabel:'{{ $client->actif ? 'Archiver' : 'Réactiver' }}',danger:{{ $client->actif ? 'true' : 'false' }}}}))">
                                        @if($client->actif)<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8l1 12a2 2 0 002 2h8a2 2 0 002-2L19 8m-9 4v4m4-4v4"/></svg>
                                        @else<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>@endif
                                    </button>
                                </form>
                                <form id="form-delete-t-{{ $client->id }}" method="POST" action="{{ route('dashboard.clients.destroy', $client) }}">@csrf @method('DELETE')
                                    <button type="button" class="btn-icon text-red-400 hover:text-red-600" title="Supprimer définitivement"
                                        onclick="window.dispatchEvent(new CustomEvent('confirm-action',{detail:{formId:'form-delete-t-{{ $client->id }}',title:'Supprimer ce client',message:'Cette action est irréversible. Le client ne pourra être supprimé que s\'il n\'est lié à aucune vente, RDV, crédit, commande, devis ou facture.',confirmLabel:'Supprimer',danger:true}}))"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($clients->hasPages())
    <div class="mt-4">{{ $clients->withQueryString()->links() }}</div>
    @endif
    @else
    <div class="card p-12 text-center dark:bg-slate-800 dark:border-slate-700">
        <div class="w-16 h-16 bg-gradient-to-br from-primary-50 to-secondary-50 dark:from-primary-500/20 dark:to-secondary-500/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-primary-400 dark:text-primary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <p class="font-semibold text-gray-900 dark:text-white mb-1">Aucun client</p>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            {{ request('q') ? 'Aucun résultat pour "'.request('q').'"' : 'Commencez par ajouter votre premier client.' }}
        </p>
        <button @click="createOpen = true" class="btn-primary">Ajouter un client</button>
    </div>
    @endif

    </div>{{-- end onglet clients --}}

    {{-- ═══ ONGLET AVIS ═══ --}}
    <div x-show="onglet === 'avis'">
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">Approuvez les avis pour les afficher sur votre vitrine publique.</p>
            <form method="GET" action="{{ route('dashboard.clients.index') }}">
                <input type="hidden" name="onglet" value="avis">
                <select name="statut_avis" class="form-input text-sm" onchange="this.form.submit()">
                    <option value="">Tous les statuts</option>
                    <option value="en_attente" @selected($statutAvis==='en_attente')>En attente</option>
                    <option value="approuve" @selected($statutAvis==='approuve')>Approuvés</option>
                    <option value="rejete" @selected($statutAvis==='rejete')>Rejetés</option>
                </select>
            </form>
        </div>

        @if($avis->count())
        <div class="space-y-3">
            @foreach($avis as $a)
            <div class="card p-4">
                <div class="flex justify-between items-start gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-yellow-500 text-lg">{{ str_repeat('★', (int)$a->note) }}{{ str_repeat('☆', 5 - (int)$a->note) }}</span>
                            <span class="font-semibold text-sm text-gray-800">{{ $a->client_nom_snap ?: 'Anonyme' }}</span>
                            <span class="text-xs text-gray-400">· {{ $a->repondu_le?->format('d/m/Y') }}</span>
                        </div>
                        @if($a->commentaire)
                            <p class="text-gray-700 text-sm mb-2">« {{ $a->commentaire }} »</p>
                        @endif
                        @if($a->rdv && $a->rdv->prestations->isNotEmpty())
                            <div class="flex flex-wrap gap-1.5 mb-2">
                                @foreach($a->rdv->prestations as $p)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-purple-50 text-purple-700 border border-purple-200">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                        </svg>
                                        {{ $p->nom }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                        <span class="inline-block px-2 py-0.5 text-xs rounded-full font-medium
                            {{ $a->statut==='approuve' ? 'bg-green-100 text-green-800' :
                               ($a->statut==='rejete' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800') }}">
                            {{ $a->statut === 'en_attente' ? 'En attente' : ($a->statut === 'approuve' ? 'Approuvé' : 'Rejeté') }}
                        </span>
                    </div>
                    <div class="flex gap-2 shrink-0">
                        @if($a->statut !== 'approuve')
                        <form method="POST" action="{{ route('dashboard.avis.approuver', $a) }}">
                            @csrf
                            <button class="btn-primary text-xs px-3 py-1">✓ Approuver</button>
                        </form>
                        @endif
                        @if($a->statut !== 'rejete')
                        <form method="POST" action="{{ route('dashboard.avis.rejeter', $a) }}">
                            @csrf
                            <button class="btn-outline text-xs px-3 py-1">✕ Rejeter</button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @if($avis->hasPages())
        <div class="mt-4">{{ $avis->links() }}</div>
        @endif
        @else
        <div class="card p-12 text-center">
            <p class="text-gray-500">Aucun avis reçu pour le moment.</p>
            <p class="text-sm text-gray-400 mt-1">Les avis apparaissent automatiquement lorsqu'un client répond au sondage post-visite.</p>
        </div>
        @endif
    </div>{{-- end onglet avis --}}

    {{-- ═══ MODAL CRÉATION ═══ --}}
    <div x-show="createOpen" x-cloak class="modal-backdrop" @keydown.escape.window="createOpen = false" @click.self="createOpen = false">
        <div class="modal max-w-lg" x-transition @click.stop>
            <div class="modal-header">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, rgba(147,51,234,0.1), rgba(236,72,153,0.1));">
                        <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                    </div>
                    <h3 class="modal-title">Nouveau client</h3>
                </div>
                <button @click="createOpen = false" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                @if($errors->any() && old('_form') === 'create')
                <div class="mb-4 p-3 bg-red-50 rounded-xl text-sm text-red-600 space-y-0.5">
                    @foreach($errors->all() as $e)<p>• {{ $e }}</p>@endforeach
                </div>
                @endif
                <form method="POST" action="{{ route('dashboard.clients.store') }}" class="space-y-4"
                      x-data="{ typeClient: '{{ old('type_client', 'personne_physique') }}' }">
                    @csrf
                    <input type="hidden" name="_form" value="create">

                    {{-- Type de client --}}
                    <div class="form-group mb-0">
                        <label class="form-label">Type de client *</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center gap-2 px-3 py-2 border rounded-lg cursor-pointer transition"
                                   :class="typeClient === 'personne_physique' ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:border-gray-300'">
                                <input type="radio" name="type_client" value="personne_physique" class="text-primary-600"
                                       x-model="typeClient" checked>
                                <span class="text-sm font-medium">👤 Personne physique</span>
                            </label>
                            <label class="flex items-center gap-2 px-3 py-2 border rounded-lg cursor-pointer transition"
                                   :class="typeClient === 'entreprise' ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:border-gray-300'">
                                <input type="radio" name="type_client" value="entreprise" class="text-primary-600"
                                       x-model="typeClient">
                                <span class="text-sm font-medium">🏢 Entreprise</span>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        {{-- Champs pour personne physique --}}
                        <template x-if="typeClient === 'personne_physique'">
                            <div class="col-span-2 space-y-3">
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="form-group mb-0">
                                        <label class="form-label">Prénom *</label>
                                        <input type="text" name="prenom" maxlength="50" class="form-input"
                                               value="{{ old('_form') === 'create' ? old('prenom') : '' }}" placeholder="Fatou"
                                               :required="typeClient === 'personne_physique'">
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="form-label">Nom *</label>
                                        <input type="text" name="nom" maxlength="50" class="form-input"
                                               value="{{ old('_form') === 'create' ? old('nom') : '' }}" placeholder="Traoré"
                                               :required="typeClient === 'personne_physique'">
                                    </div>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="est_patient" value="1" class="rounded text-primary-600"
                                               {{ old('_form') === 'create' && old('est_patient') ? 'checked' : '' }}>
                                        <span class="text-sm font-medium text-gray-700">Ce client est un patient</span>
                                    </label>
                                    <p class="text-xs text-gray-500 mt-1">Affichera "Patient" sur les factures</p>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="form-label">Anniversaire (jour et mois)</label>
                                    <x-input-naissance :valeur="old('_form') === 'create' ? old('date_naissance') : null" />
                                </div>
                            </div>
                        </template>

                        {{-- Champs pour entreprise --}}
                        <template x-if="typeClient === 'entreprise'">
                            <div class="col-span-2 space-y-3">
                                <div class="form-group mb-0">
                                    <label class="form-label">Raison sociale *</label>
                                    <input type="text" name="raison_sociale" maxlength="255" class="form-input"
                                           value="{{ old('_form') === 'create' ? old('raison_sociale') : '' }}"
                                           placeholder="Entreprise SARL"
                                           :required="typeClient === 'entreprise'">
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="form-group mb-0">
                                        <label class="form-label">N° Registre Commerce</label>
                                        <input type="text" name="numero_registre_commerce" maxlength="100" class="form-input"
                                               value="{{ old('_form') === 'create' ? old('numero_registre_commerce') : '' }}"
                                               placeholder="RC-123456">
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="form-label">Contact (Prénom Nom)</label>
                                        <input type="text" name="prenom" maxlength="100" class="form-input"
                                               value="{{ old('_form') === 'create' ? old('prenom') : '' }}"
                                               placeholder="Jean Dupont">
                                    </div>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="form-label">Adresse entreprise</label>
                                    <textarea name="adresse_entreprise" rows="2" maxlength="500" class="form-input resize-none"
                                              placeholder="Adresse complète de l'entreprise...">{{ old('_form') === 'create' ? old('adresse_entreprise') : '' }}</textarea>
                                </div>
                            </div>
                        </template>

                        {{-- Champs communs --}}
                        <div class="form-group mb-0">
                            <label class="form-label">Téléphone *</label>
                            <input type="text" name="telephone" required maxlength="30" class="form-input"
                                   value="{{ old('_form') === 'create' ? old('telephone') : '' }}" placeholder="+225 07 00 00 00">
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" maxlength="255" class="form-input"
                                   value="{{ old('_form') === 'create' ? old('email') : '' }}" placeholder="contact@exemple.ci">
                        </div>

                        <div class="col-span-2 form-group mb-0">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" rows="3" class="form-input resize-none"
                                      placeholder="Informations complémentaires, allergies, préférences...">{{ old('_form') === 'create' ? old('notes') : '' }}</textarea>
                            <p class="text-xs text-gray-500 mt-1">Vous pouvez utiliser le HTML basique (gras, italique, listes...)</p>
                        </div>

                        {{-- Informations supplémentaires (collapsible) - uniquement pour personne physique --}}
                        <template x-if="typeClient === 'personne_physique'">
                            <div class="col-span-2" x-data="{ showExtra: false }">
                                <button type="button" @click="showExtra = !showExtra"
                                        class="flex items-center gap-2 text-xs font-medium text-gray-500 hover:text-gray-700 transition-colors">
                                    <svg class="w-3.5 h-3.5 transition-transform" :class="showExtra ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                    Informations supplémentaires
                                </button>
                                <div x-show="showExtra" x-collapse class="mt-3 space-y-3">
                                    <div class="form-group mb-0">
                                        <label class="form-label">Adresse</label>
                                        <input type="text" name="adresse" maxlength="255" class="form-input"
                                               value="{{ old('_form') === 'create' ? old('adresse') : '' }}" placeholder="Abidjan, Cocody...">
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="form-label">Pièce d'identité</label>
                                        <input type="text" name="piece_identite" maxlength="100" class="form-input"
                                               value="{{ old('_form') === 'create' ? old('piece_identite') : '' }}" placeholder="N° CNI, Passeport...">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="flex gap-3 pt-1">
                        <button type="button" @click="createOpen = false" class="btn btn-outline flex-1 justify-center">Annuler</button>
                        <button type="submit" class="btn-primary flex-1 justify-center">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ═══ MODAL ÉDITION ═══ --}}
    <div x-show="editOpen" x-cloak class="modal-backdrop" @keydown.escape.window="editOpen = false" @click.self="editOpen = false">
        <div class="modal max-w-lg" x-transition @click.stop>
            <div class="modal-header">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, rgba(147,51,234,0.1), rgba(236,72,153,0.1));">
                        <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                    <h3 class="modal-title">Modifier le client</h3>
                </div>
                <button @click="editOpen = false" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                @if($errors->any() && old('_form') === 'edit')
                <div class="mb-4 p-3 bg-red-50 rounded-xl text-sm text-red-600 space-y-0.5">
                    @foreach($errors->all() as $e)<p>• {{ $e }}</p>@endforeach
                </div>
                @endif
                <form method="POST" :action="edit.action" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_form" value="edit">
                    <input type="hidden" name="_client_id" :value="edit.id">

                    {{-- Type de client --}}
                    <div class="form-group mb-0">
                        <label class="form-label">Type de client *</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center gap-2 px-3 py-2 border rounded-lg cursor-pointer transition"
                                   :class="edit.type_client === 'personne_physique' ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:border-gray-300'">
                                <input type="radio" name="type_client" value="personne_physique" class="text-primary-600"
                                       x-model="edit.type_client">
                                <span class="text-sm font-medium">👤 Personne physique</span>
                            </label>
                            <label class="flex items-center gap-2 px-3 py-2 border rounded-lg cursor-pointer transition"
                                   :class="edit.type_client === 'entreprise' ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:border-gray-300'">
                                <input type="radio" name="type_client" value="entreprise" class="text-primary-600"
                                       x-model="edit.type_client">
                                <span class="text-sm font-medium">🏢 Entreprise</span>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        {{-- Champs pour personne physique --}}
                        <template x-if="edit.type_client === 'personne_physique'">
                            <div class="col-span-2 space-y-3">
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="form-group mb-0">
                                        <label class="form-label">Prénom *</label>
                                        <input type="text" name="prenom" maxlength="50" class="form-input"
                                               x-model="edit.prenom" placeholder="Fatou"
                                               :required="edit.type_client === 'personne_physique'">
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="form-label">Nom *</label>
                                        <input type="text" name="nom" maxlength="50" class="form-input"
                                               x-model="edit.nom" placeholder="Traoré"
                                               :required="edit.type_client === 'personne_physique'">
                                    </div>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="est_patient" value="1" class="rounded text-primary-600"
                                               :checked="edit.est_patient">
                                        <span class="text-sm font-medium text-gray-700">Ce client est un patient</span>
                                    </label>
                                    <p class="text-xs text-gray-500 mt-1">Affichera "Patient" sur les factures</p>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="form-label">Anniversaire (jour et mois)</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <select name="date_naissance_mois" class="form-input"
                                                @change="edit.date_naissance = $el.value ? $el.value + '-' + ($refs.joureditsel?.value||'01') : ''">
                                            <option value="">Mois</option>
                                            @foreach(['01'=>'Janvier','02'=>'Février','03'=>'Mars','04'=>'Avril','05'=>'Mai','06'=>'Juin','07'=>'Juillet','08'=>'Août','09'=>'Septembre','10'=>'Octobre','11'=>'Novembre','12'=>'Décembre'] as $n=>$m)
                                            <option value="{{ $n }}" :selected="edit.date_naissance && edit.date_naissance.substring(0,2) === '{{ $n }}'">{{ $m }}</option>
                                            @endforeach
                                        </select>
                                        <select name="date_naissance_jour" x-ref="joureditsel" class="form-input"
                                                @change="edit.date_naissance = ($refs.moissel?.value||edit.date_naissance.substring(0,2)||'01') + '-' + $el.value">
                                            <option value="">Jour</option>
                                            @for($d=1;$d<=31;$d++)
                                            @php $ds=str_pad($d,2,'0',STR_PAD_LEFT) @endphp
                                            <option value="{{ $ds }}" :selected="edit.date_naissance && edit.date_naissance.substring(3,5) === '{{ $ds }}'">{{ $d }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                    <input type="hidden" name="date_naissance" :value="edit.date_naissance">
                                </div>
                            </div>
                        </template>

                        {{-- Champs pour entreprise --}}
                        <template x-if="edit.type_client === 'entreprise'">
                            <div class="col-span-2 space-y-3">
                                <div class="form-group mb-0">
                                    <label class="form-label">Raison sociale *</label>
                                    <input type="text" name="raison_sociale" maxlength="255" class="form-input"
                                           x-model="edit.raison_sociale" placeholder="Entreprise SARL"
                                           :required="edit.type_client === 'entreprise'">
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="form-group mb-0">
                                        <label class="form-label">N° Registre Commerce</label>
                                        <input type="text" name="numero_registre_commerce" maxlength="100" class="form-input"
                                               x-model="edit.numero_registre_commerce" placeholder="RC-123456">
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="form-label">Contact (Prénom Nom)</label>
                                        <input type="text" name="prenom" maxlength="100" class="form-input"
                                               x-model="edit.prenom" placeholder="Jean Dupont">
                                    </div>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="form-label">Adresse entreprise</label>
                                    <textarea name="adresse_entreprise" rows="2" maxlength="500" class="form-input resize-none"
                                              x-model="edit.adresse_entreprise"
                                              placeholder="Adresse complète de l'entreprise..."></textarea>
                                </div>
                            </div>
                        </template>

                        {{-- Champs communs --}}
                        <div class="form-group mb-0">
                            <label class="form-label">Téléphone *</label>
                            <input type="text" name="telephone" required maxlength="30" class="form-input"
                                   x-model="edit.telephone" placeholder="+225 07 00 00 00">
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" maxlength="255" class="form-input"
                                   x-model="edit.email" placeholder="contact@exemple.ci">
                        </div>

                        <div class="col-span-2 form-group mb-0">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" rows="3" class="form-input resize-none"
                                      x-model="edit.notes"
                                      placeholder="Informations complémentaires, allergies, préférences..."></textarea>
                            <p class="text-xs text-gray-500 mt-1">Vous pouvez utiliser le HTML basique (gras, italique, listes...)</p>
                        </div>

                        {{-- Informations supplémentaires (collapsible) - uniquement pour personne physique --}}
                        <template x-if="edit.type_client === 'personne_physique'">
                            <div class="col-span-2" x-data="{ showExtraEdit: false }">
                                <button type="button" @click="showExtraEdit = !showExtraEdit"
                                        class="flex items-center gap-2 text-xs font-medium text-gray-500 hover:text-gray-700 transition-colors">
                                    <svg class="w-3.5 h-3.5 transition-transform" :class="showExtraEdit ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                    Informations supplémentaires
                                </button>
                                <div x-show="showExtraEdit" x-collapse class="mt-3 space-y-3">
                                    <div class="form-group mb-0">
                                        <label class="form-label">Adresse</label>
                                        <input type="text" name="adresse" maxlength="255" class="form-input"
                                               x-model="edit.adresse" placeholder="Abidjan, Cocody...">
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="form-label">Pièce d'identité</label>
                                        <input type="text" name="piece_identite" maxlength="100" class="form-input"
                                               x-model="edit.piece_identite" placeholder="N° CNI, Passeport...">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="flex gap-3 pt-1">
                        <button type="button" @click="editOpen = false" class="btn btn-outline flex-1 justify-center">Annuler</button>
                        <button type="submit" class="btn-primary flex-1 justify-center">Enregistrer</button>
                    </div>
                </form>
            </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
</x-dashboard-layout>
