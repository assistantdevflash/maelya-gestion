<x-dashboard-layout>
<div class="max-w-5xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="flex items-start justify-between">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('dashboard.boutique.commandes.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <h1 class="text-2xl font-display font-bold text-gray-900 dark:text-white tracking-tight">Commande {{ $commande->numero }}</h1>
            </div>
            <p class="text-sm text-gray-500 dark:text-slate-400">Créée le {{ $commande->created_at->format('d/m/Y à H:i') }}</p>
        </div>

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
        <span class="px-3 py-1.5 text-sm font-semibold rounded-full {{ $colors[$commande->statut] ?? 'bg-gray-100 text-gray-800' }}">
            {{ ucfirst(str_replace('_', ' ', $commande->statut)) }}
        </span>
    </div>

    @if(session('success'))
        <div class="card p-4 bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800/40 flex items-start gap-3">
            <div class="w-8 h-8 bg-emerald-100 dark:bg-emerald-900/40 rounded-lg flex items-center justify-center text-emerald-600 dark:text-emerald-400 flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <p class="text-emerald-700 dark:text-emerald-300 text-sm font-medium">{{ session('success') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Informations client --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="card">
                <div class="card-header">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Informations client</h2>
                </div>
                <div class="card-body space-y-3">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Nom complet</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $commande->client_prenom }} {{ $commande->client_nom }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Téléphone</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $commande->client_telephone }}</p>
                    </div>
                    @if($commande->client_email)
                    <div>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Email</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $commande->client_email }}</p>
                    </div>
                    @endif
                    <div>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Adresse de livraison</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $commande->adresse_livraison }}</p>
                    </div>
                </div>
            </div>

            {{-- Produits --}}
            <div class="card">
                <div class="card-header">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Produits commandés</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase">Produit</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-slate-400 uppercase">Qté</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-slate-400 uppercase">Prix unitaire</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-slate-400 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-700">
                            @foreach($commande->items as $item)
                                <tr>
                                    <td class="px-6 py-4 text-gray-900 dark:text-white">{{ $item->nom_snapshot }}</td>
                                    <td class="px-6 py-4 text-center text-gray-700 dark:text-slate-300">{{ $item->quantite }}</td>
                                    <td class="px-6 py-4 text-right text-gray-700 dark:text-slate-300">{{ number_format($item->prix_snapshot, 0, ',', ' ') }} FCFA</td>
                                    <td class="px-6 py-4 text-right font-semibold text-gray-900 dark:text-white">{{ number_format($item->sous_total, 0, ',', ' ') }} FCFA</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-slate-800">
                            <tr>
                                <td colspan="3" class="px-6 py-3 text-right text-sm font-medium text-gray-700 dark:text-slate-300">Sous-total</td>
                                <td class="px-6 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ number_format($commande->sous_total, 0, ',', ' ') }} FCFA</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="px-6 py-3 text-right text-sm font-medium text-gray-700 dark:text-slate-300">Frais de livraison</td>
                                <td class="px-6 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ number_format($commande->frais_livraison, 0, ',', ' ') }} FCFA</td>
                            </tr>
                            <tr class="bg-gray-100 dark:bg-slate-700">
                                <td colspan="3" class="px-6 py-4 text-right text-base font-bold text-gray-900 dark:text-white">Total</td>
                                <td class="px-6 py-4 text-right text-lg font-bold text-primary-600 dark:text-primary-400">{{ number_format($commande->total, 0, ',', ' ') }} FCFA</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Notes --}}
            <div class="card">
                <div class="card-header">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Notes et remarques</h2>
                </div>
                <div class="card-body">
                    @if($commande->notes_client)
                        <div class="mb-4">
                            <p class="text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Notes du client :</p>
                            <p class="text-gray-600 dark:text-slate-400">{{ $commande->notes_client }}</p>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('dashboard.boutique.commandes.updateNotes', $commande) }}">
                        @csrf
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Notes administrateur</label>
                        <textarea 
                            name="notes_admin" 
                            rows="3"
                            placeholder="Remarques internes..."
                            class="input w-full mb-3"
                        >{{ old('notes_admin', $commande->notes_admin) }}</textarea>
                        <button type="submit" class="btn-ghost btn-sm">
                            Enregistrer
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="space-y-6">
            @can('update', $commande)
            <div class="card">
                <div class="card-header">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Changer le statut</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('dashboard.boutique.commandes.updateStatut', $commande) }}" class="space-y-3">
                        @csrf
                        <select name="statut" class="input w-full">
                            <option value="nouvelle" {{ $commande->statut == 'nouvelle' ? 'selected' : '' }}>Nouvelle</option>
                            <option value="acceptee" {{ $commande->statut == 'acceptee' ? 'selected' : '' }}>Acceptée</option>
                            <option value="en_preparation" {{ $commande->statut == 'en_preparation' ? 'selected' : '' }}>En préparation</option>
                            <option value="en_livraison" {{ $commande->statut == 'en_livraison' ? 'selected' : '' }}>En livraison</option>
                            <option value="livree" {{ $commande->statut == 'livree' ? 'selected' : '' }}>Livrée</option>
                            <option value="annulee" {{ $commande->statut == 'annulee' ? 'selected' : '' }}>Annulée</option>
                            <option value="refusee" {{ $commande->statut == 'refusee' ? 'selected' : '' }}>Refusée</option>
                        </select>
                        <button type="submit" class="btn-primary w-full">
                            Mettre à jour
                        </button>
                    </form>
                </div>
            </div>
            @endcan

            @if(!$commande->payee && $commande->peutEtreMarqueePayee())
            @can('update', $commande)
            <div class="card">
                <div class="card-header">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Paiement</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('dashboard.boutique.commandes.marquerPayee', $commande) }}">
                        @csrf
                        <p class="text-sm text-gray-600 dark:text-slate-400 mb-4">Marquer cette commande comme payée ({{ number_format($commande->total, 0, ',', ' ') }} FCFA)</p>
                        <button type="submit" class="btn-success w-full">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            Marquer payée
                        </button>
                    </form>
                </div>
            </div>
            @endcan
            @elseif($commande->payee)
            <div class="card">
                <div class="card-body">
                    <div class="flex items-center gap-3 text-emerald-600 dark:text-emerald-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div>
                            <p class="font-semibold">Commande payée</p>
                            <p class="text-sm text-gray-500 dark:text-slate-400">Le {{ $commande->payee_le?->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if($commande->peutEtreAnnulee())
            @can('delete', $commande)
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('dashboard.boutique.commandes.destroy', $commande) }}" onsubmit="return confirm('Supprimer définitivement cette commande ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-danger w-full">
                            Supprimer
                        </button>
                    </form>
                </div>
            </div>
            @endcan
            @endif

            <div class="card">
                <div class="card-body">
                    <p class="text-xs text-gray-500 dark:text-slate-400 mb-1">Mode de paiement</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $commande->mode_paiement == 'livraison' ? 'À la livraison' : ucfirst($commande->mode_paiement) }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
</x-dashboard-layout>
