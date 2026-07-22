<x-dashboard-layout>
<div class="max-w-4xl mx-auto space-y-6" x-data="devisForm(@js($allClients->toArray()), @js($catalogue->toArray()))">
    <div class="flex items-center gap-4">
        <a href="{{ route('dashboard.factures.index') }}" class="p-2 text-gray-400 hover:text-gray-600 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 transition">←</a>
        <div><h1 class="text-2xl font-bold text-gray-900 dark:text-white">Nouvelle facture</h1></div>
    </div>

    <form method="POST" action="{{ route('dashboard.factures.store') }}">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-5">
                {{-- Lignes --}}
                <div class="card" style="overflow: visible">
                    <div class="card-header flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Articles / Prestations</h2>
                        <button type="button" @click="ajouterLigne()" class="text-sm text-primary-600 hover:text-primary-700 font-medium">+ Ajouter une ligne</button>
                    </div>
                    <div class="card-body space-y-3">
                        <template x-for="(ligne, i) in lignes" :key="i">
                            <div class="flex flex-wrap items-center gap-2 p-3 bg-gray-50 dark:bg-slate-800 rounded-xl">
                                {{-- Sélecteur catalogue --}}
                                <div class="relative" @click.outside="ligne.pickerOpen = false">
                                    <button type="button" @click="ligne.pickerOpen = !ligne.pickerOpen"
                                        class="p-1.5 text-gray-400 hover:text-primary-600 hover:bg-white rounded-lg transition"
                                        title="Choisir une prestation/produit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    </button>
                                    <div x-show="ligne.pickerOpen" x-cloak
                                        class="absolute left-0 top-full mt-1 w-64 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-gray-200 dark:border-slate-700 z-20 overflow-hidden">
                                        <div class="p-2">
                                            <input type="text" x-model="ligne.pickerSearch" placeholder="Rechercher..."
                                                class="form-input text-xs w-full" @keydown.escape="ligne.pickerOpen = false">
                                        </div>
                                        <div class="max-h-40 overflow-y-auto divide-y divide-gray-100 dark:divide-slate-700">
                                            <template x-for="item in catalogueFiltered(ligne)" :key="item.id">
                                                <button type="button" @click="choisirCatalogue(ligne, item)"
                                                    class="w-full text-left px-3 py-2 text-xs hover:bg-gray-50 dark:hover:bg-slate-700 flex justify-between items-center">
                                                    <span>
                                                        <span x-text="item.designation"></span>
                                                        <span class="ml-1.5 text-[10px] px-1 py-0.5 rounded"
                                                            :class="item.type === 'prestation' ? 'bg-purple-100 text-purple-700' : 'bg-amber-100 text-amber-700'"
                                                            x-text="item.type === 'prestation' ? 'Prest.' : 'Prod.'"></span>
                                                    </span>
                                                    <span class="font-semibold text-gray-900" x-text="formatPrix(item.prix) + ' F'"></span>
                                                </button>
                                            </template>
                                            <p x-show="catalogueFiltered(ligne).length === 0" class="px-3 py-2 text-xs text-gray-400 text-center">Aucun résultat</p>
                                        </div>
                                    </div>
                                </div>
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
                            <select name="remise_globale_type" x-model="remiseGlobaleType" class="form-input text-sm w-32"><option value="">Aucune</option><option value="pourcentage">%</option><option value="montant_fixe">Fixe</option></select>
                            <input type="number" name="remise_globale_valeur" x-model.number="remiseGlobaleValeur" min="0" placeholder="0" class="form-input text-sm w-24">
                        </div>
                        <div class="flex justify-between text-sm"><span class="text-gray-500">Total HT</span><span class="font-bold" x-text="formatPrix(totalHT) + ' F'"></span></div>
                        <div class="flex items-center gap-3">
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="tva_applicable" value="1" x-model="tvaApplicable" @change="tva = $el.checked ? 18 : 0"> TVA
                            </label>
                            <template x-if="tvaApplicable">
                                <span class="inline-flex items-center gap-1">
                                    <input type="number" name="tva_taux" x-model="tva" min="0" max="100" step="0.01" class="form-input text-sm w-20">
                                    <span class="text-sm text-gray-500">%</span>
                                </span>
                            </template>
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
                    <div class="card-header"><h2 class="text-lg font-semibold">Titre (optionnel)</h2></div>
                    <div class="card-body"><input type="text" name="titre" maxlength="200" value="{{ old('titre') }}" placeholder="Ex: Facture loyer Janvier..." class="form-input text-sm"></div>
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

    {{-- ═══ MODAL NOUVEAU CLIENT (identique Caisse) ═══ --}}
    <div x-show="newClientOpen" x-cloak class="modal-backdrop"
         x-on:keydown.escape.window="newClientOpen = false; document.body.classList.remove('overflow-hidden')"
         x-init="$watch('newClientOpen', v => document.body.classList.toggle('overflow-hidden', v))"
         @click.self="newClientOpen = false; document.body.classList.remove('overflow-hidden')">
        <div class="modal max-w-lg" x-transition @click.stop>
            <div class="modal-header">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, rgba(147,51,234,0.1), rgba(236,72,153,0.1));">
                        <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                    </div>
                    <h3 class="modal-title">Nouveau client</h3>
                </div>
                <button @click="newClientOpen = false; document.body.classList.remove('overflow-hidden')" type="button" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <div x-show="newClientError" x-cloak class="mb-4 p-3 bg-red-50 rounded-xl text-sm text-red-600" x-text="newClientError"></div>
                <div class="space-y-4">
                    {{-- Type de client --}}
                    <div class="flex gap-2 p-1 bg-gray-100 dark:bg-slate-800 rounded-xl">
                        <button type="button"
                            @click="newClient.type_client = 'personne_physique'"
                            :class="newClient.type_client === 'personne_physique' ? 'bg-white dark:bg-slate-700 shadow-sm text-gray-900 dark:text-white' : 'text-gray-500'"
                            class="flex-1 py-2 px-3 rounded-lg text-sm font-semibold transition">Personne physique</button>
                        <button type="button"
                            @click="newClient.type_client = 'entreprise'"
                            :class="newClient.type_client === 'entreprise' ? 'bg-white dark:bg-slate-700 shadow-sm text-gray-900 dark:text-white' : 'text-gray-500'"
                            class="flex-1 py-2 px-3 rounded-lg text-sm font-semibold transition">Entreprise</button>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        {{-- Personne physique --}}
                        <template x-if="newClient.type_client === 'personne_physique'">
                            <div class="col-span-2 grid grid-cols-2 gap-3">
                                <div class="form-group mb-0"><label class="form-label">Prénom *</label><input type="text" x-model="newClient.prenom" maxlength="50" class="form-input" placeholder="Fatou"></div>
                                <div class="form-group mb-0"><label class="form-label">Nom *</label><input type="text" x-model="newClient.nom" maxlength="50" class="form-input" placeholder="Traoré"></div>
                            </div>
                        </template>
                        {{-- Entreprise --}}
                        <template x-if="newClient.type_client === 'entreprise'">
                            <div class="col-span-2 form-group mb-0"><label class="form-label">Raison sociale *</label><input type="text" x-model="newClient.raison_sociale" maxlength="255" class="form-input" placeholder="SARL Exemple & Cie"></div>
                        </template>
                        <div class="form-group mb-0"><label class="form-label">Téléphone *</label><input type="text" x-model="newClient.telephone" maxlength="30" class="form-input" placeholder="+225 07 00 00 00"></div>
                        <div class="form-group mb-0"><label class="form-label">Email</label><input type="email" x-model="newClient.email" maxlength="255" class="form-input" placeholder="contact@exemple.ci"></div>
                        <template x-if="newClient.type_client === 'personne_physique'">
                            <div class="col-span-2 form-group mb-0">
                                <label class="form-label">Anniversaire (jour et mois)</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <select x-model="newClient.date_naissance_mois" class="form-input">
                                        <option value="">Mois</option>
                                        <option value="01">Janvier</option><option value="02">Février</option><option value="03">Mars</option>
                                        <option value="04">Avril</option><option value="05">Mai</option><option value="06">Juin</option>
                                        <option value="07">Juillet</option><option value="08">Août</option><option value="09">Septembre</option>
                                        <option value="10">Octobre</option><option value="11">Novembre</option><option value="12">Décembre</option>
                                    </select>
                                    <select x-model="newClient.date_naissance_jour" class="form-input">
                                        <option value="">Jour</option>
                                        <template x-for="d in 31" :key="d"><option :value="String(d).padStart(2,'0')" x-text="d"></option></template>
                                    </select>
                                </div>
                            </div>
                        </template>
                        <div class="col-span-2 form-group mb-0"><label class="form-label">Notes</label><textarea x-model="newClient.notes" rows="2" maxlength="1000" class="form-input resize-none" placeholder="Allergies, préférences..."></textarea></div>
                        {{-- Informations supplémentaires (collapsible) --}}
                        <div class="col-span-2" x-data="{ showExtra: false }">
                            <button type="button" @click="showExtra = !showExtra" class="flex items-center gap-2 text-xs font-medium text-gray-500 hover:text-gray-700 transition-colors">
                                <svg class="w-3.5 h-3.5 transition-transform" :class="showExtra ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                Informations supplémentaires
                            </button>
                            <div x-show="showExtra" x-collapse class="mt-3 space-y-3">
                                <div class="form-group mb-0"><label class="form-label">Adresse</label><input type="text" x-model="newClient.adresse" maxlength="255" class="form-input" placeholder="Abidjan, Cocody..."></div>
                                <div class="form-group mb-0"><label class="form-label">Pièce d'identité</label><input type="text" x-model="newClient.piece_identite" maxlength="100" class="form-input" placeholder="N° CNI, Passeport..."></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button @click="newClientOpen = false; document.body.classList.remove('overflow-hidden')" type="button" class="btn-outlined">Annuler</button>
                <button @click="creerClient()" type="button" class="btn-primary" :disabled="newClientSaving">
                    <span x-show="!newClientSaving">Enregistrer</span>
                    <span x-show="newClientSaving" class="flex items-center gap-2">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Ajout...
                    </span>
                </button>
            </div>
        </div>
    </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('devisForm', (clientsInit, catalogueInit) => ({
        lignes: [{designation:'', quantite:1, prix_unitaire:0, remise_type:'', remise_valeur:0, tva_taux:null, pickerOpen: false, pickerSearch: ''}],
        tva: 0,
        tvaApplicable: false,
        remiseGlobaleType: '',
        remiseGlobaleValeur: 0,
        catalogue: catalogueInit,
        ajouterLigne() { this.lignes.push({designation:'', quantite:1, prix_unitaire:0, remise_type:'', remise_valeur:0, tva_taux:null, pickerOpen: false, pickerSearch: ''}); },
        catalogueFiltered(ligne) {
            const q = (ligne.pickerSearch || '').toLowerCase();
            if (q.length < 1) return this.catalogue.slice(0, 10);
            return this.catalogue.filter(c => c.search.includes(q)).slice(0, 10);
        },
        choisirCatalogue(ligne, item) {
            ligne.designation = item.designation;
            ligne.prix_unitaire = item.prix;
            ligne.pickerOpen = false;
            ligne.pickerSearch = '';
        },
        get sousTotal() { return this.lignes.reduce((s, l) => s + ((l.prix_unitaire||0) * (l.quantite||1)), 0); },
        get remiseGlobale() {
            if (!this.remiseGlobaleType || !this.remiseGlobaleValeur) return 0;
            if (this.remiseGlobaleType === 'pourcentage') return Math.round(this.sousTotal * this.remiseGlobaleValeur / 100);
            return this.remiseGlobaleValeur;
        },
        get totalHT() { return Math.max(0, this.sousTotal - this.remiseGlobale); },
        get totalTTC() { return Math.round(this.totalHT * (1 + (this.tva || 0) / 100)); },
        formatPrix(v) { return new Intl.NumberFormat('fr-FR').format(v); },
        clientsList: clientsInit,
        clientChoisi: null,
        newClientOpen: false,
        newClientSaving: false,
        newClientError: '',
        newClient: { type_client: 'personne_physique', prenom: '', nom: '', raison_sociale: '', telephone: '', email: '', date_naissance_mois: '', date_naissance_jour: '', notes: '', adresse: '', piece_identite: '' },
        selectClient(client) { this.clientChoisi = client; },
        retirerClient() { this.clientChoisi = null; },
        async creerClient() {
            this.newClientError = '';
            const nc = this.newClient;
            if (nc.type_client === 'personne_physique') {
                if (!nc.prenom.trim() || !nc.nom.trim()) { this.newClientError = 'Prénom et nom sont requis.'; return; }
            } else {
                if (!nc.raison_sociale.trim()) { this.newClientError = 'La raison sociale est requise.'; return; }
            }
            if (!nc.telephone.trim()) { this.newClientError = 'Le téléphone est requis.'; return; }
            this.newClientSaving = true;
            try {
                const payload = {
                    type_client: nc.type_client,
                    prenom: nc.prenom || null,
                    nom: nc.nom || null,
                    raison_sociale: nc.raison_sociale || null,
                    telephone: nc.telephone,
                    email: nc.email || null,
                    date_naissance: nc.date_naissance_mois && nc.date_naissance_jour ? nc.date_naissance_mois + '-' + nc.date_naissance_jour : null,
                    notes: nc.notes || null,
                    adresse: nc.adresse || null,
                    piece_identite: nc.piece_identite || null,
                };
                const res = await fetch('{{ route('dashboard.clients.quick-store') }}', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').getAttribute('content'),'Accept':'application/json'},
                    body: JSON.stringify(payload),
                });
                if (!res.ok) { const err = await res.json(); this.newClientError = err.message || 'Erreur lors de la création.'; return; }
                const client = await res.json();
                this.clientsList.unshift(client);
                this.clientChoisi = client;
                this.newClient = { type_client:'personne_physique', prenom:'',nom:'',raison_sociale:'',telephone:'',email:'',date_naissance_mois:'',date_naissance_jour:'',notes:'',adresse:'',piece_identite:'' };
                this.newClientOpen = false;
                document.body.classList.remove('overflow-hidden');
            } catch (e) { this.newClientError = 'Erreur réseau. Réessayez.'; }
            finally { this.newClientSaving = false; }
        },
    }));
});
</script>
@endpush
</x-dashboard-layout>
