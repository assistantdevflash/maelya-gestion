<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' — ' : '' }}Maëlya Gestion</title>

    {{-- PWA --}}
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#9333ea">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Maëlya Gestion">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <script>
        (function() {
            try {
                var t = localStorage.getItem('maelya-theme');
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (t === 'dark' || (t !== 'light' && prefersDark)) {
                    document.documentElement.classList.add('dark');
                }
            } catch(e) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    {{ $styles ?? '' }}
</head>
<body class="dashboard h-full font-sans">

<div class="flex h-full" x-data="{
    sidebarOpen: false,
    themeMenu: false,
    theme: localStorage.getItem('maelya-theme') || 'system',
    get isDark() { return this.theme==='dark' || (this.theme==='system' && matchMedia('(prefers-color-scheme: dark)').matches) },
    setTheme(t) {
        this.theme = t;
        localStorage.setItem('maelya-theme', t);
        if (t === 'dark') document.documentElement.classList.add('dark');
        else if (t === 'light') document.documentElement.classList.remove('dark');
        else document.documentElement.classList.toggle('dark', matchMedia('(prefers-color-scheme: dark)').matches);
        this.themeMenu = false;
    }}">

    {{-- Overlay mobile --}}
    <div x-show="sidebarOpen"
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm lg:hidden"
         style="display:none">
    </div>

    {{-- ═══ SIDEBAR ═══ --}}
    <aside class="fixed inset-y-0 left-0 z-50 w-[260px] flex flex-col lg:translate-x-0 transition-transform duration-300 ease-out"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
        <div class="flex flex-col flex-1 min-h-0 bg-white dark:bg-slate-900 border-r border-gray-100 dark:border-slate-700/60">

            {{-- Logo / Sélecteur d'institut (style TopResto) --}}
            @php
                $__sidebarUser = auth()->user();
                $__sidebarInstituts = $__sidebarUser->isAdmin()
                    ? $__sidebarUser->mesInstituts()->orderBy('nom')->get()
                    : collect();
                // Inclure l'institut principal s'il n'est pas dans mesInstituts()
                if ($__sidebarUser->isAdmin() && $__sidebarUser->institut && !$__sidebarInstituts->contains('id', $__sidebarUser->institut_id)) {
                    $__sidebarInstituts = $__sidebarInstituts->prepend($__sidebarUser->institut);
                }
                $__sidebarCurrentId = session('current_institut_id', $__sidebarUser->institut_id);
                $__sidebarIsMulti   = $__sidebarInstituts->count() > 1;
                $__sidebarCanSwitch = $__sidebarUser->isAdmin();
                $__sidebarCurrentInst = $__sidebarInstituts->firstWhere('id', $__sidebarCurrentId) ?? $__sidebarUser->institut;
                $__sidebarNbInstituts = $__sidebarInstituts->count();
            @endphp
            <div class="relative flex-shrink-0" x-data="{ institutMenu: false }">

                {{-- Header cliquable --}}
                @if($__sidebarCanSwitch)
                <button @click="institutMenu = !institutMenu" @click.away="institutMenu = false"
                        class="flex items-center gap-3 w-full px-4 h-16 border-b border-gray-100/80 dark:border-slate-700/60 hover:bg-gray-50/60 dark:hover:bg-slate-800/40 transition-colors text-left">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shadow-glow-sm flex-shrink-0 text-white text-sm font-bold"
                         style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                        {{ strtoupper(substr($__sidebarCurrentInst?->nom ?? 'M', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-display font-bold text-gray-900 dark:text-white text-sm leading-tight tracking-tight truncate">{{ $__sidebarCurrentInst?->nom ?? 'Mon Institut' }}</p>
                        @if($__sidebarCurrentInst?->ville)
                        <p class="text-[11px] text-gray-400 truncate">{{ $__sidebarCurrentInst->ville }}</p>
                        @else
                        <p class="text-[11px] text-gray-400">Maëlya Gestion</p>
                        @endif
                    </div>
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform duration-200" :class="institutMenu ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                @else
                <div class="flex items-center gap-3 px-5 h-16 border-b border-gray-100/80 dark:border-slate-700/60">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-white text-base shadow-glow-sm flex-shrink-0"
                         style="background: linear-gradient(135deg, #9333ea, #ec4899);">M</div>
                    <div class="min-w-0 flex-1">
                        <p class="font-display font-bold text-gray-900 dark:text-white text-sm leading-none tracking-tight">Maëlya Gestion</p>
                        <p class="text-[11px] text-gray-400 mt-0.5 truncate max-w-[140px]">{{ $__sidebarUser->institut?->nom }}</p>
                    </div>
                </div>
                @endif

                {{-- Dropdown --}}
                <div x-show="institutMenu" x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="absolute left-0 right-0 top-full z-50 bg-white dark:bg-slate-900 border-x border-b border-gray-100 dark:border-slate-700 shadow-xl rounded-b-2xl overflow-hidden">

                    {{-- Header du dropdown --}}
                    <div class="px-4 pt-3 pb-1.5">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.1em]">Vos instituts ({{ $__sidebarNbInstituts }})</p>
                    </div>

                    {{-- Liste des instituts --}}
                    @foreach($__sidebarInstituts as $__inst)
                    @if($__inst->id !== $__sidebarCurrentId)
                    <form method="POST" action="{{ route('dashboard.mes-instituts.switch', $__inst) }}">
                        @csrf
                        <button type="submit"
                                class="flex items-center gap-2.5 w-full px-4 py-2.5 text-left hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0"
                                 style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                                {{ strtoupper(substr($__inst->nom, 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-gray-800 dark:text-white truncate">{{ $__inst->nom }}</p>
                                @if($__inst->ville)<p class="text-[10px] text-gray-400 truncate">{{ $__inst->ville }}</p>@endif
                            </div>
                            <svg class="w-3.5 h-3.5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                            </svg>
                        </button>
                    </form>
                    @else
                    <div class="flex items-center gap-2.5 w-full px-4 py-2.5 bg-primary-50 dark:bg-primary-900/20">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0"
                             style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                            {{ strtoupper(substr($__inst->nom, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-primary-700 dark:text-primary-300 truncate">{{ $__inst->nom }}</p>
                            @if($__inst->ville)<p class="text-[10px] text-primary-400 truncate">{{ $__inst->ville }}</p>@endif
                        </div>
                        <svg class="w-3.5 h-3.5 text-primary-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    @endif
                    @endforeach

                    {{-- Actions --}}
                    <div class="border-t border-gray-100 dark:border-slate-700 mt-1">
                        <a href="{{ route('dashboard.mes-instituts.index') }}"
                           @click="institutMenu = false"
                           class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ $__sidebarNbInstituts > 1 ? 'Gérer mes instituts' : 'Gérer mon institut' }}
                        </a>
                        @if($__sidebarUser->aPlanEntreprise())
                        <a href="{{ route('dashboard.mes-instituts.index') }}"
                           @click="institutMenu = false"
                           x-data @click="$dispatch('open-create-modal')"
                           class="flex items-center gap-2.5 px-4 py-2.5 pb-3 text-xs font-semibold transition-colors"
                           style="color: #9333ea;">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Ajouter un institut
                        </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">

                {{-- ── VUE D'ENSEMBLE ──────────────────────────────────────── --}}
                @if(auth()->user()->isAdmin())
                @php
                    // Helpers feature-gating sidebar
                    $featureHas = fn($f) => auth()->user()->aFonctionnalite($f);
                    $featureHref = fn($f, $route) => $featureHas($f) ? $route : route('abonnement.upgrade', ['feature' => $f]);
                @endphp

                <p class="px-3 mt-6 mb-2 text-[10px] font-bold text-gray-400 uppercase tracking-[0.1em]">Vue d'ensemble</p>

                <a href="{{ route('dashboard.index') }}"
                   class="sidebar-link {{ request()->routeIs('dashboard.index') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/>
                        <rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>
                    </svg>
                    Tableau de bord
                </a>
                @endif

                {{-- ── MON INSTITUT (admin) ─────────────────────────────────── --}}
                @if(auth()->user()->isAdmin())
                <p class="px-3 mt-14 mb-2 text-[10px] font-bold text-gray-400 uppercase tracking-[0.1em]">Mon institut</p>

                <a href="{{ route('dashboard.mes-instituts.index') }}"
                   class="sidebar-link {{ request()->routeIs('dashboard.mes-instituts.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    {{ isset($__sidebarNbInstituts) && $__sidebarNbInstituts > 1 ? 'Mes instituts' : 'Paramètres' }}
                </a>

                <a href="{{ $featureHref('clients', route('dashboard.clients.index')) }}"
                   class="sidebar-link {{ request()->routeIs('dashboard.clients.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    Clients
                    @if(!$featureHas('clients'))
                        <svg class="ml-auto w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 24 24" title="Fonctionnalité Premium"><path d="M12 1a5 5 0 00-5 5v4H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2v-9a2 2 0 00-2-2h-2V6a5 5 0 00-5-5zm-3 9V6a3 3 0 016 0v4H9z"/></svg>
                    @elseif(!empty($sidebarNbAnniversaires) && $sidebarNbAnniversaires > 0)
                    <span class="ml-auto inline-flex items-center justify-center w-5 h-5 rounded-full bg-pink-500 text-white text-[10px] font-bold leading-none">
                        {{ $sidebarNbAnniversaires }}
                    </span>
                    @endif
                </a>

                <a href="{{ $featureHref('rdv', route('dashboard.rdv.index')) }}"
                   class="sidebar-link {{ request()->routeIs('dashboard.rdv.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    Rendez-vous
                    @if(!$featureHas('rdv'))
                        <svg class="ml-auto w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1a5 5 0 00-5 5v4H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2v-9a2 2 0 00-2-2h-2V6a5 5 0 00-5-5zm-3 9V6a3 3 0 016 0v4H9z"/></svg>
                    @endif
                </a>

                <a href="{{ route('dashboard.prestations.index') }}"
                   class="sidebar-link {{ request()->routeIs('dashboard.prestations.*') || request()->routeIs('dashboard.categories-prestations.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                    Prestations
                </a>
                @endif

                {{-- ── COMMERCE ─────────────────────────────────────────────── --}}
                <p class="px-3 mt-14 mb-2 text-[10px] font-bold text-gray-400 uppercase tracking-[0.1em]">Commerce</p>

                <a href="{{ route('dashboard.caisse') }}"
                   class="sidebar-link {{ request()->routeIs('dashboard.caisse') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                        <line x1="1" y1="10" x2="23" y2="10"/>
                    </svg>
                    Caisse
                </a>

                <a href="{{ route('dashboard.ventes.index') }}"
                   class="sidebar-link {{ request()->routeIs('dashboard.ventes.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                    Historique
                </a>

                @if(auth()->user()->isAdmin())
                <a href="{{ $featureHref('codes_reduction', route('dashboard.codes-reduction.index')) }}"
                   class="sidebar-link {{ request()->routeIs('dashboard.codes-reduction.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    Codes de réduction
                    @if(!$featureHas('codes_reduction'))
                        <svg class="ml-auto w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1a5 5 0 00-5 5v4H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2v-9a2 2 0 00-2-2h-2V6a5 5 0 00-5-5zm-3 9V6a3 3 0 016 0v4H9z"/></svg>
                    @endif
                </a>

                @php
                    $fideliteBadge = 0;
                    $fideliteProgramme = \App\Models\ProgrammeFidelite::where('institut_id', session('current_institut_id', auth()->user()->institut_id))->first();
                    if ($fideliteProgramme && $fideliteProgramme->actif) {
                        $fideliteBadge = \App\Models\Client::where('institut_id', session('current_institut_id', auth()->user()->institut_id))
                            ->where('actif', true)
                            ->where('points_fidelite', '>=', $fideliteProgramme->seuil_recompense)
                            ->count();
                    }
                @endphp
                <a href="{{ $featureHref('fidelite', route('dashboard.fidelite.index')) }}"
                   class="sidebar-link {{ request()->routeIs('dashboard.fidelite.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                    Fidélité
                    @if(!$featureHas('fidelite'))
                        <svg class="ml-auto w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1a5 5 0 00-5 5v4H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2v-9a2 2 0 00-2-2h-2V6a5 5 0 00-5-5zm-3 9V6a3 3 0 016 0v4H9z"/></svg>
                    @elseif($fideliteBadge > 0)
                    <span class="ml-auto inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white bg-emerald-500 rounded-full">{{ $fideliteBadge }}</span>
                    @endif
                </a>

                <a href="{{ $featureHref('finances', route('dashboard.finances.index')) }}"
                   class="sidebar-link {{ request()->routeIs('dashboard.finances.*') || request()->routeIs('dashboard.depenses.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                    Point financier
                    @if(!$featureHas('finances'))
                        <svg class="ml-auto w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1a5 5 0 00-5 5v4H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2v-9a2 2 0 00-2-2h-2V6a5 5 0 00-5-5zm-3 9V6a3 3 0 016 0v4H9z"/></svg>
                    @endif
                </a>
                @endif

                @php $alertesStock = \App\Models\Produit::where('actif', true)->whereColumn('stock', '<=', 'seuil_alerte')->count(); @endphp
                @php $stockOpen = request()->routeIs('dashboard.stock.*') || request()->routeIs('dashboard.produits.*') || request()->routeIs('dashboard.categories-produits.*'); @endphp

                @if(!$featureHas('stock') && !$featureHas('produits'))
                    {{-- Bloc verrouillé : redirige directement vers la page d'upgrade --}}
                    <a href="{{ route('abonnement.upgrade', ['feature' => 'stock']) }}" class="sidebar-link">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>
                        </svg>
                        <span class="flex-1">Gestion stocks</span>
                        <svg class="ml-auto w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1a5 5 0 00-5 5v4H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2v-9a2 2 0 00-2-2h-2V6a5 5 0 00-5-5zm-3 9V6a3 3 0 016 0v4H9z"/></svg>
                    </a>
                @else
                <div x-data="{ open: {{ $stockOpen ? 'true' : 'false' }} }">
                    <button @click="open = !open"
                            class="sidebar-link w-full {{ $stockOpen ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>
                        </svg>
                        <span class="flex-1">Gestion stocks</span>
                        @if($alertesStock > 0)
                            <span class="flex items-center justify-center w-5 h-5 rounded-full bg-red-500 text-white text-[10px] font-bold mr-1">{{ $alertesStock }}</span>
                        @endif
                        <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200 flex-shrink-0" :class="open ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="open"
                         x-transition:enter="transition-all duration-200 ease-out"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition-all duration-150 ease-in"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1"
                         class="mt-0.5 ml-4 pl-3 border-l border-gray-100 dark:border-slate-700/60 space-y-0.5">
                        @if(auth()->user()->isAdmin())
                        <a href="{{ route('dashboard.produits.index') }}"
                           class="sidebar-link text-sm {{ request()->routeIs('dashboard.produits.*') || request()->routeIs('dashboard.categories-produits.*') ? 'active' : '' }}">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <span class="flex-1">Produits</span>
                        </a>
                        @endif
                        <a href="{{ route('dashboard.stock.index') }}"
                           class="sidebar-link text-sm {{ request()->routeIs('dashboard.stock.*') ? 'active' : '' }}">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-4 0v2M8 7V5a2 2 0 0 0-4 0v2"/>
                            </svg>
                            <span class="flex-1">Inventaire</span>
                            @if($alertesStock > 0)
                                <span class="flex items-center justify-center w-4 h-4 rounded-full bg-red-500 text-white text-[9px] font-bold">{{ $alertesStock }}</span>
                            @endif
                        </a>
                    </div>
                </div>
                @endif

                {{-- ── COMPTE ───────────────────────────────────────────────── --}}
                @if(auth()->user()->isAdmin())
                <p class="px-3 mt-14 mb-2 text-[10px] font-bold text-gray-400 uppercase tracking-[0.1em]">Compte</p>

                <a href="{{ route('abonnement.plans') }}"
                   class="sidebar-link {{ request()->routeIs('abonnement.plans') || request()->routeIs('abonnement.expire') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                        <line x1="1" y1="10" x2="23" y2="10"/>
                    </svg>
                    Abonnement
                </a>

                <a href="{{ route('abonnement.historique') }}"
                   class="sidebar-link {{ request()->routeIs('abonnement.historique') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    Mes transactions
                </a>

                <a href="{{ $featureHref('equipe', route('dashboard.employes.index')) }}"
                   class="sidebar-link {{ request()->routeIs('dashboard.employes.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    Mon équipe
                    @if(!$featureHas('equipe'))
                        <svg class="ml-auto w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1a5 5 0 00-5 5v4H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2v-9a2 2 0 00-2-2h-2V6a5 5 0 00-5-5zm-3 9V6a3 3 0 016 0v4H9z"/></svg>
                    @endif
                </a>

                <a href="{{ route('dashboard.parrainage.index') }}"
                   class="sidebar-link {{ request()->routeIs('dashboard.parrainage.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                    </svg>
                    Parrainage
                </a>
                @endif
            </nav>

            {{-- Bas de sidebar --}}
            <div class="border-t border-gray-100 dark:border-slate-700/60 p-3 flex-shrink-0 space-y-2">
                @php
                    $__user = auth()->user();
                    if ($__user->isEmploye()) {
                        $__owner = \App\Models\User::where('institut_id', $__user->institut_id)->where('role', 'admin')->first();
                        $abonnement = $__owner?->abonnementActif;
                    } else {
                        $abonnement = $__user->abonnementActif;
                    }
                @endphp
                @if($abonnement && $abonnement->plan?->duree_type === 'essai')
                @php
                    $essaiJours = $abonnement->joursRestants();
                    if ($essaiJours <= 0)      $essaiLabel = "Expire aujourd'hui";
                    elseif ($essaiJours === 1) $essaiLabel = 'Expire demain';
                    else                      $essaiLabel = $essaiJours . ' jours restants';
                    $essaiPct = max(0, min(100, round(($essaiJours / 14) * 100)));
                @endphp
                    <a href="{{ route('abonnement.plans') }}" class="block p-3 rounded-xl ring-1 transition-all hover:shadow-sm" style="background: linear-gradient(135deg, rgba(147,51,234,0.06), rgba(236,72,153,0.06)); border-color: rgba(147,51,234,0.2);">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-bold" style="background: linear-gradient(135deg, #9333ea, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Essai gratuit</span>
                            <span class="text-xs font-bold px-1.5 py-0.5 rounded-full text-white" style="background: linear-gradient(135deg, {{ $essaiJours <= 1 ? '#f59e0b, #ef4444' : '#9333ea, #ec4899' }});">{{ $essaiLabel }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="flex-1 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all" style="width: {{ $essaiPct }}%; background: linear-gradient(90deg, #9333ea, #ec4899);"></div>
                            </div>
                        </div>
                        <p class="text-[11px] text-gray-500 mt-1.5">S'abonner pour continuer &rarr;</p>
                    </a>
                @elseif($abonnement && $abonnement->joursRestants() <= 8)
                @php
                    $sidebarJours = $abonnement->joursRestants();
                    if ($sidebarJours <= 0)      $sidebarLabel = "Expire aujourd'hui";
                    elseif ($sidebarJours === 1) $sidebarLabel = 'Expire demain';
                    else                        $sidebarLabel = 'Expire dans ' . $sidebarJours . 'j';
                @endphp
                    <a href="{{ route('abonnement.plans') }}" class="flex items-center gap-2.5 p-2.5 bg-gradient-to-r from-amber-50 to-orange-50 rounded-xl text-xs text-amber-700 font-semibold hover:from-amber-100 hover:to-orange-100 transition-all ring-1 ring-amber-200/50">
                        <div class="w-7 h-7 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <span>{{ $sidebarLabel }}</span>
                    </a>
                @endif

                <div class="flex items-center gap-3 px-2 py-2 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-xs flex-shrink-0 shadow-sm"
                         style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                        {{ strtoupper(substr(auth()->user()->prenom ?? 'U', 0, 1)) }}{{ strtoupper(substr(auth()->user()->nom_famille ?? '', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-gray-900 dark:text-white truncate">{{ auth()->user()->prenom }}</p>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <p class="text-[11px] text-gray-400 capitalize">{{ auth()->user()->role }}</p>
                            @if(auth()->user()->isAdmin() && auth()->user()->abonnementActif && auth()->user()->aPlanEntreprise())
                            <span class="text-[9px] font-bold px-1.5 py-px rounded-full text-white leading-tight"
                                  style="background: linear-gradient(135deg, #9333ea, #ec4899);">Premium+</span>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('dashboard.profil.edit') }}" class="p-1.5 text-gray-400 hover:text-primary-600 rounded-lg hover:bg-white dark:hover:bg-slate-700 transition-all" title="Profil">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </a>
                    {{-- Bouton thème --}}
                    <div class="relative">
                        <button @click="themeMenu = !themeMenu" @click.outside="themeMenu = false"
                                class="p-1.5 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-all" title="Thème">
                            <svg x-show="isDark" style="width:1rem;height:1rem" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <svg x-show="!isDark" style="width:1rem;height:1rem" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        </button>
                        <div x-show="themeMenu" x-cloak
                             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                             class="absolute bottom-full mb-1 right-0 w-36 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-gray-100 dark:border-white/10 p-1 z-50">
                            @foreach([['system','Système'],['light','Clair'],['dark','Sombre']] as [$val,$lbl])
                            <button @click="setTheme('{{ $val }}')" class="w-full text-left flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-colors"
                                    :class="theme === '{{ $val }}' ? 'bg-primary-50 dark:bg-primary-950 text-primary-700 dark:text-primary-300 font-semibold' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5'">
                                {{ $lbl }}
                                <svg x-show="theme === '{{ $val }}'" class="w-3 h-3 ml-auto text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </button>
                            @endforeach
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="p-1.5 text-gray-400 hover:text-red-500 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-all" title="Déconnexion">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </aside>

    {{-- ═══ CONTENU PRINCIPAL ═══ --}}
    <div class="flex-1 flex flex-col min-w-0 lg:pl-[260px]">

        {{-- Topbar mobile --}}
        <header class="lg:hidden bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border-b border-gray-100 dark:border-slate-700/60 px-4 h-14 flex items-center justify-between sticky top-0 z-30">
            <button @click="sidebarOpen = true" class="p-2 rounded-xl text-gray-500 hover:bg-gray-100 transition-colors active:scale-95">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center font-bold text-white text-xs shadow-sm"
                     style="background: linear-gradient(135deg, #9333ea, #ec4899);">M</div>
                <span class="font-display font-bold text-gray-900 dark:text-white text-sm">Maëlya Gestion</span>
            </div>
            <a href="{{ route('dashboard.caisse') }}" class="btn-primary btn-sm">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Vente
            </a>
        </header>

        {{-- Bannière sursis (abonnement expiré, période de grâce de 2 jours) --}}
        @if(isset($enSursis) && $enSursis)
        <div class="px-3 sm:px-6 lg:px-8 pt-4">
            <div class="flex flex-col sm:flex-row sm:items-center gap-3 p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800/40 rounded-xl text-sm text-red-700 dark:text-red-300">
                <div class="flex items-start gap-3 flex-1">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-red-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <div>
                        <p class="font-semibold">Mode lecture seule — Abonnement expiré</p>
                        <p class="text-red-600 dark:text-red-400 mt-0.5">
                            Votre abonnement a expiré il y a {{ $sursisJours ?? 0 }} jour(s).
                            Vous ne pouvez plus enregistrer de ventes, ajouter des clients ni modifier vos données.
                        </p>
                    </div>
                </div>
                <a href="{{ route('abonnement.plans') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold text-white flex-shrink-0"
                   style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                    Renouveler maintenant
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
            </div>
        </div>
        @endif

        {{-- Bannière nouvelles offres promotionnelles --}}
        <div class="px-3 sm:px-6 lg:px-8 pt-2">
            <x-offre-banner class="space-y-2" :cta-route="route('abonnement.plans')" cta-label="Voir les plans" />
        </div>

        {{-- Flash messages --}}
        <div class="px-6 lg:px-8 pt-4 space-y-2">
            @if(session('success'))
                <div class="alert-success animate-slide-down" x-data="{show: true}" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                     x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2">
                    <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="flex-1">{{ session('success') }}</span>
                    <button @click="show = false" class="text-emerald-600 hover:text-emerald-800 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert-error animate-slide-down" x-data="{show: true}" x-show="show">
                    <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="flex-1">{{ session('error') }}</span>
                </div>
            @endif
        </div>

        {{-- Page content --}}
        <main class="flex-1 px-3 sm:px-6 lg:px-8 py-4 sm:py-6">
            <div class="max-w-7xl mx-auto w-full">
                {{ $slot }}
            </div>
        </main>
    </div>
</div>

@livewireScripts
<script>
    window.csrfToken = '{{ csrf_token() }}';
</script>

{{-- Modal de confirmation global --}}
<div x-data="{
        show: false,
        title: 'Confirmer',
        message: 'Êtes-vous sûr ?',
        confirmLabel: 'Confirmer',
        confirmClass: '!bg-red-600 hover:!bg-red-700',
        danger: true,
        formId: null,
        processing: false,
        init() {
            window.addEventListener('confirm-action', (e) => {
                this.title        = e.detail.title        || 'Confirmer';
                this.message      = e.detail.message      || 'Êtes-vous sûr ?';
                this.confirmLabel = e.detail.confirmLabel || 'Confirmer';
                this.confirmClass = e.detail.confirmClass || '!bg-red-600 hover:!bg-red-700';
                this.danger       = e.detail.danger !== false;
                this.formId       = e.detail.formId;
                this.processing   = false;
                this.show = true;
            });
        },
        proceed() {
            if (this.processing) return;
            this.processing = true;
            if (this.formId) document.getElementById(this.formId).submit();
        }
     }"
     x-show="show" x-cloak class="modal-backdrop z-[60]" @keydown.escape.window="show = false" @click.self="show = false">
    <div class="modal max-w-sm" x-transition @click.stop>
        <div class="p-6 text-center">
            <div class="w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4"
                 :class="danger ? 'bg-red-100' : 'bg-amber-100'">
                <svg class="w-6 h-6" :class="danger ? 'text-red-500' : 'text-amber-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="text-lg font-display font-bold text-gray-900 mb-2" x-text="title"></h3>
            <p class="text-sm text-gray-500 mb-6" x-text="message"></p>
            <div class="flex gap-3">
                <button @click="show = false" :disabled="processing" class="btn btn-outline flex-1 justify-center">Annuler</button>
                <button @click="proceed()" class="btn-primary flex-1 justify-center" :class="confirmClass" :disabled="processing">
                    <span x-show="processing" class="spinner spinner-sm" aria-hidden="true"></span>
                    <span x-text="confirmLabel"></span>
                </button>
            </div>
        </div>
    </div>
</div>

{{ $scripts ?? '' }}

{{-- ═══ Bouton flottant d'installation PWA ═══ --}}
<div id="pwa-install-banner"
     style="display:none; position:fixed; bottom:1.5rem; right:1.5rem; z-index:9999;">
    <button id="pwa-install-btn"
            style="display:flex; align-items:center; gap:0.625rem; padding:0.75rem 1.25rem;
                   background:linear-gradient(135deg,#9333ea,#ec4899);
                   color:#fff; font-family:inherit; font-size:0.875rem; font-weight:600;
                   border:none; border-radius:9999px; cursor:pointer;
                   box-shadow:0 4px 24px rgba(147,51,234,0.45); transition:transform .15s,box-shadow .15s;"
            onmouseover="this.style.transform='scale(1.04)';this.style.boxShadow='0 6px 30px rgba(147,51,234,0.55)'"
            onmouseout="this.style.transform='';this.style.boxShadow='0 4px 24px rgba(147,51,234,0.45)'">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        <span>Installer l'application</span>
    </button>
    <button id="pwa-dismiss-btn"
            title="Ignorer"
            style="position:absolute; top:-0.5rem; right:-0.5rem; width:1.5rem; height:1.5rem;
                   background:#fff; border:1px solid #e5e7eb; border-radius:9999px;
                   display:flex; align-items:center; justify-content:center;
                   cursor:pointer; box-shadow:0 1px 4px rgba(0,0,0,.1); color:#9ca3af; font-size:0.75rem;">
        ✕
    </button>
</div>

{{-- Modale instructions (iOS / Firefox) --}}
<div id="pwa-help-modal"
     style="display:none; position:fixed; inset:0; z-index:10000;
            background:rgba(0,0,0,.55); align-items:center; justify-content:center; padding:1rem;">
    <div style="background:#fff; border-radius:1rem; padding:1.5rem; max-width:320px; width:100%;
                box-shadow:0 20px 60px rgba(0,0,0,.3); font-family:inherit;">
        <p style="margin:0 0 .75rem; font-weight:700; font-size:1rem; color:#111;">Installer Maëlya Gestion</p>
        <p style="margin:0 0 .5rem; font-size:.875rem; color:#555;">
            <strong>Android Chrome :</strong> Menu ⋮ → <em>Ajouter à l'écran d'accueil</em>
        </p>
        <p style="margin:0 0 1rem; font-size:.875rem; color:#555;">
            <strong>iPhone Safari :</strong> Partager ↑ → <em>Sur l'écran d'accueil</em>
        </p>
        <button onclick="document.getElementById('pwa-help-modal').style.display='none'"
                style="width:100%; padding:.6rem; background:linear-gradient(135deg,#9333ea,#ec4899);
                       color:#fff; border:none; border-radius:.5rem; font-weight:600;
                       font-size:.875rem; cursor:pointer;">Fermer</button>
    </div>
</div>

{{-- PWA Service Worker --}}
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then(reg => console.log('[PWA] Service worker enregistré', reg.scope))
                .catch(err => console.warn('[PWA] Échec enregistrement SW:', err));
        });
    }

    (function () {
        // Déjà en mode standalone = déjà ouvert comme app → rien à faire
        if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone) return;
        // Utilisateur a explicitement ignoré récemment
        var dismissed = localStorage.getItem('pwa-install-dismissed');
        if (dismissed && Date.now() < parseInt(dismissed)) return;

        var banner  = document.getElementById('pwa-install-banner');
        var btn     = document.getElementById('pwa-install-btn');
        var dismiss = document.getElementById('pwa-dismiss-btn');
        var modal   = document.getElementById('pwa-help-modal');
        var deferredPrompt = null;

        // Afficher immédiatement (pas besoin d'attendre beforeinstallprompt)
        // Reset forcé via ?reset-pwa=1
        if (new URLSearchParams(location.search).get('reset-pwa') === '1') {
            localStorage.removeItem('pwa-install-dismissed');
        }
        banner.style.display = 'block';

        // Chrome/Edge/Android : stocker le prompt natif
        window.addEventListener('beforeinstallprompt', function (e) {
            e.preventDefault();
            deferredPrompt = e;
        });

        // Masquer si l'app vient d'être installée (sans verrouiller longtemps)
        window.addEventListener('appinstalled', function () {
            banner.style.display = 'none';
        });

        btn.addEventListener('click', function () {
            if (deferredPrompt) {
                // Popup native Chrome/Edge
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then(function (choice) {
                    if (choice.outcome === 'accepted') banner.style.display = 'none';
                    deferredPrompt = null;
                });
            } else {
                // iOS / autres : instructions manuelles
                modal.style.display = 'flex';
            }
        });

        dismiss.addEventListener('click', function () {
            banner.style.display = 'none';
            localStorage.setItem('pwa-install-dismissed', Date.now() + 7 * 24 * 3600 * 1000);
        });
    })();
</script>
@include('partials.push-init')
</body>
</html>
