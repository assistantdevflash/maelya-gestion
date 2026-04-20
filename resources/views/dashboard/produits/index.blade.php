<x-dashboard-layout>
    <div class="space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-display font-bold text-gray-900 tracking-tight">Produits</h1>
                <p class="text-sm text-gray-500 mt-1">{{ $produits->total() }} produit(s) dans votre catalogue</p>
            </div>
            <div class="flex gap-2">
                <button x-data @click="$dispatch('open-categories')" class="btn-outline">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    Catégories
                    @if($categories->count() > 0)
                        <span class="ml-1 inline-flex items-center justify-center min-w-[18px] h-[18px] text-xs bg-gray-100 text-gray-600 rounded-full px-1 font-mono leading-none">{{ $categories->count() }}</span>
                    @else
                        <span class="ml-1 inline-flex items-center justify-center w-[18px] h-[18px] text-xs bg-amber-100 text-amber-600 rounded-full font-bold leading-none">!</span>
                    @endif
                </button>
                <button x-data @click="$dispatch('open-produit')" class="btn-primary group">
                    <svg class="w-4 h-4 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Nouveau produit
                </button>
            </div>
        </div>

        {{-- Filtres --}}
        <div class="card p-4">
            <form method="GET" action="{{ route('dashboard.produits.index') }}" class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Nom, référence..."
                           class="form-input pl-9">
                </div>
                <select name="categorie" class="form-select w-auto">
                    <option value="">Toutes catégories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('categorie') === $cat->id ? 'selected' : '' }}>{{ $cat->nom }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn-outline">Filtrer</button>
                @if(request()->hasAny(['q', 'categorie']))
                    <a href="{{ route('dashboard.produits.index') }}" class="btn btn-ghost">Réinitialiser</a>
                @endif
            </form>
        </div>

        {{-- Table --}}
        @if($produits->count() > 0)
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Produit</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 hidden md:table-cell">Catégorie</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">Stock</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600 hidden sm:table-cell">Prix vente</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($produits as $produit)
                        <tr class="hover:bg-gray-50 group transition-colors">
                            <td class="px-4 py-3">
                                <div>
                                    <span class="font-medium text-gray-900">{{ $produit->nom }}</span>
                                    @if($produit->reference)
                                        <p class="text-xs text-gray-400">Réf: {{ $produit->reference }}</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-500 hidden md:table-cell">{{ $produit->categorie?->nom ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                @if($produit->isEnAlerte())
                                    <span class="badge badge-danger">{{ $produit->stock }} {{ $produit->unite }}</span>
                                @else
                                    <span class="font-medium text-gray-900">{{ $produit->stock }} {{ $produit->unite }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-primary-600 hidden sm:table-cell">
                                {{ number_format($produit->prix_vente, 0, ',', ' ') }} F
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button x-data @click="$dispatch('show-produit', {
                                        nom: '{{ addslashes($produit->nom) }}',
                                        reference: '{{ addslashes($produit->reference ?? '—') }}',
                                        categorie: '{{ addslashes($produit->categorie?->nom ?? '—') }}',
                                        prix_achat: '{{ $produit->prix_achat ? number_format($produit->prix_achat, 0, ',', ' ').' F' : '—' }}',
                                        prix_vente: '{{ number_format($produit->prix_vente, 0, ',', ' ') }} F',
                                        stock: '{{ $produit->stock }} {{ $produit->unite }}',
                                        seuil_alerte: '{{ $produit->seuil_alerte }} {{ $produit->unite }}',
                                        unite: '{{ $produit->unite }}',
                                        description: '{{ addslashes($produit->description ?? '—') }}'
                                    })" class="btn-icon text-gray-400 hover:text-primary-600" title="Détails">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                    <button x-data @click="$dispatch('edit-produit', {
                                        id: '{{ $produit->id }}',
                                        categorie_id: '{{ $produit->categorie_id }}',
                                        nom: '{{ addslashes($produit->nom) }}',
                                        reference: '{{ addslashes($produit->reference ?? '') }}',
                                        prix_achat: {{ $produit->prix_achat ?? 0 }},
                                        prix_vente: {{ $produit->prix_vente }},
                                        stock: {{ $produit->stock }},
                                        seuil_alerte: {{ $produit->seuil_alerte }},
                                        unite: '{{ $produit->unite }}',
                                        description: '{{ addslashes($produit->description ?? '') }}'
                                    })" class="btn-icon" title="Modifier">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <form id="delete-produit-{{ $produit->id }}" method="POST" action="{{ route('dashboard.produits.destroy', $produit) }}">
                                        @csrf @method('DELETE')
                                    </form>
                                    <button x-data @click="$dispatch('confirm-delete', { formId: 'delete-produit-{{ $produit->id }}', title: 'Supprimer ce produit ?', message: '{{ addslashes($produit->nom) }} sera définitivement supprimé.' })" class="btn-icon text-red-400 hover:text-red-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $produits->withQueryString()->links() }}
            </div>
        </div>
        @else
        <div class="card p-8 text-center">
            <div class="text-4xl mb-3">📦</div>
            @if($categories->isEmpty())
                <p class="font-semibold text-gray-900 mb-2">Commencez par créer vos catégories</p>
                <p class="text-sm text-gray-500 mb-4">Les catégories permettent d'organiser vos produits.<br>Créez-en au moins une avant d'ajouter des produits.</p>
                <button x-data @click="$dispatch('open-categories')" class="btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    Créer des catégories
                </button>
            @else
                <p class="font-semibold text-gray-900 mb-1">Aucun produit dans le catalogue</p>
                <p class="text-sm text-gray-500 mb-4">Ajoutez vos premiers produits pour les vendre en caisse.</p>
                <button x-data @click="$dispatch('open-produit')" class="btn-primary">Ajouter un produit</button>
            @endif
        </div>
        @endif
    </div>

    {{-- Modal Produit (création / édition) --}}
    <div x-data="{
            show: false,
            isEdit: false,
            formAction: '{{ route('dashboard.produits.store') }}',
            form: { categorie_id: '', nom: '', reference: '', prix_achat: '', prix_vente: '', stock: 0, seuil_alerte: 5, unite: 'pièce', description: '' },
            resetForm() {
                this.isEdit = false;
                this.formAction = '{{ route('dashboard.produits.store') }}';
                this.form = { categorie_id: '', nom: '', reference: '', prix_achat: '', prix_vente: '', stock: 0, seuil_alerte: 5, unite: 'pièce', description: '' };
            },
            init() {
                window.addEventListener('open-produit', () => { this.resetForm(); this.show = true; });
                window.addEventListener('edit-produit', (e) => {
                    this.isEdit = true;
                    this.formAction = '{{ url('dashboard/produits') }}/' + e.detail.id;
                    this.form = {
                        categorie_id: e.detail.categorie_id,
                        nom: e.detail.nom,
                        reference: e.detail.reference,
                        prix_achat: e.detail.prix_achat || '',
                        prix_vente: e.detail.prix_vente,
                        stock: e.detail.stock,
                        seuil_alerte: e.detail.seuil_alerte,
                        unite: e.detail.unite,
                        description: e.detail.description
                    };
                    this.show = true;
                });
            }
         }"
         x-show="show"
         x-cloak
         class="modal-backdrop"
         @keydown.escape.window="show = false">
        <div class="modal max-w-lg" x-transition @click.stop>
            <div class="modal-header">
                <h3 class="modal-title" x-text="isEdit ? 'Modifier le produit' : 'Nouveau produit'"></h3>
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
                        <select name="categorie_id" required x-model="form.categorie_id" class="form-select">
                            <option value="">Choisir une catégorie...</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->nom }}</option>
                            @endforeach
                        </select>
                        @if($categories->isEmpty())
                            <p class="text-xs text-amber-600 mt-1">Aucune catégorie. Fermez et cliquez sur « Catégories » pour en créer.</p>
                        @endif
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nom du produit *</label>
                        <input type="text" name="nom" required maxlength="150"
                               x-model="form.nom" class="form-input"
                               placeholder="Ex: Shampooing kératine 500ml">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Référence</label>
                        <input type="text" name="reference" maxlength="50"
                               x-model="form.reference" class="form-input" placeholder="SKU-001">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Prix d'achat (FCFA)</label>
                            <input type="number" name="prix_achat" min="0" step="1"
                                   x-model="form.prix_achat" class="form-input" placeholder="5000">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Prix de vente (FCFA) *</label>
                            <input type="number" name="prix_vente" required min="0" step="1"
                                   x-model="form.prix_vente" class="form-input" placeholder="8000">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label" x-text="isEdit ? 'Stock actuel' : 'Stock initial *'"></label>
                            <input type="number" name="stock" min="0"
                                   x-model="form.stock" class="form-input"
                                   :required="!isEdit" :disabled="isEdit">
                            <template x-if="isEdit">
                                <p class="text-xs text-gray-400 mt-1">Modifiez le stock via Entrée / Correction.</p>
                            </template>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Seuil d'alerte *</label>
                            <input type="number" name="seuil_alerte" required min="0"
                                   x-model="form.seuil_alerte" class="form-input">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Unité</label>
                        <select name="unite" x-model="form.unite" class="form-select">
                            <option value="pièce">pièce</option>
                            <option value="flacon">flacon</option>
                            <option value="tube">tube</option>
                            <option value="kg">kg</option>
                            <option value="litre">litre</option>
                            <option value="boîte">boîte</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="2" maxlength="500"
                                  x-model="form.description" class="form-textarea"></textarea>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="show = false" class="btn btn-outline flex-1 justify-center">Annuler</button>
                        <button type="submit" class="btn-primary flex-1 justify-center">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Gestion des catégories --}}
    <div x-data="{ show: false }"
         x-show="show"
         x-cloak
         class="modal-backdrop"
         @open-categories.window="show = true"
         @keydown.escape.window="show = false">
        <div class="modal" x-transition @click.stop>
            <div class="modal-header">
                <h3 class="modal-title">Catégories de produits</h3>
                <button @click="show = false" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body space-y-4">
                <form method="POST" action="{{ route('dashboard.categories-produits.store') }}" class="flex gap-2">
                    @csrf
                    <input type="text" name="nom" required maxlength="100" class="form-input flex-1" placeholder="Nouvelle catégorie...">
                    <button type="submit" class="btn-primary">Ajouter</button>
                </form>

                @if($categories->count() > 0)
                <div class="divide-y divide-gray-100 border border-gray-100 rounded-xl overflow-hidden">
                    @foreach($categories as $cat)
                    <div class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 group" x-data="{ editing: false, nom: '{{ addslashes($cat->nom) }}' }">
                        <template x-if="!editing">
                            <div class="flex items-center justify-between w-full">
                                <span class="text-sm font-medium text-gray-900">{{ $cat->nom }}</span>
                                <span class="text-xs text-gray-400">{{ $cat->produits_count ?? $cat->produits()->count() }} produit(s)</span>
                            </div>
                        </template>
                        <template x-if="editing">
                            <form method="POST" action="{{ route('dashboard.categories-produits.update', $cat) }}" class="flex gap-2 w-full">
                                @csrf @method('PUT')
                                <input type="text" name="nom" x-model="nom" required maxlength="100" class="form-input flex-1 text-sm">
                                <button type="submit" class="btn-primary text-xs px-3">OK</button>
                                <button type="button" @click="editing = false" class="btn-outline text-xs px-3">×</button>
                            </form>
                        </template>
                        <div class="flex items-center gap-1 ml-2 opacity-0 group-hover:opacity-100 transition-opacity" x-show="!editing">
                            <button @click="editing = true" class="btn-icon" title="Renommer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <form id="delete-cat-{{ $cat->id }}" method="POST" action="{{ route('dashboard.categories-produits.destroy', $cat) }}">
                                @csrf @method('DELETE')
                            </form>
                            <button @click="$dispatch('confirm-delete', { formId: 'delete-cat-{{ $cat->id }}', title: 'Supprimer cette catégorie ?', message: 'La catégorie {{ addslashes($cat->nom) }} sera supprimée.' })" class="btn-icon text-red-400 hover:text-red-600" title="Supprimer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-sm text-gray-400 text-center py-4">Aucune catégorie. Créez-en une ci-dessus.</p>
                @endif
            </div>
        </div>
    </div>
    {{-- Modal Détails produit --}}
    <div x-data="{
            show: false,
            produit: {},
            init() {
                window.addEventListener('show-produit', (e) => {
                    this.produit = e.detail;
                    this.show = true;
                });
            }
         }"
         x-show="show"
         x-cloak
         class="modal-backdrop"
         @keydown.escape.window="show = false">
        <div class="modal max-w-md" x-transition @click.stop>
            <div class="modal-header">
                <h3 class="modal-title" x-text="produit.nom"></h3>
                <button @click="show = false" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Référence</dt>
                        <dd class="font-medium text-gray-900" x-text="produit.reference"></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Catégorie</dt>
                        <dd class="font-medium text-gray-900" x-text="produit.categorie"></dd>
                    </div>
                    <div class="border-t border-gray-100 pt-3 flex justify-between">
                        <dt class="text-gray-500">Prix d'achat</dt>
                        <dd class="font-medium text-gray-900" x-text="produit.prix_achat"></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Prix de vente</dt>
                        <dd class="font-semibold text-primary-600" x-text="produit.prix_vente"></dd>
                    </div>
                    <div class="border-t border-gray-100 pt-3 flex justify-between">
                        <dt class="text-gray-500">Stock actuel</dt>
                        <dd class="font-medium text-gray-900" x-text="produit.stock"></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Seuil d'alerte</dt>
                        <dd class="font-medium text-gray-900" x-text="produit.seuil_alerte"></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Unité</dt>
                        <dd class="font-medium text-gray-900" x-text="produit.unite"></dd>
                    </div>
                    <div class="border-t border-gray-100 pt-3">
                        <dt class="text-gray-500 mb-1">Description</dt>
                        <dd class="text-gray-700" x-text="produit.description"></dd>
                    </div>
                </dl>
                <div class="pt-4">
                    <button @click="show = false" class="btn-outline w-full justify-center">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    @if(request('action') === 'categories')
    <script>
        document.addEventListener('alpine:init', () => {
            setTimeout(() => window.dispatchEvent(new CustomEvent('open-categories')), 300);
        });
    </script>
    @endif
</x-dashboard-layout>
