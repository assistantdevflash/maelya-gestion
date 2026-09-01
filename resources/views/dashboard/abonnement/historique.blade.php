<x-dashboard-layout>
    <div class="max-w-4xl mx-auto space-y-6 py-4">

        {{-- En-tête --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Historique des transactions</h1>
                <p class="text-sm text-gray-500 mt-1">Retrouvez toutes vos demandes d'abonnement, vos paiements et leurs statuts.</p>
            </div>
            <a href="{{ route('abonnement.plans') }}" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nouvel abonnement
            </a>
        </div>

        @if($transactions->isEmpty())
            <div class="card p-12 text-center">
                <div class="w-14 h-14 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <p class="text-gray-500 font-medium">Aucune transaction pour le moment</p>
                <p class="text-sm text-gray-400 mt-1">Choisissez un plan pour commencer.</p>
                <a href="{{ route('abonnement.plans') }}" class="btn-primary mt-4 inline-flex">Voir les plans</a>
            </div>
        @else
            <div class="space-y-3">
                @foreach($transactions as $item)
                    @php
                        $iconBg = match($item['statut']) {
                            'actif', 'paye' => 'bg-emerald-100 text-emerald-600',
                            'en_attente' => 'bg-amber-100 text-amber-600',
                            'rejete', 'echoue' => 'bg-red-100 text-red-600',
                            'rembourse' => 'bg-indigo-100 text-indigo-600',
                            default => 'bg-gray-100 text-gray-500',
                        };
                        $badgeCls = match($item['statut']) {
                            'actif', 'paye' => 'bg-emerald-100 text-emerald-700',
                            'en_attente' => 'bg-amber-100 text-amber-700',
                            'rejete', 'echoue' => 'bg-red-100 text-red-700',
                            'rembourse' => 'bg-indigo-100 text-indigo-700',
                            default => 'bg-gray-100 text-gray-600',
                        };
                    @endphp
                    <div class="card p-5 hover:shadow-md transition-shadow">
                        <div class="flex items-start justify-between gap-4">
                            {{-- Infos principales --}}
                            <div class="flex items-start gap-4 min-w-0">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 {{ $iconBg }}">
                                    @if($item['is_boutique'])
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                    @else
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="font-semibold text-gray-900">{{ $item['titre'] }}</h3>
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full {{ $badgeCls }}">
                                            {{ $item['statut_label'] }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-gray-400">{{ $item['type_label'] }}</div>
                                    <div class="flex items-center gap-3 mt-1 text-sm text-gray-500 flex-wrap">
                                        <span>{{ number_format($item['montant'], 0, ',', ' ') }} FCFA</span>
                                        <span class="text-gray-300">·</span>
                                        <span>{{ $item['periode_label'] }}</span>
                                        <span class="text-gray-300">·</span>
                                        <span>Le {{ $item['date']->format('d/m/Y') }}</span>
                                    </div>

                                    {{-- Établissement concerné (option boutique) --}}
                                    @if($item['institut_nom'])
                                        <p class="text-xs text-gray-400 mt-1">Établissement : <span class="font-medium">{{ $item['institut_nom'] }}</span></p>
                                    @endif

                                    {{-- Dates si présentes --}}
                                    @if($item['debut_le'] && $item['expire_le'])
                                        <p class="text-xs text-gray-400 mt-1">
                                            Du {{ $item['debut_le']->format('d/m/Y') }} au {{ $item['expire_le']->format('d/m/Y') }}
                                            @if($item['jours_restants'] !== null)
                                                — <span class="text-emerald-600 font-medium">{{ $item['jours_restants'] }} jours restants</span>
                                            @endif
                                        </p>
                                    @elseif($item['expire_le'])
                                        <p class="text-xs text-gray-400 mt-1">Valable jusqu'au {{ $item['expire_le']->format('d/m/Y') }}</p>
                                    @endif

                                    {{-- Référence --}}
                                    @if($item['reference'])
                                        <p class="text-xs text-gray-400 mt-1">Réf. : <span class="font-mono">{{ $item['reference'] }}</span></p>
                                    @endif

                                    {{-- Méthode de paiement --}}
                                    <p class="text-xs text-gray-400 mt-1">Méthode : {{ $item['methode'] }}</p>

                                    {{-- Raison rejet --}}
                                    @if($item['statut'] === 'rejete' && $item['notes_admin'])
                                        <div class="mt-2 flex items-start gap-2 bg-red-50 rounded-lg p-2.5">
                                            <svg class="w-4 h-4 text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <p class="text-xs text-red-700">{{ $item['notes_admin'] }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Action : télécharger la facture --}}
                            <div class="flex-shrink-0 flex flex-col items-end gap-2">
                                <a href="{{ $item['facture_url'] }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-primary-700 bg-primary-50 hover:bg-primary-100 rounded-lg transition-colors" title="Télécharger la facture">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2"/></svg>
                                    Facture
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($transactions->hasPages())
                <div class="mt-4">
                    {{ $transactions->links() }}
                </div>
            @endif
        @endif
    </div>
</x-dashboard-layout>
