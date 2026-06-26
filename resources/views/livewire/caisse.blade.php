<div
    x-data="caisseApp({
        prestations: @js($prestations),
        produits: @js($produits),
        catPrestations: @js($catPrestations),
        catProduits: @js($catProduits),
        allCatPrestations: @js($allCatPrestations),
        allCatProduits: @js($allCatProduits),
        prefilledItems: @js($this->prefilledItems),
        prefilledPanier: @js($this->prefilledPanier),
        routeBrouillonStore: @js(route('dashboard.caisse.brouillons.store')),
    })"
    class="grid lg:grid-cols-5 gap-5 h-full"
>
    {{-- Succès vente crédit --}}
    @if($creditSuccess)
    <div class="lg:col-span-5 flex items-center gap-3 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-sm text-emerald-700">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ $creditSuccess }}
        <a href="{{ route('dashboard.credits.index') }}" class="ml-auto text-xs font-semibold text-emerald-600 hover:underline">Voir les crédits →</a>
    </div>
    @endif

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

            {{-- Bouton Vente rapide --}}
            <button x-show="!showVenteRapide"
                    @click="toggleVenteRapide()"
                    class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-sm font-semibold border-2 border-amber-200 dark:border-amber-700/60 bg-amber-50/50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 hover:bg-amber-100/70 dark:hover:bg-amber-900/40 transition-all duration-200">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Vente rapide
            </button>

            {{-- Bouton Scanner code-barres produit --}}
            <div x-data="scannerCodeBarre()" x-init="init()" x-show="onglet === 'produits'" class="flex flex-col gap-1">
                <button type="button" @click="ouvrir()"
                        class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-sm font-semibold border-2 border-blue-200 dark:border-blue-700/60 bg-blue-50/50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 hover:bg-blue-100/70 dark:hover:bg-blue-900/40 transition-all duration-200">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h2v12H4zm3 0h1v12H7zm3 0h2v12h-2zm4 0h1v12h-1zm3 0h2v12h-2z"/>
                    </svg>
                    Scanner code-barres
                </button>
                <p x-show="statut" x-text="statut" class="text-xs text-center"
                   :class="erreur ? 'text-red-600' : 'text-emerald-600'"></p>

                {{-- Modal --}}
                <div x-show="modalOuvert" x-cloak
                     class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4"
                     @keydown.escape.window="fermer()">
                    <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-sm w-full p-5 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-gray-800 dark:text-slate-100">Scanner un produit</h3>
                            <button @click="fermer()" class="text-gray-400 hover:text-gray-600">✕</button>
                        </div>
                        <template x-if="hasCamera">
                            <div>
                                <video x-ref="video" autoplay playsinline class="w-full rounded-lg bg-black"></video>
                                <p class="text-xs text-gray-500 mt-2 text-center">Placez le code-barres face à la caméra.</p>
                            </div>
                        </template>
                        <template x-if="!hasCamera">
                            <p class="text-sm text-amber-600">Caméra indisponible sur ce navigateur.</p>
                        </template>
                        <div class="space-y-2">
                            <label class="text-xs text-gray-500 dark:text-slate-400">Ou saisissez le code manuellement</label>
                            <div class="flex gap-2">
                                <input x-model="saisie" type="text" placeholder="EAN-13 ou code interne"
                                       @keydown.enter.prevent="resoudre(saisie)"
                                       class="form-input flex-1" autofocus>
                                <button @click="resoudre(saisie)" class="btn-primary px-4">OK</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Formulaire vente rapide --}}
        <div x-show="showVenteRapide"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-1"
             class="card p-4 sm:p-5 border-2 border-amber-200/80 dark:border-amber-700/50">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-9 h-9 rounded-xl bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Vente rapide</h3>
                    <p class="text-xs text-gray-400">Article hors catalogue — non enregistré dans votre liste</p>
                </div>
            </div>
            <div class="space-y-3">
                {{-- Article ou prestation --}}
                <div>
                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Article ou prestation *</label>
                    <input type="text" x-model="venteRapideNom"
                           placeholder="Ex : Tresse spéciale, Soin visage..."
                           class="form-input mt-1.5"
                           maxlength="150"
                           @keydown.enter="ajouterVenteRapide()">
                </div>
                {{-- Prix --}}
                <div>
                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Prix (F) *</label>
                    <input type="number" x-model.number="venteRapidePrix"
                           placeholder="0"
                           class="form-input mt-1.5"
                           min="1"
                           inputmode="numeric"
                           @keydown.enter="ajouterVenteRapide()">
                </div>
                {{-- Type (optionnel) --}}
                <div>
                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type (optionnel)</label>
                    <div class="flex gap-2 mt-1.5">
                        <button type="button"
                                @click="setVenteRapideType('prestation')"
                                :class="venteRapideType === 'prestation' ? 'bg-primary-50 dark:bg-primary-900/30 border-primary-400 text-primary-700 dark:text-primary-300' : 'border-gray-200 dark:border-slate-600 text-gray-500 dark:text-gray-400 hover:border-gray-300'"
                                class="flex-1 py-2.5 rounded-xl text-xs font-semibold border-2 transition-all duration-200 text-center">
                            <svg class="w-4 h-4 inline-block mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Prestation
                        </button>
                        <button type="button"
                                @click="setVenteRapideType('produit')"
                                :class="venteRapideType === 'produit' ? 'bg-emerald-50 dark:bg-emerald-900/30 border-emerald-400 text-emerald-700 dark:text-emerald-300' : 'border-gray-200 dark:border-slate-600 text-gray-500 dark:text-gray-400 hover:border-gray-300'"
                                class="flex-1 py-2.5 rounded-xl text-xs font-semibold border-2 transition-all duration-200 text-center">
                            <svg class="w-4 h-4 inline-block mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            Produit
                        </button>
                    </div>
                </div>
                {{-- Catégorie (optionnel - affiché si un type est choisi) --}}
                <div x-show="venteRapideCategories.length > 0" x-transition>
                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Catégorie (optionnel)</label>
                    <div class="flex gap-2 flex-wrap mt-1.5">
                        <button type="button"
                                @click="setVenteRapideCategorie('')"
                                :class="venteRapideCategorieId === '' ? 'text-white shadow-sm' : 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-600'"
                                :style="venteRapideCategorieId === '' ? 'background: linear-gradient(135deg, #9333ea, #ec4899);' : ''"
                                class="px-3 py-1.5 rounded-full text-xs font-semibold transition-all duration-150">
                            Aucune
                        </button>
                        <template x-for="cat in venteRapideCategories" :key="cat.id">
                            <button type="button"
                                    @click="setVenteRapideCategorie(cat.id)"
                                    :class="venteRapideCategorieId === cat.id ? 'text-white shadow-sm' : 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-600'"
                                    :style="venteRapideCategorieId === cat.id ? 'background: linear-gradient(135deg, #9333ea, #ec4899);' : ''"
                                    class="px-3 py-1.5 rounded-full text-xs font-semibold transition-all duration-150"
                                    x-text="cat.nom">
                            </button>
                        </template>
                    </div>
                </div>
                {{-- Erreur --}}
                <p x-show="venteRapideErreur" x-text="venteRapideErreur" class="text-xs text-red-500 !mt-1"></p>
                {{-- Actions : Annuler | Ajouter --}}
                <div class="flex gap-2 pt-1">
                    <button @click="toggleVenteRapide()"
                            class="flex-1 py-3 rounded-xl text-sm font-bold border-2 border-gray-200 dark:border-slate-600 text-gray-500 dark:text-gray-400 hover:border-gray-300 dark:hover:border-slate-500 hover:text-gray-700 dark:hover:text-gray-200 transition-all duration-200 flex items-center justify-center gap-2">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span class="hidden sm:inline">Annuler</span>
                    </button>
                    <button @click="ajouterVenteRapide()"
                            class="flex-[2] py-3 rounded-xl text-sm font-bold text-white transition-all duration-200 hover:shadow-lg active:scale-[0.98] flex items-center justify-center gap-2"
                            style="background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Ajouter au panier
                    </button>
                </div>
            </div>
        </div>

        {{-- Grille items --}}
        <div x-show="!showVenteRapide" class="grid grid-cols-2 sm:grid-cols-3 gap-3 max-h-[55vh] overflow-y-auto pr-1">
            <template x-for="item in filteredItems" :key="item.id">
                <button
                    @click="ajouterItem(item)"
                    :class="quantiteDans(item) > 0 ? 'ring-2 ring-primary-400 bg-primary-50/30' : ''"
                    class="card p-4 text-left hover:ring-1 hover:ring-primary-300 active:scale-[0.97] transition-all duration-200 group cursor-pointer min-w-0 overflow-hidden">
                    <div class="flex items-start justify-between mb-3">
                        {{-- Icône ou photo --}}
                        <template x-if="onglet === 'prestations'">
                            <div class="w-9 h-9 rounded-xl bg-primary-100/40 dark:bg-primary-400/25 flex items-center justify-center group-hover:scale-110 transition-transform duration-200 flex-shrink-0">
                                <svg class="w-[18px] h-[18px] text-primary-600 dark:text-primary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </template>
                        <template x-if="onglet === 'produits'">
                            <div>
                                <img x-show="item.photo" :src="item.photo" :alt="item.nom"
                                     class="w-9 h-9 rounded-xl object-cover border border-gray-200 dark:border-slate-600 group-hover:scale-110 transition-transform duration-200 flex-shrink-0">
                                <div x-show="!item.photo"
                                     class="w-9 h-9 rounded-xl bg-emerald-100/40 dark:bg-emerald-400/25 flex items-center justify-center group-hover:scale-110 transition-transform duration-200 flex-shrink-0">
                                    <svg class="w-[18px] h-[18px] text-emerald-600 dark:text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                            </div>
                        </template>
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
                <div x-data="{
                        clients: {{ $allClients->toJson() }},
                        search: '',
                        open: false,
                        get filtered() {
                            if (this.search.length < 2) return this.clients.slice(0, 8);
                            const q = this.search.toLowerCase();
                            return this.clients.filter(c => c.search.includes(q)).slice(0, 8);
                        },
                        choose(id) {
                            this.open = false;
                            this.search = '';
                            $wire.selectClient(id);
                        }
                    }" @click.outside="open = false">
                    <input
                        type="text"
                        x-model="search"
                        @focus="open = true"
                        @input="open = true"
                        @keydown.escape="open = false"
                        placeholder="Chercher un client..."
                        class="form-input text-sm">
                    <div x-show="open && filtered.length > 0" x-cloak
                         class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden mt-2 shadow-sm max-h-52 overflow-y-auto bg-white dark:bg-gray-800">
                        <template x-for="c in filtered" :key="c.id">
                            <button type="button"
                                    @mousedown.prevent
                                    @click="choose(c.id)"
                                    @touchend.prevent="choose(c.id)"
                                    class="w-full text-left px-3 py-2.5 text-sm hover:bg-primary-50/50 dark:hover:bg-gray-700 flex items-center gap-2.5 border-b border-gray-100 dark:border-gray-700 last:border-b-0 transition-colors">
                                <div class="w-7 h-7 bg-gradient-to-br from-primary-100 to-secondary-100 rounded-full flex items-center justify-center text-primary-700 text-xs font-bold"
                                     x-text="c.initiale"></div>
                                <span class="font-medium text-gray-900 dark:text-white" x-text="c.nom"></span>
                                <span class="text-gray-400 text-xs ml-auto" x-text="c.telephone"></span>
                            </button>
                        </template>
                    </div>
                    <div x-show="open && search.length >= 2 && filtered.length === 0" x-cloak
                         class="text-xs text-gray-400 mt-2 text-center py-2">
                        Aucun client trouvé
                    </div>
                </div>

                {{-- Bouton + Nouveau client --}}
                <button @click="newClientOpen = true" class="mt-2 flex items-center gap-1.5 text-xs text-primary-600 hover:text-primary-800 font-medium transition-colors">
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

                {{-- Modes de paiement (5 modes) --}}
                <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                    <button @click="modePaiement = 'cash'"
                            :class="modePaiement === 'cash' ? 'border-primary-500 bg-primary-50 text-primary-700 shadow-sm' : 'border-gray-200 text-gray-500 hover:border-gray-300'"
                            class="py-2 px-1 rounded-xl text-[11px] font-semibold border-2 transition-all duration-200 text-center">
                        💵 Espèces
                    </button>
                    <button @click="modePaiement = 'carte'"
                            :class="modePaiement === 'carte' ? 'border-blue-500 bg-blue-50 text-blue-700 shadow-sm' : 'border-gray-200 text-gray-500 hover:border-gray-300'"
                            class="py-2 px-1 rounded-xl text-[11px] font-semibold border-2 transition-all duration-200 text-center">
                        💳 Carte
                    </button>
                    <button @click="modePaiement = 'mobile_money'"
                            :class="modePaiement === 'mobile_money' ? 'border-orange-500 bg-orange-50 text-orange-700 shadow-sm' : 'border-gray-200 text-gray-500 hover:border-gray-300'"
                            class="py-2 px-1 rounded-xl text-[11px] font-semibold border-2 transition-all duration-200 text-center">
                        📱 Mobile
                    </button>
                    <button @click="modePaiement = 'mixte'"
                            :class="modePaiement === 'mixte' ? 'border-violet-500 bg-violet-50 text-violet-700 shadow-sm' : 'border-gray-200 text-gray-500 hover:border-gray-300'"
                            class="py-2 px-1 rounded-xl text-[11px] font-semibold border-2 transition-all duration-200 text-center">
                        🔀 Mixte
                    </button>
                    <button @click="modePaiement = 'credit'"
                            :class="modePaiement === 'credit' ? 'border-emerald-500 bg-emerald-50 text-emerald-700 shadow-sm' : 'border-gray-200 text-gray-500 hover:border-gray-300'"
                            class="py-2 px-1 rounded-xl text-[11px] font-semibold border-2 transition-all duration-200 text-center">
                        🕐 Crédit
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

                {{-- Panneau Crédit --}}
                <template x-if="modePaiement === 'credit'">
                    <div class="space-y-3 p-4 bg-emerald-50/50 dark:bg-emerald-900/20 rounded-xl border border-emerald-200 dark:border-emerald-800/50">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Client</span>
                            <span x-show="$wire.clientId" class="text-sm font-bold text-emerald-700 dark:text-emerald-400" x-text="$wire.selectedClientNom || ''"></span>
                            <span x-show="!$wire.clientId" class="text-xs text-red-500 dark:text-red-400 font-medium">⚠️ Client obligatoire</span>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400">Apport initial (optionnel)</label>
                            <input type="number" x-model.number="creditApport" min="0" :max="total"
                                   class="form-input text-sm mt-1" placeholder="0">
                        </div>
                        <div class="bg-white dark:bg-slate-800 rounded-lg p-3 text-sm space-y-1">
                            <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-300">Total vente</span><strong class="text-gray-900 dark:text-white" x-text="formatNumber(total) + ' FCFA'"></strong></div>
                            <div class="flex justify-between text-emerald-600 dark:text-emerald-400"><span>Apport</span><strong x-text="formatNumber(parseInt(creditApport)||0) + ' FCFA'"></strong></div>
                            <div class="flex justify-between text-red-600 dark:text-red-400 font-bold pt-1 border-t dark:border-slate-700"><span>Reste à payer</span><strong x-text="formatNumber(Math.max(0, total-(parseInt(creditApport)||0))) + ' FCFA'"></strong></div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs text-gray-500">Échéances</label>
                                <select x-model.number="creditNbEcheances" class="form-input text-sm mt-1">
                                    <option value="2">2</option><option value="3">3</option><option value="4">4</option>
                                    <option value="6">6</option><option value="12">12</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">Fréquence</label>
                                <select x-model="creditFrequence" class="form-input text-sm mt-1">
                                    <option value="mensuelle">Mensuelle</option>
                                    <option value="hebdomadaire">Hebdomadaire</option>
                                </select>
                            </div>
                        </div>
                        {{-- Infos client crédit --}}
                        <div x-show="$wire.clientId" class="space-y-2 bg-gray-50 dark:bg-slate-700/50 rounded-lg p-2.5 text-xs">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-400">Tél :</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $this->selectedClientTel ?? '—' }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-400 flex-shrink-0">Adresse :</span>
                                <input type="text" wire:model.blur="selectedClientAdresse" wire:change="updateClientInfosCredit"
                                       class="flex-1 bg-transparent border-0 border-b border-gray-300 dark:border-slate-600 px-1 py-0.5 text-xs focus:border-emerald-500 focus:ring-0 dark:text-white"
                                       placeholder="Adresse du client">
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-400 flex-shrink-0">Pièce ID :</span>
                                <input type="text" wire:model.blur="selectedClientPieceId" wire:change="updateClientInfosCredit"
                                       class="flex-1 bg-transparent border-0 border-b border-gray-300 dark:border-slate-600 px-1 py-0.5 text-xs focus:border-emerald-500 focus:ring-0 dark:text-white"
                                       placeholder="N° CNI, Passeport...">
                            </div>
                        </div>
                        <button @click="validerVenteCredit()"
                                :disabled="!$wire.clientId || (total-(parseInt(creditApport)||0)) <= 0"
                                class="w-full py-2.5 rounded-xl text-white text-sm font-bold bg-gradient-to-r from-emerald-500 to-teal-600 disabled:opacity-50">
                            Enregistrer la vente à crédit
                        </button>
                    </div>
                </template>

                {{-- Modal confirmation crédit --}}
                <template x-if="showCreditConfirmation">
                    <div class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="background: rgba(0,0,0,0.5);"
                         @click.self="showCreditConfirmation = false">
                        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100">
                            {{-- Header --}}
                            <div class="p-5 border-b border-gray-100 dark:border-slate-700 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Confirmer la vente à crédit</h3>
                                </div>
                                <button @click="showCreditConfirmation = false" class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                            {{-- Body --}}
                            <div class="p-5 space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Total vente</span>
                                    <strong class="text-gray-900 dark:text-white" x-text="formatNumber(total) + ' FCFA'"></strong>
                                </div>
                                <div class="flex justify-between text-emerald-600 dark:text-emerald-400">
                                    <span>Apport initial</span>
                                    <strong x-text="formatNumber(parseInt(creditApport)||0) + ' FCFA'"></strong>
                                </div>
                                <div class="flex justify-between text-red-600 dark:text-red-400 font-bold pt-2 border-t dark:border-slate-700">
                                    <span>Reste à payer</span>
                                    <strong x-text="formatNumber(Math.max(0, total-(parseInt(creditApport)||0))) + ' FCFA'"></strong>
                                </div>
                                <div class="flex justify-between text-gray-500 dark:text-gray-400">
                                    <span>Échéancier</span>
                                    <span x-text="creditNbEcheances + ' × ' + (creditFrequence === 'mensuelle' ? 'mois' : 'semaines')"></span>
                                </div>
                                <div x-show="$wire.clientId" class="bg-gray-50 dark:bg-slate-700/50 rounded-lg p-2.5 flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Client</span>
                                    <span class="font-semibold text-gray-900 dark:text-white" x-text="$wire.selectedClientNom || ''"></span>
                                </div>
                            </div>
                            {{-- Footer --}}
                            <div class="px-5 pb-5 flex gap-2">
                                <button @click="showCreditConfirmation = false" class="flex-1 btn-outline justify-center text-sm py-2.5">Annuler</button>
                                <button @click="confirmerVenteCredit()" class="flex-1 py-2.5 text-sm font-bold rounded-xl text-white bg-gradient-to-r from-emerald-500 to-teal-600 hover:brightness-110">
                                    Confirmer
                                </button>
                            </div>
                        </div>
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

                {{-- Bouton Encaisser (masqué en mode crédit) --}}
                <button @click="ouvrirConfirmation"
                        :disabled="!mixtePret || loading"
                        :class="(!mixtePret || loading) ? 'opacity-50 cursor-not-allowed' : 'hover:shadow-xl active:scale-[0.98]'"
                        x-show="modePaiement !== 'credit'"
                        class="w-full justify-center py-3.5 text-base font-bold rounded-xl text-white shadow-lg transition-all duration-200 flex items-center gap-2"
                        style="background: linear-gradient(135deg, #9333ea 0%, #ec4899 100%);">
                    <span x-show="loading" class="spinner" aria-hidden="true"></span>
                    <svg x-show="!loading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span x-text="loading ? 'Traitement...' : 'Encaisser ' + formatNumber(total) + ' F'"></span>
                </button>

                {{-- Mettre en attente / Liens (masqués en mode crédit) --}}
                <div class="flex items-center justify-between gap-2 mt-2" x-show="modePaiement !== 'credit'">
                    <button type="button"
                            @click="mettreEnAttente()"
                            :disabled="panierKeys.length === 0 || enAttenteLoading"
                            :class="(panierKeys.length === 0 || enAttenteLoading) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-amber-100 dark:hover:bg-amber-900/30'"
                            class="flex-1 justify-center py-2 text-xs font-semibold rounded-lg border border-amber-300 dark:border-amber-700 text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/20 transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span x-text="enAttenteLoading ? 'Enregistrement...' : 'Mettre en attente'"></span>
                    </button>
                    <a href="{{ route('dashboard.caisse.brouillons.index') }}"
                       class="px-3 py-2 text-xs font-semibold rounded-lg text-gray-600 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-700 transition">
                        Brouillons
                    </a>
                    <a href="{{ route('dashboard.credits.index') }}"
                       class="px-3 py-2 text-xs font-semibold rounded-lg text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition">
                        Crédits
                    </a>
                </div>
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
                <div class="bg-gray-50 dark:bg-slate-800/50 rounded-xl p-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500 dark:text-slate-400">Sous-total</span>
                        <span class="font-semibold text-gray-700 dark:text-slate-200" x-text="formatNumber(totalBrut) + ' F'"></span>
                    </div>
                    <template x-if="remise > 0">
                        <div class="flex justify-between text-sm">
                            <span class="text-emerald-600 font-medium" x-text="'Code ' + (codePromo?.code ?? '')"></span>
                            <span class="font-semibold text-emerald-600" x-text="'-' + formatNumber(remise) + ' F'"></span>
                        </div>
                    </template>
                    <div class="flex justify-between text-base pt-2 border-t border-gray-200 dark:border-slate-700">
                        <span class="font-bold text-gray-900 dark:text-slate-100">Total</span>
                        <span class="font-extrabold" style="background: linear-gradient(135deg, #9333ea, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;" x-text="formatNumber(total) + ' F'"></span>
                    </div>
                    <template x-if="pourboire > 0">
                        <div class="flex justify-between text-sm pt-1">
                            <span class="text-amber-600 dark:text-amber-400 font-medium">💰 Pourboire</span>
                            <span class="font-semibold text-amber-600 dark:text-amber-400" x-text="'+' + formatNumber(pourboire) + ' F'"></span>
                        </div>
                    </template>
                    <template x-if="pourboire > 0">
                        <div class="flex justify-between text-base pt-2 border-t border-gray-200 dark:border-slate-700">
                            <span class="font-bold text-gray-900 dark:text-slate-100">À encaisser</span>
                            <span class="font-extrabold text-primary-700 dark:text-primary-300" x-text="formatNumber(total + pourboire) + ' F'"></span>
                        </div>
                    </template>
                </div>

                {{-- Pourboire (optionnel) --}}
                <div class="space-y-2">
                    <label class="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1">
                        💰 Pourboire (optionnel)
                    </label>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="montant in [0, 500, 1000, 2000, 5000]" :key="'tip_' + montant">
                            <button type="button" @click="pourboire = montant"
                                    :class="pourboire === montant ? 'bg-amber-500 text-white shadow-sm' : 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 hover:bg-gray-200 dark:hover:bg-slate-600'"
                                    class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all"
                                    x-text="montant === 0 ? 'Aucun' : formatNumber(montant) + ' F'"></button>
                        </template>
                    </div>
                    <input type="number" x-model.number="pourboire" min="0" step="100"
                           placeholder="Autre montant..."
                           class="form-input text-sm">
                </div>

                {{-- Mode de paiement affiché --}}
                <div class="flex items-center gap-2 p-3 bg-gray-50 dark:bg-slate-700/40 rounded-xl">
                    <span class="text-xs font-semibold text-gray-500 dark:text-slate-400">Paiement :</span>
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
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold text-violet-700 bg-violet-50 px-2.5 py-1 rounded-lg">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            Mixte
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
                    <div class="bg-violet-50 dark:bg-violet-900/30 rounded-xl p-3 space-y-2">
                        <p class="text-xs font-bold text-violet-700 dark:text-violet-300 uppercase tracking-wider">Paiement Mixte</p>
                        <div x-show="montantMixteCash > 0" class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-slate-400">💵 Espèces</span>
                            <span class="font-bold text-gray-900 dark:text-slate-100" x-text="formatNumber(montantMixteCash) + ' F'"></span>
                        </div>
                        <div x-show="montantMixteCartes > 0" class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-slate-400">💳 Carte</span>
                            <span class="font-bold text-gray-900 dark:text-slate-100" x-text="formatNumber(montantMixteCartes) + ' F'"></span>
                        </div>
                        <div x-show="montantMixteMobile > 0" class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-slate-400">📱 Mobile</span>
                            <span class="font-bold text-gray-900 dark:text-slate-100" x-text="formatNumber(montantMixteMobile) + ' F'"></span>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Actions (masquées en mode crédit — le panneau crédit a son propre bouton) --}}
            <div class="p-5 border-t border-gray-100 flex flex-col gap-2" x-show="modePaiement !== 'credit'">
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
    <div x-show="newClientOpen" x-cloak class="modal-backdrop"
         x-on:keydown.escape.window="newClientOpen = false; document.body.classList.remove('overflow-hidden')"
         x-init="$watch('newClientOpen', v => document.body.classList.toggle('overflow-hidden', v))"
         @click.self="newClientOpen = false; document.body.classList.remove('overflow-hidden')">
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
                <button @click="newClientOpen = false; document.body.classList.remove('overflow-hidden')" class="btn-icon">
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
                        {{-- Informations supplémentaires (collapsible) --}}
                        <div class="col-span-2" x-data="{ showExtraCaisse: false }">
                            <button type="button" @click="showExtraCaisse = !showExtraCaisse"
                                    class="flex items-center gap-2 text-xs font-medium text-gray-500 hover:text-gray-700 transition-colors">
                                <svg class="w-3.5 h-3.5 transition-transform" :class="showExtraCaisse ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                                Informations supplémentaires
                            </button>
                            <div x-show="showExtraCaisse" x-collapse class="mt-3 space-y-3">
                                <div class="form-group mb-0">
                                    <label class="form-label">Adresse</label>
                                    <input type="text" wire:model="newClientAdresse" maxlength="255" class="form-input" placeholder="Abidjan, Cocody...">
                                </div>
                                <div class="form-group mb-0">
                                    <label class="form-label">Pièce d'identité</label>
                                    <input type="text" wire:model="newClientPieceIdentite" maxlength="100" class="form-input" placeholder="N° CNI, Passeport...">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-3 pt-1">
                        <button type="button" @click="newClientOpen = false; document.body.classList.remove('overflow-hidden')" class="btn btn-outline flex-1 justify-center">Annuler</button>
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
</div>

<script>
function scannerCodeBarre() {
    return {
        modalOuvert: false,
        hasCamera: false,
        saisie: '',
        statut: '',
        erreur: false,
        stream: null,
        detector: null,
        intervalScan: null,
        init() {
            // Vérification lazy — évaluée à l'ouverture
        },
        async ouvrir() {
            this.modalOuvert = true;
            this.statut = '';
            this.erreur = false;
            this.saisie = '';
            this.hasCamera = 'BarcodeDetector' in window && !!navigator.mediaDevices?.getUserMedia;
            if (!this.hasCamera) return;
            try {
                this.detector = new BarcodeDetector({ formats: ['ean_13','ean_8','code_128','code_39','upc_a','upc_e','qr_code'] });
                this.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                await this.$nextTick();
                this.$refs.video.srcObject = this.stream;
                this.intervalScan = setInterval(() => this.scanner(), 500);
            } catch (e) {
                this.hasCamera = false;
            }
        },
        async scanner() {
            if (!this.$refs.video || this.$refs.video.readyState < 2) return;
            try {
                const codes = await this.detector.detect(this.$refs.video);
                if (codes.length) this.resoudre(codes[0].rawValue);
            } catch (_) {}
        },
        fermer() {
            this.modalOuvert = false;
            if (this.intervalScan) { clearInterval(this.intervalScan); this.intervalScan = null; }
            if (this.stream) { this.stream.getTracks().forEach(t => t.stop()); this.stream = null; }
        },
        async resoudre(code) {
            code = (code || '').trim();
            if (!code) return;
            this.statut = 'Recherche…';
            this.erreur = false;
            try {
                const url = "{{ route('dashboard.produits.scan') }}?code=" + encodeURIComponent(code);
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) {
                    this.statut = 'Produit introuvable : ' + code;
                    this.erreur = true;
                    this.fermer();
                    return;
                }
                const data = await res.json();
                if (data.found) {
                    // Ajoute au panier Alpine du composant parent caisseApp
                    this.$dispatch('scanner-produit', { id: data.id, nom: data.nom, prix: data.prix });
                    this.statut = data.nom + ' ajouté';
                    this.erreur = false;
                    this.fermer();
                } else {
                    this.statut = 'Produit introuvable.';
                    this.erreur = true;
                    this.fermer();
                }
            } catch (e) {
                this.statut = 'Erreur : ' + e.message;
                this.erreur = true;
                this.fermer();
            }
        }
    };
}
</script>
