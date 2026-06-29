<x-dashboard-layout>
    <x-slot name="title">Depenses</x-slot>

    <div class="space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-display font-bold text-gray-900 tracking-tight">Depenses</h1>
                <p class="text-sm text-gray-500 mt-1">
                    @if(auth()->user()->isEmploye())
                        Mes depenses enregistrees
                    @else
                        Toutes les depenses
                    @endif
                </p>
            </div>
            <button x-data @click="$dispatch('open-modal', 'add-depense')" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Nouvelle depense
            </button>
        </div>

        @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="alert-danger">{{ session('error') }}</div>
        @endif

        {{-- Total --}}
        <div class="card p-4 flex items-center justify-between">
            <span class="text-sm text-gray-500">Total des depenses</span>
            <span class="text-xl font-bold text-red-600">{{ number_format($totalPeriode, 0, ',', ' ') }} FCFA</span>
        </div>

        {{-- Liste --}}
        @if($depenses->count() > 0)
        <div class="card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Description</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 hidden sm:table-cell">Categorie</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 hidden md:table-cell">Date</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Montant</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($depenses as $depense)
                    <tr class="hover:bg-gray-50 group transition-colors">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-900">{{ $depense->description }}</p>
                            @if($depense->notes)
                            <p class="text-xs text-gray-400">{{ $depense->notes }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 hidden sm:table-cell">
                            <span class="badge badge-secondary text-xs">{{ \App\Models\Depense::categorieLabel($depense->categorie) }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-500 hidden md:table-cell text-xs">
                            {{ $depense->date->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-red-600 font-mono">
                            {{ number_format($depense->montant, 0, ',', ' ') }} F
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1.5">
                                <button x-data
                                        @click="$dispatch('open-edit-depense', {
                                            id: '{{ $depense->id }}',
                                            description: '{{ addslashes($depense->description) }}',
                                            categorie: '{{ $depense->categorie }}',
                                            montant: {{ $depense->montant }},
                                            date: '{{ $depense->date->format('Y-m-d') }}',
                                            notes: '{{ addslashes($depense->notes ?? '') }}'
                                        })"
                                        class="btn-icon text-blue-500" title="Modifier">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <form method="POST" action="{{ route('dashboard.depenses.destroy', $depense) }}"
                                      onsubmit="return confirm('Supprimer cette depense ?')" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon text-red-500" title="Supprimer">
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
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $depenses->links() }}
            </div>
        </div>
        @else
        <div class="card p-12 text-center">
            <div class="text-4xl mb-3">💰</div>
            <p class="font-semibold text-gray-900 mb-1">Aucune depense</p>
            <p class="text-sm text-gray-500">Cliquez sur "Nouvelle depense" pour enregistrer une depense.</p>
        </div>
        @endif
    </div>

    {{-- ═══ MODAL AJOUT ═══ --}}
    <div x-data="{ show: false }"
         @open-modal.window="if ($event.detail === 'add-depense') show = true"
         x-show="show" x-cloak
         class="modal-backdrop"
         @keydown.escape.window="show = false"
         @click.self="show = false">
        <div class="modal max-w-md" x-transition @click.stop>
            <div class="modal-header">
                <h3 class="modal-title">Nouvelle depense</h3>
                <button @click="show = false" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('dashboard.depenses.store') }}" class="space-y-4">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Description *</label>
                        <input type="text" name="description" required maxlength="255" class="form-input"
                               placeholder="Ex: Achat de fournitures...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Categorie *</label>
                        <select name="categorie" required class="form-input">
                            <option value="">Choisir...</option>
                            @foreach(['loyer'=>'Loyer','salaires'=>'Salaires','fournitures'=>'Fournitures','produits'=>'Produits','equipement'=>'Equipement','marketing'=>'Marketing','autres'=>'Autres'] as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Montant (FCFA) *</label>
                        <input type="number" name="montant" required min="1" class="form-input" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date *</label>
                        <input type="date" name="date" required value="{{ now()->toDateString() }}" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="2" maxlength="500" class="form-input resize-none" placeholder="Optionnel..."></textarea>
                    </div>
                    <div class="flex gap-3 pt-1">
                        <button type="button" @click="show = false" class="btn btn-outline flex-1 justify-center">Annuler</button>
                        <button type="submit" class="btn-primary flex-1 justify-center">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ═══ MODAL EDITION ═══ --}}
    <div x-data="{ show: false, edit: {} }"
         @open-edit-depense.window="edit = $event.detail; show = true"
         x-show="show" x-cloak
         class="modal-backdrop"
         @keydown.escape.window="show = false"
         @click.self="show = false">
        <div class="modal max-w-md" x-transition @click.stop>
            <div class="modal-header">
                <h3 class="modal-title">Modifier la depense</h3>
                <button @click="show = false" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" :action="'/dashboard/depenses/' + edit.id" class="space-y-4">
                    @csrf @method('PUT')
                    <div class="form-group">
                        <label class="form-label">Description *</label>
                        <input type="text" name="description" required maxlength="255" class="form-input" x-model="edit.description">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Categorie *</label>
                        <select name="categorie" required class="form-input" x-model="edit.categorie">
                            <option value="">Choisir...</option>
                            @foreach(['loyer'=>'Loyer','salaires'=>'Salaires','fournitures'=>'Fournitures','produits'=>'Produits','equipement'=>'Equipement','marketing'=>'Marketing','autres'=>'Autres'] as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Montant (FCFA) *</label>
                        <input type="number" name="montant" required min="1" class="form-input" x-model.number="edit.montant">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date *</label>
                        <input type="date" name="date" required class="form-input" x-model="edit.date">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="2" maxlength="500" class="form-input resize-none" x-model="edit.notes"></textarea>
                    </div>
                    <div class="flex gap-3 pt-1">
                        <button type="button" @click="show = false" class="btn btn-outline flex-1 justify-center">Annuler</button>
                        <button type="submit" class="btn-primary flex-1 justify-center">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-dashboard-layout>
