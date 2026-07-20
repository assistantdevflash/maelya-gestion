@extends('boutique.layouts.app')

@section('title', 'Suivi commande ' . $commande->numero)

@section('content')
<div class="min-h-screen bg-purple-50 dark:bg-gray-900 py-12">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Commande {{ $commande->numero }}
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ $commande->created_at->format('d/m/Y à H:i') }}
                </p>
            </div>

            {{-- Statut --}}
            <div class="mb-8">
                <div class="flex items-center justify-center mb-4">
                    @php
                        $statutColors = [
                            'nouvelle' => 'bg-blue-100 text-blue-800',
                            'acceptee' => 'bg-green-100 text-green-800',
                            'en_preparation' => 'bg-yellow-100 text-yellow-800',
                            'en_livraison' => 'bg-indigo-100 text-indigo-800',
                            'livree' => 'bg-emerald-100 text-emerald-800',
                            'annulee' => 'bg-red-100 text-red-800',
                            'refusee' => 'bg-red-100 text-red-800',
                        ];
                        $statutLabels = [
                            'nouvelle' => 'Nouvelle',
                            'acceptee' => 'Acceptée',
                            'en_preparation' => 'En préparation',
                            'en_livraison' => 'En livraison',
                            'livree' => 'Livrée',
                            'annulee' => 'Annulée',
                            'refusee' => 'Refusée',
                        ];
                    @endphp
                    <span class="px-4 py-2 rounded-full text-sm font-semibold {{ $statutColors[$commande->statut] ?? 'bg-gray-100 text-gray-800' }}">
                        {{ $statutLabels[$commande->statut] ?? $commande->statut }}
                    </span>
                </div>

                {{-- Timeline --}}
                <div class="space-y-4">
                    @if($commande->created_at)
                        <div class="flex gap-3">
                            <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold">Commande passée</p>
                                <p class="text-sm text-gray-500">{{ $commande->created_at->format('d/m/Y à H:i') }}</p>
                            </div>
                        </div>
                    @endif

                    @if($commande->acceptee_at)
                        <div class="flex gap-3">
                            <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold">Acceptée</p>
                                <p class="text-sm text-gray-500">{{ $commande->acceptee_at->format('d/m/Y à H:i') }}</p>
                            </div>
                        </div>
                    @endif

                    @if($commande->en_livraison_at)
                        <div class="flex gap-3">
                            <div class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                    <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold">En livraison</p>
                                <p class="text-sm text-gray-500">{{ $commande->en_livraison_at->format('d/m/Y à H:i') }}</p>
                            </div>
                        </div>
                    @endif

                    @if($commande->livree_at)
                        <div class="flex gap-3">
                            <div class="w-8 h-8 rounded-full bg-emerald-500 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold">Livrée</p>
                                <p class="text-sm text-gray-500">{{ $commande->livree_at->format('d/m/Y à H:i') }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Produits --}}
            <div class="border-t dark:border-gray-700 pt-6 mb-6">
                <h2 class="font-semibold text-lg mb-4">Produits commandés</h2>
                <div class="space-y-3">
                    @foreach($commande->items as $item)
                        <div class="flex justify-between">
                            <div>
                                <p class="font-medium">{{ $item->nom_snapshot }}</p>
                                <p class="text-sm text-gray-500">Quantité : {{ $item->quantite }}</p>
                            </div>
                            <p class="font-semibold">{{ number_format($item->sous_total, 0, ',', ' ') }} FCFA</p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Totaux --}}
            <div class="border-t dark:border-gray-700 pt-6 space-y-2">
                <div class="flex justify-between">
                    <span>Sous-total</span>
                    <span>{{ number_format($commande->sous_total, 0, ',', ' ') }} FCFA</span>
                </div>
                <div class="flex justify-between">
                    <span>Frais de livraison</span>
                    <span>{{ number_format($commande->frais_livraison, 0, ',', ' ') }} FCFA</span>
                </div>
                <div class="flex justify-between text-lg font-bold">
                    <span>Total</span>
                    <span>{{ number_format($commande->total, 0, ',', ' ') }} FCFA</span>
                </div>
            </div>

            {{-- Adresse --}}
            <div class="border-t dark:border-gray-700 pt-6 mt-6">
                <h3 class="font-semibold mb-2">Adresse de livraison</h3>
                <p class="text-gray-600 dark:text-gray-400">{{ $commande->client_adresse }}</p>
            </div>

            <div class="mt-8 text-center">
                <a href="{{ route('shop.index', $institut->slug) }}" class="text-indigo-600 hover:text-indigo-700 font-medium">
                    ← Retour à la boutique
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
