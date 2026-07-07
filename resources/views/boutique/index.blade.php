@php
    $ogUrl = url('/shop/' . $institut->slug);
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $institut->nom }} - Boutique en ligne</title>
    
    {{-- Open Graph & Social --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $ogUrl }}">
    <meta property="og:title" content="{{ $ogTitle ?? $institut->nom . ' - Boutique en ligne' }}">
    <meta property="og:description" content="{{ $ogDescription ?? 'Découvrez nos produits et commandez en ligne avec livraison à domicile' }}">
    @if($ogImage ?? null)
        <meta property="og:image" content="{{ $ogImage }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
    @endif
    <meta property="og:locale" content="fr_FR">
    <meta name="twitter:card" content="summary_large_image">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 dark:bg-slate-900 min-h-screen" x-data="boutique()" x-cloak>
    {{-- Toast notification --}}
    <div x-show="toast.show" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed top-4 right-4 z-50 max-w-sm">
        <div class="bg-emerald-500 text-white px-6 py-3 rounded-xl shadow-lg flex items-center gap-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <p class="font-medium" x-text="toast.message"></p>
        </div>
    </div>

    {{-- Header sticky --}}
    <header class="bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 sticky top-0 z-40 shadow-sm">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between gap-4">
                {{-- Logo et nom --}}
                <div class="flex items-center gap-3 min-w-0">
                    @if($institut->logo)
                        <img src="{{ asset('storage/' . $institut->logo) }}" alt="{{ $institut->nom }}" class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl object-cover flex-shrink-0">
                    @endif
                    <div class="min-w-0">
                        <h1 class="text-base sm:text-xl font-bold text-gray-900 dark:text-white truncate">{{ $institut->nom }}</h1>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-slate-400">Boutique en ligne</p>
                    </div>
                </div>
                
                {{-- Bouton panier avec badge --}}
                <button @click="panierOpen = true" class="relative inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl hover:bg-primary-700 transition-all hover:scale-105 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <span class="hidden sm:inline">Panier</span>
                    {{-- Badge compteur --}}
                    <span x-show="totalArticles > 0" 
                          x-text="totalArticles"
                          class="absolute -top-2 -right-2 min-w-[1.5rem] h-6 px-1.5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center border-2 border-white dark:border-slate-900 shadow-lg"></span>
                </button>
            </div>
        </div>
    </header>

    {{-- Contenu principal --}}
    <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
        {{-- Section filtres --}}
        <div class="mb-8 space-y-4">
            {{-- Barre de recherche --}}
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input 
                    type="text"
                    x-model="searchQuery"
                    placeholder="Rechercher un produit..."
                    class="w-full pl-11 pr-4 py-3 sm:py-3.5 bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-shadow shadow-sm"
                >
            </div>

            {{-- Filtres catégories --}}
            @if($categories->isNotEmpty())
            <div>
                <p class="text-sm font-medium text-gray-700 dark:text-slate-300 mb-3">Catégories</p>
                <div class="flex flex-wrap gap-2">
                    <button 
                        @click="selectedCategorie = null"
                        :class="selectedCategorie === null ? 'bg-primary-600 text-white ring-2 ring-primary-600 ring-offset-2' : 'bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-700'"
                        class="px-4 py-2 rounded-lg text-sm font-medium border border-gray-200 dark:border-slate-600 transition-all"
                    >
                        Tous les produits
                    </button>
                    @foreach($categories as $cat)
                    <button 
                        @click="selectedCategorie = '{{ $cat->id }}'"
                        :class="selectedCategorie === '{{ $cat->id }}' ? 'bg-primary-600 text-white ring-2 ring-primary-600 ring-offset-2' : 'bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-700'"
                        class="px-4 py-2 rounded-lg text-sm font-medium border border-gray-200 dark:border-slate-600 transition-all"
                    >
                        {{ $cat->nom }}
                    </button>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Compteur résultats --}}
            <p class="text-sm text-gray-600 dark:text-slate-400">
                <span x-text="produitsAffiches.length"></span> produit<span x-show="produitsAffiches.length > 1">s</span> trouvé<span x-show="produitsAffiches.length > 1">s</span>
            </p>
        </div>

        {{-- Grille produits --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            <template x-for="produit in produitsAffiches" :key="produit.id">
                <div class="bg-white dark:bg-slate-800 rounded-2xl overflow-hidden border border-gray-200 dark:border-slate-700 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                    {{-- Image produit --}}
                    <div class="relative aspect-square bg-gray-100 dark:bg-slate-900 overflow-hidden">
                        <template x-if="produit.photo">
                            <img :src="'/storage/' + produit.photo" :alt="produit.nom" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!produit.photo">
                            <div class="w-full h-full flex items-center justify-center">
                                <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                        </template>
                        {{-- Badge stock faible --}}
                        <div x-show="produit.stock <= 5" class="absolute top-2 right-2 bg-amber-500 text-white text-xs font-bold px-2 py-1 rounded-lg">
                            Plus que <span x-text="produit.stock"></span>
                        </div>
                    </div>
                    
                    {{-- Infos produit --}}
                    <div class="p-4 space-y-3">
                        <div class="min-h-[3rem]">
                            <h3 class="font-semibold text-gray-900 dark:text-white text-base line-clamp-2" x-text="produit.nom"></h3>
                            <p x-show="produit.categorie" class="text-xs text-gray-500 dark:text-slate-400 mt-1" x-text="produit.categorie"></p>
                        </div>
                        
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xl font-bold text-primary-600 dark:text-primary-400">
                                <span x-text="new Intl.NumberFormat('fr-FR').format(produit.prix)"></span> F
                            </p>
                            <button 
                                @click="ajouterAuPanier(produit)"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium text-sm shadow-md hover:shadow-lg transition-all hover:scale-105"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                Ajouter
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Message aucun produit --}}
        <div x-show="produitsAffiches.length === 0" class="text-center py-16">
            <div class="w-20 h-20 bg-gray-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <p class="text-gray-500 dark:text-slate-400 text-lg">Aucun produit trouvé</p>
            <button @click="searchQuery = ''; selectedCategorie = null" class="mt-4 text-primary-600 hover:text-primary-700 font-medium">
                Réinitialiser les filtres
            </button>
        </div>
    </main>

    {{-- Modal Panier --}}
    <div x-show="panierOpen" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4"
         @click="panierOpen = false">
        <div @click.stop 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] flex flex-col">
            {{-- Header --}}
            <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-slate-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Mon panier</h2>
                <button @click="panierOpen = false" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Corps --}}
            <div class="flex-1 overflow-y-auto p-5 space-y-4">
                <template x-if="panier.length === 0">
                    <div class="text-center py-12">
                        <div class="w-16 h-16 bg-gray-100 dark:bg-slate-900 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                        <p class="text-gray-500 dark:text-slate-400">Votre panier est vide</p>
                    </div>
                </template>

                <template x-for="(item, index) in panier" :key="index">
                    <div class="flex gap-4 p-4 bg-gray-50 dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-700">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 dark:text-white truncate" x-text="item.nom"></p>
                            <p class="text-sm text-gray-500 dark:text-slate-400 mt-1" x-text="new Intl.NumberFormat('fr-FR').format(item.prix) + ' F'"></p>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button @click="diminuerQuantite(index)" class="w-8 h-8 rounded-lg bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-700 flex items-center justify-center font-bold transition-colors">−</button>
                            <span class="w-10 text-center font-bold text-gray-900 dark:text-white" x-text="item.quantite"></span>
                            <button @click="augmenterQuantite(index)" class="w-8 h-8 rounded-lg bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-700 flex items-center justify-center font-bold transition-colors">+</button>
                        </div>
                        <button @click="retirerDuPanier(index)" class="flex-shrink-0 text-red-500 hover:text-red-700 p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </template>
            </div>

            {{-- Footer --}}
            <div x-show="panier.length > 0" class="p-5 border-t border-gray-200 dark:border-slate-700 space-y-4">
                <div class="space-y-2">
                    <div class="flex justify-between text-gray-600 dark:text-slate-400">
                        <span>Sous-total</span>
                        <span x-text="new Intl.NumberFormat('fr-FR').format(sousTotal) + ' F'"></span>
                    </div>
                    <div class="flex justify-between text-gray-600 dark:text-slate-400">
                        <span>Livraison</span>
                        <span>{{ number_format($institut->boutique_frais_livraison ?? 0, 0, ',', ' ') }} F</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold text-gray-900 dark:text-white pt-2 border-t border-gray-200 dark:border-slate-700">
                        <span>Total</span>
                        <span x-text="new Intl.NumberFormat('fr-FR').format(total) + ' F'"></span>
                    </div>
                </div>
                <button @click="ouvrirCommande()" class="w-full py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition-all hover:scale-105">
                    Commander
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Commande --}}
    <div x-show="commandeOpen" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 overflow-y-auto"
         @click="commandeOpen = false">
        <div @click.stop 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-2xl my-8">
            <div class="p-5 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Finaliser la commande</h2>
                <button @click="commandeOpen = false" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('shop.commander', $institut->slug) }}" class="p-6 space-y-5">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Prénom *</label>
                        <input type="text" name="prenom" required class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-slate-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Nom *</label>
                        <input type="text" name="nom" required class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-slate-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Téléphone *</label>
                    <input type="tel" name="telephone" required class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-slate-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Email (optionnel)</label>
                    <input type="email" name="email" class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-slate-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Adresse de livraison *</label>
                    <textarea name="adresse_livraison" rows="3" required class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-slate-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Mode de paiement *</label>
                    <select name="mode_paiement" required class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-slate-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <option value="livraison">Paiement à la livraison</option>
                        <option value="mobile">Mobile Money</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Notes (optionnel)</label>
                    <textarea name="notes_client" rows="2" placeholder="Précisions, instructions..." class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-slate-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"></textarea>
                </div>

                <template x-for="(item, index) in panier" :key="index">
                    <div>
                        <input type="hidden" :name="'items[' + index + '][id]'" :value="item.id">
                        <input type="hidden" :name="'items[' + index + '][quantite]'" :value="item.quantite">
                    </div>
                </template>

                <div class="bg-gradient-to-r from-primary-50 to-secondary-50 dark:from-primary-950/20 dark:to-secondary-950/20 border-2 border-primary-200 dark:border-primary-800 rounded-xl p-4">
                    <div class="flex justify-between items-center">
                        <span class="font-bold text-gray-900 dark:text-white text-lg">Total à payer</span>
                        <span class="font-bold bg-gradient-to-r from-primary-600 to-secondary-500 bg-clip-text text-transparent text-2xl" x-text="new Intl.NumberFormat('fr-FR').format(total) + ' F'"></span>
                    </div>
                </div>

                <button type="submit" class="w-full py-3.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-bold text-lg shadow-lg hover:shadow-xl transition-all hover:scale-105">
                    Confirmer la commande
                </button>
            </form>
        </div>
    </div>

    <script>
        function boutique() {
            return {
                searchQuery: '',
                selectedCategorie: null,
                panierOpen: false,
                commandeOpen: false,
                panier: JSON.parse(localStorage.getItem('panier_{{ $institut->id }}') || '[]'),
                fraisLivraison: {{ $institut->boutique_frais_livraison ?? 0 }},
                toast: { show: false, message: '' },
                produits: @json($produitsJson),

                init() {
                    // Nettoyer le panier des items invalides (prix à 0 ou manquant)
                    this.panier = this.panier.filter(item => item.prix && item.prix > 0);
                    this.sauvegarderPanier();
                },

                get produitsAffiches() {
                    return this.produits.filter(p => {
                        const matchCategorie = this.selectedCategorie === null || String(p.categorie_id) === String(this.selectedCategorie);
                        const matchSearch = this.searchQuery === '' || p.nom.toLowerCase().includes(this.searchQuery.toLowerCase());
                        return matchCategorie && matchSearch;
                    });
                },

                get totalArticles() {
                    return this.panier.reduce((sum, item) => sum + item.quantite, 0);
                },

                get sousTotal() {
                    return this.panier.reduce((sum, item) => sum + (item.prix * item.quantite), 0);
                },

                get total() {
                    return this.sousTotal + this.fraisLivraison;
                },

                ajouterAuPanier(produit) {
                    const index = this.panier.findIndex(item => item.id === produit.id);
                    if (index >= 0) {
                        this.panier[index].quantite++;
                    } else {
                        this.panier.push({ ...produit, quantite: 1 });
                    }
                    this.sauvegarderPanier();
                    this.showToast(`${produit.nom} ajouté au panier`);
                },

                augmenterQuantite(index) {
                    this.panier[index].quantite++;
                    this.sauvegarderPanier();
                },

                diminuerQuantite(index) {
                    if (this.panier[index].quantite > 1) {
                        this.panier[index].quantite--;
                        this.sauvegarderPanier();
                    } else {
                        this.retirerDuPanier(index);
                    }
                },

                retirerDuPanier(index) {
                    this.panier.splice(index, 1);
                    this.sauvegarderPanier();
                },

                sauvegarderPanier() {
                    localStorage.setItem('panier_{{ $institut->id }}', JSON.stringify(this.panier));
                },

                ouvrirCommande() {
                    this.panierOpen = false;
                    this.commandeOpen = true;
                },

                showToast(message) {
                    this.toast.message = message;
                    this.toast.show = true;
                    setTimeout(() => { this.toast.show = false; }, 3000);
                }
            }
        }
    </script>
</body>
</html>
