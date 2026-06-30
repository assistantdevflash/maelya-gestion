<x-dashboard-layout>
    <x-slot name="title">Crédit #{{ substr($credit->id, 0, 8) }}</x-slot>

    <div class="space-y-5">
        {{-- En-tête --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('dashboard.credits.index') }}" class="btn-icon flex-shrink-0" title="Retour">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div class="min-w-0">
                    <h1 class="text-lg sm:text-2xl font-display font-bold text-gray-900 truncate">Crédit {{ $credit->client?->nom_complet ?? '—' }}</h1>
                    <p class="text-xs sm:text-sm text-gray-500">Vente #{{ $credit->vente?->numero ?? substr($credit->vente_id, 0, 8) }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                @if($credit->statut === 'solde')
                    <span class="badge badge-success text-xs">Soldé</span>
                @elseif($credit->statut === 'retard')
                    <span class="badge badge-danger text-xs">En retard</span>
                @else
                    <span class="badge badge-info text-xs">En cours</span>
                @endif
                <a href="{{ route('dashboard.credits.fiche-pdf', $credit) }}" class="btn-outline text-xs py-1.5 px-3 whitespace-nowrap" title="Télécharger la fiche PDF">
                    🖨️ <span class="hidden sm:inline">Imprimer</span>
                </a>
                @php
                    $waPhonePrint = $credit->client?->telephone ? preg_replace('/[^0-9+]/', '', $credit->client->telephone) : null;
                    if ($waPhonePrint) {
                        $waPhonePrint = ltrim($waPhonePrint, '+');
                        if (str_starts_with($waPhonePrint, '0')) {
                            $waPhonePrint = '225' . $waPhonePrint;
                        }
                    }
                    $ficheUrl = route('credit.fiche.public', $credit->id);
                    $waMsgPrint = $waPhonePrint ? rawurlencode(
                        "Bonjour " . ($credit->client->prenom ?? '') . ",\n\n"
                        . "Voici votre fiche de credit :\n"
                        . "Montant total : " . number_format($credit->montant_total, 0, ',', ' ') . " FCFA\n"
                        . "Reste a payer : " . number_format($credit->reste_a_payer, 0, ',', ' ') . " FCFA\n"
                        . "Fiche : " . $ficheUrl . "\n\n"
                        . "Merci de votre confiance !"
                    ) : null;
                @endphp
                @if($waPhonePrint)
                <a href="https://wa.me/{{ $waPhonePrint }}?text={{ $waMsgPrint }}" target="_blank" class="btn-outline text-xs py-1.5 px-3 text-green-600 border-green-200 hover:bg-green-50 whitespace-nowrap" title="Partager par WhatsApp">
                    💬 <span class="hidden sm:inline">WhatsApp</span>
                </a>
                @endif
            </div>
        </div>

        @if(session('success'))
        <div class="flex items-center gap-3 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-sm text-emerald-700">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('success') }}
        </div>
        @endif

        {{-- Résumé --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="card p-5">
                <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Client</p>
                <p class="font-bold text-gray-900">{{ $credit->client?->nom_complet ?? '—' }}</p>
                @if($credit->client?->telephone)
                <p class="text-sm text-gray-500">{{ $credit->client->telephone }}</p>
                @endif
                <a href="{{ route('dashboard.clients.show', $credit->client) }}" class="text-xs text-primary-600 hover:underline mt-1 inline-block">Voir fiche client</a>
            </div>
            <div class="card p-5">
                <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Progression</p>
                @php $pct = $credit->montant_total > 0 ? round(($credit->montant_total - $credit->reste_a_payer) * 100 / $credit->montant_total) : 100; @endphp
                <div class="flex items-center gap-3">
                    <div class="flex-1 h-2.5 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full rounded-full {{ $credit->statut === 'solde' ? 'bg-emerald-500' : 'bg-primary-500' }}" style="width: {{ $pct }}%"></div>
                    </div>
                    <span class="text-sm font-bold text-gray-900">{{ $pct }}%</span>
                </div>
                <div class="flex justify-between text-xs mt-2">
                    <span class="text-emerald-600">{{ number_format($credit->montant_total - $credit->reste_a_payer, 0, ',', ' ') }} F payé</span>
                    <span class="text-red-500">{{ number_format($credit->reste_a_payer, 0, ',', ' ') }} F restant</span>
                </div>
            </div>
            <div class="card p-5">
                <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Conditions</p>
                <p class="text-sm"><strong>{{ $credit->nb_echeances }} échéances</strong> {{ $credit->frequence === 'mensuelle' ? 'mensuelles' : 'hebdomadaires' }}</p>
                <p class="text-xs text-gray-500 mt-1">Début : {{ \Carbon\Carbon::parse($credit->date_debut)->format('d/m/Y') }}</p>
                <p class="text-xs text-gray-500">Fin prévue : {{ $credit->date_fin_prevue ? \Carbon\Carbon::parse($credit->date_fin_prevue)->format('d/m/Y') : '—' }}</p>
            </div>
        </div>

        {{-- Articles --}}
        <div class="card p-5">
            <h2 class="text-sm font-bold text-gray-900 mb-3">Articles vendus</h2>
            <div class="space-y-2">
                @foreach($credit->vente->items as $item)
                <div class="flex justify-between text-sm">
                    <span>{{ $item->nom_snapshot }} ×{{ $item->quantite }}</span>
                    <span class="font-semibold">{{ number_format($item->sous_total, 0, ',', ' ') }} F</span>
                </div>
                @endforeach
                <div class="flex justify-between text-sm font-bold pt-2 border-t">
                    <span>Total</span>
                    <span>{{ number_format($credit->montant_total, 0, ',', ' ') }} F</span>
                </div>
            </div>
        </div>

        {{-- Échéancier --}}
        <div class="card overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h2 class="text-sm font-bold text-gray-900">Échéancier</h2>
            </div>
            <div class="overflow-x-auto -webkit-overflow-scrolling:touch">
            <table class="w-full text-sm min-w-[500px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 sm:px-4 py-2 text-left text-xs font-semibold text-gray-500">N°</th>
                        <th class="px-3 sm:px-4 py-2 text-left text-xs font-semibold text-gray-500">Date prévue</th>
                        <th class="px-3 sm:px-4 py-2 text-right text-xs font-semibold text-gray-500">Montant</th>
                        <th class="px-3 sm:px-4 py-2 text-right text-xs font-semibold text-gray-500">Payé</th>
                        <th class="px-3 sm:px-4 py-2 text-center text-xs font-semibold text-gray-500">Statut</th>
                        <th class="px-3 sm:px-4 py-2 text-right text-xs font-semibold text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($credit->echeances as $echeance)
                    <tr class="{{ $echeance->statut === 'retard' ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                        <td class="px-3 sm:px-4 py-3 font-mono text-xs">{{ $echeance->numero }}/{{ $credit->nb_echeances }}</td>
                        <td class="px-3 sm:px-4 py-3 whitespace-nowrap {{ $echeance->statut === 'retard' ? 'text-red-600 font-semibold' : '' }}">
                            {{ \Carbon\Carbon::parse($echeance->date_prevue)->format('d/m/Y') }}
                        </td>
                        <td class="px-3 sm:px-4 py-3 text-right font-mono text-xs whitespace-nowrap">{{ number_format($echeance->montant, 0, ',', ' ') }} F</td>
                        <td class="px-3 sm:px-4 py-3 text-right font-mono text-xs text-emerald-600 whitespace-nowrap">
                            {{ $echeance->montant_paye > 0 ? number_format($echeance->montant_paye, 0, ',', ' ') . ' F' : '—' }}
                        </td>
                        <td class="px-3 sm:px-4 py-3 text-center">
                            @if($echeance->statut === 'payee')
                                <span class="badge badge-success text-[10px]">Payée</span>
                            @elseif($echeance->statut === 'retard')
                                <span class="badge badge-danger text-[10px]">Retard</span>
                            @else
                                <span class="badge badge-secondary text-[10px]">En attente</span>
                            @endif
                        </td>
                        <td class="px-3 sm:px-4 py-3 text-right whitespace-nowrap">
                            @if($echeance->statut !== 'payee')
                            <button onclick="document.getElementById('payer-{{ $echeance->id }}').showModal()"
                                    class="text-xs font-semibold text-primary-600 hover:text-primary-700 whitespace-nowrap">
                                Encaisser
                            </button>

                            {{-- Mini dialogue de paiement --}}
                            <dialog id="payer-{{ $echeance->id }}" class="rounded-2xl shadow-xl border-0 p-0 w-full max-w-sm backdrop:bg-black/50">
                                <form method="POST" action="{{ route('dashboard.credits.payer', $credit) }}" class="p-5 space-y-4">
                                    @csrf
                                    <input type="hidden" name="echeance_id" value="{{ $echeance->id }}">
                                    <h3 class="text-base font-bold text-gray-900">Encaisser échéance {{ $echeance->numero }}/{{ $credit->nb_echeances }}</h3>
                                    <div>
                                        <label class="text-xs text-gray-500">Montant à encaisser</label>
                                        <input type="number" name="montant" value="{{ $echeance->montant - $echeance->montant_paye }}"
                                               min="1" max="{{ $echeance->montant - $echeance->montant_paye }}"
                                               class="form-input text-sm mt-1" required>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2">
                                        <label class="flex items-center gap-1 text-xs cursor-pointer">
                                            <input type="radio" name="mode_paiement" value="cash" checked> Espèces
                                        </label>
                                        <label class="flex items-center gap-1 text-xs cursor-pointer">
                                            <input type="radio" name="mode_paiement" value="mobile_money"> Mobile
                                        </label>
                                        <label class="flex items-center gap-1 text-xs cursor-pointer">
                                            <input type="radio" name="mode_paiement" value="carte"> Carte
                                        </label>
                                    </div>
                                    <input type="text" name="reference" placeholder="Référence (optionnel)" class="form-input text-sm">
                                    <div class="flex gap-2">
                                        <button type="button" onclick="this.closest('dialog').close()" class="flex-1 btn-outline justify-center text-sm py-2">Annuler</button>
                                        <button type="submit" class="flex-1 btn-primary justify-center text-sm py-2">Encaisser</button>
                                    </div>
                                </form>
                            </dialog>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>{{-- fin overflow-x-auto --}}
        </div>

        {{-- Historique des paiements --}}
        @if($credit->paiements->isNotEmpty())
        <div class="card overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h2 class="text-sm font-bold text-gray-900">Historique des paiements</h2>
            </div>
            <div class="overflow-x-auto -webkit-overflow-scrolling:touch">
            <table class="w-full text-sm min-w-[400px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 sm:px-4 py-2 text-left text-xs font-semibold text-gray-500">Date</th>
                        <th class="px-3 sm:px-4 py-2 text-right text-xs font-semibold text-gray-500">Montant</th>
                        <th class="px-3 sm:px-4 py-2 text-left text-xs font-semibold text-gray-500">Mode</th>
                        <th class="px-3 sm:px-4 py-2 text-left text-xs font-semibold text-gray-500">Encaissé par</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($credit->paiements as $p)
                    <tr>
                        <td class="px-3 sm:px-4 py-2 text-xs whitespace-nowrap">{{ \Carbon\Carbon::parse($p->created_at)->format('d/m/Y H:i') }}</td>
                        <td class="px-3 sm:px-4 py-2 text-right font-mono text-xs font-semibold whitespace-nowrap">{{ number_format($p->montant, 0, ',', ' ') }} F</td>
                        <td class="px-3 sm:px-4 py-2 text-xs whitespace-nowrap">
                            @if($p->mode_paiement === 'cash') 💵 Espèces
                            @elseif($p->mode_paiement === 'mobile_money') 📱 Mobile
                            @else 💳 Carte
                            @endif
                        </td>
                        <td class="px-3 sm:px-4 py-2 text-xs whitespace-nowrap">{{ $p->encaisseur?->prenom ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
        @endif
    </div>
</x-dashboard-layout>
