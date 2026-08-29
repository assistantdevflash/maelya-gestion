{{--
  Sélecteur de client avec recherche + ajout rapide (style caisse).
  Usage : <x-selecteur-client :clients="$clients" :preselectionne="$clientPreselectionne" old-client-id="{{ old('client_id') }}" preselection-nom="{{ $rdv->client_nom ?? '' }}" />

  Ce composant gère lui-même les champs du formulaire :
    - client_id (hidden)
    - client_nom, client_telephone, client_email (inputs du formulaire parent)
--}}
@props(['clients', 'preselectionne' => null, 'oldClientId' => null, 'preselectionNom' => null])

@php
    $clientData = $clients->map(fn($c) => [
        'id'        => $c->id,
        'nom'       => $c->nom_complet,
        'telephone' => $c->telephone ?? '',
        'email'     => $c->email ?? '',
        'initiale'  => $c->isEntreprise() ? '🏢' : strtoupper(substr($c->prenom ?? $c->nom ?? '?', 0, 1)),
        'search'    => mb_strtolower(($c->prenom ?? '') . ' ' . ($c->nom ?? '') . ' ' . ($c->raison_sociale ?? '') . ' ' . ($c->telephone ?? '')),
    ])->values();
    $selClient = $preselectionne ?: ($oldClientId ? $clients->firstWhere('id', $oldClientId) : null);
@endphp

<div
    x-data="{
        clients: @js($clientData),
        search: '',
        open: false,
        selectedId: {{ $selClient ? "'{$selClient->id}'" : "''" }},
        preselectionNom: {{ $selClient ? "'{$selClient->nom_complet}'" : ($preselectionNom ? "'" . addslashes($preselectionNom) . "'" : "''") }},
        newOpen: false,
        newPrenom: '',

        get selectedClient() {
            return this.clients.find(c => c.id === this.selectedId) || null;
        },

        get selectedNom() {
            return this.selectedClient ? this.selectedClient.nom : (this.selectedId ? this.preselectionNom : '');
        },

        get selectedInitiale() {
            const c = this.selectedClient;
            if (c) return c.initiale;
            return this.preselectionNom ? String(this.preselectionNom).charAt(0).toUpperCase() : '?';
        },
        newNom: '',
        newTel: '',
        newEmail: '',
        newNaissanceMois: '',
        newNaissanceJour: '',
        newNotes: '',
        newAdresse: '',
        newPieceIdentite: '',
        showExtra: false,
        submitting: false,
        error: '',

        get filtered() {
            const q = this.search.toLowerCase();
            if (q.length < 2) return this.clients.slice(0, 8);
            return this.clients.filter(c => c.search.includes(q)).slice(0, 8);
        },

        init() {
            // Remplir les champs si un client est déjà présélectionné
            const c = this.clients.find(c => c.id === this.selectedId);
            if (c) this.setInputs(c);
        },

        choose(c) {
            this.selectedId = c.id;
            this.search = '';
            this.open = false;
            this.setInputs(c);
            // Garder l'état de la fenêtre sélectionnée synchronisé
            this.$dispatch('client-selected', { id: c.id, nom: c.nom, telephone: c.telephone, email: c.email });
        },

        setInputs(c) {
            const f = this.$el.closest('form');
            if (!f) return;
            if (f.client_id) f.client_id.value = c.id;
            if (f.client_nom) f.client_nom.value = c.nom;
            if (f.client_telephone) f.client_telephone.value = c.telephone || '';
            if (f.client_email) f.client_email.value = c.email || '';
        },

        clearSelection() {
            this.selectedId = '';
            this.search = '';
            const f = this.$el.closest('form');
            if (!f) return;
            if (f.client_id) f.client_id.value = '';
        },

        async submitNew() {
            if (!this.newPrenom.trim() && !this.newNom.trim()) {
                this.error = 'Veuillez saisir au moins un prénom ou un nom.';
                return;
            }
            if (!this.newTel.trim()) {
                this.error = 'Le téléphone est requis pour créer le client.';
                return;
            }
            this.submitting = true;
            this.error = '';
            const fd = new FormData();
            fd.append('_token', '{{ csrf_token() }}');
            fd.append('type_client', 'personne_physique');
            fd.append('prenom', this.newPrenom.trim());
            fd.append('nom', this.newNom.trim());
            fd.append('telephone', this.newTel.trim());
            fd.append('email', this.newEmail.trim());
            if (this.newNaissanceMois && this.newNaissanceJour) {
                fd.append('date_naissance', this.newNaissanceMois + '-' + String(this.newNaissanceJour).padStart(2, '0'));
            }
            if (this.newNotes) fd.append('notes', this.newNotes.trim());
            if (this.newAdresse) fd.append('adresse', this.newAdresse.trim());
            if (this.newPieceIdentite) fd.append('piece_identite', this.newPieceIdentite.trim());
            try {
                const res = await fetch('{{ route('dashboard.clients.quick-store') }}', { method: 'POST', body: fd });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Erreur lors de la création.');
                const nouveau = {
                    id: data.id,
                    nom: data.nom_affichage || (this.newPrenom + ' ' + this.newNom).trim(),
                    telephone: data.telephone || '',
                    email: data.email || '',
                    initiale: data.initiale || '?',
                    search: ((data.nom || this.newNom) + ' ' + (data.prenom || this.newPrenom) + ' ' + (data.telephone || '')).toLowerCase(),
                };
                this.clients.unshift(nouveau);
                this.choose(nouveau);
                this.newOpen = false;
                this.newPrenom = ''; this.newNom = ''; this.newTel = ''; this.newEmail = '';
                this.newNaissanceMois = ''; this.newNaissanceJour = ''; this.newNotes = '';
                this.newAdresse = ''; this.newPieceIdentite = ''; this.showExtra = false;
            } catch (e) {
                this.error = e.message || 'Erreur lors de la création.';
            } finally {
                this.submitting = false;
            }
        }
    }"
    @click.outside="open = false"
>
    <input type="hidden" name="client_id" :value="selectedId">

    {{-- Client sélectionné (puce, comme à la caisse) --}}
    <div x-show="selectedId" x-cloak
         class="flex items-center gap-3 p-2.5 bg-primary-50/60 dark:bg-primary-900/20 rounded-xl border border-primary-200/60 dark:border-primary-700/40">
        <div class="w-9 h-9 bg-gradient-to-br from-primary-400 to-secondary-400 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0" x-text="selectedInitiale"></div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate" x-text="selectedNom"></p>
            <p class="text-xs text-gray-500 dark:text-slate-400" x-text="selectedClient ? selectedClient.telephone : ''"></p>
        </div>
        <button type="button" @click="clearSelection()"
                class="flex-shrink-0 inline-flex items-center gap-1 text-xs font-medium text-red-500 hover:text-red-700 transition-colors"
                title="Retirer ce client">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Retirer
        </button>
    </div>

    {{-- Recherche + nouvel client (si aucun sélectionné) --}}
    <div x-show="!selectedId">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text"
                   x-model="search"
                   @focus="open = true"
                   @input="open = true"
                   @keydown.escape="open = false"
                   placeholder="Rechercher un client…"
                   autocomplete="off"
                   class="form-input pl-9 text-sm">
        </div>

        {{-- Dropdown résultats --}}
        <div x-show="open && filtered.length > 0" x-cloak
             class="absolute z-50 mt-1 w-full rounded-xl border border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-800 shadow-xl max-h-52 overflow-y-auto">
            <template x-for="c in filtered" :key="c.id">
                <button type="button" @click="choose(c)"
                        class="w-full flex items-center gap-3 px-3 py-2.5 text-sm hover:bg-primary-50/50 dark:hover:bg-primary-900/20 transition-colors border-b border-gray-50 dark:border-slate-700/50 last:border-0 text-left">
                    <div class="w-7 h-7 bg-gradient-to-br from-primary-100 to-secondary-100 dark:from-primary-900/40 dark:to-secondary-900/40 rounded-full flex items-center justify-center text-xs font-bold text-primary-700 dark:text-primary-400 flex-shrink-0" x-text="c.initiale"></div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-900 dark:text-white truncate" x-text="c.nom"></p>
                        <p class="text-xs text-gray-400 dark:text-slate-500" x-text="c.telephone"></p>
                    </div>
                    <svg x-show="selectedId === c.id" class="w-4 h-4 text-primary-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </button>
            </template>
        </div>
        <p x-show="open && search.length >= 2 && filtered.length === 0" x-cloak
           class="text-xs text-gray-400 dark:text-slate-500 mt-2 text-center py-2">Aucun client trouvé.</p>

        {{-- Nouveau client --}}
        <button type="button" @click="newOpen = true"
                class="mt-2 inline-flex items-center gap-1.5 text-xs font-bold text-primary-600 dark:text-primary-400 hover:text-primary-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            Nouveau client
        </button>
    </div>

    {{-- Modal ajout rapide --}}
    <template x-if="newOpen">
    <div class="fixed inset-0 z-[100] flex items-center justify-center p-4" style="background: rgba(0,0,0,0.4);"
         @click.self="newOpen = false">
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4" @click.stop>
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">➕ Nouveau client</h3>
                <button type="button" @click="newOpen = false" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <p x-show="error" x-text="error" x-cloak
               class="text-xs text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800/40 rounded-lg px-3 py-2"></p>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label">Prénom</label>
                    <input type="text" x-model="newPrenom" class="form-input" placeholder="Prénom">
                </div>
                <div>
                    <label class="form-label">Nom</label>
                    <input type="text" x-model="newNom" class="form-input" placeholder="Nom">
                </div>
                <div class="col-span-2">
                    <label class="form-label">Téléphone <span class="text-red-500">*</span></label>
                    <input type="tel" x-model="newTel" class="form-input" placeholder="06 XX XX XX XX">
                </div>
                <div class="col-span-2">
                    <label class="form-label">E-mail</label>
                    <input type="email" x-model="newEmail" class="form-input" placeholder="client@exemple.fr">
                </div>
                <div class="col-span-2">
                    <label class="form-label">Anniversaire (jour et mois)</label>
                    <div class="grid grid-cols-2 gap-2">
                        <select x-model="newNaissanceMois" class="form-input">
                            <option value="">Mois</option>
                            <option value="01">Janvier</option><option value="02">Février</option>
                            <option value="03">Mars</option><option value="04">Avril</option>
                            <option value="05">Mai</option><option value="06">Juin</option>
                            <option value="07">Juillet</option><option value="08">Août</option>
                            <option value="09">Septembre</option><option value="10">Octobre</option>
                            <option value="11">Novembre</option><option value="12">Décembre</option>
                        </select>
                        <select x-model="newNaissanceJour" class="form-input">
                            <option value="">Jour</option>
                            @for($d = 1; $d <= 31; $d++)
                            <option value="{{ str_pad($d, 2, '0', STR_PAD_LEFT) }}">{{ $d }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                <div class="col-span-2">
                    <label class="form-label">Notes</label>
                    <textarea x-model="newNotes" rows="2" maxlength="1000" class="form-input resize-none"
                              placeholder="Allergies, préférences..."></textarea>
                </div>
                {{-- Informations supplémentaires (collapsible) --}}
                <div class="col-span-2">
                    <button type="button" @click="showExtra = !showExtra"
                            class="flex items-center gap-2 text-xs font-medium text-gray-500 dark:text-slate-400 hover:text-gray-700 transition-colors">
                        <svg class="w-3.5 h-3.5 transition-transform" :class="showExtra ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        Informations supplémentaires
                    </button>
                    <div x-show="showExtra" class="mt-3 space-y-3">
                        <div>
                            <label class="form-label">Adresse</label>
                            <input type="text" x-model="newAdresse" maxlength="255" class="form-input" placeholder="Abidjan, Cocody...">
                        </div>
                        <div>
                            <label class="form-label">Pièce d'identité</label>
                            <input type="text" x-model="newPieceIdentite" maxlength="100" class="form-input" placeholder="N° CNI, Passeport...">
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" @click="newOpen = false" class="btn-outline flex-1 justify-center">Annuler</button>
                <button type="button" @click="submitNew" :disabled="submitting"
                        class="btn-primary flex-1 justify-center">
                    <span x-text="submitting ? 'Création…' : 'Créer le client'"></span>
                </button>
            </div>
        </div>
    </div>
    </template>
</div>
