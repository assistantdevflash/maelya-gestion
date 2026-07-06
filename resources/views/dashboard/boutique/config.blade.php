@extends('layouts.dashboard')

@section('title', 'Configuration boutique')

@section('content')
<div class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Configuration de la boutique en ligne</h1>

    @if(session('success'))
        <div class="mb-6 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 p-4 rounded">
            <p class="text-green-700 dark:text-green-400">{{ session('success') }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('dashboard.boutique.config.update') }}" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 space-y-6">
        @csrf

        {{-- Activation --}}
        <div>
            <label class="flex items-center gap-3 cursor-pointer">
                <input 
                    type="checkbox" 
                    name="boutique_active" 
                    value="1"
                    {{ $institut->boutique_active ? 'checked' : '' }}
                    class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500"
                >
                <div>
                    <span class="font-medium text-gray-900 dark:text-white">Activer la boutique en ligne</span>
                    <p class="text-sm text-gray-500">Les clients pourront commander vos produits en ligne</p>
                </div>
            </label>
        </div>

        {{-- URL de la boutique --}}
        @if($institut->boutique_active)
            <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg p-4">
                <p class="text-sm font-medium text-indigo-900 dark:text-indigo-300 mb-2">Lien de votre boutique :</p>
                <div class="flex items-center gap-2">
                    <input 
                        type="text" 
                        value="{{ route('shop.index', $institut->slug) }}" 
                        readonly
                        class="flex-1 px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-sm"
                    >
                    <button 
                        type="button"
                        onclick="navigator.clipboard.writeText('{{ route('shop.index', $institut->slug) }}'); alert('Lien copié !')"
                        class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700"
                    >
                        Copier
                    </button>
                </div>
            </div>
        @endif

        {{-- Frais de livraison --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Frais de livraison (FCFA)
            </label>
            <input 
                type="number" 
                name="boutique_frais_livraison" 
                value="{{ old('boutique_frais_livraison', $institut->boutique_frais_livraison ?? 0) }}"
                min="0"
                step="100"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700"
            >
            <p class="text-sm text-gray-500 mt-1">Mettez 0 pour une livraison gratuite</p>
        </div>

        {{-- Délai de livraison --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Délai de livraison
            </label>
            <input 
                type="text" 
                name="boutique_delai_livraison" 
                value="{{ old('boutique_delai_livraison', $institut->boutique_delai_livraison) }}"
                placeholder="Ex: 24h, 2-3 jours..."
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700"
            >
        </div>

        {{-- Zones de livraison --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Zones de livraison (optionnel)
            </label>
            <textarea 
                name="boutique_zones_livraison[]" 
                rows="3"
                placeholder="Une zone par ligne..."
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700"
            >{{ old('boutique_zones_livraison', is_array($institut->boutique_zones_livraison) ? implode("\n", $institut->boutique_zones_livraison) : '') }}</textarea>
            <p class="text-sm text-gray-500 mt-1">Listez les quartiers ou zones où vous livrez</p>
        </div>

        {{-- Conditions --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Conditions de vente (optionnel)
            </label>
            <textarea 
                name="boutique_conditions" 
                rows="4"
                placeholder="Vos conditions de vente..."
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700"
            >{{ old('boutique_conditions', $institut->boutique_conditions) }}</textarea>
        </div>

        {{-- Bouton enregistrer --}}
        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
            <button 
                type="submit"
                class="px-6 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700"
            >
                Enregistrer les modifications
            </button>
        </div>
    </form>
</div>
@endsection
