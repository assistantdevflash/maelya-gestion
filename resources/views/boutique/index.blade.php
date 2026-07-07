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
    <meta name="twitter:url" content="{{ $ogUrl }}">
    <meta name="twitter:title" content="{{ $ogTitle ?? $institut->nom }}">
    <meta name="twitter:description" content="{{ $ogDescription ?? 'Commandez en ligne' }}">
    @if($ogImage ?? null)
        <meta name="twitter:image" content="{{ $ogImage }}">
    @endif
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 dark:bg-slate-900" x-data="boutique()">
    {{-- Toast notification --}}
    <div x-show="toast.show" x-transition class="fixed top-4 right-4 z-50 max-w-sm">
        <div class="card p-4 bg-emerald-50 dark:bg-emerald-950/90 border-emerald-200 dark:border-emerald-800 flex items-start gap-3 shadow-lg">
            <div class="w-8 h-8 bg-emerald-100 dark:bg-emerald-900/40 rounded-lg flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <p class="text-emerald-700 dark:text-emerald-300 text-sm font-medium" x-text="toast.message"></p>
        </div>
    </div>

    {{-- Header --}}
    <header class="bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 sticky top-0 z-40 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    @if($institut->logo)
                        <img src="{{ asset('storage/' . $institut->logo) }}" alt="{{ $institut->nom }}" class="w-12 h-12 rounded-xl object-cover">
                    @endif
                    <div>
                        <h1 class="text-xl font-display font-bold text-gray-900 dark:text-white">{{ $institut->nom }}</h1>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Boutique en ligne</p>
                    </div>
                </div>
                <button @click="panierOpen = !panierOpen" class="relative btn-primary btn-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Panier
                    <span x-show="panier.length > 0" x-text="panier.length" class="absolute -top-2 -right-2 w-5 h-5 bg-secondary-500 text-white text-xs font-bold rounded-full flex items-center justify-center"></span>
                </button>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Filtres --}}
        <div class="mb-6 space-y-4">
            {{-- Barre de recherche --}}
            <div class="relative">
                <input 
                    type="text"
                    x-model="searchQuery"
                    placeholder="Rechercher un produit..."
                    class="w-full px-4 py-3 pl-11 bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                >
                <svg class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            {{-- Filtres catégories --}}
            @if($categories->isNotEmpty())
            <div class="flex flex-wrap gap-2">
                <button 
                    @click="selectedCategorie = null"
                    :class="selectedCategorie === null ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-300 border-primary-300 dark:border-primary-700' : 'bg-white text-gray-700 dark:bg-slate-800 dark:text-slate-300 border-gray-300 dark:border-slate-600 hover:bg-gray-50 dark:hover:bg-slate-700'"
                    class="px-4 py-2 rounded-lg text-sm font-medium border transition-colors"
                >
                    Tous les produits
                </button>
                @foreach($categories as $cat)
                <button 
                    @click="selectedCategorie = {{ $cat->id }}"
                    :class="selectedCategorie === {{ $cat->id }} ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-300 border-primary-300 dark:border-primary-700' : 'bg-white text-gray-700 dark:bg-slate-800 dark:text-slate-300 border-gray-300 dark:border-slate-600 hover:bg-gray-50 dark:hover:bg-slate-700'"
                    class="px-4 py-2 rounded-lg text-sm font-medium border transition-colors"
                >
                    {{ $cat->nom }}
                </button>
                @endforeach
            </div>
            @endif

            {{-- Compteur de résultats --}}
            <p class="text-sm text-gray-600 dark:text-slate-400">
                <span x-text="produitsAffichés.length"></span> produit<span x-show="produitsAffichés.length > 1">s</span>
            </p>
        </div>

        {{-- Grille de produits --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <template x-for="produit in produitsAffichés" :key="produit.id">
                <div class="card hover:shadow-lg transition-shadow">
                    <div class="aspect-square bg-gray-100 dark:bg-slate-800 rounded-t-2xl overflow-hidden" x-show="produit.photo">
                        <img :src="produit.photo ? '/storage/' + produit.photo : ''" :alt="produit.nom" class="w-full h-full object-cover">
                    </div>
                    <div class="card-body space-y-3">
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white text-lg line-clamp-2" x-text="produit.nom"></h3>
                            <p class="text-sm text-gray-500 dark:text-slate-400 mt-1" x-show="produit.categorie" x-text="produit.categorie"></p>
                        </div>
                        <div class="flex items-center justify-between">
                            <p class="text-xl font-bold text-primary-600 dark:text-primary-400">
                                <span x-text="new Intl.NumberFormat('fr-FR').format(produit.prix)"></span> FCFA
                            </p>
                            <button 
                                @click="ajouterAuPanier(produit)"
                                class="btn-primary btn-sm"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                Ajouter
                            </button>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-slate-400" x-show="produit.stock <= 5">
                            <span class="text-amber-600 dark:text-amber-400">⚠️ Plus que <span x-text="produit.stock"></span> en stock</span>
                        </p>
                    </div>
                </div>
            </template>
        </div>

        {{-- Message si aucun résultat --}}
        <div x-show="produitsAffichés.length === 0" class="text-center py-12">
            <div class="w-16 h-16 bg-gray-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <p class="text-gray-500 dark:text-slate-400">Aucun produit trouvé</p>
        </div>
    </div>

    {{-- Modal Panier --}}
    <div x-show="panierOpen" x-transition.opacity class="fixed inset-0 bg-black/50 z-50" @click="panierOpen = false">
        <div @click.stop class="absolute right-0 top-0 h-full w-full sm:w-96 bg-white dark:bg-slate-800 shadow-2xl overflow-y-auto">
            <div class="sticky top-0 bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 px-6 py-4 flex items-center justify-between">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Mon panier</h2>
                <button @click="panierOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-6 space-y-4">
                <template x-if="panier.length === 0">
                    <div class="text-center py-12">
                        <div class="w-16 h-16 bg-gray-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                        <p class="text-gray-500 dark:text-slate-400">Votre panier est vide</p>
                    </div>
                </template>

                <template x-for="(item, index) in panier" :key="index">
                    <div class="flex gap-4 p-4 bg-gray-50 dark:bg-slate-900 rounded-xl">
                        <div class="flex-1">
                            <p class="font-medium text-gray-900 dark:text-white" x-text="item.nom"></p>
                            <p class="text-sm text-gray-500 dark:text-slate-400" x-text="new Intl.NumberFormat('fr-FR').format(item.prix) + ' FCFA'"></p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="diminuerQuantite(index)" class="w-7 h-7 rounded-lg bg-white dark:bg-slate-800 text-gray-600 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-700 flex items-center justify-center">−</button>
                            <span class="w-8 text-center font-medium text-gray-900 dark:text-white" x-text="item.quantite"></span>
                            <button @click="augmenterQuantite(index)" class="w-7 h-7 rounded-lg bg-white dark:bg-slate-800 text-gray-600 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-700 flex items-center justify-center">+</button>
                        </div>
                        <button @click="retirerDuPanier(index)" class="text-red-500 hover:text-red-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </template>

                <div x-show="panier.length > 0" class="border-t border-gray-200 dark:border-slate-700 pt-4 space-y-2">
                    <div class="flex justify-between text-gray-600 dark:text-slate-400">
                        <span>Sous-total</span>
                        <span x-text="new Intl.NumberFormat('fr-FR').format(sousTotal) + ' FCFA'"></span>
                    </div>
                    <div class="flex justify-between text-gray-600 dark:text-slate-400">
                        <span>Livraison</span>
                        <span>{{ number_format($institut->boutique_frais_livraison ?? 0, 0, ',', ' ') }} FCFA</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold text-gray-900 dark:text-white pt-2 border-t border-gray-200 dark:border-slate-700">
                        <span>Total</span>
                        <span x-text="new Intl.NumberFormat('fr-FR').format(total) + ' FCFA'"></span>
                    </div>
                </div>

                <button x-show="panier.length > 0" @click="ouvrirCommande()" class="btn-primary w-full">
                    Commander
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Commande --}}
    <div x-show="commandeOpen" x-transition.opacity class="fixed inset-0 bg-black/50 z-50 overflow-y-auto p-4">
        <div @click.stop class="max-w-2xl mx-auto bg-white dark:bg-slate-800 rounded-2xl shadow-2xl my-8">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Finaliser la commande</h2>
                <button @click="commandeOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('boutique.commander', $institut->slug) }}" class="p-6 space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Prénom *</label>
                        <input type="text" name="prenom" required class="input w-full">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Nom *</label>
                        <input type="text" name="nom" required class="input w-full">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Téléphone *</label>
                    <input type="tel" name="telephone" required class="input w-full">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Email (optionnel)</label>
                    <input type="email" name="email" class="input w-full">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Adresse de livraison *</label>
                    <textarea name="adresse_livraison" rows="3" required class="input w-full"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Mode de paiement *</label>
                    <select name="mode_paiement" required class="input w-full">
                        <option value="livraison">Paiement à la livraison</option>
                        <option value="mobile">Mobile Money</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Notes (optionnel)</label>
                    <textarea name="notes_client" rows="2" placeholder="Précisions, instructions..." class="input w-full"></textarea>
                </div>

                <template x-for="(item, index) in panier" :key="index">
                    <div>
                        <input type="hidden" :name="'items[' + index + '][id]'" :value="item.id">
                        <input type="hidden" :name="'items[' + index + '][quantite]'" :value="item.quantite">
                    </div>
                </template>

                <div class="bg-primary-50 dark:bg-primary-950/20 border border-primary-200 dark:border-primary-800/40 rounded-xl p-4">
                    <div class="flex justify-between font-bold text-gray-900 dark:text-white">
                        <span>Total à payer</span>
                        <span x-text="new Intl.NumberFormat('fr-FR').format(total) + ' FCFA'"></span>
                    </div>
                </div>

                <button type="submit" class="btn-primary w-full">
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
                toast: {
                    show: false,
                    message: ''
                },
                produits: @json($produits->map(function($p) {
                    return [
                        'id' => $p->id,
                        'nom' => $p->nom,
                        'prix' => $p->prix,
                        'stock' => $p->stock,
                        'photo' => $p->photo,
                        'categorie' => $p->categorie?->nom,
                        'categorie_id' => $p->categorie_id,
                    ];
                })),

                get produitsAffichés() {
                    return this.produits.filter(p => {
                        const matchCategorie = this.selectedCategorie === null || p.categorie_id === this.selectedCategorie;
                        const matchSearch = this.searchQuery === '' || p.nom.toLowerCase().includes(this.searchQuery.toLowerCase());
                        return matchCategorie && matchSearch;
                    });
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
