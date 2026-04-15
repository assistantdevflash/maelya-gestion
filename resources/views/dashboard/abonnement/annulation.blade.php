<x-dashboard-layout>
<div class="max-w-lg mx-auto py-12 text-center space-y-4">
    <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto">
        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 3a9 9 0 100 18 9 9 0 000-18z"/>
        </svg>
    </div>
    <h1 class="text-2xl font-bold text-gray-900">Paiement annulé</h1>
    <p class="text-gray-500">Le paiement a été annulé. Votre abonnement n'a pas été modifié.</p>
    <a href="{{ route('dashboard.abonnement') }}" class="btn-primary inline-block mt-4">Retour à l'abonnement</a>
</div>
</x-dashboard-layout>
