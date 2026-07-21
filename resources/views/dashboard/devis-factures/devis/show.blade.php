<x-dashboard-layout>
<div class="max-w-5xl mx-auto space-y-6" x-data="{}">
    @php
        $devisId = $devis->id;
        $estBrouillon = $devis->statut === 'brouillon';
        $estModifiable = in_array($devis->statut, ['brouillon', 'envoye']);
        $client = $devis->client;
    @endphp

    {{-- ═══ EN-TÊTE ═══ --}}
    <div class="flex flex-col gap-5">
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

        @php
            $clientTel = $devis->client_telephone ?: ($client->telephone ?? null);
            $clientEmail = $devis->client_email ?: ($client->email ?? null);
            $clientAdresse = $devis->client_adresse ?: ($client->adresse ?? null);
            $whatsappTel = $clientTel ? preg_replace('/[^0-9]/', '', $clientTel) : '';
            if ($whatsappTel && str_starts_with($whatsappTel, '0')) $whatsappTel = '225' . $whatsappTel;
            $whatsappMsg = "Bonjour " . ($client?->prenom ?? '') . "\n\n"
                . "Voici votre devis " . $devis->numero . "\n"
                . "Montant : " . number_format($devis->total_ttc, 0, ',', ' ') . " F CFA\n"
                . "Valable jusqu'au " . $devis->date_expiration->format('d/m/Y') . "\n\n"
                . "Merci de votre confiance !";
            $mailtoSubject = urlencode("Devis {$devis->numero}");
            $mailtoBody = urlencode("Bonjour,\n\nVeuillez trouver ci-joint votre devis {$devis->numero}.\nMontant : " . number_format($devis->total_ttc, 0, ',', ' ') . " F CFA.\n\nCordialement.");
        @endphp

        {{-- ═══ BARRE D'ACTIONS ═══ --}}
        <div class="flex flex-wrap items-center gap-2">
            {{-- Actions principales (toujours visibles) --}}
            @if($estModifiable)
            <button onclick="openModal('modal-transformer')"
               class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 shadow-sm shadow-emerald-500/20 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="hidden sm:inline">Transformer en facture</span><span class="sm:hidden">Facture</span>
            </button>
            @endif

            @if($estBrouillon)
            <form method="POST" action="{{ route('dashboard.devis.envoyer', ['devis' => $devisId]) }}" class="inline">
                @csrf
                <button class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-semibold bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-700/30 hover:bg-blue-100 dark:hover:bg-blue-900/40 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span class="hidden sm:inline">Envoyer par email</span><span class="sm:hidden">Email</span>
                </button>
            </form>
            @endif

            {{-- Dropdown actions secondaires --}}
            <x-dropdown-actions>
                @if($estBrouillon)
                <a href="{{ route('dashboard.devis.edit', ['devis' => $devisId]) }}"
                   class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Modifier
                </a>
                @endif
                <a href="{{ route('dashboard.devis.pdf', ['devis' => $devisId]) }}" target="_blank"
                   class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Télécharger PDF
                </a>
                @if($whatsappTel)
                <a href="https://wa.me/{{ $whatsappTel }}?text={{ rawurlencode($whatsappMsg) }}" target="_blank" rel="noopener"
                   class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                    <svg class="w-4 h-4 text-[#25D366]" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    Envoyer par WhatsApp
                </a>
                @endif
                @if($clientEmail)
                <a href="mailto:{{ $devis->client_email }}?subject={{ $mailtoSubject }}&body={{ $mailtoBody }}"
                   class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Envoyer par Email
                </a>
                @endif
                <button onclick="openModal('modal-dupliquer')"
                   class="w-full text-left flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    Dupliquer
                </button>
                @if($estBrouillon)
                <div class="border-t border-gray-100 dark:border-slate-700 my-1"></div>
                <button onclick="openModal('modal-supprimer')"
                   class="w-full text-left flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Supprimer
                </button>
                @endif
            </x-dropdown-actions>
        </div>
    </div>

    {{-- ═══ INFOS CLIENT ═══ --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-2 card overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-slate-700">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-100 to-secondary-100 dark:from-primary-900/30 dark:to-secondary-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Client</p>
                        @if($client)
                        <a href="{{ route('dashboard.clients.show', $client) }}" class="text-lg font-bold text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 transition">
                            {{ $devis->client_nom_complet ?: $client->nom_complet }}
                        </a>
                        @else
                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $devis->client_nom_complet ?: '—' }}</p>
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
            <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
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

    {{-- ═══ MODALS ═══ --}}
    <x-modal-confirm id="modal-transformer" title="Transformer en facture ?"
        message="Le devis &laquo;&nbsp;{{ $devis->numero }}&nbsp;&raquo; sera converti en facture de <strong>{{ number_format($devis->total_ttc, 0, ',', ' ') }} F</strong>."
        action="{{ route('dashboard.devis.transformer', ['devis' => $devisId]) }}" method="POST" confirm="Confirmer" />

    <x-modal-confirm id="modal-dupliquer" title="Dupliquer ce devis ?"
        message="Un nouveau devis sera créé avec les mêmes lignes et le même client.<br>Le devis actuel ne sera pas modifié."
        :href="route('dashboard.devis.dupliquer', ['devis' => $devisId])" confirm="Dupliquer" />

    <x-modal-confirm id="modal-supprimer" title="Supprimer ce devis ?"
        message="Cette action est irréversible. Le devis &laquo;&nbsp;{{ $devis->numero }}&nbsp;&raquo; sera définitivement supprimé."
        action="{{ route('dashboard.devis.destroy', ['devis' => $devisId]) }}" method="DELETE" confirm="Supprimer" danger="true" />
</div>

@push('scripts')
<script>function openModal(id){document.getElementById(id).classList.remove('hidden');}</script>
@endpush
</x-dashboard-layout>
