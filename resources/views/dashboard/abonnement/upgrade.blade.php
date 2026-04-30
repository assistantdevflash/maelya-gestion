<x-dashboard-layout>

@php
    // Mapping icone → svg path
    $icons = [
        'users' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4" stroke-width="1.7"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'tag' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>',
        'star' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>',
        'chart' => '<line x1="12" y1="1" x2="12" y2="23" stroke-width="1.7" stroke-linecap="round"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
        'box' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96" stroke-width="1.7" fill="none"/><line x1="12" y1="22.08" x2="12" y2="12" stroke-width="1.7"/>',
        'users-group' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4" stroke-width="1.7"/>',
        'printer' => '<polyline points="6 9 6 2 18 2 18 9" stroke-width="1.7" fill="none"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8" stroke-width="1.7"/>',
        'building' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
    ];
    $iconSvg = $icons[$meta['icon'] ?? 'users'] ?? $icons['users'];

    // Libellé du plan requis
    $planLabel = match($meta['plan_requis']) {
        'premium' => 'Premium',
        'premium-plus' => 'Premium+',
        default => ucfirst($meta['plan_requis']),
    };
@endphp

<div class="max-w-4xl mx-auto px-3 sm:px-6 lg:px-8 py-6 lg:py-10">

    {{-- Bouton retour --}}
    <a href="javascript:history.back()" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-primary-600 mb-6 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Retour
    </a>

    {{-- Hero card --}}
    <div class="card overflow-hidden">
        <div class="p-6 sm:p-10 text-center"
             style="background: linear-gradient(135deg, rgba(147,51,234,0.06), rgba(236,72,153,0.06));">

            {{-- Icône cadenas + module --}}
            <div class="relative inline-flex items-center justify-center mb-5">
                <div class="w-20 h-20 rounded-3xl flex items-center justify-center shadow-lg"
                     style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        {!! $iconSvg !!}
                    </svg>
                </div>
                <div class="absolute -bottom-1 -right-1 w-9 h-9 rounded-full bg-amber-400 flex items-center justify-center ring-4 ring-white dark:ring-slate-900 shadow-md">
                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 1a5 5 0 00-5 5v4H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2v-9a2 2 0 00-2-2h-2V6a5 5 0 00-5-5zm-3 9V6a3 3 0 016 0v4H9z"/>
                    </svg>
                </div>
            </div>

            <p class="text-xs font-bold uppercase tracking-[0.2em] mb-2"
               style="background: linear-gradient(135deg, #9333ea, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                Fonctionnalité {{ $planLabel }}
            </p>

            <h1 class="text-2xl sm:text-3xl font-display font-extrabold text-gray-900 dark:text-white mb-3">
                {{ $meta['titre'] }}
            </h1>

            <p class="text-base sm:text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">
                {{ $meta['accroche'] }}
            </p>

            <p class="text-sm text-gray-500 dark:text-gray-400 max-w-2xl mx-auto leading-relaxed">
                {{ $meta['description'] }}
            </p>
        </div>

        {{-- Avantages --}}
        <div class="p-6 sm:p-8 border-t border-gray-100 dark:border-slate-700/60">
            <p class="text-xs font-bold uppercase tracking-[0.15em] text-gray-400 mb-4">
                Ce que vous obtenez avec le plan {{ $planLabel }}
            </p>
            <ul class="grid sm:grid-cols-2 gap-3">
                @foreach($meta['avantages'] as $avantage)
                <li class="flex items-start gap-2.5">
                    <div class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5"
                         style="background: linear-gradient(135deg, rgba(16,185,129,0.15), rgba(16,185,129,0.05));">
                        <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $avantage }}</span>
                </li>
                @endforeach
            </ul>
        </div>

        {{-- Carte plan + CTA --}}
        @if($planRequis)
        <div class="p-6 sm:p-8 border-t border-gray-100 dark:border-slate-700/60 bg-gray-50/50 dark:bg-slate-800/30">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-6">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Plan {{ $planLabel }}</span>
                        @if($planRequis->mis_en_avant)
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full text-white"
                              style="background: linear-gradient(135deg, #9333ea, #ec4899);">Recommandé</span>
                        @endif
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-display font-extrabold text-gray-900 dark:text-white">
                            {{ number_format($planRequis->prix, 0, ',', ' ') }}
                        </span>
                        <span class="text-sm text-gray-500">FCFA / mois</span>
                    </div>
                    @if($planRequis->description)
                    <p class="text-xs text-gray-500 mt-1">{{ $planRequis->description }}</p>
                    @endif
                </div>
                <a href="{{ route('abonnement.plans') }}"
                   class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl text-sm font-bold text-white shadow-md hover:shadow-lg active:scale-[0.98] transition-all flex-shrink-0"
                   style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                    Passer au plan {{ $planLabel }}
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
            </div>
        </div>
        @endif
    </div>

    {{-- Note rassurante --}}
    <p class="text-center text-xs text-gray-400 dark:text-gray-500 mt-6">
        Vous gardez toutes vos données. La mise à niveau est instantanée après validation du paiement.
    </p>
</div>
</x-dashboard-layout>
