<x-dashboard-layout>
<div class="max-w-2xl mx-auto py-8 px-4 space-y-8">
    <div>
        <h1 class="text-2xl font-display font-bold text-gray-900 dark:text-white">Instructions de paiement</h1>
        <p class="text-gray-500 dark:text-slate-400 mt-1">Votre demande a été enregistrée. Effectuez le transfert ci-dessous pour l'activer.</p>
    </div>
    <div class="card p-6 space-y-4">
        <p class="font-semibold text-gray-900 dark:text-white">Montant à transférer : <span class="text-primary-600 dark:text-primary-400">{{ number_format($transaction->amount, 0, ',', ' ') }} FCFA</span></p>
        <p class="text-sm text-gray-600 dark:text-slate-300">Référence dossier : <span class="font-mono font-semibold">{{ $transaction->reference }}</span></p>
        <p class="text-sm text-gray-500 dark:text-slate-400">Contactez le support pour transmettre votre preuve de paiement une fois le transfert effectué.</p>
    </div>
    <a href="{{ route('abonnement.historique') }}" class="btn-primary">Voir mes demandes</a>
</div>
</x-dashboard-layout>
