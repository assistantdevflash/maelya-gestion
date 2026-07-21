<x-dashboard-layout>
    <div class="space-y-5">
        {{-- Bannière anniversaire --}}
        @if($client->isAnniversaire())
        <x-banniere-anniversaire :clients="collect([$client])" />
        @endif

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-start sm:items-center gap-3 min-w-0">
                <a href="{{ route('dashboard.clients.index') }}" class="btn-icon text-gray-500 flex-shrink-0 mt-0.5 sm:mt-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                    </svg>
                </a>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-2 min-w-0">
                    <div class="flex items-center gap-3 min-w-0">
                        @if($client->isEntreprise())
                            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-purple-400 to-pink-400 rounded-full flex items-center justify-center text-white text-base sm:text-lg font-bold flex-shrink-0">
                                🏢
                            </div>
                        @else
                            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-primary-400 to-secondary-400 rounded-full flex items-center justify-center text-white text-base sm:text-lg font-bold flex-shrink-0">
                                {{ strtoupper(substr($client->prenom ?? '', 0, 1)) }}{{ strtoupper(substr($client->nom ?? '', 0, 1)) }}
                            </div>
                        @endif
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h1 class="text-lg sm:text-xl font-display font-bold text-gray-900 truncate">{{ $client->nom_affichage }}</h1>
                                @if($client->est_patient)
                                    <span class="px-2 py-0.5 text-[9px] font-bold uppercase bg-purple-100 text-purple-700 rounded">Patient</span>
                                @endif
                            </div>
                            @if($client->isEntreprise())
                                @if($client->numero_registre_commerce)
                                    <p class="text-xs sm:text-sm text-gray-500">RC : {{ $client->numero_registre_commerce }}</p>
                                @endif
                                @if($client->prenom)
                                    <p class="text-xs text-gray-400">Contact : {{ $client->prenom }}</p>
                                @endif
                            @else
                                @if($client->date_naissance)
                                    <p class="text-xs sm:text-sm text-gray-500">{{ $client->naissance_formatee }}</p>
                                @endif
                            @endif
                        </div>
                    </div>
                    {{-- Badge points de fidélité (toujours visible, responsive) --}}
                    <div class="flex items-center gap-1.5 px-3 py-2 rounded-xl flex-shrink-0 ml-1 sm:ml-2
                                {{ $client->points_fidelite > 0
                                    ? 'bg-amber-100 dark:bg-amber-500/20 ring-1 ring-amber-300 dark:ring-amber-400/40'
                                    : 'bg-gray-100 dark:bg-slate-600 ring-1 ring-gray-200 dark:ring-slate-500' }}">
                        <span class="text-base sm:text-lg">{{ $client->points_fidelite > 0 ? '⭐' : '☆' }}</span>
                        <div>
                            <p class="text-base sm:text-lg font-bold {{ $client->points_fidelite > 0 ? 'text-amber-700 dark:text-amber-300' : 'text-gray-500 dark:text-slate-300' }}">
                                {{ number_format($client->points_fidelite, 0, ',', ' ') }}
                            </p>
                            <p class="text-[10px] {{ $client->points_fidelite > 0 ? 'text-amber-500 dark:text-amber-400' : 'text-gray-400 dark:text-slate-400' }} leading-none hidden sm:block">points fidélité</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex gap-2 flex-shrink-0">
                <a href="{{ route('dashboard.caisse') }}?client={{ $client->id }}" class="btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Nouvelle vente
                </a>
                <button type="button" x-data @click="$dispatch('open-edit-show')" class="btn-outline">Modifier</button>
                @if($client->fidelite_token)
                    @php
                        $carteUrl = route('public.carte-fidelite', $client->fidelite_token);
                        $waMsg = "Bonjour {$client->prenom}, voici votre carte de fidélité personnelle : {$carteUrl}";
                        $waPhone = preg_replace('/\D/', '', $client->telephone ?? '');
                    @endphp
                    <div x-data="{ open: false }" class="relative">
                        <button type="button" @click="open = !open" class="btn-outline" title="Carte fidélité">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm14 0h2v2h-2v-2zm-4 0h2v2h-2v-2zm0 4h2v2h-2v-2zm4 0h2v2h-2v-2z"/>
                            </svg>
                            Carte fidélité
                            <svg class="w-3 h-3 ml-1" fill="currentColor" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
                        </button>
                        <div x-show="open" @click.away="open = false" x-cloak
                             class="absolute right-0 mt-2 w-64 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-gray-100 dark:border-slate-700 z-50 overflow-hidden">
                            <a href="{{ $carteUrl }}" target="_blank"
                               class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-gray-50 dark:hover:bg-slate-700">
                                <span>🔗</span> Ouvrir la carte (web)
                            </a>
                            <a href="{{ route('dashboard.clients.fidelite.pdf', $client) }}"
                               class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-gray-50 dark:hover:bg-slate-700">
                                <span>📄</span> Télécharger PDF (format carte)
                            </a>
                            @if($waPhone)
                            <a href="https://wa.me/{{ $waPhone }}?text={{ urlencode($waMsg) }}" target="_blank"
                               class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-gray-50 dark:hover:bg-slate-700 text-green-600">
                                <span>💬</span> Envoyer par WhatsApp
                            </a>
                            @endif
                            <form method="POST" action="{{ route('dashboard.clients.fidelite.regenerer', $client) }}"
                                  onsubmit="return confirm('Régénérer le token ? L\'ancien lien et QR ne fonctionneront plus.');">
                                @csrf
                                <button type="submit"
                                        class="w-full text-left flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-gray-50 dark:hover:bg-slate-700 text-red-600">
                                    <span>🔄</span> Régénérer le token
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- KPI -- }}
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div class="stat-card text-center">
                <p class="text-2xl font-bold text-primary-600">{{ $client->nombre_visites }}</p>
                <p class="text-xs text-gray-500 mt-1">Visites</p>
            </div>
            <div class="stat-card text-center">
                <p class="text-2xl font-bold text-secondary-600">{{ number_format($client->total_depense, 0, ',', ' ') }}</p>
                <p class="text-xs text-gray-500 mt-1">FCFA dépensés</p>
            </div>
            <div class="stat-card text-center relative overflow-hidden">
                <p class="text-2xl font-bold {{ $client->points_fidelite > 0 ? 'text-amber-500' : 'text-gray-400' }}">
                    {{ number_format($client->points_fidelite, 0, ',', ' ') }}
                </p>
                <p class="text-xs text-gray-500 mt-1">Points fidélité</p>
                @if($client->points_fidelite > 0)
                <div class="absolute -bottom-1 left-0 right-0 h-1 bg-amber-200 dark:bg-amber-800/50"></div>
                @endif
            </div>
            <div class="stat-card text-center">
                <p class="text-sm font-semibold text-gray-900">{{ $client->derniere_visite?->diffForHumans() ?? 'Jamais' }}</p>
                <p class="text-xs text-gray-500 mt-1">Dernière visite</p>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-5">
            {{-- Infos client --}}
            <div class="card p-5">
                <h2 class="font-semibold text-gray-900 mb-4 text-sm">📋 Informations</h2>
                <div class="space-y-3 text-sm">
                    @if($client->isEntreprise())
                        @if($client->raison_sociale)
                        <div class="flex items-center gap-2 text-gray-600">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <span class="font-medium">{{ $client->raison_sociale }}</span>
                        </div>
                        @endif
                        @if($client->numero_registre_commerce)
                        <div class="flex items-center gap-2 text-gray-600">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            RC : {{ $client->numero_registre_commerce }}
                        </div>
                        @endif
                        @if($client->adresse_entreprise)
                        <div class="flex items-start gap-2 text-gray-600">
                            <svg class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="flex-1">{{ $client->adresse_entreprise }}</span>
                        </div>
                        @endif
                        @if($client->prenom)
                        <div class="flex items-center gap-2 text-gray-600">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Contact : {{ $client->prenom }}
                        </div>
                        @endif
                    @endif
                    @if($client->telephone)
                    <div class="flex items-center gap-2 text-gray-600">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        {{ $client->telephone }}
                    </div>
                    @endif
                    @if($client->email)
                    <div class="flex items-center gap-2 text-gray-600">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        {{ $client->email }}
                    </div>
                    @endif
                    @if($client->notes)
                    <div class="text-gray-600 bg-gray-50 rounded-lg p-3 text-xs leading-relaxed">
                        {!! nl2br(e($client->notes)) !!}
                    </div>
                    @endif
                    @if($client->isPersonnePhysique() && $client->adresse)
                    <div class="flex items-start gap-2 text-gray-600">
                        <svg class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="text-xs">{{ $client->adresse }}</span>
                    </div>
                    @endif
                    @if($client->piece_identite)
                    <div class="flex items-start gap-2 text-gray-600">
                        <svg class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a1 1 0 011-1h2a1 1 0 011 1v1m-4 0h4M9 14l2 2 4-4"/>
                        </svg>
                        <span class="text-xs">{{ $client->piece_identite }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Onglets Achats / Rendez-vous / Historique / Galerie --}}
            <div class="lg:col-span-2 card overflow-hidden" x-data="{ onglet: new URLSearchParams(window.location.search).get('onglet') || 'achats' }">
                {{-- En-tête onglets --}}
                <div class="p-3 border-b border-gray-100 dark:border-slate-700 flex items-center gap-1 bg-gray-50/60 dark:bg-slate-800/50 overflow-x-auto -webkit-overflow-scrolling:touch">
                    <button type="button" x-on:click="onglet = 'achats'"
                            class="px-3 sm:px-4 py-2 rounded-xl text-xs font-semibold transition-all flex-shrink-0 whitespace-nowrap"
                            :class="onglet === 'achats' ? 'bg-white dark:bg-slate-700 shadow-sm text-primary-700 dark:text-primary-300' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200'">
                        🛍️ Achats
                        <span class="ml-1 text-[10px] font-bold text-gray-400">{{ $ventes->total() }}</span>
                    </button>
                    @if(auth()->user()?->aFonctionnalite('rdv'))
                    <button type="button" x-on:click="onglet = 'rdv'"
                            class="px-3 sm:px-4 py-2 rounded-xl text-xs font-semibold transition-all flex-shrink-0 whitespace-nowrap"
                            :class="onglet === 'rdv' ? 'bg-white dark:bg-slate-700 shadow-sm text-primary-700 dark:text-primary-300' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200'">
                        📅 RDV
                        <span class="ml-1 text-[10px] font-bold text-gray-400">{{ $rdvAVenir->count() + $rdvPasses->count() }}</span>
                    </button>
                    @endif
                    <button type="button" x-on:click="onglet = 'timeline'"
                            class="px-3 sm:px-4 py-2 rounded-xl text-xs font-semibold transition-all flex-shrink-0 whitespace-nowrap"
                            :class="onglet === 'timeline' ? 'bg-white dark:bg-slate-700 shadow-sm text-primary-700 dark:text-primary-300' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200'">
                        🕒 Historique
                        <span class="ml-1 text-[10px] font-bold text-gray-400">{{ $timeline->count() }}</span>
                    </button>
                    @if(auth()->user()->aFonctionnalite('credits'))
                    <button type="button" x-on:click="onglet = 'credits'"
                            class="px-3 sm:px-4 py-2 rounded-xl text-xs font-semibold transition-all flex-shrink-0 whitespace-nowrap"
                            :class="onglet === 'credits' ? 'bg-white dark:bg-slate-700 shadow-sm text-primary-700 dark:text-primary-300' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200'">
                        🕐 Crédits
                        <span class="ml-1 text-[10px] font-bold text-gray-400">{{ $credits->count() }}</span>
                    </button>
                    @endif
                    <button type="button" x-on:click="onglet = 'devis'"
                            class="px-3 sm:px-4 py-2 rounded-xl text-xs font-semibold transition-all flex-shrink-0 whitespace-nowrap"
                            :class="onglet === 'devis' ? 'bg-white dark:bg-slate-700 shadow-sm text-primary-700 dark:text-primary-300' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200'">
                        📄 Devis
                        <span class="ml-1 text-[10px] font-bold text-gray-400">{{ $devis->count() }}</span>
                    </button>
                    <button type="button" x-on:click="onglet = 'factures'"
                            class="px-3 sm:px-4 py-2 rounded-xl text-xs font-semibold transition-all flex-shrink-0 whitespace-nowrap"
                            :class="onglet === 'factures' ? 'bg-white dark:bg-slate-700 shadow-sm text-primary-700 dark:text-primary-300' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200'">
                        🧾 Factures
                        <span class="ml-1 text-[10px] font-bold text-gray-400">{{ $factures->count() }}</span>
                    </button>
                    <button type="button" x-on:click="onglet = 'commandes'"
                            class="px-3 sm:px-4 py-2 rounded-xl text-xs font-semibold transition-all flex-shrink-0 whitespace-nowrap"
                            :class="onglet === 'commandes' ? 'bg-white dark:bg-slate-700 shadow-sm text-primary-700 dark:text-primary-300' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200'">
                        🛍️ Commandes
                        <span class="ml-1 text-[10px] font-bold text-gray-400">{{ $commandes->count() }}</span>
                    </button>
                    <button type="button" x-on:click="onglet = 'photos'"
                            class="px-3 sm:px-4 py-2 rounded-xl text-xs font-semibold transition-all flex-shrink-0 whitespace-nowrap"
                            :class="onglet === 'photos' ? 'bg-white dark:bg-slate-700 shadow-sm text-primary-700 dark:text-primary-300' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200'">
                        📎 Photos & fichiers
                        <span class="ml-1 text-[10px] font-bold text-gray-400">{{ $client->photos->count() }}</span>
                    </button>
                    <button type="button" x-on:click="onglet = 'remises'"
                            class="px-3 sm:px-4 py-2 rounded-xl text-xs font-semibold transition-all flex-shrink-0 whitespace-nowrap"
                            :class="onglet === 'remises' ? 'bg-white dark:bg-slate-700 shadow-sm text-primary-700 dark:text-primary-300' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200'">
                        🎫 Remises & Avoirs
                        <span class="ml-1 text-[10px] font-bold text-gray-400">{{ $codesReduction->count() + $avoirs->count() }}</span>
                    </button>
                </div>

                {{-- Onglet Timeline --}}
                <div x-show="onglet === 'timeline'" x-cloak class="max-h-96 overflow-y-auto p-4">
                    @if($timeline->count())
                        <ol class="relative border-l border-gray-200 dark:border-slate-700 ml-3 space-y-4">
                            @foreach($timeline as $event)
                                <li class="ml-5">
                                    <span class="absolute -left-3 flex items-center justify-center w-6 h-6 bg-white dark:bg-slate-800 rounded-full ring-2 ring-gray-200 dark:ring-slate-700 text-sm">
                                        {{ $event['icon'] }}
                                    </span>
                                    <div class="flex justify-between items-start gap-2">
                                        <div class="min-w-0">
                                            <a href="{{ $event['url'] }}" class="font-semibold text-sm text-gray-800 dark:text-slate-100 hover:text-primary-600">{{ $event['titre'] }}</a>
                                            <p class="text-xs text-gray-500 dark:text-slate-400 truncate">{{ $event['sous'] }}</p>
                                        </div>
                                        <time class="text-[11px] text-gray-400 shrink-0">{{ $event['date']?->format('d/m/Y H:i') }}</time>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <p class="text-center text-gray-400 text-sm py-8">Aucun événement</p>
                    @endif
                </div>

                {{-- Onglet Achats --}}
                <div x-show="onglet === 'achats'" class="divide-y divide-gray-50 dark:divide-slate-700 max-h-96 overflow-y-auto">
                    @forelse($ventes as $vente)
                    <div class="px-5 py-3 flex items-center justify-between text-sm">
                        <div>
                            <p class="font-medium text-gray-900">{{ number_format($vente->total, 0, ',', ' ') }} FCFA</p>
                            <p class="text-xs text-gray-400">{{ $vente->created_at->format('d/m/Y à H:i') }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="badge {{ $vente->mode_paiement === 'mobile_money' ? 'badge-primary' : 'badge-secondary' }}">
                                {{ $vente->mode_paiement === 'mobile_money' ? 'Mobile' : 'Cash' }}
                            </span>
                            <a href="{{ route('dashboard.ventes.show', $vente) }}" class="btn-icon text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                    @empty
                    <div class="px-5 py-8 text-center text-sm text-gray-400">Aucun achat enregistré.</div>
                    @endforelse
                </div>

                {{-- Onglet RDV --}}
                @if(auth()->user()?->aFonctionnalite('rdv'))
                <div x-show="onglet === 'rdv'" x-cloak class="divide-y divide-gray-50 dark:divide-slate-700 max-h-96 overflow-y-auto">
                    @if($rdvAVenir->isNotEmpty())
                    <div class="px-5 py-2 text-[10px] font-bold uppercase tracking-wider text-primary-600 dark:text-primary-400 bg-primary-50/40 dark:bg-primary-900/20">
                        À venir ({{ $rdvAVenir->count() }})
                    </div>
                    @foreach($rdvAVenir as $rdv)
                    <div class="px-5 py-3 flex items-center justify-between text-sm">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-slate-100">{{ $rdv->debut_le->translatedFormat('d F Y') }} à {{ $rdv->debut_le->format('H\hi') }}</p>
                            <p class="text-xs text-gray-400 dark:text-slate-500">{{ $rdv->label_prestations }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            @php $badge = $rdv->statut_badge; @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold
                                @switch($badge['color'])
                                    @case('amber') bg-amber-100 text-amber-700 @break
                                    @case('blue') bg-blue-100 text-blue-700 @break
                                    @default bg-gray-100 text-gray-700
                                @endswitch">{{ $badge['label'] }}</span>
                            <a href="{{ route('dashboard.rdv.show', $rdv) }}" class="btn-icon text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                    @endforeach
                    @endif

                    @if($rdvPasses->isNotEmpty())
                    <div class="px-5 py-2 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-slate-500 bg-gray-50/60 dark:bg-slate-800/60">
                        Historique
                    </div>
                    @foreach($rdvPasses as $rdv)
                    <div class="px-5 py-3 flex items-center justify-between text-sm opacity-70">
                        <div>
                            <p class="font-medium text-gray-700 dark:text-slate-300">{{ $rdv->debut_le->translatedFormat('d F Y') }} à {{ $rdv->debut_le->format('H\hi') }}</p>
                            <p class="text-xs text-gray-400 dark:text-slate-500">{{ $rdv->label_prestations }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            @php $badge = $rdv->statut_badge; @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold
                                @switch($badge['color'])
                                    @case('emerald') bg-emerald-100 text-emerald-700 @break
                                    @case('red') bg-red-100 text-red-700 @break
                                    @default bg-gray-100 text-gray-600
                                @endswitch">{{ $badge['label'] }}</span>
                            <a href="{{ route('dashboard.rdv.show', $rdv) }}" class="btn-icon text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                    @endforeach
                    @endif

                    @if($rdvAVenir->isEmpty() && $rdvPasses->isEmpty())
                    <div class="px-5 py-8 text-center text-sm text-gray-400">Aucun rendez-vous enregistré.</div>
                    @endif

                    <div class="px-5 py-3">
                        <a href="{{ route('dashboard.rdv.create') }}?client_id={{ $client->id }}"
                           class="text-xs text-primary-600 hover:underline font-medium">+ Créer un RDV pour ce client</a>
                    </div>
                </div>
                @endif

                {{-- Onglet Crédits --}}
                @if(auth()->user()->aFonctionnalite('credits'))
                <div x-show="onglet === 'credits'" x-cloak class="divide-y divide-gray-50 dark:divide-slate-700 max-h-96 overflow-y-auto">
                    @if($credits->count())
                        @foreach($credits as $credit)
                        <a href="{{ route('dashboard.credits.show', $credit) }}" class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                    {{ $credit->vente->items->pluck('nom_snapshot')->implode(', ') ?: 'Crédit #' . substr($credit->id, 0, 8) }}
                                </p>
                                <p class="text-xs text-gray-500">{{ $credit->date_debut->format('d/m/Y') }} · {{ $credit->nb_echeances }} éch.</p>
                            </div>
                            <div class="text-right flex-shrink-0 ml-3">
                                <p class="text-sm font-bold {{ $credit->reste_a_payer > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                    {{ number_format($credit->reste_a_payer, 0, ',', ' ') }} F
                                </p>
                                <span class="text-[10px] {{ $credit->statut === 'solde' ? 'badge badge-success' : ($credit->statut === 'retard' ? 'badge badge-danger' : 'badge badge-info') }}">
                                    {{ $credit->statut === 'solde' ? 'Soldé' : ($credit->statut === 'retard' ? 'Retard' : 'En cours') }}
                                </span>
                            </div>
                        </a>
                        @endforeach
                    @else
                        <p class="text-center text-gray-400 text-sm py-8">Aucun crédit pour ce client.</p>
                    @endif
                </div>
                @endif

                {{-- Onglet Devis --}}
                <div x-show="onglet === 'devis'" x-cloak class="divide-y divide-gray-50 dark:divide-slate-700 max-h-96 overflow-y-auto">
                    @forelse($devis as $d)
                    <a href="{{ route('dashboard.devis.show', ['devis' => $d->id]) }}" class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $d->numero }}</p>
                            <p class="text-xs text-gray-500">{{ $d->date_creation->format('d/m/Y') }} · Expire le {{ $d->date_expiration->format('d/m/Y') }}</p>
                        </div>
                        <div class="text-right flex-shrink-0 ml-3">
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($d->total_ttc, 0, ',', ' ') }} F</p>
                            <span class="text-[10px] px-2 py-0.5 rounded-full font-bold
                                @switch($d->statut)
                                    @case('brouillon') bg-gray-100 text-gray-700 @break
                                    @case('envoye') bg-blue-100 text-blue-700 @break
                                    @case('accepte') bg-emerald-100 text-emerald-700 @break
                                    @case('refuse') bg-red-100 text-red-700 @break
                                    @default bg-gray-100 text-gray-600
                                @endswitch">{{ ucfirst($d->statut) }}</span>
                        </div>
                    </a>
                    @empty
                    <div class="px-5 py-8 text-center text-sm text-gray-400">Aucun devis.</div>
                    @endforelse
                </div>

                {{-- Onglet Factures --}}
                <div x-show="onglet === 'factures'" x-cloak class="divide-y divide-gray-50 dark:divide-slate-700 max-h-96 overflow-y-auto">
                    @forelse($factures as $f)
                    <a href="{{ route('dashboard.factures.show', ['facture' => $f->id]) }}" class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $f->numero }}</p>
                            <p class="text-xs text-gray-500">Émise le {{ $f->date_emission->format('d/m/Y') }} · Échéance {{ $f->date_echeance->format('d/m/Y') }}</p>
                        </div>
                        <div class="text-right flex-shrink-0 ml-3">
                            <p class="text-sm font-bold {{ $f->estPayee ? 'text-emerald-600' : ($f->resteAPayer > 0 && $f->date_echeance->isPast() ? 'text-red-600' : 'text-gray-900 dark:text-white') }}">{{ number_format($f->total_ttc, 0, ',', ' ') }} F</p>
                            <span class="text-[10px] px-2 py-0.5 rounded-full font-bold
                                @switch($f->statut)
                                    @case('payee') bg-emerald-100 text-emerald-700 @break
                                    @case('en_attente') bg-amber-100 text-amber-700 @break
                                    @case('annulee') bg-red-100 text-red-700 @break
                                    @default bg-gray-100 text-gray-600
                                @endswitch">{{ $f->estPayee ? 'Payée' : ucfirst(str_replace('_', ' ', $f->statut)) }}</span>
                        </div>
                    </a>
                    @empty
                    <div class="px-5 py-8 text-center text-sm text-gray-400">Aucune facture.</div>
                    @endforelse
                </div>

                {{-- Onglet Commandes boutique --}}
                <div x-show="onglet === 'commandes'" x-cloak class="divide-y divide-gray-50 dark:divide-slate-700 max-h-96 overflow-y-auto">
                    @forelse($commandes as $cmd)
                    <a href="{{ route('dashboard.boutique.commandes.show', $cmd) }}" class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $cmd->numero }}</p>
                            <p class="text-xs text-gray-500">{{ $cmd->created_at->format('d/m/Y H:i') }} · {{ $cmd->items->count() }} article(s)</p>
                        </div>
                        <div class="text-right flex-shrink-0 ml-3">
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($cmd->total, 0, ',', ' ') }} F</p>
                            <span class="text-[10px] px-2 py-0.5 rounded-full font-bold
                                @switch($cmd->statut)
                                    @case('nouvelle') bg-blue-100 text-blue-700 @break
                                    @case('acceptee') bg-amber-100 text-amber-700 @break
                                    @case('en_preparation') bg-purple-100 text-purple-700 @break
                                    @case('en_livraison') bg-sky-100 text-sky-700 @break
                                    @case('livree') bg-emerald-100 text-emerald-700 @break
                                    @case('annulee') bg-red-100 text-red-700 @break
                                    @case('refusee') bg-red-100 text-red-700 @break
                                    @default bg-gray-100 text-gray-600
                                @endswitch">{{ ucfirst(str_replace('_', ' ', $cmd->statut)) }}</span>
                        </div>
                    </a>
                    @empty
                    <div class="px-5 py-8 text-center text-sm text-gray-400">Aucune commande en ligne.</div>
                    @endforelse
                </div>

                {{-- Onglet Galerie photos & fichiers --}}
                <div x-show="onglet === 'photos'" x-cloak
                     x-data="{
                         photos: @js($client->photos->map(fn($p) => [
                             'url'     => $p->url,
                             'legende' => $p->legende ?? '',
                             'type'    => $p->type,
                             'isPdf'   => $p->isPdf(),
                             'label'   => match($p->type) {
                                 'avant'       => 'Avant',
                                 'apres'       => 'Après',
                                 'avant_apres' => 'Avant/Après',
                                 default       => 'Autre',
                             },
                             'color'   => match($p->type) {
                                 'avant'       => 'bg-amber-500',
                                 'apres'       => 'bg-emerald-500',
                                 'avant_apres' => 'bg-blue-500',
                                 default       => 'bg-gray-500',
                             },
                         ])->values()->all()),
                         current: 0,
                         open: false,
                         openAt(i) { 
                             // Si c'est un PDF, ouvrir dans un nouvel onglet au lieu du lightbox
                             if (this.photos[i].isPdf) {
                                 window.open(this.photos[i].url, '_blank');
                             } else {
                                 this.current = i;
                                 this.open = true;
                             }
                         },
                         prev() { 
                             do {
                                 this.current = (this.current - 1 + this.photos.length) % this.photos.length;
                             } while (this.photos[this.current].isPdf && this.photos.some(p => !p.isPdf));
                         },
                         next() { 
                             do {
                                 this.current = (this.current + 1) % this.photos.length;
                             } while (this.photos[this.current].isPdf && this.photos.some(p => !p.isPdf));
                         }
                     }"
                     @keydown.arrow-left.window="if(open) prev()"
                     @keydown.arrow-right.window="if(open) next()"
                     @keydown.escape.window="open = false"
                     class="p-5">

                    <div class="flex items-center justify-between mb-4">
                        <p class="text-xs text-gray-500">{{ $client->photos->count() }} fichier(s)</p>
                        <button x-data type="button" @click="$dispatch('open-photos-modal')" class="btn-primary text-xs">
                            + Ajouter
                        </button>
                    </div>

                    @if($client->photos->count() > 0)
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                            @foreach($client->photos as $i => $photo)
                                <div class="flex flex-col gap-1">
                                    @if($photo->isPdf())
                                        {{-- Affichage pour les PDF --}}
                                        <a href="{{ $photo->url }}" target="_blank" class="relative group">
                                            <div class="w-full aspect-square bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 rounded-lg hover:opacity-80 transition flex flex-col items-center justify-center border-2 border-red-200 dark:border-red-700">
                                                <svg class="w-12 h-12 text-red-600 dark:text-red-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                                </svg>
                                                <span class="text-xs font-bold text-red-600 dark:text-red-400">PDF</span>
                                            </div>
                                    @else
                                        {{-- Affichage pour les images --}}
                                        <div class="relative group cursor-pointer" @click="openAt({{ $i }})">
                                            <img src="{{ $photo->url }}" alt="{{ $photo->legende }}"
                                                 class="w-full aspect-square object-cover rounded-lg hover:opacity-80 transition">
                                    @endif
                                        {{-- Badge type --}}
                                        @php
                                            $typeColor = match($photo->type) {
                                                'avant' => 'bg-amber-500',
                                                'apres' => 'bg-emerald-500',
                                                'avant_apres' => 'bg-blue-500',
                                                default => 'bg-gray-500',
                                            };
                                            $typeLabel = match($photo->type) {
                                                'avant' => 'Avant',
                                                'apres' => 'Après',
                                                'avant_apres' => 'Avant/Après',
                                                default => 'Autre',
                                            };
                                        @endphp
                                        <div class="absolute top-1.5 left-1.5">
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold text-white {{ $typeColor }} uppercase shadow">
                                                {{ $typeLabel }}
                                            </span>
                                        </div>
                                        {{-- Bouton supprimer --}}
                                        @if(auth()->user()->isAdmin())
                                        <form method="POST" action="{{ route('dashboard.clients.photos.destroy', [$client, $photo]) }}?onglet=photos"
                                              id="delete-photo-{{ $photo->id }}"
                                              class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition"
                                              @click.stop>
                                            @csrf @method('DELETE')
                                            <button type="button"
                                                    onclick="window.dispatchEvent(new CustomEvent('confirm-delete',{detail:{formId:'delete-photo-{{ $photo->id }}',title:'Supprimer ce fichier ?',message:'Ce fichier sera définitivement supprimé.'}}))"
                                                    class="w-6 h-6 bg-red-600 text-white rounded flex items-center justify-center hover:bg-red-700">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </form>
                                        @endif
                                        {{-- Légende overlay au survol (seulement pour les images) --}}
                                        @if($photo->legende && $photo->isImage())
                                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent rounded-b-lg px-2 pt-6 pb-1.5
                                                    opacity-0 group-hover:opacity-100 transition pointer-events-none">
                                            <p class="text-white text-[10px] leading-tight truncate">{{ $photo->legende }}</p>
                                        </div>
                                        @endif
                                    @if($photo->isPdf())
                                        </a>
                                    @else
                                        </div>
                                    @endif
                                    {{-- Légende sous la vignette (toujours visible) --}}
                                    @if($photo->legende)
                                    <p class="text-[10px] text-gray-500 dark:text-slate-400 truncate leading-tight px-0.5">{{ $photo->legende }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 text-center py-8">Aucun fichier. Ajoutez des photos ou documents pour suivre l'évolution.</p>
                    @endif

                    {{-- ── Lightbox / Slider ── --}}
                    <div x-show="open" x-cloak
                         class="fixed inset-0 z-[80] bg-black/95 flex items-center justify-center"
                         @click="open = false">
                        <div class="relative w-full max-w-3xl mx-4 flex flex-col items-center" @click.stop>

                            {{-- Bouton fermer --}}
                            <button @click="open = false"
                                    class="absolute -top-10 right-0 text-white/70 hover:text-white transition text-3xl leading-none">&times;</button>

                            {{-- Photo --}}
                            <img :src="photos[current].url"
                                 :alt="photos[current].legende"
                                 class="max-h-[72vh] w-auto max-w-full object-contain rounded-xl shadow-2xl">

                            {{-- Badge + légende --}}
                            <div class="mt-4 flex flex-col items-center gap-1.5">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold text-white uppercase"
                                      :class="{
                                          'bg-amber-500':   photos[current].type === 'avant',
                                          'bg-emerald-500': photos[current].type === 'apres',
                                          'bg-blue-500':    photos[current].type === 'avant_apres',
                                          'bg-gray-500':    !['avant','apres','avant_apres'].includes(photos[current].type)
                                      }"
                                      x-text="photos[current].label"></span>
                                <p class="text-white/80 text-sm text-center" x-show="photos[current].legende" x-text="photos[current].legende"></p>
                                <p class="text-gray-500 text-xs" x-text="(current + 1) + ' / ' + photos.filter(p => !p.isPdf).length"></p>
                            </div>

                            {{-- Prev --}}
                            <button x-show="photos.filter(p => !p.isPdf).length > 1"
                                    @click.stop="prev()"
                                    class="absolute left-0 top-1/3 -translate-y-1/2 -translate-x-12 w-10 h-10 rounded-full bg-white/15 hover:bg-white/30 text-white flex items-center justify-center transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                            </button>
                            {{-- Next --}}
                            <button x-show="photos.filter(p => !p.isPdf).length > 1"
                                    @click.stop="next()"
                                    class="absolute right-0 top-1/3 -translate-y-1/2 translate-x-12 w-10 h-10 rounded-full bg-white/15 hover:bg-white/30 text-white flex items-center justify-center transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>

                            {{-- Miniatures (uniquement les images) --}}
                            <div class="flex gap-2 mt-4 overflow-x-auto max-w-full pb-1" x-show="photos.filter(p => !p.isPdf).length > 1">
                                <template x-for="(p, i) in photos" :key="i">
                                    <button x-show="!p.isPdf" @click.stop="current = i"
                                            class="flex-shrink-0 w-12 h-12 rounded-lg overflow-hidden border-2 transition"
                                            :class="i === current ? 'border-white' : 'border-transparent opacity-50 hover:opacity-80'">
                                        <img :src="p.url" :alt="p.legende" class="w-full h-full object-cover">
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Onglet Remises & Avoirs --}}
                <div x-show="onglet === 'remises'" x-cloak class="max-h-96 overflow-y-auto">
                    {{-- Codes de réduction actifs --}}
                    @php
                        $codesActifs = $codesReduction->filter(fn($c) => $c->statut() === 'actif');
                        $codesInactifs = $codesReduction->filter(fn($c) => $c->statut() !== 'actif');
                    @endphp

                    @if($codesReduction->count() > 0)
                    <div class="px-5 py-2 text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 bg-emerald-50/40 dark:bg-emerald-900/20">
                        Codes de réduction ({{ $codesActifs->count() }} actifs / {{ $codesReduction->count() }} total)
                    </div>
                    <div class="divide-y divide-gray-50 dark:divide-slate-700">
                        @foreach($codesReduction->sortByDesc(fn($c) => $c->statut() === 'actif') as $code)
                        <div class="px-5 py-3 flex items-center justify-between text-sm">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold text-gray-900 dark:text-white text-xs">{{ $code->code }}</span>
                                    @php $statut = $code->statut(); @endphp
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-bold
                                        @switch($statut)
                                            @case('actif') bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 @break
                                            @case('epuise') bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 @break
                                            @case('expire') bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400 @break
                                            @default bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400
                                        @endswitch">
                                        {{ $statut }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    @if($code->type === 'pourcentage')
                                        {{ $code->valeur }}% de réduction
                                    @else
                                        {{ number_format($code->valeur, 0, ',', ' ') }} FCFA de réduction
                                    @endif
                                    @if($code->montant_minimum)
                                        · min. {{ number_format($code->montant_minimum, 0, ',', ' ') }} FCFA
                                    @endif
                                </p>
                                @if($code->date_fin || $code->limite_utilisation)
                                <p class="text-[10px] text-gray-400 mt-0.5">
                                    @if($code->date_debut && $code->date_debut->isFuture())
                                        Valide à partir du {{ $code->date_debut->format('d/m/Y') }}
                                    @endif
                                    @if($code->date_fin)
                                        @if($code->date_debut && $code->date_debut->isFuture()) · @endif
                                        Expire le {{ $code->date_fin->format('d/m/Y') }}
                                    @endif
                                    @if($code->limite_utilisation)
                                        · {{ $code->nb_utilisations }}/{{ $code->limite_utilisation }} utilisation(s)
                                    @endif
                                </p>
                                @endif
                                @if($code->description)
                                <p class="text-[10px] text-gray-400 italic mt-0.5">{{ $code->description }}</p>
                                @endif
                            </div>
                            <div class="flex-shrink-0 ml-3">
                                <span class="text-sm font-bold text-gray-900 dark:text-white">
                                    @if($code->type === 'pourcentage')
                                        -{{ $code->valeur }}%
                                    @else
                                        -{{ number_format($code->valeur, 0, ',', ' ') }}
                                    @endif
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="px-5 py-8 text-center text-sm text-gray-400">Aucun code de réduction.</div>
                    @endif

                    {{-- Avoirs --}}
                    @if($avoirs->count() > 0)
                    <div class="px-5 py-2 text-[10px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400 bg-blue-50/40 dark:bg-blue-900/20 mt-1">
                        Avoirs ({{ $avoirs->count() }})
                    </div>
                    <div class="divide-y divide-gray-50 dark:divide-slate-700">
                        @foreach($avoirs as $avoir)
                        <div class="px-5 py-3 flex items-center justify-between text-sm">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold text-gray-900 dark:text-white text-xs">
                                        {{ $avoir->codeReduction?->code ?? 'Avoir #' . substr($avoir->id, 0, 6) }}
                                    </span>
                                    @if($avoir->codeReduction)
                                        @php $statutAvoir = $avoir->codeReduction->statut(); @endphp
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-bold
                                            @if($statutAvoir === 'actif') bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400
                                            @elseif($statutAvoir === 'epuise') bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400
                                            @else bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 @endif">
                                            {{ $statutAvoir }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ number_format($avoir->montant, 0, ',', ' ') }} FCFA
                                    @if($avoir->motif)
                                        · {{ $avoir->motif }}
                                    @endif
                                </p>
                                @if($avoir->vente)
                                <p class="text-[10px] text-gray-400 mt-0.5">
                                    De la vente
                                    <a href="{{ route('dashboard.ventes.show', $avoir->vente) }}" class="text-primary-500 hover:underline">
                                        {{ $avoir->vente->numero ?? '#' . substr($avoir->vente->id, 0, 8) }}
                                    </a>
                                    · {{ $avoir->created_at->format('d/m/Y') }}
                                </p>
                                @endif
                            </div>
                            <div class="flex-shrink-0 ml-3">
                                <span class="text-sm font-bold text-blue-600 dark:text-blue-400">
                                    {{ number_format($avoir->montant, 0, ',', ' ') }} F
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="px-5 py-8 text-center text-sm text-gray-400">Aucun avoir.</div>
                    @endif
                </div>

            </div>
        </div>

        {{-- ═══ MODAL ÉDITION ═══ --}}
        <div x-data="{ show: false }"
             @open-edit-show.window="show = true"
             x-init="{{ $errors->any() ? 'show = true' : '' }}"
             x-show="show" x-cloak
             class="modal-backdrop"
             @keydown.escape.window="show = false"
             @click.self="show = false">
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
                    <button @click="show = false" class="btn-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    @if($errors->any())
                    <div class="mb-4 p-3 bg-red-50 rounded-xl text-sm text-red-600 space-y-0.5">
                        @foreach($errors->all() as $e)<p>&bull; {{ $e }}</p>@endforeach
                    </div>
                    @endif
                    <form method="POST" action="{{ route('dashboard.clients.update', $client) }}" class="space-y-4" x-data="{ typeClient: '{{ old('type_client', $client->type_client ?? 'personne_physique') }}' }">
                        @csrf
                        @method('PUT')
                        
                        {{-- Type de client --}}
                        <div class="form-group mb-0">
                            <label class="form-label">Type de client *</label>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="flex items-center gap-2 px-3 py-2 border rounded-lg cursor-pointer transition"
                                       :class="typeClient === 'personne_physique' ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:border-gray-300'">
                                    <input type="radio" name="type_client" value="personne_physique" class="text-primary-600" 
                                           x-model="typeClient">
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
                                                   value="{{ old('prenom', $client->prenom) }}"
                                                   :required="typeClient === 'personne_physique'">
                                        </div>
                                        <div class="form-group mb-0">
                                            <label class="form-label">Nom *</label>
                                            <input type="text" name="nom" maxlength="50" class="form-input"
                                                   value="{{ old('nom', $client->nom) }}"
                                                   :required="typeClient === 'personne_physique'">
                                        </div>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="est_patient" value="1" class="rounded text-primary-600"
                                                   {{ old('est_patient', $client->est_patient) ? 'checked' : '' }}>
                                            <span class="text-sm font-medium text-gray-700">Ce client est un patient</span>
                                        </label>
                                        <p class="text-xs text-gray-500 mt-1">Affichera "Patient" sur les factures</p>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="form-label">Anniversaire (jour et mois)</label>
                                        <div class="grid grid-cols-2 gap-2">
                                            @php
                                                $dn = old('date_naissance', $client->date_naissance ?? '');
                                                $dnMois = $dn ? substr($dn, 0, 2) : '';
                                                $dnJour = $dn ? substr($dn, 3, 2) : '';
                                                $mois = ['01'=>'Janvier','02'=>'Février','03'=>'Mars','04'=>'Avril','05'=>'Mai','06'=>'Juin','07'=>'Juillet','08'=>'Août','09'=>'Septembre','10'=>'Octobre','11'=>'Novembre','12'=>'Décembre'];
                                            @endphp
                                            <select name="date_naissance_mois" id="show-mois-sel" class="form-input">
                                                <option value="">Mois</option>
                                                @foreach($mois as $n => $m)
                                                <option value="{{ $n }}" @selected($dnMois === $n)>{{ $m }}</option>
                                                @endforeach
                                            </select>
                                            <select name="date_naissance_jour" id="show-jour-sel" class="form-input">
                                                <option value="">Jour</option>
                                                @for($d = 1; $d <= 31; $d++)
                                                @php $ds = str_pad($d, 2, '0', STR_PAD_LEFT) @endphp
                                                <option value="{{ $ds }}" @selected($dnJour === $ds)>{{ $d }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <input type="hidden" name="date_naissance" id="show-dn-hidden" value="{{ $dn }}">
                                    </div>
                                </div>
                            </template>
                            
                            {{-- Champs pour entreprise --}}
                            <template x-if="typeClient === 'entreprise'">
                                <div class="col-span-2 space-y-3">
                                    <div class="form-group mb-0">
                                        <label class="form-label">Raison sociale *</label>
                                        <input type="text" name="raison_sociale" maxlength="255" class="form-input"
                                               value="{{ old('raison_sociale', $client->raison_sociale) }}"
                                               placeholder="Entreprise SARL"
                                               :required="typeClient === 'entreprise'">
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="form-group mb-0">
                                            <label class="form-label">N° Registre Commerce</label>
                                            <input type="text" name="numero_registre_commerce" maxlength="100" class="form-input"
                                                   value="{{ old('numero_registre_commerce', $client->numero_registre_commerce) }}"
                                                   placeholder="RC-123456">
                                        </div>
                                        <div class="form-group mb-0">
                                            <label class="form-label">Contact (Prénom Nom)</label>
                                            <input type="text" name="prenom" maxlength="100" class="form-input"
                                                   value="{{ old('prenom', $client->prenom) }}"
                                                   placeholder="Jean Dupont">
                                        </div>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="form-label">Adresse entreprise</label>
                                        <textarea name="adresse_entreprise" rows="2" maxlength="500" class="form-input resize-none"
                                                  placeholder="Adresse complète de l'entreprise...">{{ old('adresse_entreprise', $client->adresse_entreprise) }}</textarea>
                                    </div>
                                </div>
                            </template>
                            
                            {{-- Champs communs --}}
                            <div class="form-group mb-0">
                                <label class="form-label">Téléphone *</label>
                                <input type="text" name="telephone" required maxlength="30" class="form-input"
                                       value="{{ old('telephone', $client->telephone) }}">
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" maxlength="255" class="form-input"
                                       value="{{ old('email', $client->email) }}">
                            </div>
                            
                            <div class="col-span-2 form-group mb-0">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" rows="3" class="form-input resize-none"
                                          placeholder="Informations complémentaires, allergies, préférences...">{{ old('notes', $client->notes) }}</textarea>
                                <p class="text-xs text-gray-500 mt-1">Vous pouvez utiliser le HTML basique (gras, italique, listes...)</p>
                            </div>
                            
                            {{-- Informations supplémentaires (collapsible) - uniquement pour personne physique --}}
                            <template x-if="typeClient === 'personne_physique'">
                                <div class="col-span-2" x-data="{ showExtraShowEdit: false }">
                                    <button type="button" @click="showExtraShowEdit = !showExtraShowEdit"
                                            class="flex items-center gap-2 text-xs font-medium text-gray-500 hover:text-gray-700 transition-colors">
                                        <svg class="w-3.5 h-3.5 transition-transform" :class="showExtraShowEdit ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                        Informations supplémentaires
                                    </button>
                                    <div x-show="showExtraShowEdit" x-collapse class="mt-3 space-y-3">
                                        <div class="form-group mb-0">
                                            <label class="form-label">Adresse</label>
                                            <input type="text" name="adresse" maxlength="255" class="form-input"
                                                   value="{{ old('adresse', $client->adresse) }}" placeholder="Abidjan, Cocody...">
                                        </div>
                                        <div class="form-group mb-0">
                                            <label class="form-label">Pièce d'identité</label>
                                            <input type="text" name="piece_identite" maxlength="100" class="form-input"
                                                   value="{{ old('piece_identite', $client->piece_identite) }}" placeholder="N° CNI, Passeport...">
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        
                        {{-- Script pour synchroniser la date de naissance --}}
                        <script>
                        (function() {
                            document.addEventListener('DOMContentLoaded', function() {
                                var moisSel = document.getElementById('show-mois-sel');
                                var jourSel = document.getElementById('show-jour-sel');
                                var hidden  = document.getElementById('show-dn-hidden');
                                function update() {
                                    if (hidden) {
                                        hidden.value = (moisSel.value && jourSel.value) ? moisSel.value + '-' + jourSel.value : '';
                                    }
                                }
                                if (moisSel) moisSel.addEventListener('change', update);
                                if (jourSel) jourSel.addEventListener('change', update);
                            });
                        })();
                        </script>
                        
                        <div class="flex gap-3 pt-1">
                            <button type="button" @click="show = false" class="btn btn-outline flex-1 justify-center">Annuler</button>
                            <button type="submit" class="btn-primary flex-1 justify-center">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal d'ajout photos --}}
        <div x-data="{ show: false }" x-cloak
             @open-photos-modal.window="show = true">
            <div x-show="show" class="fixed inset-0 z-[70] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50" @click="show = false"></div>
                <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-2xl w-full max-w-md p-6" @click.stop>
                    <h3 class="font-bold text-gray-900 dark:text-gray-100 mb-4">Ajouter des photos & fichiers</h3>
                    <form method="POST" action="{{ route('dashboard.clients.photos.store', $client) }}?onglet=photos" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <div>
                            <label class="form-label">Type *</label>
                            <select name="type" required class="form-select">
                                <option value="avant">Avant</option>
                                <option value="apres">Après</option>
                                <option value="avant_apres" selected>Avant / Après</option>
                                <option value="autre">Autre</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Date</label>
                            <input type="date" name="date_prise" value="{{ now()->toDateString() }}" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Légende</label>
                            <input type="text" name="legende" maxlength="255" class="form-input" placeholder="Ex: Soin du visage">
                        </div>
                        <div>
                            <label class="form-label">Fichiers (jpg/png/pdf, max 10 Mo) *</label>
                            <input type="file" name="photos[]" multiple required accept="image/*,.pdf" class="form-input">
                            <p class="text-xs text-gray-500 mt-1">Photos (JPG, PNG, WebP) ou documents PDF scannés</p>
                        </div>
                        <div class="flex gap-3 pt-2">
                            <button type="button" @click="show = false" class="btn btn-outline flex-1 justify-center">Annuler</button>
                            <button type="submit" class="btn-primary flex-1 justify-center">Téléverser</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-dashboard-layout>
