<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#9333ea">
    <title>{{ isset($title) ? $title . ' — ' : '' }}Maëlya Gestion</title>
    <meta name="description" content="{{ $metaDescription ?? 'Maëlya Gestion : logiciel de gestion tout-en-un pour instituts de beauté en Côte d\'Ivoire. Caisse, clients, stocks et finances.' }}">
    @if(!empty($noindex))
    <meta name="robots" content="noindex, nofollow">
    @endif
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="manifest" href="/manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Maëlya Gestion">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Maëlya Gestion">
    <meta property="og:locale" content="fr_CI">
    <meta property="og:title" content="{{ isset($title) ? $title . ' — Maëlya Gestion' : 'Maëlya Gestion' }}">
    <meta property="og:description" content="{{ $metaDescription ?? 'Maëlya Gestion : logiciel de gestion tout-en-un pour instituts de beauté en Côte d\'Ivoire. Caisse, clients, stocks et finances.' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('og-image.svg') }}">
    <meta property="og:image:type" content="image/svg+xml">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Maëlya Gestion — Logiciel de gestion pour instituts de beauté">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ isset($title) ? $title . ' — Maëlya Gestion' : 'Maëlya Gestion' }}">
    <meta name="twitter:description" content="{{ $metaDescription ?? 'Maëlya Gestion : logiciel de gestion tout-en-un pour instituts de beauté en Côte d\'Ivoire. Caisse, clients, stocks et finances.' }}">
    <meta name="twitter:image" content="{{ asset('og-image.svg') }}">
    <meta name="twitter:image:alt" content="Maëlya Gestion — Logiciel de gestion pour instituts de beauté">
    {{-- Anti-flash : appliquer le thème avant le rendu --}}
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
    <style>
        [x-cloak] { display: none !important; }
        @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-12px)} }
        @keyframes fadeInUp { from{opacity:0;transform:translateY(30px)} to{opacity:1;transform:translateY(0)} }
        @keyframes fadeInLeft { from{opacity:0;transform:translateX(-30px)} to{opacity:1;transform:translateX(0)} }
        @keyframes fadeInRight { from{opacity:0;transform:translateX(30px)} to{opacity:1;transform:translateX(0)} }
        @keyframes scaleIn { from{opacity:0;transform:scale(0.9)} to{opacity:1;transform:scale(1)} }
        @keyframes countUp { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
        @keyframes shimmer { 0%{background-position:-200% 0} 100%{background-position:200% 0} }
        .animate-float { animation: float 6s ease-in-out infinite; }
        .animate-float-delayed { animation: float 6s ease-in-out 2s infinite; }
        .animate-fade-in-up { animation: fadeInUp 0.7s ease-out forwards; }
        .animate-fade-in-left { animation: fadeInLeft 0.7s ease-out forwards; }
        .animate-fade-in-right { animation: fadeInRight 0.7s ease-out forwards; }
        .animate-scale-in { animation: scaleIn 0.5s ease-out forwards; }
        .delay-100 { animation-delay: 0.1s; }
        .delay-200 { animation-delay: 0.2s; }
        .delay-300 { animation-delay: 0.3s; }
        .delay-400 { animation-delay: 0.4s; }
        .delay-500 { animation-delay: 0.5s; }
        .shimmer-text {
            background: linear-gradient(90deg, #9333ea, #ec4899, #f97316, #ec4899, #9333ea);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: shimmer 4s linear infinite;
        }
        .glass { background: rgba(255,255,255,0.7); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); }
        .glass-dark { background: rgba(0,0,0,0.3); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); }
    </style>

    {{-- JSON-LD : Organization --}}
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "Organization",
        "name": "Maëlya Gestion",
        "url": "{{ url('/') }}",
        "logo": "{{ asset('favicon.svg') }}",
        "description": "Logiciel de gestion tout-en-un pour instituts de beauté en Côte d'Ivoire.",
        "address": {
            "@@type": "PostalAddress",
            "addressLocality": "Abidjan",
            "addressCountry": "CI"
        },
        "contactPoint": {
            "@@type": "ContactPoint",
            "email": "contact@@maelyagestion.com",
            "contactType": "customer service",
            "availableLanguage": "French"
        }
    }
    </script>

    @stack('jsonld')
</head>
<body class="landing bg-primary-50/20 dark:bg-gray-950 text-gray-900 dark:text-white overflow-x-hidden">

    {{-- ═══ NAVBAR ═══ --}}
    <header
        x-data="{
            mobileMenu: false,
            scrolled: false,
            themeMenu: false,
            theme: localStorage.getItem('maelya-theme') || 'system',
            get isDark() {
                if (this.theme === 'dark') return true;
                if (this.theme === 'light') return false;
                return window.matchMedia('(prefers-color-scheme: dark)').matches;
            },
            setTheme(t) {
                this.theme = t;
                localStorage.setItem('maelya-theme', t);
                if (t === 'dark') {
                    document.documentElement.classList.add('dark');
                } else if (t === 'light') {
                    document.documentElement.classList.remove('dark');
                } else {
                    document.documentElement.classList.toggle('dark', window.matchMedia('(prefers-color-scheme: dark)').matches);
                    if (!this._mqListener) {
                        this._mqListener = (e) => {
                            if (localStorage.getItem('maelya-theme') !== 'system') return;
                            document.documentElement.classList.toggle('dark', e.matches);
                            this.theme = 'system';
                        };
                        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', this._mqListener);
                    }
                }
                if (t !== 'system' && this._mqListener) {
                    window.matchMedia('(prefers-color-scheme: dark)').removeEventListener('change', this._mqListener);
                    this._mqListener = null;
                }
                this.themeMenu = false;
            }
        }"
        @scroll.window="scrolled = (window.scrollY > 20)"
        class="fixed top-0 left-0 right-0 z-50 transition-all duration-500"
        :style="scrolled ? `background: ${isDark ? 'rgba(3,7,18,0.85)' : 'rgba(255,255,255,0.82)'}; backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);` : ''"
        :class="scrolled ? (isDark ? 'shadow-sm border-b border-white/10' : 'shadow-sm border-b border-gray-200/60') : 'bg-transparent'">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-between h-16 lg:h-18">
                {{-- Logo --}}
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 group">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-white text-base shadow-md transition-all duration-300 group-hover:scale-110 group-hover:shadow-lg"
                         style="background: linear-gradient(135deg, #9333ea, #ec4899);">M</div>
                    <span class="font-display font-bold text-lg tracking-tight"
                          :class="scrolled ? 'text-gray-900 dark:text-white' : 'text-white'">Maëlya <span class="gradient-text">Gestion</span></span>
                </a>

                {{-- Nav desktop --}}
                <nav class="hidden md:flex items-center gap-0.5" aria-label="Navigation principale">
                    @foreach([
                        ['Accueil', 'home'],
                        ['À propos', 'about'],
                        ['FAQ', 'faq'],
                        ['Contact', 'contact'],
                    ] as [$label, $routeName])
                    <a href="{{ route($routeName) }}"
                       class="relative px-4 py-2 text-sm font-medium rounded-lg transition-all duration-300"
                       :class="scrolled
                           ? '{{ request()->routeIs($routeName) ? 'text-primary-600 bg-primary-50 dark:text-primary-400 dark:bg-primary-950' : 'text-gray-600 dark:text-gray-300 hover:text-primary-600 hover:bg-gray-50 dark:hover:bg-white/10' }}'
                           : '{{ request()->routeIs($routeName) ? 'text-white bg-white/20' : 'text-white/80 hover:text-white hover:bg-white/10' }}'">
                        {{ $label }}
                        @if(request()->routeIs($routeName))
                        <span class="absolute bottom-0 left-1/2 -translate-x-1/2 w-5 h-0.5 bg-primary-500 rounded-full"></span>
                        @endif
                    </a>
                    @endforeach
                </nav>

                {{-- CTA + Theme toggle --}}
                <div class="hidden md:flex items-center gap-3">
                    {{-- Bouton toggle thème --}}
                    <div class="relative">
                        <button @click="themeMenu = !themeMenu" @click.outside="themeMenu = false"
                                class="w-9 h-9 rounded-xl flex items-center justify-center transition-all duration-200"
                                :class="scrolled ? 'text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/10' : 'text-white/70 hover:text-white hover:bg-white/10'"
                                title="Changer le thème">
                            {{-- Soleil (mode sombre actif) --}}
                            <svg x-show="isDark" class="w-4.5 h-4.5" style="width:1.1rem;height:1.1rem" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            {{-- Lune (mode clair actif) --}}
                            <svg x-show="!isDark" class="w-4.5 h-4.5" style="width:1.1rem;height:1.1rem" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        </button>
                        <div x-show="themeMenu" x-cloak
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             class="absolute right-0 top-full mt-2 w-40 bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-100 dark:border-white/10 p-1.5 z-10">
                            @foreach([['system','Système','M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],['light','Clair','M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M16 12a4 4 0 11-8 0 4 4 0 018 0z'],['dark','Sombre','M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z']] as [$val,$lbl,$ico])
                            <button @click="setTheme('{{ $val }}')" class="w-full flex items-center gap-2.5 px-3 py-2 text-sm rounded-xl transition-colors"
                                    :class="theme === '{{ $val }}' ? 'bg-primary-50 dark:bg-primary-950 text-primary-700 dark:text-primary-300 font-semibold' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5'">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $ico }}"/></svg>
                                {{ $lbl }}
                                <svg x-show="theme === '{{ $val }}'" class="w-3.5 h-3.5 ml-auto text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    @auth
                        <a href="{{ auth()->user()->role === 'super_admin' ? route('admin.dashboard') : route('dashboard.index') }}" class="btn-primary btn-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                            Mon espace
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium transition-colors px-3 py-2"
                           :class="scrolled ? 'text-gray-600 dark:text-gray-300 hover:text-primary-600' : 'text-white/80 hover:text-white'">Connexion</a>
                        <a href="{{ route('inscription') }}" class="btn-primary btn-sm">
                            Essayer gratuitement
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </a>
                    @endauth
                </div>

                {{-- Burger --}}
                <button @click="mobileMenu = !mobileMenu"
                        class="md:hidden p-2 rounded-xl transition-colors"
                        :class="scrolled ? 'text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/10' : 'text-white/80 hover:text-white hover:bg-white/10'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path x-show="!mobileMenu" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path x-show="mobileMenu" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Mobile menu --}}
            <div x-show="mobileMenu" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0 -translate-y-2"
                 class="md:hidden glass rounded-2xl shadow-xl p-4 mb-4 space-y-1 border border-gray-200/50">
                @foreach([
                    ['Accueil', 'home'], ['À propos', 'about'], ['FAQ', 'faq'], ['Contact', 'contact']
                ] as [$label, $routeName])
                <a href="{{ route($routeName) }}"
                   class="block px-4 py-3 text-sm font-medium rounded-xl transition-colors
                          {{ request()->routeIs($routeName) ? 'text-primary-600 bg-primary-50' : 'text-gray-700 hover:bg-gray-50' }}">
                    {{ $label }}
                </a>
                @endforeach
                <div class="pt-3 border-t border-gray-100 flex flex-col gap-2">
                    @auth
                        <a href="{{ auth()->user()->role === 'super_admin' ? route('admin.dashboard') : route('dashboard.index') }}" class="btn-primary w-full justify-center">Mon espace</a>
                    @else
                        <a href="{{ route('login') }}" class="btn-outline w-full justify-center">Connexion</a>
                        <a href="{{ route('inscription') }}" class="btn-primary w-full justify-center">Essayer gratuitement</a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    {{-- Main --}}
    <main>
        {{ $slot }}
    </main>

    {{-- Toast offre promotionnelle (sticky bottom-right) --}}
    <x-offre-toast />

    {{-- ═══ FOOTER ═══ --}}
    <footer class="relative bg-gray-950 text-gray-400 overflow-hidden" role="contentinfo">
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute -top-40 -right-40 w-80 h-80 bg-primary-600/5 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-secondary-600/5 rounded-full blur-3xl"></div>
        </div>

        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 py-16">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-8 sm:gap-10">
                {{-- Branding --}}
                <div class="md:col-span-5">
                    <div class="flex items-center gap-2.5 mb-5">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-white text-base"
                             style="background: linear-gradient(135deg, #9333ea, #ec4899);">M</div>
                        <span class="font-display font-bold text-white text-lg tracking-tight">Maëlya Gestion</span>
                    </div>
                    <p class="text-sm leading-relaxed max-w-sm mb-6">
                        La solution de gestion tout-en-un pour les instituts de beauté en Côte d'Ivoire et en Afrique de l'Ouest.
                    </p>
                </div>

                {{-- Navigation --}}
                <div class="md:col-span-3">
                    <h3 class="text-sm font-semibold text-white mb-4 uppercase tracking-wider">Navigation</h3>
                    <ul class="space-y-3 text-sm">
                        <li><a href="{{ route('about') }}" class="hover:text-white transition-colors duration-200 hover:translate-x-1 inline-block">À propos</a></li>
                        <li><a href="{{ route('faq') }}" class="hover:text-white transition-colors duration-200 hover:translate-x-1 inline-block">FAQ</a></li>
                        <li><a href="{{ route('contact') }}" class="hover:text-white transition-colors duration-200 hover:translate-x-1 inline-block">Contact</a></li>
                        <li><a href="{{ route('mentions') }}" class="hover:text-white transition-colors duration-200 hover:translate-x-1 inline-block">Mentions légales</a></li>
                    </ul>
                </div>

                {{-- Compte --}}
                <div class="md:col-span-4">
                    <h3 class="text-sm font-semibold text-white mb-4 uppercase tracking-wider">Commencer</h3>
                    <ul class="space-y-3 text-sm mb-6">
                        <li><a href="{{ route('inscription') }}" class="hover:text-white transition-colors duration-200">Créer un compte</a></li>
                        <li><a href="{{ route('login') }}" class="hover:text-white transition-colors duration-200">Se connecter</a></li>
                    </ul>
                    <a href="{{ route('inscription') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-white bg-gradient-to-r from-primary-600 to-secondary-600 px-5 py-2.5 rounded-xl hover:from-primary-500 hover:to-secondary-500 transition-all shadow-lg shadow-primary-900/30">
                        Essayer gratuitement
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </a>
                </div>
            </div>

            <div class="border-t border-gray-800/50 mt-12 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
                <p>© {{ date('Y') }} Maëlya Gestion. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

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

<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then(reg => console.log('[PWA] SW enregistré', reg.scope))
                .catch(err => console.warn('[PWA] Échec SW:', err));
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

        // Masquer si l'app vient d'être installée (ne pas verrouiller longtemps)
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
                // iOS / autres : afficher les instructions manuelles
                modal.style.display = 'flex';
            }
        });

        dismiss.addEventListener('click', function () {
            banner.style.display = 'none';
            // Masquer 7 jours seulement si l'utilisateur clique explicitement ✕
            localStorage.setItem('pwa-install-dismissed', Date.now() + 7 * 24 * 3600 * 1000);
        });
    })();
</script>
</body>
</html>
