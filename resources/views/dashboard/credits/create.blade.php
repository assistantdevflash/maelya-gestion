<x-dashboard-layout>
{{-- Même largeur que la liste des crédits (conteneur max-w-7xl du layout) --}}
<div class="space-y-6" x-data="creditForm(@js($allClients->toArray()), @js($catalogue->toArray()), @js($prefill ?? null))">
    <div class="flex items-center gap-4">
        <a href="{{ route('dashboard.credits.index') }}" class="p-2 text-gray-400 hover:text-gray-600 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 transition">←</a>
        <div><h1 class="text-2xl font-bold text-gray-900 dark:text-white">Nouveau crédit client</h1></div>
    </div>

    @if(! empty($venteSource))
    <div class="flex items-start gap-3 p-4 rounded-xl border border-blue-200 dark:border-blue-800/50 bg-blue-50 dark:bg-blue-950/30">
        <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div class="min-w-0">
            <p class="text-sm font-bold text-blue-800 dark:text-blue-300">
                Crédit créé depuis la vente {{ $venteSource['numero'] ?? '' }}
            </p>
            <p class="text-xs text-blue-700 dark:text-blue-400/90 mt-0.5">
                Les articles et le client ont été pré-remplis depuis la vente
                @if(! empty($venteSource['total']))
                    ({{ number_format($venteSource['total'], 0, ',', ' ') }} F)
                @endif.
                Vérifiez les modalités avant d'enregistrer — la vente d'origine reste inchangée.
            </p>
        </div>
    </div>
    @endif

    <form method="POST" action="{{ route('dashboard.credits.store') }}">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-5">
                {{-- Lignes --}}
                <div class="card" style="overflow: visible">
                    <div class="card-header">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Articles / Prestations</h2>
                    </div>
                    <div class="card-body space-y-3">
                        <template x-for="(ligne, i) in lignes" :key="i">
                            <div class="flex flex-wrap items-center gap-2 p-3 bg-gray-50 dark:bg-slate-800 rounded-xl" x-transition.opacity.duration.300>
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
                        <div class="text-right pt-1">
                            <button type="button" @click="ajouterLigne()" class="inline-flex items-center gap-1.5 text-sm text-primary-600 hover:text-primary-700 font-medium transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                Ajouter une ligne
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Total --}}
                <div class="card">
                    <div class="card-body">
                        <div class="flex justify-between text-lg font-bold">
                            <span class="text-gray-500">Total</span>
                            <span class="text-primary-600" x-text="formatPrix(sousTotal) + ' FCFA'"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-5">
                {{-- Client --}}
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

                        {{-- Alerte : le client a déjà un encours (en cours ou en retard) --}}
                        <div x-show="creditsEnCours.length > 0" x-cloak
                             class="rounded-xl border border-red-200 dark:border-red-800/50 bg-red-50 dark:bg-red-950/30 p-3">
                            <div class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                </svg>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-bold text-red-800 dark:text-red-300">
                                        Ce client a déjà <span x-text="creditsEnCours.length"></span>
                                        <span x-text="creditsEnCours.length > 1 ? 'crédits non soldés' : 'crédit non soldé'"></span>
                                    </p>
                                    <p class="text-[11px] text-red-700 dark:text-red-400/90 mt-0.5">
                                        Reste dû : <strong x-text="totalDuCredits"></strong>
                                    </p>
                                    <p class="text-[11px] text-red-700 dark:text-red-400/90 mt-0.5" x-show="plafondLabel" x-cloak>
                                        Plafond autorisé : <strong x-text="plafondLabel"></strong>
                                        · disponible : <strong x-text="resteDisponibleLabel"></strong>
                                    </p>
                                    <p class="text-[11px] mt-1" x-show="scoreClient" x-cloak>
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded font-bold"
                                              :class="scoreClient && scoreClient.couleur === 'success' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : (scoreClient && scoreClient.couleur === 'warning' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300')"
                                              x-text="scoreClient ? ('Score ' + (scoreClient.score !== null ? scoreClient.score + '/100' : '—') + ' · ' + scoreClient.libelle) : ''"></span>
                                    </p>
                                </div>
                            </div>
                            <ul class="mt-2 space-y-1.5">
                                <template x-for="c in creditsEnCours" :key="c.id">
                                    <li>
                                        <a :href="c.url"
                                           class="block rounded-lg bg-white dark:bg-slate-900/50 px-2.5 py-2 hover:bg-white/80 dark:hover:bg-slate-900 transition-colors">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold"
                                                      :class="c.statut === 'retard' ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'"
                                                      x-text="c.statut_label"></span>
                                                <span class="text-[11px] font-bold text-gray-900 dark:text-white" x-text="c.reste_label"></span>
                                            </div>
                                            <p class="text-[10px] text-gray-500 dark:text-slate-400 mt-1" x-text="c.details"></p>
                                        </a>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        {{-- Dépassement de plafond --}}
                        <div x-show="plafondDepasse" x-cloak
                             class="rounded-xl border-2 border-red-400 dark:border-red-600 bg-red-100 dark:bg-red-950/50 p-3">
                            <p class="text-xs font-bold text-red-800 dark:text-red-300">
                                ⚠️ Plafond d'encours dépassé
                            </p>
                            <p class="text-[11px] text-red-700 dark:text-red-400 mt-0.5">
                                Plafond autorisé : <strong x-text="plafondLabel"></strong> —
                                encours actuel <strong x-text="formatPrix(encoursDu) + ' FCFA'"></strong>,
                                ce crédit porterait le total à <strong x-text="formatPrix(totalAvecCredit) + ' FCFA'"></strong>.
                            </p>
                            <label class="mt-2 flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="forcer_plafond" value="1" x-model="forcerPlafond"
                                       class="mt-0.5 rounded border-red-300 text-red-600 focus:ring-red-500">
                                <span class="text-[11px] font-semibold text-red-800 dark:text-red-300">Passer outre le plafond (décision assumée)</span>
                            </label>
                        </div>
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
                                    <p x-show="open && search.length >= 2 && filtered.length === 0" x-cloak
                                        class="text-xs text-gray-400 mt-2 text-center py-2">Aucun client trouvé</p>
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

                {{-- Modalités --}}
                <div class="card">
                    <div class="card-header"><h2 class="text-lg font-semibold">Modalités de crédit</h2></div>
                    <div class="card-body space-y-3">
                        <div>
                            <label class="form-label">Apport initial (FCFA)</label>
                            <input type="number" name="apport_initial" x-model.number="apport" min="0" :max="sousTotal"
                                   class="form-input text-sm" value="{{ old('apport_initial', 0) }}">
                            <p class="text-xs text-gray-400 mt-1">Reste à payer : <strong x-text="formatPrix(reste) + ' FCFA'"></strong></p>
                        </div>
                        <div>
                            <label class="form-label">Nombre d'échéances</label>
                            <input type="number" name="nb_echeances" min="1" max="24" x-model.number="nbEcheances"
                                   value="{{ old('nb_echeances', 3) }}" class="form-input text-sm">
                        </div>
                        <div>
                            <label class="form-label">Fréquence</label>
                            <select name="frequence" x-model="frequence" class="form-input text-sm">
                                <option value="mensuelle" {{ old('frequence') === 'mensuelle' ? 'selected' : '' }}>Mensuelle</option>
                                <option value="hebdomadaire" {{ old('frequence') === 'hebdomadaire' ? 'selected' : '' }}>Hebdomadaire</option>
                            </select>
                        </div>

                        {{-- Aperçu de l'échéancier --}}
                        <div class="rounded-xl bg-gray-50 dark:bg-slate-800/60 border border-gray-100 dark:border-slate-700 p-3"
                             x-show="echeancierPreview.length > 0" x-cloak>
                            <p class="text-[11px] font-bold uppercase tracking-wide text-gray-500 dark:text-slate-400 mb-2">Aperçu de l'échéancier</p>
                            <div class="space-y-1 max-h-40 overflow-y-auto">
                                <template x-for="(e, i) in echeancierPreview" :key="i">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-gray-500 dark:text-slate-400" x-text="'Échéance ' + (i + 1) + ' — ' + e.date"></span>
                                        <span class="font-mono font-bold text-gray-900 dark:text-white" x-text="formatPrix(e.montant) + ' F'"></span>
                                    </div>
                                </template>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-2">
                                Aperçu indicatif. La première échéance est fixée à 1 mois après aujourd'hui
                                (ou 7 jours en hebdomadaire).
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Notes --}}
                <div class="card">
                    <div class="card-header"><h2 class="text-lg font-semibold">Notes</h2></div>
                    <div class="card-body"><textarea name="notes" rows="3" placeholder="Notes internes..." class="form-textarea text-sm">{{ old('notes') }}</textarea></div>
                </div>

                <input type="hidden" name="lignes" :value="JSON.stringify(lignes)">
                <button type="submit" class="btn-primary w-full" :disabled="lignes.length === 0 || sousTotal <= 0 || !clientChoisi">
                    Créer le crédit
                </button>
            </div>
        </div>
    </form>

    {{-- Modal nouveau client (identique devis) --}}
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
                <button @click="newClientOpen = false; document.body.classList.remove('overflow-hidden')" class="modal-close">✕</button>
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
                                <div class="form-group mb-0">
                                    <label class="form-label">Prénom *</label>
                                    <input type="text" x-model="newClient.prenom" maxlength="50" class="form-input" placeholder="Fatou">
                                </div>
                                <div class="form-group mb-0">
                                    <label class="form-label">Nom *</label>
                                    <input type="text" x-model="newClient.nom" maxlength="50" class="form-input" placeholder="Traoré">
                                </div>
                            </div>
                        </template>
                        {{-- Entreprise --}}
                        <template x-if="newClient.type_client === 'entreprise'">
                            <div class="col-span-2 form-group mb-0">
                                <label class="form-label">Raison sociale *</label>
                                <input type="text" x-model="newClient.raison_sociale" maxlength="255" class="form-input" placeholder="SARL Exemple & Cie">
                            </div>
                        </template>
                        <div class="form-group mb-0">
                            <label class="form-label">Téléphone *</label>
                            <input type="text" x-model="newClient.telephone" maxlength="30" class="form-input" placeholder="+225 07 00 00 00">
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Email</label>
                            <input type="email" x-model="newClient.email" maxlength="255" class="form-input" placeholder="contact@exemple.ci">
                        </div>
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
                                        <template x-for="d in 31" :key="d">
                                            <option :value="String(d).padStart(2,'0')" x-text="d"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                        </template>
                        <div class="col-span-2 form-group mb-0">
                            <label class="form-label">Notes</label>
                            <textarea x-model="newClient.notes" rows="2" maxlength="1000" class="form-input resize-none" placeholder="Allergies, préférences..."></textarea>
                        </div>
                        {{-- Informations supplémentaires --}}
                        <div class="col-span-2" x-data="{ showExtra: false }">
                            <button type="button" @click="showExtra = !showExtra"
                                    class="flex items-center gap-2 text-xs font-medium text-gray-500 hover:text-gray-700 transition-colors">
                                <svg class="w-3.5 h-3.5 transition-transform" :class="showExtra ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                                Informations supplémentaires
                            </button>
                            <div x-show="showExtra" x-collapse class="mt-3 space-y-3">
                                <div class="form-group mb-0">
                                    <label class="form-label">Adresse</label>
                                    <input type="text" x-model="newClient.adresse" maxlength="255" class="form-input" placeholder="Abidjan, Cocody...">
                                </div>
                                <div class="form-group mb-0">
                                    <label class="form-label">Pièce d'identité</label>
                                    <input type="text" x-model="newClient.piece_identite" maxlength="100" class="form-input" placeholder="N° CNI, Passeport...">
                                </div>
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

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('creditForm', (clientsInit, catalogueInit, editData) => ({
        lignes: (editData?.lignes?.length
            ? editData.lignes.map(l => ({...l, pickerOpen: false, pickerSearch: ''}))
            : [{designation:'', quantite:1, prix_unitaire:0, pickerOpen: false, pickerSearch: ''}]),
        catalogue: catalogueInit,
        init() {
            // Pré-remplissage depuis une vente : on charge l'encours du client
            if (this.clientChoisi?.id) {
                this.chargerCreditsClient(this.clientChoisi.id);
            }
        },
        ajouterLigne() { this.lignes.push({designation:'', quantite:1, prix_unitaire:0, pickerOpen: false, pickerSearch: ''}); },
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
        apport: editData?.apport ?? {{ old('apport_initial', 0) }},
        get reste() { return Math.max(0, this.sousTotal - this.apport); },
        nbEcheances: {{ (int) old('nb_echeances', 3) }},
        frequence: @js(old('frequence', 'mensuelle')),
        plafond: null,
        plafondLabel: '',
        resteDisponibleLabel: '',
        encoursDu: 0,
        scoreClient: null,
        forcerPlafond: false,
        get totalAvecCredit() { return this.encoursDu + this.reste; },
        get plafondDepasse() {
            return this.plafond !== null && this.plafond > 0 && this.totalAvecCredit > this.plafond;
        },
        get echeancierPreview() {
            const n = Math.max(1, Math.min(24, parseInt(this.nbEcheances) || 1));
            if (!this.reste || this.reste <= 0) return [];
            const base = Math.floor(this.reste / n);
            const solde = this.reste - base * n;
            const pas = this.frequence === 'hebdomadaire' ? 7 : 30;
            const out = [];
            for (let i = 1; i <= n; i++) {
                const d = new Date();
                d.setDate(d.getDate() + pas * i);
                out.push({
                    date: d.toLocaleDateString('fr-FR'),
                    montant: base + (i === n ? solde : 0),
                });
            }
            return out;
        },
        formatPrix(v) { return new Intl.NumberFormat('fr-FR').format(v); },
        clientsList: clientsInit,
        clientChoisi: editData?.client ?? null,
        creditsEnCours: [],
        totalDuCredits: '',
        chargementCredits: false,
        newClientOpen: false,
        newClientSaving: false,
        newClientError: '',
        newClient: { type_client: 'personne_physique', prenom: '', nom: '', raison_sociale: '', telephone: '', email: '', date_naissance_mois: '', date_naissance_jour: '', notes: '', adresse: '', piece_identite: '' },
        selectClient(client) {
            this.clientChoisi = client;
            this.chargerCreditsClient(client.id);
        },
        retirerClient() {
            this.clientChoisi = null;
            this.creditsEnCours = [];
            this.totalDuCredits = '';
            this.plafond = null;
            this.plafondLabel = '';
            this.resteDisponibleLabel = '';
            this.encoursDu = 0;
            this.scoreClient = null;
            this.forcerPlafond = false;
        },
        async chargerCreditsClient(clientId) {
            this.creditsEnCours = [];
            this.totalDuCredits = '';
            this.plafond = null;
            this.plafondLabel = '';
            this.resteDisponibleLabel = '';
            this.encoursDu = 0;
            this.scoreClient = null;
            this.forcerPlafond = false;
            if (!clientId) return;
            this.chargementCredits = true;
            try {
                const res = await fetch(`{{ url('/dashboard/credits/client') }}/${clientId}/encours`, {
                    headers: { 'Accept': 'application/json' },
                });
                if (res.ok) {
                    const data = await res.json();
                    this.creditsEnCours = data.credits || [];
                    this.totalDuCredits = data.total_du_label || '';
                    this.plafond = data.plafond ?? null;
                    this.plafondLabel = data.plafond_label || '';
                    this.resteDisponibleLabel = data.reste_disponible_label || '';
                    this.encoursDu = data.total_du ?? 0;
                    this.scoreClient = data.score ?? null;
                }
            } catch (e) {
                // silencieux : l'alerte est informative, elle ne doit pas bloquer la saisie
            } finally {
                this.chargementCredits = false;
            }
        },
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
                this.selectClient(client);
                this.newClient = { type_client:'personne_physique', prenom:'',nom:'',raison_sociale:'',telephone:'',email:'',date_naissance_mois:'',date_naissance_jour:'',notes:'',adresse:'',piece_identite:'' };
                this.newClientOpen = false;
                document.body.classList.remove('overflow-hidden');
            } catch (e) { this.newClientError = 'Erreur réseau.'; }
            finally { this.newClientSaving = false; }
        },
    }));
});
</script>
@endpush
</x-dashboard-layout>
