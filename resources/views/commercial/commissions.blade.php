@extends('layouts.commercial')
@section('title', 'Mes commissions')

@section('content')
<div class="mb-6 flex items-center justify-between gap-4 flex-wrap">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Mes commissions</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Historique complet de vos gains</p>
    </div>
    {{-- Filtre statut --}}
    <form method="GET" class="flex items-center gap-2">
        <select name="statut" onchange="this.form.submit()"
                class="px-3 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-purple-400 focus:border-transparent">
            <option value="" {{ !request('statut') ? 'selected' : '' }}>Toutes</option>
            <option value="en_attente" {{ request('statut') === 'en_attente' ? 'selected' : '' }}>En attente</option>
            <option value="payee" {{ request('statut') === 'payee' ? 'selected' : '' }}>Payées</option>
        </select>
    </form>
</div>

{{-- Totaux --}}
<div class="grid grid-cols-2 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total reçu</p>
        <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ number_format($totalGagne, 0, ',', ' ') }} FCFA</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">À recevoir</p>
        <p class="text-xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($totalEnAttente, 0, ',', ' ') }} FCFA</p>
    </div>
</div>

{{-- Liste --}}
<div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
    @if($commissions->isEmpty())
    <div class="p-12 text-center">
        <p class="text-gray-500 dark:text-gray-400 text-sm">Aucune commission {{ request('statut') ? 'avec ce filtre' : 'pour l\'instant' }}.</p>
    </div>
    @else
    <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Institut</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden md:table-cell">Plan</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden md:table-cell">Base × taux</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Montant</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Statut</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach($commissions as $c)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                <td class="px-5 py-3">
                    <p class="font-medium text-gray-900 dark:text-white">{{ $c->parrainage?->proprietaire?->institut?->nom ?? '—' }}</p>
                    <p class="text-xs text-gray-400">{{ $c->created_at->format('d/m/Y') }}</p>
                </td>
                <td class="px-5 py-3 hidden md:table-cell text-gray-600 dark:text-gray-400">
                    {{ $c->abonnement?->plan?->nom ?? '—' }}
                </td>
                <td class="px-5 py-3 hidden md:table-cell text-gray-600 dark:text-gray-400">
                    {{ number_format($c->montant_base, 0, ',', ' ') }} × {{ $c->taux }}%
                </td>
                <td class="px-5 py-3 text-right font-bold {{ $c->statut === 'payee' ? 'text-green-600 dark:text-green-400' : 'text-purple-600 dark:text-purple-400' }}">
                    {{ $c->montant_formatte }}
                </td>
                <td class="px-5 py-3">
                    @if($c->statut === 'payee')
                    <span class="inline-flex px-2 py-0.5 rounded-md bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 text-[11px] font-semibold">Payée</span>
                    @else
                    <span class="inline-flex px-2 py-0.5 rounded-md bg-yellow-50 dark:bg-yellow-900/20 text-yellow-600 dark:text-yellow-400 text-[11px] font-semibold">En attente</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($commissions->hasPages())
    <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
        {{ $commissions->links() }}
    </div>
    @endif
    @endif
</div>
@endsection
