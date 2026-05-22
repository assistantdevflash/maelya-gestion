@php
    $notifs     = \App\Models\Notif::where('user_id', auth()->id())
                    ->orderByDesc('created_at')
                    ->limit(20)
                    ->get();
    $nonLus     = $notifs->where('lu', false)->count();
    $toutLireUrl = route('notifications.tout-lire');
    $csrfToken  = csrf_token();
@endphp

<div x-data="{
        open: false,
        unread: {{ $nonLus }},
        toggle() {
            this.open = !this.open;
            if (this.open && this.unread > 0) {
                fetch('{{ $toutLireUrl }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ $csrfToken }}', 'Content-Type': 'application/json' }
                }).then(() => { this.unread = 0; });
            }
        }
     }"
     class="relative {{ $class ?? '' }}">

    {{-- Bouton cloche --}}
    <button @click="toggle()" @click.outside="open = false"
            class="relative w-8 h-8 rounded-xl flex items-center justify-center transition-all
                   {{ $dark ?? false
                       ? 'text-gray-400 hover:text-white hover:bg-white/10'
                       : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/10' }}"
            title="Notifications">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        {{-- Badge --}}
        <span x-show="unread > 0" x-text="unread > 9 ? '9+' : unread"
              class="absolute -top-1 -right-1 min-w-[16px] h-4 px-0.5 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center leading-none"
              x-cloak></span>
    </button>

    {{-- Dropdown --}}
    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95 translate-y-1"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-1"
         class="absolute right-0 top-full mt-2 w-80 max-w-[calc(100vw-1rem)] bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-gray-100 dark:border-white/10 z-[60] overflow-hidden">

        {{-- En-tête --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-white/10">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Notifications</h3>
                @if($nonLus > 0)
                <span class="px-1.5 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-[10px] font-bold rounded-full">{{ $nonLus }} non lu{{ $nonLus > 1 ? 'es' : '' }}</span>
                @endif
            </div>
        </div>

        {{-- Liste --}}
        <div class="max-h-[420px] overflow-y-auto divide-y divide-gray-50 dark:divide-white/[0.04]">
            @forelse($notifs as $notif)
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
                ];
                $colors = $typeColors[$notif->type] ?? ['bg' => 'bg-gray-100 dark:bg-white/10', 'text' => 'text-gray-500 dark:text-gray-400'];
                $icon   = $typeIcons[$notif->type] ?? 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
            @endphp
            <a href="{{ $notif->url }}"
               class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-white/[0.04] transition-colors {{ $notif->lu ? '' : 'bg-blue-50/40 dark:bg-blue-900/10' }}">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 {{ $colors['bg'] }}">
                    <svg class="w-4 h-4 {{ $colors['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-[13px] font-{{ $notif->lu ? 'normal' : 'semibold' }} text-gray-900 dark:text-white truncate">{{ $notif->titre }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">{{ $notif->corps }}</p>
                    <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">{{ $notif->created_at->diffForHumans() }}</p>
                </div>
                @if(!$notif->lu)
                <span class="w-2 h-2 rounded-full bg-blue-500 flex-shrink-0 mt-1.5"></span>
                @endif
            </a>
            @empty
            <div class="flex flex-col items-center justify-center py-12 text-center px-4">
                <div class="w-12 h-12 bg-gray-100 dark:bg-white/10 rounded-full flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Aucune notification</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Vous êtes à jour !</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
