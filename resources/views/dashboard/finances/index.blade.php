<x-dashboard-layout>
    <div class="space-y-5">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-display font-bold text-gray-900 tracking-tight">Finances</h1>
                <p class="text-sm text-gray-500 mt-1">Suivi des revenus et dépenses.</p>
            </div>
            <div class="flex gap-2">
                <button x-data @click="$dispatch('open-depense-modal')" class="btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Ajouter dépense
                </button>
                <a href="{{ route('dashboard.finances.rapport') }}" class="btn-outline">Rapport PDF</a>
            </div>
        </div>

        {{-- Filtres période --}}
        <div class="card p-4">
            <form method="GET" action="{{ route('dashboard.finances.index') }}" class="flex flex-wrap gap-3 items-end"
                  x-data="{ debut: '{{ $debut->format('Y-m-d') }}' }">
                <input type="hidden" name="periode" value="custom">
                <div class="form-group mb-0">
                    <label class="form-label text-xs">Début</label>
                    <input type="date" name="debut" x-model="debut" value="{{ $debut->format('Y-m-d') }}" class="form-input">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label text-xs">Fin</label>
                    <input type="date" name="fin" :min="debut" value="{{ $fin->format('Y-m-d') }}" class="form-input">
                </div>
                <button type="submit" class="btn-outline">Appliquer</button>
                <div class="flex gap-1 ml-auto">
                    @foreach([
                        ['today', "Aujourd'hui"],
                        ['week', 'Cette semaine'],
                        ['month', 'Ce mois'],
                    ] as [$period, $label])
                        <a href="{{ route('dashboard.finances.index', ['periode' => $period]) }}"
                           class="btn text-xs px-3 py-1.5 {{ request('periode') === $period ? 'bg-primary-100 text-primary-700' : 'text-gray-500 hover:bg-gray-100' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </form>
        </div>

        {{-- KPI résumé --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="stat-card">
                <p class="text-xs text-gray-500 mb-1">Chiffre d'affaires</p>
                <p class="text-xl font-bold text-emerald-600">{{ number_format($totalVentes, 0, ',', ' ') }}</p>
                <p class="text-xs text-gray-400">FCFA</p>
            </div>
            <div class="stat-card">
                <p class="text-xs text-gray-500 mb-1">Total dépenses</p>
                <p class="text-xl font-bold text-red-600">{{ number_format($totalDepenses, 0, ',', ' ') }}</p>
                <p class="text-xs text-gray-400">FCFA</p>
            </div>
            <div class="stat-card">
                <p class="text-xs text-gray-500 mb-1">Bénéfice net</p>
                <p class="text-xl font-bold {{ $benefice >= 0 ? 'text-primary-600' : 'text-red-600' }}">{{ number_format($benefice, 0, ',', ' ') }}</p>
                <p class="text-xs text-gray-400">FCFA</p>
            </div>
            <div class="stat-card">
                <p class="text-xs text-gray-500 mb-1">Nb ventes</p>
                <p class="text-xl font-bold text-gray-900">{{ $nbVentes }}</p>
                <p class="text-xs text-gray-400">validées</p>
            </div>
        </div>

        {{-- Dépenses --}}
        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-bold text-gray-900">Dépenses</h2>
                <div class="flex gap-2">
                    <a href="{{ route('dashboard.finances.export-depenses') . (request()->getQueryString() ? '?'.request()->getQueryString() : '') }}" class="btn-outline text-xs px-3 py-1.5">
                        ↓ CSV
                    </a>
                </div>
            </div>

            @if($depenses->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Description</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 hidden md:table-cell">Catégorie</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 hidden sm:table-cell">Date</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">Montant</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($depenses as $depense)
                        <tr class="hover:bg-gray-50 group">
                            <td class="px-4 py-3 text-gray-900 font-medium">{{ $depense->description }}</td>
                            <td class="px-4 py-3 hidden md:table-cell">
                                <span class="badge badge-secondary text-xs">{{ \App\Models\Depense::categorieLabel($depense->categorie) }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs hidden sm:table-cell">{{ $depense->date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-red-600">
                                {{ number_format($depense->montant, 0, ',', ' ') }} FCFA
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100">
                                    <button
                                        x-data
                                        @click="$dispatch('edit-depense', { id: '{{ $depense->id }}', description: '{{ addslashes($depense->description) }}', categorie: '{{ $depense->categorie }}', montant: {{ $depense->montant }}, date: '{{ $depense->date->format('Y-m-d') }}', notes: '{{ addslashes($depense->notes ?? '') }}' })"
                                        class="btn-icon">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <form id="form-depense-{{ $depense->id }}" method="POST" action="{{ route('dashboard.depenses.destroy', $depense) }}">
                                        @csrf @method('DELETE')
                                        <button type="button" class="btn-icon text-red-400 hover:text-red-600"
                                                onclick="window.dispatchEvent(new CustomEvent('confirm-action',{detail:{formId:'form-depense-{{ $depense->id }}',title:'Supprimer cette dépense',message:'Cette action est irréversible.',confirmLabel:'Supprimer',confirmClass:'!bg-red-600 hover:!bg-red-700',danger:true}}))">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $depenses->withQueryString()->links() }}
            </div>
            @else
            <div class="p-10 text-center text-gray-400 text-sm">
                Aucune dépense sur cette période.
            </div>
            @endif
        </div>
    </div>

    {{-- Modal dépense --}}
    <div x-data="{
            show: false,
            formAction: '{{ route('dashboard.depenses.store') }}',
            method: 'POST',
            form: { description: '', categorie: 'autres', montant: '', date: '{{ today()->format('Y-m-d') }}', notes: '' },
            init() {
                window.addEventListener('open-depense-modal', () => {
                    this.formAction = '{{ route('dashboard.depenses.store') }}';
                    this.method = 'POST';
                    this.form = { description: '', categorie: 'autres', montant: '', date: '{{ today()->format('Y-m-d') }}', notes: '' };
                    this.show = true;
                });
                window.addEventListener('edit-depense', (e) => {
                    this.formAction = `/dashboard/depenses/${e.detail.id}`;
                    this.method = 'PUT';
                    this.form = { description: e.detail.description, categorie: e.detail.categorie, montant: e.detail.montant, date: e.detail.date, notes: e.detail.notes };
                    this.show = true;
                });
            }
         }"
         x-show="show"
         x-cloak
         class="modal-backdrop"
         @keydown.escape.window="show = false">
        <div class="modal" x-transition @click.stop>
            <div class="modal-header">
                <h3 class="modal-title">Dépense</h3>
                <button @click="show = false" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <form :action="formAction" method="POST" class="space-y-4">
                    @csrf
                    <template x-if="method === 'PUT'">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div class="form-group">
                        <label class="form-label">Description *</label>
                        <input type="text" name="description" required maxlength="200"
                               x-model="form.description" class="form-input"
                               placeholder="Achat produits soins">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Catégorie *</label>
                        <select name="categorie" required x-model="form.categorie" class="form-select">
                            <option value="produits">Achat produits</option>
                            <option value="salaires">Salaires</option>
                            <option value="loyer">Loyer</option>
                            <option value="fournitures">Fournitures (eau/élec)</option>
                            <option value="equipement">Matériel / équipement</option>
                            <option value="marketing">Marketing</option>
                            <option value="autres">Autres charges</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Montant (FCFA) *</label>
                            <input type="number" name="montant" required min="1" step="1"
                                   x-model="form.montant" class="form-input" placeholder="15000">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date *</label>
                            <input type="date" name="date" required
                                   x-model="form.date" class="form-input">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="2" maxlength="500"
                                  x-model="form.notes" class="form-textarea"></textarea>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="show = false" class="btn btn-outline flex-1 justify-center">Annuler</button>
                        <button type="submit" class="btn-primary flex-1 justify-center">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-dashboard-layout>
