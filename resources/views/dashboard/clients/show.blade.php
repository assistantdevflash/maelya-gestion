<x-dashboard-layout>
    <div class="space-y-5">
        {{-- Bannière anniversaire --}}
        @if($client->isAnniversaire())
        <x-banniere-anniversaire :clients="collect([$client])" />
        @endif

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard.clients.index') }}" class="btn-icon text-gray-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                    </svg>
                </a>
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-primary-400 to-secondary-400 rounded-full flex items-center justify-center text-white text-lg font-bold">
                        {{ strtoupper(substr($client->prenom, 0, 1)) }}{{ strtoupper(substr($client->nom, 0, 1)) }}
                    </div>
                    <div>
                        <h1 class="text-xl font-display font-bold text-gray-900">{{ $client->nom_complet }}</h1>
                        @if($client->date_naissance)
                            <p class="text-sm text-gray-500">{{ $client->naissance_formatee }}</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('dashboard.caisse') }}?client={{ $client->id }}" class="btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Nouvelle vente
                </a>
                <a href="{{ route('dashboard.clients.edit', $client) }}" class="btn-outline">Modifier</a>
            </div>
        </div>

        {{-- KPI -- }}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="stat-card text-center">
                <p class="text-2xl font-bold text-primary-600">{{ $client->nombre_visites }}</p>
                <p class="text-xs text-gray-500 mt-1">Visites</p>
            </div>
            <div class="stat-card text-center">
                <p class="text-2xl font-bold text-secondary-600">{{ number_format($client->total_depense, 0, ',', ' ') }}</p>
                <p class="text-xs text-gray-500 mt-1">FCFA dépensés</p>
            </div>
            <div class="stat-card text-center">
                <p class="text-sm font-semibold text-gray-900">{{ $client->derniere_visite?->diffForHumans() ?? 'Jamais' }}</p>
                <p class="text-xs text-gray-500 mt-1">Dernière visite</p>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-5">
            {{-- Infos client --}}
            <div class="card p-5">
                <h2 class="font-semibold text-gray-900 mb-4 text-sm">📋 Informations</h2>
                <div class="space-y-3 text-sm">
                    @if($client->telephone)
                    <div class="flex items-center gap-2 text-gray-600">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        {{ $client->telephone }}
                    </div>
                    @endif
                    @if($client->email)
                    <div class="flex items-center gap-2 text-gray-600">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        {{ $client->email }}
                    </div>
                    @endif
                    @if($client->notes)
                    <div class="text-gray-600 bg-gray-50 rounded-lg p-3 text-xs leading-relaxed">
                        {{ $client->notes }}
                    </div>
                    @endif
                </div>
            </div>

            {{-- Historique ventes --}}
            <div class="lg:col-span-2 card overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-900 text-sm">🛍️ Historique des achats</h2>
                </div>
                <div class="divide-y divide-gray-50 max-h-96 overflow-y-auto">
                    @forelse($client->ventes()->latest()->take(20)->get() as $vente)
                    <div class="px-5 py-3 flex items-center justify-between text-sm">
                        <div>
                            <p class="font-medium text-gray-900">{{ number_format($vente->total, 0, ',', ' ') }} FCFA</p>
                            <p class="text-xs text-gray-400">{{ $vente->created_at->format('d/m/Y à H:i') }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="badge {{ $vente->mode_paiement === 'mobile_money' ? 'badge-primary' : 'badge-secondary' }}">
                                {{ $vente->mode_paiement === 'mobile_money' ? 'Mobile' : 'Cash' }}
                            </span>
                            <a href="{{ route('dashboard.ventes.show', $vente) }}" class="btn-icon text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                    @empty
                    <div class="px-5 py-8 text-center text-sm text-gray-400">Aucun achat enregistré.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout>
