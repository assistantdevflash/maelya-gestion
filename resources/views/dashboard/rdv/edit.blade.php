<x-dashboard-layout>
<div class="max-w-2xl mx-auto space-y-5">

    <div>
        <a href="{{ route('dashboard.rdv.show', $rdv) }}" class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-700 mb-3 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Retour
        </a>
        <h1 class="page-title">Modifier le rendez-vous</h1>
        <p class="page-subtitle">{{ $rdv->client_nom }} · {{ $rdv->debut_le->format('d/m/Y à H\hi') }}</p>
    </div>

    <form method="POST" action="{{ route('dashboard.rdv.update', $rdv) }}"
          x-data="rdvForm({{ $prestations->toJson() }}, {{ $rdv->prestations->pluck('id')->toJson() }}, {{ $rdv->duree_minutes }})"
          class="space-y-4">
        @csrf
        @method('PATCH')

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-700 space-y-1">
            @foreach($errors->all() as $e)<p>• {{ $e }}</p>@endforeach
        </div>
        @endif

        {{-- CLIENT --}}
        <div class="card p-5 space-y-4">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Client</p>
            <div>
                <label class="form-label">Changer de client (optionnel)</label>
                <select name="client_id" class="form-input"
                        x-on:change="fillClientFromSelect($el)"
                        x-init="$nextTick(() => { if ($el.value) fillClientFromSelect($el) })">
                    <option value="">— Conserver les informations saisies —</option>
                    @foreach($clients as $c)
                    <option value="{{ $c->id }}"
                            data-nom="{{ $c->prenom }} {{ $c->nom }}"
                            data-tel="{{ $c->telephone ?? '' }}"
                            data-email="{{ $c->email ?? '' }}"
                            {{ old('client_id', $rdv->client_id) == $c->id ? 'selected' : '' }}>
                        {{ $c->prenom }} {{ $c->nom }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Nom <span class="text-red-500">*</span></label>
                    <input type="text" name="client_nom" x-model="clientNom" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Téléphone</label>
                    <input type="tel" name="client_telephone" x-model="clientTel" class="form-input">
                </div>
            </div>
            <div>
                <label class="form-label">E-mail</label>
                <input type="email" name="client_email" x-model="clientEmail" class="form-input">
            </div>
        </div>

        {{-- DATE / HEURE --}}
        <div class="card p-5 space-y-4">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Date &amp; heure</p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Date <span class="text-red-500">*</span></label>
                    <input type="date" name="debut_date" required
                           value="{{ old('debut_date', $rdv->debut_le->format('Y-m-d')) }}"
                           class="form-input">
                </div>
                <div>
                    <label class="form-label">Heure <span class="text-red-500">*</span></label>
                    <input type="time" name="debut_heure" required
                           value="{{ old('debut_heure', $rdv->debut_le->format('H:i')) }}"
                           class="form-input">
                </div>
            </div>
            <div>
                <label class="form-label">Durée (minutes)</label>
                <input type="number" name="duree_minutes" min="5" max="480" step="5"
                       x-model="dureeMinutes" class="form-input w-32">
            </div>
        </div>

        {{-- PRESTATIONS --}}
        <div class="card p-5 space-y-3">
            <p class="text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Prestations</p>
            @if($prestations->isNotEmpty())
            {{-- Hidden inputs pour la soumission --}}
            <template x-for="id in prestationsIds" :key="'hi-'+id">
                <input type="hidden" name="prestations[]" :value="id">
            </template>

            {{-- Chips des prestations sélectionnées --}}
            <div x-show="prestationsIds.length > 0" x-cloak class="flex flex-wrap gap-1.5">
                <template x-for="id in prestationsIds" :key="'chip-'+id">
                    <span class="inline-flex items-center gap-1 pl-2.5 pr-1 py-1 rounded-full text-xs font-medium bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300 border border-primary-200 dark:border-primary-700/60">
                        <span x-text="getPrestationNom(id)"></span>
                        <button type="button" @click="togglePrestation(id, getPrestationDuree(id))"
                                class="ml-0.5 p-0.5 rounded-full hover:bg-primary-200 dark:hover:bg-primary-800 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </span>
                </template>
            </div>

            {{-- Barre de recherche + dropdown --}}
            <div @click.outside="prestationOpen = false" class="relative">
                <svg class="absolute left-3 top-3.5 w-4 h-4 text-gray-400 pointer-events-none z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" x-model="recherche"
                       @focus="prestationOpen = true"
                       @input="prestationOpen = true"
                       placeholder="Cliquez pour choisir ou tapez pour rechercher…"
                       class="form-input pl-9 text-sm"
                       autocomplete="off">

                <div x-show="prestationOpen"
                     class="absolute z-50 w-full mt-1 rounded-xl border border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-800 shadow-xl max-h-56 overflow-y-auto">
                    <template x-for="p in prestationsFiltrees" :key="p.id">
                        <label class="flex items-center gap-3 px-3 py-2.5 cursor-pointer transition-colors border-b border-gray-50 dark:border-slate-700/50 last:border-0"
                               :class="prestationsIds.includes(String(p.id))
                                   ? 'bg-primary-50 dark:bg-primary-900/30'
                                   : 'hover:bg-gray-50 dark:hover:bg-slate-700/40'">
                            <input type="checkbox"
                                   :checked="prestationsIds.includes(String(p.id))"
                                   @change="togglePrestation(String(p.id), p.duree || 0)"
                                   class="accent-primary-600 w-4 h-4 flex-shrink-0">
                            <span class="flex-1 min-w-0">
                                <span class="block text-sm font-medium text-gray-900 dark:text-slate-100" x-text="p.nom"></span>
                                <span x-show="p.duree" class="text-xs text-gray-400 dark:text-slate-500" x-text="p.duree + ' min'"></span>
                            </span>
                            <svg x-show="prestationsIds.includes(String(p.id))"
                                 class="w-4 h-4 text-primary-500 dark:text-primary-400 flex-shrink-0"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </label>
                    </template>
                    <p x-show="prestationsFiltrees.length === 0"
                       class="text-sm text-center text-gray-400 dark:text-slate-500 py-3">Aucune prestation trouvée.</p>
                </div>
            </div>
            @endif
            <div>
                <label class="form-label">Prestation libre (texte)</label>
                <input type="text" name="prestation_libre"
                       value="{{ old('prestation_libre', $rdv->prestation_libre) }}"
                       class="form-input">
            </div>
        </div>

        {{-- DÉTAILS --}}
        <div class="card p-5 space-y-4">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Détails</p>
            @if($employes->isNotEmpty())
            <div>
                <label class="form-label">Employé(e) assigné(e)</label>
                <select name="employe_id" class="form-input">
                    <option value="">— Non assigné —</option>
                    @foreach($employes as $e)
                    <option value="{{ $e->id }}" {{ old('employe_id', $rdv->employe_id) == $e->id ? 'selected' : '' }}>
                        {{ $e->prenom }} {{ $e->nom }}
                    </option>
                    @endforeach
                </select>
            </div>
            @endif
            <div>
                <label class="form-label">Statut</label>
                <select name="statut" class="form-input">
                    @foreach(['en_attente' => 'En attente', 'confirme' => 'Confirmé', 'termine' => 'Terminé', 'annule' => 'Annulé'] as $val => $lbl)
                    <option value="{{ $val }}" {{ old('statut', $rdv->statut) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Notes internes</label>
                <textarea name="notes" rows="3" class="form-input">{{ old('notes', $rdv->notes) }}</textarea>
            </div>
        </div>

        <div class="flex items-center gap-3 pt-1">
            <button type="submit" class="btn-primary">Enregistrer les modifications</button>
            <a href="{{ route('dashboard.rdv.show', $rdv) }}" class="btn-outline">Annuler</a>
        </div>
    </form>
</div>

<script>
function rdvForm(prestations, selectedIds, initialDuree) {
    return {
        prestations: prestations,
        prestationsIds: (selectedIds || []).map(String),
        dureeMinutes: initialDuree,
        clientNom:   '{{ addslashes($rdv->client_nom) }}',
        clientTel:   '{{ addslashes($rdv->client_telephone ?? '') }}',
        clientEmail: '{{ addslashes($rdv->client_email ?? '') }}',
        recherche: '',
        prestationOpen: false,

        get prestationsFiltrees() {
            if (!this.recherche) return this.prestations;
            const q = this.recherche.toLowerCase();
            return this.prestations.filter(p => p.nom.toLowerCase().includes(q));
        },

        getPrestationNom(id) {
            const p = this.prestations.find(p => String(p.id) === String(id));
            return p ? p.nom : '';
        },

        getPrestationDuree(id) {
            const p = this.prestations.find(p => String(p.id) === String(id));
            return p ? (parseInt(p.duree) || 0) : 0;
        },

        togglePrestation(id, duree) {
            const sid = String(id);
            const idx = this.prestationsIds.indexOf(sid);
            idx === -1 ? this.prestationsIds.push(sid) : this.prestationsIds.splice(idx, 1);
            this.recalcDuree();
        },

        recalcDuree() {
            const total = this.prestations
                .filter(p => this.prestationsIds.includes(String(p.id)))
                .reduce((sum, p) => sum + (parseInt(p.duree) || 0), 0);
            if (total > 0) this.dureeMinutes = total;
        },

        fillClientFromSelect(el) {
            if (!el.value) {
                this.clientNom   = '';
                this.clientTel   = '';
                this.clientEmail = '';
                return;
            }
            const opt = el.options[el.selectedIndex];
            this.clientNom   = opt.dataset.nom   || '';
            this.clientTel   = opt.dataset.tel   || '';
            this.clientEmail = opt.dataset.email || '';
        }
    }
}
</script>
</x-dashboard-layout>
