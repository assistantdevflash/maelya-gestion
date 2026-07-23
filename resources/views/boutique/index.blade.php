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
    @endif
    <meta property="og:locale" content="fr_FR">
    <meta name="twitter:card" content="summary_large_image">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-purple-50 dark:bg-slate-900 min-h-screen" x-data="boutique()" x-cloak>
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
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    @if($institut->logo)
                        <img src="{{ asset('storage/' . $institut->logo) }}" alt="{{ $institut->nom }}" class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl object-cover flex-shrink-0">
                    @endif
                    <div class="min-w-0">
                        <h1 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white truncate">{{ $institut->nom }}</h1>
                        <p class="text-xs text-gray-500 dark:text-slate-400">Boutique en ligne</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    {{-- Lien vers la boutique --}}
                    <a href="https://wa.me/?text={{ urlencode('Découvrez la boutique de ' . $institut->nom . ' : ' . $ogUrl) }}"
                       class="hidden sm:flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-600 hover:text-emerald-600 border border-gray-200 rounded-xl hover:border-emerald-200 transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                        Partager
                    </a>
                    {{-- Bouton panier --}}
                    <button @click="panierOpen = true" class="relative inline-flex items-center gap-2 px-4 py-2.5 bg-primary-600 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl hover:bg-primary-700 transition-all hover:scale-105 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span class="hidden sm:inline">Panier</span>
                        <span x-show="totalArticles > 0" x-text="totalArticles"
                              class="absolute -top-2 -right-2 min-w-[1.5rem] h-6 px-1.5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center border-2 border-white dark:border-slate-900 shadow-lg"></span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    {{-- Contenu principal --}}
    <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

        {{-- Section filtres + tri --}}
        <div class="mb-8 space-y-4">
            <div class="flex flex-col sm:flex-row gap-3">
                {{-- Barre de recherche --}}
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" x-model="searchQuery" placeholder="Rechercher un produit..."
                        class="w-full pl-11 pr-4 py-3 bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-shadow shadow-sm">
                </div>
                {{-- Tri --}}
                <select x-model="sortBy" class="py-3 px-4 bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-xl text-gray-700 dark:text-white focus:ring-2 focus:ring-primary-500 shadow-sm">
                    <option value="default">Tri : Défaut</option>
                    <option value="nom_asc">Nom : A → Z</option>
                    <option value="prix_asc">Prix : croissant</option>
                    <option value="prix_desc">Prix : décroissant</option>
                    <option value="featured">Vedettes en premier</option>
                </select>
            </div>

            {{-- Filtres catégories --}}
            @if($categories->isNotEmpty())
            <div class="flex flex-wrap gap-2">
                <button @click="setCategorie(null)"
                    :class="selectedCategorie === null ? 'bg-primary-600 text-white ring-2 ring-primary-600 ring-offset-2' : 'bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 hover:bg-gray-100'"
                    class="px-4 py-2 rounded-lg text-sm font-medium border border-gray-200 dark:border-slate-600 transition-all">Tous</button>
                @foreach($categories as $cat)
                <button @click="setCategorie('{{ $cat->id }}')"
                    :class="selectedCategorie === '{{ $cat->id }}' ? 'bg-primary-600 text-white ring-2 ring-primary-600 ring-offset-2' : 'bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 hover:bg-gray-100'"
                    class="px-4 py-2 rounded-lg text-sm font-medium border border-gray-200 dark:border-slate-600 transition-all">{{ $cat->nom }}</button>
                @endforeach
            </div>
            @endif

            <p class="text-sm text-gray-500 dark:text-slate-400">
                <span x-text="produitsAffiches.length"></span> produit<span x-show="produitsAffiches.length > 1">s</span>
            </p>
        </div>

        {{-- Grille produits --}}
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            <template x-for="produit in produitsAffiches" :key="produit.id">
                <div class="bg-white dark:bg-slate-800 rounded-2xl overflow-hidden border border-gray-200 dark:border-slate-700 hover:shadow-xl transition-all duration-300 hover:-translate-y-1 flex flex-col">
                    {{-- Image produit --}}
                    <a :href="'/shop/{{ $institut->slug }}/produit/' + produit.id" class="relative block aspect-square bg-gray-100 dark:bg-slate-900 overflow-hidden">
                        <template x-if="produit.photo">
                            <img :src="'/storage/' + produit.photo" :alt="produit.nom" class="w-full h-full object-cover" loading="lazy">
                        </template>
                        <template x-if="!produit.photo">
                            <div class="w-full h-full flex items-center justify-center">
                                <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                        </template>
                        {{-- Badge vedette --}}
                        <div x-show="produit.featured" class="absolute top-2 left-2 bg-amber-400 text-amber-900 text-[10px] font-bold px-2 py-0.5 rounded-full">★ Vedette</div>
                        {{-- Badge stock faible --}}
                        <div x-show="produit.stock > 0 && produit.stock <= 5" class="absolute top-2 right-2 bg-orange-500 text-white text-xs font-bold px-2 py-1 rounded-lg">
                            Plus que <span x-text="produit.stock"></span>
                        </div>
                    </a>

                    {{-- Infos produit --}}
                    <div class="p-3 sm:p-4 flex flex-col flex-1 gap-2">
                        <div>
                            <a :href="'/shop/{{ $institut->slug }}/produit/' + produit.id">
                                <h3 class="font-semibold text-gray-900 dark:text-white text-sm sm:text-base line-clamp-2 hover:text-primary-600 transition-colors" x-text="produit.nom"></h3>
                            </a>
                            <p x-show="produit.categorie" class="text-xs text-gray-400 mt-0.5" x-text="produit.categorie"></p>
                        </div>
                        <p x-show="produit.description_courte" class="text-xs text-gray-500 line-clamp-2" x-text="produit.description_courte"></p>
                        <div class="mt-auto flex items-center justify-between gap-2">
                            <div>
                                <p x-show="produit.prix_promo" class="text-xs text-gray-400 dark:text-gray-500 line-through">
                                    <span x-text="new Intl.NumberFormat('fr-FR').format(produit.prix)"></span> F
                                </p>
                                <p class="text-base sm:text-lg font-bold"
                                   :class="produit.prix_promo ? 'text-red-500 dark:text-red-400' : 'text-primary-600 dark:text-primary-400'">
                                    <span x-text="new Intl.NumberFormat('fr-FR').format(produit.prix_promo || produit.prix)"></span> <span class="text-sm font-normal">F</span>
                                </p>
                            </div>
                            <button @click="ajouterAuPanier(produit)"
                                class="inline-flex items-center gap-1 px-3 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium text-sm shadow-md hover:shadow-lg transition-all hover:scale-105">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                <span class="hidden sm:inline">Ajouter</span>
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
            <p class="text-gray-500 text-lg">Aucun produit trouvé</p>
            <button @click="searchQuery = ''; selectedCategorie = null" class="mt-4 text-primary-600 hover:text-primary-700 font-medium">Réinitialiser</button>
        </div>

        {{-- Info livraison --}}
        <br><br>
        <div class="mt-10 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="flex items-center gap-3 p-4 bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700">
                <svg class="w-8 h-8 text-primary-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8l1 12a2 2 0 002 2h8a2 2 0 002-2L19 8M10 12v4M14 12v4"/></svg>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white text-sm">Livraison à domicile</p>
                    <p class="text-xs text-gray-500 dark:text-slate-400">{{ $institut->boutique_delai_livraison ?? '2-5 jours ouvrables' }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 p-4 bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700">
                <svg class="w-8 h-8 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white text-sm">Paiement à la livraison</p>
                    <p class="text-xs text-gray-500 dark:text-slate-400">Payez en cash à la réception</p>
                </div>
            </div>
            <div class="flex items-center gap-3 p-4 bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700">
                <svg class="w-8 h-8 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white text-sm">Suivi en temps réel</p>
                    <p class="text-xs text-gray-500 dark:text-slate-400">Suivez votre commande en ligne</p>
                </div>
            </div>
        </div>
    </main>

    {{-- Footer --}}
    <footer class="mt-8 py-6 border-t border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800">
        <div class="container mx-auto px-4 text-center">
            <p class="text-sm text-gray-500 dark:text-slate-400">{{ $institut->nom }} &mdash; Boutique en ligne</p>
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">Propulsé par Maëlya Gestion</p>
        </div>
    </footer>

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
        <div @click.stop x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-slate-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Mon panier</h2>
                <button @click="panierOpen = false" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
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
                    <div class="flex gap-4 p-3 bg-gray-50 dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-700">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 dark:text-white truncate text-sm" x-text="item.nom"></p>
                            <p class="text-sm text-gray-500 mt-0.5" x-text="new Intl.NumberFormat('fr-FR').format(item.prix) + ' F'"></p>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button @click="diminuerQuantite(index)" class="w-7 h-7 rounded-lg bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-600 text-gray-700 flex items-center justify-center font-bold transition-colors hover:bg-gray-100">-</button>
                            <span class="w-8 text-center font-bold text-gray-900 dark:text-white text-sm" x-text="item.quantite"></span>
                            <button @click="augmenterQuantite(index)"
                                    :disabled="item.quantite >= item.stock"
                                    :class="item.quantite >= item.stock ? 'opacity-40 cursor-not-allowed' : 'hover:bg-gray-100'"
                                    class="w-7 h-7 rounded-lg bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-600 text-gray-700 flex items-center justify-center font-bold transition-colors">+</button>
                        </div>
                        <button @click="retirerDuPanier(index)" class="flex-shrink-0 text-red-400 hover:text-red-600 p-1.5 rounded-lg hover:bg-red-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </template>
            </div>
            <div x-show="panier.length > 0" class="p-5 border-t border-gray-200 dark:border-slate-700 space-y-4">
                <div class="space-y-2">
                    <div class="flex justify-between text-sm text-gray-600 dark:text-slate-400">
                        <span>Sous-total</span>
                        <span x-text="new Intl.NumberFormat('fr-FR').format(sousTotal) + ' F'"></span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-600 dark:text-slate-400">
                        <span>Livraison</span>
                        <span>{{ number_format($institut->boutique_frais_livraison ?? 0, 0, ',', ' ') }} F</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold text-gray-900 dark:text-white pt-2 border-t border-gray-200 dark:border-slate-700">
                        <span>Total</span>
                        <span x-text="new Intl.NumberFormat('fr-FR').format(total) + ' F'"></span>
                    </div>
                </div>
                <a href="{{ route('shop.commander.form', $institut->slug) }}" class="w-full py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition-all text-center block">
                    Commander maintenant
                </a>
            </div>
        </div>
    </div>

    {{-- Bouton Commander — redirige vers la page checkout --}}
    <div x-show="panier.length > 0" class="fixed bottom-24 right-6 z-40">
        <a href="{{ route('shop.commander.form', $institut->slug) }}"
           class="flex items-center gap-3 px-6 py-4 bg-primary-600 hover:bg-primary-700 text-white rounded-2xl font-bold shadow-xl hover:shadow-2xl transition-all">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
            <span>Commander</span>
            <span class="bg-white/20 px-2.5 py-1 rounded-full text-sm" x-text="new Intl.NumberFormat('fr-FR').format(sousTotal) + ' F'"></span>
        </a>
    </div>


    <script>
        function boutique() {
            return {
                searchQuery: '',
                selectedCategorie: null,
                sortBy: 'default',
                panierOpen: false,
                panier: JSON.parse(localStorage.getItem('panier_{{ $institut->id }}') || '[]'),
                fraisLivraison: {{ $institut->boutique_frais_livraison ?? 0 }},
                toast: { show: false, message: '' },
                produits: @json($produitsJson),

                init() {
                    this.panier = this.panier.filter(item => item.prix && item.prix > 0);
                    this.sauvegarderPanier();
                    // Lire catégorie depuis l'URL (?categorie=xxx)
                    const params = new URLSearchParams(location.search);
                    if (params.get('categorie')) this.selectedCategorie = params.get('categorie');
                    // Ouvrir panier si redirigé depuis fiche produit
                    if (params.get('panier') === '1') {
                        this.panierOpen = true;
                        history.replaceState(null, '', location.pathname);
                    }
                },

                // Watcher catégorie → mettre à jour l'URL
                setCategorie(catId) {
                    this.selectedCategorie = catId;
                    const url = new URL(location);
                    if (catId) url.searchParams.set('categorie', catId);
                    else url.searchParams.delete('categorie');
                    history.pushState(null, '', url);
                },

                get produitsAffiches() {
                    let liste = this.produits.filter(p => {
                        const matchCategorie = this.selectedCategorie === null || String(p.categorie_id) === String(this.selectedCategorie);
                        const matchSearch = this.searchQuery === '' || p.nom.toLowerCase().includes(this.searchQuery.toLowerCase());
                        return matchCategorie && matchSearch;
                    });

                    if (this.sortBy === 'nom_asc') return liste.sort((a, b) => a.nom.localeCompare(b.nom));
                    if (this.sortBy === 'prix_asc') return liste.sort((a, b) => a.prix - b.prix);
                    if (this.sortBy === 'prix_desc') return liste.sort((a, b) => b.prix - a.prix);
                    if (this.sortBy === 'featured') return liste.sort((a, b) => (b.featured ? 1 : 0) - (a.featured ? 1 : 0));
                    return liste; // default: vedettes en premier (déjà trié côté serveur)
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
                        if (this.panier[index].quantite < produit.stock) {
                            this.panier[index].quantite++;
                        } else {
                            this.showToast(`Stock insuffisant (max : ${produit.stock})`);
                            return;
                        }
                    } else {
                        this.panier.push({ ...produit, quantite: 1 });
                    }
                    this.sauvegarderPanier();
                    this.showToast(`${produit.nom} ajouté au panier`);
                },

                augmenterQuantite(index) {
                    const item = this.panier[index];
                    if (item.quantite < item.stock) {
                        item.quantite++;
                        this.sauvegarderPanier();
                    }
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

                showToast(message) {
                    this.toast.message = message;
                    this.toast.show = true;
                    setTimeout(() => { this.toast.show = false; }, 3000);
                },
            }
        }
    </script>
</body>
</html>
