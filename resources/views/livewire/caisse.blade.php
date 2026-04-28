<div
    x-data="caisseApp({
        prestations: @js($prestations),
        produits: @js($produits),
        catPrestations: @js($catPrestations),
        catProduits: @js($catProduits),
    })"
    class="grid lg:grid-cols-5 gap-5 h-full"
>

    {{-- ═══ Catalogue gauche (100 % Alpine – zéro requête serveur) ═══ --}}
    <div class="lg:col-span-3 space-y-4" wire:ignore>

        {{-- Recherche + onglets --}}
        <div class="card p-4 space-y-3">
            <div class="relative">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    type="text"
                    x-model.debounce.150ms="search"
                    placeholder="Rechercher un service ou produit..."
                    class="form-input pl-10"
                    autofocus>
            </div>
            <div class="flex gap-1.5 p-1 bg-gray-100/80 dark:bg-slate-700/50 rounded-xl">
                <button @click="changerOnglet('prestations')"
                        :class="onglet === 'prestations' ? 'bg-white dark:bg-slate-800 text-primary-700 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                        class="flex-1 py-2.5 px-3 rounded-lg text-sm font-semibold transition-all duration-200">
                    <svg class="w-4 h-4 inline-block mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Prestations
                </button>
                <button @click="changerOnglet('produits')"
                        :class="onglet === 'produits' ? 'bg-white dark:bg-slate-800 text-primary-700 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                        class="flex-1 py-2.5 px-3 rounded-lg text-sm font-semibold transition-all duration-200">
                    <svg class="w-4 h-4 inline-block mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    Produits
                </button>
            </div>

            {{-- Filtre catégorie --}}
            <div x-show="categories.length > 0" class="flex gap-2 flex-wrap">
                <button @click="categorieId = ''"
                        :class="categorieId === '' ? 'text-white shadow-sm' : 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-600'"
                        :style="categorieId === '' ? 'background: linear-gradient(135deg, #9333ea, #ec4899);' : ''"
                        class="px-3 py-1.5 rounded-full text-xs font-semibold transition-all duration-150">
                    Toutes
                </button>
                <template x-for="cat in categories" :key="cat.id">
                    <button @click="categorieId = cat.id"
                            :class="categorieId === cat.id ? 'text-white shadow-sm' : 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-600'"
                            :style="categorieId === cat.id ? 'background: linear-gradient(135deg, #9333ea, #ec4899);' : ''"
                            class="px-3 py-1.5 rounded-full text-xs font-semibold transition-all duration-150"
                            x-text="cat.nom">
                    </button>
                </template>
            </div>
        </div>

        {{-- Grille items --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 max-h-[55vh] overflow-y-auto pr-1">
            <template x-for="item in filteredItems" :key="item.id">
                <button
                    @click="ajouterItem(item)"
                    :class="quantiteDans(item) > 0 ? 'border-2 border-primary-400 bg-primary-50/30' : 'border border-transparent'"
                    class="card p-4 text-left hover:border-primary-300 active:scale-[0.97] transition-all duration-200 group cursor-pointer">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-200 flex-shrink-0"
                             :style="'background: linear-gradient(135deg, ' + (onglet === 'prestations' ? 'rgba(147,51,234,0.1), rgba(168,85,247,0.15)' : 'rgba(16,185,129,0.1), rgba(52,211,153,0.15)') + ')'">
                            <svg :class="onglet === 'prestations' ? 'text-primary-600' : 'text-emerald-600'" class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path x-show="onglet === 'prestations'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                <path x-show="onglet === 'produits'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <span x-show="quantiteDans(item) > 0"
                              x-text="quantiteDans(item)"
                              class="min-w-[22px] h-[22px] rounded-full text-white text-xs font-bold flex items-center justify-center px-1 shadow-sm flex-shrink-0"
                              style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                        </span>
                    </div>
                    <p class="text-xs font-semibold text-gray-900 dark:text-gray-100 leading-tight truncate" x-text="item.nom"></p>
                    <p x-show="item.categorie_nom" class="text-[10px] font-medium text-primary-500/80 dark:text-primary-400/80 mt-0.5 truncate" x-text="item.categorie_nom"></p>
                    <template x-if="onglet === 'prestations' && item.duree">
                        <p class="text-[11px] text-gray-400 mt-0.5" x-text="item.duree + ' min'"></p>
                    </template>
                    <template x-if="onglet === 'produits'">
                        <p class="text-[11px] text-gray-400 mt-0.5" x-text="'Stock: ' + (item.stock ?? '')"></p>
                    </template>
                    <p class="text-sm font-bold mt-1.5" style="background: linear-gradient(135deg, #9333ea, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;" x-text="formatNumber(item.prix) + ' F'"></p>
                </button>
            </template>
            <div x-show="filteredItems.length === 0" class="col-span-3 py-14 text-center">
                <div class="w-12 h-12 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <p class="text-sm text-gray-400">Aucun résultat</p>
            </div>
        </div>
    </div>

    {{-- ═══ Panier droit ═══ --}}
    <div class="lg:col-span-2 flex flex-col gap-4">

        {{-- Client (Livewire – seule section nécessitant le serveur) --}}
        @if(auth()->user()->aFonctionnalite('caisse_client'))
        <div class="card p-4">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-lg bg-blue-50 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">Client</span>
                </div>
                @if($clientId)
                    <button wire:click="$set('clientId', null)" class="text-xs text-red-500 hover:text-red-700 font-medium">Retirer</button>
                @endif
            </div>
            @if($clientId)
                @if($this->selectedClient)
                <div class="flex items-center gap-2.5 p-2.5 bg-primary-50/50 rounded-xl">
                    <div class="w-8 h-8 bg-gradient-to-br from-primary-400 to-secondary-400 rounded-full flex items-center justify-center text-white text-xs font-bold">
                        {{ strtoupper(substr($this->selectedClient->prenom, 0, 1)) }}
                    </div>
                    <span class="text-sm font-semibold text-gray-900">{{ $this->selectedClient->nom_complet }}</span>
                </div>
                @endif
            @else
                <div x-data="{ focused: false }" @click.outside="focused = false; $wire.set('showClientList', false)">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="clientSearch"
                        @focus="focused = true; $wire.set('showClientList', true)"
                        placeholder="Chercher un client..."
                        class="form-input text-sm">
                    <div wire:loading wire:target="clientSearch" class="flex items-center gap-2 mt-2 text-xs text-gray-400">
                        <span class="spinner spinner-sm text-primary-500"></span> Recherche...
                    </div>
                    @if($this->clients->count() > 0)
                    <div class="border border-gray-200 rounded-xl overflow-hidden mt-2 shadow-sm max-h-52 overflow-y-auto">
                        @foreach($this->clients as $c)
                        <button wire:click="$set('clientId', '{{ $c->id }}')"
                                class="w-full text-left px-3 py-2.5 text-sm hover:bg-primary-50/50 flex items-center gap-2.5 border-b border-gray-100 last:border-b-0 transition-colors">
                            <div class="w-7 h-7 bg-gradient-to-br from-primary-100 to-secondary-100 rounded-full flex items-center justify-center text-primary-700 text-xs font-bold">
                                {{ strtoupper(substr($c->prenom, 0, 1)) }}
                            </div>
                            <span class="font-medium">{{ $c->nom_complet }}</span>
                            <span class="text-gray-400 text-xs ml-auto">{{ $c->telephone }}</span>
                        </button>
                        @endforeach
                    </div>
                    @elseif($showClientList && strlen($clientSearch) === 0)
                    <div wire:loading.remove wire:target="clientSearch" class="text-xs text-gray-400 mt-2 text-center py-2">
                        Aucun client enregistré
                    </div>
                    @endif
                </div>

                {{-- Bouton + Nouveau client --}}
                <button wire:click="$toggle('showNewClientForm')" class="mt-2 flex items-center gap-1.5 text-xs text-primary-600 hover:text-primary-800 font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Nouveau client
                </button>
            @endif
        </div>
        @endif

        {{-- Panier + Paiement (100 % Alpine) --}}
        <div class="card flex-1 flex flex-col overflow-hidden" wire:ignore>
            <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="relative w-7 h-7 rounded-lg bg-primary-50 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <span x-show="nbArticles > 0"
                              x-text="nbArticles"
                              class="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] rounded-full text-[10px] font-bold text-white flex items-center justify-center px-1 shadow-sm"
                              style="background: linear-gradient(135deg, #9333ea, #ec4899);"></span>
                    </div>
                    <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">Article(s)</span>
                </div>
                <button x-show="!panierVide" @click="viderPanier" class="text-xs text-red-500 hover:text-red-700 font-medium">Vider</button>
            </div>

            <div class="flex-1 overflow-y-auto divide-y divide-gray-50 max-h-52">
                <template x-for="key in panierKeys" :key="key">
                    <div class="px-4 py-3 flex items-center gap-3 hover:bg-gray-50/50 transition-colors">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 truncate" x-text="panier[key].nom"></p>
                            <p class="text-xs text-gray-400" x-text="formatNumber(panier[key].prix) + ' F × ' + panier[key].quantite"></p>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button @click="decrementer(key)"
                                    class="w-7 h-7 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-600 text-sm font-bold transition-colors">−</button>
                            <span class="w-7 text-center text-sm font-bold text-gray-900" x-text="panier[key].quantite"></span>
                            <button @click="incrementer(key)"
                                    class="w-7 h-7 rounded-lg bg-primary-100 hover:bg-primary-200 flex items-center justify-center text-primary-700 text-sm font-bold transition-colors">+</button>
                        </div>
                        <span class="text-sm font-bold text-gray-900 w-20 text-right" x-text="formatNumber(panier[key].prix * panier[key].quantite) + ' F'"></span>
                        <button @click="supprimerItem(key)" class="text-gray-300 hover:text-red-500 transition-colors ml-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </template>
                <div x-show="panierVide" class="py-14 text-center">
                    <div class="w-14 h-14 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-3">
                        <svg class="w-7 h-7 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <p class="text-sm text-gray-400">Panier vide</p>
                    <p class="text-xs text-gray-300 mt-0.5">Cliquez sur un article pour l'ajouter</p>
                </div>
            </div>

            {{-- Total + Paiement + Encaisser --}}
            <div x-show="!panierVide" class="border-t border-gray-100 p-4 space-y-3 bg-gray-50/30">
                {{-- Total brut --}}
                <div class="flex justify-between items-center">
                    <span class="font-bold text-gray-900" x-text="remise > 0 ? 'Sous-total' : 'Total'"></span>
                    <span class="text-xl font-display font-extrabold" style="background: linear-gradient(135deg, #9333ea, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;" x-text="formatNumber(totalBrut) + ' F'"></span>
                </div>

                {{-- Code promo --}}
                @if(auth()->user()->aFonctionnalite('caisse_code_promo'))
                <template x-if="codePromo">
                    <div>
                        <div class="flex items-center justify-between p-2.5 rounded-xl" style="background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2);">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                                <span class="text-xs font-bold text-emerald-700 font-mono" x-text="codePromo.code"></span>
                                <span class="text-xs text-emerald-600" x-text="'-' + formatNumber(codePromo.remise) + ' F'"></span>
                            </div>
                            <button @click="retirerCode" class="text-emerald-500 hover:text-red-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <div class="flex justify-between items-center pt-2">
                            <span class="font-bold text-gray-900">Total</span>
                            <span class="text-xl font-display font-extrabold" style="background: linear-gradient(135deg, #9333ea, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;" x-text="formatNumber(total) + ' F'"></span>
                        </div>
                    </div>
                </template>

                <template x-if="!codePromo">
                    <div>
                        <div class="flex gap-2">
                            <input type="text" x-model="codePromoInput"
                                   placeholder="Code promo"
                                   class="form-input flex-1 text-sm font-mono uppercase tracking-widest"
                                   @input="$el.value = $el.value.toUpperCase()"
                                   @keydown.enter="appliquerCode">
                            <button @click="appliquerCode" :disabled="codePromoLoading" class="btn btn-outline text-sm px-3 flex-shrink-0">
                                <span x-show="codePromoLoading" class="spinner spinner-sm" aria-hidden="true"></span>
                                <span x-show="!codePromoLoading">Appliquer</span>
                            </button>
                        </div>
                        <p x-show="codePromoErreur" x-text="codePromoErreur" class="text-xs text-red-500 mt-1"></p>
                    </div>
                </template>
                @endif

                {{-- 4 modes de paiement (2×2) --}}
                <div class="grid grid-cols-2 gap-2">
                    <button @click="modePaiement = 'cash'"
                            :class="modePaiement === 'cash' ? 'border-primary-500 bg-primary-50 text-primary-700 shadow-sm' : 'border-gray-200 text-gray-500 hover:border-gray-300'"
                            class="py-2 px-2 rounded-xl text-xs font-semibold border-2 transition-all duration-200 text-center">
                        <svg class="w-4 h-4 mx-auto mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Espèces
                    </button>
                    <button @click="modePaiement = 'carte'"
                            :class="modePaiement === 'carte' ? 'border-blue-500 bg-blue-50 text-blue-700 shadow-sm' : 'border-gray-200 text-gray-500 hover:border-gray-300'"
                            class="py-2 px-2 rounded-xl text-xs font-semibold border-2 transition-all duration-200 text-center">
                        <svg class="w-4 h-4 mx-auto mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                        Carte
                    </button>
                    <button @click="modePaiement = 'mobile_money'"
                            :class="modePaiement === 'mobile_money' ? 'border-orange-500 bg-orange-50 text-orange-700 shadow-sm' : 'border-gray-200 text-gray-500 hover:border-gray-300'"
                            class="py-2 px-2 rounded-xl text-xs font-semibold border-2 transition-all duration-200 text-center">
                        <svg class="w-4 h-4 mx-auto mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        Mobile
                    </button>
                    <button @click="modePaiement = 'mixte'"
                            :class="modePaiement === 'mixte' ? 'border-violet-500 bg-violet-50 text-violet-700 shadow-sm' : 'border-gray-200 text-gray-500 hover:border-gray-300'"
                            class="py-2 px-2 rounded-xl text-xs font-semibold border-2 transition-all duration-200 text-center">
                        <svg class="w-4 h-4 mx-auto mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                        Mixte
                    </button>
                </div>

                {{-- Référence Mobile Money --}}
                <template x-if="modePaiement === 'mobile_money'">
                    <input type="text" x-model="referencePaiement"
                           placeholder="Référence transaction (optionnel)"
                           class="form-input text-sm">
                </template>

                {{-- Paiement Mixte --}}
                <template x-if="modePaiement === 'mixte'">
                    <div class="space-y-2">
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">💵 Espèces</label>
                                <input type="number" x-model.number="montantMixteCash"
                                       class="form-input text-sm mt-1" placeholder="0" min="0" :max="total">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">💳 Carte</label>
                                <input type="number" x-model.number="montantMixteCartes"
                                       class="form-input text-sm mt-1" placeholder="0" min="0" :max="total">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">📱 Mobile</label>
                                <input type="number" x-model.number="montantMixteMobile"
                                       class="form-input text-sm mt-1" placeholder="0" min="0" :max="total">
                            </div>
                        </div>
                        <div :class="resteMixte === 0 ? 'bg-green-50' : (resteMixte < 0 ? 'bg-red-50' : 'bg-amber-50')"
                             class="flex justify-between items-center p-2.5 rounded-xl">
                            <span class="text-xs font-semibold"
                                  :class="resteMixte === 0 ? 'text-green-700' : (resteMixte < 0 ? 'text-red-700' : 'text-amber-700')"
                                  x-text="resteMixte === 0 ? '✓ Montants OK' : (resteMixte < 0 ? 'Dépassement' : 'Reste à ventiler')"></span>
                            <span class="text-sm font-bold"
                                  :class="resteMixte === 0 ? 'text-green-700' : (resteMixte < 0 ? 'text-red-700' : 'text-amber-700')"
                                  x-text="resteMixte !== 0 ? formatNumber(Math.abs(resteMixte)) + ' F' : ''"></span>
                        </div>
                        <input type="text" x-model="referencePaiement"
                               placeholder="Référence mobile / carte (optionnel)"
                               class="form-input text-sm">
                    </div>
                </template>

                {{-- Montant remis (Espèces) --}}
                <template x-if="modePaiement === 'cash'">
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Montant remis</label>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="suggestion in montantsSuggeres" :key="suggestion">
                                <button @click="montantRemis = suggestion"
                                        :class="montantRemis === suggestion ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                                        class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all duration-200"
                                        x-text="formatNumber(suggestion) + ' F'">
                                </button>
                            </template>
                        </div>
                        <input type="number" x-model.number="montantRemis"
                               placeholder="Autre montant..."
                               class="form-input text-sm"
                               :min="total">
                        <div x-show="montantRemis && monnaie > 0" class="flex justify-between items-center p-2.5 bg-green-50 rounded-xl">
                            <span class="text-xs font-semibold text-green-700">Monnaie à rendre</span>
                            <span class="text-sm font-bold text-green-700" x-text="formatNumber(monnaie) + ' F'"></span>
                        </div>
                    </div>
                </template>

                {{-- Bouton Encaisser --}}
                <button @click="ouvrirConfirmation"
                        :disabled="!mixtePret || loading"
                        :class="(!mixtePret || loading) ? 'opacity-50 cursor-not-allowed' : 'hover:shadow-xl active:scale-[0.98]'"
                        class="w-full justify-center py-3.5 text-base font-bold rounded-xl text-white shadow-lg transition-all duration-200 flex items-center gap-2"
                        style="background: linear-gradient(135deg, #9333ea 0%, #ec4899 100%);">
                    <span x-show="loading" class="spinner" aria-hidden="true"></span>
                    <svg x-show="!loading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span x-text="loading ? 'Traitement...' : 'Encaisser ' + formatNumber(total) + ' F'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ═══ Modal de confirmation (Alpine) ═══ --}}
    <div x-show="showConfirmation" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background: rgba(0,0,0,0.5);"
         wire:ignore>
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" @click.outside="fermerConfirmation()">
            {{-- Header --}}
            <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, rgba(147,51,234,0.1), rgba(236,72,153,0.1));">
                        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Confirmer la vente</h3>
                        <p class="text-xs text-gray-400">Vérifiez les détails avant d'encaisser</p>
                    </div>
                </div>
                <button @click="fermerConfirmation" class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Articles --}}
            <div class="p-5 space-y-3">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider" x-text="'Articles (' + nbArticles + ')'"></p>
                <div class="divide-y divide-gray-50 border border-gray-100 rounded-xl overflow-hidden">
                    <template x-for="key in panierKeys" :key="'modal_' + key">
                        <div class="px-4 py-3 flex items-center gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900 truncate" x-text="panier[key].nom"></p>
                                <p class="text-xs text-gray-400" x-text="formatNumber(panier[key].prix) + ' F × ' + panier[key].quantite"></p>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <button @click="decrementer(key)"
                                        class="w-6 h-6 rounded-md bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-600 text-xs font-bold transition-colors">−</button>
                                <span class="w-6 text-center text-sm font-bold text-gray-900" x-text="panier[key].quantite"></span>
                                <button @click="incrementer(key)"
                                        class="w-6 h-6 rounded-md bg-primary-100 hover:bg-primary-200 flex items-center justify-center text-primary-700 text-xs font-bold transition-colors">+</button>
                            </div>
                            <span class="text-sm font-bold text-gray-900 w-20 text-right" x-text="formatNumber(panier[key].prix * panier[key].quantite) + ' F'"></span>
                            <button @click="supprimerItem(key)" class="text-gray-300 hover:text-red-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>

                {{-- Résumé --}}
                <div class="bg-gray-50 rounded-xl p-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Sous-total</span>
                        <span class="font-semibold text-gray-700" x-text="formatNumber(totalBrut) + ' F'"></span>
                    </div>
                    <template x-if="remise > 0">
                        <div class="flex justify-between text-sm">
                            <span class="text-emerald-600 font-medium" x-text="'Code ' + (codePromo?.code ?? '')"></span>
                            <span class="font-semibold text-emerald-600" x-text="'-' + formatNumber(remise) + ' F'"></span>
                        </div>
                    </template>
                    <div class="flex justify-between text-base pt-2 border-t border-gray-200">
                        <span class="font-bold text-gray-900">Total</span>
                        <span class="font-extrabold" style="background: linear-gradient(135deg, #9333ea, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;" x-text="formatNumber(total) + ' F'"></span>
                    </div>
                </div>

                {{-- Mode de paiement affiché --}}
                <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-xl">
                    <span class="text-xs font-semibold text-gray-500">Paiement :</span>
                    <template x-if="modePaiement === 'cash'">
                        <span class="inline-flex items-center gap-1 text-xs font-bold text-primary-700 bg-primary-50 px-2.5 py-1 rounded-lg">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            Espèces
                        </span>
                    </template>
                    <template x-if="modePaiement === 'carte'">
                        <span class="inline-flex items-center gap-1 text-xs font-bold text-blue-700 bg-blue-50 px-2.5 py-1 rounded-lg">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            Carte bancaire
                        </span>
                    </template>
                    <template x-if="modePaiement === 'mobile_money'">
                        <span class="inline-flex items-center gap-1 text-xs font-bold text-orange-700 bg-orange-50 px-2.5 py-1 rounded-lg">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            Mobile Money
                        </span>
                    </template>
                    <template x-if="modePaiement === 'mixte'">
                        <span class="inline-flex items-center gap-1 text-xs font-bold text-violet-700 bg-violet-50 px-2.5 py-1 rounded-lg">
                            💵+📱 Mixte
                            <template x-if="montantMixteCash > 0">
                                <span class="text-violet-500" x-text="formatNumber(montantMixteCash) + ' F espèces'"></span>
                            </template>
                        </span>
                    </template>
                </div>

                {{-- Montant remis dans modal (cash) --}}
                <template x-if="modePaiement === 'cash'">
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Montant remis par le client</label>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="suggestion in montantsSuggeres" :key="'modal_s_' + suggestion">
                                <button @click="montantRemis = suggestion"
                                        :class="montantRemis === suggestion ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                                        class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all duration-200"
                                        x-text="formatNumber(suggestion) + ' F'">
                                </button>
                            </template>
                        </div>
                        <input type="number" x-model.number="montantRemis"
                               placeholder="Autre montant..."
                               class="form-input text-sm"
                               :min="total">
                        <div x-show="montantRemis && monnaie > 0" class="flex justify-between items-center p-2.5 bg-green-50 rounded-xl">
                            <span class="text-xs font-semibold text-green-700">Monnaie à rendre</span>
                            <span class="text-sm font-bold text-green-700" x-text="formatNumber(monnaie) + ' F'"></span>
                        </div>
                    </div>
                </template>

                {{-- Détail paiement mixte dans modal --}}
                <template x-if="modePaiement === 'mixte'">
                    <div class="bg-violet-50 rounded-xl p-3 space-y-2">
                        <p class="text-xs font-bold text-violet-700 uppercase tracking-wider">Paiement Mixte</p>
                        <template x-if="montantMixteCash > 0">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">💵 Espèces</span>
                                <span class="font-bold text-gray-900" x-text="formatNumber(montantMixteCash) + ' F'"></span>
                            </div>
                        </template>
                        <template x-if="montantMixteCartes > 0">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">💳 Carte</span>
                                <span class="font-bold text-gray-900" x-text="formatNumber(montantMixteCartes) + ' F'"></span>
                            </div>
                        </template>
                        <template x-if="montantMixteMobile > 0">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">📱 Mobile</span>
                                <span class="font-bold text-gray-900" x-text="formatNumber(montantMixteMobile) + ' F'"></span>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            {{-- Actions --}}
            <div class="p-5 border-t border-gray-100 flex flex-col gap-2">
                <div class="grid grid-cols-2 gap-2">
                    <button @click="fermerConfirmation" class="btn-secondary justify-center py-3">
                        Annuler
                    </button>
                    <button @click="valider(false)"
                            :disabled="loading"
                            :class="loading ? 'opacity-70 cursor-not-allowed' : ''"
                            class="btn-primary justify-center py-3">
                        <span x-show="loading" class="spinner spinner-sm" aria-hidden="true"></span>
                        <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Encaisser
                    </button>
                </div>
                <button @click="valider(true)"
                        :disabled="loading"
                        :class="loading ? 'opacity-70 cursor-not-allowed' : 'hover:shadow-lg active:scale-[0.98]'"
                        @class([
                            'w-full justify-center py-3 text-sm font-bold rounded-xl text-white flex items-center gap-2 transition-all duration-200',
                            'hidden' => !auth()->user()->aFonctionnalite('caisse_impression'),
                        ])
                        style="background: linear-gradient(135deg, #9333ea 0%, #ec4899 100%);">
                    <span x-show="loading" class="spinner spinner-sm" aria-hidden="true"></span>
                    <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    </svg>
                    Encaisser & Imprimer
                </button>
            </div>
        </div>
    </div>

    {{-- ═══ MODAL NOUVEAU CLIENT ═══ --}}
    @if($showNewClientForm)
    <div class="modal-backdrop" x-data x-init="document.body.classList.add('overflow-hidden')"
         x-on:remove="document.body.classList.remove('overflow-hidden')"
         @keydown.escape.window="$wire.set('showNewClientForm', false); document.body.classList.remove('overflow-hidden')"
         @click.self="$wire.set('showNewClientForm', false); document.body.classList.remove('overflow-hidden')">
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
                <button wire:click="$set('showNewClientForm', false)" class="btn-icon"
                        @click="document.body.classList.remove('overflow-hidden')">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                @if($errors->any())
                <div class="mb-4 p-3 bg-red-50 rounded-xl text-sm text-red-600 space-y-0.5">
                    @foreach($errors->all() as $e)<p>• {{ $e }}</p>@endforeach
                </div>
                @endif
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="form-group mb-0">
                            <label class="form-label">Prénom *</label>
                            <input type="text" wire:model="newClientPrenom" maxlength="50" class="form-input" placeholder="Fatou">
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Nom *</label>
                            <input type="text" wire:model="newClientNom" maxlength="50" class="form-input" placeholder="Traoré">
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Téléphone *</label>
                            <input type="text" wire:model="newClientTelephone" maxlength="30" class="form-input" placeholder="+225 07 00 00 00">
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Email</label>
                            <input type="email" wire:model="newClientEmail" maxlength="255" class="form-input" placeholder="fatou@exemple.ci">
                        </div>
                        <div class="col-span-2 form-group mb-0">
                            <label class="form-label">Anniversaire (jour et mois)</label>
                            <div class="grid grid-cols-2 gap-2">
                                <select wire:model="newClientNaissanceMois" class="form-input">
                                    <option value="">Mois</option>
                                    <option value="01">Janvier</option>
                                    <option value="02">Février</option>
                                    <option value="03">Mars</option>
                                    <option value="04">Avril</option>
                                    <option value="05">Mai</option>
                                    <option value="06">Juin</option>
                                    <option value="07">Juillet</option>
                                    <option value="08">Août</option>
                                    <option value="09">Septembre</option>
                                    <option value="10">Octobre</option>
                                    <option value="11">Novembre</option>
                                    <option value="12">Décembre</option>
                                </select>
                                <select wire:model="newClientNaissanceJour" class="form-input">
                                    <option value="">Jour</option>
                                    @for($d = 1; $d <= 31; $d++)
                                    <option value="{{ str_pad($d, 2, '0', STR_PAD_LEFT) }}">{{ $d }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <div class="col-span-2 form-group mb-0">
                            <label class="form-label">Notes</label>
                            <textarea wire:model="newClientNotes" rows="2" maxlength="1000" class="form-input resize-none"
                                      placeholder="Allergies, préférences..."></textarea>
                        </div>
                    </div>
                    <div class="flex gap-3 pt-1">
                        <button type="button" wire:click="$set('showNewClientForm', false)" class="btn btn-outline flex-1 justify-center"
                                @click="document.body.classList.remove('overflow-hidden')">Annuler</button>
                        <button type="button" wire:click="ajouterClientRapide" class="btn-primary flex-1 justify-center">
                            <span wire:loading.remove wire:target="ajouterClientRapide">Enregistrer</span>
                            <span wire:loading wire:target="ajouterClientRapide" class="flex items-center gap-2">
                                <span class="spinner spinner-sm"></span> Ajout...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
