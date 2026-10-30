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
                    $creditService = app(\App\Services\CreditService::class);
                    $waLinkRelance = $creditService->lienWhatsApp($credit);
                    $waLinkFiche = $creditService->lienWhatsApp($credit, 'fiche');
                    // Échéances réellement en retard : statut « retard » ou date
                    // dépassée sans règlement complet.
                    $nbEcheancesRetard = $credit->echeances->filter(
                        fn ($e) => (int) $e->montant_paye < (int) $e->montant
                            && ($e->statut === 'retard' || ($e->date_prevue && $e->date_prevue->lt(today())))
                    )->count();
                @endphp
                @if($waLinkFiche)
                <a href="{{ $waLinkFiche }}" target="_blank" class="btn-outline text-xs py-1.5 px-3 text-green-600 border-green-200 hover:bg-green-50 whitespace-nowrap" title="Partager la fiche par WhatsApp">
                    💬 <span class="hidden sm:inline">WhatsApp</span>
                </a>
                @endif
                <x-dropdown-actions>
                    @if($credit->paiements->isEmpty())
                    <a href="{{ route('dashboard.credits.edit', $credit) }}"
                       class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Modifier
                    </a>
                    @endif

                    @if($credit->statut !== 'solde')
                    <button type="button" onclick="document.getElementById('modal-reechelonner').showModal()"
                       class="w-full text-left flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                        <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Réaménager l'échéancier
                    </button>
                    @endif

                    @if($credit->statut !== 'solde' && $nbEcheancesRetard > 0)
                    <div class="border-t border-gray-100 dark:border-slate-700 my-1"></div>
                    @if($waLinkRelance)
                    <a href="{{ $waLinkRelance }}" target="_blank"
                       class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        Relancer par WhatsApp
                        <span class="ml-auto text-[10px] font-bold text-red-600 dark:text-red-400">{{ $nbEcheancesRetard }} en retard</span>
                    </a>
                    @endif
                    @if($credit->client?->email)
                    <form method="POST" action="{{ route('dashboard.credits.relancer', $credit) }}">
                        @csrf
                        <input type="hidden" name="canal" value="email">
                        <button type="submit"
                           class="w-full text-left flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Relancer par e-mail
                        </button>
                    </form>
                    @endif
                    @endif

                    @if($credit->paiements->isEmpty())
                    <div class="border-t border-gray-100 dark:border-slate-700 my-1"></div>
                    <button onclick="openModal('modal-supprimer-credit')"
                       class="w-full text-left flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Supprimer
                    </button>
                    @endif
                </x-dropdown-actions>
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

        @if(session('recu_credit'))
        <div class="rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400 mb-1">Reçu de paiement</p>
                    <pre class="text-xs whitespace-pre-wrap font-mono text-gray-700 dark:text-slate-300 leading-relaxed">{{ session('recu_credit') }}</pre>
                </div>
                <div class="flex flex-col gap-2 flex-shrink-0">
                    @if(session('recu_whatsapp'))
                    <a href="{{ session('recu_whatsapp') }}" target="_blank"
                       class="btn-outline text-xs py-1.5 px-3 text-green-600 border-green-200 hover:bg-green-50 whitespace-nowrap">
                        💬 Envoyer
                    </a>
                    @endif
                    <button type="button" onclick="navigator.clipboard.writeText(@js(session('recu_credit'))); this.textContent='Copié ✓'"
                            class="btn-outline text-xs py-1.5 px-3 whitespace-nowrap">Copier</button>
                </div>
            </div>
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
                @php $sc = $credit->client ? $creditService->scoreClient($credit->client) : null; @endphp
                @if($sc)
                <div class="flex items-center gap-2 mt-2">
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-bold
                        {{ $sc['couleur'] === 'success' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : '' }}
                        {{ $sc['couleur'] === 'warning' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' : '' }}
                        {{ $sc['couleur'] === 'danger'  ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' : '' }}
                        {{ $sc['couleur'] === 'secondary' ? 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-slate-300' : '' }}">
                        Score {{ $sc['score'] !== null ? $sc['score'] . '/100' : '—' }} · {{ $sc['libelle'] }}
                    </span>
                </div>
                @if($sc['en_retard'] > 0)
                <p class="text-[11px] text-red-600 dark:text-red-400 mt-1">{{ $sc['en_retard'] }} échéance{{ $sc['en_retard'] > 1 ? 's' : '' }} en retard</p>
                @endif
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
            <h2 class="text-sm font-bold text-gray-900 dark:text-white mb-3">Articles vendus</h2>
            <div class="space-y-2">
                @foreach($credit->vente->items as $item)
                <div class="flex justify-between text-sm text-gray-700 dark:text-gray-300">
                    <span>{{ $item->nom_snapshot }} ×{{ $item->quantite }}</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($item->sous_total, 0, ',', ' ') }} F</span>
                </div>
                @endforeach
                <div class="flex justify-between text-sm font-bold pt-2 border-t border-gray-200 dark:border-slate-700 text-gray-900 dark:text-white">
                    <span>Total</span>
                    <span>{{ number_format($credit->montant_total, 0, ',', ' ') }} F</span>
                </div>
            </div>
        </div>

        {{-- Échéancier --}}
        <div class="card overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 dark:border-slate-700">
                <h2 class="text-sm font-bold text-gray-900 dark:text-white">Échéancier</h2>
            </div>
            <div class="overflow-x-auto -webkit-overflow-scrolling:touch">
            <table class="w-full text-sm min-w-[500px]">
                <thead class="bg-gray-50 dark:bg-slate-800">
                    <tr>
                        <th class="px-3 sm:px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">N°</th>
                        <th class="px-3 sm:px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">Date prévue</th>
                        <th class="px-3 sm:px-4 py-2 text-right text-xs font-semibold text-gray-500 dark:text-gray-400">Montant</th>
                        <th class="px-3 sm:px-4 py-2 text-right text-xs font-semibold text-gray-500 dark:text-gray-400">Payé</th>
                        <th class="px-3 sm:px-4 py-2 text-center text-xs font-semibold text-gray-500 dark:text-gray-400">Statut</th>
                        <th class="px-3 sm:px-4 py-2 text-right text-xs font-semibold text-gray-500 dark:text-gray-400">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                    @foreach($credit->echeances as $echeance)
                    <tr class="{{ $echeance->statut === 'retard' ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                        <td class="px-3 sm:px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $echeance->numero }}/{{ $credit->nb_echeances }}</td>
                        <td class="px-3 sm:px-4 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300 {{ $echeance->statut === 'retard' ? 'text-red-600 dark:text-red-400 font-semibold' : '' }}">
                            {{ \Carbon\Carbon::parse($echeance->date_prevue)->format('d/m/Y') }}
                        </td>
                        <td class="px-3 sm:px-4 py-3 text-right font-mono text-xs whitespace-nowrap text-gray-900 dark:text-white">{{ number_format($echeance->montant, 0, ',', ' ') }} F</td>
                        <td class="px-3 sm:px-4 py-3 text-right font-mono text-xs text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
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
                            <button onclick="document.getElementById('reporter-{{ $echeance->id }}').showModal()"
                                    class="ml-2 text-xs font-semibold text-amber-600 hover:text-amber-700 whitespace-nowrap"
                                    title="Reporter cette échéance">
                                Reporter
                            </button>

                            {{-- Dialogue de report d'échéance --}}
                            <dialog id="reporter-{{ $echeance->id }}" class="rounded-2xl shadow-2xl border-0 p-0 w-full max-w-xs backdrop:bg-black/50 dark:backdrop:bg-black/70 bg-white dark:bg-slate-800 text-gray-900 dark:text-slate-100">
                                <form method="POST" action="{{ route('dashboard.credits.echeances.reporter', [$credit, $echeance]) }}" class="p-5 space-y-4">
                                    @csrf
                                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Reporter l'échéance {{ $echeance->numero }}</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Initialement prévue le {{ \Carbon\Carbon::parse($echeance->date_prevue)->format('d/m/Y') }}.</p>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Nouvelle date</label>
                                        <input type="date" name="date_prevue" required
                                               min="{{ now()->addDay()->format('Y-m-d') }}"
                                               value="{{ \Carbon\Carbon::parse($echeance->date_prevue)->max(now()->addDay())->format('Y-m-d') }}"
                                               class="w-full form-input text-sm">
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="button" onclick="this.closest('dialog').close()" class="btn-outline flex-1 text-sm">Annuler</button>
                                        <button type="submit" class="btn-primary flex-1 text-sm">Reporter</button>
                                    </div>
                                </form>
                            </dialog>

                            {{-- Dialogue de paiement --}}
                            <dialog id="payer-{{ $echeance->id }}" class="rounded-2xl shadow-2xl border-0 p-0 w-full max-w-sm backdrop:bg-black/50 dark:backdrop:bg-black/70 bg-white dark:bg-slate-800 text-gray-900 dark:text-slate-100">
                                <form method="POST" action="{{ route('dashboard.credits.payer', $credit) }}" class="p-6 space-y-5" x-data="{ mode: 'cash' }">
                                    @csrf
                                    <input type="hidden" name="echeance_id" value="{{ $echeance->id }}">

                                    {{-- En-tête --}}
                                    <div class="text-center">
                                        <div class="w-12 h-12 rounded-xl bg-purple-100 dark:bg-purple-900/40 flex items-center justify-center mx-auto mb-3">
                                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Encaisser échéance</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                            {{ $echeance->numero }}/{{ $credit->nb_echeances }}
                                            · <span class="font-semibold text-purple-600 dark:text-purple-400">{{ number_format($echeance->montant - $echeance->montant_paye, 0, ',', ' ') }} FCFA</span> restants
                                        </p>
                                    </div>

                                    {{-- Montant --}}
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Montant à encaisser</label>
                                        <div class="relative">
                                            <input type="number" name="montant" value="{{ $echeance->montant - $echeance->montant_paye }}"
                                                   min="1" max="{{ $echeance->montant - $echeance->montant_paye }}"
                                                   class="w-full form-input text-sm pr-12 font-mono" required>
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-400 dark:text-gray-500">FCFA</span>
                                        </div>
                                    </div>

                                    {{-- Mode de paiement — boutons toggle --}}
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Mode de paiement</label>
                                        <div class="grid grid-cols-3 gap-1.5 p-1 bg-gray-100 dark:bg-slate-700 rounded-xl">
                                            <label @click="mode = 'cash'" :class="mode === 'cash' ? 'bg-white dark:bg-slate-600 shadow-sm text-gray-900 dark:text-white font-semibold' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'" class="flex items-center justify-center gap-1.5 px-2 py-2 rounded-lg text-xs cursor-pointer transition">
                                                <input type="radio" name="mode_paiement" value="cash" x-model="mode" class="sr-only">
                                                <span>💵</span> Espèces
                                            </label>
                                            <label @click="mode = 'mobile_money'" :class="mode === 'mobile_money' ? 'bg-white dark:bg-slate-600 shadow-sm text-gray-900 dark:text-white font-semibold' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'" class="flex items-center justify-center gap-1.5 px-2 py-2 rounded-lg text-xs cursor-pointer transition">
                                                <input type="radio" name="mode_paiement" value="mobile_money" x-model="mode" class="sr-only">
                                                <span>📱</span> Mobile
                                            </label>
                                            <label @click="mode = 'carte'" :class="mode === 'carte' ? 'bg-white dark:bg-slate-600 shadow-sm text-gray-900 dark:text-white font-semibold' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'" class="flex items-center justify-center gap-1.5 px-2 py-2 rounded-lg text-xs cursor-pointer transition">
                                                <input type="radio" name="mode_paiement" value="carte" x-model="mode" class="sr-only">
                                                <span>💳</span> Carte
                                            </label>
                                        </div>
                                    </div>

                                    {{-- Référence --}}
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Référence <span class="font-normal normal-case text-gray-400">(optionnel)</span></label>
                                        <input type="text" name="reference" placeholder="Ex: N° transaction..."
                                               class="w-full form-input text-sm">
                                    </div>

                                    {{-- Actions --}}
                                    <div class="flex gap-3 pt-2">
                                        <button type="button" onclick="this.closest('dialog').close()"
                                                class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-600 transition">
                                            Annuler
                                        </button>
                                        <button type="submit"
                                                class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-purple-600 hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600 shadow-sm transition">
                                            Encaisser
                                        </button>
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
            <div class="px-5 py-3 border-b border-gray-100 dark:border-slate-700">
                <h2 class="text-sm font-bold text-gray-900 dark:text-white">Historique des paiements</h2>
            </div>
            <div class="overflow-x-auto -webkit-overflow-scrolling:touch">
            <table class="w-full text-sm min-w-[400px]">
                <thead class="bg-gray-50 dark:bg-slate-800">
                    <tr>
                        <th class="px-3 sm:px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">Date</th>
                        <th class="px-3 sm:px-4 py-2 text-right text-xs font-semibold text-gray-500 dark:text-gray-400">Montant</th>
                        <th class="px-3 sm:px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">Mode</th>
                        <th class="px-3 sm:px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">Encaissé par</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                    @foreach($credit->paiements as $p)
                    <tr>
                        <td class="px-3 sm:px-4 py-2 text-xs whitespace-nowrap text-gray-600 dark:text-gray-300">{{ \Carbon\Carbon::parse($p->created_at)->format('d/m/Y H:i') }}</td>
                        <td class="px-3 sm:px-4 py-2 text-right font-mono text-xs font-semibold whitespace-nowrap text-gray-900 dark:text-white">{{ number_format($p->montant, 0, ',', ' ') }} F</td>
                        <td class="px-3 sm:px-4 py-2 text-xs whitespace-nowrap text-gray-600 dark:text-gray-300">
                            @if($p->mode_paiement === 'cash') 💵 Espèces
                            @elseif($p->mode_paiement === 'mobile_money') 📱 Mobile
                            @else 💳 Carte
                            @endif
                        </td>
                        <td class="px-3 sm:px-4 py-2 text-xs whitespace-nowrap text-gray-500 dark:text-gray-400">{{ $p->encaisseur?->prenom ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
        @endif
    </div>

    <x-modal-confirm id="modal-supprimer-credit" title="Supprimer ce crédit ?"
        message="Le crédit de <strong>{{ $credit->client?->nom_complet ?? '—' }}</strong> d'un montant de <strong>{{ number_format($credit->montant_total, 0, ',', ' ') }} F</strong> sera définitivement supprimé. Cette action est <strong>irréversible</strong>."
        action="{{ route('dashboard.credits.destroy', $credit) }}" method="DELETE" confirm="Supprimer" danger="true" />

    @if($credit->statut !== 'solde')
    @php
        $nbReglees   = $credit->echeances->where('statut', 'payee')->count();
        $nbPartielles = $credit->echeances->filter(fn ($e) => $e->statut !== 'payee' && (int) $e->montant_paye > 0)->count();
    @endphp
    <dialog id="modal-reechelonner" class="rounded-2xl shadow-2xl border-0 p-0 w-full max-w-sm backdrop:bg-black/50 dark:backdrop:bg-black/70 bg-white dark:bg-slate-800 text-gray-900 dark:text-slate-100">
        <form method="POST" action="{{ route('dashboard.credits.reechelonner', $credit) }}" class="p-6 space-y-4">
            @csrf
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Réaménager l'échéancier</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Reste à payer : <strong class="text-gray-900 dark:text-white">{{ number_format($credit->reste_a_payer, 0, ',', ' ') }} F</strong>.
                    Ce montant sera redistribué sur les nouvelles échéances.
                </p>
            </div>

            @if($nbReglees > 0 || $nbPartielles > 0)
            <div class="rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/40 p-3">
                <p class="text-[11px] text-amber-800 dark:text-amber-300 leading-relaxed">
                    @if($nbReglees > 0)
                        <strong>{{ $nbReglees }}</strong> échéance{{ $nbReglees > 1 ? 's' : '' }} déjà réglée{{ $nbReglees > 1 ? 's' : '' }} {{ $nbReglees > 1 ? 'sont conservées' : 'est conservée' }} intacts{{ $nbReglees > 1 ? 'es' : 'e' }} (historique et paiements inchangés).
                    @endif
                    @if($nbPartielles > 0)
                        <br><strong>{{ $nbPartielles }}</strong> échéance{{ $nbPartielles > 1 ? 's' : '' }} partiellement réglée{{ $nbPartielles > 1 ? 's' : '' }} {{ $nbPartielles > 1 ? 'restent' : 'reste' }} en place, avec {{ $nbPartielles > 1 ? 'leurs' : 'son' }} encaissement{{ $nbPartielles > 1 ? 's' : '' }}.
                    @endif
                </p>
            </div>
            @endif

            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Nombre de nouvelles échéances</label>
                <input type="number" name="nb_echeances" min="1" max="36" value="{{ max(1, $credit->echeances->where('statut', '!=', 'payee')->where('montant_paye', '<=', 0)->count()) }}" required class="w-full form-input text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Fréquence</label>
                <select name="frequence" class="w-full form-input text-sm">
                    <option value="mensuelle" selected>Chaque mois</option>
                    <option value="hebdomadaire">Chaque semaine</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Première échéance</label>
                <input type="date" name="date_debut" value="{{ now()->format('Y-m-d') }}" min="{{ now()->format('Y-m-d') }}" class="w-full form-input text-sm">
            </div>
            <div class="flex gap-2 pt-1">
                <button type="button" onclick="this.closest('dialog').close()" class="btn-outline flex-1 text-sm">Annuler</button>
                <button type="submit" class="btn-primary flex-1 text-sm">Réaménager</button>
            </div>
        </form>
    </dialog>
    @endif

    <script>function openModal(id){document.getElementById(id).classList.remove('hidden');}</script>
</x-dashboard-layout>
