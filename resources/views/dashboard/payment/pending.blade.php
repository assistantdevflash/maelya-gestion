<x-dashboard-layout>
<div class="max-w-2xl mx-auto py-12 px-4 text-center space-y-6">
    <div class="w-20 h-20 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center mx-auto">
        <svg class="w-10 h-10 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
    </div>
    <h1 class="text-2xl font-display font-bold text-gray-900 dark:text-white">Paiement en attente</h1>
    <p class="text-gray-500 dark:text-slate-400">Votre paiement est en cours de traitement. Vous serez notifié dès la confirmation.</p>
    <div class="card p-4 text-sm">Référence : <span class="font-mono font-semibold">{{ $transaction->reference }}</span></div>
    <a href="{{ route('dashboard.index') }}" class="btn-primary">Retour au tableau de bord</a>
</div>
</x-dashboard-layout>
