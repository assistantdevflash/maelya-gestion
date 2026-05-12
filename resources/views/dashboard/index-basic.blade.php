<x-dashboard-layout>
<div class="px-3 sm:px-6 lg:px-8 py-4 lg:py-6 space-y-6">

    {{-- En-tête --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-display font-extrabold text-gray-900 dark:text-white">
                Bonjour {{ auth()->user()->prenom }} 👋
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Voici un aperçu de votre activité aujourd'hui — {{ now()->translatedFormat('l d F Y') }}
            </p>
        </div>
        <a href="{{ route('dashboard.caisse') }}" class="btn-primary flex-shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nouvelle vente
        </a>
    </div>

    {{-- KPIs simples --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="stat-card">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400">CA aujourd'hui</span>
                <div class="w-7 h-7 rounded-lg flex items-center justify-center"
                     style="background: linear-gradient(135deg, rgba(147,51,234,0.1), rgba(236,72,153,0.1));">
                    <svg class="w-3.5 h-3.5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                    </svg>
                </div>
            </div>
            <p class="text-xl sm:text-2xl font-display font-extrabold text-gray-900 dark:text-white">
                {{ number_format($caJour, 0, ',', ' ') }} <span class="text-sm font-bold text-gray-400">F</span>
            </p>
            <p class="text-xs text-gray-500 mt-1">{{ $ventesJour }} vente{{ $ventesJour > 1 ? 's' : '' }}</p>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400">CA du mois</span>
                <div class="w-7 h-7 rounded-lg bg-emerald-50 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
            </div>
            <p class="text-xl sm:text-2xl font-display font-extrabold text-gray-900 dark:text-white">
                {{ number_format($caMois, 0, ',', ' ') }} <span class="text-sm font-bold text-gray-400">F</span>
            </p>
            <p class="text-xs text-gray-500 mt-1">{{ $ventesMois }} vente{{ $ventesMois > 1 ? 's' : '' }} ce mois</p>
        </div>

        <a href="{{ route('dashboard.caisse') }}" class="stat-card hover:shadow-md transition-shadow group">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Caisse</span>
                <div class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center group-hover:bg-blue-100 transition-colors">
                    <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                        <line x1="1" y1="10" x2="23" y2="10"/>
                    </svg>
                </div>
            </div>
            <p class="text-base sm:text-lg font-display font-bold text-gray-900 dark:text-white">Ouvrir la caisse</p>
            <p class="text-xs text-gray-500 mt-1">Encaisser une prestation →</p>
        </a>

        <a href="{{ route('dashboard.ventes.index') }}" class="stat-card hover:shadow-md transition-shadow group">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Historique</span>
                <div class="w-7 h-7 rounded-lg bg-amber-50 flex items-center justify-center group-hover:bg-amber-100 transition-colors">
                    <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8" stroke-width="2" fill="none"/>
                    </svg>
                </div>
            </div>
            <p class="text-base sm:text-lg font-display font-bold text-gray-900 dark:text-white">Voir l'historique</p>
            <p class="text-xs text-gray-500 mt-1">Toutes mes ventes →</p>
        </a>
    </div>

    {{-- Bandeau d'upsell vers Premium --}}
    <div class="card overflow-hidden">
        <div class="p-5 sm:p-6"
             style="background: linear-gradient(135deg, rgba(147,51,234,0.06), rgba(236,72,153,0.06));">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-md"
                     style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold uppercase tracking-[0.15em] mb-1"
                       style="background: linear-gradient(135deg, #9333ea, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        Plan Basic actif
                    </p>
                    <h3 class="font-display font-bold text-base sm:text-lg text-gray-900 dark:text-white">
                        Passez à Premium pour débloquer plus
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Fichier client, fidélité, finances, gestion stock, équipe et bien plus.
                    </p>
                </div>
                <a href="{{ route('abonnement.plans') }}"
                   class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl text-sm font-bold text-white shadow-md hover:shadow-lg active:scale-[0.98] transition-all flex-shrink-0"
                   style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                    Voir Premium
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
            </div>
        </div>

        {{-- Aperçu des modules verrouillés --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-px bg-gray-100 dark:bg-slate-700/50">
            @php
                $modules = [
                    ['feature' => 'clients', 'titre' => 'Clients', 'icon' => 'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zm14 10v-2a4 4 0 00-3-3.87m-4-12a4 4 0 010 7.75'],
                    ['feature' => 'codes_reduction', 'titre' => 'Codes promo', 'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'],
                    ['feature' => 'fidelite', 'titre' => 'Fidélité', 'icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z'],
                    ['feature' => 'finances', 'titre' => 'Finances', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1'],
                    ['feature' => 'stock', 'titre' => 'Stocks', 'icon' => 'M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z'],
                    ['feature' => 'equipe', 'titre' => 'Équipe', 'icon' => 'M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2'],
                ];
            @endphp
            @foreach($modules as $m)
            <a href="{{ route('abonnement.upgrade', ['feature' => $m['feature']]) }}"
               class="group bg-white dark:bg-slate-800 hover:bg-primary-50/30 dark:hover:bg-slate-700/50 transition-colors p-4 flex flex-col items-center justify-center text-center gap-2 relative">
                <div class="absolute top-2 right-2">
                    <svg class="w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 1a5 5 0 00-5 5v4H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2v-9a2 2 0 00-2-2h-2V6a5 5 0 00-5-5zm-3 9V6a3 3 0 016 0v4H9z"/>
                    </svg>
                </div>
                <div class="w-9 h-9 rounded-xl bg-gray-50 dark:bg-slate-700 flex items-center justify-center group-hover:bg-primary-100 dark:group-hover:bg-primary-900/30 transition-colors">
                    <svg class="w-4 h-4 text-gray-400 group-hover:text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $m['icon'] }}"/>
                    </svg>
                </div>
                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 group-hover:text-primary-700 dark:group-hover:text-primary-400">{{ $m['titre'] }}</p>
            </a>
            @endforeach
        </div>
    </div>

    {{-- Raccourci prestations --}}
    @if(auth()->user()->isAdmin())
    <div class="grid sm:grid-cols-2 gap-4">
        <a href="{{ route('dashboard.prestations.index') }}" class="card p-5 hover:shadow-md transition-shadow group">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                     style="background: linear-gradient(135deg, rgba(147,51,234,0.1), rgba(236,72,153,0.1));">
                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                </div>
                <h3 class="font-display font-bold text-gray-900 dark:text-white">Mes prestations</h3>
            </div>
            <p class="text-sm text-gray-500">Gérez votre catalogue de services et leurs tarifs.</p>
        </a>

        <a href="{{ route('dashboard.mes-instituts.index') }}" class="card p-5 hover:shadow-md transition-shadow group">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <h3 class="font-display font-bold text-gray-900 dark:text-white">Paramètres</h3>
            </div>
            <p class="text-sm text-gray-500">Coordonnées de votre établissement, logo et informations de contact.</p>
        </a>
    </div>
    @endif

</div>
</x-dashboard-layout>
