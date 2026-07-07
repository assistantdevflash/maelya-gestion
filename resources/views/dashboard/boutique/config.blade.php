<x-dashboard-layout>
<div class="space-y-6">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-display font-bold text-gray-900 dark:text-white tracking-tight">Configuration boutique en ligne</h1>
        <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">Paramétrez votre boutique et gérez son activation</p>
    </div>

    @if(session('success'))
        <div class="card p-4 bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800/40 flex items-start gap-3">
            <div class="w-8 h-8 bg-emerald-100 dark:bg-emerald-900/40 rounded-lg flex items-center justify-center text-emerald-600 dark:text-emerald-400 flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <p class="text-emerald-700 dark:text-emerald-300 text-sm font-medium">{{ session('success') }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('dashboard.boutique.config.update') }}" class="space-y-6">
        @csrf

        {{-- Activation --}}
        <div class="card">
            <div class="card-header">
                <h2 class="font-semibold text-gray-900 dark:text-white">Activation</h2>
            </div>
            <div class="card-body space-y-4">
                <label class="flex items-start gap-3 cursor-pointer group">
                    <input 
                        type="checkbox" 
                        name="boutique_active" 
                        value="1"
                        {{ $institut->boutique_active ? 'checked' : '' }}
                        class="mt-1 w-5 h-5 rounded border-gray-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500 dark:bg-slate-800"
                    >
                    <div class="flex-1">
                        <span class="font-medium text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">Activer la boutique en ligne</span>
                        <p class="text-sm text-gray-500 dark:text-slate-400 mt-0.5">Les clients pourront commander vos produits en ligne</p>
                    </div>
                </label>

                @if($institut->boutique_active)
                <div class="p-4 bg-primary-50 dark:bg-primary-950/20 border border-primary-200 dark:border-primary-800/40 rounded-xl">
                    <p class="text-sm font-medium text-primary-900 dark:text-primary-300 mb-2">🔗 Lien de votre boutique :</p>
                    <div class="flex gap-2">
                        <input 
                            type="text" 
                            value="{{ url('/shop/' . $institut->slug) }}" 
                            readonly
                            class="flex-1 px-3 py-2 bg-white dark:bg-slate-900 border border-primary-200 dark:border-primary-800 rounded-lg text-sm text-gray-700 dark:text-slate-300 font-mono"
                        >
                        <button 
                            type="button"
                            onclick="navigator.clipboard.writeText('{{ url('/shop/' . $institut->slug) }}'); this.innerHTML='✓ Copié'; setTimeout(() => this.innerHTML='Copier', 2000)"
                            class="btn-primary btn-sm"
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
                <h2 class="font-semibold text-gray-900 dark:text-white">Livraison</h2>
            </div>
            <div class="card-body space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Frais de livraison (FCFA)</label>
                    <input 
                        type="number" 
                        name="boutique_frais_livraison" 
                        value="{{ old('boutique_frais_livraison', $institut->boutique_frais_livraison) }}"
                        placeholder="1500"
                        min="0"
                        step="100"
                        class="input w-full"
                    >
                    <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">Mettez 0 pour une livraison gratuite</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Délai de livraison</label>
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
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Zones de livraison (optionnel)</label>
                    <textarea 
                        name="boutique_zones_livraison" 
                        rows="3"
                        placeholder="Ex: Abidjan Sud, Abidjan Nord, Intérieur du pays"
                        class="input w-full"
                    >{{ old('boutique_zones_livraison', is_array($institut->boutique_zones_livraison) ? implode("\n", $institut->boutique_zones_livraison) : $institut->boutique_zones_livraison) }}</textarea>
                    <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">Listez les quartiers ou zones que vous livrez</p>
                </div>
            </div>
        </div>

        {{-- Conditions --}}
        <div class="card">
            <div class="card-header">
                <h2 class="font-semibold text-gray-900 dark:text-white">Conditions de vente</h2>
            </div>
            <div class="card-body">
                <textarea 
                    name="boutique_conditions" 
                    rows="5"
                    placeholder="Ex: Paiement à la livraison, Retour sous 7 jours..."
                    class="input w-full"
                >{{ old('boutique_conditions', $institut->boutique_conditions) }}</textarea>
                <p class="text-xs text-gray-500 dark:text-slate-400 mt-2">Ces conditions seront affichées sur votre boutique</p>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('dashboard.boutique.commandes.index') }}" class="btn-ghost">
                Annuler
            </a>
            <button type="submit" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Enregistrer
            </button>
        </div>
    </form>
</div>
</x-dashboard-layout>
