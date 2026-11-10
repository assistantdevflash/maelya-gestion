    {{-- ═══ Modal de confirmation (Alpine) ═══ --}}
    <div x-show="showConfirmation" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background: rgba(0,0,0,0.5);"
         wire:ignore>
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" @click.outside="fermerConfirmation()">
            {{-- Header --}}
            <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, rgba(147,51,234,0.1), rgba(236,72,153,0.1));">
                        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Confirmer la vente</h3>
                        <p class="text-xs text-gray-400">Vérifiez les détails avant d'encaisser</p>
                    </div>
                </div>
                <button @click="fermerConfirmation" class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Articles --}}
            <div class="p-5 space-y-3">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider" x-text="'Articles (' + nbArticles + ')'"></p>
                <div class="divide-y divide-gray-50 border border-gray-100 rounded-xl overflow-hidden">
                    <template x-for="key in panierKeys" :key="'modal_' + key">
                        <div class="px-4 py-3 flex items-center gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900 line-clamp-2" x-text="panier[key].nom"></p>
                                <p class="text-xs text-gray-400" x-text="formatNumber(panier[key].prix) + ' F × ' + panier[key].quantite"></p>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <button @click="decrementer(key)"
                                        class="w-6 h-6 rounded-md bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-600 text-xs font-bold transition-colors flex-shrink-0">−</button>
                                <input type="number" x-model.number="panier[key].quantite" min="1" max="99999"
                                       class="w-14 text-center text-xs font-bold text-gray-900 dark:text-gray-100 bg-transparent border border-gray-200 dark:border-slate-600 rounded-md py-0.5 px-0.5 focus:ring-1 focus:ring-primary-400 focus:border-primary-400"
                                       @change="if (panier[key].quantite < 1) panier[key].quantite = 1">
                                <button @click="incrementer(key)"
                                        class="w-6 h-6 rounded-md bg-primary-100 hover:bg-primary-200 flex items-center justify-center text-primary-700 text-xs font-bold transition-colors flex-shrink-0">+</button>
                            </div>
                            <span class="text-sm font-bold text-gray-900 w-20 text-right" x-text="formatNumber(panier[key].prix * panier[key].quantite) + ' F'"></span>
                            <button @click="supprimerItem(key)" class="text-gray-300 hover:text-red-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>

                {{-- Résumé --}}
                <div class="bg-gray-50 dark:bg-slate-800/50 rounded-xl p-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500 dark:text-slate-400">Sous-total</span>
                        <span class="font-semibold text-gray-700 dark:text-slate-200" x-text="formatNumber(totalBrut) + ' F'"></span>
                    </div>
                    <template x-if="remise > 0">
                        <div class="flex justify-between text-sm">
                            <span class="text-emerald-600 font-medium" x-text="'Code ' + (codePromo?.code ?? '')"></span>
                            <span class="font-semibold text-emerald-600" x-text="'-' + formatNumber(remise) + ' F'"></span>
                        </div>
                    </template>
                    <div class="flex justify-between text-base pt-2 border-t border-gray-200 dark:border-slate-700">
                        <span class="font-bold text-gray-900 dark:text-slate-100">Total</span>
                        <span class="font-extrabold" style="background: linear-gradient(135deg, #9333ea, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;" x-text="formatNumber(total) + ' F'"></span>
                    </div>
                    <template x-if="pourboire > 0">
                        <div class="flex justify-between text-sm pt-1">
                            <span class="text-amber-600 dark:text-amber-400 font-medium">💰 Pourboire</span>
                            <span class="font-semibold text-amber-600 dark:text-amber-400" x-text="'+' + formatNumber(pourboire) + ' F'"></span>
                        </div>
                    </template>
                    <template x-if="pourboire > 0">
                        <div class="flex justify-between text-base pt-2 border-t border-gray-200 dark:border-slate-700">
                            <span class="font-bold text-gray-900 dark:text-slate-100">À encaisser</span>
                            <span class="font-extrabold text-primary-700 dark:text-primary-300" x-text="formatNumber(total + pourboire) + ' F'"></span>
                        </div>
                    </template>
                </div>

                {{-- Pourboire (optionnel) --}}
                <div class="space-y-2">
                    <label class="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1">
                        💰 Pourboire (optionnel)
                    </label>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="montant in [0, 500, 1000, 2000, 5000]" :key="'tip_' + montant">
                            <button type="button" @click="pourboire = montant"
                                    :class="pourboire === montant ? 'bg-amber-500 text-white shadow-sm' : 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 hover:bg-gray-200 dark:hover:bg-slate-600'"
                                    class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all"
                                    x-text="montant === 0 ? 'Aucun' : formatNumber(montant) + ' F'"></button>
                        </template>
                    </div>
                    <input type="number" x-model.number="pourboire" min="0" step="100"
                           placeholder="Autre montant..."
                           class="form-input text-sm">
                </div>

                {{-- Mode de paiement affiché --}}
                <div class="flex items-center gap-2 p-3 bg-gray-50 dark:bg-slate-700/40 rounded-xl">
                    <span class="text-xs font-semibold text-gray-500 dark:text-slate-400">Paiement :</span>
                    <template x-if="modePaiement === 'cash'">
                        <span class="inline-flex items-center gap-1 text-xs font-bold text-primary-700 bg-primary-50 px-2.5 py-1 rounded-lg">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            Espèces
                        </span>
                    </template>
                    <template x-if="modePaiement === 'carte'">
                        <span class="inline-flex items-center gap-1 text-xs font-bold text-blue-700 bg-blue-50 px-2.5 py-1 rounded-lg">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            Carte bancaire
                        </span>
                    </template>
                    <template x-if="modePaiement === 'mobile_money'">
                        <span class="inline-flex items-center gap-1 text-xs font-bold text-orange-700 bg-orange-50 px-2.5 py-1 rounded-lg">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            Mobile Money
                        </span>
                    </template>
                    <template x-if="modePaiement === 'mixte'">
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold text-violet-700 bg-violet-50 px-2.5 py-1 rounded-lg">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            Mixte
                        </span>
                    </template>
                </div>

                {{-- Montant remis dans modal (cash) --}}
                <template x-if="modePaiement === 'cash'">
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Montant remis par le client</label>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="suggestion in montantsSuggeres" :key="'modal_s_' + suggestion">
                                <button @click="montantRemis = suggestion"
                                        :class="montantRemis === suggestion ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                                        class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all duration-200"
                                        x-text="formatNumber(suggestion) + ' F'">
                                </button>
                            </template>
                        </div>
                        <input type="number" x-model.number="montantRemis"
                               placeholder="Autre montant..."
                               class="form-input text-sm"
                               :min="total">
                        <div x-show="montantRemis && monnaie > 0" class="flex justify-between items-center p-2.5 bg-green-50 rounded-xl">
                            <span class="text-xs font-semibold text-green-700">Monnaie à rendre</span>
                            <span class="text-sm font-bold text-green-700" x-text="formatNumber(monnaie) + ' F'"></span>
                        </div>
                    </div>
                </template>

                {{-- Détail paiement mixte dans modal --}}
                <template x-if="modePaiement === 'mixte'">
                    <div class="bg-violet-50 dark:bg-violet-900/30 rounded-xl p-3 space-y-2">
                        <p class="text-xs font-bold text-violet-700 dark:text-violet-300 uppercase tracking-wider">Paiement Mixte</p>
                        <div x-show="montantMixteCash > 0" class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-slate-400">💵 Espèces</span>
                            <span class="font-bold text-gray-900 dark:text-slate-100" x-text="formatNumber(montantMixteCash) + ' F'"></span>
                        </div>
                        <div x-show="montantMixteCartes > 0" class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-slate-400">💳 Carte</span>
                            <span class="font-bold text-gray-900 dark:text-slate-100" x-text="formatNumber(montantMixteCartes) + ' F'"></span>
                        </div>
                        <div x-show="montantMixteMobile > 0" class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-slate-400">📱 Mobile</span>
                            <span class="font-bold text-gray-900 dark:text-slate-100" x-text="formatNumber(montantMixteMobile) + ' F'"></span>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Actions (masquées en mode crédit — le panneau crédit a son propre bouton) --}}
            <div class="p-5 border-t border-gray-100 flex flex-col gap-2" x-show="modePaiement !== 'credit'">
                <div class="grid grid-cols-2 gap-2">
                    <button @click="fermerConfirmation" class="btn-secondary justify-center py-3">
                        Annuler
                    </button>
                    <button @click="valider(false)"
                            :disabled="loading"
                            :class="loading ? 'opacity-70 cursor-not-allowed' : ''"
                            class="btn-primary justify-center py-3">
                        <span x-show="loading" class="spinner spinner-sm" aria-hidden="true"></span>
                        <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Encaisser
                    </button>
                </div>
                <button @click="valider(true)"
                        :disabled="loading"
                        :class="loading ? 'opacity-70 cursor-not-allowed' : 'hover:shadow-lg active:scale-[0.98]'"
                        @class([
                            'w-full justify-center py-3 text-sm font-bold rounded-xl text-white flex items-center gap-2 transition-all duration-200',
                            'hidden' => !auth()->user()->aFonctionnalite('caisse_impression'),
                        ])
                        style="background: linear-gradient(135deg, #9333ea 0%, #ec4899 100%);">
                    <span x-show="loading" class="spinner spinner-sm" aria-hidden="true"></span>
                    <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    </svg>
                    Encaisser & Imprimer
                </button>
            </div>
        </div>
    </div>