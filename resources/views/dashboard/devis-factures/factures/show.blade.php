<x-dashboard-layout>
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('dashboard.factures.index') }}" class="p-2 text-gray-400 hover:text-gray-600 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 transition">←</a>
            <div><h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $facture->numero }}</h1>
                <div class="mt-1">@include('dashboard.devis-factures.partials.statut-badge', ['statut' => $facture->statut, 'type' => 'facture'])</div>
            </div>
        </div>
        <div class="flex gap-2">
            @if(!$facture->estPayee && $facture->statut !== 'annulee')
            <button onclick="document.getElementById('modal-paiement').classList.remove('hidden')" class="btn-primary text-sm">💰 Paiement</button>
            <form method="POST" action="{{ route('dashboard.factures.marquer-payee', $facture) }}" class="inline" onsubmit="return confirm('Marquer comme entièrement payée ?')">
                @csrf<button class="btn-outlined text-sm">✓ Marquer payée</button>
            </form>
            @endif
            <a href="{{ route('dashboard.factures.pdf', $facture) }}" target="_blank" class="btn-outlined text-sm">📄 PDF</a>
            @if($facture->statut !== 'annulee')
            <form method="POST" action="{{ route('dashboard.factures.annuler', $facture) }}" class="inline" onsubmit="return confirm('Annuler cette facture ?')">
                @csrf<button class="btn-outlined text-sm text-red-600 border-red-200 hover:bg-red-50">Annuler</button>
            </form>
            @endif
        </div>
    </div>

    {{-- Infos --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="card">
            <div class="card-header"><h2 class="font-semibold">Client</h2></div>
            <div class="card-body text-sm space-y-1">
                <p class="font-bold text-gray-900 dark:text-white">{{ $facture->client_nom_complet ?: ($facture->client->nom_complet ?? '—') }}</p>
                @if($facture->client_telephone)<p>📞 {{ $facture->client_telephone }}</p>@endif
                @if($facture->client_email)<p>✉️ {{ $facture->client_email }}</p>@endif
                @if($facture->client_adresse)<p class="text-gray-500">{{ $facture->client_adresse }}</p>@endif
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h2 class="font-semibold">Dates</h2></div>
            <div class="card-body text-sm space-y-1">
                <p><span class="text-gray-500">Émise le :</span> {{ $facture->date_emission->format('d/m/Y') }}</p>
                <p><span class="text-gray-500">Échéance :</span> {{ $facture->date_echeance->format('d/m/Y') }}</p>
                @if($facture->statut === 'payee')
                <p><span class="text-gray-500">Payée le :</span> {{ $facture->date_paiement->format('d/m/Y') }}</p>
                @endif
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h2 class="font-semibold">Paiement</h2></div>
            <div class="card-body text-sm space-y-1">
                <div class="w-full bg-gray-200 dark:bg-slate-700 rounded-full h-2.5 mb-2">
                    <div class="bg-emerald-500 h-2.5 rounded-full" style="width: {{ $facture->total_ttc > 0 ? min(100, ($facture->montant_paye / $facture->total_ttc) * 100) : 0 }}%"></div>
                </div>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($facture->montant_paye, 0, ',', ' ') }} / {{ number_format($facture->total_ttc, 0, ',', ' ') }} F</p>
                @if($facture->resteAPayer > 0)
                <p class="text-red-500 font-semibold">Reste à payer : {{ number_format($facture->resteAPayer, 0, ',', ' ') }} F</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Lignes --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-slate-800"><tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Désignation</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase w-16">Qté</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">PU</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                @foreach($facture->items as $item)
                <tr>
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $item->designation }}</td>
                    <td class="px-4 py-3 text-center">{{ $item->quantite }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($item->prix_unitaire, 0, ',', ' ') }} F</td>
                    <td class="px-4 py-3 text-right font-bold">{{ number_format($item->total_ligne, 0, ',', ' ') }} F</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>

    {{-- Totaux --}}
    <div class="card ml-auto w-full max-w-sm">
        <div class="card-body space-y-2 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">Total HT</span><span>{{ number_format($facture->total_ht, 0, ',', ' ') }} F</span></div>
            @if($facture->remise_globale > 0)
            <div class="flex justify-between"><span class="text-gray-500">Remise</span><span class="text-red-500">−{{ number_format($facture->remise_globale, 0, ',', ' ') }} F</span></div>
            @endif
            @if($facture->tva_taux > 0)
            <div class="flex justify-between"><span class="text-gray-500">TVA {{ $facture->tva_taux }}%</span><span>{{ number_format($facture->total_tva, 0, ',', ' ') }} F</span></div>
            @endif
            <div class="flex justify-between text-lg font-bold pt-2 border-t"><span>Total TTC</span><span class="text-primary-600">{{ number_format($facture->total_ttc, 0, ',', ' ') }} F</span></div>
        </div>
    </div>

    {{-- Paiements --}}
    <div class="card">
        <div class="card-header flex justify-between items-center">
            <h2 class="font-semibold">Historique des paiements</h2>
            <button onclick="document.getElementById('modal-paiement').classList.remove('hidden')" class="text-sm text-primary-600 hover:text-primary-700 font-medium">+ Ajouter</button>
        </div>
        <div class="card-body">
            @if($facture->paiements->count() === 0)
            <p class="text-sm text-gray-400 text-center py-4">Aucun paiement enregistré.</p>
            @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-slate-800"><tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Date</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500">Montant</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Mode</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Réf</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    @foreach($facture->paiements as $p)
                    <tr>
                        <td class="px-3 py-2">{{ $p->date_paiement->format('d/m/Y') }}</td>
                        <td class="px-3 py-2 text-right font-bold">{{ number_format($p->montant, 0, ',', ' ') }} F</td>
                        <td class="px-3 py-2">{{ $p->mode_paiement_label ?? $p->mode_paiement }}</td>
                        <td class="px-3 py-2 text-gray-500 font-mono text-xs">{{ $p->reference ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>
</div>

{{-- Modal Paiement --}}
<div id="modal-paiement" class="hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4" onclick="if(event.target===this) this.classList.add('hidden')">
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl w-full max-w-sm p-6 space-y-4" onclick="event.stopPropagation()">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-bold">Nouveau paiement</h3>
            <button onclick="document.getElementById('modal-paiement').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form method="POST" action="{{ route('dashboard.factures.payer', $facture) }}">
            @csrf
            <div class="space-y-3">
                <div><label class="form-label">Date</label><input type="date" name="date_paiement" value="{{ now()->toDateString() }}" class="form-input" required></div>
                <div><label class="form-label">Montant</label><input type="number" name="montant" min="1" max="{{ $facture->resteAPayer }}" value="{{ $facture->resteAPayer }}" class="form-input" required></div>
                <div><label class="form-label">Mode</label>
                    <select name="mode_paiement" class="form-input" required>
                        <option value="especes">Espèces</option><option value="mobile_money">Mobile Money</option><option value="virement">Virement</option><option value="cheque">Chèque</option><option value="carte">Carte</option>
                    </select>
                </div>
                <div><label class="form-label">Référence</label><input type="text" name="reference" placeholder="N° transaction..." class="form-input"></div>
            </div>
            <button type="submit" class="btn-primary w-full mt-4">Enregistrer le paiement</button>
        </form>
    </div>
</div>
</x-dashboard-layout>
