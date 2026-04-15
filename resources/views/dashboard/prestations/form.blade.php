<x-dashboard-layout>
    <div class="max-w-xl space-y-5">
        <div>
            <h1 class="text-2xl font-display font-bold text-gray-900 tracking-tight">{{ isset($prestation) ? 'Modifier la prestation' : 'Nouvelle prestation' }}</h1>
        </div>

        <div class="card p-6">
            <form method="POST"
                  action="{{ isset($prestation) ? route('dashboard.prestations.update', $prestation) : route('dashboard.prestations.store') }}"
                  class="space-y-4">
                @csrf
                @if(isset($prestation)) @method('PUT') @endif

                @if($errors->any())
                    <div class="alert-danger text-sm">
                        @foreach($errors->all() as $e) <p>• {{ $e }}</p> @endforeach
                    </div>
                @endif

                <div class="form-group">
                    <label class="form-label">Catégorie *</label>
                    <div class="flex gap-2">
                        <select name="categorie_prestation_id" required class="form-select flex-1">
                            <option value="">Choisir une catégorie...</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}"
                                    {{ old('categorie_prestation_id', $prestation->categorie_id ?? '') === $cat->id ? 'selected' : '' }}>
                                    {{ $cat->nom }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @error('categorie_prestation_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Nom de la prestation *</label>
                    <input type="text" name="nom" required maxlength="150"
                           value="{{ old('nom', $prestation->nom ?? '') }}"
                           class="form-input @error('nom') border-red-400 @enderror"
                           placeholder="Ex: Soin hydratant visage">
                    @error('nom') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Prix (FCFA) *</label>
                        <input type="number" name="prix" required min="0" step="100"
                               value="{{ old('prix', $prestation->prix ?? '') }}"
                               class="form-input @error('prix') border-red-400 @enderror"
                               placeholder="15000">
                        @error('prix') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Durée (minutes)</label>
                        <input type="number" name="duree" min="5" max="480"
                               value="{{ old('duree', $prestation->duree ?? '') }}"
                               class="form-input"
                               placeholder="60">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="3" maxlength="500"
                              class="form-textarea"
                              placeholder="Décrivez cette prestation...">{{ old('description', $prestation->description ?? '') }}</textarea>
                </div>

                @if(isset($prestation))
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="actif" name="actif" value="1"
                           {{ old('actif', $prestation->actif) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <label for="actif" class="text-sm text-gray-700">Prestation active (visible en caisse)</label>
                </div>
                @endif

                <div class="flex gap-3 pt-2">
                    <a href="{{ route('dashboard.prestations.index') }}" class="btn btn-outline flex-1 justify-center">Annuler</a>
                    <button type="submit" class="btn-primary flex-1 justify-center">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</x-dashboard-layout>
