<x-dashboard-layout>
    <div class="space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-display font-bold text-gray-900 tracking-tight">Prestations</h1>
                <p class="text-sm text-gray-500 mt-1">Gérez vos services et leurs tarifs.</p>
            </div>
            <button x-data @click="$dispatch('open-prestation')" class="btn-primary group">
                <svg class="w-4 h-4 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Nouvelle prestation
            </button>
        </div>

        {{-- Filtres --}}
        <form method="GET" action="{{ route('dashboard.prestations.index') }}" class="flex flex-wrap gap-3 items-center">
            <div class="relative flex-1 min-w-[200px]">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                </svg>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Rechercher une prestation…"
                       class="form-input pl-9 dark:bg-slate-800 dark:border-slate-600 dark:text-gray-100 dark:placeholder:text-gray-500">
            </div>
            <select name="categorie_id" onchange="this.form.submit()"
                    class="form-select w-auto dark:bg-slate-800 dark:border-slate-600 dark:text-gray-100">
                <option value="">Toutes les catégories</option>
                @foreach($categoriesForFilter as $cat)
                    <option value="{{ $cat->id }}" {{ ($categorieId ?? '') === $cat->id ? 'selected' : '' }}>{{ $cat->nom }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn-primary">Rechercher</button>
            @if($search || $categorieId)
                <a href="{{ route('dashboard.prestations.index') }}" class="btn-secondary">Réinitialiser</a>
            @endif
        </form>

        {{-- Par Catégorie --}}
        @forelse($categories as $categorie)
        <div class="card overflow-hidden">
            <div class="px-5 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between" x-data="{ editing: false, nom: '{{ addslashes($categorie->nom) }}' }">
                <div class="flex items-center gap-2">
                    <template x-if="!editing">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-gray-900">{{ $categorie->nom }}</span>
                            <span class="badge badge-secondary">{{ $categorie->prestations->count() }}</span>
                        </div>
                    </template>
                    <template x-if="editing">
                        <form method="POST" action="{{ route('dashboard.categories-prestations.update', $categorie) }}" class="flex items-center gap-2">
                            @csrf @method('PUT')
                            <input type="text" name="nom" x-model="nom" required maxlength="100"
                                   class="form-input py-1 text-sm w-48" @keydown.escape="editing = false">
                            <button type="submit" class="btn-icon text-emerald-500 hover:text-emerald-600" title="Enregistrer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                            <button type="button" @click="editing = false" class="btn-icon text-gray-400 hover:text-gray-600" title="Annuler">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </form>
                    </template>
                </div>
                <div class="flex items-center gap-1">
                    <button x-show="!editing" @click="$dispatch('open-prestation-with-cat', { categorieId: '{{ $categorie->id }}' })" class="btn-icon text-gray-400 hover:text-emerald-600" title="Ajouter une prestation">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </button>
                    <button @click="editing = true" x-show="!editing" class="btn-icon text-gray-400 hover:text-primary-600" title="Modifier la catégorie">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    <form id="delete-catpresta-{{ $categorie->id }}" method="POST" action="{{ route('dashboard.categories-prestations.destroy', $categorie) }}">
                        @csrf @method('DELETE')
                    </form>
                    <button @click="$dispatch('confirm-delete', { formId: 'delete-catpresta-{{ $categorie->id }}', title: 'Supprimer cette catégorie ?', message: 'La catégorie {{ addslashes($categorie->nom) }} et toutes ses prestations seront supprimées.' })" class="btn-icon text-gray-400 hover:text-red-500" title="Supprimer la catégorie">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>

            @if($categorie->prestations->count() > 0)
            <div class="divide-y divide-gray-50">
                @foreach($categorie->prestations as $prestation)
                <div class="px-5 py-3 flex items-center gap-3 hover:bg-gray-50 group">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-gray-900 truncate">{{ $prestation->nom }}</span>
                            @if(!$prestation->actif) <span class="badge text-xs bg-gray-100 text-gray-500">Inactive</span> @endif
                        </div>
                        @if($prestation->description)
                            <p class="text-xs text-gray-400 truncate">{{ $prestation->description }}</p>
                        @endif
                    </div>
                    @if($prestation->duree)
                        <span class="text-xs text-gray-400 hidden sm:block whitespace-nowrap">{{ $prestation->duree }} min</span>
                    @endif
                    <span class="font-semibold text-sm text-primary-600 dark:text-primary-400 whitespace-nowrap">{{ $prestation->prix_formate }}</span>
                    <div class="flex items-center gap-1">
                        <button type="button" @click="$dispatch('edit-prestation', @js([
                            'id' => $prestation->id,
                            'categorie_prestation_id' => $prestation->categorie_id,
                            'nom' => $prestation->nom,
                            'prix' => $prestation->prix,
                            'duree' => $prestation->duree,
                            'description' => $prestation->description ?? '',
                            'actif' => $prestation->actif,
                        ]))" class="btn-icon text-gray-500 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400" title="Modifier">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <form method="POST" action="{{ route('dashboard.prestations.toggle', $prestation) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn-icon {{ $prestation->actif ? 'text-amber-500' : 'text-emerald-500' }}" title="{{ $prestation->actif ? 'Désactiver' : 'Activer' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $prestation->actif ? 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' }}"/>
                                </svg>
                            </button>
                        </form>
                        <form id="delete-presta-{{ $prestation->id }}" method="POST" action="{{ route('dashboard.prestations.destroy', $prestation) }}">
                            @csrf @method('DELETE')
                        </form>
                        <button type="button" @click="$dispatch('confirm-delete', { formId: 'delete-presta-{{ $prestation->id }}', title: 'Supprimer cette prestation ?', message: '{{ addslashes($prestation->nom) }} sera définitivement supprimée.' })" class="btn-icon text-red-400 hover:text-red-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
            @else
                <p class="px-5 py-3 text-sm text-gray-400">Aucune prestation dans cette catégorie.</p>
            @endif
        </div>
        @empty
        <div class="card p-12 text-center">
            <div class="text-4xl mb-3">💆‍♀️</div>
            <p class="font-semibold text-gray-900 mb-1">Aucune prestation</p>
            <p class="text-sm text-gray-500 mb-4">Commencez par créer vos services.</p>
            <button x-data @click="$dispatch('open-prestation')" class="btn-primary">Créer une prestation</button>
        </div>
        @endforelse

        {{-- Ajouter une catégorie --}}
        <div class="card p-5" x-data="{ open: false }">
            <button @click="open = !open" class="flex items-center gap-2 text-primary-600 font-semibold text-sm hover:text-primary-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Ajouter une catégorie
            </button>
            <div x-show="open" x-transition class="mt-4">
                <form method="POST" action="{{ route('dashboard.categories-prestations.store') }}" class="flex gap-3">
                    @csrf
                    <input type="text" name="nom" required maxlength="100" placeholder="Ex: Soins visage"
                           class="form-input flex-1">
                    <button type="submit" class="btn-primary">Créer</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Prestation (création / édition) --}}
    <div x-data="{
            show: false,
            isEdit: false,
            catFixe: false,
            formAction: '{{ route('dashboard.prestations.store') }}',
            form: { categorie_prestation_id: '', nom: '', prix: '', duree: '', description: '', actif: true },
            resetForm() {
                this.isEdit = false;
                this.catFixe = false;
                this.formAction = '{{ route('dashboard.prestations.store') }}';
                this.form = { categorie_prestation_id: '', nom: '', prix: '', duree: '', description: '', actif: true };
            },
            editPrestation(detail) {
                this.isEdit = true;
                this.catFixe = false;
                this.formAction = '{{ url('dashboard/prestations') }}/' + detail.id;
                this.form = {
                    categorie_prestation_id: detail.categorie_prestation_id,
                    nom: detail.nom,
                    prix: detail.prix,
                    duree: detail.duree || '',
                    description: detail.description || '',
                    actif: detail.actif
                };
                this.show = true;
            }
         }"
         x-show="show"
         x-cloak
         class="modal-backdrop"
         @open-prestation.window="resetForm(); show = true"
         @open-prestation-with-cat.window="resetForm(); form.categorie_prestation_id = $event.detail.categorieId; catFixe = true; show = true"
         @edit-prestation.window="editPrestation($event.detail)"
         @keydown.escape.window="show = false">
        <div class="modal max-w-lg" x-transition @click.stop>
            <div class="modal-header">
                <h3 class="modal-title" x-text="isEdit ? 'Modifier la prestation' : 'Nouvelle prestation'"></h3>
                <button @click="show = false" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <form :action="formAction" method="POST" class="space-y-4">
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div class="form-group">
                        <label class="form-label">Catégorie *</label>
                        <template x-if="catFixe">
                            <input type="hidden" name="categorie_prestation_id" :value="form.categorie_prestation_id">
                        </template>
                        <select name="categorie_prestation_id" required x-model="form.categorie_prestation_id" :disabled="catFixe" class="form-select" :class="catFixe ? 'opacity-60 cursor-not-allowed bg-gray-50' : ''">
                            <option value="">Choisir une catégorie...</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->nom }}</option>
                            @endforeach
                        </select>
                        @if($categories->isEmpty())
                            <p class="text-xs text-amber-600 mt-1">Aucune catégorie. Fermez et créez-en une en bas de page.</p>
                        @endif
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nom de la prestation *</label>
                        <input type="text" name="nom" required maxlength="150"
                               x-model="form.nom" class="form-input"
                               placeholder="Ex: Soin hydratant visage">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Prix (FCFA) *</label>
                            <input type="number" name="prix" required min="0" step="100"
                                   x-model="form.prix" class="form-input" placeholder="15000">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Durée (minutes)</label>
                            <input type="number" name="duree" min="5" max="480"
                                   x-model="form.duree" class="form-input" placeholder="60">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="2" maxlength="500"
                                  x-model="form.description" class="form-textarea"
                                  placeholder="Décrivez cette prestation..."></textarea>
                    </div>

                    <template x-if="isEdit">
                        <div class="flex items-center gap-2">
                            <input type="hidden" name="actif" value="0">
                            <input type="checkbox" id="modal-actif" name="actif" value="1"
                                   x-bind:checked="form.actif"
                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            <label for="modal-actif" class="text-sm text-gray-700">Prestation active (visible en caisse)</label>
                        </div>
                    </template>

                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="show = false" class="btn btn-outline flex-1 justify-center">Annuler</button>
                        <button type="submit" class="btn-primary flex-1 justify-center">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-dashboard-layout>
