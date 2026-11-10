    {{-- ═══ Panier droit (masqué sur mobile) ═══ --}}
    <div class="hidden lg:flex {{ $simple ? 'lg:col-span-3' : 'lg:col-span-2' }} lg:sticky lg:top-4 lg:max-h-[calc(100vh-8.5rem)] flex-col gap-4">

        {{-- Client (Livewire – seule section nécessitant le serveur) --}}
        @if(auth()->user()->aFonctionnalite('caisse_client'))
        <div class="card p-4">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-lg bg-blue-50 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">Client</span>
                </div>
                @if($clientId)
                    <button wire:click="$set('clientId', null)" class="text-xs text-red-500 hover:text-red-700 font-medium">Retirer</button>
                @endif
            </div>
            @if($clientId)
                @if($this->selectedClient)
                <div class="flex items-center gap-2.5 p-2.5 bg-primary-50/50 rounded-xl">
                    @if($this->selectedClient->isEntreprise())
                        <div class="w-8 h-8 bg-gradient-to-br from-purple-400 to-pink-400 rounded-full flex items-center justify-center text-white text-xs font-bold">
                            🏢
                        </div>
                    @else
                        <div class="w-8 h-8 bg-gradient-to-br from-primary-400 to-secondary-400 rounded-full flex items-center justify-center text-white text-xs font-bold">
                            {{ strtoupper(substr($this->selectedClient->prenom ?? '', 0, 1)) }}
                        </div>
                    @endif
                    <span class="text-sm font-semibold text-gray-900">{{ $this->selectedClient->nom_affichage }}</span>
                </div>
                @endif
            @else
                <div x-data="selecteurClient({ initial: {{ $allClients->toJson() }} })"
                     @click.outside="open = false">
                    <input
                        type="text"
                        x-model="search"
                        @focus="open = true"
                        @input="onInput()"
                        @keydown.escape="open = false"
                        placeholder="Chercher un client..."
                        class="form-input text-sm">
                    <div x-show="open && filtered.length > 0" x-cloak
                         class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden mt-2 shadow-sm max-h-52 overflow-y-auto bg-white dark:bg-gray-800">
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
                <button @click="newClientOpen = true" class="mt-2 flex items-center gap-1.5 text-xs text-primary-600 hover:text-primary-800 font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Nouveau client
                </button>
            @endif
        </div>
        @endif

        {{-- Panier + Paiement (100 % Alpine) --}}
        <div class="card flex-1 min-h-0 flex flex-col overflow-hidden" wire:ignore>
            {{-- En-tête : compteur + prix courant (affichage tête haute) --}}
            <div class="p-3 border-b border-gray-100 flex items-center justify-between gap-3 flex-shrink-0">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="relative w-7 h-7 rounded-lg bg-primary-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-3.5 h-3.5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <span x-show="nbArticles > 0"
                              x-text="nbArticles"
                              class="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] rounded-full text-[10px] font-bold text-white flex items-center justify-center px-1 shadow-sm"
                              style="background: linear-gradient(135deg, #9333ea, #ec4899);"></span>
                    </div>
                    <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">Articles</span>
                </div>

                <div class="flex items-center gap-3 flex-shrink-0">
                    {{-- Prix du panier, toujours visible quel que soit le nombre d'articles --}}
                    <div class="text-right leading-none" x-show="!panierVide">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">Total</p>
                        <p class="text-base font-extrabold whitespace-nowrap"
                           style="background: linear-gradient(135deg, #9333ea, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"
                           x-text="formatNumber(total) + ' F'"></p>
                    </div>
                    <button x-show="!panierVide" @click="viderPanier" class="text-xs text-red-500 hover:text-red-700 font-medium">Vider</button>
                </div>
            </div>

            <div class="flex-1 min-h-0 overflow-y-auto divide-y divide-gray-50 {{ $simple ? 'max-h-60 lg:max-h-none' : 'max-h-52' }}">
                <template x-for="key in panierKeys" :key="key">
                    <div class="px-3 sm:px-4 py-3 hover:bg-gray-50/50 transition-colors"
                         :class="{
                             'cart-item-add': animatingItems[key] === 'add',
                             'cart-item-remove': animatingItems[key] === 'remove',
                             'cart-item-flash': animatingItems[key] === 'flash'
                         }">
                        {{-- Ligne 1 : nom + supprimer --}}
                        <div class="flex items-center gap-2">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900 truncate" x-text="panier[key].nom"></p>
                                <p class="text-xs text-gray-400" x-text="formatNumber(panier[key].prix) + ' F × ' + panier[key].quantite"></p>
                            </div>
                            <button @click="supprimerItem(key)" class="text-gray-300 hover:text-red-500 transition-colors flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        {{-- Ligne 2 : quantité + total --}}
                        <div class="flex items-center justify-between mt-2 gap-2">
                            <div class="flex items-center gap-1.5">
                                <button @click="decrementer(key)"
                                        class="w-7 h-7 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-600 text-sm font-bold transition-colors flex-shrink-0">−</button>
                                <input type="number" x-model.number="panier[key].quantite" min="1" max="99999"
                                       class="w-14 text-center text-sm font-bold text-gray-900 dark:text-gray-100 bg-transparent border border-gray-200 dark:border-slate-600 rounded-lg py-1 px-1 focus:ring-1 focus:ring-primary-400 focus:border-primary-400"
                                       @change="if (panier[key].quantite < 1) panier[key].quantite = 1">
                                <button @click="incrementer(key)"
                                        class="w-7 h-7 rounded-lg bg-primary-100 hover:bg-primary-200 flex items-center justify-center text-primary-700 text-sm font-bold transition-colors flex-shrink-0">+</button>
                            </div>
                            <span class="text-sm font-bold text-gray-900 text-right" x-text="formatNumber(panier[key].prix * panier[key].quantite) + ' F'"></span>
                        </div>
                    </div>
                </template>
                <div x-show="panierVide" class="px-4 py-8 text-center">
                    <div class="w-14 h-14 bg-gray-50 dark:bg-slate-700/40 rounded-2xl flex items-center justify-center mx-auto mb-3">
                        <svg class="w-7 h-7 text-gray-200 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <p class="text-sm text-gray-400">Panier vide</p>
                    <p class="text-xs text-gray-300 dark:text-slate-500 mt-0.5">
                        Cliquez sur un article, ou tapez
                        <kbd class="px-1 py-0.5 rounded border border-gray-300 dark:border-slate-600 text-[10px] font-mono text-gray-500 dark:text-slate-400">/</kbd>
                        pour chercher
                    </p>

                    {{-- Raccourcis : le panier vide ne doit pas être un cul-de-sac --}}
                    <div class="mt-5 space-y-2 text-left">
                        <button type="button"
                                @click="toggleVenteRapide()"
                                class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl border border-dashed border-primary-300 dark:border-primary-700 text-primary-700 dark:text-primary-300 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            <span class="text-xs font-semibold">Vente libre / rapide</span>
                            <span class="text-[10px] text-primary-400 ml-auto">sans fiche article</span>
                        </button>

                        @if($derniersVendus->isNotEmpty())
                            <div class="pt-3">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-2">Derniers vendus</p>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($derniersVendus as $article)
                                        <button type="button"
                                                @click="ajouterAuPanier(@js($article['type']), @js($article))"
                                                class="px-2.5 py-1.5 rounded-lg bg-gray-100 dark:bg-slate-700/60 hover:bg-primary-100 dark:hover:bg-primary-900/30 text-[11px] font-medium text-gray-700 dark:text-slate-200 transition-colors truncate max-w-full">
                                            {{ $article['nom'] }}
                                            <span class="text-gray-400 ml-1">{{ number_format($article['prix'], 0, ',', ' ') }} F</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Total + Paiement + Encaisser — barre collée en bas du panneau,
                 le total et le bouton d'encaissement restent visibles même
                 quand la liste d'articles défile. --}}
            <div x-show="!panierVide"
                 class="sticky bottom-0 z-10 border-t border-gray-200 dark:border-slate-700 p-4 space-y-3 bg-white dark:bg-slate-800 flex-shrink-0 shadow-[0_-6px_16px_-8px_rgba(15,23,42,0.25)]">
                {{-- Total --}}
                @if($simple)
                {{-- En mode simplifié la ligne affiche directement le net : la
                     zone « code promo » étant repliée, le total remisé doit
                     rester lisible sans avoir à la déplier. --}}
                <div class="flex justify-between items-center">
                    <span class="font-bold text-gray-900">
                        Total
                        <span x-show="remise > 0" class="ml-1 text-[11px] font-semibold text-emerald-600"
                              x-text="'−' + formatNumber(remise) + ' F'"></span>
                    </span>
                    <span class="text-xl font-display font-extrabold" style="background: linear-gradient(135deg, #9333ea, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;" x-text="formatNumber(total) + ' F'"></span>
                </div>
                @else
                <div class="flex justify-between items-center">
                    <span class="font-bold text-gray-900" x-text="remise > 0 ? 'Sous-total' : 'Total'"></span>
                    <span class="text-xl font-display font-extrabold" style="background: linear-gradient(135deg, #9333ea, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;" x-text="formatNumber(totalBrut) + ' F'"></span>
                </div>
                @endif

                {{-- Code promo --}}
                @if(auth()->user()->aFonctionnalite('caisse_code_promo'))
                @if($simple)
                {{-- Caisse simplifiée : zone repliable, fermée par défaut --}}
                <div x-data="{ ouvert: false }" class="rounded-xl border border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-800 overflow-hidden">
                    <button type="button" @click="ouvert = !ouvert"
                            class="w-full flex items-center justify-between gap-2 px-3 py-2 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">
                        <span class="flex items-center gap-1.5 min-w-0">
                            <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            <span>Code promo</span>
                            <template x-if="codePromo">
                                <span class="px-1.5 py-0.5 rounded font-mono text-[10px] bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 truncate"
                                      x-text="codePromo.code + ' · −' + formatNumber(codePromo.remise) + ' F'"></span>
                            </template>
                        </span>
                        <svg class="w-3.5 h-3.5 flex-shrink-0 transition-transform duration-200" :class="ouvert ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="ouvert" x-transition.opacity.duration.150ms x-cloak>
                        <div class="px-3 pb-3 space-y-2">
                @endif
                <template x-if="codePromo">
                    <div>
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
                        @if(! $simple)
                        <div class="flex justify-between items-center pt-2">
                            <span class="font-bold text-gray-900">Total</span>
                            <span class="text-xl font-display font-extrabold" style="background: linear-gradient(135deg, #9333ea, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;" x-text="formatNumber(total) + ' F'"></span>
                        </div>
                        @endif
                    </div>
                </template>

                <template x-if="!codePromo">
                    <div>
                        <div class="flex gap-2">
                            <input type="text" x-model="codePromoInput"
                                   placeholder="Code promo"
                                   class="form-input flex-1 text-sm font-mono uppercase tracking-widest"
                                   @input="$el.value = $el.value.toUpperCase()"
                                   @keydown.enter="appliquerCode">
                            <button @click="appliquerCode" :disabled="codePromoLoading" class="btn btn-outline text-sm px-3 flex-shrink-0">
                                <span x-show="codePromoLoading" class="spinner spinner-sm" aria-hidden="true"></span>
                                <span x-show="!codePromoLoading">Appliquer</span>
                            </button>
                        </div>
                        <p x-show="codePromoErreur" x-text="codePromoErreur" class="text-xs text-red-500 mt-1"></p>
                    </div>
                </template>
                @if($simple)
                        </div>
                    </div>
                </div>
                @endif
                @endif

                {{-- Modes de paiement (5 modes) --}}
                @if($simple)
                {{-- Caisse simplifiée : zone repliable, fermée par défaut --}}
                <div x-data="{ ouvert: false }" class="rounded-xl border border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-800 overflow-hidden">
                    <button type="button" @click="ouvert = !ouvert"
                            class="w-full flex items-center justify-between gap-2 px-3 py-2 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">
                        <span class="flex items-center gap-1.5 min-w-0">
                            <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                            <span>Moyens de paiement</span>
                            <span class="px-1.5 py-0.5 rounded text-[10px] bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-300"
                                  x-text="modePaiement === 'cash' ? '💵 Espèces' : (modePaiement === 'carte' ? '💳 Carte' : (modePaiement === 'mobile_money' ? '📱 Mobile' : '🔀 Mixte'))"></span>
                        </span>
                        <svg class="w-3.5 h-3.5 flex-shrink-0 transition-transform duration-200" :class="ouvert ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="ouvert" x-transition.opacity.duration.150ms x-cloak>
                        <div class="px-3 pb-3">
                @endif
                <div class="grid grid-cols-3 {{ ($hasCredits && ! $simple) ? 'sm:grid-cols-5' : 'sm:grid-cols-4' }} gap-2">
                    <button @click="modePaiement = 'cash'"
                            :class="modePaiement === 'cash' ? 'border-primary-500 bg-primary-50 text-primary-700 shadow-sm' : 'border-gray-200 text-gray-500 hover:border-gray-300'"
                            class="py-2 px-1 rounded-xl text-[11px] font-semibold border-2 transition-all duration-200 text-center">
                        💵 Espèces
                    </button>
                    <button @click="modePaiement = 'carte'"
                            :class="modePaiement === 'carte' ? 'border-blue-500 bg-blue-50 text-blue-700 shadow-sm' : 'border-gray-200 text-gray-500 hover:border-gray-300'"
                            class="py-2 px-1 rounded-xl text-[11px] font-semibold border-2 transition-all duration-200 text-center">
                        💳 Carte
                    </button>
                    <button @click="modePaiement = 'mobile_money'"
                            :class="modePaiement === 'mobile_money' ? 'border-orange-500 bg-orange-50 text-orange-700 shadow-sm' : 'border-gray-200 text-gray-500 hover:border-gray-300'"
                            class="py-2 px-1 rounded-xl text-[11px] font-semibold border-2 transition-all duration-200 text-center">
                        📱 Mobile
                    </button>
                    <button @click="modePaiement = 'mixte'"
                            :class="modePaiement === 'mixte' ? 'border-violet-500 bg-violet-50 text-violet-700 shadow-sm' : 'border-gray-200 text-gray-500 hover:border-gray-300'"
                            class="py-2 px-1 rounded-xl text-[11px] font-semibold border-2 transition-all duration-200 text-center">
                        🔀 Mixte
                    </button>
                    @if($hasCredits && ! $simple)
                    <button @click="modePaiement = 'credit'"
                            :class="modePaiement === 'credit' ? 'border-emerald-500 bg-emerald-50 text-emerald-700 shadow-sm' : 'border-gray-200 text-gray-500 hover:border-gray-300'"
                            class="py-2 px-1 rounded-xl text-[11px] font-semibold border-2 transition-all duration-200 text-center">
                        🕐 Crédit
                    </button>
                    @endif
                </div>
                @if($simple)
                        </div>
                    </div>
                </div>
                @endif

                {{-- Référence Mobile Money --}}
                <template x-if="modePaiement === 'mobile_money'">
                    <input type="text" x-model="referencePaiement"
                           placeholder="Référence transaction (optionnel)"
                           class="form-input text-sm">
                </template>

                {{-- Paiement Mixte --}}
                <template x-if="modePaiement === 'mixte'">
                    <div class="space-y-2">
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">💵 Espèces</label>
                                <input type="number" x-model.number="montantMixteCash"
                                       class="form-input text-sm mt-1" placeholder="0" min="0" :max="total">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">💳 Carte</label>
                                <input type="number" x-model.number="montantMixteCartes"
                                       class="form-input text-sm mt-1" placeholder="0" min="0" :max="total">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">📱 Mobile</label>
                                <input type="number" x-model.number="montantMixteMobile"
                                       class="form-input text-sm mt-1" placeholder="0" min="0" :max="total">
                            </div>
                        </div>
                        <div :class="resteMixte === 0 ? 'bg-green-50' : (resteMixte < 0 ? 'bg-red-50' : 'bg-amber-50')"
                             class="flex justify-between items-center p-2.5 rounded-xl">
                            <span class="text-xs font-semibold"
                                  :class="resteMixte === 0 ? 'text-green-700' : (resteMixte < 0 ? 'text-red-700' : 'text-amber-700')"
                                  x-text="resteMixte === 0 ? '✓ Montants OK' : (resteMixte < 0 ? 'Dépassement' : 'Reste à ventiler')"></span>
                            <span class="text-sm font-bold"
                                  :class="resteMixte === 0 ? 'text-green-700' : (resteMixte < 0 ? 'text-red-700' : 'text-amber-700')"
                                  x-text="resteMixte !== 0 ? formatNumber(Math.abs(resteMixte)) + ' F' : ''"></span>
                        </div>
                        <input type="text" x-model="referencePaiement"
                               placeholder="Référence mobile / carte (optionnel)"
                               class="form-input text-sm">
                    </div>
                </template>

                {{-- Panneau Crédit --}}
                @if($hasCredits)
                <template x-if="modePaiement === 'credit'">
                    <div class="space-y-3 p-4 bg-emerald-50/50 dark:bg-emerald-900/20 rounded-xl border border-emerald-200 dark:border-emerald-800/50">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Client</span>
                            <span x-show="$wire.clientId" class="text-sm font-bold text-emerald-700 dark:text-emerald-400" x-text="$wire.selectedClientNom || ''"></span>
                            <span x-show="!$wire.clientId" class="text-xs text-red-500 dark:text-red-400 font-medium">⚠️ Client obligatoire</span>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400">Apport initial (optionnel)</label>
                            <input type="number" x-model.number="creditApport" min="0" :max="total"
                                   class="form-input text-sm mt-1" placeholder="0">
                        </div>
                        <div class="bg-white dark:bg-slate-800 rounded-lg p-3 text-sm space-y-1">
                            <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-300">Total vente</span><strong class="text-gray-900 dark:text-white" x-text="formatNumber(total) + ' FCFA'"></strong></div>
                            <div class="flex justify-between text-emerald-600 dark:text-emerald-400"><span>Apport</span><strong x-text="formatNumber(parseInt(creditApport)||0) + ' FCFA'"></strong></div>
                            <div class="flex justify-between text-red-600 dark:text-red-400 font-bold pt-1 border-t dark:border-slate-700"><span>Reste à payer</span><strong x-text="formatNumber(Math.max(0, total-(parseInt(creditApport)||0))) + ' FCFA'"></strong></div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs text-gray-500">Échéances</label>
                                <select x-model.number="creditNbEcheances" class="form-input text-sm mt-1">
                                    <option value="2">2</option><option value="3">3</option><option value="4">4</option>
                                    <option value="6">6</option><option value="12">12</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">Fréquence</label>
                                <select x-model="creditFrequence" class="form-input text-sm mt-1">
                                    <option value="mensuelle">Mensuelle</option>
                                    <option value="hebdomadaire">Hebdomadaire</option>
                                </select>
                            </div>
                        </div>
                        {{-- Infos client crédit --}}
                        <div x-show="$wire.clientId" class="space-y-2 bg-gray-50 dark:bg-slate-700/50 rounded-lg p-2.5 text-xs">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-400">Tél :</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $this->selectedClientTel ?? '—' }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-400 flex-shrink-0">Adresse :</span>
                                <input type="text" wire:model.blur="selectedClientAdresse" wire:change="updateClientInfosCredit"
                                       class="flex-1 bg-transparent border-0 border-b border-gray-300 dark:border-slate-600 px-1 py-0.5 text-xs focus:border-emerald-500 focus:ring-0 dark:text-white"
                                       placeholder="Adresse du client">
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-400 flex-shrink-0">Pièce ID :</span>
                                <input type="text" wire:model.blur="selectedClientPieceId" wire:change="updateClientInfosCredit"
                                       class="flex-1 bg-transparent border-0 border-b border-gray-300 dark:border-slate-600 px-1 py-0.5 text-xs focus:border-emerald-500 focus:ring-0 dark:text-white"
                                       placeholder="N° CNI, Passeport...">
                            </div>
                        </div>
                        <button @click="validerVenteCredit()"
                                :disabled="!$wire.clientId || (total-(parseInt(creditApport)||0)) <= 0"
                                class="w-full py-2.5 rounded-xl text-white text-sm font-bold bg-gradient-to-r from-emerald-500 to-teal-600 disabled:opacity-50">
                            Enregistrer la vente à crédit
                        </button>
                    </div>
                </template>
                @endif

                {{-- Modal confirmation crédit --}}
                @if($hasCredits)
                <template x-if="showCreditConfirmation">
                    <div class="fixed inset-0 flex items-center justify-center p-4" style="z-index: 10001; background: rgba(0,0,0,0.5);"
                         @click.self="showCreditConfirmation = false">
                        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100">
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
                                    <strong class="text-gray-900 dark:text-white" x-text="formatNumber(total) + ' FCFA'"></strong>
                                </div>
                                <div class="flex justify-between text-emerald-600 dark:text-emerald-400">
                                    <span>Apport initial</span>
                                    <strong x-text="formatNumber(parseInt(creditApport)||0) + ' FCFA'"></strong>
                                </div>
                                <div class="flex justify-between text-red-600 dark:text-red-400 font-bold pt-2 border-t dark:border-slate-700">
                                    <span>Reste à payer</span>
                                    <strong x-text="formatNumber(Math.max(0, total-(parseInt(creditApport)||0))) + ' FCFA'"></strong>
                                </div>
                                <div class="flex justify-between text-gray-500 dark:text-gray-400">
                                    <span>Échéancier</span>
                                    <span x-text="creditNbEcheances + ' × ' + (creditFrequence === 'mensuelle' ? 'mois' : 'semaines')"></span>
                                </div>
                                <div x-show="$wire.clientId" class="bg-gray-50 dark:bg-slate-700/50 rounded-lg p-2.5 flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Client</span>
                                    <span class="font-semibold text-gray-900 dark:text-white" x-text="$wire.selectedClientNom || ''"></span>
                                </div>
                            </div>
                            {{-- Footer --}}
                            <div class="px-5 pb-5 flex gap-2">
                                <button @click="showCreditConfirmation = false" class="flex-1 btn-outline justify-center text-sm py-2.5">Annuler</button>
                                <button @click="confirmerVenteCredit()" class="flex-1 py-2.5 text-sm font-bold rounded-xl text-white bg-gradient-to-r from-emerald-500 to-teal-600 hover:brightness-110">
                                    Confirmer
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
                @endif

                {{-- Montant remis (Espèces) --}}
                <template x-if="modePaiement === 'cash'">
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Montant remis</label>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="suggestion in montantsSuggeres" :key="suggestion">
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

                {{-- Bouton Encaisser (masqué en mode crédit) --}}
                <button @click="ouvrirConfirmation"
                        :disabled="!mixtePret || loading"
                        :class="(!mixtePret || loading) ? 'opacity-50 cursor-not-allowed' : 'hover:shadow-xl active:scale-[0.98]'"
                        x-show="modePaiement !== 'credit'"
                        class="w-full justify-center py-3.5 text-base font-bold rounded-xl text-white shadow-lg transition-all duration-200 flex items-center gap-2"
                        style="background: linear-gradient(135deg, #9333ea 0%, #ec4899 100%);">
                    <span x-show="loading" class="spinner" aria-hidden="true"></span>
                    <svg x-show="!loading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span x-text="loading ? 'Traitement...' : libelleEncaisser"></span>
                </button>

                {{-- Mettre en attente / Liens (masqués en mode crédit) --}}
                <div class="flex items-center justify-between gap-2 mt-2" x-show="modePaiement !== 'credit'">
                    <button type="button"
                            @click="mettreEnAttente()"
                            :disabled="panierKeys.length === 0 || enAttenteLoading"
                            :class="(panierKeys.length === 0 || enAttenteLoading) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-amber-100 dark:hover:bg-amber-900/30'"
                            class="flex-1 justify-center py-2 text-xs font-semibold rounded-lg border border-amber-300 dark:border-amber-700 text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/20 transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span x-text="enAttenteLoading ? 'Enregistrement...' : 'Mettre en attente'"></span>
                    </button>
                    {{-- <a href="{{ route('dashboard.caisse.brouillons.index') }}"
                       class="px-3 py-2 text-xs font-semibold rounded-lg text-gray-600 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-700 transition">
                        Brouillons
                    </a>
                    @if($hasCredits)
                    <a href="{{ route('dashboard.credits.index') }}"
                       class="px-3 py-2 text-xs font-semibold rounded-lg text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition">
                        Crédits
                    </a>
                    @endif --}}
                </div>
            </div>
        </div>
    </div>