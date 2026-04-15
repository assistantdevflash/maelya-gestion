<x-dashboard-layout>
    <div class="max-w-4xl mx-auto space-y-6 py-4">

        {{-- En-tête --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Historique des abonnements</h1>
                <p class="text-sm text-gray-500 mt-1">Retrouvez toutes vos demandes d'abonnement et leur statut.</p>
            </div>
            <a href="{{ route('abonnement.plans') }}" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nouvel abonnement
            </a>
        </div>

        @if($abonnements->isEmpty())
            <div class="card p-12 text-center">
                <div class="w-14 h-14 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <p class="text-gray-500 font-medium">Aucun abonnement pour le moment</p>
                <p class="text-sm text-gray-400 mt-1">Choisissez un plan pour commencer.</p>
                <a href="{{ route('abonnement.plans') }}" class="btn-primary mt-4 inline-flex">Voir les plans</a>
            </div>
        @else
            <div class="space-y-3">
                @foreach($abonnements as $abo)
                    <div class="card p-5 hover:shadow-md transition-shadow">
                        <div class="flex items-start justify-between gap-4">
                            {{-- Infos principales --}}
                            <div class="flex items-start gap-4 min-w-0">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0
                                    @switch($abo->statut)
                                        @case('actif') bg-emerald-100 text-emerald-600 @break
                                        @case('en_attente') bg-amber-100 text-amber-600 @break
                                        @case('rejete') bg-red-100 text-red-600 @break
                                        @case('expire') bg-gray-100 text-gray-500 @break
                                        @default bg-gray-100 text-gray-500
                                    @endswitch">
                                    @switch($abo->statut)
                                        @case('actif')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            @break
                                        @case('en_attente')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            @break
                                        @case('rejete')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            @break
                                        @default
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    @endswitch
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="font-semibold text-gray-900">{{ $abo->plan->nom }}</h3>
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full
                                            @switch($abo->statut)
                                                @case('actif') bg-emerald-100 text-emerald-700 @break
                                                @case('en_attente') bg-amber-100 text-amber-700 @break
                                                @case('rejete') bg-red-100 text-red-700 @break
                                                @case('expire') bg-gray-100 text-gray-600 @break
                                                @default bg-gray-100 text-gray-600
                                            @endswitch">
                                            @switch($abo->statut)
                                                @case('actif') Actif @break
                                                @case('en_attente') En attente @break
                                                @case('rejete') Rejeté @break
                                                @case('expire') Expiré @break
                                                @default {{ ucfirst($abo->statut) }}
                                            @endswitch
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-3 mt-1 text-sm text-gray-500 flex-wrap">
                                        <span>{{ number_format($abo->montant, 0, ',', ' ') }} FCFA</span>
                                        <span class="text-gray-300">·</span>
                                        <span>
                                            @switch($abo->periode)
                                                @case('mensuel') Mensuel @break
                                                @case('annuel') 1 an @break
                                                @case('triennal') 3 ans @break
                                                @default {{ ucfirst($abo->periode) }}
                                            @endswitch
                                        </span>
                                        <span class="text-gray-300">·</span>
                                        <span>Demandé le {{ $abo->created_at->format('d/m/Y') }}</span>
                                    </div>

                                    {{-- Dates si actif ou expiré --}}
                                    @if($abo->debut_le && $abo->expire_le)
                                        <p class="text-xs text-gray-400 mt-1">
                                            Du {{ $abo->debut_le->format('d/m/Y') }} au {{ $abo->expire_le->format('d/m/Y') }}
                                            @if($abo->isActif())
                                                — <span class="text-emerald-600 font-medium">{{ $abo->joursRestants() }} jours restants</span>
                                            @endif
                                        </p>
                                    @endif

                                    {{-- Référence --}}
                                    @if($abo->reference_transfert)
                                        <p class="text-xs text-gray-400 mt-1">Réf. : <span class="font-mono">{{ $abo->reference_transfert }}</span></p>
                                    @endif

                                    {{-- Raison rejet --}}
                                    @if($abo->statut === 'rejete' && $abo->notes_admin)
                                        <div class="mt-2 flex items-start gap-2 bg-red-50 rounded-lg p-2.5">
                                            <svg class="w-4 h-4 text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <p class="text-xs text-red-700">{{ $abo->notes_admin }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Preuve --}}
                            @if($abo->preuve_paiement)
                                <a href="{{ asset('storage/' . $abo->preuve_paiement) }}" target="_blank"
                                   class="flex-shrink-0 p-2 text-gray-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors" title="Voir la preuve">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($abonnements->hasPages())
                <div class="mt-4">
                    {{ $abonnements->links() }}
                </div>
            @endif
        @endif
    </div>
</x-dashboard-layout>
