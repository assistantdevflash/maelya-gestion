<x-dashboard-layout>
    <div class="max-w-5xl mx-auto space-y-6 px-3 sm:px-4">
        {{-- En-tête --}}
        <div class="flex items-start justify-between flex-wrap gap-3 sm:gap-4">
            <div class="flex items-center gap-2 sm:gap-3 min-w-0 flex-1">
                <a href="{{ route('dashboard.ventes.index') }}" class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:text-gray-500 dark:hover:text-gray-300 dark:hover:bg-gray-800 transition-colors flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div class="min-w-0 flex-1">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100 truncate">Vente {{ $vente->numero }}</h1>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $vente->created_at->format('d/m/Y à H:i') }}</p>
                </div>
            </div>
            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs sm:text-sm font-medium flex-shrink-0 {{ $vente->statut === 'validee' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                {{ $vente->statut === 'validee' ? '✓ Validée' : '✕ Annulée' }}
            </span>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            {{-- Colonne principale : Informations et Articles --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Informations de la vente --}}
                <div class="card p-4 sm:p-6 space-y-4">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider">Informations</h2>
                    <div class="grid sm:grid-cols-2 gap-4 text-sm">
                        <div class="space-y-3">
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Mode de paiement</p>
                                <p class="font-medium text-gray-900 dark:text-gray-100 break-words">
                                    @if($vente->mode_paiement === 'mobile_money') 📱 Mobile Money
                                    @elseif($vente->mode_paiement === 'carte') 💳 Carte bancaire
                                    @elseif($vente->mode_paiement === 'credit') 📅 Crédit
                                    @else 💵 Espèces
                                    @endif
                                </p>
                            </div>
                            @if($vente->reference_paiement)
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Référence</p>
                                <p class="font-mono text-xs text-gray-900 dark:text-gray-100 break-all">{{ $vente->reference_paiement }}</p>
                            </div>
                            @endif
                        </div>
                        <div class="space-y-3">
                            @if($vente->client)
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Client</p>
                                <a href="{{ route('dashboard.clients.show', $vente->client) }}" class="font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 hover:underline break-words">
                                    {{ $vente->client->nom_affichage }}
                                </a>
                            </div>
                            @endif
                            @if($vente->user)
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Vendeur</p>
                                <p class="font-medium text-gray-900 dark:text-gray-100 break-words">{{ $vente->user->name ?? $vente->user->email }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Total --}}
                    <div class="border-t border-gray-100 dark:border-gray-700 pt-4 mt-4">
                        @if($vente->remise > 0)
                        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                            <span>Sous-total</span>
                            <span class="font-medium">{{ number_format($vente->total + $vente->remise, 0, ',', ' ') }} FCFA</span>
                        </div>
                        <div class="flex justify-between text-sm text-emerald-600 dark:text-emerald-400 mb-2">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                                @if($vente->codeReduction)
                                    <span class="font-mono text-xs font-bold">{{ $vente->codeReduction->code }}</span>
                                @else
                                    Réduction
                                @endif
                            </span>
                            <span class="font-semibold whitespace-nowrap">-{{ number_format($vente->remise, 0, ',', ' ') }} FCFA</span>
                        </div>
                        @endif
                        <div class="flex justify-between items-baseline gap-2">
                            <span class="text-base font-bold text-gray-900 dark:text-gray-100">Total</span>
                            <span class="text-xl sm:text-2xl font-extrabold text-primary-600 dark:text-primary-400 whitespace-nowrap">{{ number_format($vente->total, 0, ',', ' ') }} FCFA</span>
                        </div>
                    </div>
                </div>
                
                {{-- Détail des articles --}}
                <div class="card overflow-hidden">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider">Articles</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-700">
                                <tr>
                                    <th class="px-3 sm:px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Désignation</th>
                                    <th class="px-2 sm:px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Qté</th>
                                    <th class="px-2 sm:px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">P.U.</th>
                                    <th class="px-3 sm:px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($vente->items as $item)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                    <td class="px-3 sm:px-6 py-3 sm:py-3.5">
                                        <div class="flex items-start gap-2 min-w-0">
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-md text-xs font-medium flex-shrink-0 {{ $item->type === 'prestation' ? 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400' : ($item->type === 'produit' ? 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400') }}">
                                                @if($item->type === 'prestation') ✨ @elseif($item->type === 'produit') 📦 @else ✏️ @endif
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <p class="font-medium text-gray-900 dark:text-gray-100 break-words text-xs sm:text-sm">{{ $item->nom_snapshot }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                    {{ match($item->type) { 'prestation' => 'Prestation', 'produit' => 'Produit', 'libre' => 'Article libre', default => ucfirst($item->type) } }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-2 sm:px-6 py-3 sm:py-3.5 text-center font-medium text-gray-900 dark:text-gray-100 text-xs sm:text-sm">{{ $item->quantite }}</td>
                                    <td class="px-2 sm:px-6 py-3 sm:py-3.5 text-right text-gray-600 dark:text-gray-400 text-xs sm:text-sm whitespace-nowrap">{{ number_format($item->prix_snapshot, 0, ',', ' ') }}</td>
                                    <td class="px-3 sm:px-6 py-3 sm:py-3.5 text-right font-semibold text-gray-900 dark:text-gray-100 text-xs sm:text-sm whitespace-nowrap">{{ number_format($item->sous_total, 0, ',', ' ') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                
                {{-- Avoirs émis sur cette vente --}}
                @if($vente->avoirs->count() > 0)
                <div class="card overflow-hidden">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider">Avoirs émis</h2>
                    </div>
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($vente->avoirs as $av)
                        <li class="px-4 sm:px-6 py-4 flex items-center gap-3 sm:gap-4 text-sm hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-900 dark:text-gray-100 break-words">{{ $av->numero }}</p>
                                @if($av->motif)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $av->motif }}</p>
                                @endif
                                @if($av->codeReduction)
                                    <p class="text-xs mt-1.5 flex items-center gap-2 flex-wrap">
                                        <span class="inline-flex items-center gap-1">
                                            Code : <span class="font-mono font-semibold text-primary-600 dark:text-primary-400">{{ $av->codeReduction->code }}</span>
                                        </span>
                                        @if($av->codeReduction->nb_utilisations >= $av->codeReduction->limite_utilisation)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">Utilisé</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Disponible jusqu'au {{ $av->codeReduction->date_fin?->format('d/m/Y') }}</span>
                                        @endif
                                    </p>
                                @endif
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="font-bold text-gray-900 dark:text-gray-100 whitespace-nowrap">{{ number_format($av->montant, 0, ',', ' ') }} F</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 whitespace-nowrap">{{ $av->created_at->format('d/m/Y') }}</p>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
            
            {{-- Colonne latérale : Actions --}}
            <div class="lg:col-span-1">
                <div class="lg:sticky lg:top-4 space-y-4">
                    {{-- Documents et impression --}}
                    @if($vente->statut === 'validee')
                    <div class="card p-4 space-y-3">
                        <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Documents</h3>
                        <div class="space-y-2">
                            @if(auth()->user()->aFonctionnalite('caisse_impression'))
                            <div x-data="printButton()">
                                <button @click="print('{{ route('dashboard.ventes.ticket-pdf', $vente) }}')" 
                                        :disabled="loading" 
                                        class="w-full inline-flex items-center justify-center gap-2 px-3.5 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-all disabled:opacity-50 disabled:cursor-not-allowed dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                                    <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                    </svg>
                                    <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span x-text="loading ? 'Chargement...' : 'Ticket'"></span>
                                </button>
                            </div>
                            <div x-data="printButton()">
                                <button @click="print('{{ route('dashboard.ventes.facture-pdf', $vente) }}')" 
                                        :disabled="loading" 
                                        class="w-full inline-flex items-center justify-center gap-2 px-3.5 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-all disabled:opacity-50 disabled:cursor-not-allowed dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                                    <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span x-text="loading ? 'Chargement...' : 'Facture{{ $vente->numero_facture ? ' (' . $vente->numero_facture . ')' : '' }}'"></span>
                                </button>
                            </div>
                            @else
                            <a href="{{ route('abonnement.upgrade', ['feature' => 'caisse_impression']) }}" 
                               class="w-full inline-flex items-center justify-center gap-2 px-3.5 py-2 text-sm font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded-lg hover:bg-amber-100 transition-colors dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-800 dark:hover:bg-amber-900/30">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 1a5 5 0 00-5 5v4H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2v-9a2 2 0 00-2-2h-2V6a5 5 0 00-5-5zm-3 9V6a3 3 0 016 0v4H9z"/>
                                </svg>
                                Débloquer impression
                            </a>
                            @endif
                        </div>
                    </div>

                    {{-- Communication client --}}
                    @php
                        $waTel = $vente->client?->telephone ? preg_replace('/[^0-9+]/', '', $vente->client->telephone) : null;
                        if ($waTel) {
                            $waTel = ltrim($waTel, '+');
                            if (str_starts_with($waTel, '0')) {
                                $waTel = '225' . $waTel;
                            }
                        }
                        $ticketUrl = route('ticket.public', $vente->id);
                        $waMessage = "Bonjour " . ($vente->client?->prenom ?? '') . "\n\n"
                            . "Merci pour votre achat chez " . (auth()->user()->institut?->nom ?? 'notre institut') . " !\n"
                            . "Ticket n°" . $vente->numero . "\n"
                            . "Total : " . number_format($vente->total, 0, ',', ' ') . " FCFA\n\n"
                            . "Votre ticket : " . $ticketUrl . "\n\n"
                            . "À bientôt !";
                    @endphp
                    @if($vente->client)
                    <div class="card p-4 space-y-3">
                        <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Communication</h3>
                        @if($waTel)
                            <a href="https://wa.me/{{ $waTel }}?text={{ rawurlencode($waMessage) }}" target="_blank" rel="noopener"
                               class="w-full inline-flex items-center justify-center gap-2 px-3.5 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-all dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                                <svg class="w-4 h-4 text-[#25D366]" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20.52 3.48A11.94 11.94 0 0012 0C5.37 0 0 5.37 0 12a11.93 11.93 0 001.64 6.06L0 24l6.18-1.62A11.93 11.93 0 0012 24c6.63 0 12-5.37 12-12 0-3.2-1.25-6.21-3.48-8.52zM12 21.82a9.8 9.8 0 01-5-1.37l-.36-.22-3.67.96.98-3.58-.24-.37A9.82 9.82 0 1721.82 12c0 5.42-4.4 9.82-9.82 9.82zm5.39-7.36c-.29-.15-1.7-.84-1.97-.93-.26-.1-.45-.15-.64.15-.19.29-.74.93-.91 1.12-.17.19-.34.21-.62.07-.29-.15-1.22-.45-2.33-1.44-.86-.77-1.44-1.72-1.61-2.01-.17-.29-.02-.45.13-.6.13-.13.29-.34.43-.5.15-.17.19-.29.29-.48.1-.19.05-.36-.02-.5-.07-.15-.64-1.54-.88-2.11-.23-.55-.47-.48-.64-.48l-.55-.01c-.19 0-.5.07-.76.36-.26.29-1 1-1 2.43s1.02 2.82 1.17 3.02c.15.19 2.02 3.08 4.89 4.32.68.29 1.22.47 1.63.6.69.22 1.31.19 1.81.12.55-.08 1.7-.69 1.94-1.36.24-.67.24-1.25.17-1.36-.07-.12-.26-.19-.55-.34z"/>
                                </svg>
                                Envoyer par WhatsApp
                            </a>
                        @else
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800/50">
                                <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
                                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Aucun numéro de téléphone
                                </p>
                            </div>
                        @endif
                    </div>
                    @endif

                    {{-- Avis client --}}
                    @if($vente->client)
                    @php
                        $avis = \App\Models\AvisClient::withoutGlobalScopes()->where('vente_id', $vente->id)->first();
                        $sondageUrl = $avis ? route('public.avis.show', $avis->token) : null;
                        $sondageEnvoye = $avis && $avis->repondu_le;
                        if ($waTel && $sondageUrl) {
                            $waSondageMessage = "Bonjour " . ($vente->client->prenom ?? '') . " !\n\n"
                                . "Merci pour votre confiance chez " . (auth()->user()->institut?->nom ?? 'notre institut') . "\n\n"
                                . "Votre avis compte beaucoup pour nous ! Pourriez-vous prendre 1 minute pour noter votre expérience ?\n\n"
                                . $sondageUrl . "\n\n"
                                . "Merci d'avance !";
                        }
                    @endphp
                    
                    @if($sondageEnvoye)
                    <div class="card p-4">
                        <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Avis client</h3>
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 space-y-2 dark:border-emerald-800 dark:bg-emerald-900/20">
                            <div class="flex items-center gap-2 text-emerald-700 dark:text-emerald-400">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-sm font-medium">Sondage répondu</span>
                            </div>
                            <p class="text-xs text-emerald-600 dark:text-emerald-400">Le {{ $avis->repondu_le->format('d/m/Y') }}</p>
                            @if($avis->note)
                                <div class="flex items-center gap-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg class="w-4 h-4 {{ $i <= $avis->note ? 'text-yellow-400 dark:text-yellow-300' : 'text-gray-300 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @endfor
                                </div>
                            @endif
                        </div>
                    </div>
                    @else
                    <div class="card p-4 space-y-3">
                        <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Avis client</h3>
                        @if(!$avis)
                            <form method="POST" action="{{ route('dashboard.ventes.sondage.generer', $vente) }}">
                                @csrf
                                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-3.5 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800 dark:hover:bg-blue-900/30">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                    </svg>
                                    Générer lien sondage
                                </button>
                            </form>
                        @elseif($sondageUrl)
                            <div class="space-y-2">
                                <form method="POST" action="{{ route('dashboard.ventes.sondage.envoyer-email', $vente) }}">
                                    @csrf
                                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-3.5 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800 dark:hover:bg-blue-900/30"
                                            @if(!$vente->client->email) disabled title="Client sans email" @endif>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                        Envoyer par Email
                                    </button>
                                </form>
                                
                                @if($waTel)
                                <a href="https://wa.me/{{ $waTel }}?text={{ rawurlencode($waSondageMessage) }}" target="_blank" rel="noopener"
                                   class="w-full inline-flex items-center justify-center gap-2 px-3.5 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-all dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                                    <svg class="w-4 h-4 text-[#25D366]" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M20.52 3.48A11.94 11.94 0 0012 0C5.37 0 0 5.37 0 12a11.93 11.93 0 001.64 6.06L0 24l6.18-1.62A11.93 11.93 0 0012 24c6.63 0 12-5.37 12-12 0-3.2-1.25-6.21-3.48-8.52zM12 21.82a9.8 9.8 0 01-5-1.37l-.36-.22-3.67.96.98-3.58-.24-.37A9.82 9.82 0 1721.82 12c0 5.42-4.4 9.82-9.82 9.82zm5.39-7.36c-.29-.15-1.7-.84-1.97-.93-.26-.1-.45-.15-.64.15-.19.29-.74.93-.91 1.12-.17.19-.34.21-.62.07-.29-.15-1.22-.45-2.33-1.44-.86-.77-1.44-1.72-1.61-2.01-.17-.29-.02-.45.13-.6.13-.13.29-.34.43-.5.15-.17.19-.29.29-.48.1-.19.05-.36-.02-.5-.07-.15-.64-1.54-.88-2.11-.23-.55-.47-.48-.64-.48l-.55-.01c-.19 0-.5.07-.76.36-.26.29-1 1-1 2.43s1.02 2.82 1.17 3.02c.15.19 2.02 3.08 4.89 4.32.68.29 1.22.47 1.63.6.69.22 1.31.19 1.81.12.55-.08 1.7-.69 1.94-1.36.24-.67.24-1.25.17-1.36-.07-.12-.26-.19-.55-.34z"/>
                                    </svg>
                                    Envoyer par WhatsApp
                                </a>
                                @endif
                            </div>
                        @endif
                    </div>
                    @endif
                    @endif

                    {{-- Actions avancées (Admin uniquement) --}}
                    @if(auth()->user()->isAdmin())
                    <div class="card p-4 space-y-3">
                        <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions avancées</h3>
                        <div class="space-y-2">
                            {{-- Créer un avoir --}}
                            @php($montantAvoirsDeja = $vente->avoirs->sum('montant'))
                            @php($montantDisponible = max(0, (int) $vente->total - (int) $montantAvoirsDeja))
                            @if($montantDisponible > 0)
                            <div x-data="{ showAvoir: false, montant: {{ $montantDisponible }}, motif: '' }">
                                <button type="button" @click="showAvoir = true" 
                                        class="w-full inline-flex items-center justify-center gap-2 px-3.5 py-2 text-sm font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded-lg hover:bg-amber-100 transition-colors dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-800 dark:hover:bg-amber-900/30">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"/>
                                    </svg>
                                    Créer un avoir
                                </button>

                                <div x-show="showAvoir" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center px-4" x-transition.opacity>
                                    <div class="absolute inset-0 bg-black/50 dark:bg-black/70" @click="showAvoir = false"></div>
                                    <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md p-6 space-y-4" @click.stop>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Créer un avoir</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            Génère un code de réduction utilisable lors d'une prochaine vente.
                                            Disponible : <strong>{{ number_format($montantDisponible, 0, ',', ' ') }} F</strong>.
                                        </p>
                                        <form method="POST" action="{{ route('dashboard.ventes.avoirs.store', $vente) }}" class="space-y-4">
                                            @csrf
                                            <div>
                                                <label class="form-label">Montant (FCFA) <span class="text-red-500">*</span></label>
                                                <input type="number" name="montant" x-model.number="montant" required
                                                       min="100" max="{{ $montantDisponible }}" step="100"
                                                       class="form-input">
                                            </div>
                                            <div>
                                                <label class="form-label">Motif</label>
                                                <select name="motif" x-model="motif" class="form-select">
                                                    <option value="Retour produit">Retour produit</option>
                                                    <option value="Prestation annulée">Prestation annulée</option>
                                                    <option value="Geste commercial">Geste commercial</option>
                                                    <option value="Autre">Autre</option>
                                                </select>
                                            </div>
                                            <div class="flex gap-3 pt-2">
                                                <button type="button" @click="showAvoir = false" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">Retour</button>
                                                <button type="submit" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors dark:bg-primary-500 dark:hover:bg-primary-600">Créer l'avoir</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endif

                            {{-- Annuler la vente --}}
                            <div x-data="{ showAnnul: false, motif: '' }">
                                <button type="button" @click="showAnnul = true" 
                                        class="w-full inline-flex items-center justify-center gap-2 px-3.5 py-2 text-sm font-medium text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors dark:bg-red-900/20 dark:text-red-400 dark:border-red-800 dark:hover:bg-red-900/30">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    Annuler la vente
                                </button>
                                
                                <div x-show="showAnnul" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center px-4" x-transition.opacity>
                                    <div class="absolute inset-0 bg-black/50 dark:bg-black/70" @click="showAnnul = false"></div>
                                    <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md p-6 space-y-4" @click.stop>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Annuler cette vente ?</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            Le stock sera restauré et les points de fidélité gagnés seront reversés. Cette action est irréversible.
                                        </p>
                                        <form method="POST" action="{{ route('dashboard.ventes.annuler', $vente) }}" class="space-y-4">
                                            @csrf
                                            <div>
                                                <label class="form-label">Motif d'annulation <span class="text-red-500">*</span></label>
                                                <select name="motif_annulation" required x-model="motif" class="form-select">
                                                    <option value="">— Sélectionner —</option>
                                                    <option value="Erreur de caisse">Erreur de caisse</option>
                                                    <option value="Retour client">Retour client</option>
                                                    <option value="Geste commercial">Geste commercial</option>
                                                    <option value="Test / démonstration">Test / démonstration</option>
                                                    <option value="Autre">Autre</option>
                                                </select>
                                            </div>
                                            <div class="flex gap-3 pt-2">
                                                <button type="button" @click="showAnnul = false" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">Retour</button>
                                                <button type="submit" :disabled="!motif" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed dark:bg-red-500 dark:hover:bg-red-600">Confirmer</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    @endif

                    {{-- Info vente annulée --}}
                    @if($vente->statut === 'annulee')
                    <div class="card p-4">
                        <div class="rounded-lg border border-red-200 bg-red-50 p-4 space-y-2 dark:border-red-800 dark:bg-red-900/20">
                            <div class="flex items-center gap-2 text-red-700 dark:text-red-400">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span class="font-semibold">Vente annulée</span>
                            </div>
                            @if($vente->motif_annulation)
                                <p class="text-sm text-red-700 dark:text-red-400 break-words"><strong>Motif :</strong> {{ $vente->motif_annulation }}</p>
                            @endif
                            @if($vente->annulee_le)
                                <p class="text-xs text-red-600/80 dark:text-red-400/80 break-words">
                                    Le {{ $vente->annulee_le->format('d/m/Y à H:i') }}
                                    @if($vente->annulee_par)
                                        @php($annulePar = \App\Models\User::withoutGlobalScopes()->find($vente->annulee_par))
                                        @if($annulePar) par {{ $annulePar->name ?? $annulePar->email }} @endif
                                    @endif
                                </p>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout>
