<x-dashboard-layout>
<div class="max-w-4xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Commande {{ $commande->numero }}</h1>
            <p class="text-sm text-gray-500">{{ $commande->created_at->format('d/m/Y à H:i') }}</p>
        </div>
        <a href="{{ route('dashboard.boutique.commandes.index') }}" class="text-indigo-600 hover:text-indigo-700 font-medium">
            ← Retour
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 p-4 rounded">
            <p class="text-green-700 dark:text-green-400">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 rounded">
            <p class="text-red-700 dark:text-red-400">{{ session('error') }}</p>
        </div>
    @endif

    {{-- Infos principales --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Client --}}
            <div>
                <h2 class="font-semibold text-lg mb-4">Informations client</h2>
                <dl class="space-y-2 text-sm">
                    <div>
                        <dt class="text-gray-500">Nom complet</dt>
                        <dd class="font-medium">{{ $commande->client_prenom }} {{ $commande->client_nom }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Téléphone</dt>
                        <dd class="font-medium">{{ $commande->client_telephone }}</dd>
                    </div>
                    @if($commande->client_email)
                        <div>
                            <dt class="text-gray-500">Email</dt>
                            <dd class="font-medium">{{ $commande->client_email }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-gray-500">Adresse de livraison</dt>
                        <dd class="font-medium">{{ $commande->client_adresse }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Statut et actions --}}
            <div>
                <h2 class="font-semibold text-lg mb-4">Statut et actions</h2>
                
                {{-- Changement de statut --}}
                @if(auth()->user()->role === 'admin')
                    <form method="POST" action="{{ route('dashboard.boutique.commandes.statut', $commande) }}" class="mb-4">
                        @csrf
                        <label class="block text-sm font-medium mb-2">Changer le statut</label>
                        <div class="flex gap-2">
                            <select name="statut" class="flex-1 px-3 py-2 border rounded-lg dark:bg-gray-700">
                                <option value="nouvelle" {{ $commande->statut == 'nouvelle' ? 'selected' : '' }}>Nouvelle</option>
                                <option value="acceptee" {{ $commande->statut == 'acceptee' ? 'selected' : '' }}>Acceptée</option>
                                <option value="en_preparation" {{ $commande->statut == 'en_preparation' ? 'selected' : '' }}>En préparation</option>
                                <option value="en_livraison" {{ $commande->statut == 'en_livraison' ? 'selected' : '' }}>En livraison</option>
                                <option value="livree" {{ $commande->statut == 'livree' ? 'selected' : '' }}>Livrée</option>
                                <option value="annulee" {{ $commande->statut == 'annulee' ? 'selected' : '' }}>Annulée</option>
                                <option value="refusee" {{ $commande->statut == 'refusee' ? 'selected' : '' }}>Refusée</option>
                            </select>
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                                Mettre à jour
                            </button>
                        </div>
                    </form>

                    {{-- Marquer comme payée --}}
                    @if($commande->peutEtreMarqueePayee())
                        <form method="POST" action="{{ route('dashboard.boutique.commandes.payer', $commande) }}" class="mb-4">
                            @csrf
                            <button type="submit" onclick="return confirm('Créer la vente et déduire le stock ?')" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                ✓ Marquer comme payée (créer vente)
                            </button>
                        </form>
                    @endif

                    @if($commande->payee)
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-3 mb-4">
                            <p class="text-sm text-green-700 dark:text-green-400">
                                ✓ Commande payée le {{ $commande->payee_at->format('d/m/Y à H:i') }}
                            </p>
                            @if($commande->vente_id)
                                <a href="{{ route('dashboard.ventes.show', $commande->vente_id) }}" class="text-sm text-green-600 hover:underline">
                                    → Voir la vente associée
                                </a>
                            @endif
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>

    {{-- Produits --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <h2 class="font-semibold text-lg mb-4">Produits commandés</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Produit</th>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-500">Quantité</th>
                        <th class="px-4 py-2 text-right text-sm font-medium text-gray-500">Prix unitaire</th>
                        <th class="px-4 py-2 text-right text-sm font-medium text-gray-500">Sous-total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($commande->items as $item)
                        <tr>
                            <td class="px-4 py-3">{{ $item->nom_snapshot }}</td>
                            <td class="px-4 py-3 text-center">{{ $item->quantite }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($item->prix_snapshot, 0, ',', ' ') }} FCFA</td>
                            <td class="px-4 py-3 text-right font-semibold">{{ number_format($item->sous_total, 0, ',', ' ') }} FCFA</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <td colspan="3" class="px-4 py-3 text-right font-medium">Sous-total :</td>
                        <td class="px-4 py-3 text-right font-semibold">{{ number_format($commande->sous_total, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="px-4 py-3 text-right font-medium">Frais de livraison :</td>
                        <td class="px-4 py-3 text-right font-semibold">{{ number_format($commande->frais_livraison, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="px-4 py-3 text-right font-bold text-lg">Total :</td>
                        <td class="px-4 py-3 text-right font-bold text-lg text-indigo-600">{{ number_format($commande->total, 0, ',', ' ') }} FCFA</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Notes --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <h2 class="font-semibold text-lg mb-4">Notes</h2>
        
        @if($commande->notes_client)
            <div class="mb-4">
                <p class="text-sm font-medium text-gray-500 mb-1">Notes du client :</p>
                <p class="text-gray-700 dark:text-gray-300">{{ $commande->notes_client }}</p>
            </div>
        @endif

        @if(auth()->user()->role === 'admin')
            <form method="POST" action="{{ route('dashboard.boutique.commandes.notes', $commande) }}">
                @csrf
                <label class="block text-sm font-medium mb-2">Notes internes (admin) :</label>
                <textarea 
                    name="notes_admin" 
                    rows="3"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700"
                >{{ $commande->notes_admin }}</textarea>
                <button type="submit" class="mt-2 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                    Enregistrer les notes
                </button>
            </form>
        @elseif($commande->notes_admin)
            <div>
                <p class="text-sm font-medium text-gray-500 mb-1">Notes internes :</p>
                <p class="text-gray-700 dark:text-gray-300">{{ $commande->notes_admin }}</p>
            </div>
        @endif
    </div>
</div>
</x-dashboard-layout>
