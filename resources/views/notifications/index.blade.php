<x-dashboard-layout>
    <div class="space-y-5">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-display font-bold text-gray-900 dark:text-white tracking-tight">Notifications</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Toutes vos notifications en un seul endroit.</p>
            </div>
            @if($notifs->where('lu', false)->count() > 0)
            <button id="btn-tout-lire" onclick="marquerToutLu()"
                    class="btn-outline text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Tout marquer comme lu
            </button>
            @endif
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
            <div class="card p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Total</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $notifs->total() }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Non lues</p>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $notifs->where('lu', false)->count() }}</p>
            </div>
            <div class="card p-4 hidden sm:block">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Cette semaine</p>
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">
                    {{ \App\Models\Notif::where('user_id', auth()->id())->where('created_at', '>=', now()->startOfWeek())->count() }}
                </p>
            </div>
        </div>

        {{-- Liste des notifications --}}
        <div class="card divide-y divide-gray-100 dark:divide-white/[0.06]">
            @php
                $typeColors = [
                    'abonnement_valide'  => ['bg' => 'bg-emerald-100 dark:bg-emerald-900/30', 'text' => 'text-emerald-600 dark:text-emerald-400'],
                    'abonnement_rejete'  => ['bg' => 'bg-red-100 dark:bg-red-900/30',     'text' => 'text-red-600 dark:text-red-400'],
                    'abonnement_expire'  => ['bg' => 'bg-red-100 dark:bg-red-900/30',     'text' => 'text-red-600 dark:text-red-400'],
                    'rappel_abonnement'  => ['bg' => 'bg-amber-100 dark:bg-amber-900/30', 'text' => 'text-amber-600 dark:text-amber-400'],
                    'nouvelle_demande'   => ['bg' => 'bg-blue-100 dark:bg-blue-900/30',   'text' => 'text-blue-600 dark:text-blue-400'],
                    'nouveau_message'    => ['bg' => 'bg-purple-100 dark:bg-purple-900/30','text' => 'text-purple-600 dark:text-purple-400'],
                    'bienvenue'          => ['bg' => 'bg-violet-100 dark:bg-violet-900/30','text' => 'text-violet-600 dark:text-violet-400'],
                    'nouvel_institut'    => ['bg' => 'bg-indigo-100 dark:bg-indigo-900/30','text' => 'text-indigo-600 dark:text-indigo-400'],
                    'rdv_confirme'       => ['bg' => 'bg-cyan-100 dark:bg-cyan-900/30',   'text' => 'text-cyan-600 dark:text-cyan-400'],
                    'rdv_rappel'         => ['bg' => 'bg-cyan-100 dark:bg-cyan-900/30',   'text' => 'text-cyan-600 dark:text-cyan-400'],
                    'commission_gagnee'  => ['bg' => 'bg-yellow-100 dark:bg-yellow-900/30','text' => 'text-yellow-600 dark:text-yellow-500'],
                    'commission_payee'   => ['bg' => 'bg-green-100 dark:bg-green-900/30', 'text' => 'text-green-600 dark:text-green-400'],
                    'nouveau_filleul'    => ['bg' => 'bg-pink-100 dark:bg-pink-900/30',   'text' => 'text-pink-600 dark:text-pink-400'],
                    'anomalie_stock'     => ['bg' => 'bg-red-100 dark:bg-red-900/30',     'text' => 'text-red-600 dark:text-red-400'],
                    'anomalie_rdv_doublon' => ['bg' => 'bg-orange-100 dark:bg-orange-900/30', 'text' => 'text-orange-600 dark:text-orange-400'],
                    'anomalie_vente'     => ['bg' => 'bg-amber-100 dark:bg-amber-900/30', 'text' => 'text-amber-600 dark:text-amber-400'],
                ];
                $typeIcons = [
                    'abonnement_valide'  => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                    'abonnement_rejete'  => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
                    'abonnement_expire'  => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                    'rappel_abonnement'  => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                    'nouvelle_demande'   => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                    'nouveau_message'    => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                    'bienvenue'          => 'M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                    'nouvel_institut'    => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                    'rdv_confirme'       => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                    'rdv_rappel'         => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                    'commission_gagnee'  => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                    'commission_payee'   => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                    'nouveau_filleul'    => 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
                    'anomalie_stock'     => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
                    'anomalie_rdv_doublon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                    'anomalie_vente'     => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
                ];
            @endphp

            @forelse($notifs as $notif)
                @php
                    $colors = $typeColors[$notif->type] ?? ['bg' => 'bg-gray-100 dark:bg-white/10', 'text' => 'text-gray-500 dark:text-gray-400'];
                    $icon   = $typeIcons[$notif->type] ?? 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
                @endphp
                <a href="{{ $notif->url }}"
                   class="flex items-start gap-4 px-4 sm:px-6 py-4 hover:bg-gray-50 dark:hover:bg-white/[0.03] transition-colors {{ $notif->lu ? '' : 'bg-blue-50/40 dark:bg-blue-900/10' }}">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center flex-shrink-0 {{ $colors['bg'] }}">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 {{ $colors['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-sm sm:text-base font-{{ $notif->lu ? 'normal' : 'semibold' }} text-gray-900 dark:text-white">
                                {{ $notif->titre }}
                            </p>
                            @if(!$notif->lu)
                            <span class="w-2 h-2 rounded-full bg-blue-500 flex-shrink-0 mt-1.5"></span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $notif->corps }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                            <time datetime="{{ $notif->created_at->toIso8601String() }}">
                                {{ $notif->created_at->isoFormat('D MMMM YYYY à HH:mm') }}
                            </time>
                            <span class="mx-1.5">•</span>
                            <span>{{ $notif->created_at->diffForHumans() }}</span>
                        </p>
                    </div>
                </a>
            @empty
                <div class="flex flex-col items-center justify-center py-16 text-center px-4">
                    <div class="w-16 h-16 bg-gray-100 dark:bg-white/10 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>
                    <p class="text-base font-medium text-gray-600 dark:text-gray-400">Aucune notification</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Vous êtes à jour ! 🎉</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($notifs->hasPages())
        <div class="flex justify-center">
            {{ $notifs->links() }}
        </div>
        @endif
    </div>

    @push('scripts')
    <script>
        function marquerToutLu() {
            const btn = document.getElementById('btn-tout-lire');
            if (!btn) return;
            
            btn.disabled = true;
            btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Chargement...';
            
            fetch('{{ route('notifications.tout-lire') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.ok) {
                    window.location.reload();
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Tout marquer comme lu';
            });
        }
    </script>
    @endpush
</x-dashboard-layout>
