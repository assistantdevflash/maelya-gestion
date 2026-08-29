@extends('layouts.admin')
@section('page-title', 'Transaction ' . $paymentTransaction->reference)

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.payment-transactions.index') }}" class="text-gray-400 hover:text-gray-700 text-sm">← Transactions</a>
            <div class="h-5 w-px bg-gray-200 dark:bg-slate-700"></div>
            <h1 class="page-title !mb-0">Transaction {{ $paymentTransaction->reference }}</h1>
        </div>
    </div>

    @if(session('success'))
    <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
    <div class="alert-error">{{ session('error') }}</div>
    @endif

    <div class="grid lg:grid-cols-3 gap-6">

        {{-- Détails transaction --}}
        <div class="lg:col-span-2 card p-6 space-y-4">
            <h2 class="font-bold text-lg text-gray-900 dark:text-white">Détails de la transaction</h2>

            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-400">Référence Maëlya</span>
                    <p class="font-mono font-medium">{{ $paymentTransaction->reference }}</p>
                </div>
                <div>
                    <span class="text-gray-400">Référence Gateway</span>
                    <p class="font-mono font-medium">{{ $paymentTransaction->gateway_reference ?? '—' }}</p>
                </div>
                <div>
                    <span class="text-gray-400">Statut</span>
                    <p>
                        @php
                            $colors = [
                                'completed' => 'bg-emerald-100 text-emerald-800',
                                'refunded'  => 'bg-indigo-100 text-indigo-800',
                                'pending'   => 'bg-amber-100 text-amber-800',
                                'processing'=> 'bg-amber-100 text-amber-800',
                                'failed'    => 'bg-red-100 text-red-800',
                                'cancelled' => 'bg-gray-100 text-gray-600',
                                'expired'   => 'bg-gray-100 text-gray-600',
                            ];
                            $labels = [
                                'completed' => 'Complété', 'refunded' => 'Remboursé',
                                'pending' => 'En attente', 'processing' => 'En traitement',
                                'failed' => 'Échoué', 'cancelled' => 'Annulé', 'expired' => 'Expiré',
                            ];
                        @endphp
                        <span class="badge {{ $colors[$paymentTransaction->status] ?? 'bg-gray-100 text-gray-600' }} text-xs">
                            {{ $labels[$paymentTransaction->status] ?? $paymentTransaction->status }}
                        </span>
                    </p>
                </div>
                <div>
                    <span class="text-gray-400">Type</span>
                    <p class="font-medium capitalize">{{ str_replace('_', ' ', $paymentTransaction->type) }}</p>
                </div>
                <div>
                    <span class="text-gray-400">Méthode</span>
                    <p class="font-medium">{{ $paymentTransaction->payment_method_code === 'geniuspay' ? 'GeniusPay' : 'Transfert bancaire' }}</p>
                </div>
                <div>
                    <span class="text-gray-400">Devise</span>
                    <p class="font-medium">{{ $paymentTransaction->currency }}</p>
                </div>
                <div>
                    <span class="text-gray-400">Montant</span>
                    <p class="font-bold text-lg">{{ number_format($paymentTransaction->amount, 0, ',', ' ') }} FCFA</p>
                </div>
                <div>
                    <span class="text-gray-400">Net reçu</span>
                    <p class="font-medium">
                        {{ number_format($paymentTransaction->net_amount, 0, ',', ' ') }} FCFA
                        @if($paymentTransaction->fees > 0)
                        <span class="block text-xs text-gray-400">dont {{ number_format($paymentTransaction->fees, 0, ',', ' ') }} F de frais</span>
                        @endif
                    </p>
                </div>
                @if($paymentTransaction->refunded_amount > 0)
                <div>
                    <span class="text-gray-400">Montant remboursé</span>
                    <p class="font-medium text-indigo-600">{{ number_format($paymentTransaction->refunded_amount, 0, ',', ' ') }} FCFA</p>
                </div>
                @endif
                <div>
                    <span class="text-gray-400">Créé le</span>
                    <p class="font-medium">{{ $paymentTransaction->created_at->format('d/m/Y H:i') }}</p>
                </div>
                @if($paymentTransaction->paid_at)
                <div>
                    <span class="text-gray-400">Payé le</span>
                    <p class="font-medium">{{ $paymentTransaction->paid_at->format('d/m/Y H:i') }}</p>
                </div>
                @endif
                @if($paymentTransaction->refunded_at)
                <div>
                    <span class="text-gray-400">Remboursé le</span>
                    <p class="font-medium">{{ $paymentTransaction->refunded_at->format('d/m/Y H:i') }}</p>
                </div>
                @endif
                @if($paymentTransaction->refund_reference)
                <div>
                    <span class="text-gray-400">Réf. remboursement</span>
                    <p class="font-mono font-medium">{{ $paymentTransaction->refund_reference }}</p>
                </div>
                @endif
            </div>

            {{-- Abonnement lié --}}
            @if($paymentTransaction->abonnement)
            <div class="border-t border-gray-100 dark:border-slate-700 pt-4">
                <span class="text-gray-400 text-xs font-semibold uppercase tracking-wider">Abonnement lié</span>
                <div class="mt-2 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 dark:text-purple-400 text-sm">📦</div>
                    <div>
                        <p class="font-medium">{{ $paymentTransaction->abonnement->plan?->nom ?? 'Plan inconnu' }}
                            <span class="text-gray-400 text-xs">· {{ ucfirst($paymentTransaction->abonnement->periode) }}</span>
                        </p>
                        <p class="text-xs text-gray-400">
                            Statut : <span class="capitalize">{{ str_replace('_', ' ', $paymentTransaction->abonnement->statut) }}</span>
                        </p>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Sidebar : client + actions --}}
        <div class="space-y-6">
            <div class="card p-6 space-y-3">
                <h2 class="font-bold text-lg text-gray-900 dark:text-white">Client</h2>
                @if($paymentTransaction->user)
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold">
                        {{ strtoupper(substr($paymentTransaction->user->name ?? '?', 0, 2)) }}
                    </div>
                    <div>
                        <p class="font-medium">{{ $paymentTransaction->user->name }}</p>
                        <p class="text-xs text-gray-500">{{ $paymentTransaction->user->email }}</p>
                    </div>
                </div>
                @else
                <p class="text-sm text-gray-400">Utilisateur supprimé.</p>
                @endif
            </div>

            {{-- Actions --}}
            @if($paymentTransaction->status === 'completed')
            <div class="card p-6 space-y-4">
                <h2 class="font-bold text-gray-900 dark:text-white">Actions</h2>
                <p class="text-xs text-gray-500 dark:text-slate-400">Rembourser la totalité de cette transaction ({{ number_format($paymentTransaction->amount, 0, ',', ' ') }} FCFA).</p>
                <form method="POST" action="{{ route('admin.payment-transactions.refund', $paymentTransaction) }}"
                      onsubmit="return confirm('Confirmer le remboursement de {{ number_format($paymentTransaction->amount, 0, ',', ' ') }} FCFA ? Le service associé sera désactivé.')">
                    @csrf
                    <input type="text" name="reason" placeholder="Motif du remboursement (optionnel)" class="form-input text-sm mb-3">
                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white font-semibold py-2.5 px-4 rounded-lg text-sm transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                        Rembourser
                    </button>
                </form>
            </div>
            @endif

            @if($paymentTransaction->status === 'refunded')
            <div class="card p-6">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">↩</div>
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white">Transaction remboursée</p>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ number_format($paymentTransaction->refunded_amount, 0, ',', ' ') }} FCFA
                            @if($paymentTransaction->refunded_at) le {{ $paymentTransaction->refunded_at->format('d/m/Y H:i') }} @endif
                        </p>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Réponse gateway brute --}}
    @if($paymentTransaction->gateway_response)
    <div class="card p-6">
        <details>
            <summary class="cursor-pointer font-semibold text-sm text-gray-600 dark:text-slate-300">🔎 Réponse complète du gateway</summary>
            <pre class="mt-3 text-xs bg-gray-50 dark:bg-slate-800 rounded-lg p-4 overflow-x-auto text-gray-600 dark:text-slate-300">{{ json_encode(json_decode($paymentTransaction->gateway_response, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </details>
    </div>
    @endif

</div>
@endsection
