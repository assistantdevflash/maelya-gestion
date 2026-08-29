{{--
  Sélecteur de client avec recherche + ajout rapide (style caisse).
  Usage : <x-selecteur-client :clients="$clients" :preselectionne="$clientPreselectionne" old-client-id="{{ old('client_id') }}" />

  Ce composant gère lui-même les champs du formulaire :
    - client_id (hidden)
    - client_nom, client_telephone, client_email (inputs du formulaire parent)
--}}
@props(['clients', 'preselectionne' => null, 'oldClientId' => null])

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
        newOpen: false,
        newPrenom: '',
        newNom: '',
        newTel: '',
        newEmail: '',
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

    <div class="relative">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input type="text"
               x-model="search"
               @focus="open = true"
               @input="open = true"
               @keydown.escape="open = false"
               @keydown.backspace="selectedId && !search ? clearSelection() : null"
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
            </div>
            <div>
                <label class="form-label">Téléphone <span class="text-red-500">*</span></label>
                <input type="tel" x-model="newTel" class="form-input" placeholder="06 XX XX XX XX">
            </div>
            <div>
                <label class="form-label">E-mail</label>
                <input type="email" x-model="newEmail" class="form-input" placeholder="client@exemple.fr">
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
