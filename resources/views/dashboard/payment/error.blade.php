<x-dashboard-layout>
<div class="max-w-2xl mx-auto py-12 px-4 text-center space-y-6">
    <div class="w-20 h-20 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mx-auto">
        <svg class="w-10 h-10 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </div>
    <div>
        <h1 class="text-2xl font-display font-bold text-gray-900 dark:text-white">Paiement non abouti</h1>
        <p class="text-gray-500 dark:text-slate-400 mt-2">Le paiement a été annulé ou a échoué. Aucun montant n'a été prélevé.</p>
    </div>
    <div class="card p-4 text-sm text-gray-600 dark:text-slate-300">
        Référence : <span class="font-mono font-semibold">{{ $transaction->reference }}</span>
    </div>
    <div class="flex gap-3 justify-center">
        <a href="{{ route('abonnement.plans') }}" class="btn-primary">Réessayer</a>
        <a href="{{ route('dashboard.index') }}" class="btn-ghost">Retour</a>
    </div>
</div>
</x-dashboard-layout>
