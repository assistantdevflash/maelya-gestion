<x-dashboard-layout>
@php $isEdit = isset($produit); @endphp

<div class="max-w-4xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('dashboard.produits.index') }}"
           class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-xl transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-display font-bold text-gray-900 dark:text-white tracking-tight">
                {{ $isEdit ? 'Modifier ' . $produit->nom : 'Nouveau produit' }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-slate-400 mt-0.5">
                {{ $isEdit ? 'Modifiez les informations du produit' : 'Renseignez les informations du nouveau produit' }}
            </p>
        </div>
    </div>

    @if($errors->any())
        <div class="alert-danger text-sm">@foreach($errors->all() as $e)<p>• {{ $e }}</p>@endforeach</div>
    @endif
    @if(session('success'))
        <div class="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/40 rounded-xl p-4 text-emerald-800 dark:text-emerald-200 text-sm font-medium">
            ✓ {{ session('success') }}
        </div>
    @endif

    {{-- ─────────────────────────────────────────────────────────────────────
         FORMULAIRE PRINCIPAL  (jamais imbriqué avec la galerie)
    ──────────────────────────────────────────────────────────────────────── --}}
    <form id="produit-form" method="POST"
          action="{{ $isEdit ? route('dashboard.produits.update', $produit) : route('dashboard.produits.store') }}"
          enctype="multipart/form-data">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Colonne principale --}}
            <div class="lg:col-span-2 space-y-5">

                <div class="card">
                    <div class="card-header">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Informations générales</h2>
                    </div>
                    <div class="card-body space-y-4">

                        <div class="form-group">
                            <label class="form-label">Catégorie *</label>
                            <select name="categorie_id" required class="form-select">
                                <option value="">Choisir une catégorie...</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}"
                                        {{ old('categorie_id', $produit->categorie_id ?? '') === $cat->id ? 'selected' : '' }}>
                                        {{ $cat->nom }}
                                    </option>
                                @endforeach
                            </select>
                            @if($categories->isEmpty())
                                <p class="text-xs text-amber-600 mt-1">Aucune catégorie. <a href="{{ route('dashboard.produits.index') }}" class="underline font-medium">Créez-en une d'abord</a>.</p>
                            @endif
                            @error('categorie_id') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nom du produit *</label>
                            <input type="text" name="nom" required maxlength="150"
                                   value="{{ old('nom', $produit->nom ?? '') }}"
                                   class="form-input @error('nom') border-red-400 @enderror"
                                   placeholder="Ex: Shampooing kératine 500ml">
                            @error('nom') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Référence</label>
                                <input type="text" name="reference" maxlength="50"
                                       value="{{ old('reference', $produit->reference ?? '') }}"
                                       class="form-input" placeholder="SKU-001">
                            </div>
                            <div class="form-group" x-data="scannerCodeBarre()" x-init="hasCamera = 'BarcodeDetector' in window && !!navigator.mediaDevices?.getUserMedia">
                                <label class="form-label">Code-barres</label>
                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <input type="text" name="code_barre" maxlength="50" id="code_barre_input"
                                               x-ref="codeInput"
                                               x-model="saisieExterne"
                                               value="{{ old('code_barre', $produit->code_barre ?? '') }}"
                                               @keydown.enter.prevent="resoudreExterne()"
                                               @focus="scanExterneFocus = true"
                                               @blur="scanExterneFocus = false"
                                               class="form-input flex-1" placeholder="EAN-13">
                                    </div>
                                    <button type="button" @click="ouvrir()"
                                            class="p-2.5 rounded-xl bg-blue-50/50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 hover:bg-blue-100/70 dark:hover:bg-blue-900/40 border-2 border-blue-200 dark:border-blue-700/60 transition"
                                            title="Scanner un code-barres">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h2v12H4zm3 0h1v12H7zm3 0h2v12h-2zm4 0h1v12h-1zm3 0h2v12h-2z"/>
                                        </svg>
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Scannez ou saisissez un code-barres. Enter pour valider.</p>

                                {{-- Modal scan caméra --}}
                                <div x-show="modalOuvert" x-cloak
                                     class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4"
                                     @keydown.escape.window="fermer()">
                                    <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-sm w-full p-5 space-y-4">
                                        <div class="flex items-center justify-between">
                                            <h3 class="font-bold text-gray-800 dark:text-slate-100">Scanner un produit</h3>
                                            <button @click="fermer()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">✕</button>
                                        </div>
                                        <template x-if="hasCamera">
                                            <div>
                                                <video x-ref="video" autoplay playsinline class="w-full rounded-lg bg-black"></video>
                                                <p class="text-xs text-gray-500 mt-2 text-center">Placez le code-barres face à la caméra.</p>
                                            </div>
                                        </template>
                                        <template x-if="!hasCamera">
                                            <p class="text-sm text-amber-600">Caméra indisponible sur ce navigateur. Utilisez un scanner externe ou saisissez le code manuellement.</p>
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

                        <div class="form-group">
                            <label class="form-label">Description longue</label>
                            <textarea name="description" id="desc-textarea" rows="4"
                                      class="form-textarea"
                                      placeholder="Composition, utilisation, conseils...">{{ old('description', $produit->description ?? '') }}</textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Description courte <span class="text-gray-400 font-normal text-xs">(boutique en ligne)</span></label>
                            <input type="text" name="description_courte" maxlength="255"
                                   value="{{ old('description_courte', $produit->description_courte ?? '') }}"
                                   class="form-input"
                                   placeholder="Résumé visible sur la fiche produit en ligne...">
                        </div>

                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Prix et stock</h2>
                    </div>
                    <div class="card-body space-y-4">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Prix d'achat (FCFA)</label>
                                <input type="number" name="prix_achat" min="0" step="1"
                                       value="{{ old('prix_achat', $produit->prix_achat ?? '') }}"
                                       class="form-input" placeholder="0">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Prix de vente (FCFA) *</label>
                                <input type="number" name="prix_vente" required min="0" step="1"
                                       value="{{ old('prix_vente', $produit->prix_vente ?? '') }}"
                                       class="form-input @error('prix_vente') border-red-400 @enderror" placeholder="5000">
                                @error('prix_vente') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Prix promo (FCFA) <span class="text-gray-400 font-normal text-xs">(prix barré sur la boutique)</span></label>
                            <input type="number" name="prix_promo" min="0" step="1"
                                   value="{{ old('prix_promo', $produit->prix_promo ?? '') }}"
                                   class="form-input @error('prix_promo') border-red-400 @enderror"
                                   placeholder="Laisser vide si pas de promo">
                            @error('prix_promo') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                                       class="form-input @error('prix_vente') border-red-400 @enderror">
                                @error('prix_vente') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="form-group">
                                <label class="form-label">{{ $isEdit ? 'Stock actuel' : 'Stock initial *' }}</label>
                                @if($isEdit)
                                    <input type="number" value="{{ $produit->stock }}" class="form-input opacity-60" disabled>
                                    <p class="text-xs text-gray-400 mt-1">Via Entrée / Correction.</p>
                                @else
                                    <input type="number" name="stock" required min="0" value="{{ old('stock', 0) }}"
                                           class="form-input @error('stock') border-red-400 @enderror">
                                    @error('stock') <p class="form-error">{{ $message }}</p> @enderror
                                @endif
                            </div>
                            <div class="form-group">
                                <label class="form-label">Seuil d'alerte *</label>
                                <input type="number" name="seuil_alerte" required min="0"
                                       value="{{ old('seuil_alerte', $produit->seuil_alerte ?? 5) }}"
                                       class="form-input @error('seuil_alerte') border-red-400 @enderror">
                                @error('seuil_alerte') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label">Unité</label>
                                <select name="unite" class="form-select">
                                    @foreach(['pièce', 'flacon', 'tube', 'kg', 'litre', 'boîte', 'sachet', 'carton'] as $u)
                                        <option value="{{ $u }}" {{ old('unite', $produit->unite ?? 'pièce') === $u ? 'selected' : '' }}>{{ $u }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

            {{-- Colonne latérale --}}
            <div class="space-y-5">

                <div class="card">
                    <div class="card-header">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Photo principale</h2>
                    </div>
                    <div class="card-body space-y-3">
                        @if($isEdit && $produit->photo)
                            <img src="{{ asset('storage/' . $produit->photo) }}" alt="{{ $produit->nom }}"
                                 class="w-full aspect-square object-cover rounded-xl border border-gray-200 dark:border-slate-700">
                            <label class="flex items-center gap-2 text-sm text-red-500 hover:text-red-700 cursor-pointer">
                                <input type="checkbox" name="supprimer_photo" value="1" class="rounded">
                                Supprimer cette photo
                            </label>
                        @elseif($isEdit)
                            <div class="aspect-square bg-gray-100 dark:bg-slate-800 rounded-xl flex items-center justify-center">
                                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                        @endif
                        <div>
                            <label class="form-label text-xs">{{ ($isEdit && $produit->photo) ? 'Remplacer la photo' : 'Ajouter une photo' }}</label>
                            <input type="file" name="photo" accept="image/*" class="form-input text-sm">
                            <p class="text-xs text-gray-400 mt-1">JPG, PNG, WebP · Max 2 Mo</p>
                        </div>
                        @error('photo') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Boutique en ligne — toggles Alpine.js (pas de CSS peer non compilé) --}}
                <div class="card"
                     x-data="{
                        visibleBoutique: {{ old('visible_boutique', $produit->visible_boutique ?? true) ? 'true' : 'false' }},
                        featured: {{ old('featured', $produit->featured ?? false) ? 'true' : 'false' }}
                     }">
                    <div class="card-header">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Boutique en ligne</h2>
                    </div>
                    <div class="card-body space-y-4">

                        {{-- Visible en boutique --}}
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Visible en boutique</p>
                                <p class="text-xs text-gray-500">Affiché sur la page publique</p>
                            </div>
                            <button type="button" @click="visibleBoutique = !visibleBoutique"
                                    :class="visibleBoutique ? 'bg-primary-600' : 'bg-gray-300 dark:bg-slate-600'"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 rounded-full transition-colors duration-200 ease-in-out cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                                <span :class="visibleBoutique ? 'translate-x-5' : 'translate-x-0.5'"
                                      class="inline-block mt-0.5 h-5 w-5 transform rounded-full bg-white shadow transition-transform duration-200 ease-in-out"></span>
                            </button>
                            <input type="hidden" name="visible_boutique" :value="visibleBoutique ? '1' : '0'">
                        </div>

                        <hr class="border-gray-100 dark:border-slate-700">

                        {{-- En vedette --}}
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">⭐ En vedette</p>
                                <p class="text-xs text-gray-500">Mis en avant dans la boutique</p>
                            </div>
                            <button type="button" @click="featured = !featured"
                                    :class="featured ? 'bg-amber-500' : 'bg-gray-300 dark:bg-slate-600'"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 rounded-full transition-colors duration-200 ease-in-out cursor-pointer focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2">
                                <span :class="featured ? 'translate-x-5' : 'translate-x-0.5'"
                                      class="inline-block mt-0.5 h-5 w-5 transform rounded-full bg-white shadow transition-transform duration-200 ease-in-out"></span>
                            </button>
                            <input type="hidden" name="featured" :value="featured ? '1' : '0'">
                        </div>

                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    <button type="submit" class="btn-primary w-full justify-center py-3 text-base">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ $isEdit ? 'Enregistrer les modifications' : 'Créer le produit' }}
                    </button>
                    <a href="{{ route('dashboard.produits.index') }}" class="btn btn-outline w-full justify-center">Annuler</a>
                    @if($isEdit)
                    <button type="button" x-data
                            @click="$dispatch('confirm-delete', {
                                formId: 'delete-form',
                                title: 'Supprimer ce produit ?',
                                message: '{{ addslashes($produit->nom) }} sera définitivement supprimé.'
                            })"
                            class="btn w-full justify-center text-red-600 border border-red-200 hover:bg-red-50 dark:border-red-800 dark:hover:bg-red-950/30">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Supprimer ce produit
                    </button>
                    @endif
                </div>

            </div>
        </div>
    </form>

    {{-- Formulaire de suppression — HORS du formulaire principal (évite @method DELETE d'écraser @method PUT) --}}
    @if($isEdit)
    <form id="delete-form" method="POST" action="{{ route('dashboard.produits.destroy', $produit) }}" style="display:none">
        @csrf @method('DELETE')
    </form>
    @endif

    {{-- ─────────────────────────────────────────────────────────────────────
         GALERIE PHOTOS — HORS du formulaire principal, upload 100% AJAX
    ──────────────────────────────────────────────────────────────────────── --}}
    @if($isEdit)
    @php
        $imagesData = ($images ?? collect())->map(function($img) {
            return [
                'id'           => $img->id,
                'url'          => asset('storage/' . $img->chemin),
                'is_principale' => $img->is_principale ? true : false,
            ];
        })->values()->toArray();
    @endphp
    <div class="max-w-[calc(66.667%-12px)]"
         x-data="galerieManager({
             images:      {{ json_encode($imagesData) }},
             uploadUrl:   '{{ route('dashboard.produits.images.store', $produit) }}',
             deleteBase:  '{{ url('dashboard/produits/' . $produit->id . '/images') }}',
             csrf:        '{{ csrf_token() }}',
             max:         5
         })">
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Galerie photos</h2>
                <span class="text-xs text-gray-400">
                    <span x-text="images.length"></span>/5 &nbsp;·&nbsp; ⭐ = couverture boutique
                </span>
            </div>
            <div class="card-body space-y-4">

                {{-- Message d'erreur inline --}}
                <div x-show="errorMsg" x-cloak
                     class="flex items-start gap-3 p-3 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800/40 rounded-xl text-sm text-red-700 dark:text-red-300">
                    <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span x-text="errorMsg"></span>
                    <button @click="errorMsg = ''" class="ml-auto text-red-400 hover:text-red-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Grille des images existantes --}}
                <div x-show="images.length > 0" class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                    <template x-for="img in images" :key="img.id">
                        <div class="relative group rounded-xl overflow-hidden"
                             :class="img.is_principale ? 'ring-2 ring-amber-400' : 'ring-1 ring-gray-200 dark:ring-slate-700'">
                            <img :src="img.url" alt="" class="w-full aspect-square object-cover">

                            {{-- Badge couverture --}}
                            <div x-show="img.is_principale"
                                 class="absolute top-1.5 left-1.5 bg-amber-400 text-amber-900 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1">
                                ⭐ Couverture
                            </div>

                            {{-- Spinner suppression --}}
                            <div x-show="img.deleting"
                                 class="absolute inset-0 bg-white/70 dark:bg-slate-900/70 flex items-center justify-center">
                                <svg class="w-6 h-6 animate-spin text-primary-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </div>

                            {{-- Overlay actions (hover) --}}
                            <div x-show="!img.deleting"
                                 class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-end justify-center gap-2 pb-3">
                                <template x-if="!img.is_principale">
                                    <button type="button" @click="setPrincipale(img)"
                                            title="Définir comme couverture"
                                            class="flex items-center gap-1 px-2.5 py-1.5 bg-amber-400 hover:bg-amber-300 text-amber-900 text-xs font-semibold rounded-lg transition-colors">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                        Couverture
                                    </button>
                                </template>
                                <button type="button" @click="deleteImage(img)"
                                        title="Supprimer"
                                        class="flex items-center gap-1 px-2.5 py-1.5 bg-red-500 hover:bg-red-400 text-white text-xs font-semibold rounded-lg transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Supprimer
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Zone d'upload (masquée si max atteint) --}}
                <div x-show="images.length < max">
                    <label class="block cursor-pointer"
                           :class="uploading ? 'pointer-events-none opacity-60' : ''"
                           @dragover.prevent @drop.prevent="handleDrop($event)">
                        <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp,image/gif"
                               multiple class="hidden" @change="handleFiles($event)">
                        <div class="border-2 border-dashed rounded-xl p-6 text-center transition-colors"
                             :class="dragging ? 'border-primary-400 bg-primary-50 dark:bg-primary-950/20' : 'border-gray-300 dark:border-slate-600 hover:border-primary-400'">
                            <template x-if="!uploading">
                                <div>
                                    <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <p class="text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">
                                        Cliquez ou glissez-déposez vos photos ici
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        JPG, PNG, WebP · Max 5 Mo / image ·
                                        <span x-text="max - images.length"></span> emplacement(s) restant(s)
                                    </p>
                                </div>
                            </template>
                            <template x-if="uploading">
                                <div class="flex flex-col items-center gap-3">
                                    <svg class="w-8 h-8 animate-spin text-primary-600" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <p class="text-sm text-primary-600 font-medium">Envoi en cours...</p>
                                    <div class="w-48 bg-gray-200 rounded-full h-1.5">
                                        <div class="bg-primary-600 h-1.5 rounded-full transition-all duration-300"
                                             :style="'width:' + uploadProgress + '%'"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </label>
                </div>

                <div x-show="images.length >= max"
                     class="text-sm text-amber-600 dark:text-amber-400 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Limite de 5 images atteinte. Survolez une image pour la supprimer.
                </div>

            </div>
        </div>
    </div>

    <script>
    function galerieManager({ images, uploadUrl, deleteBase, csrf, max }) {
        return {
            images,
            uploading: false,
            uploadProgress: 0,
            errorMsg: '',
            dragging: false,
            max,

            handleFiles(event) {
                const files = Array.from(event.target.files);
                event.target.value = '';
                this.upload(files);
            },

            handleDrop(event) {
                this.dragging = false;
                const files = Array.from(event.dataTransfer.files).filter(f => f.type.startsWith('image/'));
                if (files.length) this.upload(files);
            },

            async upload(files) {
                this.errorMsg = '';

                // Validation côté client
                const slotsLeft = this.max - this.images.length;
                if (files.length > slotsLeft) {
                    this.errorMsg = `Vous pouvez ajouter au maximum ${slotsLeft} image(s) supplémentaire(s).`;
                    return;
                }
                const oversized = files.filter(f => f.size > 5 * 1024 * 1024);
                if (oversized.length) {
                    this.errorMsg = `${oversized.length} fichier(s) dépassent 5 Mo : ${oversized.map(f => f.name).join(', ')}`;
                    return;
                }

                const formData = new FormData();
                files.forEach(f => formData.append('images[]', f));
                formData.append('_token', csrf);

                this.uploading = true;
                this.uploadProgress = 0;

                try {
                    const xhr = new XMLHttpRequest();
                    await new Promise((resolve, reject) => {
                        xhr.upload.onprogress = (e) => {
                            if (e.lengthComputable) this.uploadProgress = Math.round((e.loaded / e.total) * 90);
                        };
                        xhr.onload = () => {
                            this.uploadProgress = 100;
                            if (xhr.status >= 200 && xhr.status < 300) resolve(JSON.parse(xhr.responseText));
                            else reject(JSON.parse(xhr.responseText));
                        };
                        xhr.onerror = () => reject({ error: 'Erreur réseau' });
                        xhr.open('POST', uploadUrl);
                        xhr.setRequestHeader('Accept', 'application/json');
                        xhr.send(formData);
                    }).then(data => {
                        data.images.forEach(img => this.images.push(img));
                    });
                } catch (err) {
                    this.errorMsg = err.error ?? err.message ?? 'Erreur lors de l\'envoi.';
                    if (err.errors) {
                        this.errorMsg = Object.values(err.errors).flat().join(' ');
                    }
                } finally {
                    this.uploading = false;
                    this.uploadProgress = 0;
                }
            },

            async deleteImage(img) {
                if (!confirm(`Supprimer cette image ?`)) return;
                img.deleting = true;
                try {
                    const res = await fetch(`${deleteBase}/${img.id}`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                        body: JSON.stringify({ _method: 'DELETE' }),
                    });
                    const data = await res.json();
                    this.images = this.images.filter(i => i.id !== img.id);
                    // Mettre à jour la principale si besoin
                    if (data.new_principale_id) {
                        const next = this.images.find(i => i.id === data.new_principale_id);
                        if (next) next.is_principale = true;
                    }
                } catch (e) {
                    img.deleting = false;
                    this.errorMsg = 'Erreur lors de la suppression.';
                }
            },

            async setPrincipale(img) {
                try {
                    await fetch(`${deleteBase}/${img.id}/principale`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    });
                    this.images.forEach(i => i.is_principale = i.id === img.id);
                } catch (e) {
                    this.errorMsg = 'Erreur lors de la mise à jour.';
                }
            },
        };
    }
    </script>
    @endif

    {{-- Scanner code-barres — inline AVANT Alpine --}}
    <script>
    (function() {
        window.scannerCodeBarre = function() {
            return {
                modalOuvert: false,
                hasCamera: false,
                saisie: '',
                saisieExterne: @js(old('code_barre', $produit->code_barre ?? '')),
                scanExterneFocus: false,
                stream: null,
                detector: null,
                intervalScan: null,

                async ouvrir() {
                    this.modalOuvert = true;
                    this.saisie = '';
                    this.hasCamera = 'BarcodeDetector' in window && !!navigator.mediaDevices?.getUserMedia;
                    if (!this.hasCamera) return;
                    try {
                        this.detector = new BarcodeDetector({ formats: ['ean_13','ean_8','code_128','code_39','upc_a','upc_e'] });
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

                resoudreExterne() {
                    this.resoudre(this.saisieExterne);
                    this.saisieExterne = '';
                },

                resoudre(code) {
                    code = (code || '').trim();
                    if (!code) return;
                    this.saisieExterne = code;
                    this.$refs.codeInput.value = code;
                    this.fermer();
                }
            };
        };
    })();
    </script>

</div>
</x-dashboard-layout>

@push('styles')
<style>
    .ql-toolbar.ql-snow {
        background: var(--ql-toolbar-bg, #f9fafb);
        border-color: #e5e7eb;
        border-radius: 12px 12px 0 0;
    }
    .ql-container.ql-snow {
        background: var(--ql-bg, #ffffff);
        border-color: #e5e7eb;
        border-radius: 0 0 12px 12px;
        font-size: 14px;
        min-height: 150px;
    }
    .ql-editor { min-height: 150px; color: #111827; }
    .ql-editor.ql-blank::before { color: #9ca3af; font-style: normal; }
    .dark .ql-toolbar.ql-snow  { --ql-toolbar-bg: rgb(255 255 255 / 0.05); border-color: rgb(255 255 255 / 0.1); }
    .dark .ql-container.ql-snow { --ql-bg: rgb(255 255 255 / 0.03); border-color: rgb(255 255 255 / 0.1); }
    .dark .ql-editor { color: #f3f4f6; }
    .dark .ql-toolbar button svg .ql-stroke { stroke: #d1d5db; }
    .dark .ql-toolbar button svg .ql-fill { fill: #d1d5db; }
    .dark .ql-toolbar .ql-picker-label { color: #d1d5db; }
    .dark .ql-toolbar .ql-picker-options { background: #1f2937; border-color: rgb(255 255 255 / 0.1); }
    .dark .ql-toolbar button:hover svg .ql-stroke,
    .dark .ql-toolbar button.ql-active svg .ql-stroke { stroke: #c084fc; }
    .dark .ql-toolbar button:hover svg .ql-fill,
    .dark .ql-toolbar button.ql-active svg .ql-fill { fill: #c084fc; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const textarea = document.getElementById('desc-textarea');
    if (!textarea || typeof window.Quill === 'undefined') return;

    // Remplacer le textarea par un conteneur Quill
    const wrapper = document.createElement('div');
    wrapper.id = 'desc-editor';
    textarea.parentNode.insertBefore(wrapper, textarea);
    textarea.style.display = 'none';

    const quill = new window.Quill('#desc-editor', {
        theme: 'snow',
        placeholder: 'Composition, utilisation, conseils…',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['clean']
            ]
        }
    });

    if (textarea.value) quill.clipboard.dangerouslyPasteHTML(textarea.value);

    document.getElementById('produit-form').addEventListener('submit', function () {
        textarea.value = quill.root.innerHTML;
    });
});
</script>
@endpush
