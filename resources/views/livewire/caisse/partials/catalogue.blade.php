    {{-- ═══ Catalogue gauche (100 % Alpine – zéro requête serveur) ═══ --}}
    <div class="{{ $simple ? 'lg:col-span-2' : 'lg:col-span-3' }} space-y-4 pb-24 lg:pb-0" wire:ignore>

        {{-- Recherche + onglets --}}
        <div class="card p-4 space-y-3">
            <div class="relative">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    type="text"
                    data-recherche
                    x-model.debounce.150ms="search"
                    placeholder="{{ $simple ? 'Scanner ou rechercher un produit…' : 'Rechercher un service ou produit...' }}"
                    class="form-input pl-10"
                    autofocus>
            </div>

            {{-- Filet de sécurité : erreur de caisse corrigeable en un clic.
                 Réservé aux admins, comme l'annulation classique. --}}
            @if(auth()->user()->isAdmin())
            <div class="flex justify-end -mt-1">
                <form method="POST"
                      action="{{ route('dashboard.ventes.annuler-derniere') }}"
                      onsubmit="return confirm('Annuler la dernière vente encaissée ? Le stock sera réintégré et les points retirés.');">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 text-[11px] font-medium text-gray-400 hover:text-red-600 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                        Annuler la dernière vente
                    </button>
                </form>
            </div>
            @endif

            @if(! $simple)
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
            @endif
        </div>

        @if(! $simple)
        {{-- Filtre catégorie (hors card pour permettre le scroll horizontal) --}}
        <div x-show="categories.length > 0"
             x-init="$nextTick(() => {
                const el = $refs.catsScroll, g = $refs.catsLeft, d = $refs.catsRight;
                if (!el || !g || !d) return;

                const maj = () => {
                    // Élément masqué (x-show) ou pas encore mesuré : ne rien
                    // toucher, sinon les deux flèches se désactivent.
                    if (!el.clientWidth || !el.scrollWidth) return;
                    g.disabled = el.scrollLeft <= 4;
                    d.disabled = el.scrollLeft + el.clientWidth >= el.scrollWidth - 4;
                };

                el.addEventListener('scroll', maj, { passive: true });
                window.addEventListener('resize', maj);

                // Le contenu change de largeur (changement d'onglet, filtre
                // catégorie, réapparition après masquage) → on recalcule.
                if (window.ResizeObserver) {
                    const ro = new ResizeObserver(() => maj());
                    ro.observe(el);
                    if (el.firstElementChild) ro.observe(el.firstElementChild);
                }

                // Nouvel onglet = nouvelle liste : on repart du début.
                $watch('categories', () => $nextTick(() => { el.scrollLeft = 0; maj(); }));

                maj();
             })"
             class="flex items-center gap-2">

            {{-- Flèche gauche — desktop uniquement (mobile : balayage tactile) --}}
            <button type="button" x-ref="catsLeft"
                    @click="$refs.catsScroll.scrollBy({ left: -240, behavior: 'smooth' })"
                    class="hidden lg:inline-flex items-center justify-center w-8 h-8 flex-shrink-0 rounded-full border border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-500 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-all duration-150 disabled:opacity-25 disabled:cursor-default disabled:hover:bg-white dark:disabled:hover:bg-slate-800"
                    aria-label="Catégories précédentes" title="Catégories précédentes">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>

            <div x-ref="catsScroll" class="relative lg:static left-0 w-screen lg:w-auto lg:flex-1 min-w-0 -ml-4 lg:ml-0 overflow-x-auto scrollbar-hide">
                <div class="flex gap-2 pb-0.5 px-4 lg:px-0 w-max">
                    <button @click="categorieId = ''"
                            :class="categorieId === '' ? 'text-white shadow-sm' : 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-600'"
                            :style="categorieId === '' ? 'background: linear-gradient(135deg, #9333ea, #ec4899);' : ''"
                            class="px-3 py-1.5 rounded-full text-xs font-semibold transition-all duration-150 whitespace-nowrap">
                        Toutes
                    </button>
                    <template x-for="cat in categories" :key="cat.id">
                        <button @click="categorieId = cat.id"
                                :class="categorieId === cat.id ? 'text-white shadow-sm' : 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-600'"
                                :style="categorieId === cat.id ? 'background: linear-gradient(135deg, #9333ea, #ec4899);' : ''"
                                class="px-3 py-1.5 rounded-full text-xs font-semibold transition-all duration-150 whitespace-nowrap"
                                x-text="cat.nom">
                        </button>
                    </template>
                </div>
            </div>

            {{-- Flèche droite — desktop uniquement --}}
            <button type="button" x-ref="catsRight"
                    @click="$refs.catsScroll.scrollBy({ left: 240, behavior: 'smooth' })"
                    class="hidden lg:inline-flex items-center justify-center w-8 h-8 flex-shrink-0 rounded-full border border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-500 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-all duration-150 disabled:opacity-25 disabled:cursor-default disabled:hover:bg-white dark:disabled:hover:bg-slate-800"
                    aria-label="Catégories suivantes" title="Catégories suivantes">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
        @endif

        <div class="card p-4 space-y-3">
            {{-- Bouton Vente rapide (disponible dans les deux modes) --}}
            <button x-show="!showVenteRapide"
                    @click="toggleVenteRapide()"
                    class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-sm font-semibold border-2 border-amber-200 dark:border-amber-700/60 bg-amber-50/50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 hover:bg-amber-100/70 dark:hover:bg-amber-900/40 transition-all duration-200">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Vente rapide
            </button>

            {{-- Bouton Scanner code-barres produit + champ scanner externe (essai + premium-plus uniquement) --}}
            @if(auth()->user()->aFonctionnalite('scan_code_barre'))
            <div x-data="scannerCodeBarre()" x-init="init()"
                 @if(! $simple) x-show="onglet === 'produits'" @endif
                 class="flex flex-col gap-1">
                <div class="flex items-center gap-2">
                    <button type="button" @click="ouvrir()"
                            class="flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-sm font-semibold border-2 border-blue-200 dark:border-blue-700/60 bg-blue-50/50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 hover:bg-blue-100/70 dark:hover:bg-blue-900/40 transition-all duration-200 flex-shrink-0">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h2v12H4zm3 0h1v12H7zm3 0h2v12h-2zm4 0h1v12h-1zm3 0h2v12h-2z"/>
                        </svg>
                        <span class="hidden sm:inline">Scanner</span>
                    </button>
                    {{-- Champ pour scanner externe (USB/Bluetooth) --}}
                    <div class="relative flex-1 min-w-0">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h2v12H4zm3 0h1v12H7zm3 0h2v12h-2zm4 0h1v12h-1zm3 0h2v12h-2z"/>
                        </svg>
                        <input x-model="saisieExterne" type="text"
                               placeholder="Scanner externe ou saisir code-barres..."
                               @keydown.enter.prevent="resoudreExterne()"
                               @focus="scanExterneFocus = true"
                               @blur="scanExterneFocus = false"
                               class="w-full pl-9 pr-3 py-2.5 text-xs rounded-xl border border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-1 focus:ring-blue-400 placeholder:text-gray-400">
                    </div>
                </div>
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
            @endif
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
                {{-- Article --}}
                <div>
                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $simple ? 'Article' : 'Article ou prestation' }} *</label>
                    <input type="text" x-model="venteRapideNom"
                           placeholder="{{ $simple ? 'Ex : Bloc-notes, Riz 5 kg...' : 'Ex : Tresse spéciale, Soin visage...' }}"
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
                @if(! $simple)
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
                @endif
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

        @if(! $simple)
        {{-- Grille items (mode complet uniquement) --}}
        <div x-show="!showVenteRapide" class="grid grid-cols-2 sm:grid-cols-3 gap-3 max-h-[55vh] overflow-y-auto pr-1">
            <template x-for="item in filteredItems" :key="item.id">
                <button
                    @click="ajouterItem(item)"
                    :disabled="enRupture(item)"
                    :class="[
                        quantiteDans(item) > 0 ? 'ring-2 ring-primary-400 bg-primary-50/30' : '',
                        enRupture(item) ? 'opacity-50 cursor-not-allowed grayscale' : 'hover:ring-1 hover:ring-primary-300 active:scale-[0.97] cursor-pointer'
                    ]"
                    :title="enRupture(item) ? 'Produit en rupture de stock' : ''"
                    class="card p-4 text-left transition-all duration-200 group min-w-0 overflow-hidden relative">
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
                        <span x-show="enRupture(item)"
                              class="px-1.5 py-0.5 rounded-full bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300 text-[9px] font-bold uppercase tracking-wide flex-shrink-0">
                            Rupture
                        </span>
                        <span x-show="!enRupture(item) && quantiteDans(item) > 0"
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
                        <p class="text-[11px] mt-0.5 font-medium"
                           :class="enRupture(item) ? 'text-rose-600 dark:text-rose-400' : 'text-gray-400'"
                           x-text="enRupture(item) ? 'Épuisé' : 'Stock : ' + (item.stock ?? '—')"></p>
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
        @endif

        @if($simple)
        {{-- ═══ MODE SIMPLIFIÉ : résultats de recherche (produits uniquement) ═══ --}}
        <div class="card overflow-hidden">
            <div class="px-4 py-2.5 border-b border-gray-100 dark:border-slate-700 flex items-center justify-between">
                <p class="text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Résultats</p>
                <span class="text-[11px] text-gray-400" x-show="resultatsRecherche.length > 0"
                      x-text="resultatsRecherche.length + ' produit' + (resultatsRecherche.length > 1 ? 's' : '')"></span>
            </div>

            {{-- Liste compacte : clic sur une ligne = ajout au panier --}}
            <div class="max-h-[52vh] overflow-y-auto divide-y divide-gray-50 dark:divide-slate-700/60">
                <template x-for="item in resultatsRecherche" :key="item.id">
                    <button type="button" @click="ajouterItem(item)"
                            class="w-full flex items-center gap-3 px-4 py-2.5 text-left hover:bg-primary-50/60 dark:hover:bg-slate-700/50 transition-colors">                        <template x-if="item.photo">
                            <img :src="item.photo" :alt="item.nom" class="w-9 h-9 rounded-lg object-cover border border-gray-200 dark:border-slate-600 flex-shrink-0">
                        </template>
                        <template x-if="!item.photo">
                            <div class="w-9 h-9 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                        </template>

                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate" x-text="item.nom"></p>
                            <p class="text-[11px] text-gray-400">Stock : <span x-text="item.stock ?? '—'"></span></p>
                        </div>

                        <span x-show="quantiteDans(item) > 0" x-text="quantiteDans(item)"
                              class="min-w-[20px] h-5 px-1 rounded-full text-[10px] font-bold text-white flex items-center justify-center flex-shrink-0"
                              style="background: linear-gradient(135deg, #9333ea, #ec4899);"></span>

                        <span class="text-sm font-bold text-gray-900 dark:text-white whitespace-nowrap flex-shrink-0"
                              x-text="formatNumber(item.prix) + ' F'"></span>

                        <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </button>
                </template>

                {{-- Aucun résultat pour la recherche en cours --}}
                <div x-show="search.trim().length > 0 && resultatsRecherche.length === 0" class="py-10 text-center">
                    <p class="text-sm text-gray-400">Aucun produit trouvé pour « <span x-text="search"></span> »</p>
                </div>

                {{-- État initial : ni grille ni catégories, on invite à scanner --}}
                <div x-show="search.trim().length === 0" class="py-10 text-center px-6">
                    <div class="w-12 h-12 bg-blue-50 dark:bg-blue-900/30 rounded-2xl flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">Scannez ou recherchez un produit</p>
                    <p class="text-xs text-gray-400 mt-1">Le catalogue n'est pas affiché dans la caisse simplifiée.</p>
                </div>
            </div>
        </div>
        @endif
    </div>