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
</x-dashboard-layout>
