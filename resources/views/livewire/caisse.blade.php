@php $simple = ($mode ?? 'complet') === 'simple'; @endphp
<div
    x-data="caisseApp({
        prestations: @js($prestations),
        produits: @js($produits),
        catPrestations: @js($catPrestations),
        catProduits: @js($catProduits),
        allCatPrestations: @js($allCatPrestations),
        allCatProduits: @js($allCatProduits),
        prefilledItems: @js($this->prefilledItems),
        prefilledPanier: @js($this->prefilledPanier),
        routeBrouillonStore: @js(route('dashboard.caisse.brouillons.store')),
        mode: @js($mode ?? 'complet'),
        institutId: @js(session('current_institut_id', auth()->user()->institut_id)),
    })"
    class="grid lg:grid-cols-5 gap-4 lg:gap-5 min-h-0"
    data-caisse-pos
>
    @php $hasCredits = auth()->user()->aFonctionnalite('credits'); @endphp
    {{-- Succès vente crédit --}}
    @if($hasCredits && $creditSuccess)
    <div class="lg:col-span-5 flex items-center gap-3 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-sm text-emerald-700">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ $creditSuccess }}
        <a href="{{ route('dashboard.credits.index') }}" class="ml-auto text-xs font-semibold text-emerald-600 hover:underline">Voir les crédits →</a>
    </div>
    @endif

    @include('livewire.caisse.partials.catalogue')
    @include('livewire.caisse.partials.panier')
    @include('livewire.caisse.partials.modal-confirmation')
    @include('livewire.caisse.partials.calculatrice')
    @include('livewire.caisse.partials.modales')
</div>
