<x-dashboard-layout>
    <div class="space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-display font-bold text-gray-900 tracking-tight">Gestion du Stock</h1>
                <p class="text-sm text-gray-500 mt-1">
                    @if($nbAlertes > 0)
                        <span class="text-amber-600 font-medium">⚠️ {{ $nbAlertes }} produit(s) en alerte</span>
                    @else
                        Tous les stocks sont à niveau ✅
                    @endif
                </p>
            </div>
            <div class="flex gap-2">
                @if(auth()->user()->isAdmin())
                <button
                    x-data
                    @click="$dispatch('open-modal', 'entree-stock')"
                    class="btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Entrée stock
                </button>
                @endif
            </div>
        </div>

        {{-- Filtres --}}
        <div class="card p-4">
            <form method="GET" action="{{ route('dashboard.stock.index') }}" class="flex flex-wrap gap-3">
                <div class="relative flex-1 min-w-48">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Nom, référence..." class="form-input pl-9">
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="alerte" value="1" {{ request('alerte') ? 'checked' : '' }}
                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    Alertes uniquement
                </label>
                <button type="submit" class="btn-outline">Filtrer</button>
            </form>
        </div>

        {{-- Table produits --}}
        @if($produits->count() > 0)
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Produit</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 hidden md:table-cell">Référence</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">Stock</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600 hidden sm:table-cell">Seuil</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($produits as $produit)
                        <tr class="hover:bg-gray-50 group transition-colors {{ $produit->isEnAlerte() ? 'bg-amber-50/50' : '' }}">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    @if($produit->isEnAlerte())
                                        <div class="w-2 h-2 bg-amber-400 rounded-full flex-shrink-0 animate-pulse"></div>
                                    @else
                                        <div class="w-2 h-2 bg-emerald-400 rounded-full flex-shrink-0"></div>
                                    @endif
                                    <span class="font-medium text-gray-900">{{ $produit->nom }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-400 font-mono text-xs hidden md:table-cell">
                                {{ $produit->reference ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if($produit->isEnAlerte())
                                    <span class="badge badge-danger">{{ $produit->stock }} {{ $produit->unite }}</span>
                                @else
                                    <span class="font-semibold text-gray-900">{{ $produit->stock }} {{ $produit->unite }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-gray-400 hidden sm:table-cell">
                                {{ $produit->seuil_alerte }} {{ $produit->unite }}
                            </td>
                            @if(auth()->user()->isAdmin())
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button
                                        x-data
                                        @click="$dispatch('ouvrir-entree', { produit_id: '{{ $produit->id }}', nom: '{{ addslashes($produit->nom) }}' })"
                                        class="btn-icon text-emerald-600" title="Entrée stock">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                    </button>
                                    <button
                                        x-data
                                        @click="$dispatch('ouvrir-correction', { produit_id: '{{ $produit->id }}', nom: '{{ addslashes($produit->nom) }}', stock: {{ $produit->stock }} })"
                                        class="btn-icon text-blue-500" title="Correction">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            @else
                            <td class="px-4 py-3 text-right text-gray-300 text-xs">—</td>
                            @endif
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
        <div class="space-y-4">
            <div class="card p-5">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">Aucun produit dans le stock</p>
                        <p class="text-sm text-gray-500 mt-0.5">Suivez ces 3 étapes pour configurer votre gestion de stock.</p>
                    </div>
                </div>
            </div>
            <div class="grid sm:grid-cols-3 gap-4">
                <a href="{{ route('dashboard.produits.index', ['action' => 'categories']) }}" class="card p-5 group hover:border-primary-200 hover:shadow-sm transition-all block">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 rounded-full bg-gray-100 text-gray-500 flex items-center justify-center text-sm font-bold group-hover:bg-primary-600 group-hover:text-white transition-colors">1</div>
                        <span class="text-xs font-medium text-gray-400 group-hover:text-primary-600 transition-colors">Première étape</span>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-1.5">Créer des catégories</h3>
                    <p class="text-sm text-gray-500">Organisez vos produits par famille : soins, colorations, accessoires…</p>
                    <div class="flex items-center gap-1 mt-3 text-xs font-medium text-primary-600">
                        Ouvrir les catégories
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </a>
                <a href="{{ route('dashboard.produits.index') }}" class="card p-5 group hover:border-primary-200 hover:shadow-sm transition-all block">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 rounded-full bg-gray-100 text-gray-500 flex items-center justify-center text-sm font-bold group-hover:bg-primary-600 group-hover:text-white transition-colors">2</div>
                        <span class="text-xs font-medium text-gray-400 group-hover:text-primary-600 transition-colors">Deuxième étape</span>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-1.5">Ajouter les produits</h3>
                    <p class="text-sm text-gray-500">Saisissez votre catalogue avec les prix d'achat, de vente et le seuil d'alerte.</p>
                    <div class="flex items-center gap-1 mt-3 text-xs font-medium text-primary-600">
                        Gérer les produits
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </a>
                <div class="card p-5 ring-1 ring-primary-200 bg-primary-50/20">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 rounded-full bg-primary-600 text-white flex items-center justify-center text-sm font-bold">3</div>
                        <span class="text-xs font-medium text-primary-600">Vous êtes ici</span>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-1.5">Gérer les entrées de stock</h3>
                    <p class="text-sm text-gray-500">Enregistrez les réceptions et ajustez les quantités disponibles.</p>
                    <div class="flex items-center gap-1.5 mt-3 text-xs text-gray-400">
                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Prochaine étape après les produits
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Modal Entrée Stock --}}
    <div x-data="{
            show: false,
            produit_id: '',
            nom: 'Entrée de stock',
            init() {
                window.addEventListener('open-modal', e => { if(e.detail === 'entree-stock') { this.produit_id = ''; this.nom = 'Entrée de stock'; this.show = true; } });
                window.addEventListener('ouvrir-entree', e => { this.produit_id = e.detail.produit_id; this.nom = e.detail.nom; this.show = true });
            }
         }"
         x-show="show"
         x-cloak
         class="modal-backdrop"
         @keydown.escape.window="show = false">
        <div class="modal" x-transition @click.stop>
            <div class="modal-header">
                <h3 class="modal-title">Entrée de stock</h3>
                <button @click="show = false" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" x-bind:action="'{{ url('dashboard/stock') }}/' + produit_id + '/entree'" class="space-y-4">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Produit</label>
                        <template x-if="produit_id">
                            <p class="font-medium text-gray-900" x-text="nom"></p>
                        </template>
                        <template x-if="!produit_id">
                            <select x-model="produit_id" class="form-select" required>
                                <option value="">Choisir un produit...</option>
                                @foreach($tousLesProduits as $p)
                                    <option value="{{ $p->id }}">{{ $p->nom }}</option>
                                @endforeach
                            </select>
                        </template>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quantité à ajouter *</label>
                        <input type="number" name="quantite" required min="1" class="form-input" placeholder="10">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Note</label>
                        <input type="text" name="note" maxlength="200" class="form-input" placeholder="Ex: Livraison fournisseur">
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="show = false" class="btn btn-outline flex-1 justify-center">Annuler</button>
                        <button type="submit" class="btn-primary flex-1 justify-center" :disabled="!produit_id">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Correction Stock --}}
    <div x-data="{
            show: false,
            produit_id: '',
            nom: '',
            stock_actuel: 0,
            init() {
                window.addEventListener('ouvrir-correction', e => { this.produit_id = e.detail.produit_id; this.nom = e.detail.nom; this.stock_actuel = e.detail.stock; this.show = true });
            }
         }"
         x-show="show"
         x-cloak
         class="modal-backdrop"
         @keydown.escape.window="show = false">
        <div class="modal" x-transition @click.stop>
            <div class="modal-header">
                <h3 class="modal-title">Corriger le stock</h3>
                <button @click="show = false" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" x-bind:action="'{{ url('dashboard/stock') }}/' + produit_id + '/correction'" class="space-y-4">
                    @csrf
                    <input type="hidden" name="produit_id" :value="produit_id">
                    <p class="text-sm text-gray-600">Produit : <strong x-text="nom"></strong></p>
                    <p class="text-sm text-gray-600">Stock actuel : <strong x-text="stock_actuel"></strong></p>
                    <div class="form-group">
                        <label class="form-label">Nouveau stock réel *</label>
                        <input type="number" name="stock_corrige" required min="0" class="form-input" :placeholder="stock_actuel">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Motif *</label>
                        <input type="text" name="note" required maxlength="200" class="form-input" placeholder="Ex: Inventaire physique">
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="show = false" class="btn btn-outline flex-1 justify-center">Annuler</button>
                        <button type="submit" class="btn-primary flex-1 justify-center">Corriger</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-dashboard-layout>
