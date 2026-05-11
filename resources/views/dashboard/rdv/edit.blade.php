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
                <select name="client_id" class="form-input" x-on:change="fillClientFromSelect($event)">
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
        <div class="card p-5 space-y-4">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Prestations</p>
            @if($prestations->isNotEmpty())
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @foreach($prestations as $p)
                <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-colors"
                       :class="prestationsIds.includes('{{ $p->id }}') ? 'border-primary-400 bg-primary-50/50' : 'border-gray-200 hover:border-gray-300'">
                    <input type="checkbox" name="prestations[]" value="{{ $p->id }}"
                           class="accent-primary-600 w-4 h-4 flex-shrink-0"
                           x-on:change="togglePrestation('{{ $p->id }}', {{ $p->duree ?? 0 }})"
                           :checked="prestationsIds.includes('{{ $p->id }}')">
                    <span class="flex-1 text-sm">
                        <span class="font-medium text-gray-900">{{ $p->nom }}</span>
                        @if($p->duree)<span class="text-xs text-gray-400 block">{{ $p->duree }} min</span>@endif
                    </span>
                </label>
                @endforeach
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
        prestationsIds: selectedIds.map(String),
        dureeMinutes: initialDuree,
        clientNom:   '{{ addslashes($rdv->client_nom) }}',
        clientTel:   '{{ addslashes($rdv->client_telephone ?? '') }}',
        clientEmail: '{{ addslashes($rdv->client_email ?? '') }}',

        togglePrestation(id, duree) {
            const idx = this.prestationsIds.indexOf(String(id));
            idx === -1 ? this.prestationsIds.push(String(id)) : this.prestationsIds.splice(idx, 1);
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
            if (!opt.value) return;
            this.clientNom   = opt.dataset.nom   || '';
            this.clientTel   = opt.dataset.tel   || '';
            this.clientEmail = opt.dataset.email || '';
        }
    }
}
</script>
</x-dashboard-layout>
