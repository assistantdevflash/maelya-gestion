<x-dashboard-layout>
<div class="max-w-2xl mx-auto py-12 px-4">
    @if($transaction->isCompleted())
    <div class="text-center space-y-6">
        <div class="w-20 h-20 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center mx-auto">
            <svg class="w-10 h-10 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <div>
            <h1 class="text-2xl font-display font-bold text-gray-900 dark:text-white">Paiement confirmé !</h1>
            <p class="text-gray-500 dark:text-slate-400 mt-2">
                @if(in_array($transaction->type, ['abonnement', 'renouvellement', 'upgrade']))
                    Votre abonnement a été activé automatiquement.
                @else
                    Votre boutique en ligne est maintenant active.
                @endif
            </p>
        </div>
        <div class="card p-6 text-left space-y-3">
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Référence</span>
                <span class="font-semibold text-gray-900 dark:text-white font-mono">{{ $transaction->reference }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Montant payé</span>
                <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($transaction->amount, 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Date</span>
                <span class="font-semibold text-gray-900 dark:text-white">{{ $transaction->paid_at?->format('d/m/Y à H:i') }}</span>
            </div>
        </div>
        <div class="flex gap-3 justify-center">
            <a href="{{ route('dashboard.index') }}" class="btn-primary">
                Aller au tableau de bord
            </a>
            @if(in_array($transaction->type, ['boutique_activation', 'boutique_renouvellement']))
            <a href="{{ route('dashboard.boutique.config.index') }}" class="btn-outline">
                Configurer ma boutique
            </a>
            @endif
        </div>
    </div>
    @else
    {{-- Paiement en attente de confirmation --}}
    <div class="text-center space-y-6">
        <div class="w-20 h-20 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center mx-auto">
            <svg class="w-10 h-10 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <h1 class="text-2xl font-display font-bold text-gray-900 dark:text-white">Paiement en cours de vérification</h1>
            <p class="text-gray-500 dark:text-slate-400 mt-2">Votre paiement est en cours de traitement. Votre abonnement sera activé automatiquement dès la confirmation.</p>
        </div>
        <div class="card p-6">
            <p class="text-sm text-gray-600 dark:text-slate-300">Référence : <span class="font-mono font-semibold">{{ $transaction->reference }}</span></p>
        </div>
        <a href="{{ route('dashboard.index') }}" class="btn-primary">Retour au tableau de bord</a>
    </div>
    @endif
</div>
</x-dashboard-layout>
