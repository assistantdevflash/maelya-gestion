@extends('layouts.admin')
@section('page-title', 'Offres promotionnelles')

@section('content')
<div x-data="offresManager()" class="space-y-6">

    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <h1 class="page-title">Offres promotionnelles</h1>
            <p class="page-subtitle">Créez et gérez vos offres : lancement, fin d'année, Pâques, etc.</p>
        </div>
        <button @click="openCreate()" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Nouvelle offre
        </button>
    </div>

    {{-- Statistiques --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="card p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center text-emerald-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $offres->where('actif', true)->filter(fn($o) => $o->estActive())->count() }}</p>
                    <p class="text-xs text-gray-500">Actives maintenant</p>
                </div>
            </div>
        </div>
        <div class="card p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center text-amber-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $offres->where('actif', true)->filter(fn($o) => $o->date_debut->isFuture())->count() }}</p>
                    <p class="text-xs text-gray-500">Programmées</p>
                </div>
            </div>
        </div>
        <div class="card p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center text-gray-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $offres->filter(fn($o) => $o->date_fin->lt(today()))->count() }}</p>
                    <p class="text-xs text-gray-500">Expirées</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Tableau des offres --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-auto">
                <thead>
                <tr>
                    <th>Offre</th>
                    <th>Réduction</th>
                    <th>Période</th>
                    <th>Plans concernés</th>
                    <th>Statut</th>
                    <th>Priorité</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($offres as $offre)
                <tr class="{{ $offre->date_fin->lt(today()) ? 'opacity-50' : '' }}">
                    <td>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-gradient-to-r {{ $offre->badge_class }} text-white text-[10px] font-bold">
                                {{ $offre->badge_texte }}
                            </span>
                        </div>
                        <p class="font-medium text-gray-900 mt-1">{{ $offre->nom }}</p>
                        @if($offre->description)
                            <p class="text-xs text-gray-400 mt-0.5 max-w-xs truncate">{{ $offre->description }}</p>
                        @endif
                    </td>
                    <td>
                        <span class="text-lg font-bold text-emerald-600">{{ $offre->reduction_texte }}</span>
                        <p class="text-[10px] text-gray-400">{{ $offre->type_reduction === 'pourcentage' ? 'Pourcentage' : 'Montant fixe' }}</p>
                    </td>
                    <td class="text-sm">
                        <div class="text-gray-600">{{ $offre->date_debut->format('d/m/Y') }}</div>
                        <div class="text-gray-400 text-xs">au {{ $offre->date_fin->format('d/m/Y') }}</div>
                        @if($offre->estActive())
                            <span class="text-[10px] text-emerald-600 font-medium">J-{{ (int) today()->diffInDays($offre->date_fin) }}</span>
                        @endif
                    </td>
                    <td class="text-sm">
                        @if(empty($offre->plans_concernes))
                            <span class="text-xs text-gray-500 italic">Tous les plans</span>
                        @else
                            <div class="flex flex-wrap gap-1">
                                @foreach($plans->whereIn('id', $offre->plans_concernes) as $plan)
                                    <span class="inline-flex px-2 py-0.5 rounded bg-primary-50 text-primary-700 text-[10px] font-medium">{{ $plan->nom }}</span>
                                @endforeach
                            </div>
                        @endif
                        @if(!empty($offre->periodes_concernees))
                            <div class="flex flex-wrap gap-1 mt-1">
                                @foreach($offre->periodes_concernees as $p)
                                    <span class="inline-flex px-1.5 py-0.5 rounded bg-gray-100 text-gray-500 text-[9px] font-medium">{{ ucfirst($p) }}</span>
                                @endforeach
                            </div>
                        @else
                            <p class="text-[10px] text-gray-400 mt-0.5">Toutes périodes</p>
                        @endif
                    </td>
                    <td>
                        @if($offre->estActive())
                            <span class="badge badge-success text-xs">Active</span>
                        @elseif(!$offre->actif)
                            <span class="badge bg-gray-100 text-gray-500 text-xs">Désactivée</span>
                        @elseif($offre->date_debut->isFuture())
                            <span class="badge bg-amber-100 text-amber-700 text-xs">Programmée</span>
                        @elseif($offre->date_fin->lt(today()))
                            <span class="badge bg-red-100 text-red-600 text-xs">Expirée</span>
                        @else
                            <span class="badge bg-gray-100 text-gray-500 text-xs">Inactive</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-gray-100 text-sm font-bold text-gray-700">{{ $offre->priorite }}</span>
                    </td>
                    <td>
                        <div class="flex items-center gap-2">
                            <button type="button" @click='openEdit(@json($offre))'
                                class="btn-outline btn-sm inline-flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Modifier
                            </button>
                            <form action="{{ route('admin.offres.toggle', $offre) }}" method="POST">
                                @csrf @method('PATCH')
                                <button class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border transition
                                    {{ $offre->actif ? 'border-amber-200 text-amber-700 bg-amber-50 hover:bg-amber-100' : 'border-emerald-200 text-emerald-700 bg-emerald-50 hover:bg-emerald-100' }}">
                                    {{ $offre->actif ? 'Désactiver' : 'Activer' }}
                                </button>
                            </form>
                            <form action="{{ route('admin.offres.destroy', $offre) }}" method="POST"
                                  onsubmit="return confirm('Supprimer cette offre ?')">
                                @csrf @method('DELETE')
                                <button class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-red-200 text-red-600 bg-red-50 hover:bg-red-100 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Supprimer
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-10">
                        <div class="text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>
                            Aucune offre promotionnelle. Cliquez sur « Nouvelle offre » pour commencer.
                        </div>
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal créer / modifier --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
         @keydown.escape.window="open = false">
        <div @click.outside="open = false" class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="px-6 pt-6 pb-4 border-b border-gray-100 dark:border-slate-700 flex items-center justify-between">
                <h2 class="font-bold text-gray-900 dark:text-white text-lg" x-text="editing ? 'Modifier l\'offre' : 'Nouvelle offre promotionnelle'"></h2>
                <button @click="open = false" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form :action="editing ? '{{ url('admin/offres') }}/' + form.id : '{{ route('admin.offres.store') }}'" method="POST" class="px-6 py-5 space-y-4">
                @csrf
                <template x-if="editing">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                {{-- Nom + Badge --}}
                <div>
                    <label class="form-label">Nom de l'offre <span class="text-red-500">*</span></label>
                    <input type="text" name="nom" x-model="form.nom" class="form-input" placeholder="Ex: Offre de Pâques, Offre fin d'année..." required>
                </div>

                <div>
                    <label class="form-label">Description <span class="text-xs text-gray-400">(facultatif)</span></label>
                    <textarea name="description" x-model="form.description" rows="2" class="form-input resize-none" placeholder="Détails de l'offre..."></textarea>
                </div>

                {{-- Badge --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Texte du badge <span class="text-red-500">*</span></label>
                        <input type="text" name="badge_texte" x-model="form.badge_texte" class="form-input" placeholder="🔥 Offre spéciale" required>
                    </div>
                    <div>
                        <label class="form-label">Couleur du badge</label>
                        <select name="badge_couleur" x-model="form.badge_couleur" class="form-input">
                            @foreach(\App\Models\OffrePromotionnelle::couleursDisponibles() as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Aperçu du badge --}}
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-400">Aperçu :</span>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-white text-[10px] font-bold bg-gradient-to-r"
                          :class="{
                              'from-amber-400 to-orange-500': form.badge_couleur === 'amber',
                              'from-emerald-400 to-green-500': form.badge_couleur === 'emerald',
                              'from-rose-400 to-pink-500': form.badge_couleur === 'rose',
                              'from-blue-400 to-indigo-500': form.badge_couleur === 'blue',
                              'from-purple-400 to-violet-500': form.badge_couleur === 'purple',
                              'from-red-400 to-orange-500': form.badge_couleur === 'red',
                              'from-indigo-400 to-blue-500': form.badge_couleur === 'indigo',
                              'from-cyan-400 to-teal-500': form.badge_couleur === 'cyan',
                          }"
                          x-text="form.badge_texte || 'Offre spéciale'"></span>
                </div>

                {{-- Réduction --}}
                <div class="border-t border-gray-100 dark:border-slate-700 pt-4">
                    <p class="text-xs font-bold text-emerald-600 uppercase tracking-wide mb-3 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Réduction
                    </p>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Type <span class="text-red-500">*</span></label>
                            <select name="type_reduction" x-model="form.type_reduction" class="form-input" required>
                                <option value="pourcentage">Pourcentage (%)</option>
                                <option value="montant_fixe">Montant fixe (FCFA)</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Valeur <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="number" name="valeur_reduction" x-model="form.valeur_reduction" min="1" class="form-input pr-12" required>
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-medium" x-text="form.type_reduction === 'pourcentage' ? '%' : 'FCFA'"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Dates --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Date de début <span class="text-red-500">*</span></label>
                        <input type="date" name="date_debut" x-model="form.date_debut" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">Date de fin <span class="text-red-500">*</span></label>
                        <input type="date" name="date_fin" x-model="form.date_fin" class="form-input" required>
                    </div>
                </div>

                {{-- Conditions --}}
                <div class="border-t border-gray-100 dark:border-slate-700 pt-4">
                    <p class="text-xs font-bold text-blue-600 uppercase tracking-wide mb-3 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                        Conditions d'application
                    </p>

                    <div class="space-y-3">
                        <div>
                            <label class="form-label">Plans concernés <span class="text-xs text-gray-400">(vide = tous)</span></label>
                            <div class="flex flex-wrap gap-2 mt-1">
                                @foreach($plans as $plan)
                                <label class="flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 hover:border-primary-300 cursor-pointer transition-colors text-sm">
                                    <input type="checkbox" name="plans_concernes[]" value="{{ $plan->id }}"
                                           :checked="form.plans_concernes && form.plans_concernes.includes('{{ $plan->id }}')"
                                           class="rounded text-primary-600">
                                    {{ $plan->nom }}
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Périodes concernées <span class="text-xs text-gray-400">(vide = toutes)</span></label>
                            <div class="flex flex-wrap gap-2 mt-1">
                                @foreach(['mensuel' => 'Mensuel', 'annuel' => 'Annuel (1 an)', 'triennal' => 'Triennal (3 ans)'] as $key => $label)
                                <label class="flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 hover:border-primary-300 cursor-pointer transition-colors text-sm">
                                    <input type="checkbox" name="periodes_concernees[]" value="{{ $key }}"
                                           :checked="form.periodes_concernees && form.periodes_concernees.includes('{{ $key }}')"
                                           class="rounded text-primary-600">
                                    {{ $label }}
                                </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Priorité + Actif --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Priorité <span class="text-xs text-gray-400">(plus élevé = prioritaire)</span></label>
                        <input type="number" name="priorite" x-model="form.priorite" min="0" max="100" class="form-input" required>
                    </div>
                    <div class="flex items-end pb-1">
                        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                            <input type="checkbox" name="actif" value="1" :checked="form.actif" class="rounded text-primary-600">
                            Offre active
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-3 border-t border-gray-100 dark:border-slate-700">
                    <button type="button" @click="open = false" class="btn-secondary">Annuler</button>
                    <button class="btn-primary" type="submit" x-text="editing ? 'Enregistrer' : 'Créer l\'offre'"></button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function offresManager() {
    return {
        open: false,
        editing: false,
        form: {
            id: null,
            nom: '',
            description: '',
            type_reduction: 'pourcentage',
            valeur_reduction: '',
            date_debut: '',
            date_fin: '',
            actif: true,
            plans_concernes: null,
            periodes_concernees: null,
            badge_texte: '',
            badge_couleur: 'amber',
            priorite: 0,
        },
        openCreate() {
            this.editing = false;
            this.form = {
                id: null,
                nom: '',
                description: '',
                type_reduction: 'pourcentage',
                valeur_reduction: '',
                date_debut: new Date().toISOString().split('T')[0],
                date_fin: '',
                actif: true,
                plans_concernes: null,
                periodes_concernees: null,
                badge_texte: '',
                badge_couleur: 'amber',
                priorite: 0,
            };
            this.open = true;
        },
        openEdit(offre) {
            this.editing = true;
            this.form = {
                ...offre,
                actif: !!offre.actif,
                date_debut: offre.date_debut ? offre.date_debut.split('T')[0] : '',
                date_fin: offre.date_fin ? offre.date_fin.split('T')[0] : '',
                plans_concernes: offre.plans_concernes || [],
                periodes_concernees: offre.periodes_concernees || [],
            };
            this.open = true;
        }
    }
}
</script>
@endpush
@endsection
