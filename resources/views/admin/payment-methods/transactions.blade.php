@extends('layouts.admin')
@section('page-title', 'Transactions')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="page-title">Transactions de paiement</h1>
            <p class="page-subtitle">Historique complet des paiements GeniusPay et transferts bancaires</p>
        </div>
        <a href="{{ route('admin.payment-methods.index') }}" class="btn-outline text-sm">
            ← Retour aux moyens de paiement
        </a>
    </div>

    @if(session('success'))
    <div class="alert-success">{{ session('success') }}</div>
    @endif

    {{-- Filtres --}}
    <div class="card p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-semibold text-gray-700 dark:text-slate-200 mb-2">Statut</label>
                <select name="status" class="form-input text-sm">
                    <option value="">Tous les statuts</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Complété</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                    <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>En traitement</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Échoué</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Annulé</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expiré</option>
                    <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Remboursé</option>
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-semibold text-gray-700 dark:text-slate-200 mb-2">Type</label>
                <select name="type" class="form-input text-sm">
                    <option value="">Tous les types</option>
                    <option value="abonnement" {{ request('type') === 'abonnement' ? 'selected' : '' }}>Abonnement</option>
                    <option value="renouvellement" {{ request('type') === 'renouvellement' ? 'selected' : '' }}>Renouvellement</option>
                    <option value="upgrade" {{ request('type') === 'upgrade' ? 'selected' : '' }}>Upgrade</option>
                    <option value="boutique_activation" {{ request('type') === 'boutique_activation' ? 'selected' : '' }}>Boutique activation</option>
                    <option value="boutique_renouvellement" {{ request('type') === 'boutique_renouvellement' ? 'selected' : '' }}>Boutique renouvellement</option>
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-semibold text-gray-700 dark:text-slate-200 mb-2">Méthode</label>
                <select name="method" class="form-input text-sm">
                    <option value="">Toutes les méthodes</option>
                    <option value="geniuspay" {{ request('method') === 'geniuspay' ? 'selected' : '' }}>GeniusPay</option>
                    <option value="bank_transfer" {{ request('method') === 'bank_transfer' ? 'selected' : '' }}>Transfert bancaire</option>
                </select>
            </div>
            <button type="submit" class="btn-primary text-sm">Filtrer</button>
            @if(request()->hasAny(['status', 'type', 'method']))
            <a href="{{ route('admin.payment-transactions.index') }}" class="btn-ghost text-sm">Réinitialiser</a>
            @endif
        </form>
    </div>

    {{-- Tableau des transactions --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-slate-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Référence</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Client</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Méthode</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Montant</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Statut</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    @forelse($transactions as $tx)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/30 transition-colors">
                        <td class="px-4 py-3">
                            <div class="font-mono text-xs text-gray-900 dark:text-white font-semibold">{{ $tx->reference }}</div>
                            @if($tx->gateway_reference)
                            <div class="font-mono text-[10px] text-gray-400 dark:text-slate-500 mt-0.5">{{ $tx->gateway_reference }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900 dark:text-white">{{ $tx->user?->name }}</div>
                            <div class="text-xs text-gray-500 dark:text-slate-400">{{ $tx->user?->email }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-gray-700 dark:text-slate-300">{{ str_replace('_', ' ', ucfirst($tx->type)) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @if($tx->payment_method_code === 'geniuspay')
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                                    <svg class="w-3 h-3 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                </div>
                                <span class="text-gray-700 dark:text-slate-300">GeniusPay</span>
                            </div>
                            @else
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded bg-gray-100 dark:bg-slate-700 flex items-center justify-center">
                                    <svg class="w-3 h-3 text-gray-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                                </div>
                                <span class="text-gray-700 dark:text-slate-300">Transfert</span>
                            </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="font-semibold text-gray-900 dark:text-white">{{ number_format($tx->amount, 0, ',', ' ') }} F</div>
                            @if($tx->fees > 0)
                            <div class="text-xs text-gray-500 dark:text-slate-400">Frais: {{ number_format($tx->fees, 0, ',', ' ') }} F</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold
                                {{ $tx->status === 'completed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400' :
                                   ($tx->status === 'refunded' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400' :
                                   ($tx->status === 'pending' || $tx->status === 'processing' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400' :
                                   'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400')) }}">
                                @if($tx->status === 'completed') ✓ @endif
                                @if($tx->status === 'refunded') ↩ @endif
                                {{ $tx->status === 'refunded' ? 'Remboursé' : ucfirst($tx->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                            <div>{{ $tx->created_at->format('d/m/Y') }}</div>
                            <div class="text-xs text-gray-400 dark:text-slate-500">{{ $tx->created_at->format('H:i') }}</div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.payment-transactions.show', $tx) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-purple-600 dark:text-purple-400 hover:text-purple-800">
                                Voir
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-gray-500 dark:text-slate-400">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="font-medium">Aucune transaction trouvée</p>
                            <p class="text-sm mt-1">Les paiements apparaîtront ici une fois effectués.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($transactions->hasPages())
    <div class="flex justify-center">
        {{ $transactions->links() }}
    </div>
    @endif

</div>
@endsection
