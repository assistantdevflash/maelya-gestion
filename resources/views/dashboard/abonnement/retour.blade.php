<x-dashboard-layout>
<div class="max-w-lg mx-auto py-12 text-center space-y-4">
    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto">
        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
    </div>
    <h1 class="text-2xl font-bold text-gray-900">Paiement confirmé</h1>
    <p class="text-gray-500">Votre abonnement a été activé avec succès. Vous pouvez maintenant utiliser toutes les fonctionnalités de votre plan.</p>
    <a href="{{ route('dashboard.index') }}" class="btn-primary inline-block mt-4">Retour au tableau de bord</a>
</div>
</x-dashboard-layout>
