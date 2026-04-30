<x-dashboard-layout>
    <div class="max-w-4xl mx-auto space-y-6 py-4">

        {{-- En-tête --}}
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Parrainage</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Partagez votre code et gagnez des jours d'abonnement gratuits.</p>
        </div>

        {{-- Bannière code suspendu --}}
        @if(!$parrainageActif)
        <div class="flex items-start gap-3 p-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700/40 text-sm text-amber-800 dark:text-amber-300">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <div class="flex-1">
                <p class="font-semibold">Votre code parrainage est temporairement suspendu</p>
                <p class="mt-0.5">Votre abonnement a expiré : les nouveaux filleuls ne peuvent pas utiliser votre code pendant cette période. Il sera automatiquement réactivé dès le renouvellement de votre abonnement.</p>
                <a href="{{ route('abonnement.plans') }}" class="inline-flex items-center gap-1 mt-2 text-xs font-semibold text-amber-700 dark:text-amber-400 hover:underline">
                    Renouveler mon abonnement →
                </a>
            </div>
        </div>
        @endif

        {{-- Carte code de parrainage --}}
        <div class="card p-6 {{ !$parrainageActif ? 'opacity-60' : '' }}" x-data="{ copied: false }">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">
                        Votre code de parrainage
                        @if(!$parrainageActif)
                            <span class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                Suspendu
                            </span>
                        @endif
                    </p>
                    <div class="flex items-center gap-3">
                        <span class="text-3xl font-bold font-mono tracking-[0.2em] {{ $parrainageActif ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400 dark:text-gray-500 line-through' }}">{{ $user->code_parrainage }}</span>
                        @if($parrainageActif)
                        <button @click="navigator.clipboard.writeText('{{ $user->code_parrainage }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                class="p-2 rounded-lg bg-gray-100 dark:bg-slate-700 hover:bg-primary-50 dark:hover:bg-primary-900/30 text-gray-500 hover:text-primary-600 transition-colors"
                                title="Copier le code">
                            <svg x-show="!copied" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            <svg x-show="copied" x-cloak class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </button>
                        @endif
                    </div>
                </div>

                {{-- Lien d'inscription --}}
                @if($parrainageActif)
                <div class="flex-1" x-data="{ linkCopied: false }">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Lien d'inscription avec votre code</p>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly
                               value="{{ url('/inscription?ref=' . $user->code_parrainage) }}"
                               class="form-input text-xs font-mono flex-1 bg-gray-50 dark:bg-slate-700 truncate">
                        <button @click="navigator.clipboard.writeText('{{ url('/inscription?ref=' . $user->code_parrainage) }}'); linkCopied = true; setTimeout(() => linkCopied = false, 2000)"
                                class="btn-outline btn-sm flex-shrink-0">
                            <span x-show="!linkCopied">Copier</span>
                            <span x-show="linkCopied" x-cloak class="text-emerald-600">Copié !</span>
                        </button>
                    </div>
                </div>
                @endif
            </div>

            {{-- Explication --}}
            @if($parrainageActif)
            <div class="mt-4 p-3 rounded-xl bg-primary-50 dark:bg-primary-900/20 border border-primary-100 dark:border-primary-800">
                <p class="text-xs text-primary-800 dark:text-primary-300 leading-relaxed">
                    <strong>Comment ça marche ?</strong> Partagez votre code ou lien avec d’autres professionnels de la beauté.
                    Quand ils s'inscrivent avec votre code et souscrivent un abonnement payant,
                    vous recevez <strong>15 jours gratuits</strong> et votre filleul reçoit <strong>7 jours gratuits</strong> en bonus.
                </p>
            </div>
            @endif
        </div>

        {{-- Notification bonus récent --}}
        @php
            $recentValides = $parrainages->where('statut', 'valide')->filter(fn($p) => $p->updated_at->gte(now()->subDays(7)));
        @endphp
        @if($recentValides->isNotEmpty())
        @php $lastParrainageKey = $recentValides->max('updated_at')->timestamp; @endphp
        <div x-data="{ show: localStorage.getItem('parrainage_dismissed') !== '{{ $lastParrainageKey }}' }" x-show="show" x-transition class="relative card p-4 bg-emerald-50 border-emerald-200 flex items-start gap-3">
            <button @click="show = false; localStorage.setItem('parrainage_dismissed', '{{ $lastParrainageKey }}')" class="absolute top-2 right-2 p-1 text-emerald-400 hover:text-emerald-600 rounded-lg hover:bg-emerald-100 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <div class="w-9 h-9 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600 flex-shrink-0 mt-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>
            </div>
            <div class="flex-1 pr-6">
                <p class="font-semibold text-emerald-900">🎉 Bonus parrainage crédité !</p>
                <p class="text-sm text-emerald-700 mt-0.5">
                    @foreach($recentValides as $rv)
                        <strong>{{ $rv->filleul->nom_complet ?? $rv->filleul->name }}</strong> a souscrit un abonnement — vous avez reçu <strong>+{{ $rv->jours_offerts_parrain }} jours</strong> gratuits.@if(!$loop->last)<br>@endif
                    @endforeach
                </p>
            </div>
        </div>
        @endif

        {{-- Stats --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="card p-4 text-center">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_filleuls'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Filleuls</p>
            </div>
            <div class="card p-4 text-center">
                <p class="text-2xl font-bold text-emerald-600">{{ $stats['valides'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Abonnés</p>
            </div>
            <div class="card p-4 text-center">
                <p class="text-2xl font-bold text-amber-600">{{ $stats['en_attente'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">En attente</p>
            </div>
            <div class="card p-4 text-center">
                <p class="text-2xl font-bold text-primary-600">{{ $stats['jours_gagnes'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Jours gagnés</p>
            </div>
        </div>

        {{-- Liste des filleuls --}}
        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-700">
                <h2 class="font-semibold text-gray-900 dark:text-white">Vos filleuls</h2>
            </div>

            @if($parrainages->isEmpty())
                <div class="text-center py-12 px-4">
                    <div class="w-14 h-14 rounded-full bg-gray-100 dark:bg-slate-700 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Aucun filleul pour l'instant.</p>
                    <p class="text-gray-400 dark:text-gray-500 text-xs mt-1">Partagez votre code pour commencer à parrainer !</p>
                </div>
            @else
                <div class="divide-y divide-gray-100 dark:divide-slate-700">
                    @foreach($parrainages as $parrainage)
                    <div class="flex items-center justify-between px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                                <span class="text-sm font-bold text-primary-700 dark:text-primary-400">{{ strtoupper(substr($parrainage->filleul->prenom ?? 'U', 0, 1)) }}</span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $parrainage->filleul->name }}</p>
                                <p class="text-xs text-gray-400">{{ $parrainage->filleul->institut?->nom ?? 'Institut' }} · {{ $parrainage->created_at->format('d/m/Y') }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($parrainage->statut === 'valide')
                                <span class="badge badge-success text-xs">
                                    <svg class="w-3 h-3 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    +{{ $parrainage->jours_offerts_parrain }}j
                                </span>
                            @else
                                <span class="badge bg-amber-50 text-amber-700 text-xs">En attente</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</x-dashboard-layout>
