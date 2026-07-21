<x-dashboard-layout>
<div class="max-w-5xl mx-auto space-y-6">
    @php
        $factureId = $facture->id;
        $client = $facture->client;
        $clientTel = $facture->client_telephone ?: ($client->telephone ?? null);
        $clientEmail = $facture->client_email ?: ($client->email ?? null);
        $clientAdresse = $facture->client_adresse ?: ($client->adresse ?? null);
        $whatsappTel = $clientTel ? preg_replace('/[^0-9]/', '', $clientTel) : '';
        if ($whatsappTel && str_starts_with($whatsappTel, '0')) $whatsappTel = '225' . $whatsappTel;
        $whatsappMsg = rawurlencode("Bonjour " . ($client?->prenom ?? '') . ",\n\n"
            . "Voici votre facture " . $facture->numero . "\n"
            . "Montant : " . number_format($facture->total_ttc, 0, ',', ' ') . " F CFA\n"
            . "Échéance : " . $facture->date_echeance->format('d/m/Y') . "\n"
            . ($facture->resteAPayer > 0 ? "Reste à payer : " . number_format($facture->resteAPayer, 0, ',', ' ') . " F CFA\n" : "")
            . "\nTélécharger le PDF : " . route('dashboard.factures.pdf', ['facture' => $factureId]) . "\n\n"
            . "Merci de votre confiance !");
    @endphp

    {{-- ═══ EN-TÊTE ═══ --}}
    <div class="flex flex-col gap-5">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard.factures.index') }}" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">{{ $facture->numero }}</h1>
                    @include('dashboard.devis-factures.partials.statut-badge', ['statut' => $facture->statut, 'type' => 'facture'])
                </div>
                <p class="text-sm text-gray-500 mt-0.5">Émise le {{ $facture->date_emission->format('d/m/Y') }} · Échéance {{ $facture->date_echeance->format('d/m/Y') }}</p>
            </div>
        </div>

        {{-- ═══ BARRE D'ACTIONS ═══ --}}
        <div class="flex flex-wrap items-center gap-2 justify-end">
            {{-- Actions principales (toujours visibles) --}}
            @if(!$facture->estPayee && $facture->statut !== 'annulee')
            <button onclick="document.getElementById('modal-paiement').classList.remove('hidden')"
               class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 shadow-sm shadow-emerald-500/20 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="hidden sm:inline">Enregistrer un paiement</span><span class="sm:hidden">Paiement</span>
            </button>
            <form method="POST" action="{{ route('dashboard.factures.marquer-payee', ['facture' => $factureId]) }}" class="inline">
                @csrf
                <button onclick="return confirm('Marquer comme entièrement payée ?')"
                   class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-semibold bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700/30 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span class="hidden sm:inline">Marquer payée</span><span class="sm:hidden">Payée</span>
                </button>
            </form>
            @endif

            {{-- Email : envoi réel avec PDF joint --}}
            @if($clientEmail)
            <form method="POST" action="{{ route('dashboard.factures.envoyer-email', ['facture' => $factureId]) }}" class="inline">
                @csrf
                <button class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-semibold bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-700/30 hover:bg-blue-100 dark:hover:bg-blue-900/40 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span class="hidden sm:inline">Email</span>
                </button>
            </form>
            @endif

            {{-- WhatsApp --}}
            @if($whatsappTel)
            <a href="https://wa.me/{{ $whatsappTel }}?text={{ $whatsappMsg }}" target="_blank" rel="noopener"
               class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-semibold bg-[#25D366]/10 text-[#25D366] border border-[#25D366]/20 hover:bg-[#25D366]/20 transition">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                <span class="hidden sm:inline">WhatsApp</span>
            </a>
            @endif

            {{-- Dropdown actions secondaires --}}
            <x-dropdown-actions>
                <a href="{{ route('dashboard.factures.pdf', ['facture' => $factureId]) }}" target="_blank"
                   class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Télécharger PDF
                </a>
                @if($facture->statut !== 'annulee')
                <button onclick="openModal('modal-annuler')"
                   class="w-full text-left flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Annuler la facture
                </button>
                @endif
            </x-dropdown-actions>
        </div>
    </div>

    {{-- ═══ INFOS CLIENT ═══ --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-slate-700">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-100 to-secondary-100 dark:from-primary-900/30 dark:to-secondary-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Client</p>
                        @if($client)
                        <a href="{{ route('dashboard.clients.show', $client) }}" class="text-lg font-bold text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 transition">
                            {{ $facture->client_nom_complet ?: $client->nom_complet }}
                        </a>
                        @else
                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $facture->client_nom_complet ?: '—' }}</p>
                        @endif
                    </div>
                </div>
                @if($client)
                <a href="{{ route('dashboard.clients.show', $client) }}" class="text-xs text-primary-600 hover:text-primary-700 font-medium flex items-center gap-1">
                    Voir la fiche
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                @endif
            </div>
            <div class="p-5 flex flex-col gap-2 text-sm">
                @if($clientTel)
                <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    <span>{{ $clientTel }}</span>
                </div>
                @endif
                @if($clientEmail)
                <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span>{{ $clientEmail }}</span>
                </div>
                @endif
                @if($clientAdresse)
                <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400 col-span-full">
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span>{{ $clientAdresse }}</span>
                </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-700">
                <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Dates & Paiement</p>
            </div>
            <div class="p-5 space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Émission</span><span class="font-semibold text-gray-900 dark:text-white">{{ $facture->date_emission->format('d/m/Y') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Échéance</span><span class="font-semibold {{ $facture->date_echeance->isPast() && !$facture->estPayee ? 'text-red-500' : 'text-gray-900 dark:text-white' }}">{{ $facture->date_echeance->format('d/m/Y') }}</span></div>
                <div class="pt-3 border-t border-gray-100 dark:border-slate-700">
                    <div class="w-full bg-gray-200 dark:bg-slate-700 rounded-full h-2 mb-2">
                        <div class="bg-emerald-500 h-2 rounded-full" style="width: {{ $facture->total_ttc > 0 ? min(100, ($facture->montant_paye / $facture->total_ttc) * 100) : 0 }}%"></div>
                    </div>
                    <p class="font-bold text-gray-900 dark:text-white">{{ number_format($facture->montant_paye, 0, ',', ' ') }} / {{ number_format($facture->total_ttc, 0, ',', ' ') }} F</p>
                    @if($facture->resteAPayer > 0)<p class="text-red-500 font-semibold text-xs mt-0.5">Reste : {{ number_format($facture->resteAPayer, 0, ',', ' ') }} F</p>@endif
                </div>
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
            <div class="flex justify-between"><span class="text-gray-500">Sous-total</span><span>{{ number_format($facture->sous_total, 0, ',', ' ') }} F</span></div>
            @if($facture->remise_globale > 0)
            <div class="flex justify-between">
                <span class="text-gray-500">
                    Remise
                    @if($facture->remise_globale_type === 'pourcentage')
                        ({{ (int) $facture->remise_globale_valeur }}%)
                    @endif
                </span>
                <span class="text-red-500">−{{ number_format($facture->remise_globale, 0, ',', ' ') }} F</span>
            </div>
            @endif
            <div class="flex justify-between"><span class="text-gray-500">Total HT</span><span>{{ number_format($facture->total_ht, 0, ',', ' ') }} F</span></div>
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
        <form method="POST" action="{{ route('dashboard.factures.payer', ['facture' => $factureId]) }}">
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

{{-- ═══ MODALS ═══ --}}
<x-modal-confirm id="modal-annuler" title="Annuler cette facture ?"
    message="La facture &laquo;&nbsp;{{ $facture->numero }}&nbsp;&raquo; sera marquée comme annulée."
    action="{{ route('dashboard.factures.annuler', ['facture' => $factureId]) }}" method="POST" confirm="Annuler la facture" danger="true" />

@push('scripts')
<script>function openModal(id){document.getElementById(id).classList.remove('hidden');}</script>
@endpush
</x-dashboard-layout>
