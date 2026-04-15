<x-dashboard-layout>
    <div class="max-w-3xl mx-auto space-y-6">
        <div class="text-center py-10">
            <div class="w-20 h-20 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-5">
                <svg class="w-10 h-10 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-3">Abonnement expiré ou inactif</h1>
            <p class="text-gray-500 max-w-md mx-auto">
                Votre abonnement n'est plus actif. Renouvelez pour continuer à utiliser toutes les fonctionnalités de Maëlya Gestion.
            </p>
            <a href="{{ route('abonnement.plans') }}" class="btn-primary btn-lg mt-6 inline-flex items-center gap-2">
                Voir les plans d'abonnement
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
        </div>
    </div>
</x-dashboard-layout>
