<x-dashboard-layout>
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-display font-bold text-gray-900 dark:text-white tracking-tight">Commandes boutique</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">Gérez les commandes de votre boutique en ligne</p>
        </div>
        @if(auth()->user()->isAdmin())
        <a href="{{ route('dashboard.boutique.config.index') }}" class="btn-ghost btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M12 1v6m0 6v6m4.22-13.22l-4.24 4.24m0 6l-4.24 4.24M23 12h-6m-6 0H5m13.22 4.22l-4.24-4.24m0-6l-4.24-4.24"/></svg>
            Configuration
        </a>
        @endif
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card card-body">
            <p class="text-sm text-gray-500 dark:text-slate-400 mb-1">Nouvelles</p>
            <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ $stats['nouvelles'] }}</p>
        </div>
        <div class="card card-body">
            <p class="text-sm text-gray-500 dark:text-slate-400 mb-1">En cours</p>
            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $stats['en_cours'] }}</p>
        </div>
        <div class="card card-body">
            <p class="text-sm text-gray-500 dark:text-slate-400 mb-1">Livrées</p>
            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $stats['livrees'] }}</p>
        </div>
        <div class="card card-body">
            <p class="text-sm text-gray-500 dark:text-slate-400 mb-1">CA Total</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_ca'], 0, ',', ' ') }} <span class="text-sm font-normal text-gray-500">FCFA</span></p>
        </div>
    </div>

    {{-- Filtres --}}
    <form method="GET" class="card card-body">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Statut</label>
                <select name="statut" class="input w-full">
                    <option value="">Tous</option>
                    <option value="nouvelle" {{ request('statut') == 'nouvelle' ? 'selected' : '' }}>Nouvelles</option>
                    <option value="acceptee" {{ request('statut') == 'acceptee' ? 'selected' : '' }}>Acceptées</option>
                    <option value="en_preparation" {{ request('statut') == 'en_preparation' ? 'selected' : '' }}>En préparation</option>
                    <option value="en_livraison" {{ request('statut') == 'en_livraison' ? 'selected' : '' }}>En livraison</option>
                    <option value="livree" {{ request('statut') == 'livree' ? 'selected' : '' }}>Livrées</option>
                    <option value="annulee" {{ request('statut') == 'annulee' ? 'selected' : '' }}>Annulées</option>
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
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Recherche</label>
                <div class="flex gap-2">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}"
                        placeholder="N°, nom, téléphone..."
                        class="flex-1 input"
                    >
                    <button type="submit" class="btn-primary">
                        Filtrer
                    </button>
                </div>
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
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">N° Commande</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Client</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Montant</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Statut</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Paiement</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-700">
                        @foreach($commandes as $commande)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="font-medium text-gray-900 dark:text-white font-mono text-sm">{{ $commande->numero }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $commande->client_prenom }} {{ $commande->client_nom }}</p>
                                        <p class="text-sm text-gray-500 dark:text-slate-400">{{ $commande->client_telephone }}</p>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                    {{ $commande->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-semibold text-gray-900 dark:text-white">
                                    {{ number_format($commande->total, 0, ',', ' ') }} <span class="text-xs font-normal text-gray-500">F</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @php
                                        $colors = [
                                            'nouvelle' => 'bg-primary-100 text-primary-800 dark:bg-primary-900/40 dark:text-primary-300',
                                            'acceptee' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
                                            'en_preparation' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                                            'en_livraison' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-300',
                                            'livree' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
                                            'annulee' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
                                            'refusee' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
                                        ];
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $colors[$commande->statut] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst(str_replace('_', ' ', $commande->statut)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($commande->payee)
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                            Payée
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-slate-400">
                                            Non payée
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <a href="{{ route('dashboard.boutique.commandes.show', $commande) }}" class="text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 font-medium transition-colors">
                                        Voir →
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card-body border-t border-gray-200 dark:border-slate-700">
                {{ $commandes->links() }}
            </div>
        @endif
    </div>
</div>
</x-dashboard-layout>
