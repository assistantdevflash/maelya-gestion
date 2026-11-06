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

        @include('dashboard.boutique.commandes.partials.statut-badge', ['statut' => $commande->statut])
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
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-slate-400">Livraison promise</p>
                        @if($commande->date_livraison_prevue)
                        <p class="text-base font-semibold mt-1 {{ $commande->livraison_en_retard ? 'text-red-500' : 'text-gray-900 dark:text-white' }}">
                            {{ $commande->date_livraison_prevue->format('d/m/Y') }}
                            @if($commande->creneau_livraison) <span class="text-sm font-normal">· {{ $commande->creneau_livraison }}</span>@endif
                        </p>
                        @if($commande->livraison_en_retard)
                        <p class="text-[11px] font-bold text-red-500 mt-0.5">Date dépassée</p>
                        @endif
                        @else
                        <p class="text-base font-semibold text-gray-400 mt-1">Non définie</p>
                        @endif
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
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase w-20">Image</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase">Produit</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase">Qté</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase">Prix unitaire</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-700">
                            @foreach($commande->items as $item)
                                @php
                                    // Le produit peut avoir été supprimé (produit_id nullable)
                                    $urlImage = $item->produit?->photo_url;
                                @endphp
                                <tr>
                                    <td class="px-6 py-3">
                                        @if($urlImage)
                                            <img src="{{ $urlImage }}"
                                                 alt="{{ $item->nom_snapshot }}"
                                                 loading="lazy"
                                                 class="w-12 h-12 rounded-lg object-cover ring-1 ring-gray-200 dark:ring-slate-700 bg-gray-50 dark:bg-slate-800">
                                        @else
                                            <div class="w-12 h-12 rounded-lg ring-1 ring-gray-200 dark:ring-slate-700 bg-gray-50 dark:bg-slate-800 flex items-center justify-center" title="Aucune image">
                                                <svg class="w-5 h-5 text-gray-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-gray-900 dark:text-white font-medium">
                                        {{ $item->nom_snapshot }}
                                        @if(!$item->produit)
                                            <span class="block text-xs font-normal text-gray-400 dark:text-slate-500">Produit supprimé</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-center text-gray-700 dark:text-slate-300 font-semibold">{{ $item->quantite }}</td>
                                    <td class="px-6 py-3 text-right text-gray-700 dark:text-slate-300">{{ number_format($item->prix_snapshot, 0, ',', ' ') }} FCFA</td>
                                    <td class="px-6 py-3 text-right font-bold text-gray-900 dark:text-white">{{ number_format($item->sous_total, 0, ',', ' ') }} FCFA</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-slate-800">
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-right text-sm font-semibold text-gray-700 dark:text-slate-300">Sous-total</td>
                                <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">{{ number_format($commande->sous_total, 0, ',', ' ') }} FCFA</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-right text-sm font-semibold text-gray-700 dark:text-slate-300">Frais de livraison</td>
                                <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">{{ number_format($commande->frais_livraison, 0, ',', ' ') }} FCFA</td>
                            </tr>
                            <tr class="bg-gray-100 dark:bg-slate-700">
                                <td colspan="4" class="px-6 py-4 text-right text-lg font-bold text-gray-900 dark:text-white">Total</td>
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
                <div id="edit-commande-body" class="card-body {{ $errors->any() ? '' : 'hidden' }}">
                    {{-- Erreurs validation --}}
                    @if($errors->any())
                    <div class="mb-4 p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800/40 rounded-xl text-sm text-red-700 dark:text-red-300">
                        @foreach($errors->all() as $e)<p>• {{ $e }}</p>@endforeach
                    </div>
                    @endif

                    <form method="POST" action="{{ route('dashboard.boutique.commandes.update', $commande) }}" class="space-y-4">
                        @csrf

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
                        <input type="hidden" name="frais_livraison" value="{{ (int) $commande->frais_livraison }}">

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

            {{-- Paiement — carte unique (statut, montant et action au même endroit) --}}
            <div class="card">
                <div class="card-body space-y-4">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Paiement</p>

                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1.5 text-xs font-semibold rounded-lg {{ $commande->payee ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-slate-400' }}">
                            {{ $commande->payee ? 'Payée' : 'Non payée' }}
                        </span>
                        <span class="text-xs text-gray-400">{{ $commande->mode_paiement === 'cash' ? 'Cash à la livraison' : ucfirst($commande->mode_paiement ?? 'cash') }}</span>
                    </div>

                    @if($commande->payee)
                        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/30 border-2 border-emerald-200 dark:border-emerald-800/40 rounded-xl">
                            <div class="flex items-center gap-2 text-emerald-700 dark:text-emerald-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <p class="font-semibold">Commande payée</p>
                            </div>
                            @if($commande->payee_at)
                            <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">Payée le {{ $commande->payee_at->format('d/m/Y à H:i') }}</p>
                            @endif
                        </div>
                    @elseif($commande->peutEtreMarqueePayee())
                        @can('update', $commande)
                        <form method="POST" action="{{ route('dashboard.boutique.commandes.payer', $commande) }}" class="space-y-4">
                            @csrf
                            <div class="p-4 bg-emerald-50 dark:bg-emerald-950/30 border-2 border-emerald-200 dark:border-emerald-800/40 rounded-xl">
                                <p class="text-sm text-emerald-800 dark:text-emerald-200 font-medium">Montant à encaisser</p>
                                <p class="text-2xl font-bold text-emerald-900 dark:text-emerald-100 mt-1">{{ number_format($commande->total, 0, ',', ' ') }} FCFA</p>
                            </div>
                            <button type="submit" class="btn-success w-full">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Marquer comme payée
                            </button>
                        </form>
                        @endcan
                    @else
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Le paiement pourra être enregistré une fois la commande livrée.
                        </p>
                    @endif
                </div>
            </div>

            {{-- Documents --}}
            <div class="card">
                <div class="card-body space-y-3">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Documents</p>
                    <a href="{{ route('dashboard.boutique.commandes.facture', $commande) }}" target="_blank" class="btn-outline w-full">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Facture PDF
                    </a>
                    <a href="{{ route('dashboard.boutique.commandes.bon-livraison', $commande) }}" target="_blank" class="btn-outline w-full">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zm10 0a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4v11m9-1h5l3-5h-8"/></svg>
                        Bon de livraison
                    </a>

                    @php
                        $tel = preg_replace('/[^0-9]/', '', (string) $commande->client_telephone);
                        if ($tel && str_starts_with($tel, '0')) $tel = '225' . $tel;
                        $waMessage = rawurlencode(
                            "Bonjour " . $commande->client_prenom . ",

"
                            . "Votre commande " . $commande->numero . " est " . strtolower(str_replace('_', ' ', $commande->statut)) . ".\n"
                            . "Montant : " . number_format($commande->total, 0, ',', ' ') . " F CFA\n"
                            . ($commande->date_livraison_prevue ? "Livraison prévue le " . $commande->date_livraison_prevue->format('d/m/Y') . "\n" : '')
                            . "\nSuivi : " . \Illuminate\Support\Facades\URL::signedRoute('shop.suivi', ['slug' => $commande->institut->slug, 'numero' => $commande->numero])
                        );
                    @endphp
                    @if($tel)
                    <a href="https://wa.me/{{ $tel }}?text={{ $waMessage }}" target="_blank" rel="noopener"
                       class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-[#25D366]/10 text-[#25D366] border border-[#25D366]/20 hover:bg-[#25D366]/20 transition">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        Notifier par WhatsApp
                    </a>
                    @endif
                </div>
            </div>

            @if($commande->peutEtreAnnulee())
            @can('delete', $commande)
            <div class="card">
                <div class="card-body">
                    <form id="form-supprimer-commande" method="POST" action="{{ route('dashboard.boutique.commandes.destroy', $commande) }}">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="btn-danger w-full"
                                onclick="window.dispatchEvent(new CustomEvent('confirm-action',{detail:{formId:'form-supprimer-commande',title:'Supprimer cette commande',message:'La commande {{ $commande->numero }} sera définitivement supprimée. Cette action est irréversible.',confirmLabel:'Supprimer',danger:true}}))">
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

    {{-- ═══ HISTORIQUE ═══ --}}
    @php
        $etapes = [];
        $etapes[] = ['date' => $commande->created_at, 'titre' => 'Commande reçue', 'texte' => 'Commande passée sur la boutique', 'couleur' => 'bg-gray-400'];
        foreach ([
            'acceptee_at'       => ['Acceptée', 'Prise en charge par l\'établissement', 'bg-emerald-500'],
            'en_preparation_at' => ['En préparation', 'Les articles sont préparés', 'bg-amber-500'],
            'en_livraison_at'   => ['Expédiée', 'Confiée au livreur', 'bg-cyan-500'],
            'livree_at'         => ['Livrée', 'Remise au client', 'bg-emerald-600'],
        ] as $champ => $info) {
            if ($commande->{$champ}) {
                $etapes[] = ['date' => $commande->{$champ}, 'titre' => $info[0], 'texte' => $info[1], 'couleur' => $info[2]];
            }
        }
        if ($commande->payee_at) {
            $etapes[] = ['date' => $commande->payee_at, 'titre' => 'Encaissée', 'texte' => number_format($commande->total, 0, ',', ' ') . ' F — ' . ($commande->mode_paiement ?? ''), 'couleur' => 'bg-emerald-700'];
        }
        if ($commande->annulee_at) {
            $etapes[] = [
                'date'   => $commande->annulee_at,
                'titre'  => $commande->statut === 'refusee' ? 'Refusée' : 'Annulée',
                'texte'  => $commande->motif_annulation ?: ($commande->annulee_par === 'client' ? 'Annulée par le client' : 'Annulée par l\'établissement'),
                'couleur'=> 'bg-red-500',
            ];
        }
        $etapes = collect($etapes)->sortBy('date')->values();
    @endphp
    <div class="card">
        <div class="card-header"><h2 class="text-lg font-semibold text-gray-900 dark:text-white">Historique</h2></div>
        <div class="card-body">
            <ol class="relative border-l border-gray-200 dark:border-slate-700 ml-2 space-y-4">
                @foreach($etapes as $etape)
                <li class="ml-4">
                    <span class="absolute -left-[5px] w-2.5 h-2.5 rounded-full {{ $etape['couleur'] }}"></span>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $etape['titre'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-slate-400">{{ $etape['texte'] }}</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">{{ \Illuminate\Support\Carbon::parse($etape['date'])->translatedFormat('d M Y à H:i') }}</p>
                </li>
                @endforeach
            </ol>
        </div>
    </div>
</div>
</x-dashboard-layout>
