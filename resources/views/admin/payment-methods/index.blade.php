@extends('layouts.admin')
@section('page-title', 'Paiements')

@section('content')
<div class="max-w-6xl mx-auto space-y-8">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-display font-bold text-gray-900 dark:text-white">Moyens de paiement</h1>
            <p class="text-gray-500 dark:text-slate-400 mt-1">Activez ou désactivez les gateways disponibles pour vos clients.</p>
        </div>
        <a href="{{ route('admin.payment-transactions.index') }}" class="btn-outline text-sm">
            Voir toutes les transactions
        </a>
    </div>

    @if(session('success'))
    <div class="alert-success">{{ session('success') }}</div>
    @endif

    {{-- Statistiques --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="card p-4 text-center">
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['revenue'], 0, ',', ' ') }}</p>
            <p class="text-xs text-gray-500 mt-1">FCFA collectés</p>
        </div>
        <div class="card p-4 text-center">
            <p class="text-2xl font-bold text-emerald-600">{{ $stats['completed'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Complétés</p>
        </div>
        <div class="card p-4 text-center">
            <p class="text-2xl font-bold text-amber-600">{{ $stats['pending'] }}</p>
            <p class="text-xs text-gray-500 mt-1">En attente</p>
        </div>
        <div class="card p-4 text-center">
            <p class="text-2xl font-bold text-red-600">{{ $stats['failed'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Échoués</p>
        </div>
    </div>

    {{-- Gateways --}}
    <div class="card overflow-hidden">
        <div class="card-header">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Gateways configurés</h2>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-slate-700">
            @foreach($methods as $method)
            <div class="flex items-center gap-4 p-5">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0
                    {{ $method->type === 'gateway' ? 'bg-purple-100 dark:bg-purple-900/30' : 'bg-gray-100 dark:bg-slate-700' }}">
                    @if($method->code === 'geniuspay')
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    @else
                    <svg class="w-6 h-6 text-gray-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $method->name }}</p>
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                            {{ $method->type === 'gateway' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400' : 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-slate-300' }}">
                            {{ $method->type === 'gateway' ? 'Automatique' : 'Manuel' }}
                        </span>
                        @if($method->auto_validate)
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                            Activation auto
                        </span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500 dark:text-slate-400 mt-0.5">{{ $method->description }}</p>
                </div>
                <div class="flex items-center gap-3 flex-shrink-0">
                    <form action="{{ route('admin.payment-methods.toggle', $method) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none
                                {{ $method->is_active ? 'bg-primary-600' : 'bg-gray-200 dark:bg-slate-600' }}"
                            title="{{ $method->is_active ? 'Désactiver' : 'Activer' }}">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform
                                {{ $method->is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                        </button>
                    </form>
                    <span class="text-sm font-medium {{ $method->is_active ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-slate-500' }}">
                        {{ $method->is_active ? 'Actif' : 'Inactif' }}
                    </span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Transactions récentes --}}
    @if($recent->isNotEmpty())
    <div class="card overflow-hidden">
        <div class="card-header">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Transactions récentes</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-slate-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Référence</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Client</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Montant</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Statut</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    @foreach($recent as $tx)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs text-gray-600 dark:text-slate-300">{{ $tx->reference }}</td>
                        <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $tx->user?->name }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-slate-300">{{ str_replace('_', ' ', $tx->type) }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ number_format($tx->amount, 0, ',', ' ') }} F</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                {{ $tx->status === 'completed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400' :
                                   ($tx->status === 'pending' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400' :
                                   'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400') }}">
                                {{ $tx->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500 dark:text-slate-400 text-xs">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection
