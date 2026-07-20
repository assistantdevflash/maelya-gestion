<x-dashboard-layout>
<div class="space-y-8">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div class="flex-1">
            <div class="flex items-center gap-3 mb-3">
                <a href="{{ route('dashboard.boutique.commandes.index') }}" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <h1 class="text-3xl font-display font-bold text-gray-900 dark:text-white tracking-tight">{{ $commande->numero }}</h1>
            </div>
            <p class="text-gray-500 dark:text-slate-400 ml-14">Créée le {{ $commande->created_at->format('d/m/Y à H:i') }}</p>
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
        <span class="px-4 py-2 text-sm font-bold rounded-xl shadow-sm {{ $colors[$commande->statut] ?? 'bg-gray-100 text-gray-800' }}">
            {{ ucfirst(str_replace('_', ' ', $commande->statut)) }}
        </span>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 dark:bg-emerald-950/40 border-2 border-emerald-200 dark:border-emerald-800/40 rounded-2xl p-5 flex items-start gap-4">
            <div class="w-10 h-10 bg-emerald-500 dark:bg-emerald-600 rounded-xl flex items-center justify-center text-white flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <p class="text-emerald-800 dark:text-emerald-200 font-medium pt-1.5">{{ session('success') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Informations client --}}
        <div class="lg:col-span-2 space-y-8">
            <div class="card">
                <div class="card-header">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Informations client</h2>
                </div>
                <div class="card-body">
                    <div class="space-y-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-slate-400">Nom complet</p>
                        <p class="text-base font-semibold text-gray-900 dark:text-white mt-1">{{ $commande->client_prenom }} {{ $commande->client_nom }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-slate-400">Téléphone</p>
                        <p class="text-base font-semibold text-gray-900 dark:text-white mt-1">{{ $commande->client_telephone }}</p>
                    </div>
                    @if($commande->client_email)
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-slate-400">Email</p>
                        <p class="text-base font-semibold text-gray-900 dark:text-white mt-1">{{ $commande->client_email }}</p>
                    </div>
                    @endif
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-slate-400">Adresse de livraison</p>
                        <p class="text-base font-semibold text-gray-900 dark:text-white mt-1">{{ $commande->client_adresse }}</p>
                    </div>
                    {{-- Zone de livraison --}}
                    @php
                        $zones = is_array($commande->institut->boutique_zones_livraison) ? $commande->institut->boutique_zones_livraison : [];
                        $zoneTrouvee = null;
                        foreach ($zones as $z) {
                            if (isset($z['frais']) && (int)$z['frais'] === (int)$commande->frais_livraison) { $zoneTrouvee = $z; break; }
                        }
                    @endphp
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-slate-400">Zone de livraison</p>
                        <p class="text-base font-semibold text-gray-900 dark:text-white mt-1">
                            @if($zoneTrouvee)
                                {{ $zoneTrouvee['nom'] }} · {{ number_format($zoneTrouvee['frais'], 0, ',', ' ') }} FCFA
                                @if(!empty($zoneTrouvee['delai'])) · {{ $zoneTrouvee['delai'] }} @endif
                            @elseif($commande->frais_livraison > 0)
                                Frais : {{ number_format($commande->frais_livraison, 0, ',', ' ') }} FCFA
                            @else
                                Non spécifiée
                            @endif
                        </p>
                    </div>
                    </div>
                </div>
            </div>

            {{-- Produits --}}
            <div class="card">
                <div class="card-header">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Produits commandés</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-800">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase">Produit</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase">Qté</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase">Prix unitaire</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-700">
                            @foreach($commande->items as $item)
                                <tr>
                                    <td class="px-6 py-4 text-gray-900 dark:text-white font-medium">{{ $item->nom_snapshot }}</td>
                                    <td class="px-6 py-4 text-center text-gray-700 dark:text-slate-300 font-semibold">{{ $item->quantite }}</td>
                                    <td class="px-6 py-4 text-right text-gray-700 dark:text-slate-300">{{ number_format($item->prix_snapshot, 0, ',', ' ') }} FCFA</td>
                                    <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">{{ number_format($item->sous_total, 0, ',', ' ') }} FCFA</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-slate-800">
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-right text-sm font-semibold text-gray-700 dark:text-slate-300">Sous-total</td>
                                <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">{{ number_format($commande->sous_total, 0, ',', ' ') }} FCFA</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-right text-sm font-semibold text-gray-700 dark:text-slate-300">Frais de livraison</td>
                                <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">{{ number_format($commande->frais_livraison, 0, ',', ' ') }} FCFA</td>
                            </tr>
                            <tr class="bg-gray-100 dark:bg-slate-700">
                                <td colspan="3" class="px-6 py-4 text-right text-lg font-bold text-gray-900 dark:text-white">Total</td>
                                <td class="px-6 py-4 text-right text-xl font-bold text-primary-600 dark:text-primary-400">{{ number_format($commande->total, 0, ',', ' ') }} FCFA</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Notes --}}
            <div class="card">
                <div class="card-header">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Notes et remarques</h2>
                </div>
                <div class="card-body space-y-5">
                    @if($commande->notes_client)
                        <div class="p-4 bg-blue-50 dark:bg-blue-950/30 border-2 border-blue-200 dark:border-blue-800/40 rounded-xl">
                            <p class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-2 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Notes du client
                            </p>
                            <p class="text-gray-800 dark:text-slate-200">{{ $commande->notes_client }}</p>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('dashboard.boutique.commandes.notes', $commande) }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-3">Notes administrateur</label>
                            <textarea
                                name="notes_admin"
                                rows="4"
                                placeholder="Remarques internes..."
                                class="input w-full"
                            >{{ old('notes_admin', $commande->notes_admin) }}</textarea>
                        </div>
                        <button type="submit" class="btn-primary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Enregistrer
                        </button>
                    </form>
                </div>
            </div>

            {{-- ✏️ Modifier la commande (avant acceptation) --}}
            @if($commande->statut === 'nouvelle')
            <div class="card border-2 border-amber-200 dark:border-amber-700" id="edit-commande-card">
                <div class="card-header flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">✏️ Ajuster la commande</h2>
                    <button type="button" onclick="document.getElementById('edit-commande-body').classList.toggle('hidden')"
                            class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                        Modifier
                    </button>
                </div>
                <div id="edit-commande-body" class="card-body hidden">
                    <form method="POST" action="{{ route('dashboard.boutique.commandes.update', $commande) }}" class="space-y-4">
                        @csrf @method('PUT')

                        {{-- Zone de livraison --}}
                        @php $zones = is_array($commande->institut->boutique_zones_livraison) ? $commande->institut->boutique_zones_livraison : []; @endphp
                        @if(count($zones) > 0)
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-2">Zone de livraison</label>
                            <select name="zone_index" class="form-input" onchange="this.form.querySelector('[name=frais_livraison]').value = this.options[this.selectedIndex].dataset.frais || {{ $commande->frais_livraison }}">
                                <option value="">Frais par défaut ({{ number_format($commande->institut->boutique_frais_livraison ?? 0, 0, ',', ' ') }} F)</option>
                                @foreach($zones as $i => $z)
                                <option value="{{ $i }}" data-frais="{{ $z['frais'] ?? 0 }}"
                                    {{ isset($zoneTrouvee) && (int)($z['frais']??0) === (int)$commande->frais_livraison ? 'selected' : '' }}>
                                    {{ $z['nom'] }} — {{ number_format($z['frais'] ?? 0, 0, ',', ' ') }} F{{ !empty($z['delai']) ? ' (' . $z['delai'] . ')' : '' }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <input type="hidden" name="frais_livraison" value="{{ $commande->frais_livraison }}">

                        {{-- Articles --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-2">Articles</label>
                            <div class="space-y-2">
                                @foreach($commande->items as $i => $item)
                                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-slate-800 rounded-xl">
                                    <input type="hidden" name="items[{{ $i }}][id]" value="{{ $item->id }}">
                                    <span class="flex-1 text-sm font-medium text-gray-900 dark:text-white">{{ $item->nom_snapshot }}</span>
                                    <input type="number" name="items[{{ $i }}][quantite]" value="{{ $item->quantite }}" min="0" max="999"
                                           class="form-input w-20 text-center text-sm">
                                    <span class="text-xs text-gray-500 w-24 text-right">{{ number_format($item->prix_snapshot, 0, ',', ' ') }} F</span>
                                    <label class="flex items-center gap-1 text-xs text-red-500 cursor-pointer">
                                        <input type="checkbox" name="items[{{ $i }}][supprimer]" value="1"> Retirer
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <button type="submit" class="btn-primary w-full">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Mettre à jour la commande
                        </button>
                    </form>
                </div>
            </div>
            @endif
        </div>

        {{-- Actions --}}
        <div class="space-y-6">
            @can('update', $commande)
            @php
                $actionsDisponibles = [
                    'nouvelle' => [
                        ['statut' => 'acceptee', 'label' => 'Accepter', 'class' => 'btn-success', 'icon' => 'M5 13l4 4L19 7'],
                        ['statut' => 'refusee', 'label' => 'Refuser', 'class' => 'btn-danger', 'icon' => 'M6 18L18 6M6 6l12 12'],
                    ],
                    'acceptee' => [
                        ['statut' => 'en_preparation', 'label' => 'Mettre en préparation', 'class' => 'btn-primary', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
                    ],
                    'en_preparation' => [
                        ['statut' => 'en_livraison', 'label' => 'Mettre en livraison', 'class' => 'btn-primary', 'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z'],
                    ],
                    'en_livraison' => [
                        ['statut' => 'livree', 'label' => 'Marquer livrée', 'class' => 'btn-success', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ],
                ];
                $actions = $actionsDisponibles[$commande->statut] ?? [];
            @endphp
            @if(!empty($actions))
            <div class="card">
                <div class="card-header">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Actions</h2>
                </div>
                <div class="card-body space-y-3">
                    @foreach($actions as $action)
                    <form method="POST" action="{{ route('dashboard.boutique.commandes.statut', $commande) }}">
                        @csrf
                        <input type="hidden" name="statut" value="{{ $action['statut'] }}">
                        <button type="submit" class="{{ $action['class'] }} w-full">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $action['icon'] }}"/></svg>
                            {{ $action['label'] }}
                        </button>
                    </form>
                    @endforeach
                </div>
            </div>
            @endif
            @endcan

            <div class="card">
                <div class="card-body space-y-3">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Paiement</p>
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1.5 text-xs font-semibold rounded-lg {{ $commande->payee ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-slate-400' }}">
                            {{ $commande->payee ? 'Payée' : 'Non payée' }}
                        </span>
                        <span class="text-xs text-gray-400">{{ $commande->mode_paiement === 'cash' ? 'Cash à la livraison' : ucfirst($commande->mode_paiement ?? 'cash') }}</span>
                    </div>
                    @if($commande->statut === 'livree' && !$commande->payee)
                    @can('update', $commande)
                    <form method="POST" action="{{ route('dashboard.boutique.commandes.payer', $commande) }}">
                        @csrf
                        <button type="submit" class="btn-success w-full">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Marquer payée
                        </button>
                    </form>
                    @endcan
                    @endif
                </div>
            </div>

            {{-- Facture --}}
            <div class="card">
                <div class="card-body space-y-3">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Télécharger la facture</p>
                    <a href="{{ route('dashboard.boutique.commandes.facture', $commande) }}" target="_blank" class="btn-outline w-full">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Facture PDF
                    </a>
                </div>
            </div>

            @if(!$commande->payee && $commande->peutEtreMarqueePayee())
            @can('update', $commande)
            <div class="card">
                <div class="card-header">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Paiement</h2>
                </div>
                <div class="card-body space-y-4">
                    <form method="POST" action="{{ route('dashboard.boutique.commandes.payer', $commande) }}">
                        @csrf
                        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/30 border-2 border-emerald-200 dark:border-emerald-800/40 rounded-xl mb-4">
                            <p class="text-sm text-emerald-800 dark:text-emerald-200 font-medium">Montant à encaisser</p>
                            <p class="text-2xl font-bold text-emerald-900 dark:text-emerald-100 mt-1">{{ number_format($commande->total, 0, ',', ' ') }} FCFA</p>
                        </div>
                        <button type="submit" class="btn-success w-full btn-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Marquer comme payée
                        </button>
                    </form>
                </div>
            </div>
            @endcan
            @elseif($commande->payee)
            <div class="card">
                <div class="card-body">
                    <div class="p-5 bg-emerald-50 dark:bg-emerald-950/30 border-2 border-emerald-200 dark:border-emerald-800/40 rounded-xl">
                        <div class="flex items-center gap-3 text-emerald-700 dark:text-emerald-300 mb-2">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="font-bold text-lg">Commande payée</p>
                        </div>
                        <p class="text-sm text-emerald-600 dark:text-emerald-400">Le {{ $commande->payee_le?->format('d/m/Y à H:i') }}</p>
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
                    <p class="text-xs font-medium text-gray-500 dark:text-slate-400 mb-2">Mode de paiement</p>
                    <p class="text-base font-bold text-gray-900 dark:text-white">{{ $commande->mode_paiement == 'livraison' ? '📦 À la livraison' : '💳 ' . ucfirst($commande->mode_paiement) }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
</x-dashboard-layout>
