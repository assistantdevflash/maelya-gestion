<x-dashboard-layout>
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-display font-bold text-gray-900 tracking-tight">Rapport financier</h1>
            <p class="text-sm text-gray-500 mt-1">Du {{ \Carbon\Carbon::parse($debut)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($fin)->format('d/m/Y') }}</p>
        </div>
        <a href="{{ route('dashboard.finances.index') }}" class="btn-secondary text-sm">← Retour</a>
    </div>

    {{-- Filtres --}}
    <form method="GET" action="{{ route('dashboard.finances.rapport') }}" class="flex flex-wrap items-end gap-3"
          x-data="{ debut: '{{ $debut }}' }">
        <div>
            <label class="form-label">Du</label>
            <input type="date" name="debut" x-model="debut" value="{{ $debut }}" class="form-input">
        </div>
        <div>
            <label class="form-label">Au</label>
            <input type="date" name="fin" :min="debut" value="{{ $fin }}" class="form-input">
        </div>
        <button type="submit" class="btn-primary">Générer</button>
    </form>

    {{-- Télécharger PDF --}}
    <a href="{{ route('dashboard.finances.export-pdf', ['debut' => $debut, 'fin' => $fin]) }}" class="btn-primary inline-flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        Télécharger PDF
    </a>

    {{-- Résumé --}}
    <div class="grid sm:grid-cols-3 gap-4">
        <div class="card p-5">
            <p class="text-xs text-gray-400 uppercase tracking-wider">Revenus</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($revenus, 0, ',', ' ') }} F</p>
        </div>
        <div class="card p-5">
            <p class="text-xs text-gray-400 uppercase tracking-wider">Dépenses</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ number_format($totalDepenses, 0, ',', ' ') }} F</p>
        </div>
        <div class="card p-5">
            <p class="text-xs text-gray-400 uppercase tracking-wider">Bénéfice net</p>
            <p class="text-2xl font-bold {{ $benefice >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1">{{ number_format($benefice, 0, ',', ' ') }} F</p>
        </div>
    </div>

    {{-- Ventes --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-slate-700 font-medium text-sm text-gray-900 dark:text-white">Ventes ({{ $ventes->count() }})</div>
        <table class="table-auto">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Articles</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ventes as $vente)
                <tr>
                    <td class="text-sm text-gray-500">{{ $vente->created_at->format('d/m/Y H:i') }}</td>
                    <td class="text-sm">{{ $vente->client?->prenom }} {{ $vente->client?->nom }}</td>
                    <td class="text-sm text-gray-500">{{ $vente->items->count() }} article(s)</td>
                    <td class="text-sm font-medium text-right">{{ number_format($vente->total, 0, ',', ' ') }} F</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center py-6 text-gray-400">Aucune vente sur cette période.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Dépenses --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-slate-700 font-medium text-sm text-gray-900 dark:text-white">Dépenses ({{ $depenses->count() }})</div>
        <table class="table-auto">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Libellé</th>
                    <th>Catégorie</th>
                    <th class="text-right">Montant</th>
                </tr>
            </thead>
            <tbody>
                @forelse($depenses as $depense)
                <tr>
                    <td class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($depense->date)->format('d/m/Y') }}</td>
                    <td class="text-sm">{{ $depense->description }}</td>
                    <td class="text-sm text-gray-500 capitalize">{{ str_replace('_', ' ', $depense->categorie) }}</td>
                    <td class="text-sm font-medium text-right">{{ number_format($depense->montant, 0, ',', ' ') }} F</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center py-6 text-gray-400">Aucune dépense sur cette période.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
</x-dashboard-layout>
