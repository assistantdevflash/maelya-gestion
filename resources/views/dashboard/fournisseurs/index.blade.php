<x-dashboard-layout>
    <x-slot name="title">Fournisseurs</x-slot>

    <div class="space-y-4" x-data="{ showForm: false, editing: null }">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-display font-bold text-gray-900">Fournisseurs</h1>
                <p class="text-sm text-gray-500 mt-1">{{ $fournisseurs->total() }} fournisseur(s)</p>
            </div>
            <button @click="showForm = true; editing = null" class="btn-primary">+ Nouveau fournisseur</button>
        </div>

        @if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif

        <div class="card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Nom</th>
                        <th class="px-4 py-3 text-left">Contact</th>
                        <th class="px-4 py-3 text-left">Téléphone</th>
                        <th class="px-4 py-3 text-left">Email</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($fournisseurs as $f)
                        <tr class="{{ $f->actif ? '' : 'opacity-50' }}">
                            <td class="px-4 py-3 font-semibold">{{ $f->nom }}</td>
                            <td class="px-4 py-3 text-xs text-gray-600">{{ $f->contact_principal ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs">{{ $f->telephone ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs">{{ $f->email ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <button @click="showForm = true; editing = {{ Js::from($f->toArray()) }}" class="text-primary-600 text-xs">Éditer</button>
                                <form method="POST" action="{{ route('dashboard.fournisseurs.destroy', $f) }}" class="inline" onsubmit="return confirm('Supprimer ?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 text-xs ml-2">Suppr.</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-gray-400">Aucun fournisseur</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $fournisseurs->links() }}

        {{-- Modal form --}}
        <div x-show="showForm" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" @click="showForm = false"></div>
            <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-2xl w-full max-w-lg p-6" @click.stop>
                <h3 class="font-bold text-gray-900 dark:text-gray-100 mb-4" x-text="editing ? 'Modifier fournisseur' : 'Nouveau fournisseur'"></h3>
                <form :action="editing ? '{{ url('dashboard/fournisseurs') }}/' + editing.id : '{{ route('dashboard.fournisseurs.store') }}'" method="POST" class="space-y-3">
                    @csrf
                    <template x-if="editing">@method('PUT')</template>
                    <div><label class="form-label">Nom *</label><input type="text" name="nom" :value="editing?.nom" required class="form-input"></div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="form-label">Contact</label><input type="text" name="contact_principal" :value="editing?.contact_principal" class="form-input"></div>
                        <div><label class="form-label">Téléphone</label><input type="text" name="telephone" :value="editing?.telephone" class="form-input"></div>
                    </div>
                    <div><label class="form-label">Email</label><input type="email" name="email" :value="editing?.email" class="form-input"></div>
                    <div><label class="form-label">Adresse</label><input type="text" name="adresse" :value="editing?.adresse" class="form-input"></div>
                    <div><label class="form-label">Notes</label><textarea name="notes" rows="2" class="form-input" x-text="editing?.notes"></textarea></div>
                    <template x-if="editing">
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="actif" value="1" :checked="editing?.actif"> Actif</label>
                    </template>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="showForm = false" class="btn-outline flex-1 justify-center">Annuler</button>
                        <button type="submit" class="btn-primary flex-1 justify-center">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-dashboard-layout>
