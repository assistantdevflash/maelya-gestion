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
          x-data="rdvForm({{ $prestations->toJson() }})"
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
                            {{ old('client_id') == $c->id ? 'selected' : '' }}>
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
                           {{ in_array($p->id, old('prestations', [])) ? 'checked' : '' }}>
                    <span class="flex-1 text-sm">
                        <span class="font-medium text-gray-900">{{ $p->nom }}</span>
                        @if($p->duree)
                        <span class="text-xs text-gray-400 block">{{ $p->duree }} min</span>
                        @endif
                    </span>
                </label>
                @endforeach
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
function rdvForm(prestations) {
    return {
        prestations: prestations,
        prestationsIds: @json(old('prestations', [])),
        dureeMinutes: {{ old('duree_minutes', 30) }},
        clientNom: '{{ old('client_nom', '') }}',
        clientTel: '{{ old('client_telephone', '') }}',
        clientEmail: '{{ old('client_email', '') }}',

        togglePrestation(id, duree) {
            const idx = this.prestationsIds.indexOf(id);
            if (idx === -1) {
                this.prestationsIds.push(id);
            } else {
                this.prestationsIds.splice(idx, 1);
            }
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
