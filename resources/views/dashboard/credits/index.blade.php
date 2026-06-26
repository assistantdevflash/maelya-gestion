<x-dashboard-layout>
    <x-slot name="title">Crédits clients</x-slot>

    <div class="space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-display font-bold text-gray-900 tracking-tight">Crédits clients</h1>
                <p class="text-sm text-gray-500 mt-1">{{ $credits->total() }} crédit(s)</p>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="card p-4">
                <p class="text-xs text-gray-500">Total crédits</p>
                <p class="text-2xl font-bold text-gray-900">{{ $totaux->total ?? 0 }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs text-gray-500">En cours</p>
                <p class="text-2xl font-bold text-blue-600">{{ $totaux->en_cours ?? 0 }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs text-gray-500">En retard</p>
                <p class="text-2xl font-bold text-red-600">{{ $totaux->en_retard ?? 0 }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs text-gray-500">Total dû</p>
                <p class="text-2xl font-bold text-amber-600">{{ number_format($totaux->total_du ?? 0, 0, ',', ' ') }} F</p>
            </div>
        </div>

        {{-- Filtres --}}
        <div class="card p-4">
            <div class="flex flex-wrap items-end gap-3">
                {{-- Boutons de filtre statut --}}
                <div class="flex gap-1">
                    @foreach(['tous' => 'Tous', 'en_cours' => 'En cours', 'retard' => 'En retard', 'solde' => 'Soldés'] as $val => $label)
                    <a href="{{ route('dashboard.credits.index', ['statut' => $val, 'q' => request('q')]) }}"
                       class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $filtre === $val ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        {{ $label }}
                    </a>
                    @endforeach
                </div>
                {{-- Recherche --}}
                <form method="GET" action="{{ route('dashboard.credits.index') }}" class="flex flex-1 gap-2 min-w-[200px]">
                    @if($filtre !== 'tous')
                        <input type="hidden" name="statut" value="{{ $filtre }}">
                    @endif
                    <div class="relative flex-1">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="search" name="q" value="{{ request('q') }}"
                               placeholder="Nom, prénom ou téléphone..."
                               class="form-input pl-9">
                    </div>
                    <button type="submit" class="btn-outline">Rechercher</button>
                    @if(request()->hasAny(['q','statut']))
                        <a href="{{ route('dashboard.credits.index') }}" class="btn btn-ghost">Effacer</a>
                    @endif
                </form>
            </div>
        </div>

        {{-- Liste --}}
        @if($credits->count() > 0)
        <div class="card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Client</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 hidden sm:table-cell">Article(s)</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Total</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600 hidden md:table-cell">Payé</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Reste</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($credits as $credit)
                    <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('dashboard.credits.show', $credit) }}'">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-900">{{ $credit->client?->nom_complet ?? '—' }}</p>
                            <p class="text-xs text-gray-400">{{ $credit->client?->telephone ?? '' }}</p>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-600 hidden sm:table-cell">
                            {{ $credit->vente?->items->pluck('nom_snapshot')->implode(', ') ?: '—' }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-xs">{{ number_format($credit->montant_total, 0, ',', ' ') }} F</td>
                        <td class="px-4 py-3 text-right font-mono text-xs text-emerald-600 hidden md:table-cell">{{ number_format($credit->montant_total - $credit->reste_a_payer, 0, ',', ' ') }} F</td>
                        <td class="px-4 py-3 text-right font-bold {{ $credit->reste_a_payer > 0 ? 'text-red-600' : 'text-gray-400' }}">
                            {{ number_format($credit->reste_a_payer, 0, ',', ' ') }} F
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($credit->statut === 'solde')
                                <span class="badge badge-success text-xs">Soldé</span>
                            @elseif($credit->statut === 'retard')
                                <span class="badge badge-danger text-xs">🔴 Retard</span>
                            @else
                                <span class="badge badge-info text-xs">En cours</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $credits->links() }}
            </div>
        </div>
        @else
        <div class="card p-12 text-center">
            <div class="text-4xl mb-3">📋</div>
            <p class="font-semibold text-gray-900 mb-1">Aucun crédit</p>
            <p class="text-sm text-gray-500">Les ventes à crédit apparaîtront ici.</p>
        </div>
        @endif
    </div>
</x-dashboard-layout>
