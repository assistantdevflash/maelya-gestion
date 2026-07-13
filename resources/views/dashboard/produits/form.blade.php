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
                            <div class="form-group">
                                <label class="form-label">Code-barres</label>
                                <input type="text" name="code_barre" maxlength="50"
                                       value="{{ old('code_barre', $produit->code_barre ?? '') }}"
                                       class="form-input" placeholder="EAN-13">
                                <p class="text-xs text-gray-500 mt-1">Utilisé pour le scan à la caisse.</p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Description longue</label>
                            <textarea name="description" rows="3" maxlength="500"
                                      class="form-textarea"
                                      placeholder="Composition, utilisation...">{{ old('description', $produit->description ?? '') }}</textarea>
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
                    <form id="delete-form" method="POST" action="{{ route('dashboard.produits.destroy', $produit) }}">
                        @csrf @method('DELETE')
                    </form>
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

    {{-- ─────────────────────────────────────────────────────────────────────
         GALERIE PHOTOS — HORS du formulaire principal (évite imbrication)
    ──────────────────────────────────────────────────────────────────────── --}}
    @if($isEdit)
    @php $images = $images ?? collect(); @endphp
    <div class="card max-w-[calc(66.667%-12px)]">
        <div class="card-header flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Galerie photos</h2>
            <span class="text-xs text-gray-400">{{ $images->count() }}/5 &nbsp;·&nbsp; ⭐ = image de couverture dans la boutique</span>
        </div>
        <div class="card-body space-y-4">

            @if($images->isNotEmpty())
            <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                @foreach($images as $img)
                <div class="relative group rounded-xl overflow-hidden border-2 {{ $img->is_principale ? 'border-amber-400' : 'border-gray-200 dark:border-slate-700' }}">
                    <img src="{{ asset('storage/' . $img->chemin) }}" alt="" class="w-full aspect-square object-cover">
                    @if($img->is_principale)
                        <div class="absolute top-1 left-1 bg-amber-400 text-amber-900 text-[9px] font-bold px-1.5 py-0.5 rounded-full">⭐</div>
                    @endif
                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1.5">
                        @if(!$img->is_principale)
                        <form method="POST" action="{{ route('dashboard.produits.images.principale', [$produit, $img]) }}">
                            @csrf
                            <button type="submit" title="Définir comme couverture"
                                    class="p-1.5 bg-amber-400 hover:bg-amber-300 text-amber-900 rounded-lg">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            </button>
                        </form>
                        @endif
                        <form method="POST" action="{{ route('dashboard.produits.images.destroy', [$produit, $img]) }}"
                              onsubmit="return confirm('Supprimer cette image ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-1.5 bg-red-500 hover:bg-red-400 text-white rounded-lg">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
            @else
                <p class="text-sm text-gray-400 italic">Aucune image dans la galerie.</p>
            @endif

            @if($images->count() < 5)
            <form method="POST" action="{{ route('dashboard.produits.images.store', $produit) }}"
                  enctype="multipart/form-data" id="form-galerie">
                @csrf
                <div class="border-2 border-dashed border-gray-300 dark:border-slate-600 rounded-xl p-5 text-center hover:border-primary-400 transition-colors">
                    <svg class="w-7 h-7 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <p class="text-sm text-gray-500 mb-1">{{ 5 - $images->count() }} emplacement(s) disponible(s)</p>
                    <p class="text-xs text-gray-400 mb-3">JPG, PNG, WebP · Max 2 Mo · Sélectionner plusieurs à la fois</p>
                    <label class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-xl transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        Ajouter des photos
                        <input type="file" name="images[]" accept="image/*" multiple class="hidden"
                               onchange="document.getElementById('form-galerie').submit()">
                    </label>
                </div>
            </form>
            @else
                <p class="text-xs text-amber-600">Limite de 5 images atteinte. Survolez une image pour la supprimer.</p>
            @endif

        </div>
    </div>
    @endif

</div>
</x-dashboard-layout>
