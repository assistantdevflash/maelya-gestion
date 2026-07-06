<x-dashboard-layout>
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Commandes boutique</h1>
        <a href="{{ route('dashboard.boutique.config.index') }}" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200">
            ⚙️ Configuration
        </a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
            <p class="text-sm text-gray-500 mb-1">Nouvelles</p>
            <p class="text-2xl font-bold text-blue-600">{{ $stats['nouvelles'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
            <p class="text-sm text-gray-500 mb-1">En cours</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $stats['en_cours'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
            <p class="text-sm text-gray-500 mb-1">Livrées</p>
            <p class="text-2xl font-bold text-green-600">{{ $stats['livrees'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
            <p class="text-sm text-gray-500 mb-1">CA Total</p>
            <p class="text-2xl font-bold text-indigo-600">{{ number_format($stats['total_ca'], 0, ',', ' ') }} FCFA</p>
        </div>
    </div>

    {{-- Filtres --}}
    <form method="GET" class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Statut</label>
                <select name="statut" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700">
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
                <label class="block text-sm font-medium mb-1">Paiement</label>
                <select name="payee" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700">
                    <option value="">Tous</option>
                    <option value="1" {{ request('payee') == '1' ? 'selected' : '' }}>Payées</option>
                    <option value="0" {{ request('payee') == '0' ? 'selected' : '' }}>Non payées</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Recherche</label>
                <div class="flex gap-2">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}"
                        placeholder="N°, nom, téléphone..."
                        class="flex-1 px-3 py-2 border rounded-lg dark:bg-gray-700"
                    >
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        Filtrer
                    </button>
                </div>
            </div>
        </div>
    </form>

    {{-- Liste des commandes --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
        @if($commandes->isEmpty())
            <div class="text-center py-12">
                <p class="text-gray-500">Aucune commande pour le moment.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">N° Commande</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paiement</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($commandes as $commande)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-medium">{{ $commande->numero }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="font-medium">{{ $commande->client_prenom }} {{ $commande->client_nom }}</p>
                                        <p class="text-sm text-gray-500">{{ $commande->client_telephone }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    {{ $commande->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-semibold">
                                    {{ number_format($commande->total, 0, ',', ' ') }} FCFA
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $colors = [
                                            'nouvelle' => 'bg-blue-100 text-blue-800',
                                            'acceptee' => 'bg-green-100 text-green-800',
                                            'en_preparation' => 'bg-yellow-100 text-yellow-800',
                                            'en_livraison' => 'bg-indigo-100 text-indigo-800',
                                            'livree' => 'bg-emerald-100 text-emerald-800',
                                            'annulee' => 'bg-red-100 text-red-800',
                                            'refusee' => 'bg-red-100 text-red-800',
                                        ];
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $colors[$commande->statut] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst(str_replace('_', ' ', $commande->statut)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($commande->payee)
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            Payée
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                            Non payée
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <a href="{{ route('dashboard.boutique.commandes.show', $commande) }}" class="text-indigo-600 hover:text-indigo-900 font-medium">
                                        Voir
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $commandes->links() }}
            </div>
        @endif
    </div>
</div>
</x-dashboard-layout>
