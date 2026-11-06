<x-dashboard-layout>
<div class="space-y-8">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-display font-bold text-gray-900 dark:text-white tracking-tight">Commandes boutique</h1>
                <span id="badge-nouvelles"
                      class="px-2 py-0.5 rounded-full text-xs font-bold bg-primary-600 text-white {{ ($stats['nouvelles'] ?? 0) > 0 ? '' : 'hidden' }}">{{ $stats['nouvelles'] ?? 0 }}</span>
            </div>
            <p class="text-gray-500 dark:text-slate-400 mt-2">Gérez les commandes de votre boutique en ligne</p>
        </div>
        <div class="flex items-center gap-2 self-start sm:self-auto">
            <a href="{{ route('dashboard.boutique.commandes.export') }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}"
               class="btn-outline">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export CSV
            </a>
            @if(auth()->user()->isAdmin())
            <a href="{{ route('dashboard.boutique.config.index') }}" class="btn-ghost">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Configuration
            </a>
            @endif
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <a href="{{ route('dashboard.boutique.commandes.index', ['statut' => 'nouvelle']) }}"
           class="card hover:border-primary-300 dark:hover:border-primary-700 transition {{ request('statut') === 'nouvelle' ? 'ring-2 ring-primary-300 dark:ring-primary-700' : '' }}">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-slate-400 mb-1">Nouvelles</p>
                        <p class="text-3xl font-bold text-primary-600 dark:text-primary-400">{{ $stats['nouvelles'] }}</p>
                    </div>
                    <div class="w-14 h-14 bg-primary-100 dark:bg-primary-900/40 rounded-2xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>
        </a>
        <div class="card">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-slate-400 mb-1">En cours</p>
                        <p class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ $stats['en_cours'] }}</p>
                    </div>
                    <div class="w-14 h-14 bg-amber-100 dark:bg-amber-900/40 rounded-2xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                </div>
            </div>
        </div>
        <a href="{{ route('dashboard.boutique.commandes.index', ['statut' => 'livree']) }}"
           class="card hover:border-emerald-300 dark:hover:border-emerald-700 transition {{ request('statut') === 'livree' ? 'ring-2 ring-emerald-300 dark:ring-emerald-700' : '' }}">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-slate-400 mb-1">Livrées</p>
                        <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ $stats['livrees'] }}</p>
                    </div>
                    <div class="w-14 h-14 bg-emerald-100 dark:bg-emerald-900/40 rounded-2xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>
        </a>
        <div class="card">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-slate-400 mb-1">CA encaissé</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_ca'], 0, ',', ' ') }}</p>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">FCFA</p>
                    </div>
                    <div class="w-14 h-14 bg-gray-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-gray-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtres --}}
    <form method="GET" class="card">
        <div class="card-body">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Statut</label>
                    <select name="statut" class="input w-full">
                        <option value="">Tous</option>
                        @foreach(['nouvelle' => 'Nouvelles', 'acceptee' => 'Acceptées', 'en_preparation' => 'En préparation', 'en_livraison' => 'En livraison', 'livree' => 'Livrées', 'annulee' => 'Annulées', 'refusee' => 'Refusées'] as $valeur => $libelle)
                        <option value="{{ $valeur }}" {{ request('statut') == $valeur ? 'selected' : '' }}>{{ $libelle }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Paiement</label>
                    <select name="payee" class="input w-full">
                        <option value="">Tous</option>
                        <option value="1" {{ request('payee') == '1' ? 'selected' : '' }}>Payées</option>
                        <option value="0" {{ request('payee') == '0' ? 'selected' : '' }}>Non payées</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Du</label>
                    <input type="date" name="du" value="{{ request('du') }}" class="input w-full">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Au</label>
                    <input type="date" name="au" value="{{ request('au') }}" class="input w-full">
                </div>

                @if(! empty($zones))
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Zone</label>
                    <select name="zone" class="input w-full">
                        <option value="">Toutes</option>
                        @foreach($zones as $index => $zone)
                        <option value="{{ $index }}" {{ (string) request('zone') === (string) $index ? 'selected' : '' }}>
                            {{ $zone['nom'] ?? 'Zone ' . ($index + 1) }}@if(isset($zone['frais'])) — {{ number_format((int) $zone['frais'], 0, ',', ' ') }} F @endif
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Recherche</label>
                    <div class="flex gap-2">
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="N°, nom, téléphone, e-mail…" class="flex-1 input">
                        <button type="submit" class="btn-primary">Filtrer</button>
                    </div>
                </div>
            </div>

            @if(request()->hasAny(['statut', 'payee', 'search', 'du', 'au', 'zone']))
            <div class="mt-3">
                <a href="{{ route('dashboard.boutique.commandes.index') }}" class="text-xs text-gray-500 hover:text-gray-700 font-semibold">Réinitialiser les filtres</a>
            </div>
            @endif
        </div>
    </form>

    {{-- Barre d'actions groupées (les cases à cocher lui sont rattachées par l'attribut form) --}}
    <form id="form-actions-groupees" method="POST" action="{{ route('dashboard.boutique.commandes.actions-groupees') }}" class="card">
        @csrf
        <div class="card-body flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <input type="checkbox" id="cocher-tout" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                <label for="cocher-tout" class="text-sm font-medium text-gray-600 dark:text-slate-300 cursor-pointer select-none">Tout sélectionner</label>
                <span id="compteur-selection" class="text-xs font-semibold text-primary-600 hidden"></span>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" name="action" value="accepter"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/40 rounded-lg hover:bg-emerald-100 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Accepter la sélection
                </button>
                <button type="submit" name="action" value="refuser"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800/40 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Refuser la sélection
                </button>
            </div>
        </div>
    </form>

    {{-- Liste --}}
    <div class="card">
        @if($commandes->isEmpty())
            <div class="card-body text-center py-12">
                <div class="w-16 h-16 bg-gray-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <p class="text-gray-500 dark:text-slate-400">Aucune commande pour le moment</p>
            </div>
        @else
            {{-- Version mobile : cartes --}}
            <div class="lg:hidden divide-y divide-gray-100 dark:divide-slate-700">
                @foreach($commandes as $commande)
                <a href="{{ route('dashboard.boutique.commandes.show', $commande) }}" class="block p-4 active:bg-gray-50 dark:active:bg-slate-800">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-mono text-[11px] font-semibold text-primary-600">{{ $commande->numero }}</p>
                            <p class="font-semibold text-gray-900 dark:text-white truncate">{{ $commande->client_prenom }} {{ $commande->client_nom }}</p>
                            <p class="text-[11px] text-gray-400 dark:text-slate-500">{{ $commande->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="font-bold text-gray-900 dark:text-white">{{ number_format($commande->total, 0, ',', ' ') }} F</p>
                            <div class="mt-1">@include('dashboard.boutique.commandes.partials.statut-badge', ['statut' => $commande->statut])</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="text-[10px] font-bold uppercase {{ $commande->payee ? 'text-emerald-600' : 'text-gray-400' }}">
                            {{ $commande->payee ? 'Payée' : 'Non payée' }}
                        </span>
                        @if($commande->livraison_en_retard)
                        <span class="text-[10px] font-bold text-red-500">Livraison en retard</span>
                        @endif
                    </div>
                </a>
                @endforeach
            </div>

            <div class="overflow-x-auto hidden lg:block">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-800">
                        <tr>
                            <th class="pl-6 pr-2 py-4 w-10"></th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase tracking-wider">N° Commande</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase tracking-wider">Client</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase tracking-wider">Montant</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase tracking-wider">Paiement</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-700">
                        @foreach($commandes as $commande)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors">
                                <td class="pl-6 pr-2 py-4">
                                    <input type="checkbox" form="form-actions-groupees" name="ids[]"
                                           value="{{ $commande->id }}" data-selection
                                           {{ $commande->statut === 'nouvelle' ? '' : 'disabled' }}
                                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 disabled:opacity-40">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-semibold text-gray-900 dark:text-white font-mono text-sm">{{ $commande->numero }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $commande->client_prenom }} {{ $commande->client_nom }}</p>
                                        <p class="text-sm text-gray-500 dark:text-slate-400">{{ $commande->client_telephone }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-slate-400">
                                    {{ $commande->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-semibold text-gray-900 dark:text-white">
                                    {{ number_format($commande->total, 0, ',', ' ') }} <span class="text-xs font-normal text-gray-500">F</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @include('dashboard.boutique.commandes.partials.statut-badge', ['statut' => $commande->statut])
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($commande->payee)
                                        <span class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                            Payée
                                        </span>
                                    @else
                                        <span class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-slate-400">
                                            Non payée
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($commande->statut === 'nouvelle' && (auth()->user()->isAdmin() || auth()->user()->isGerant()))
                                        <form method="POST" action="{{ route('dashboard.boutique.commandes.statut', $commande) }}">
                                            @csrf
                                            <input type="hidden" name="statut" value="acceptee">
                                            <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg hover:bg-emerald-100 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Accepter
                                            </button>
                                        </form>
                                        @endif
                                        <a href="{{ route('dashboard.boutique.commandes.show', $commande) }}" class="inline-flex items-center gap-1.5 text-primary-600 hover:text-primary-700 dark:text-primary-400 font-semibold transition-colors text-sm">
                                            Voir
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card-body border-t border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/50">
                {{ $commandes->links() }}
            </div>
        @endif
    </div>
</div>
</x-dashboard-layout>