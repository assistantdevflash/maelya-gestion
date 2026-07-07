<x-dashboard-layout>
<div class="space-y-8">
    {{-- Header --}}
    <div>
        <h1 class="text-3xl font-display font-bold text-gray-900 dark:text-white tracking-tight">Configuration boutique en ligne</h1>
        <p class="text-gray-500 dark:text-slate-400 mt-2">Paramétrez votre boutique et gérez son activation</p>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 dark:bg-emerald-950/40 border-2 border-emerald-200 dark:border-emerald-800/40 rounded-2xl p-5 flex items-start gap-4">
            <div class="w-10 h-10 bg-emerald-500 dark:bg-emerald-600 rounded-xl flex items-center justify-center text-white flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <p class="text-emerald-800 dark:text-emerald-200 font-medium pt-1.5">{{ session('success') }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('dashboard.boutique.config.update') }}" class="space-y-8">
        @csrf

        {{-- Activation --}}
        <div class="card">
            <div class="card-header">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Activation</h2>
                <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">Activez ou désactivez votre boutique en ligne</p>
            </div>
            <div class="card-body space-y-6">
                <label class="flex items-start gap-4 cursor-pointer group p-4 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors">
                    <input 
                        type="checkbox" 
                        name="boutique_active" 
                        value="1"
                        {{ $institut->boutique_active ? 'checked' : '' }}
                        class="mt-1 w-6 h-6 rounded-lg border-gray-300 dark:border-slate-600 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:bg-slate-900"
                    >
                    <div class="flex-1">
                        <span class="text-base font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">Activer la boutique en ligne</span>
                        <p class="text-sm text-gray-600 dark:text-slate-400 mt-1">Les clients pourront commander vos produits en ligne</p>
                    </div>
                </label>

                @if($institut->boutique_active)
                <div class="p-5 bg-gradient-to-br from-primary-50 to-secondary-50 dark:from-primary-950/30 dark:to-secondary-950/30 border-2 border-primary-200 dark:border-primary-800/40 rounded-2xl">
                    <div class="flex items-center gap-2 mb-3">
                        <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                        <p class="font-semibold text-primary-900 dark:text-primary-300">Lien de votre boutique</p>
                    </div>
                    <div class="flex gap-3">
                        <input 
                            type="text" 
                            value="{{ url('/shop/' . $institut->slug) }}" 
                            readonly
                            class="flex-1 px-4 py-3 bg-white dark:bg-slate-900 border-2 border-primary-200 dark:border-primary-700 rounded-xl text-sm text-gray-800 dark:text-slate-200 font-mono"
                        >
                        <button 
                            type="button"
                            onclick="navigator.clipboard.writeText('{{ url('/shop/' . $institut->slug) }}'); this.innerHTML='<svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M5 13l4 4L19 7\"/></svg> Copié'; setTimeout(() => this.innerHTML='Copier', 2000)"
                            class="btn-primary px-5"
                        >
                            Copier
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Livraison --}}
        <div class="card">
            <div class="card-header">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Livraison</h2>
                <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">Configurez les options de livraison</p>
            </div>
            <div class="card-body space-y-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-3">Frais de livraison (FCFA)</label>
                    <input 
                        type="number" 
                        name="boutique_frais_livraison" 
                        value="{{ old('boutique_frais_livraison', $institut->boutique_frais_livraison) }}"
                        placeholder="1500"
                        min="0"
                        step="100"
                        class="input w-full"
                    >
                    <p class="text-xs text-gray-500 dark:text-slate-400 mt-2">Mettez 0 pour une livraison gratuite</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-3">Délai de livraison</label>
                    <input 
                        type="text" 
                        name="boutique_delai_livraison" 
                        value="{{ old('boutique_delai_livraison', $institut->boutique_delai_livraison) }}"
                        placeholder="24h - 48h"
                        maxlength="100"
                        class="input w-full"
                    >
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-3">Zones de livraison (optionnel)</label>
                    <textarea 
                        name="boutique_zones_livraison" 
                        rows="4"
                        placeholder="Ex: Abidjan Sud, Abidjan Nord, Intérieur du pays"
                        class="input w-full"
                    >{{ old('boutique_zones_livraison', is_array($institut->boutique_zones_livraison) ? implode("\n", $institut->boutique_zones_livraison) : $institut->boutique_zones_livraison) }}</textarea>
                    <p class="text-xs text-gray-500 dark:text-slate-400 mt-2">Listez les quartiers ou zones que vous livrez</p>
                </div>
            </div>
        </div>

        {{-- Conditions --}}
        <div class="card">
            <div class="card-header">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Conditions de vente</h2>
                <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">Définissez vos conditions générales</p>
            </div>
            <div class="card-body">
                <label class="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-3">Conditions générales de vente</label>
                <textarea 
                    name="boutique_conditions" 
                    rows="6"
                    placeholder="Ex: Paiement à la livraison, Retour sous 7 jours..."
                    class="input w-full"
                >{{ old('boutique_conditions', $institut->boutique_conditions) }}</textarea>
                <p class="text-xs text-gray-500 dark:text-slate-400 mt-2">Ces conditions seront affichées sur votre boutique</p>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-slate-700">
            <a href="{{ route('dashboard.boutique.commandes.index') }}" class="btn-ghost">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Retour
            </a>
            <button type="submit" class="btn-primary btn-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Enregistrer les modifications
            </button>
        </div>
    </form>
</div>
</x-dashboard-layout>
