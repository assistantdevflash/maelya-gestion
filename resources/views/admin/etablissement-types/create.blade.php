@extends('layouts.admin')

@section('title', 'Ajouter un type d\'établissement')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-slate-400">
        <a href="{{ route('admin.etablissement-types.index') }}" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Types d'établissements</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-900 dark:text-white font-medium">Ajouter</span>
    </nav>

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-display font-bold text-gray-900 dark:text-white tracking-tight">
            Ajouter un type d'établissement
        </h1>
        <p class="text-gray-500 dark:text-slate-400 mt-1">
            Ce type sera disponible lors de l'inscription et la modification des établissements
        </p>
    </div>

    {{-- Formulaire --}}
    <form method="POST" action="{{ route('admin.etablissement-types.store') }}" class="card">
        @csrf
        <div class="card-body space-y-6">
            {{-- Libellé --}}
            <div>
                <label class="form-label">Libellé *</label>
                <input type="text" id="libelle" name="libelle" value="{{ old('libelle') }}" required autofocus maxlength="200"
                       class="form-input @error('libelle') border-red-400 @enderror"
                       placeholder="Ex: Salon de coiffure">
                @error('libelle')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">Nom affiché dans les formulaires</p>
            </div>

            {{-- Code --}}
            <div>
                <label class="form-label">Code technique</label>
                <input type="text" id="code" name="code" value="{{ old('code') }}" maxlength="100"
                       class="form-input @error('code') border-red-400 @enderror"
                       placeholder="Ex: salon_coiffure">
                @error('code')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">Généré automatiquement depuis le libellé (modifiable)</p>
            </div>

            {{-- Position --}}
            <div>
                <label class="form-label">Position d'affichage</label>
                <input type="number" name="position" value="{{ old('position', 0) }}" min="0" max="999"
                       class="form-input @error('position') border-red-400 @enderror">
                @error('position')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">Ordre d'affichage dans la liste (0 = en premier)</p>
            </div>

            {{-- Actif --}}
            <div class="flex items-center gap-3">
                <input type="checkbox" id="actif" name="actif" value="1" {{ old('actif', true) ? 'checked' : '' }}
                       class="w-4 h-4 text-primary-600 bg-gray-100 dark:bg-slate-700 border-gray-300 dark:border-slate-600 rounded focus:ring-primary-500 dark:focus:ring-primary-600">
                <label for="actif" class="text-sm font-medium text-gray-700 dark:text-slate-200">Type actif (visible dans les formulaires)</label>
            </div>
        </div>

        {{-- Actions --}}
        <div class="card-footer flex items-center justify-end gap-3">
            <a href="{{ route('admin.etablissement-types.index') }}" class="btn-ghost">
                Annuler
            </a>
            <button type="submit" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Créer le type
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const libelleInput = document.getElementById('libelle');
    const codeInput = document.getElementById('code');
    
    if (libelleInput && codeInput) {
        libelleInput.addEventListener('blur', function() {
            // Ne générer que si le champ code est vide
            if (!codeInput.value.trim()) {
                const libelle = this.value.trim();
                if (libelle) {
                    codeInput.value = slugify(libelle);
                }
            }
        });
    }
    
    function slugify(text) {
        return text
            .toLowerCase()
            .normalize('NFD')                   // Décomposer les caractères accentués
            .replace(/[\u0300-\u036f]/g, '')    // Supprimer les accents
            .replace(/[^\w\s-]/g, '')           // Supprimer les caractères spéciaux
            .replace(/\s+/g, '_')               // Remplacer espaces par underscore
            .replace(/-+/g, '_')                // Remplacer tirets par underscore
            .replace(/_+/g, '_')                // Supprimer underscores multiples
            .replace(/^_+|_+$/g, '');           // Supprimer underscores début/fin
    }
});
</script>
@endpush

@endsection
