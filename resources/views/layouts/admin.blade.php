<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') — Maëlya Gestion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
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
    @stack('styles')
</head>
<body class="admin text-gray-900 dark:text-white overflow-x-hidden" x-data="{
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

    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-50 w-[260px] bg-gray-950 flex flex-col transform transition-transform duration-300 lg:translate-x-0"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">

        {{-- Logo --}}
        <div class="flex items-center gap-3 px-5 py-5 border-b border-white/[0.06]">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-white text-sm flex-shrink-0 shadow-lg"
                 style="background: linear-gradient(135deg, #9333ea, #ec4899);">M</div>
            <div>
                <p class="text-white font-display font-bold text-sm tracking-tight">Maëlya Gestion</p>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-500/15 text-amber-400 text-[10px] font-semibold mt-0.5">
                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    Super Admin
                </span>
            </div>
        </div>

        <nav class="flex-1 px-3 py-5 space-y-1 overflow-y-auto">
            @php
            $adminNav = [
                ['route' => 'admin.dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'label' => 'Tableau de bord'],
                ['route' => 'admin.instituts.index', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'label' => 'Établissements'],
                ['route' => 'admin.abonnements.index', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'label' => 'Abonnements'],
                ['route' => 'admin.plans.index', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'label' => 'Plans'],
                ['route' => 'admin.offres.index', 'icon' => 'M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7', 'label' => 'Offres promo'],
                ['route' => 'admin.finance.index', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'label' => 'Point financier'],
                ['route' => 'admin.users.index', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', 'label' => 'Utilisateurs'],
                ['route' => 'admin.commerciaux.index', 'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'label' => 'Commerciaux'],
                ['route' => 'admin.messages.index', 'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'label' => 'Messages'],
                ['route' => 'admin.emails.index', 'icon' => 'M12 19l9 2-9-18-9 18 9-2zm0 0v-8', 'label' => 'Envoyer un email'],
                ['route' => 'admin.config.edit', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z', 'label' => 'Configuration'],
            ];
            $__aboPending = \App\Models\Abonnement::where('statut', 'en_attente')->count();
            $__msgNonLus = \App\Models\MessageContact::where('lu', false)->count();
            @endphp

            @foreach($adminNav as $item)
            <a href="{{ route($item['route']) }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-medium transition-all duration-200 relative
                      {{ request()->routeIs($item['route'].'*') ? 'text-white font-semibold' : 'text-gray-400 hover:text-white hover:bg-white/[0.06]' }}"
               @if(request()->routeIs($item['route'].'*'))
               style="background: linear-gradient(135deg, rgba(147, 51, 234, 0.25), rgba(236, 72, 153, 0.15));"
               @endif>
                @if(request()->routeIs($item['route'].'*'))
                <div class="absolute left-0 top-1/2 -translate-y-1/2 w-[3px] h-5 rounded-r-full" style="background: linear-gradient(180deg, #9333ea, #ec4899);"></div>
                @endif
                <svg class="w-[18px] h-[18px] {{ request()->routeIs($item['route'].'*') ? 'opacity-100' : 'opacity-60' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $item['icon'] }}"/>
                </svg>
                <span class="flex-1">{{ $item['label'] }}</span>
                @if($item['route'] === 'admin.abonnements.index' && $__aboPending > 0)
                    <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-bold text-white rounded-full animate-pulse" style="background: linear-gradient(135deg, #f59e0b, #ef4444);">{{ $__aboPending }}</span>
                @endif
                @if($item['route'] === 'admin.messages.index' && $__msgNonLus > 0)
                    <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-bold text-white rounded-full bg-blue-500">{{ $__msgNonLus }}</span>
                @endif
            </a>
            @endforeach
        </nav>

        <div class="px-3 py-4 border-t border-white/[0.06]">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl text-[13px] font-medium text-gray-400 hover:text-white hover:bg-white/[0.06] transition-all duration-200">
                    <svg class="w-[18px] h-[18px] opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Déconnexion
                </button>
            </form>
        </div>
    </aside>

    {{-- Overlay mobile --}}
    <div x-show="sidebarOpen" @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm lg:hidden" x-cloak x-transition.opacity></div>

    {{-- Main --}}
    <div class="lg:pl-[260px] min-h-screen flex flex-col overflow-x-hidden">
        {{-- Topbar --}}
        <header class="sticky top-0 z-30 bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border-b border-gray-200/60 dark:border-slate-700/60 px-4 sm:px-6 lg:px-8 h-14 flex items-center gap-3">
            <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-xl text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/10 active:scale-95 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <h1 class="text-sm font-semibold text-gray-900 dark:text-white flex-1">@yield('page-title', 'Administration')</h1>
            {{-- Bouton thème --}}
            <div class="relative">
                <button @click="themeMenu = !themeMenu" @click.outside="themeMenu = false"
                        class="w-8 h-8 rounded-xl flex items-center justify-center text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/10 transition-all" title="Thème">
                    <svg x-show="isDark" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <svg x-show="!isDark" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                </button>
                <div x-show="themeMenu" x-cloak
                     x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95 translate-y-1" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     class="absolute right-0 top-full mt-1 w-36 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-gray-100 dark:border-white/10 p-1 z-50">
                    @foreach([['system','Système'],['light','Clair'],['dark','Sombre']] as [$val,$lbl])
                    <button @click="setTheme('{{ $val }}')" class="w-full text-left flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-colors"
                            :class="theme === '{{ $val }}' ? 'bg-primary-50 dark:bg-primary-950 text-primary-700 dark:text-primary-300 font-semibold' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5'">
                        {{ $lbl }}
                        <svg x-show="theme === '{{ $val }}'" class="w-3 h-3 ml-auto text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </button>
                    @endforeach
                </div>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 bg-gradient-to-br from-primary-400 to-secondary-400 rounded-full flex items-center justify-center text-white text-xs font-bold">
                    {{ strtoupper(substr(auth()->user()->prenom ?? auth()->user()->name ?? 'A', 0, 1)) }}
                </div>
                <span class="text-xs font-medium text-gray-600 dark:text-gray-400 hidden sm:block">{{ auth()->user()->nom_complet ?? auth()->user()->name }}</span>
            </div>
        </header>

        {{-- Flash --}}
        @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="mx-4 lg:mx-8 mt-4 alert-success flex items-center justify-between">
            <span>{{ session('success') }}</span>
            <button @click="show = false" class="text-emerald-700 hover:text-emerald-900 ml-3">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        @endif

        <main class="flex-1 px-3 sm:px-6 lg:px-8 py-4 sm:py-6">
            @yield('content')
        </main>
    </div>

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
         x-show="show" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 backdrop-blur-sm"
         @keydown.escape.window="show = false" @click.self="show = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-6 text-center" x-transition @click.stop>
            <div class="w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4"
                 :class="danger ? 'bg-red-100' : 'bg-amber-100'">
                <svg class="w-6 h-6" :class="danger ? 'text-red-500' : 'text-amber-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2" x-text="title"></h3>
            <p class="text-sm text-gray-500 mb-6" x-text="message"></p>
            <div class="flex gap-3">
                <button @click="show = false" :disabled="processing" class="flex-1 px-4 py-2 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 font-medium text-sm transition">Annuler</button>
                <button @click="proceed()" class="flex-1 px-4 py-2 rounded-xl text-white font-medium text-sm transition btn-primary" :class="confirmClass" :disabled="processing">
                    <span x-show="processing" class="spinner spinner-sm" aria-hidden="true"></span>
                    <span x-text="confirmLabel"></span>
                </button>
            </div>
        </div>
    </div>

    @livewireScripts
    @stack('scripts')
    {{-- PWA Service Worker --}}
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .catch(err => console.warn('[PWA] SW admin échec :', err));
            });
        }
    </script>
    @include('partials.push-init')
</body>
</html>
