<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commander — {{ $institut->nom }}</title>
    <meta name="description" content="Finalisez votre commande chez {{ $institut->nom }}">
    <meta property="og:title" content="Commander — {{ $institut->nom }}">
    <meta property="og:image" content="{{ $institut->logo ? asset('storage/' . $institut->logo) : '' }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-purple-50 dark:bg-slate-900 min-h-screen" x-data="checkout()">
<div class="max-w-lg mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('shop.index', $institut->slug) }}" class="p-2 text-gray-400 hover:text-gray-600 rounded-xl hover:bg-white dark:hover:bg-slate-800 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-lg font-bold text-gray-900 dark:text-white">Finaliser la commande</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $institut->nom }}</p>
        </div>
    </div>

    {{-- Panier vide --}}
    <div x-show="panier.length === 0" class="card p-6 text-center">
        <div class="w-16 h-16 bg-gray-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
        </div>
        <p class="text-gray-500 dark:text-gray-400 mb-4">Votre panier est vide.</p>
        <a href="{{ route('shop.index', $institut->slug) }}" class="btn-primary inline-block">Parcourir la boutique</a>
    </div>

    {{-- Formulaire --}}
    <div x-show="panier.length > 0">
        <form id="form-commande" method="POST" action="{{ route('shop.commander', $institut->slug) }}" class="space-y-5">
            @csrf

            {{-- Récapitulatif --}}
            <div class="card p-4 space-y-2">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Votre commande</p>
                <template x-for="item in panier">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-700 dark:text-slate-300" x-text="item.quantite + 'x ' + item.nom"></span>
                        <span class="font-medium text-gray-900 dark:text-white" x-text="new Intl.NumberFormat('fr-FR').format(item.prix * item.quantite) + ' F'"></span>
                    </div>
                </template>
                <div class="border-t border-gray-200 dark:border-slate-700 pt-2 mt-2 flex justify-between font-bold">
                    <span class="text-gray-900 dark:text-white">Total</span>
                    <span class="text-primary-600 dark:text-primary-400" x-text="new Intl.NumberFormat('fr-FR').format(total) + ' F'"></span>
                </div>
            </div>

            {{-- Formulaire client --}}
            <div class="card p-5 space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1.5">Prénom *</label>
                        <input type="text" name="prenom" required class="form-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1.5">Nom *</label>
                        <input type="text" name="nom" required class="form-input">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1.5">Téléphone *</label>
                    <input type="tel" name="telephone" required placeholder="07 XX XX XX XX" class="form-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1.5">Email (optionnel)</label>
                    <input type="email" name="email" class="form-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1.5">Adresse de livraison *</label>
                    <textarea name="adresse" rows="3" required placeholder="Quartier, commune, ville, point de repère..." class="form-textarea"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1.5">Notes (optionnel)</label>
                    <textarea name="notes" rows="2" placeholder="Instructions spéciales, horaires..." class="form-textarea"></textarea>
                </div>
            </div>

            {{-- Paiement --}}
            <div class="flex items-center gap-3 p-4 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-xl">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <div>
                    <p class="text-sm font-semibold text-emerald-900 dark:text-emerald-300">Paiement à la livraison</p>
                    <p class="text-xs text-emerald-700 dark:text-emerald-400">Cash à la réception</p>
                </div>
            </div>
            <input type="hidden" name="mode_paiement" value="cash">

            {{-- Items panier --}}
            <template x-for="(item, index) in panier" :key="index">
                <div>
                    <input type="hidden" :name="'panier[' + index + '][produit_id]'" :value="item.id">
                    <input type="hidden" :name="'panier[' + index + '][quantite]'" :value="item.quantite">
                </div>
            </template>

            {{-- Erreurs --}}
            @if(session('error') || $errors->any())
            <div class="flex items-start gap-3 p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800/40 rounded-xl text-sm text-red-700 dark:text-red-300">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div>
                    @if(session('error'))<p>{{ session('error') }}</p>@endif
                    @foreach($errors->all() as $e)<p>• {{ $e }}</p>@endforeach
                </div>
            </div>
            @endif

            <button type="submit" @click="handleSubmit($event)"
                    class="w-full py-4 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-bold text-lg shadow-lg transition-all flex items-center justify-center gap-3">
                <span x-show="!submitting">Confirmer la commande</span>
                <span x-show="submitting" class="flex items-center gap-2">
                    <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    Envoi en cours...
                </span>
            </button>
            <p class="text-center text-xs text-gray-400 dark:text-gray-500">{{ $institut->boutique_delai_livraison ? 'Livraison : ' . $institut->boutique_delai_livraison : 'Délai selon disponibilité' }}</p>
        </form>
    </div>
</div>

<script>
function checkout() {
    return {
        panier: [],
        submitting: false,

        init() {
            const raw = localStorage.getItem('maelya_panier');
            if (raw) {
                try { this.panier = JSON.parse(raw); } catch(e) {}
            }
        },

        get total() {
            return this.panier.reduce((sum, item) => sum + (item.prix * item.quantite), 0);
        },

        handleSubmit(event) {
            if (this.panier.length === 0) { event.preventDefault(); return; }
            if (this.submitting) { event.preventDefault(); return; }
            this.submitting = true;
            // Vider le panier immédiatement
            localStorage.removeItem('maelya_panier');
        }
    };
}
</script>
</body>
</html>
