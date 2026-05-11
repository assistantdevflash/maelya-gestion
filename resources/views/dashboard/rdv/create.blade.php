<x-dashboard-layout>
<div class="max-w-2xl mx-auto space-y-5">

    <div>
        <a href="{{ route('dashboard.rdv.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-700 mb-3 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Retour aux RDV
        </a>
        <h1 class="page-title">Nouveau rendez-vous</h1>
    </div>

    <form method="POST" action="{{ route('dashboard.rdv.store') }}"
          x-data="rdvForm({{ $prestations->toJson() }}, @json(old('prestations', [])))"
          class="space-y-4">
        @csrf

        {{-- Erreurs --}}
        @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-700 space-y-1">
            @foreach($errors->all() as $e)<p>• {{ $e }}</p>@endforeach
        </div>
        @endif

        {{-- CLIENT --}}
        <div class="card p-5 space-y-4">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Client</p>

            <div>
                <label class="form-label">Sélectionner un client existant (optionnel)</label>
                <select name="client_id" class="form-input" x-on:change="fillClientFromSelect($event)">
                    <option value="">— Choisir un client —</option>
                    @foreach($clients as $c)
                    <option value="{{ $c->id }}"
                            data-nom="{{ $c->prenom }} {{ $c->nom }}"
                            data-tel="{{ $c->telephone ?? '' }}"
                            data-email="{{ $c->email ?? '' }}"
                                    {{ old('client_id') == $c->id || ($clientPreselectionne && $clientPreselectionne->id == $c->id) ? 'selected' : '' }}>
                        {{ $c->prenom }} {{ $c->nom }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Nom du client <span class="text-red-500">*</span></label>
                    <input type="text" name="client_nom" x-model="clientNom" required
                           value="{{ old('client_nom') }}"
                           class="form-input" placeholder="Prénom Nom">
                </div>
                <div>
                    <label class="form-label">Téléphone</label>
                    <input type="tel" name="client_telephone" x-model="clientTel"
                           value="{{ old('client_telephone') }}"
                           class="form-input" placeholder="06 XX XX XX XX">
                </div>
            </div>
            <div>
                <label class="form-label">E-mail</label>
                <input type="email" name="client_email" x-model="clientEmail"
                       value="{{ old('client_email') }}"
                       class="form-input" placeholder="client@exemple.fr">
            </div>
        </div>

        {{-- DATE / HEURE --}}
        <div class="card p-5 space-y-4">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Date &amp; heure</p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Date <span class="text-red-500">*</span></label>
                    <input type="date" name="debut_date" required
                           value="{{ old('debut_date', now()->format('Y-m-d')) }}"
                           class="form-input">
                </div>
                <div>
                    <label class="form-label">Heure <span class="text-red-500">*</span></label>
                    <input type="time" name="debut_heure" required
                           value="{{ old('debut_heure', '09:00') }}"
                           class="form-input">
                </div>
            </div>
            <div>
                <label class="form-label">Durée (minutes)</label>
                <input type="number" name="duree_minutes" min="5" max="480" step="5"
                       x-model="dureeMinutes"
                       value="{{ old('duree_minutes', 30) }}"
                       class="form-input w-32">
                <p class="text-xs text-gray-400 mt-1">Calculée automatiquement d'après les prestations si renseignées.</p>
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
            <div class="relative" @click.outside="showDropdown = false">
                <svg class="absolute left-3 top-3.5 w-4 h-4 text-gray-400 pointer-events-none z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" x-model="recherche"
                       @focus="showDropdown = true"
                       placeholder="Cliquez pour choisir ou tapez pour rechercher…"
                       class="form-input pl-9 text-sm"
                       autocomplete="off">

                {{-- Dropdown --}}
                <div x-show="showDropdown" x-cloak
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
                       value="{{ old('prestation_libre') }}"
                       class="form-input" placeholder="Ex : Balayage + coupe + brushing">
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
                    <option value="{{ $e->id }}" {{ old('employe_id') == $e->id ? 'selected' : '' }}>
                        {{ $e->prenom }} {{ $e->nom }}
                    </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div>
                <label class="form-label">Statut</label>
                <select name="statut" class="form-input">
                    <option value="en_attente" {{ old('statut', 'en_attente') === 'en_attente' ? 'selected' : '' }}>En attente</option>
                    <option value="confirme" {{ old('statut') === 'confirme' ? 'selected' : '' }}>Confirmé</option>
                </select>
            </div>

            <div>
                <label class="form-label">Notes internes</label>
                <textarea name="notes" rows="3" class="form-input"
                          placeholder="Informations complémentaires…">{{ old('notes') }}</textarea>
            </div>

            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="envoyer_confirmation" value="1"
                       {{ old('envoyer_confirmation', 1) ? 'checked' : '' }}
                       class="accent-primary-600 w-4 h-4">
                <span class="text-sm text-gray-700">Envoyer un e-mail de confirmation au client</span>
            </label>
        </div>

        {{-- Boutons --}}
        <div class="flex items-center gap-3 pt-1">
            <button type="submit" class="btn-primary">Créer le rendez-vous</button>
            <a href="{{ route('dashboard.rdv.index') }}" class="btn-outline">Annuler</a>
        </div>
    </form>
</div>

<script>
function rdvForm(prestations, selectedIds) {
    return {
        prestations: prestations,
        prestationsIds: (selectedIds || []).map(String),
        dureeMinutes: {{ old('duree_minutes', 30) }},
        clientNom:   {{ json_encode(old('client_nom', $clientPreselectionne ? $clientPreselectionne->prenom . ' ' . $clientPreselectionne->nom : '')) }},
        clientTel:   {{ json_encode(old('client_telephone', $clientPreselectionne?->telephone ?? '')) }},
        clientEmail: {{ json_encode(old('client_email', $clientPreselectionne?->email ?? '')) }},
        recherche: '',
        showDropdown: false,

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

        fillClientFromSelect(event) {
            const opt = event.target.selectedOptions[0];
            if (!opt.value) {
                this.clientNom   = '';
                this.clientTel   = '';
                this.clientEmail = '';
                return;
            }
            this.clientNom   = opt.dataset.nom   || '';
            this.clientTel   = opt.dataset.tel   || '';
            this.clientEmail = opt.dataset.email || '';
        }
    }
}
</script>
</x-dashboard-layout>
