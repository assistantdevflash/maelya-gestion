@extends('boutique.layouts.app')

@section('title', $institut->nom . ' - Boutique')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900" x-data="panier()">
    
    {{-- Header --}}
    <header class="bg-white dark:bg-gray-800 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    @if($institut->logo)
                        <img src="{{ asset('storage/' . $institut->logo) }}" alt="{{ $institut->nom }}" class="w-16 h-16 rounded-lg object-cover">
                    @endif
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $institut->nom }}</h1>
                        <p class="text-gray-600 dark:text-gray-400">Boutique en ligne</p>
                    </div>
                </div>
                
                {{-- Bouton panier --}}
                <button 
                    @click="afficherPanier = true"
                    class="relative inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span x-show="items.length > 0" x-text="items.length" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center"></span>
                </button>
            </div>
        </div>
    </header>

    {{-- Produits --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if($produits->isEmpty())
            <div class="text-center py-12">
                <p class="text-gray-500">Aucun produit disponible.</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($produits as $produit)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                        @if($produit->photo)
                            <img src="{{ asset('storage/' . $produit->photo) }}" alt="{{ $produit->nom }}" class="w-full h-48 object-cover rounded-t-lg">
                        @else
                            <div class="w-full h-48 bg-gray-200 dark:bg-gray-700 rounded-t-lg flex items-center justify-center">
                                <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                        @endif
                        
                        <div class="p-4">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-1">{{ $produit->nom }}</h3>
                            @if($produit->description)
                                <p class="text-sm text-gray-500 mb-2 line-clamp-2">{{ $produit->description }}</p>
                            @endif
                            <p class="text-sm text-gray-500 mb-2">Stock : {{ $produit->stock }}</p>
                            <div class="flex items-center justify-between">
                                <span class="text-lg font-bold text-indigo-600">{{ number_format($produit->prix_vente, 0, ',', ' ') }} FCFA</span>
                                <button 
                                    @click="ajouterAuPanier('{{ $produit->id }}', '{{ addslashes($produit->nom) }}', {{ $produit->prix_vente }}, {{ $produit->stock }})"
                                    class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700"
                                >
                                    Ajouter
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Modal Panier --}}
    <div 
        x-show="afficherPanier"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
    >
        <div class="fixed inset-0 bg-black bg-opacity-50" @click="afficherPanier = false"></div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-2xl">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold">Mon panier</h2>
                        <button @click="afficherPanier = false">✕</button>
                    </div>

                    <div class="space-y-4 mb-4" x-show="items.length > 0">
                        <template x-for="item in items" :key="item.id">
                            <div class="flex items-center gap-4 bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                <div class="flex-1">
                                    <h3 class="font-semibold" x-text="item.nom"></h3>
                                    <p class="text-sm text-gray-500" x-text="item.prix.toLocaleString('fr-FR') + ' FCFA'"></p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button @click="decrementerQuantite(item.id)" class="px-2 py-1 bg-gray-200 rounded">-</button>
                                    <span x-text="item.quantite"></span>
                                    <button @click="incrementerQuantite(item.id)" class="px-2 py-1 bg-gray-200 rounded">+</button>
                                </div>
                                <button @click="retirerDuPanier(item.id)" class="text-red-600">
                                    Retirer
                                </button>
                            </div>
                        </template>
                    </div>

                    <div x-show="items.length === 0" class="text-center py-8 text-gray-500">
                        Votre panier est vide
                    </div>

                    <div x-show="items.length > 0" class="border-t pt-4">
                        <div class="flex justify-between text-lg font-bold mb-4">
                            <span>Total</span>
                            <span x-text="calculerTotal().toLocaleString('fr-FR') + ' FCFA'"></span>
                        </div>
                        <button 
                            @click="afficherPanier = false; afficherFormulaire = true"
                            class="w-full py-3 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700"
                        >
                            Commander
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Formulaire --}}
    <div 
        x-show="afficherFormulaire"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
    >
        <div class="fixed inset-0 bg-black bg-opacity-50" @click="afficherFormulaire = false"></div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-2xl">
                <form method="POST" action="{{ route('shop.commander', $institut->slug) }}">
                    @csrf
                    
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold">Finaliser ma commande</h2>
                            <button type="button" @click="afficherFormulaire = false">✕</button>
                        </div>

                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">Prénom *</label>
                                    <input type="text" name="prenom" required class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Nom *</label>
                                    <input type="text" name="nom" required class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">Téléphone *</label>
                                <input type="tel" name="telephone" required class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600">
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">Email (optionnel)</label>
                                <input type="email" name="email" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600">
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">Adresse de livraison *</label>
                                <textarea name="adresse" required rows="3" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">Notes (optionnel)</label>
                                <textarea name="notes" rows="2" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"></textarea>
                            </div>

                            <template x-for="(item, index) in items" :key="item.id">
                                <div>
                                    <input type="hidden" :name="'panier[' + index + '][produit_id]'" :value="item.id">
                                    <input type="hidden" :name="'panier[' + index + '][quantite]'" :value="item.quantite">
                                </div>
                            </template>
                        </div>

                        <button 
                            type="submit"
                            class="w-full mt-6 py-3 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700"
                        >
                            Valider ma commande
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('panier', () => ({
        items: [],
        afficherPanier: false,
        afficherFormulaire: false,

        init() {
            const saved = localStorage.getItem('panier_{{ $institut->slug }}');
            if (saved) this.items = JSON.parse(saved);
        },

        ajouterAuPanier(id, nom, prix, stock) {
            const existant = this.items.find(item => item.id === id);
            
            if (existant) {
                if (existant.quantite < stock) existant.quantite++;
                else { alert('Stock insuffisant'); return; }
            } else {
                this.items.push({ id, nom, prix, quantite: 1, stock });
            }
            
            this.sauvegarder();
        },

        incrementerQuantite(id) {
            const item = this.items.find(i => i.id === id);
            if (item && item.quantite < item.stock) {
                item.quantite++;
                this.sauvegarder();
            }
        },

        decrementerQuantite(id) {
            const item = this.items.find(i => i.id === id);
            if (item) {
                item.quantite--;
                if (item.quantite === 0) this.retirerDuPanier(id);
                else this.sauvegarder();
            }
        },

        retirerDuPanier(id) {
            this.items = this.items.filter(i => i.id !== id);
            this.sauvegarder();
        },

        calculerTotal() {
            return this.items.reduce((total, item) => total + (item.prix * item.quantite), 0) + {{ $institut->boutique_frais_livraison ?? 0 }};
        },

        sauvegarder() {
            localStorage.setItem('panier_{{ $institut->slug }}', JSON.stringify(this.items));
        }
    }));
});
</script>
@endpush
@endsection
