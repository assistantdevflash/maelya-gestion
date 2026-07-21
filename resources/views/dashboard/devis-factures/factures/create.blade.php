<x-dashboard-layout>
<div class="max-w-4xl mx-auto space-y-6" x-data="devisForm(@js($allClients->toArray()))">
    <div class="flex items-center gap-4">
        <a href="{{ route('dashboard.factures.index') }}" class="p-2 text-gray-400 hover:text-gray-600 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 transition">←</a>
        <div><h1 class="text-2xl font-bold text-gray-900 dark:text-white">Nouvelle facture</h1></div>
    </div>

    <form method="POST" action="{{ route('dashboard.factures.store') }}">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-5">
                {{-- Lignes --}}
                <div class="card">
                    <div class="card-header flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Articles / Prestations</h2>
                        <button type="button" @click="ajouterLigne()" class="text-sm text-primary-600 hover:text-primary-700 font-medium">+ Ajouter une ligne</button>
                    </div>
                    <div class="card-body space-y-3">
                        <template x-for="(ligne, i) in lignes" :key="i">
                            <div class="flex flex-wrap items-center gap-2 p-3 bg-gray-50 dark:bg-slate-800 rounded-xl">
                                <input type="text" :name="'ligne_'+i+'_designation'" x-model="ligne.designation" placeholder="Désignation" class="form-input text-sm flex-1 min-w-[140px]" required>
                                <input type="number" :name="'ligne_'+i+'_quantite'" x-model.number="ligne.quantite" min="1" class="form-input text-sm w-16 text-center" required>
                                <input type="number" :name="'ligne_'+i+'_prix'" x-model.number="ligne.prix_unitaire" min="0" placeholder="PU" class="form-input text-sm w-24" required>
                                <button type="button" @click="lignes.splice(i,1)" class="p-1.5 text-red-400 hover:text-red-600" title="Supprimer">✕</button>
                            </div>
                        </template>
                        <p x-show="lignes.length === 0" class="text-sm text-gray-400 text-center py-4">Ajoutez au moins un article ou une prestation.</p>
                    </div>
                </div>

                {{-- Totaux --}}
                <div class="card">
                    <div class="card-header"><h2 class="text-lg font-semibold">Totaux</h2></div>
                    <div class="card-body space-y-3">
                        <div class="flex justify-between text-sm"><span class="text-gray-500">Sous-total</span><span class="font-bold" x-text="formatPrix(sousTotal) + ' F'"></span></div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-gray-500">Remise globale</span>
                            <select name="remise_globale_type" class="form-input text-sm w-32"><option value="">Aucune</option><option value="pourcentage">%</option><option value="montant_fixe">Fixe</option></select>
                            <input type="number" name="remise_globale_valeur" min="0" placeholder="0" class="form-input text-sm w-24">
                        </div>
                        <div class="flex justify-between text-sm"><span class="text-gray-500">Total HT</span><span class="font-bold" x-text="formatPrix(totalHT) + ' F'"></span></div>
                        <div class="flex items-center gap-3">
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="tva_applicable" value="1" @change="tva = $el.checked ? {{ auth()->user()->institut->tva_taux ?? 0 }} : 0"> TVA</label>
                            <input type="number" name="tva_taux" x-model="tva" min="0" max="100" step="0.01" class="form-input text-sm w-20">
                            <span class="text-sm text-gray-500">%</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold pt-2 border-t"><span>Total TTC</span><span class="text-primary-600" x-text="formatPrix(totalTTC) + ' F'"></span></div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-5">
                {{-- ═══ CLIENT ═══ --}}
                <div class="card">
                    <div class="card-header"><h2 class="text-lg font-semibold">Client</h2></div>
                    <div class="card-body space-y-3">
                        <template x-if="clientChoisi">
                            <div class="flex items-center gap-3 p-3 bg-primary-50 dark:bg-primary-900/20 rounded-xl">
                                <div class="w-8 h-8 bg-gradient-to-br from-primary-400 to-secondary-400 rounded-full flex items-center justify-center text-white text-xs font-bold" x-text="clientChoisi.initiale"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white" x-text="clientChoisi.nom_affichage"></p>
                                    <p class="text-xs text-gray-500" x-text="clientChoisi.telephone"></p>
                                </div>
                                <button type="button" @click="retirerClient()" class="text-xs text-red-500 hover:text-red-700 font-medium">Changer</button>
                            </div>
                        </template>
                        <template x-if="!clientChoisi">
                            <div>
                                <div x-data="{
                                    search: '',
                                    open: false,
                                    get filtered() {
                                        if (this.search.length < 2) return clientsList.slice(0, 8);
                                        const q = this.search.toLowerCase();
                                        return clientsList.filter(c => c.search.includes(q)).slice(0, 8);
                                    },
                                    choisir(client) {
                                        this.open = false;
                                        this.search = '';
                                        selectClient(client);
                                    }
                                }" @click.outside="open = false">
                                    <input type="text" x-model="search"
                                        @focus="open = true" @input="open = true"
                                        @keydown.escape="open = false"
                                        placeholder="Chercher un client..."
                                        class="form-input text-sm">
                                    <div x-show="open && filtered.length > 0" x-cloak
                                        class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden mt-2 shadow-sm max-h-52 overflow-y-auto bg-white dark:bg-gray-800">
                                        <template x-for="c in filtered" :key="c.id">
                                            <button type="button"
                                                @mousedown.prevent @click="choisir(c)"
                                                @touchend.prevent="choisir(c)"
                                                class="w-full text-left px-3 py-2.5 text-sm hover:bg-primary-50/50 dark:hover:bg-gray-700 flex items-center gap-2.5 border-b border-gray-100 dark:border-gray-700 last:border-b-0 transition-colors">
                                                <div class="w-7 h-7 bg-gradient-to-br from-primary-100 to-secondary-100 rounded-full flex items-center justify-center text-primary-700 text-xs font-bold" x-text="c.initiale"></div>
                                                <span class="font-medium text-gray-900 dark:text-white" x-text="c.nom_affichage"></span>
                                                <span class="text-gray-400 text-xs ml-auto" x-text="c.telephone"></span>
                                            </button>
                                        </template>
                                    </div>
                                    <div x-show="open && search.length >= 2 && filtered.length === 0" x-cloak
                                        class="text-xs text-gray-400 mt-2 text-center py-2">Aucun client trouvé</div>
                                </div>
                                <button @click="newClientOpen = true" type="button"
                                    class="mt-2 flex items-center gap-1.5 text-xs text-primary-600 hover:text-primary-800 font-medium transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                    Nouveau client
                                </button>
                            </div>
                        </template>
                        <input type="hidden" name="client_id" :value="clientChoisi ? clientChoisi.id : ''">
                    </div>
                </div>

                {{-- ═══ DATES ═══ --}}
                <div class="card">
                    <div class="card-header"><h2 class="text-lg font-semibold">Dates</h2></div>
                    <div class="card-body space-y-3">
                        <div><label class="form-label">Date d'émission</label><input type="date" name="date_emission" value="{{ now()->toDateString() }}" class="form-input" required></div>
                        <div><label class="form-label">Date d'échéance</label><input type="date" name="date_echeance" value="{{ now()->addDays(30)->toDateString() }}" class="form-input" required></div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h2 class="text-lg font-semibold">Notes</h2></div>
                    <div class="card-body"><textarea name="notes" rows="3" placeholder="Notes internes..." class="form-textarea text-sm"></textarea></div>
                </div>

                <input type="hidden" name="lignes" :value="JSON.stringify(lignes)">
                <button type="submit" class="btn-primary w-full">Créer la facture</button>
            </div>
        </div>
    </form>

    {{-- ═══ MODAL NOUVEAU CLIENT ═══ --}}
    <div x-show="newClientOpen" x-cloak class="modal-backdrop"
        x-on:keydown.escape.window="newClientOpen = false"
        @click.self="newClientOpen = false">
        <div class="modal max-w-lg" @click.stop>
            <div class="modal-header">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, rgba(147,51,234,0.1), rgba(236,72,153,0.1));">
                        <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                    </div>
                    <h3 class="modal-title">Nouveau client</h3>
                </div>
                <button @click="newClientOpen = false" type="button" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <div x-show="newClientError" x-cloak class="mb-4 p-3 bg-red-50 rounded-xl text-sm text-red-600" x-text="newClientError"></div>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="form-group mb-0"><label class="form-label">Prénom *</label><input type="text" x-model="newClient.prenom" maxlength="50" class="form-input" placeholder="Fatou"></div>
                        <div class="form-group mb-0"><label class="form-label">Nom *</label><input type="text" x-model="newClient.nom" maxlength="50" class="form-input" placeholder="Traoré"></div>
                        <div class="form-group mb-0"><label class="form-label">Téléphone *</label><input type="text" x-model="newClient.telephone" maxlength="30" class="form-input" placeholder="+225 07 00 00 00"></div>
                        <div class="form-group mb-0"><label class="form-label">Email</label><input type="email" x-model="newClient.email" maxlength="255" class="form-input" placeholder="fatou@exemple.ci"></div>
                        <div class="col-span-2 form-group mb-0"><label class="form-label">Adresse</label><input type="text" x-model="newClient.adresse" maxlength="255" class="form-input" placeholder="Abidjan, Cocody..."></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button @click="newClientOpen = false" type="button" class="btn-outlined">Annuler</button>
                <button @click="creerClient()" type="button" class="btn-primary" :disabled="newClientSaving">
                    <span x-show="!newClientSaving">Créer le client</span>
                    <span x-show="newClientSaving" class="flex items-center gap-1.5">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Création...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('devisForm', (clientsInit) => ({
        lignes: [{designation:'', quantite:1, prix_unitaire:0, remise_type:'', remise_valeur:0, tva_taux:null}],
        tva: 0,
        ajouterLigne() { this.lignes.push({designation:'', quantite:1, prix_unitaire:0, remise_type:'', remise_valeur:0, tva_taux:null}); },
        get sousTotal() { return this.lignes.reduce((s, l) => s + ((l.prix_unitaire||0) * (l.quantite||1)), 0); },
        get totalHT() { return this.sousTotal; },
        get totalTTC() { return Math.round(this.totalHT * (1 + (this.tva || 0) / 100)); },
        formatPrix(v) { return new Intl.NumberFormat('fr-FR').format(v); },
        clientsList: clientsInit,
        clientChoisi: null,
        newClientOpen: false,
        newClientSaving: false,
        newClientError: '',
        newClient: { prenom: '', nom: '', telephone: '', email: '', adresse: '' },
        selectClient(client) { this.clientChoisi = client; },
        retirerClient() { this.clientChoisi = null; },
        async creerClient() {
            this.newClientError = '';
            if (!this.newClient.prenom.trim() || !this.newClient.nom.trim() || !this.newClient.telephone.trim()) {
                this.newClientError = 'Prénom, nom et téléphone sont requis.'; return;
            }
            this.newClientSaving = true;
            try {
                const res = await fetch('{{ route('dashboard.clients.quick-store') }}', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').getAttribute('content'),'Accept':'application/json'},
                    body: JSON.stringify(this.newClient),
                });
                if (!res.ok) { const err = await res.json(); this.newClientError = err.message || 'Erreur lors de la création.'; return; }
                const client = await res.json();
                this.clientsList.unshift(client);
                this.clientChoisi = client;
                this.newClient = { prenom:'',nom:'',telephone:'',email:'',adresse:'' };
                this.newClientOpen = false;
            } catch (e) { this.newClientError = 'Erreur réseau. Réessayez.'; }
            finally { this.newClientSaving = false; }
        },
    }));
});
</script>
@endpush
</x-dashboard-layout>
