@php
    $ogUrl = url('/shop/' . $institut->slug . '/produit/' . $produit->id);
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $produit->nom }} — {{ $institut->nom }}</title>

    <meta property="og:type" content="product">
    <meta property="og:url" content="{{ $ogUrl }}">
    <meta property="og:title" content="{{ $ogTitle ?? $produit->nom . ' — ' . $institut->nom }}">
    <meta property="og:description" content="{{ $ogDescription ?? ($produit->description_courte ?? $produit->description ?? 'Commandez en ligne avec livraison à domicile') }}">
    @if($ogImage ?? null)
        <meta property="og:image" content="{{ $ogImage }}">
    @endif
    <meta property="og:locale" content="fr_FR">
    <meta name="twitter:card" content="summary_large_image">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 dark:bg-slate-900 min-h-screen" x-data="ficheProduit()" x-cloak>

    {{-- Toast --}}
    <div x-show="toast.show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed top-4 right-4 z-50 max-w-sm">
        <div class="bg-emerald-500 text-white px-6 py-3 rounded-xl shadow-lg flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <p class="font-medium" x-text="toast.message"></p>
        </div>
    </div>

    {{-- Header --}}
    <header class="bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 sticky top-0 z-40 shadow-sm">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <a href="{{ route('shop.index', $institut->slug) }}" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-xl transition-colors flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                    @if($institut->logo)
                        <img src="{{ asset('storage/' . $institut->logo) }}" alt="{{ $institut->nom }}" class="w-9 h-9 rounded-xl object-cover flex-shrink-0">
                    @endif
                    <span class="font-bold text-gray-900 dark:text-white text-sm truncate">{{ $institut->nom }}</span>
                </div>
                <a href="{{ route('shop.index', $institut->slug) }}?panier=1"
                   class="relative inline-flex items-center gap-2 px-4 py-2.5 bg-primary-600 text-white rounded-xl font-semibold shadow-lg hover:bg-primary-700 transition-all hover:scale-105 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <span class="hidden sm:inline">Panier</span>
                    <span x-show="totalArticles > 0" x-text="totalArticles"
                          class="absolute -top-2 -right-2 min-w-[1.5rem] h-6 px-1.5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center border-2 border-white shadow"></span>
                </a>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('shop.index', $institut->slug) }}" class="hover:text-primary-600 transition-colors">Boutique</a>
            @if($produit->categorie)
            <span>/</span>
            <a href="{{ route('shop.index', $institut->slug) }}" class="hover:text-primary-600 transition-colors">{{ $produit->categorie->nom }}</a>
            @endif
            <span>/</span>
            <span class="text-gray-900 dark:text-white font-medium">{{ $produit->nom }}</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">

            {{-- Image --}}
            <div class="aspect-square bg-white dark:bg-slate-800 rounded-2xl overflow-hidden border border-gray-200 dark:border-slate-700 shadow-sm">
                @if($produit->photo)
                    <img src="{{ asset('storage/' . $produit->photo) }}" alt="{{ $produit->nom }}"
                         class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center">
                        <svg class="w-24 h-24 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                @endif
            </div>

            {{-- Infos --}}
            <div class="space-y-5">
                {{-- Badge vedette + catégorie --}}
                <div class="flex items-center gap-2 flex-wrap">
                    @if($produit->featured)
                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 text-xs font-bold rounded-full">⭐ Produit vedette</span>
                    @endif
                    @if($produit->categorie)
                    <span class="px-3 py-1 bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 text-xs font-medium rounded-full">{{ $produit->categorie->nom }}</span>
                    @endif
                </div>

                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white leading-tight">{{ $produit->nom }}</h1>
                    @if($produit->reference)
                    <p class="text-sm text-gray-400 mt-1">Réf : {{ $produit->reference }}</p>
                    @endif
                </div>

                <div class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                    {{ number_format($produit->prix_vente, 0, ',', ' ') }} <span class="text-xl font-normal">FCFA</span>
                </div>

                {{-- Indicateur stock --}}
                @if($produit->stock <= 5)
                <div class="flex items-center gap-2 text-orange-600 dark:text-orange-400 text-sm font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    Plus que {{ $produit->stock }} en stock !
                </div>
                @elseif($produit->stock > 0)
                <div class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400 text-sm font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    En stock ({{ $produit->stock }} {{ $produit->unite }})
                </div>
                @else
                <div class="flex items-center gap-2 text-red-600 text-sm font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Rupture de stock
                </div>
                @endif

                {{-- Description --}}
                @if($produit->description_courte || $produit->description)
                <div class="prose prose-sm max-w-none text-gray-600 dark:text-slate-300">
                    <p>{{ $produit->description_courte ?? $produit->description }}</p>
                </div>
                @endif

                {{-- Sélecteur quantité + ajout panier --}}
                @if($produit->stock > 0)
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Quantité</label>
                        <div class="flex items-center gap-3">
                            <button @click="if(quantite > 1) quantite--"
                                    class="w-10 h-10 rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-700 dark:text-white flex items-center justify-center text-xl font-bold hover:bg-gray-100 transition-colors">−</button>
                            <span class="w-12 text-center text-lg font-bold text-gray-900 dark:text-white" x-text="quantite"></span>
                            <button @click="if(quantite < {{ $produit->stock }}) quantite++"
                                    :disabled="quantite >= {{ $produit->stock }}"
                                    :class="quantite >= {{ $produit->stock }} ? 'opacity-40 cursor-not-allowed' : 'hover:bg-gray-100'"
                                    class="w-10 h-10 rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-700 dark:text-white flex items-center justify-center text-xl font-bold transition-colors">+</button>
                            <span class="text-sm text-gray-400">/ {{ $produit->stock }} disponibles</span>
                        </div>
                    </div>

                    <button @click="ajouterEtRetourner()"
                            class="w-full py-4 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-bold text-lg shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        Ajouter au panier
                    </button>

                    <a href="{{ route('shop.index', $institut->slug) }}" class="block text-center text-sm text-gray-500 hover:text-primary-600 transition-colors">
                        ← Continuer mes achats
                    </a>
                </div>
                @else
                <button disabled class="w-full py-4 bg-gray-200 text-gray-500 rounded-xl font-bold text-lg cursor-not-allowed">
                    Rupture de stock
                </button>
                @endif

                {{-- Partage --}}
                <div class="flex items-center gap-3 pt-2">
                    <span class="text-sm text-gray-500">Partager :</span>
                    <a href="whatsapp://send?text={{ urlencode($produit->nom . ' — ' . $ogUrl) }}"
                       class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                        WhatsApp
                    </a>
                    <button onclick="navigator.clipboard.writeText('{{ $ogUrl }}').then(() => alert('Lien copié !'))"
                            class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                        Copier le lien
                    </button>
                </div>
            </div>
        </div>

        {{-- Produits similaires --}}
        @if($produitsSimilaires->isNotEmpty())
        <div class="mt-16">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Vous pourriez aussi aimer</h2>
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($produitsSimilaires as $sim)
                <a href="{{ route('shop.produit', [$institut->slug, $sim->id]) }}"
                   class="bg-white dark:bg-slate-800 rounded-2xl overflow-hidden border border-gray-200 dark:border-slate-700 hover:shadow-xl transition-all hover:-translate-y-1 flex flex-col">
                    <div class="aspect-square bg-gray-100 dark:bg-slate-900">
                        @if($sim->photo)
                            <img src="{{ asset('storage/' . $sim->photo) }}" alt="{{ $sim->nom }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center">
                                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                        @endif
                    </div>
                    <div class="p-3 flex-1">
                        <h3 class="font-semibold text-gray-900 dark:text-white text-sm line-clamp-2">{{ $sim->nom }}</h3>
                        <p class="text-primary-600 font-bold mt-1">{{ number_format($sim->prix_vente, 0, ',', ' ') }} F</p>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </main>

    <script>
    function ficheProduit() {
        return {
            quantite: 1,
            produit: @json(['id' => $produit->id, 'nom' => $produit->nom, 'prix' => $produit->prix_vente, 'stock' => $produit->stock, 'photo' => $produit->photo, 'categorie' => $produit->categorie?->nom, 'categorie_id' => $produit->categorie_id, 'description_courte' => $produit->description_courte, 'featured' => (bool)$produit->featured]),
            panier: JSON.parse(localStorage.getItem('panier_{{ $institut->id }}') || '[]'),
            toast: { show: false, message: '' },

            get totalArticles() {
                return this.panier.reduce((sum, item) => sum + item.quantite, 0);
            },

            ajouterEtRetourner() {
                const index = this.panier.findIndex(item => item.id === this.produit.id);
                if (index >= 0) {
                    const newQty = this.panier[index].quantite + this.quantite;
                    if (newQty > this.produit.stock) {
                        this.showToast(`Stock insuffisant (max : ${this.produit.stock})`);
                        return;
                    }
                    this.panier[index].quantite = newQty;
                } else {
                    this.panier.push({ ...this.produit, quantite: this.quantite });
                }
                localStorage.setItem('panier_{{ $institut->id }}', JSON.stringify(this.panier));
                window.location.href = '{{ route('shop.index', $institut->slug) }}?panier=1';
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
