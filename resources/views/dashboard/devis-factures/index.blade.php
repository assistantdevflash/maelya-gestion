<x-dashboard-layout>
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-3xl font-display font-bold text-gray-900 dark:text-white tracking-tight">Devis & Factures</h1>
            <p class="text-gray-500 dark:text-slate-400 mt-1">Gérez vos devis et factures</p>
        </div>
        <a href="{{ $tab === 'devis' ? route('dashboard.devis.create') : route('dashboard.factures.create') }}"
           class="btn-primary text-sm self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            {{ $tab === 'devis' ? 'Nouveau devis' : 'Nouvelle facture' }}
        </a>
    </div>

    @if(session('success'))
    <div class="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/40 rounded-xl p-4 text-emerald-800 dark:text-emerald-200 text-sm">{{ session('success') }}</div>
    @endif

    {{-- Onglets --}}
    @php $tab = $tab ?? 'devis'; @endphp
    <div class="flex gap-1 bg-gray-100 dark:bg-slate-800 rounded-xl p-1 w-fit">
        <a href="{{ route('dashboard.devis.index') }}"
           class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ $tab === 'devis' ? 'bg-white dark:bg-slate-700 shadow-sm text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700' }}">
            📋 Devis
            @if(isset($devis)) <span class="ml-1.5 px-1.5 py-0.5 bg-gray-200 dark:bg-slate-600 rounded text-xs">{{ $devis->total() }}</span> @endif
        </a>
        <a href="{{ route('dashboard.factures.index') }}"
           class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ $tab === 'factures' ? 'bg-white dark:bg-slate-700 shadow-sm text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700' }}">
            🧾 Factures
            @if(isset($factures)) <span class="ml-1.5 px-1.5 py-0.5 bg-gray-200 dark:bg-slate-600 rounded text-xs">{{ $factures->total() }}</span> @endif
        </a>
    </div>

    @php
        $libellesDevis = ['brouillon' => 'Brouillon', 'envoye' => 'Envoyé', 'accepte' => 'Accepté', 'refuse' => 'Refusé', 'expire' => 'Expiré'];
        $libellesFactures = ['en_attente' => 'En attente', 'partiellement_payee' => 'Partiel', 'payee' => 'Payée', 'annulee' => 'Annulée'];
        $libelles = $tab === 'devis' ? $libellesDevis : $libellesFactures;
        $routeIndex = $tab === 'devis' ? 'dashboard.devis.index' : 'dashboard.factures.index';
        $filtreStatut = $statut ?? '';
        $filtreSearch = $search ?? '';
        $filtreRetard = $retard ?? false;
    @endphp

    {{-- ═══ FILTRES ═══ --}}
    <div class="card p-4">
        <form method="GET" action="{{ route($routeIndex) }}" class="flex flex-col gap-2 lg:flex-row lg:items-center">
            <div class="flex items-center gap-2 w-full lg:flex-1">
                <div class="relative flex-1">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="7" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="m16.5 16.5 4 4"/>
                    </svg>
                    <input type="text" name="search" id="filtre-search" value="{{ $filtreSearch }}"
                           placeholder="N°, client, téléphone…" class="form-input text-sm py-2 pl-9">
                </div>
                @if($filtreSearch !== '' || $filtreStatut !== '' || $filtreRetard)
                <a href="{{ route($routeIndex) }}" class="text-xs text-gray-400 hover:text-gray-600 flex-shrink-0">Réinitialiser</a>
                @endif
            </div>

            <div class="flex items-center gap-2">
                <select name="statut" onchange="this.form.submit()"
                        class="form-input text-sm py-2 pr-8 flex-1 lg:w-auto lg:min-w-[170px]">
                    <option value="">Tous les statuts</option>
                    @foreach($libelles as $valeur => $libelle)
                    <option value="{{ $valeur }}" {{ $filtreStatut === $valeur ? 'selected' : '' }}>{{ $libelle }}</option>
                    @endforeach
                </select>

                @if($tab === 'factures')
                <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 dark:border-slate-600 text-xs font-semibold text-gray-600 dark:text-gray-300 cursor-pointer whitespace-nowrap">
                    <input type="checkbox" name="retard" value="1" {{ $filtreRetard ? 'checked' : '' }} onchange="this.form.submit()" class="rounded">
                    En retard
                </label>
                @endif

                <button type="submit" class="btn-outline text-sm py-2 px-3 flex-shrink-0" title="Rechercher">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="7" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="m16.5 16.5 4 4"/>
                    </svg>
                </button>
            </div>
        </form>
    </div>

    @if($tab === 'devis')
        {{-- KPI Devis --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <a href="{{ route('dashboard.devis.index', ['statut' => 'envoye']) }}"
               class="card p-4 text-center hover:border-amber-300 dark:hover:border-amber-700 transition {{ ($statut ?? '') === 'envoye' ? 'ring-2 ring-amber-300 dark:ring-amber-700' : '' }}">
                <p class="text-xs text-gray-500">En cours</p><p class="text-2xl font-bold text-amber-600">{{ $stats['en_cours'] ?? 0 }}</p>
            </a>
            <div class="card p-4 text-center"><p class="text-xs text-gray-500">Montant en cours</p><p class="text-2xl font-bold text-primary-600">{{ number_format($stats['total_ttc'] ?? 0, 0, ',', ' ') }} F</p></div>
            <a href="{{ route('dashboard.devis.index', ['statut' => 'accepte']) }}"
               class="card p-4 text-center hover:border-emerald-300 dark:hover:border-emerald-700 transition {{ ($statut ?? '') === 'accepte' ? 'ring-2 ring-emerald-300 dark:ring-emerald-700' : '' }}">
                <p class="text-xs text-gray-500">Acceptés</p><p class="text-2xl font-bold text-emerald-600">{{ $stats['acceptes'] ?? 0 }}</p>
            </a>
            <a href="{{ route('dashboard.devis.index', ['statut' => 'expire']) }}"
               class="card p-4 text-center hover:border-gray-300 dark:hover:border-slate-500 transition {{ ($statut ?? '') === 'expire' ? 'ring-2 ring-gray-300 dark:ring-slate-500' : '' }}">
                <p class="text-xs text-gray-500">Expirés</p><p class="text-2xl font-bold text-gray-500">{{ $stats['expires'] ?? 0 }}</p>
            </a>
        </div>

        {{-- Liste --}}
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-slate-800"><tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">N°</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Client</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">Date</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Statut</th>
                    <th class="px-4 py-3"></th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    @forelse($devis as $d)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50 cursor-pointer" onclick="window.location='{{ route('dashboard.devis.show', ['devis' => $d->id]) }}'">
                        <td class="px-4 py-3">
                            <span class="font-mono text-xs font-semibold text-primary-600">{{ $d->numero }}</span>
                            @if($d->titre)
                                <p class="text-[11px] text-gray-400 dark:text-slate-500 truncate max-w-[180px]">{{ $d->titre }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $d->client_nom_complet ?: ($d->client->nom_complet ?? '—') }}</p>
                            @if($d->client_telephone)
                            <p class="text-[11px] text-gray-400 dark:text-slate-500">{{ $d->client_telephone }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 hidden sm:table-cell">
                            <p class="text-gray-500">{{ $d->date_creation->format('d/m/Y') }}</p>
                            <p class="text-[11px] {{ $d->date_expiration->isPast() && in_array($d->statut, ['brouillon','envoye']) ? 'text-red-500 font-semibold' : 'text-gray-400 dark:text-slate-500' }}">
                                Valide jusqu'au {{ $d->date_expiration->format('d/m/Y') }}
                            </p>
                        </td>
                        <td class="px-4 py-3 text-right font-bold">{{ number_format($d->total_ttc, 0, ',', ' ') }} F</td>
                        <td class="px-4 py-3 text-center">@include('dashboard.devis-factures.partials.statut-badge', ['statut' => $d->statut, 'type' => 'devis'])</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('dashboard.devis.pdf', ['devis' => $d->id]) }}" onclick="event.stopPropagation()" class="btn-icon" title="Télécharger le PDF">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            </a>
                            <a href="{{ route('dashboard.devis.show', ['devis' => $d->id]) }}" class="text-xs text-primary-600 hover:text-primary-700 font-medium ml-1">Voir</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">Aucun devis. <a href="{{ route('dashboard.devis.create') }}" class="text-primary-600 font-medium">Créer un devis</a></td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
            @if($devis->hasPages())<div class="px-4 py-3 border-t">{{ $devis->links() }}</div>@endif
        </div>

    @else
        {{-- KPI Factures --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="card p-4 text-center"><p class="text-xs text-gray-500">Total facturé</p><p class="text-2xl font-bold text-primary-600">{{ number_format($stats['total_ttc'] ?? 0, 0, ',', ' ') }} F</p></div>
            <div class="card p-4 text-center"><p class="text-xs text-gray-500">Encaissé</p><p class="text-2xl font-bold text-emerald-600">{{ number_format($stats['total_paye'] ?? 0, 0, ',', ' ') }} F</p></div>
            <a href="{{ route('dashboard.factures.index', ['retard' => 1]) }}"
               class="card p-4 text-center hover:border-red-300 dark:hover:border-red-800 transition {{ !empty($retard) ? 'ring-2 ring-red-300 dark:ring-red-800' : '' }}">
                <p class="text-xs text-gray-500">En retard</p><p class="text-2xl font-bold text-red-500">{{ $stats['en_retard'] ?? 0 }}</p>
            </a>
            <div class="card p-4 text-center">
                <p class="text-xs text-gray-500">Reste à encaisser</p>
                <p class="text-2xl font-bold text-amber-600">{{ number_format(max(0, ($stats['total_ttc'] ?? 0) - ($stats['total_paye'] ?? 0)), 0, ',', ' ') }} F</p>
            </div>
        </div>

        {{-- Liste --}}
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-slate-800"><tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">N°</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Client</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">Échéance</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Reste dû</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Statut</th>
                    <th class="px-4 py-3"></th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    @forelse($factures as $f)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50 cursor-pointer" onclick="window.location='{{ route('dashboard.factures.show', ['facture' => $f->id]) }}'">
                        <td class="px-4 py-3">
                            <span class="font-mono text-xs font-semibold text-primary-600">{{ $f->numero }}</span>
                            @if($f->titre)
                                <p class="text-[11px] text-gray-400 dark:text-slate-500 truncate max-w-[180px]">{{ $f->titre }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $f->client_nom_complet ?: ($f->client->nom_complet ?? '—') }}</p>
                            @if($f->client_telephone)
                            <p class="text-[11px] text-gray-400 dark:text-slate-500">{{ $f->client_telephone }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 hidden sm:table-cell">
                            <p class="{{ $f->estEnRetard ? 'text-red-500 font-semibold' : 'text-gray-500' }}">{{ $f->date_echeance->format('d/m/Y') }}</p>
                            @if($f->estEnRetard)
                            <span class="inline-flex items-center gap-1 mt-0.5 px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">
                                {{ \App\Helpers\TexteHelper::pluriel($f->jours_retard, 'jour', 'jours') }} de retard
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-bold">{{ number_format($f->total_ttc, 0, ',', ' ') }} F</td>
                        <td class="px-4 py-3 text-right hidden md:table-cell">
                            @if($f->statut === 'annulee')
                            <span class="text-gray-400">—</span>
                            @else
                            <p class="font-semibold {{ $f->resteAPayer > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                                {{ number_format($f->resteAPayer, 0, ',', ' ') }} F
                            </p>
                            <div class="mt-1 w-20 ml-auto bg-gray-200 dark:bg-slate-700 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full {{ $f->resteAPayer <= 0 ? 'bg-emerald-500' : 'bg-amber-500' }}"
                                     style="width: {{ $f->total_ttc > 0 ? min(100, round($f->montant_paye / $f->total_ttc * 100)) : 0 }}%"></div>
                            </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">@include('dashboard.devis-factures.partials.statut-badge', ['statut' => $f->statut, 'type' => 'facture'])</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('dashboard.factures.pdf', ['facture' => $f->id]) }}" onclick="event.stopPropagation()" class="btn-icon" title="Télécharger le PDF">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            </a>
                            <a href="{{ route('dashboard.factures.show', ['facture' => $f->id]) }}" class="text-xs text-primary-600 hover:text-primary-700 font-medium ml-1">Voir</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-12 text-center text-gray-400">Aucune facture. <a href="{{ route('dashboard.factures.create') }}" class="text-primary-600 font-medium">Créer une facture</a></td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
            @if($factures->hasPages())<div class="px-4 py-3 border-t">{{ $factures->links() }}</div>@endif
        </div>
    @endif
</div>

@push('scripts')
<script>
// Recherche instantanée : soumission automatique après une pause de frappe
(function () {
    const champ = document.getElementById('filtre-search');
    if (! champ) return;
    let minuteur;
    champ.addEventListener('input', () => {
        clearTimeout(minuteur);
        minuteur = setTimeout(() => champ.form.requestSubmit(), 450);
    });
})();
</script>
@endpush
</x-dashboard-layout>
