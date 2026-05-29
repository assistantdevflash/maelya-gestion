<x-dashboard-layout>
    <div class="max-w-2xl mx-auto space-y-5">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard.ventes.index') }}" class="btn-icon text-gray-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                </svg>
            </a>
            <div>
                <h1 class="page-title">Vente {{ $vente->numero }}</h1>
                <p class="page-subtitle">{{ $vente->created_at->format('d/m/Y à H:i') }}</p>
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div class="card p-5 space-y-3">
                <h2 class="font-semibold text-gray-900 text-sm">Informations</h2>
                <div class="space-y-2 text-sm text-gray-600">
                    <div class="flex justify-between">
                        <span>Statut</span>
                        <span class="badge {{ $vente->statut === 'validee' ? 'badge-success' : 'badge-danger' }}">
                            {{ $vente->statut === 'validee' ? 'Validée' : 'Annulée' }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span>Mode paiement</span>
                        <span class="font-medium">
                            @if($vente->mode_paiement === 'mobile_money') 📱 Mobile Money
                            @elseif($vente->mode_paiement === 'carte') 💳 Carte bancaire
                            @else 💵 Espèces
                            @endif
                        </span>
                    </div>
                    @if($vente->reference_paiement)
                    <div class="flex justify-between">
                        <span>Référence</span>
                        <span class="font-mono text-xs">{{ $vente->reference_paiement }}</span>
                    </div>
                    @endif
                    @if($vente->client)
                    <div class="flex justify-between">
                        <span>Client</span>
                        <a href="{{ route('dashboard.clients.show', $vente->client) }}" class="font-medium text-primary-600 hover:underline">
                            {{ $vente->client->nom_complet }}
                        </a>
                    </div>
                    @endif
                    @if($vente->remise > 0)
                    <div class="flex justify-between">
                        <span>Sous-total</span>
                        <span>{{ number_format($vente->total + $vente->remise, 0, ',', ' ') }} FCFA</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            @if($vente->codeReduction)
                                <span class="font-mono text-xs font-bold text-emerald-700">{{ $vente->codeReduction->code }}</span>
                            @else
                                Code promo
                            @endif
                        </span>
                        <span class="font-semibold text-emerald-600">-{{ number_format($vente->remise, 0, ',', ' ') }} FCFA</span>
                    </div>
                    @endif
                    <div class="flex justify-between border-t border-gray-100 pt-2 font-bold text-gray-900">
                        <span>Total</span>
                        <span>{{ number_format($vente->total, 0, ',', ' ') }} FCFA</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-3">
                @if(auth()->user()->aFonctionnalite('caisse_impression'))
                <a href="{{ route('dashboard.ventes.ticket-pdf', $vente) }}" target="_blank" class="btn-outline w-full justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Imprimer le ticket
                </a>
                @else
                <a href="{{ route('abonnement.upgrade', ['feature' => 'caisse_impression']) }}" class="btn-outline w-full justify-center text-amber-600 border-amber-200 hover:bg-amber-50">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 1a5 5 0 00-5 5v4H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2v-9a2 2 0 00-2-2h-2V6a5 5 0 00-5-5zm-3 9V6a3 3 0 016 0v4H9z"/>
                    </svg>
                    Imprimer le ticket
                </a>
                @endif

                @php
                    $waTel = $vente->client?->telephone ? preg_replace('/[^0-9]/', '', $vente->client->telephone) : null;
                    // Si le numéro commence par 0, on remplace par l'indicatif Sénégal (221) par défaut
                    if ($waTel && str_starts_with($waTel, '0')) {
                        $waTel = '221' . substr($waTel, 1);
                    }
                    $waMessage = "Bonjour " . ($vente->client?->prenom ?? '') . ",\n\n"
                        . "Merci pour votre achat chez " . (auth()->user()->institut?->nom ?? 'notre institut') . " !\n"
                        . "Ticket n°" . $vente->numero . "\n"
                        . "Total : " . number_format($vente->total, 0, ',', ' ') . " FCFA\n"
                        . "Mode de paiement : " . ucfirst(str_replace('_', ' ', $vente->mode_paiement)) . "\n\n"
                        . "À très bientôt 💖";
                @endphp
                @if($waTel && $vente->statut === 'validee')
                <a href="https://wa.me/{{ $waTel }}?text={{ rawurlencode($waMessage) }}" target="_blank" rel="noopener"
                   class="btn-outline w-full justify-center text-emerald-700 border-emerald-200 hover:bg-emerald-50">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20.52 3.48A11.94 11.94 0 0012 0C5.37 0 0 5.37 0 12a11.93 11.93 0 001.64 6.06L0 24l6.18-1.62A11.93 11.93 0 0012 24c6.63 0 12-5.37 12-12 0-3.2-1.25-6.21-3.48-8.52zM12 21.82a9.8 9.8 0 01-5-1.37l-.36-.22-3.67.96.98-3.58-.24-.37A9.82 9.82 0 1121.82 12c0 5.42-4.4 9.82-9.82 9.82zm5.39-7.36c-.29-.15-1.7-.84-1.97-.93-.26-.1-.45-.15-.64.15-.19.29-.74.93-.91 1.12-.17.19-.34.21-.62.07-.29-.15-1.22-.45-2.33-1.44-.86-.77-1.44-1.72-1.61-2.01-.17-.29-.02-.45.13-.6.13-.13.29-.34.43-.5.15-.17.19-.29.29-.48.1-.19.05-.36-.02-.5-.07-.15-.64-1.54-.88-2.11-.23-.55-.47-.48-.64-.48l-.55-.01c-.19 0-.5.07-.76.36-.26.29-1 1-1 2.43s1.02 2.82 1.17 3.02c.15.19 2.02 3.08 4.89 4.32.68.29 1.22.47 1.63.6.69.22 1.31.19 1.81.12.55-.08 1.7-.69 1.94-1.36.24-.67.24-1.25.17-1.36-.07-.12-.26-.19-.55-.34z"/>
                    </svg>
                    Envoyer ticket par WhatsApp
                </a>
                @endif

                @if($vente->statut === 'validee' && auth()->user()->isAdmin())
                <div x-data="{ showAnnul: false, motif: '' }" class="contents">
                    <button type="button" @click="showAnnul = true" class="btn-danger w-full justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Annuler la vente
                    </button>

                    {{-- Modal saisie motif --}}
                    <div x-show="showAnnul" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center px-4"
                         x-transition.opacity>
                        <div class="absolute inset-0 bg-black/50" @click="showAnnul = false"></div>
                        <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-2xl w-full max-w-md p-6 space-y-4" @click.stop>
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">Annuler cette vente ?</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Le stock sera restauré et les points de fidélité gagnés seront reversés. Cette action est irréversible.
                            </p>
                            <form method="POST" action="{{ route('dashboard.ventes.annuler', $vente) }}" class="space-y-3">
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
                                    <button type="button" @click="showAnnul = false" class="btn btn-outline flex-1 justify-center">Retour</button>
                                    <button type="submit" :disabled="!motif" class="btn-danger flex-1 justify-center disabled:opacity-50 disabled:cursor-not-allowed">Confirmer l'annulation</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endif

                @if($vente->statut === 'annulee')
                <div class="rounded-xl border border-red-200 dark:border-red-700/60 bg-red-50 dark:bg-red-900/20 p-3 text-sm space-y-1">
                    <p class="font-semibold text-red-700 dark:text-red-300">Vente annulée</p>
                    @if($vente->motif_annulation)
                        <p class="text-red-700 dark:text-red-300"><span class="opacity-70">Motif :</span> {{ $vente->motif_annulation }}</p>
                    @endif
                    @if($vente->annulee_le)
                        <p class="text-xs text-red-600/80 dark:text-red-300/70">
                            Le {{ $vente->annulee_le->format('d/m/Y à H:i') }}
                            @if($vente->annulee_par)
                                @php($annulePar = \App\Models\User::withoutGlobalScopes()->find($vente->annulee_par))
                                @if($annulePar) par {{ $annulePar->name ?? $annulePar->email }} @endif
                            @endif
                        </p>
                    @endif
                </div>
                @endif
            </div>
        </div>

        {{-- Détail articles --}}
        <div class="card overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900 text-sm">Articles</h2>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-2 text-left text-xs font-semibold text-gray-500">Désignation</th>
                        <th class="px-5 py-2 text-right text-xs font-semibold text-gray-500">Qté</th>
                        <th class="px-5 py-2 text-right text-xs font-semibold text-gray-500">P.U.</th>
                        <th class="px-5 py-2 text-right text-xs font-semibold text-gray-500">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($vente->items as $item)
                    <tr>
                        <td class="px-5 py-2.5">
                            <div>
                                <span class="font-medium text-gray-900">{{ $item->nom_snapshot }}</span>
                                <span class="ml-1.5 text-xs text-gray-400">{{ match($item->type) { 'prestation' => 'Prestation', 'produit' => 'Produit', 'libre' => 'Article libre', default => ucfirst($item->type) } }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-2.5 text-right text-gray-900">{{ $item->quantite }}</td>
                        <td class="px-5 py-2.5 text-right text-gray-600">{{ number_format($item->prix_snapshot, 0, ',', ' ') }}</td>
                        <td class="px-5 py-2.5 text-right font-semibold text-gray-900">{{ number_format($item->sous_total, 0, ',', ' ') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2 border-gray-200 bg-gray-50">
                    @if($vente->remise > 0)
                    <tr>
                        <td colspan="3" class="px-5 py-2 text-right text-sm text-gray-500">Sous-total</td>
                        <td class="px-5 py-2 text-right text-sm text-gray-600">{{ number_format($vente->total + $vente->remise, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="px-5 py-2 text-right text-sm text-emerald-600 font-medium">
                            @if($vente->codeReduction)
                                Code <span class="font-mono font-bold">{{ $vente->codeReduction->code }}</span>
                            @else
                                Réduction
                            @endif
                        </td>
                        <td class="px-5 py-2 text-right text-sm font-semibold text-emerald-600">-{{ number_format($vente->remise, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    @endif
                    <tr>
                        <td colspan="3" class="px-5 py-3 text-right font-bold text-gray-900">Total</td>
                        <td class="px-5 py-3 text-right font-extrabold text-primary-600">{{ number_format($vente->total, 0, ',', ' ') }} FCFA</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</x-dashboard-layout>
