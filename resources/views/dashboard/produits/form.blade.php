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
                            <div class="form-group" x-data="barcodeScanner()">
                                <label class="form-label">Code-barres</label>
                                <div class="flex gap-2">
                                    <input type="text" name="code_barre" maxlength="50" id="code_barre_input"
                                           x-ref="codeInput"
                                           value="{{ old('code_barre', $produit->code_barre ?? '') }}"
                                           class="form-input flex-1" placeholder="EAN-13">
                                    <button type="button" @click="startScan()"
                                            class="p-2.5 rounded-xl bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 hover:bg-purple-200 dark:hover:bg-purple-900/60 transition"
                                            title="Scanner un code-barres">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2m0 0H8m4-7V4M5 8V6a2 2 0 012-2h2m6 0h2a2 2 0 012 2v2m0 8v2a2 2 0 01-2 2h-2m-6 0H7a2 2 0 01-2-2v-2"/>
                                        </svg>
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Utilisé pour le scan à la caisse. Cliquez sur l'icône pour scanner avec la caméra.</p>
                            </div>
                        </div>

                        <div class="form-group" x-data="richtextEditor(@js(old('description', $produit->description ?? '')))">
                            <label class="form-label">Description longue</label>

                            {{-- Barre d'outils --}}
                            <div class="flex items-center gap-0.5 p-1 bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-slate-600 rounded-t-xl">
                                <button type="button" @click="exec('bold')"
                                        :class="active('bold') ? 'bg-white dark:bg-slate-700 shadow-sm text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                        class="p-1.5 rounded-lg text-sm font-bold transition" title="Gras">B</button>
                                <button type="button" @click="exec('italic')"
                                        :class="active('italic') ? 'bg-white dark:bg-slate-700 shadow-sm text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                        class="p-1.5 rounded-lg text-sm italic font-serif transition" title="Italique"><i>I</i></button>
                                <button type="button" @click="exec('underline')"
                                        :class="active('underline') ? 'bg-white dark:bg-slate-700 shadow-sm text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                        class="p-1.5 rounded-lg text-sm underline transition" title="Souligné"><u>U</u></button>
                                <span class="w-px h-5 bg-gray-300 dark:bg-slate-600 mx-1"></span>
                                <button type="button" @click="exec('insertUnorderedList')"
                                        :class="active('insertUnorderedList') ? 'bg-white dark:bg-slate-700 shadow-sm text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                        class="p-1.5 rounded-lg text-sm transition" title="Liste à puces">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                                </button>
                                <button type="button" @click="exec('insertOrderedList')"
                                        :class="active('insertOrderedList') ? 'bg-white dark:bg-slate-700 shadow-sm text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                        class="p-1.5 rounded-lg text-sm transition" title="Liste numérotée">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20h14M7 12h14M7 4h14M3 20v-1h2v-1H3v-1h3v4H3zm0-7V9h-1v2h1zm0-3V7h3v10H3v-1h2v-3H3z"/></svg>
                                </button>
                            </div>

                            {{-- Zone d'édition --}}
                            <div x-ref="editor"
                                 contenteditable="true"
                                 @input="sync"
                                 class="min-h-[120px] px-4 py-3 bg-white dark:bg-slate-700 border border-t-0 border-gray-200 dark:border-slate-600 rounded-b-xl text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500/50 prose prose-sm max-w-none"
                                 x-html="html"></div>

                            {{-- Champ caché pour le form --}}
                            <textarea name="description" x-ref="textarea" class="sr-only" x-model="html"></textarea>
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

</div>
</x-dashboard-layout>
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {

    // ─── Richtext Editor ────────────────────────────────────────────────
    Alpine.data('richtextEditor', (initialHtml = '') => ({
        html: initialHtml || '',

        init() {
            this.$watch('html', val => {
                if (this.$refs.editor && this.$refs.editor.innerHTML !== val) {
                    this.$refs.editor.innerHTML = val;
                }
            });
        },

        exec(command) {
            this.$refs.editor.focus();
            document.execCommand(command, false, null);
            this.sync();
        },

        active(command) {
            return document.queryCommandState(command);
        },

        sync() {
            this.html = this.$refs.editor.innerHTML;
        }
    }));

    // ─── Barcode Scanner ────────────────────────────────────────────────
    Alpine.data('barcodeScanner', () => ({
        scanning: false,
        stream: null,

        async startScan() {
            // Vérifier si l'API BarcodeDetector est disponible
            if (!('BarcodeDetector' in window)) {
                // Fallback : focus le champ pour scanner matériel (clavier USB)
                this.$refs.codeInput.focus();
                this.$refs.codeInput.placeholder = 'Scannez maintenant...';
                setTimeout(() => {
                    this.$refs.codeInput.placeholder = 'EAN-13';
                }, 5000);
                return;
            }

            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment' }
                });

                // Créer le modal de scan
                const modal = document.createElement('div');
                modal.innerHTML = `
                    <div style="position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.9);display:flex;flex-direction:column;align-items:center;justify-content:center;">
                        <video id="barcode-video" autoplay playsinline style="max-width:100%;max-height:70vh;border-radius:12px;"></video>
                        <div id="barcode-zone" style="position:absolute;width:250px;height:150px;border:2px solid #a855f7;border-radius:12px;box-shadow:0 0 0 9999px rgba(0,0,0,0.5);"></div>
                        <p style="color:#fff;margin-top:16px;font-size:14px;">Placez le code-barres dans le cadre</p>
                        <button id="barcode-close" style="margin-top:12px;padding:8px 24px;background:#ef4444;color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;">Annuler</button>
                    </div>`;
                document.body.appendChild(modal);

                const video = document.getElementById('barcode-video');
                const closeBtn = document.getElementById('barcode-close');
                video.srcObject = this.stream;

                const detector = new BarcodeDetector({ formats: ['ean_13', 'ean_8', 'code_128', 'code_39', 'upc_a', 'upc_e'] });

                const scan = async () => {
                    if (!this.stream) return;
                    try {
                        const barcodes = await detector.detect(video);
                        if (barcodes.length > 0) {
                            this.$refs.codeInput.value = barcodes[0].rawValue;
                            this.stopScan();
                            modal.remove();
                        }
                    } catch (e) {}
                    if (this.stream) requestAnimationFrame(scan);
                };
                scan();

                closeBtn.onclick = () => {
                    this.stopScan();
                    modal.remove();
                };

                // Arrêter après 30s max
                setTimeout(() => {
                    if (this.stream) {
                        this.stopScan();
                        modal.remove();
                    }
                }, 30000);

            } catch (e) {
                alert('Impossible d\'accéder à la caméra. Vérifiez les permissions.');
            }
        },

        stopScan() {
            if (this.stream) {
                this.stream.getTracks().forEach(t => t.stop());
                this.stream = null;
            }
        }
    }));

});
</script>
@endpush