<x-dashboard-layout>
    <div class="max-w-xl space-y-5">
        <div>
            <h1 class="text-2xl font-display font-bold text-gray-900 tracking-tight">{{ isset($produit) ? 'Modifier '.$produit->nom : 'Nouveau produit' }}</h1>
        </div>

        <div class="card p-6">
            <form method="POST"
                  action="{{ isset($produit) ? route('dashboard.produits.update', $produit) : route('dashboard.produits.store') }}"
                  class="space-y-4">
                @csrf
                @if(isset($produit)) @method('PUT') @endif

                @if($errors->any())
                    <div class="alert-danger text-sm">
                        @foreach($errors->all() as $e) <p>• {{ $e }}</p> @endforeach
                    </div>
                @endif

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
                        <p class="text-xs text-amber-600 mt-1">Aucune catégorie. <a href="{{ route('dashboard.produits.index') }}" class="underline font-medium">Créez-en une d'abord</a> via le bouton « Catégories ».</p>
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

                <div class="form-group">
                    <label class="form-label">Référence</label>
                    <input type="text" name="reference" maxlength="50"
                           value="{{ old('reference', $produit->reference ?? '') }}"
                           class="form-input" placeholder="SKU-001">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Prix d'achat (FCFA)</label>
                        <input type="number" name="prix_achat" min="0" step="50"
                               value="{{ old('prix_achat', $produit->prix_achat ?? '') }}"
                               class="form-input" placeholder="5000">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Prix de vente (FCFA) *</label>
                        <input type="number" name="prix_vente" required min="0" step="50"
                               value="{{ old('prix_vente', $produit->prix_vente ?? '') }}"
                               class="form-input @error('prix_vente') border-red-400 @enderror"
                               placeholder="8000">
                        @error('prix_vente') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Stock initial *</label>
                        <input type="number" name="stock" required min="0"
                               value="{{ old('stock', $produit->stock ?? 0) }}"
                               class="form-input @error('stock') border-red-400 @enderror">
                        @error('stock') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Seuil d'alerte *</label>
                        <input type="number" name="seuil_alerte" required min="0"
                               value="{{ old('seuil_alerte', $produit->seuil_alerte ?? 5) }}"
                               class="form-input @error('seuil_alerte') border-red-400 @enderror">
                        @error('seuil_alerte') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Unité</label>
                    <select name="unite" class="form-select">
                        @foreach(['pièce', 'flacon', 'tube', 'kg', 'litre', 'boîte'] as $unite)
                            <option value="{{ $unite }}" {{ old('unite', $produit->unite ?? 'pièce') === $unite ? 'selected' : '' }}>{{ $unite }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="2" maxlength="500"
                              class="form-textarea">{{ old('description', $produit->description ?? '') }}</textarea>
                </div>

                <div class="flex gap-3 pt-2">
                    <a href="{{ route('dashboard.produits.index') }}" class="btn btn-outline flex-1 justify-center">Annuler</a>
                    <button type="submit" class="btn-primary flex-1 justify-center">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</x-dashboard-layout>
