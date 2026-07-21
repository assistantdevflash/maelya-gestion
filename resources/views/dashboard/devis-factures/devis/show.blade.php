<x-dashboard-layout>
<div class="max-w-5xl mx-auto space-y-6" x-data="{}">
    @php $devisId = $devis->id; $estBrouillon = $devis->statut === 'brouillon'; $estModifiable = in_array($devis->statut, ['brouillon', 'envoye']); @endphp

    {{-- ═══ EN-TÊTE ═══ --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard.devis.index') }}" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">{{ $devis->numero }}</h1>
                    @include('dashboard.devis-factures.partials.statut-badge', ['statut' => $devis->statut, 'type' => 'devis'])
                </div>
                <p class="text-sm text-gray-500 mt-0.5">Créé le {{ $devis->date_creation->format('d/m/Y') }} · Expire le {{ $devis->date_expiration->format('d/m/Y') }}</p>
            </div>
        </div>

        {{-- ═══ BOUTONS D'ACTION ═══ --}}
        <div class="flex flex-wrap items-center gap-2">
            @if($estBrouillon)
            <a href="{{ route('dashboard.devis.edit', ['devis' => $devisId]) }}"
               class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-semibold bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-700/30 hover:bg-amber-100 dark:hover:bg-amber-900/40 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Modifier
            </a>
            @endif

            @if($estModifiable)
            <button onclick="document.getElementById('modal-transformer').classList.remove('hidden')"
               class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 shadow-sm shadow-emerald-500/20 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Transformer en facture
            </button>
            @endif

            @if($estBrouillon)
            <form method="POST" action="{{ route('dashboard.devis.envoyer', ['devis' => $devisId]) }}" class="inline">
                @csrf
                <button class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-semibold bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-700/30 hover:bg-blue-100 dark:hover:bg-blue-900/40 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Envoyer par email
                </button>
            </form>
            @endif

            <a href="{{ route('dashboard.devis.pdf', ['devis' => $devisId]) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-semibold bg-gray-50 dark:bg-slate-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-slate-700 hover:bg-gray-100 dark:hover:bg-slate-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                PDF
            </a>

            <a href="{{ route('dashboard.devis.dupliquer', ['devis' => $devisId]) }}"
               class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-semibold bg-gray-50 dark:bg-slate-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-slate-700 hover:bg-gray-100 dark:hover:bg-slate-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                Dupliquer
            </a>
        </div>
    </div>

    {{-- ═══ INFOS CLIENT + DATES ═══ --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-2 card overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100 dark:border-slate-700">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-100 to-secondary-100 dark:from-primary-900/30 dark:to-secondary-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Client</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $devis->client_nom_complet ?: ($devis->client->nom_complet ?? '—') }}</p>
                </div>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                @if($devis->client_telephone)
                <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    {{ $devis->client_telephone }}
                </div>
                @endif
                @if($devis->client_email)
                <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    {{ $devis->client_email }}
                </div>
                @endif
                @if($devis->client_adresse)
                <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    {{ $devis->client_adresse }}
                </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-700">
                <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Dates</p>
            </div>
            <div class="p-5 space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Création</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $devis->date_creation->format('d/m/Y') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Expiration</span>
                    <span class="font-semibold {{ $devis->date_expiration->isPast() ? 'text-red-500' : 'text-gray-900 dark:text-white' }}">
                        {{ $devis->date_expiration->format('d/m/Y') }}
                        @if($devis->date_expiration->isPast())<span class="text-xs ml-1">(expiré)</span>@endif
                    </span>
                </div>
                @if($devis->date_acceptation)
                <div class="flex justify-between">
                    <span class="text-gray-500">Acceptation</span>
                    <span class="font-semibold text-emerald-600">{{ $devis->date_acceptation->format('d/m/Y') }}</span>
                </div>
                @endif
                @if($devis->facture_id)
                <div class="pt-3 border-t border-gray-100 dark:border-slate-700">
                    <a href="{{ route('dashboard.factures.show', ['facture' => $devis->facture_id]) }}"
                       class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-600 hover:text-emerald-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        Voir la facture {{ $devis->facture->numero ?? '' }}
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══ LIGNES ═══ --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-700 flex items-center justify-between">
            <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Articles / Prestations</p>
            <span class="text-xs text-gray-400">{{ $devis->items->count() }} ligne(s)</span>
        </div>
        <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-100 dark:border-slate-700">
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Désignation</th>
                    <th class="px-5 py-3 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider w-20">Qté</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider w-32">Prix unitaire</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider w-32">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-800">
                @foreach($devis->items as $item)
                <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/30 transition-colors">
                    <td class="px-5 py-4">
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $item->designation }}</p>
                    </td>
                    <td class="px-5 py-4 text-center">
                        <span class="inline-flex items-center justify-center min-w-[2rem] h-7 px-2 rounded-lg bg-gray-100 dark:bg-slate-800 text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $item->quantite }}</span>
                    </td>
                    <td class="px-5 py-4 text-right text-gray-600 dark:text-gray-400">{{ number_format($item->prix_unitaire, 0, ',', ' ') }} F</td>
                    <td class="px-5 py-4 text-right font-bold text-gray-900 dark:text-white">{{ number_format($item->total_ligne, 0, ',', ' ') }} F</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>

    {{-- ═══ TOTAUX ═══ --}}
    <div class="flex justify-end">
        <div class="card w-full max-w-sm">
            <div class="p-5 space-y-2.5">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Total HT</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($devis->total_ht, 0, ',', ' ') }} F</span>
                </div>
                @if($devis->remise_globale > 0)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Remise</span>
                    <span class="font-semibold text-red-500">−{{ number_format($devis->remise_globale, 0, ',', ' ') }} F</span>
                </div>
                @endif
                @if($devis->tva_applicable && $devis->tva_taux > 0)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">TVA {{ $devis->tva_taux }}%</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($devis->total_tva, 0, ',', ' ') }} F</span>
                </div>
                @endif
                <div class="flex justify-between pt-3 border-t border-gray-200 dark:border-slate-700">
                    <span class="text-base font-bold text-gray-900 dark:text-white">Total TTC</span>
                    <span class="text-base font-bold text-primary-600 dark:text-primary-400">{{ number_format($devis->total_ttc, 0, ',', ' ') }} F</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ NOTES ═══ --}}
    @if($devis->notes)
    <div class="card">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-700">
            <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Notes</p>
        </div>
        <div class="p-5 text-sm text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{{ $devis->notes }}</div>
    </div>
    @endif

    {{-- ═══ MODAL CONFIRMATION : TRANSFORMER EN FACTURE ═══ --}}
    <div id="modal-transformer" class="hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4"
         onclick="if(event.target===this) this.classList.add('hidden')">
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" onclick="event.stopPropagation()">
            <div class="p-6 text-center">
                <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                    <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Transformer en facture ?</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">
                    Le devis <strong class="text-gray-700 dark:text-gray-300">{{ $devis->numero }}</strong> sera converti en facture.
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Montant : <strong class="text-gray-900 dark:text-white">{{ number_format($devis->total_ttc, 0, ',', ' ') }} F</strong>
                </p>
            </div>
            <div class="grid grid-cols-2 divide-x divide-gray-100 dark:divide-slate-700 border-t border-gray-100 dark:border-slate-700">
                <button onclick="document.getElementById('modal-transformer').classList.add('hidden')"
                        class="py-3.5 text-sm font-semibold text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-slate-800 transition">
                    Annuler
                </button>
                <form method="POST" action="{{ route('dashboard.devis.transformer', ['devis' => $devisId]) }}">
                    @csrf
                    <button class="w-full py-3.5 text-sm font-semibold text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition">
                        Confirmer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
</x-dashboard-layout>
