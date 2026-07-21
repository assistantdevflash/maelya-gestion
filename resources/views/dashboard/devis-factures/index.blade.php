<x-dashboard-layout>
<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-display font-bold text-gray-900 dark:text-white tracking-tight">Devis & Factures</h1>
        <p class="text-gray-500 dark:text-slate-400 mt-1">Gérez vos devis et factures</p>
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

    @if($tab === 'devis')
        {{-- KPI Devis --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="card p-4 text-center"><p class="text-xs text-gray-500">En cours</p><p class="text-2xl font-bold text-amber-600">{{ $stats['en_cours'] ?? 0 }}</p></div>
            <div class="card p-4 text-center"><p class="text-xs text-gray-500">Montant total</p><p class="text-2xl font-bold text-primary-600">{{ number_format($stats['total_ttc'] ?? 0, 0, ',', ' ') }} F</p></div>
            <div class="card p-4 text-center"><p class="text-xs text-gray-500">Acceptés</p><p class="text-2xl font-bold text-emerald-600">{{ $stats['acceptes'] ?? 0 }}</p></div>
            <div class="card p-4 flex items-center justify-center">
                <a href="{{ route('dashboard.devis.create') }}" class="btn-primary text-sm">+ Nouveau devis</a>
            </div>
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
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50">
                        <td class="px-4 py-3 font-mono text-xs font-semibold text-primary-600">{{ $d->numero }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $d->client_nom_complet ?: ($d->client->nom_complet ?? '—') }}</td>
                        <td class="px-4 py-3 text-gray-500 hidden sm:table-cell">{{ $d->date_creation->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-right font-bold">{{ number_format($d->total_ttc, 0, ',', ' ') }} F</td>
                        <td class="px-4 py-3 text-center">@include('dashboard.devis-factures.partials.statut-badge', ['statut' => $d->statut, 'type' => 'devis'])</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('dashboard.devis.show', $d->id) }}" class="text-xs text-primary-600 hover:text-primary-700 font-medium">Voir</a></td>
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
            <div class="card p-4 text-center"><p class="text-xs text-gray-500">En retard</p><p class="text-2xl font-bold text-red-500">{{ $stats['en_retard'] ?? 0 }}</p></div>
            <div class="card p-4 flex items-center justify-center">
                <a href="{{ route('dashboard.factures.create') }}" class="btn-primary text-sm">+ Nouvelle facture</a>
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
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">Payé</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Statut</th>
                    <th class="px-4 py-3"></th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    @forelse($factures as $f)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50">
                        <td class="px-4 py-3 font-mono text-xs font-semibold text-primary-600">{{ $f->numero }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $f->client_nom_complet ?: ($f->client->nom_complet ?? '—') }}</td>
                        <td class="px-4 py-3 text-gray-500 hidden sm:table-cell">{{ $f->date_echeance->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-right font-bold">{{ number_format($f->total_ttc, 0, ',', ' ') }} F</td>
                        <td class="px-4 py-3 text-right text-emerald-600 hidden sm:table-cell">{{ number_format($f->montant_paye, 0, ',', ' ') }} F</td>
                        <td class="px-4 py-3 text-center">@include('dashboard.devis-factures.partials.statut-badge', ['statut' => $f->statut, 'type' => 'facture'])</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('dashboard.factures.show', $f->id) }}" class="text-xs text-primary-600 hover:text-primary-700 font-medium">Voir</a></td>
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
</x-dashboard-layout>
