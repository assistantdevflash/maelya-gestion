<x-dashboard-layout>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="page-title">Caisse</h1>
                <p class="page-subtitle">Enregistrez rapidement vos ventes.</p>
            </div>
            <a href="{{ route('dashboard.ventes.index') }}" class="btn-outline text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Historique
            </a>
        </div>

        {{-- Message de succès après impression --}}
        @if(request('vente_ok'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 x-init="setTimeout(() => show = false, 5000)"
                 class="flex items-center gap-3 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 rounded-xl px-4 py-3 text-sm font-medium">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Vente enregistrée et ticket envoyé à l'impression.
                <button @click="show = false" class="ml-auto text-emerald-400 hover:text-emerald-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        {{-- Composant Livewire --}}
        <div
            x-data="{}"
            @valider-vente.window="
                const data = $event.detail[0];
                const form = document.getElementById('form-vente');
                document.getElementById('panier-json').value = JSON.stringify(data.panier);
                document.getElementById('client-id').value = data.client_id ?? '';
                document.getElementById('mode-paiement').value = data.mode_paiement;
                document.getElementById('reference-paiement').value = data.reference_paiement ?? '';
                document.getElementById('total').value = data.total;
                document.getElementById('remise').value = data.remise ?? 0;
                document.getElementById('code-reduction-id').value = data.code_reduction_id ?? '';
                document.getElementById('imprimer').value = data.imprimer ? '1' : '';
                if (data.imprimer) {
                    form.target = '_blank';
                    form.submit();
                    setTimeout(() => { window.location.href = window.location.pathname + '?vente_ok=1'; }, 300);
                } else {
                    form.target = '_self';
                    form.submit();
                }
            "
        >
            @livewire('caisse', ['client' => request('client')])
        </div>

        {{-- Formulaire caché pour soumettre via POST classique --}}
        <form id="form-vente" method="POST" action="{{ route('dashboard.ventes.store') }}" class="hidden">
            @csrf
            <input type="hidden" id="panier-json" name="panier_json">
            <input type="hidden" id="client-id" name="client_id">
            <input type="hidden" id="mode-paiement" name="mode_paiement">
            <input type="hidden" id="reference-paiement" name="reference_paiement">
            <input type="hidden" id="total" name="total">
            <input type="hidden" id="remise" name="remise" value="0">
            <input type="hidden" id="code-reduction-id" name="code_reduction_id">
            <input type="hidden" id="imprimer" name="imprimer">
        </form>
    </div>
</x-dashboard-layout>
