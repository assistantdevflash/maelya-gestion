    {{-- ═══ MODAL ANNULATION DE LA DERNIÈRE VENTE ═══ --}}
    {{-- Réservée aux admins : même règle que le bouton qui l'ouvre. --}}
    @if(auth()->user()->isAdmin())
    <div x-show="annulationOuverte" x-cloak class="modal-backdrop"
         style="z-index: 10000;"
         x-on:keydown.escape.window="fermerAnnulation()"
         x-init="$watch('annulationOuverte', v => document.body.classList.toggle('overflow-hidden', v))"
         @click.self="fermerAnnulation()">
        <div class="modal max-w-lg" x-transition @click.stop>
            <div class="modal-header">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-red-50 dark:bg-red-900/30">
                        <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="modal-title">Annuler la dernière vente</h3>
                        <p class="text-[11px] text-gray-400 mt-0.5">Vérifiez qu'il s'agit bien de la bonne vente</p>
                    </div>
                </div>
                <button type="button" @click="fermerAnnulation()" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="p-5 space-y-4">
                {{-- Chargement --}}
                <div x-show="annulationChargement" class="py-10 text-center">
                    <span class="spinner" aria-hidden="true"></span>
                    <p class="text-xs text-gray-400 mt-3">Recherche de la dernière vente…</p>
                </div>

                {{-- Aucune vente / erreur --}}
                <div x-show="!annulationChargement && !derniereVente" class="py-8 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-gray-50 dark:bg-slate-700/40 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-gray-300 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 014-4h4m0 0l-3-3m3 3l-3 3M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400"
                       x-text="annulationErreur || 'Aucune vente récente à annuler.'"></p>
                </div>

                {{-- Détail de la vente --}}
                <template x-if="derniereVente">
                    <div class="space-y-4">
                        {{-- Identification --}}
                        <div class="flex items-start justify-between gap-3 p-3 rounded-xl bg-gray-50 dark:bg-slate-700/40">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-gray-900 dark:text-white font-mono"
                                   x-text="'Vente ' + (derniereVente.numero || ('#' + derniereVente.id.slice(0, 8)))"></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="derniereVente.date"></p>
                                <p class="text-[11px] text-gray-400" x-text="'(' + derniereVente.depuis + ')'"></p>
                            </div>
                            <span class="px-2 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wide bg-white dark:bg-slate-800 text-gray-500 dark:text-gray-300 border border-gray-200 dark:border-slate-600 flex-shrink-0"
                                  x-text="derniereVente.mode_paiement_lib"></span>
                        </div>

                        {{-- Client / caissier --}}
                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-0.5">Client</p>
                                <p class="font-semibold text-gray-800 dark:text-gray-200 truncate"
                                   x-text="derniereVente.client || 'Client de passage'"></p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-0.5">Encaissé par</p>
                                <p class="font-semibold text-gray-800 dark:text-gray-200 truncate"
                                   x-text="derniereVente.caissier || '—'"></p>
                            </div>
                        </div>

                        {{-- Articles --}}
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">
                                    Articles
                                </p>
                                <p class="text-[10px] text-gray-400"
                                   x-text="derniereVente.nb_articles + (derniereVente.nb_articles > 1 ? ' unités' : ' unité')"></p>
                            </div>
                            <div class="rounded-xl border border-gray-200 dark:border-slate-600 divide-y divide-gray-100 dark:divide-slate-700 overflow-hidden max-h-48 overflow-y-auto">
                                <template x-for="(item, i) in derniereVente.items" :key="i">
                                    <div class="flex items-center gap-3 px-3 py-2 bg-white dark:bg-slate-800">
                                        <span class="w-6 h-6 rounded-md bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-300 text-[11px] font-bold flex items-center justify-center flex-shrink-0"
                                              x-text="item.quantite"></span>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-medium text-gray-800 dark:text-gray-200 truncate" x-text="item.nom"></p>
                                            <p class="text-[10px] text-gray-400" x-text="formatNumber(item.prix) + ' F l\'unité'"></p>
                                        </div>
                                        <span class="text-xs font-bold text-gray-900 dark:text-white whitespace-nowrap"
                                              x-text="formatNumber(item.sous_total) + ' F'"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Remise / pourboire --}}
                        <div class="space-y-1 text-xs">
                            <div x-show="derniereVente.remise > 0" class="flex justify-between text-emerald-600">
                                <span>Remise appliquée</span>
                                <span x-text="'− ' + formatNumber(derniereVente.remise) + ' F'"></span>
                            </div>
                            <div x-show="derniereVente.pourboire > 0" class="flex justify-between text-gray-500 dark:text-gray-400">
                                <span>Pourboire</span>
                                <span x-text="formatNumber(derniereVente.pourboire) + ' F'"></span>
                            </div>
                            <div class="flex justify-between items-center pt-2 border-t border-gray-100 dark:border-slate-700">
                                <span class="font-bold text-gray-900 dark:text-white">Total encaissé</span>
                                <span class="text-lg font-extrabold text-gray-900 dark:text-white"
                                      x-text="formatNumber(derniereVente.total) + ' F'"></span>
                            </div>
                        </div>

                        {{-- Avertissement --}}
                        <div class="flex gap-2 p-3 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/40">
                            <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <p class="text-[11px] text-amber-800 dark:text-amber-200 leading-relaxed">
                                Cette annulation est <strong>définitive</strong> : le stock des produits sera réintégré
                                et les points de fidélité gagnés seront retirés au client.
                            </p>
                        </div>

                        <p x-show="annulationErreur" x-text="annulationErreur"
                           class="text-xs text-red-600 dark:text-red-400"></p>
                    </div>
                </template>
            </div>

            <div class="modal-footer">
                <button type="button" @click="fermerAnnulation()"
                        class="btn btn-outline text-sm" :disabled="annulationEnvoi">
                    Retour
                </button>
                <button type="button"
                        @click="confirmerAnnulationDerniere()"
                        :disabled="!derniereVente || annulationEnvoi"
                        class="btn text-sm text-white inline-flex items-center gap-2"
                        :class="(!derniereVente || annulationEnvoi) ? 'opacity-50 cursor-not-allowed' : ''"
                        style="background: linear-gradient(135deg, #dc2626, #ef4444);">
                    <span x-show="annulationEnvoi" class="spinner spinner-sm" aria-hidden="true"></span>
                    <span x-text="annulationEnvoi ? 'Annulation…' : 'Oui, annuler cette vente'"></span>
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══ MODAL NOUVEAU CLIENT ═══ --}}
    <div x-show="newClientOpen" x-cloak class="modal-backdrop"
         style="z-index: 10000;"
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
                <button @click="newClientOpen = false; document.body.classList.remove('overflow-hidden')" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                @if($errors->any())
                <div class="mb-4 p-3 bg-red-50 rounded-xl text-sm text-red-600 space-y-0.5">
                    @foreach($errors->all() as $e)<p>• {{ $e }}</p>@endforeach
                </div>
                @endif
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="form-group mb-0">
                            <label class="form-label">Prénom *</label>
                            <input type="text" wire:model="newClientPrenom" maxlength="50" class="form-input" placeholder="Fatou">
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Nom *</label>
                            <input type="text" wire:model="newClientNom" maxlength="50" class="form-input" placeholder="Traoré">
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Téléphone *</label>
                            <input type="text" wire:model="newClientTelephone" maxlength="30" class="form-input" placeholder="+225 07 00 00 00">
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Email</label>
                            <input type="email" wire:model="newClientEmail" maxlength="255" class="form-input" placeholder="fatou@exemple.ci">
                        </div>
                        <div class="col-span-2 form-group mb-0">
                            <label class="form-label">Anniversaire (jour et mois)</label>
                            <div class="grid grid-cols-2 gap-2">
                                <select wire:model="newClientNaissanceMois" class="form-input">
                                    <option value="">Mois</option>
                                    <option value="01">Janvier</option>
                                    <option value="02">Février</option>
                                    <option value="03">Mars</option>
                                    <option value="04">Avril</option>
                                    <option value="05">Mai</option>
                                    <option value="06">Juin</option>
                                    <option value="07">Juillet</option>
                                    <option value="08">Août</option>
                                    <option value="09">Septembre</option>
                                    <option value="10">Octobre</option>
                                    <option value="11">Novembre</option>
                                    <option value="12">Décembre</option>
                                </select>
                                <select wire:model="newClientNaissanceJour" class="form-input">
                                    <option value="">Jour</option>
                                    @for($d = 1; $d <= 31; $d++)
                                    <option value="{{ str_pad($d, 2, '0', STR_PAD_LEFT) }}">{{ $d }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <div class="col-span-2 form-group mb-0">
                            <label class="form-label">Notes</label>
                            <textarea wire:model="newClientNotes" rows="2" maxlength="1000" class="form-input resize-none"
                                      placeholder="Allergies, préférences..."></textarea>
                        </div>
                        {{-- Informations supplémentaires (collapsible) --}}
                        <div class="col-span-2" x-data="{ showExtraCaisse: false }">
                            <button type="button" @click="showExtraCaisse = !showExtraCaisse"
                                    class="flex items-center gap-2 text-xs font-medium text-gray-500 hover:text-gray-700 transition-colors">
                                <svg class="w-3.5 h-3.5 transition-transform" :class="showExtraCaisse ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                                Informations supplémentaires
                            </button>
                            <div x-show="showExtraCaisse" x-collapse class="mt-3 space-y-3">
                                <div class="form-group mb-0">
                                    <label class="form-label">Adresse</label>
                                    <input type="text" wire:model="newClientAdresse" maxlength="255" class="form-input" placeholder="Abidjan, Cocody...">
                                </div>
                                <div class="form-group mb-0">
                                    <label class="form-label">Pièce d'identité</label>
                                    <input type="text" wire:model="newClientPieceIdentite" maxlength="100" class="form-input" placeholder="N° CNI, Passeport...">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-3 pt-1">
                        <button type="button" @click="newClientOpen = false; document.body.classList.remove('overflow-hidden')" class="btn btn-outline flex-1 justify-center">Annuler</button>
                        <button type="button" wire:click="ajouterClientRapide" class="btn-primary flex-1 justify-center">
                            <span wire:loading.remove wire:target="ajouterClientRapide">Enregistrer</span>
                            <span wire:loading wire:target="ajouterClientRapide" class="flex items-center gap-2">
                                <span class="spinner spinner-sm"></span> Ajout...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

{{-- ═══ MOBILE : panier flottant + drawer + toast (à l'intérieur du scope Alpine, x-teleport rend dans body pour un position:fixed fiable) ═══ --}}
<template x-teleport="body">
    <div>
        {{-- Bouton flottant --}}
        <div x-show="!panierVide && !showConfirmation"
             class="lg:hidden fixed z-50 left-4 right-4"
             style="bottom: 80px;"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-4">
            <button @click="toggleMobileCart()"
                    class="w-full flex items-center justify-between px-4 py-3 rounded-2xl shadow-2xl"
                    style="background: linear-gradient(135deg, #9333ea 0%, #ec4899 100%);">
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <span x-text="nbArticles" class="absolute -top-2 -right-2 w-5 h-5 rounded-full text-[10px] font-bold bg-white text-purple-600 flex items-center justify-center shadow"></span>
                    </div>
                    <div class="text-left">
                        <p class="text-[11px] font-medium text-white/80 leading-none" x-text="nbArticles + ' article' + (nbArticles > 1 ? 's' : '')"></p>
                        <p class="text-base font-bold text-white leading-tight" x-text="formatNumber(total) + ' F'"></p>
                    </div>
                </div>
                <span class="text-sm font-semibold text-white flex items-center gap-1">
                    Panier
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                    </svg>
                </span>
            </button>
        </div>

        {{-- Drawer modal --}}
        <div x-show="mobileCartOpen && !showConfirmation"
             @click.self="toggleMobileCart()"
             class="lg:hidden"
             style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 9999; background: rgba(0,0,0,0.6);"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="absolute left-0 right-0 bottom-0 bg-white dark:bg-slate-800 flex flex-col"
                 style="border-radius: 24px 24px 0 0; max-height: 90vh;"
                 @click.stop
                 x-data="{ codePromoOpen: false, paiementOpen: false }">
                {{-- Header --}}
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-slate-700 flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, rgba(147,51,234,0.15), rgba(236,72,153,0.15));">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white text-base">Panier</h3>
                            <p class="text-xs text-gray-400" x-text="nbArticles + ' article' + (nbArticles > 1 ? 's' : '')"></p>
                        </div>
                    </div>
                    <button @click="toggleMobileCart()" class="w-8 h-8 rounded-full bg-gray-100 dark:bg-slate-700 flex items-center justify-center text-gray-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                </div>
                {{-- Contenu scrollable (Client + Articles + Code promo + Paiement) --}}
                <div class="flex-1 overflow-y-auto">
                {{-- Client (sur mobile) --}}
                @if(auth()->user()->aFonctionnalite('caisse_client'))
                <div class="px-5 py-3 border-b border-gray-100 dark:border-slate-700">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Client</span>
                        </div>
                        @if($clientId)
                            <button wire:click="$set('clientId', null)"
                                wire:loading.attr="disabled"
                                wire:target="clientId"
                                class="inline-flex items-center gap-1.5 text-xs text-red-500 hover:text-red-700 font-medium disabled:opacity-60 disabled:cursor-wait">
                            <span wire:loading wire:target="clientId" class="spinner spinner-sm" aria-hidden="true"></span>
                            <span>Retirer</span>
                        </button>
                        @endif
                    </div>
                    @if($clientId)
                        @if($this->selectedClient)
                        <div class="flex items-center gap-2.5 p-2.5 bg-primary-50/50 dark:bg-primary-900/20 rounded-xl">
                            @if($this->selectedClient->isEntreprise())
                                <div class="w-8 h-8 bg-gradient-to-br from-purple-400 to-pink-400 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                    🏢
                                </div>
                            @else
                                <div class="w-8 h-8 bg-gradient-to-br from-primary-400 to-secondary-400 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                    {{ strtoupper(substr($this->selectedClient->prenom ?? '', 0, 1)) }}
                                </div>
                            @endif
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $this->selectedClient->nom_affichage }}</span>
                        </div>
                        @endif
                    @else
                        <div x-data="selecteurClient({ initial: {{ $allClients->toJson() }} })"
                             @click.outside="open = false">
                            <div class="relative">
                                <input
                                    type="text"
                                    x-model="search"
                                    @focus="open = true"
                                    @input="onInput()"
                                    @keydown.escape="open = false"
                                    :disabled="selectionEnCours"
                                    placeholder="Chercher un client..."
                                    class="form-input text-sm w-full pr-9 disabled:opacity-60 disabled:cursor-wait">
                                <span x-show="selectionEnCours" x-cloak
                                      class="absolute right-3 top-1/2 -translate-y-1/2">
                                    <span class="spinner spinner-sm" aria-hidden="true"></span>
                                </span>
                            </div>
                            <div x-show="open && filtered.length > 0" x-cloak
                                 class="absolute left-5 right-5 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden mt-2 shadow-lg max-h-52 overflow-y-auto bg-white dark:bg-gray-800 z-10">
                                <template x-for="c in filtered" :key="c.id">
                                    <button type="button"
                                            @click="choose(c.id)"
                                            class="w-full text-left px-3 py-2.5 text-sm hover:bg-primary-50/50 dark:hover:bg-gray-700 flex items-center gap-2.5 border-b border-gray-100 dark:border-gray-700 last:border-b-0 transition-colors">
                                        <div class="w-7 h-7 bg-gradient-to-br from-primary-100 to-secondary-100 rounded-full flex items-center justify-center text-primary-700 text-xs font-bold"
                                             x-text="c.initiale"></div>
                                        <span class="font-medium text-gray-900 dark:text-white" x-text="c.nom"></span>
                                        <span class="text-gray-400 text-xs ml-auto" x-text="c.telephone"></span>
                                    </button>
                                </template>
                            </div>
                            <div x-show="open && chargement" x-cloak
                                 class="text-xs text-gray-400 mt-2 text-center py-2">
                                Recherche…
                            </div>
                            <div x-show="open && !chargement && search.length >= 2 && filtered.length === 0" x-cloak
                                 class="text-xs text-gray-400 mt-2 text-center py-2">
                                Aucun client trouvé
                            </div>
                        </div>

                        {{-- Bouton + Nouveau client --}}
                        <button @click="newClientOpen = true" class="mt-2 flex items-center gap-1.5 text-xs text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 font-medium transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Nouveau client
                        </button>
                    @endif
                </div>
                @endif

                {{-- Articles --}}
                <div>
                    <template x-for="key in panierKeys" :key="'mob_' + key">
                        <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-700">
                            <div class="flex items-center gap-3">
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-gray-900 dark:text-white truncate" x-text="panier[key].nom"></p>
                                    <p class="text-sm text-gray-400 mt-0.5" x-text="formatNumber(panier[key].prix) + ' F'"></p>
                                </div>
                                <button @click="supprimerItem(key)" class="w-8 h-8 rounded-lg bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-500 flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="flex items-center justify-between mt-3">
                                <div class="flex items-center gap-3">
                                    <button @click="decrementer(key)" class="w-9 h-9 rounded-xl bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-200 font-bold text-xl flex items-center justify-center">−</button>
                                    <span class="w-8 text-center font-bold text-gray-900 dark:text-white" x-text="panier[key].quantite"></span>
                                    <button @click="incrementer(key)" class="w-9 h-9 rounded-xl text-white font-bold text-xl flex items-center justify-center" style="background: linear-gradient(135deg, #9333ea, #ec4899);">+</button>
                                </div>
                                <span class="text-base font-bold text-gray-900 dark:text-white" x-text="formatNumber(panier[key].prix * panier[key].quantite) + ' F'"></span>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Code promo --}}
                @if(auth()->user()->aFonctionnalite('caisse_code_promo'))
                <div class="border-t border-gray-100 dark:border-slate-700">
                    <button @click="codePromoOpen = !codePromoOpen" type="button" class="w-full px-5 py-3 flex items-center justify-between text-left hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            <span class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Code promo</span>
                            <span x-show="codePromo" class="text-xs text-emerald-600 font-semibold" x-text="codePromo ? codePromo.code : ''"></span>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="codePromoOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="codePromoOpen" x-collapse class="px-5 pb-3">
                        <template x-if="codePromo">
                        <div class="flex items-center justify-between p-2.5 rounded-xl" style="background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2);">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                                <span class="text-xs font-bold text-emerald-700 font-mono" x-text="codePromo.code"></span>
                                <span class="text-xs text-emerald-600" x-text="'-' + formatNumber(codePromo.remise) + ' F'"></span>
                            </div>
                            <button @click="retirerCode" class="text-emerald-500 hover:text-red-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                    <template x-if="!codePromo">
                        <div class="flex gap-2">
                            <input type="text" x-model="codePromoInput"
                                   placeholder="Code promo"
                                   class="form-input flex-1 text-sm font-mono uppercase tracking-widest"
                                   @input="$el.value = $el.value.toUpperCase()"
                                   @keydown.enter="appliquerCode">
                            <button @click="appliquerCode" :disabled="codePromoLoading" class="btn btn-outline text-sm px-3 flex-shrink-0">
                                <span x-show="codePromoLoading" class="spinner spinner-sm" aria-hidden="true"></span>
                                <span x-show="!codePromoLoading">OK</span>
                            </button>
                        </div>
                        <p x-show="codePromoErreur" x-text="codePromoErreur" class="text-xs text-red-500 mt-1"></p>
                    </template>
                    </div>
                </div>
                @endif

                {{-- Modes de paiement --}}
                <div class="border-t border-gray-100 dark:border-slate-700">
                    <button @click="paiementOpen = !paiementOpen" type="button" class="w-full px-5 py-3 flex items-center justify-between text-left hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                            <span class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Mode de paiement</span>
                            <span class="text-xs text-primary-600 dark:text-primary-400 font-semibold" x-text="modePaiement === 'cash' ? '💵 Espèces' : (modePaiement === 'carte' ? '💳 Carte' : (modePaiement === 'mobile_money' ? '📱 Mobile' : (modePaiement === 'mixte' ? '🔀 Mixte' : (modePaiement === 'credit' ? '🕐 Crédit' : ''))))"></span>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="paiementOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="paiementOpen" x-collapse class="px-5 pb-3">
                    <div class="flex gap-2 justify-center">
                        <button @click="modePaiement = 'cash'"
                                :class="modePaiement === 'cash' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/30 shadow-sm scale-110' : 'border-gray-200 dark:border-slate-600 hover:border-gray-300'"
                                class="w-12 h-12 rounded-xl text-2xl border-2 transition-all duration-200 flex items-center justify-center">
                            💵
                        </button>
                        <button @click="modePaiement = 'carte'"
                                :class="modePaiement === 'carte' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 shadow-sm scale-110' : 'border-gray-200 dark:border-slate-600 hover:border-gray-300'"
                                class="w-12 h-12 rounded-xl text-2xl border-2 transition-all duration-200 flex items-center justify-center">
                            💳
                        </button>
                        <button @click="modePaiement = 'mobile_money'"
                                :class="modePaiement === 'mobile_money' ? 'border-orange-500 bg-orange-50 dark:bg-orange-900/30 shadow-sm scale-110' : 'border-gray-200 dark:border-slate-600 hover:border-gray-300'"
                                class="w-12 h-12 rounded-xl text-2xl border-2 transition-all duration-200 flex items-center justify-center">
                            📱
                        </button>
                        <button @click="modePaiement = 'mixte'"
                                :class="modePaiement === 'mixte' ? 'border-violet-500 bg-violet-50 dark:bg-violet-900/30 shadow-sm scale-110' : 'border-gray-200 dark:border-slate-600 hover:border-gray-300'"
                                class="w-12 h-12 rounded-xl text-2xl border-2 transition-all duration-200 flex items-center justify-center">
                            🔀
                        </button>
                        @if($hasCredits && ! $simple)
                        <button @click="modePaiement = 'credit'"
                                :class="modePaiement === 'credit' ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/30 shadow-sm scale-110' : 'border-gray-200 dark:border-slate-600 hover:border-gray-300'"
                                class="w-12 h-12 rounded-xl text-2xl border-2 transition-all duration-200 flex items-center justify-center">
                            🕐
                        </button>
                        @endif
                    </div>

                    {{-- Référence Mobile Money --}}
                    <template x-if="modePaiement === 'mobile_money'">
                        <div class="mt-3">
                            <input type="text" x-model="referencePaiement"
                                   placeholder="Référence transaction (optionnel)"
                                   class="form-input text-sm w-full">
                        </div>
                    </template>

                    {{-- Montant remis (Espèces) --}}
                    <template x-if="modePaiement === 'cash'">
                        <div class="mt-3">
                            <label class="text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-1.5 block">💵 Montant remis par le client</label>
                            <input type="number" x-model.number="montantRemis"
                                   class="form-input text-sm w-full" placeholder="0" min="0">
                            <template x-if="montantRemis && montantRemis >= total">
                                <div class="mt-2 flex justify-between items-center p-2 rounded-lg bg-emerald-50 dark:bg-emerald-900/20">
                                    <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-300">À rendre</span>
                                    <span class="text-sm font-bold text-emerald-700 dark:text-emerald-300" x-text="formatNumber(montantRemis - total) + ' F'"></span>
                                </div>
                            </template>
                        </div>
                    </template>

                    {{-- Paiement Mixte --}}
                    <template x-if="modePaiement === 'mixte'">
                        <div class="mt-3 space-y-2">
                            <label class="text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">🔀 Répartition du paiement</label>
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <label class="text-xs text-gray-500 dark:text-gray-400">💵 Espèces</label>
                                    <input type="number" x-model.number="montantMixteCash"
                                           class="form-input text-sm mt-1 w-full" placeholder="0" min="0" :max="total">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 dark:text-gray-400">💳 Carte</label>
                                    <input type="number" x-model.number="montantMixteCartes"
                                           class="form-input text-sm mt-1 w-full" placeholder="0" min="0" :max="total">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 dark:text-gray-400">📱 Mobile</label>
                                    <input type="number" x-model.number="montantMixteMobile"
                                           class="form-input text-sm mt-1 w-full" placeholder="0" min="0" :max="total">
                                </div>
                            </div>
                            <div :class="resteMixte === 0 ? 'bg-green-50 dark:bg-green-900/20' : (resteMixte < 0 ? 'bg-red-50 dark:bg-red-900/20' : 'bg-amber-50 dark:bg-amber-900/20')"
                                 class="flex justify-between items-center p-2 rounded-lg">
                                <span class="text-xs font-semibold"
                                      :class="resteMixte === 0 ? 'text-green-700 dark:text-green-300' : (resteMixte < 0 ? 'text-red-700 dark:text-red-300' : 'text-amber-700 dark:text-amber-300')"
                                      x-text="resteMixte === 0 ? '✓ Montants OK' : (resteMixte < 0 ? 'Dépassement' : 'Reste à ventiler')"></span>
                                <span class="text-sm font-bold"
                                      :class="resteMixte === 0 ? 'text-green-700 dark:text-green-300' : (resteMixte < 0 ? 'text-red-700 dark:text-red-300' : 'text-amber-700 dark:text-amber-300')"
                                      x-text="resteMixte !== 0 ? formatNumber(Math.abs(resteMixte)) + ' F' : ''"></span>
                            </div>
                            <input type="text" x-model="referencePaiement"
                                   placeholder="Référence mobile/carte (optionnel)"
                                   class="form-input text-sm w-full">
                        </div>
                    </template>

                    {{-- Paiement à Crédit --}}
                    @if($hasCredits)
                    <template x-if="modePaiement === 'credit'">
                        <div class="mt-3 space-y-2 p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg border border-emerald-200 dark:border-emerald-700">
                            <label class="text-xs font-bold text-emerald-700 dark:text-emerald-300 uppercase tracking-wider">🕐 Vente à crédit</label>
                            <div>
                                <label class="text-xs text-gray-600 dark:text-gray-400">Apport initial (optionnel)</label>
                                <input type="number" x-model.number="creditApport" min="0" :max="total"
                                       class="form-input text-sm mt-1 w-full" placeholder="0">
                            </div>
                            <div class="bg-white dark:bg-slate-800 rounded-lg p-2 text-xs space-y-1">
                                <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-300">Total vente</span><strong class="text-gray-900 dark:text-white" x-text="formatNumber(total) + ' F'"></strong></div>
                                <div class="flex justify-between text-emerald-600 dark:text-emerald-400"><span>Apport</span><strong x-text="formatNumber(parseInt(creditApport)||0) + ' F'"></strong></div>
                                <div class="flex justify-between text-red-600 dark:text-red-400 font-bold pt-1 border-t dark:border-slate-700"><span>Reste à payer</span><strong x-text="formatNumber(Math.max(0, total-(parseInt(creditApport)||0))) + ' F'"></strong></div>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-xs text-gray-600 dark:text-gray-400">Échéances</label>
                                    <select x-model.number="creditNbEcheances" class="form-input text-sm mt-1 w-full">
                                        <option value="2">2</option><option value="3">3</option><option value="4">4</option>
                                        <option value="6">6</option><option value="12">12</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-600 dark:text-gray-400">Fréquence</label>
                                    <select x-model="creditFrequence" class="form-input text-sm mt-1 w-full">
                                        <option value="mensuelle">Mensuelle</option>
                                        <option value="hebdomadaire">Hebdomadaire</option>
                                    </select>
                                </div>
                            </div>
                            {{-- Infos client crédit --}}
                            <div x-show="$wire.clientId" class="space-y-2 bg-white dark:bg-slate-800 rounded-lg p-2.5 text-xs">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-500 dark:text-gray-400">Tél :</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $this->selectedClientTel ?? '—' }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-500 dark:text-gray-400 flex-shrink-0">Adresse :</span>
                                    <input type="text" wire:model.blur="selectedClientAdresse" wire:change="updateClientInfosCredit"
                                           class="flex-1 bg-transparent border-0 border-b border-gray-300 dark:border-slate-600 px-1 py-0.5 text-xs focus:border-emerald-500 focus:ring-0 dark:text-white"
                                           placeholder="Adresse du client">
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-500 dark:text-gray-400 flex-shrink-0">Pièce ID :</span>
                                    <input type="text" wire:model.blur="selectedClientPieceId" wire:change="updateClientInfosCredit"
                                           class="flex-1 bg-transparent border-0 border-b border-gray-300 dark:border-slate-600 px-1 py-0.5 text-xs focus:border-emerald-500 focus:ring-0 dark:text-white"
                                           placeholder="N° CNI, Passeport...">
                                </div>
                            </div>
                            <div x-show="!$wire.clientId" class="text-xs text-red-600 dark:text-red-400 font-semibold bg-red-50 dark:bg-red-900/20 p-2 rounded">
                                ⚠️ Client obligatoire pour vente à crédit
                            </div>
                        </div>
                    </template>
                    @endif
                    </div>
                </div>
                </div>
                {{-- Fin contenu scrollable --}}

                {{-- Footer --}}
                <div class="px-5 py-4 bg-gray-50 dark:bg-slate-900 flex-shrink-0" style="border-top: 1px solid rgba(229,231,235,1);">
                    <div class="flex items-center justify-between mb-3">
                        <span class="font-semibold text-gray-600 dark:text-gray-400">Total</span>
                        <span class="text-2xl font-bold text-gray-900 dark:text-white" x-text="formatNumber(total) + ' F'"></span>
                    </div>
                    
                    {{-- Footer normal (tous modes sauf crédit) --}}
                    <div x-show="modePaiement !== 'credit'" class="flex gap-3">
                        <button @click="viderPanier(); toggleMobileCart();"
                                class="flex-1 py-3 rounded-xl font-semibold text-sm text-red-600 dark:text-red-400"
                                style="border: 2px solid rgba(239,68,68,0.4);">
                            Vider
                        </button>
                        <button @click="toggleMobileCart(); ouvrirConfirmation();"
                                :disabled="!mixtePret"
                                :class="!mixtePret ? 'opacity-50' : ''"
                                class="flex-[2] py-3 rounded-xl font-bold text-sm text-white"
                                style="background: linear-gradient(135deg, #9333ea 0%, #ec4899 100%);">
                            Encaisser <span x-text="formatNumber(total) + ' F'"></span>
                        </button>
                    </div>

                    {{-- Footer mode crédit --}}
                    @if($hasCredits)
                    <div x-show="modePaiement === 'credit'">
                        <button @click="validerVenteCredit()"
                                :disabled="!$wire.clientId || (total-(parseInt(creditApport)||0)) <= 0"
                                class="w-full py-3 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-emerald-500 to-teal-600 disabled:opacity-50">
                            🕐 Enregistrer la vente à crédit
                        </button>
                        <button @click="modePaiement = 'cash'"
                                class="w-full mt-2 py-2 text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                            ← Changer de mode de paiement
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Toast --}}
        <div x-show="toastVisible"
             class="lg:hidden"
             style="position: fixed; top: 72px; left: 50%; transform: translateX(-50%); z-index: 9999; pointer-events: none;"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            <div class="px-5 py-2.5 rounded-xl shadow-xl text-sm font-semibold text-white whitespace-nowrap"
                 style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                <span x-text="toastMessage"></span>
            </div>
        </div>

        {{-- Modal confirmation crédit (mobile) --}}
        @if($hasCredits)
        <div x-show="showCreditConfirmation" class="lg:hidden fixed inset-0 flex items-center justify-center p-4" 
             style="z-index: 10001; background: rgba(0,0,0,0.5);"
             @click.self="showCreditConfirmation = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 @click.stop>
                {{-- Header --}}
                <div class="p-5 border-b border-gray-100 dark:border-slate-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Confirmer la vente à crédit</h3>
                    </div>
                    <button @click="showCreditConfirmation = false" class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 flex items-center justify-center">
                        <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                {{-- Body --}}
                <div class="p-5 space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Total vente</span>
                        <strong class="text-gray-900 dark:text-white" x-text="formatNumber(total) + ' F'"></strong>
                    </div>
                    <div class="flex justify-between text-emerald-600 dark:text-emerald-400">
                        <span>Apport initial</span>
                        <strong x-text="formatNumber(parseInt(creditApport)||0) + ' F'"></strong>
                    </div>
                    <div class="flex justify-between text-red-600 dark:text-red-400 font-bold pt-2 border-t dark:border-slate-700">
                        <span>Reste à payer</span>
                        <strong x-text="formatNumber(Math.max(0, total-(parseInt(creditApport)||0))) + ' F'"></strong>
                    </div>
                    <div class="flex justify-between text-gray-500 dark:text-gray-400">
                        <span>Échéancier</span>
                        <span x-text="creditNbEcheances + ' × ' + (creditFrequence === 'mensuelle' ? 'mois' : 'semaines')"></span>
                    </div>
                    <div x-show="$wire.clientId" class="bg-gray-50 dark:bg-slate-700/50 rounded-lg p-2.5">
                        <div class="flex justify-between mb-1">
                            <span class="text-gray-500 dark:text-gray-400">Client</span>
                            <span class="font-semibold text-gray-900 dark:text-white" x-text="$wire.selectedClientNom || ''"></span>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 space-y-0.5">
                            <div><strong>Tél:</strong> {{ $this->selectedClientTel ?? '—' }}</div>
                            <div><strong>Adresse:</strong> {{ $this->selectedClientAdresse ?? '—' }}</div>
                            <div><strong>Pièce ID:</strong> {{ $this->selectedClientPieceId ?? '—' }}</div>
                        </div>
                    </div>
                </div>
                {{-- Footer --}}
                <div class="px-5 pb-5 flex gap-2">
                    <button @click="showCreditConfirmation = false" class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 border-2 border-gray-300 dark:border-slate-600 hover:bg-gray-50 dark:hover:bg-slate-700">
                        Annuler
                    </button>
                    <button @click="confirmerVenteCredit()" class="flex-1 py-2.5 text-sm font-bold rounded-xl text-white bg-gradient-to-r from-emerald-500 to-teal-600 hover:brightness-110">
                        Confirmer
                    </button>
                </div>
            </div>
        </div>
        @endif
    </div>
</template>
</div>

<script>
function scannerCodeBarre() {
    return {
        modalOuvert: false,
        hasCamera: false,
        saisie: '',
        saisieExterne: '',
        scanExterneFocus: false,
        statut: '',
        erreur: false,
        stream: null,
        detector: null,
        intervalScan: null,
        init() {
            // Vérification lazy — évaluée à l'ouverture
        },
        async ouvrir() {
            this.modalOuvert = true;
            this.statut = '';
            this.erreur = false;
            this.saisie = '';
            this.hasCamera = 'BarcodeDetector' in window && !!navigator.mediaDevices?.getUserMedia;
            if (!this.hasCamera) return;
            try {
                this.detector = new BarcodeDetector({ formats: ['ean_13','ean_8','code_128','code_39','upc_a','upc_e','qr_code'] });
                this.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                await this.$nextTick();
                this.$refs.video.srcObject = this.stream;
                this.intervalScan = setInterval(() => this.scanner(), 500);
            } catch (e) {
                this.hasCamera = false;
            }
        },
        async scanner() {
            if (!this.$refs.video || this.$refs.video.readyState < 2) return;
            try {
                const codes = await this.detector.detect(this.$refs.video);
                if (codes.length) this.resoudre(codes[0].rawValue);
            } catch (_) {}
        },
        fermer() {
            this.modalOuvert = false;
            if (this.intervalScan) { clearInterval(this.intervalScan); this.intervalScan = null; }
            if (this.stream) { this.stream.getTracks().forEach(t => t.stop()); this.stream = null; }
        },
        // Résolution depuis le champ scanner externe
        resoudreExterne() {
            this.resoudre(this.saisieExterne);
            this.saisieExterne = '';
        },
        async resoudre(code) {
            code = (code || '').trim();
            if (!code) return;
            this.statut = 'Recherche…';
            this.erreur = false;
            try {
                const url = "{{ route('dashboard.produits.scan') }}?code=" + encodeURIComponent(code);
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) {
                    this.statut = 'Produit introuvable : ' + code;
                    this.erreur = true;
                    this.fermer();
                    return;
                }
                const data = await res.json();
                if (data.found) {
                    // Ajoute au panier Alpine du composant parent caisseApp
                    this.$dispatch('scanner-produit', { id: data.id, nom: data.nom, prix: data.prix });
                    this.statut = data.nom + ' ajouté';
                    this.erreur = false;
                    this.fermer();
                } else {
                    this.statut = 'Produit introuvable.';
                    this.erreur = true;
                    this.fermer();
                }
            } catch (e) {
                this.statut = 'Erreur : ' + e.message;
                this.erreur = true;
                this.fermer();
            }
        }
    };
}
</script>
