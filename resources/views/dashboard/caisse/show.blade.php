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
                <a href="{{ route('dashboard.ventes.ticket-pdf', $vente) }}" target="_blank" class="btn-outline w-full justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Imprimer le ticket
                </a>

                @if($vente->statut === 'validee' && auth()->user()->isAdmin())
                <form id="annuler-vente-{{ $vente->id }}" method="POST" action="{{ route('dashboard.ventes.annuler', $vente) }}">
                    @csrf
                </form>
                <button x-data @click="$dispatch('confirm-delete', { formId: 'annuler-vente-{{ $vente->id }}', title: 'Annuler cette vente ?', message: 'Le stock sera restauré pour les produits concernés.' })" class="btn-danger w-full justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Annuler la vente
                </button>
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
                                <span class="ml-1.5 text-xs text-gray-400">{{ $item->type === 'prestation' ? 'Prestation' : 'Produit' }}</span>
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
