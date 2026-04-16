@extends('layouts.admin')
@section('page-title', "Plans d'abonnement")

@section('content')
<div x-data="plansManager()" class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="page-title">Plans d'abonnement</h1>
            <p class="page-subtitle">Gérez les formules proposées aux instituts.</p>
        </div>
        <button @click="openCreate()" class="btn-primary">+ Nouveau plan</button>
    </div>

    <div class="card overflow-hidden">
        <table class="table-auto">
            <thead>
            <tr>
                <th>Nom</th>
                <th>Slug</th>
                <th>Prix / mois</th>
                <th>Limites</th>
                <th>Tarifs</th>
                <th>Actif</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($plans as $plan)
            <tr>
                <td>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-medium text-gray-900">{{ $plan->nom }}</span>
                        @if($plan->mis_en_avant)
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                Recommandé
                            </span>
                        @endif
                        @if($plan->offreLancementActive())
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-orange-100 text-orange-700">
                                🔥 Offre lancement
                            </span>
                        @endif
                    </div>
                    @if($plan->description)
                        <div class="text-xs text-gray-400 mt-0.5 max-w-xs truncate">{{ $plan->description }}</div>
                    @endif
                </td>
                <td class="text-sm text-gray-500 font-mono">{{ $plan->slug }}</td>
                <td class="font-semibold">
                    {{ number_format($plan->prix, 0, ',', ' ') }} <span class="text-gray-400 font-normal text-xs">FCFA</span>
                    @if($plan->offreLancementActive())
                        <div class="flex items-center gap-1 mt-0.5">
                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700 text-[9px] font-bold">
                                🔥 Lancement
                            </span>
                            <span class="text-xs text-emerald-600 font-semibold">{{ number_format($plan->prix_lancement, 0, ',', ' ') }}</span>
                        </div>
                        <div class="text-[10px] text-gray-400">jusqu'au {{ $plan->fin_offre_lancement->format('d/m/Y') }}</div>
                    @endif
                </td>
                <td class="text-sm text-gray-600">
                    {{ $plan->max_employes ?? '∞' }} employés · {{ $plan->max_instituts ?? '∞' }} instituts
                </td>
                <td class="text-xs text-gray-500">
                    <div>1 an : {{ number_format($plan->prixPourPeriode('annuel'), 0, ',', ' ') }} (-10%)</div>
                    <div>3 ans : {{ number_format($plan->prixPourPeriode('triennal'), 0, ',', ' ') }} (-20%)</div>
                </td>
                <td>
                    <span class="badge {{ $plan->actif ? 'badge-success' : 'bg-gray-100 text-gray-500' }} text-xs">
                        {{ $plan->actif ? 'Actif' : 'Inactif' }}
                    </span>
                </td>
                <td>
                    <div class="flex items-center gap-2">
                        @if(!$plan->mis_en_avant)
                        <form action="{{ route('admin.plans.featurer', $plan) }}" method="POST">
                            @csrf
                            <button type="submit" title="Mettre en avant"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-amber-200 text-amber-700 bg-amber-50 hover:bg-amber-100 transition">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                Mettre en avant
                            </button>
                        </form>
                        @endif
                        <button @click='openEdit(@json($plan))'
                            class="btn-outline btn-sm inline-flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Modifier
                        </button>
                        <form action="{{ route('admin.plans.destroy', $plan) }}" method="POST"
                              onsubmit="return confirm('Désactiver ce plan ?')">
                            @csrf @method('DELETE')
                            <button class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-red-200 text-red-600 bg-red-50 hover:bg-red-100 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                Désactiver
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center py-10 text-gray-400">Aucun plan.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal créer / modifier --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
         @keydown.escape.window="open = false">
        <div @click.outside="open = false" class="bg-white rounded-2xl shadow-xl w-full max-w-md p-7">
            <h2 class="font-bold text-gray-900 text-lg mb-5" x-text="editing ? 'Modifier le plan' : 'Nouveau plan'"></h2>

            <form :action="editing ? '{{ route('admin.plans.index') }}/' + form.id : '{{ route('admin.plans.store') }}'" method="POST" class="space-y-4">
                @csrf
                <input x-show="editing" type="hidden" name="_method" value="PUT">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Nom <span class="text-red-500">*</span></label>
                        <input type="text" name="nom" x-model="form.nom" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">Slug <span class="text-red-500">*</span></label>
                        <input type="text" name="slug" x-model="form.slug" class="form-input" :readonly="editing" required>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Prix mensuel (FCFA) <span class="text-red-500">*</span></label>
                        <input type="number" name="prix" x-model="form.prix" min="0" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">Ordre</label>
                        <input type="number" name="ordre" x-model="form.ordre" min="0" class="form-input" required>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Max employés <span class="text-xs text-gray-400">(vide = illimité)</span></label>
                        <input type="number" name="max_employes" x-model="form.max_employes" min="1" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Max instituts <span class="text-xs text-gray-400">(vide = illimité)</span></label>
                        <input type="number" name="max_instituts" x-model="form.max_instituts" min="1" class="form-input">
                    </div>
                </div>
                <div>
                    <label class="form-label">Description</label>
                    <textarea name="description" x-model="form.description" rows="2" class="form-input resize-none"></textarea>
                </div>

                {{-- Offre de lancement --}}
                <div class="border-t border-gray-100 pt-4 mt-2">
                    <p class="text-xs font-bold text-amber-600 uppercase tracking-wide mb-3 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/></svg>
                        Offre de lancement
                    </p>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Prix lancement <span class="text-xs text-gray-400">(FCFA)</span></label>
                            <input type="number" name="prix_lancement" x-model="form.prix_lancement" min="0" class="form-input" placeholder="Vide = pas d'offre">
                        </div>
                        <div>
                            <label class="form-label">Fin de l'offre</label>
                            <input type="date" name="fin_offre_lancement" x-model="form.fin_offre_lancement" class="form-input"
                                   min="{{ now()->format('Y-m-d') }}">
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                        <input type="checkbox" name="mis_en_avant" value="1" :checked="form.mis_en_avant" class="rounded"> Mis en avant
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                        <input type="checkbox" name="actif" value="1" :checked="form.actif" class="rounded"> Actif
                    </label>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="open = false" class="btn-secondary">Annuler</button>
                    <button class="btn-primary" type="submit" x-text="editing ? 'Enregistrer' : 'Créer'"></button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function plansManager() {
    return {
        open: false,
        editing: false,
        form: { id: null, nom: '', slug: '', prix: '', max_employes: '', max_instituts: '', description: '', mis_en_avant: false, actif: true, ordre: 0, prix_lancement: '', fin_offre_lancement: '' },
        openCreate() { this.editing = false; this.form = { id: null, nom: '', slug: '', prix: '', max_employes: '', max_instituts: '', description: '', mis_en_avant: false, actif: true, ordre: 0, prix_lancement: '', fin_offre_lancement: '' }; this.open = true; },
        openEdit(plan) { this.editing = true; this.form = { ...plan, mis_en_avant: !!plan.mis_en_avant, actif: !!plan.actif, prix_lancement: plan.prix_lancement || '', fin_offre_lancement: plan.fin_offre_lancement ? plan.fin_offre_lancement.split('T')[0] : '' }; this.open = true; }
    }
}
</script>
@endpush
@endsection
