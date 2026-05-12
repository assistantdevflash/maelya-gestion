<x-landing-layout :title="$title ?? null" :meta-description="$metaDescription ?? null">

{{-- ═══ HERO ═══ --}}
<section class="relative min-h-screen flex items-center overflow-hidden bg-gradient-to-br from-gray-950 via-primary-950 to-gray-900">
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-[500px] h-[500px] bg-primary-500/15 rounded-full blur-[100px] animate-float"></div>
        <div class="absolute -bottom-40 -left-40 w-[400px] h-[400px] bg-secondary-500/15 rounded-full blur-[100px] animate-float-delayed"></div>
        <div class="absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 40px 40px;"></div>
    </div>

    <div class="relative max-w-6xl mx-auto px-4 sm:px-6 pt-28 pb-20 lg:pt-32 lg:pb-28 w-full">
        <div class="grid lg:grid-cols-2 gap-8 sm:gap-12 lg:gap-16 items-center">
            <div class="text-center lg:text-left animate-fade-in-up">
                
                <h1 class="text-4xl sm:text-5xl lg:text-[3.5rem] font-extrabold leading-[1.1] text-white mb-6 tracking-tight">
                    La gestion de votre établissement,
                    <span class="shimmer-text"> enfin simple.</span>
                </h1>

                <p class="text-lg sm:text-xl text-white/60 mb-10 leading-relaxed max-w-xl mx-auto lg:mx-0">
                    Caisse, rendez-vous, clients, stock — tout centralisé dans une application pensée pour les professionnels de la beauté, du bien-être, de la coiffure, de la mode et bien d'autres.
                </p>

                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    <a href="{{ route('inscription') }}" class="group inline-flex items-center justify-center gap-2.5 px-7 py-3.5 rounded-2xl text-base font-bold text-white shadow-xl shadow-primary-900/40 transition-all duration-300 hover:shadow-2xl hover:-translate-y-0.5"
                       style="background: linear-gradient(135deg, #9333ea 0%, #ec4899 100%);">
                        Essayer gratuitement
                        <svg class="w-5 h-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </a>
                    <a href="#fonctionnalites" class="inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-2xl text-base font-semibold text-white/80 border border-white/20 hover:bg-white/10 hover:border-white/30 transition-all duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Découvrir
                    </a>
                </div>

                <div class="mt-12 flex flex-wrap items-center gap-x-8 gap-y-3 justify-center lg:justify-start">
                    @foreach(['14 jours d\'essai gratuit', '100% en français', 'Mobile-first'] as $badge)
                    <div class="flex items-center gap-2 text-sm text-white/50">
                        <svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        {{ $badge }}
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Phone Mockup --}}
            <div class="relative hidden lg:flex justify-center animate-fade-in-right delay-200">
                <div class="relative">
                    <div class="relative mx-auto w-[280px] animate-float">
                        <div class="bg-gray-900 rounded-[2.8rem] p-3 shadow-2xl shadow-black/50 border border-white/10 ring-1 ring-white/5">
                            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-28 h-6 bg-gray-900 rounded-b-2xl z-10"></div>
                            <div class="bg-white rounded-[2.2rem] overflow-hidden">
                                <div class="bg-gradient-to-r from-primary-600 to-secondary-500 px-5 py-3.5 flex items-center justify-between">
                                    <span class="text-white text-sm font-bold">Maëlya Gestion</span>
                                    <div class="w-7 h-7 bg-white/20 rounded-full flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                    </div>
                                </div>
                                <div class="p-3 bg-gray-50 space-y-2.5">
                                    <p class="text-[11px] font-semibold text-gray-500 px-1 uppercase tracking-wider">Aujourd'hui</p>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div class="bg-white rounded-2xl p-3 shadow-sm border border-gray-100">
                                            <p class="text-[10px] text-gray-400 font-medium">CA du jour</p>
                                            <p class="text-lg font-extrabold text-primary-600 mt-0.5">285 000</p>
                                            <p class="text-[10px] text-gray-400">FCFA</p>
                                        </div>
                                        <div class="bg-white rounded-2xl p-3 shadow-sm border border-gray-100">
                                            <p class="text-[10px] text-gray-400 font-medium">Ventes</p>
                                            <p class="text-lg font-extrabold text-secondary-600 mt-0.5">12</p>
                                            <p class="text-[10px] text-emerald-500 font-medium">+3 aujourd'hui</p>
                                        </div>
                                    </div>
                                    <div class="bg-white rounded-2xl p-3 shadow-sm border border-gray-100">
                                        <p class="text-[10px] font-semibold text-gray-500 mb-2">CA — 7 jours</p>
                                        <div class="flex items-end gap-1.5 h-12">
                                            @foreach([35, 55, 40, 75, 50, 85, 65] as $h)
                                                <div class="flex-1 rounded-md" style="height: {{ $h }}%; background: linear-gradient(to top, {{ $h > 60 ? '#9333ea' : '#e9d5ff' }}, {{ $h > 60 ? '#c084fc' : '#f3e8ff' }});"></div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-white border-t border-gray-100 px-4 py-2.5 flex justify-around">
                                    @foreach([['Accueil', true], ['Caisse', false], ['Clients', false], ['Stock', false]] as [$nav, $active])
                                        <div class="flex flex-col items-center gap-0.5 {{ $active ? 'text-primary-600' : 'text-gray-300' }}">
                                            <div class="w-5 h-5 rounded {{ $active ? 'bg-primary-100' : 'bg-gray-100' }} flex items-center justify-center">
                                                <div class="w-2.5 h-2.5 bg-current rounded-sm"></div>
                                            </div>
                                            <span class="text-[9px] font-medium">{{ $nav }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="absolute -right-12 top-14 glass rounded-2xl shadow-2xl px-4 py-3 flex items-center gap-3 text-xs font-semibold text-gray-700 border border-white/60 animate-float-delayed">
                        <div class="w-8 h-8 bg-emerald-100 rounded-xl flex items-center justify-center shadow-sm">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div>
                            <p class="text-gray-900">Vente validée !</p>
                            <p class="text-gray-400 font-normal">12 500 FCFA</p>
                        </div>
                    </div>
                    <div class="absolute -left-10 bottom-28 glass rounded-2xl shadow-2xl px-4 py-3 flex items-center gap-3 text-xs font-semibold border border-white/60 animate-float">
                        <div class="w-8 h-8 bg-secondary-100 rounded-xl flex items-center justify-center shadow-sm">
                            <svg class="w-4 h-4 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-gray-900">+3 clients</p>
                            <p class="text-gray-400 font-normal">aujourd'hui</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="hero-fade"></div>
</section>

{{-- ═══ SOCIAL PROOF ═══ --}}
<section class="py-16 bg-primary-50/30 dark:bg-gray-900">
    <div class="max-w-5xl mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-8 text-center">
            @foreach([
                ['500+', 'Instituts inscrits', 'from-primary-500 to-primary-600'],
                ['98%', 'Satisfaction', 'from-secondary-500 to-secondary-600'],
                ['2M+', 'FCFA gérés / mois', 'from-emerald-500 to-emerald-600'],
                ['24/7', 'Accès permanent', 'from-amber-500 to-amber-600'],
            ] as [$stat, $label, $gradient])
                <div>
                    <p class="text-3xl sm:text-4xl font-extrabold bg-gradient-to-r {{ $gradient }} bg-clip-text text-transparent">{{ $stat }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1.5 font-medium">{{ $label }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══ FONCTIONNALITÉS ═══ --}}
<section id="fonctionnalites" class="py-24 bg-gradient-to-b from-primary-50/30 to-primary-50/60 dark:from-gray-950 dark:to-gray-900">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-16">
            <div class="inline-flex items-center gap-2 bg-primary-50 text-primary-700 text-xs font-bold px-4 py-1.5 rounded-full mb-4 uppercase tracking-wider">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Fonctionnalités
            </div>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 dark:text-white tracking-tight">Tout ce dont votre établissement a besoin</h2>
            <p class="text-lg text-gray-500 dark:text-gray-400 mt-4 max-w-2xl mx-auto leading-relaxed">Plus besoin de cahiers ou de tableurs. Gérez tout depuis votre téléphone.</p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
            @foreach([
                ['from-primary-500 to-violet-600', 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', 'Caisse rapide', 'Enregistrez une vente en moins de 30 secondes. Cash ou Mobile Money, avec ticket imprimable.'],
                ['from-secondary-500 to-pink-600', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', 'Gestion clients', 'Base clients complète avec historique des visites, préférences et statistiques d\'achat.'],
                ['from-emerald-500 to-teal-600', 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'Stock & Alertes', 'Suivez vos produits en temps réel. Alertes automatiques quand vous manquez de stock.'],
                ['from-amber-500 to-orange-600', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'Rapports financiers', 'Visualisez vos revenus, dépenses et bénéfices par jour, semaine ou mois.'],
                ['from-blue-500 to-indigo-600', 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'Multi-employés', 'Ajoutez vos employés avec des accès limités. Chaque membre se connecte avec ses identifiants.'],
                ['from-violet-500 to-purple-600', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'Agenda & Rendez-vous', 'Gérez vos rendez-vous en ligne avec confirmations et rappels automatiques envoyés à vos clients.'],
                ['from-rose-500 to-red-500', 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z', 'Accessible partout', 'Interface mobile-first. Gérez votre établissement depuis n\'importe où, à tout moment.'],
            ] as [$gradient, $icon, $title, $desc])
                <div class="group relative bg-white/70 dark:bg-gray-800 rounded-2xl p-5 sm:p-7 shadow-sm border border-primary-100/60 dark:border-gray-700 hover:shadow-xl hover:shadow-primary-200/30 hover:-translate-y-1 transition-all duration-500">
                    <div class="w-12 h-12 bg-gradient-to-br {{ $gradient }} rounded-2xl flex items-center justify-center mb-5 shadow-lg shadow-gray-200/50 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $icon }}"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-2">{{ $title }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══ COMMENT ÇA MARCHE ═══ --}}
<section class="py-24 bg-primary-50/20 dark:bg-gray-950">
    <div class="max-w-5xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-16">
            <div class="inline-flex items-center gap-2 bg-emerald-50 text-emerald-700 text-xs font-bold px-4 py-1.5 rounded-full mb-4 uppercase tracking-wider">En 3 étapes</div>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 dark:text-white tracking-tight">Démarrez en moins de 2 minutes</h2>
        </div>

        <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-6 sm:gap-8">
            @foreach([
                ['1', 'from-primary-500 to-primary-600', 'Créez votre compte', 'Inscrivez-vous gratuitement avec votre nom, email et les infos de votre établissement.'],
                ['2', 'from-secondary-500 to-secondary-600', 'Configurez votre établissement', 'Ajoutez vos prestations, vos rendez-vous, vos produits et invitez votre équipe.'],
                ['3', 'from-emerald-500 to-emerald-600', 'Gérez au quotidien', 'Encaissez, suivez vos clients, consultez vos rapports. Tout est fluide.'],
            ] as [$num, $gradient, $title, $desc])
                <div class="relative text-center group">
                    <div class="w-16 h-16 mx-auto bg-gradient-to-br {{ $gradient }} rounded-2xl flex items-center justify-center text-white text-2xl font-extrabold shadow-xl mb-6 group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">{{ $num }}</div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">{{ $title }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed max-w-xs mx-auto">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══ TARIFS ═══ --}}
<section id="tarifs" class="py-24 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-b from-primary-50/60 via-primary-50/20 to-primary-50/60 dark:from-gray-900 dark:via-gray-950 dark:to-gray-900"></div>
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[400px] bg-primary-100/40 dark:hidden rounded-full blur-[100px]"></div>

    <div class="relative max-w-5xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-16">
            <div class="inline-flex items-center gap-2 bg-amber-50 text-amber-700 text-xs font-bold px-4 py-1.5 rounded-full mb-4 uppercase tracking-wider">Tarification transparente</div>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 dark:text-white tracking-tight">Des plans adaptés à votre activité</h2>
            <p class="text-gray-500 dark:text-gray-300 mt-4 text-lg">Commencez gratuitement, évoluez à votre rythme.</p>
        </div>

        @if($plans->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 sm:gap-6 justify-center max-w-6xl mx-auto">
            @foreach($plans as $plan)
            <div class="relative group {{ $plan->mis_en_avant ? 'sm:scale-105 z-10' : '' }}">
                @if($plan->mis_en_avant)
                <div class="absolute -inset-[2px] bg-gradient-to-r from-primary-500 to-secondary-500 rounded-[1.8rem]"></div>
                @endif
                <div class="relative bg-white/80 dark:bg-gray-800 rounded-3xl p-5 sm:p-8 {{ $plan->mis_en_avant ? '' : 'border border-primary-100/60 dark:border-gray-700 hover:border-primary-200' }} shadow-sm hover:shadow-xl transition-all duration-500 flex flex-col h-full">
                    @if($plan->mis_en_avant)
                    <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                        <span class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white text-xs font-bold px-5 py-1.5 rounded-full shadow-lg inline-flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            Recommandé
                        </span>
                    </div>
                    @endif

                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1">{{ $plan->nom }}</h3>
                        @if($plan->description)
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">{{ $plan->description }}</p>
                        @endif
                        <div class="mb-8">
                            @php $offrePlan = $plan->meilleureOffre(); @endphp
                            @if($offrePlan)
                            <div class="mb-2">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-gradient-to-r {{ $offrePlan->badge_class }} text-white text-[10px] font-bold uppercase tracking-wide">
                                    {{ $offrePlan->badge_texte }}
                                </span>
                            </div>
                            <div class="flex items-baseline gap-2">
                                <span class="text-3xl sm:text-4xl font-extrabold whitespace-nowrap {{ $plan->mis_en_avant ? 'gradient-text' : 'text-gray-900 dark:text-white' }}">{{ number_format($plan->prixEffectif(), 0, ',', "\u{00A0}") }}</span>
                                <span class="text-gray-400 text-sm font-medium">FCFA / mois</span>
                            </div>
                            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">
                                au lieu de <span class="line-through">{{ number_format($plan->prix, 0, ',', ' ') }} FCFA</span>
                                · <span class="text-orange-500 font-medium">jusqu'au {{ $offrePlan->date_fin->format('d/m/Y') }}</span>
                            </p>
                            @else
                            <span class="text-3xl sm:text-4xl font-extrabold whitespace-nowrap {{ $plan->mis_en_avant ? 'gradient-text' : 'text-gray-900 dark:text-white' }}">{{ number_format($plan->prix, 0, ',', "\u{00A0}") }}</span>
                            <span class="text-gray-400 text-sm font-medium ml-1">FCFA / mois</span>
                            @endif
                        </div>
                        <ul class="space-y-3 text-sm text-gray-600 dark:text-gray-300">
                            @php
                                if ($plan->slug === 'basic') {
                                    $features = [
                                        $plan->max_instituts === null ? 'Établissements illimités' : $plan->max_instituts . ' établissement',
                                        $plan->max_employes === null ? 'Employés illimités' : $plan->max_employes . ' employé(s)',
                                        'Caisse simple',
                                        'Catalogue prestations',
                                        'Historique des ventes',
                                    ];
                                } else {
                                    $features = [
                                        $plan->max_instituts === null ? 'Établissements illimités' : $plan->max_instituts . ' établissement',
                                        $plan->max_employes === null ? 'Employés illimités' : $plan->max_employes . ' employé(s)',
                                        'Caisse illimitée', 'Agenda & Rendez-vous', 'Gestion stock & clients', 'Rapports financiers',
                                    ];
                                    if ($plan->mis_en_avant) $features[] = 'Support prioritaire';
                                }
                            @endphp
                            @foreach($features as $feature)
                            <li class="flex items-center gap-3">
                                <div class="w-5 h-5 bg-emerald-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                {{ $feature }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="mt-8">
                        <a href="{{ route('inscription') }}" class="{{ $plan->mis_en_avant ? 'bg-gradient-to-r from-primary-600 to-secondary-600 text-white shadow-xl shadow-primary-200/50 hover:shadow-2xl' : 'bg-gray-900 text-white hover:bg-gray-800' }} w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl font-bold text-sm transition-all duration-300 hover:-translate-y-0.5">
                            Choisir ce plan
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-16">
            <p class="text-gray-500 text-lg mb-4">Les plans tarifaires seront bientôt disponibles.</p>
            <a href="{{ route('contact') }}" class="btn-primary">Nous contacter</a>
        </div>
        @endif
    </div>
</section>

{{-- ═══ TÉMOIGNAGES ═══ --}}
<section class="py-24 bg-primary-50/30 dark:bg-gray-900">
    <div class="max-w-5xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-16">
            <div class="inline-flex items-center gap-2 bg-secondary-50 text-secondary-700 text-xs font-bold px-4 py-1.5 rounded-full mb-4 uppercase tracking-wider">Témoignages</div>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 dark:text-white tracking-tight">Ce que disent nos utilisateurs</h2>
        </div>
        <div class="grid md:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
            @foreach([
                ['Aicha Koné', 'Institut Beauté Prestige', 'Abidjan', 'Depuis que j\'utilise Maëlya Gestion, je sais exactement combien je gagne chaque jour. Mon comptable est content !', 'from-primary-400 to-violet-500'],
                ['Fatou Traoré', 'Onglerie Dorée', 'Bouaké', 'La caisse est tellement rapide. Mes clients sont impressionnés par les tickets que j\'imprime. Très pro !', 'from-secondary-400 to-pink-500'],
                ['Marie-Claire Bah', 'Spa Zen', 'Yopougon', 'J\'ai enfin arrêté les cahiers. Je gère mes 3 employés depuis mon téléphone. C\'est magique !', 'from-emerald-400 to-teal-500'],
            ] as [$name, $institut, $ville, $quote, $gradient])
                <div class="group bg-white/70 dark:bg-gray-800 rounded-2xl p-7 border border-primary-100/60 dark:border-gray-700 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-500">
                    <div class="flex items-center gap-0.5 mb-4">
                        @for($i = 0; $i < 5; $i++)
                        <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                    </div>
                    <p class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed mb-6 italic">"{{ $quote }}"</p>
                    <div class="flex items-center gap-3 pt-5 border-t border-gray-100">
                        <div class="w-10 h-10 bg-gradient-to-br {{ $gradient }} rounded-xl flex items-center justify-center text-white font-bold text-sm shadow-md">{{ strtoupper(substr($name, 0, 1)) }}</div>
                        <div>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $name }}</p>
                            <p class="text-xs text-gray-400">{{ $institut }} · {{ $ville }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══ CTA FINAL ═══ --}}
<section class="relative py-24 overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-primary-600 via-primary-700 to-secondary-700"></div>
    <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 30px 30px;"></div>
    <div class="absolute -top-40 -right-40 w-80 h-80 bg-white/10 rounded-full blur-[80px]"></div>

    <div class="relative max-w-3xl mx-auto px-4 sm:px-6 text-center">
        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white mb-6 leading-tight">
            Prêt(e) à transformer la gestion de votre établissement ?
        </h2>
        <p class="text-white/70 text-lg mb-10 max-w-xl mx-auto leading-relaxed">
            Rejoignez plus de 500 professionnels qui font confiance à Maëlya Gestion. Essai gratuit de 14 jours, sans engagement.
        </p>
        <a href="{{ route('inscription') }}" class="group inline-flex items-center gap-3 bg-white text-gray-900 hover:bg-gray-50 px-8 py-4 rounded-2xl font-bold text-base shadow-2xl transition-all duration-300 hover:-translate-y-1">
            Créer mon compte gratuitement
            <svg class="w-5 h-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
        </a>
    </div>
</section>

</x-landing-layout>