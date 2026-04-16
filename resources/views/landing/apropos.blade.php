<x-landing-layout :title="$title ?? null" :meta-description="$metaDescription ?? null">

{{-- ═══ HERO HEADER ═══ --}}
<section class="relative pt-32 pb-20 overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-gray-950 via-primary-950 to-gray-900"></div>
    <div class="absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 40px 40px;"></div>
    <div class="absolute -top-40 -right-40 w-96 h-96 bg-primary-500/20 rounded-full blur-[80px]"></div>

    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 text-center animate-fade-in-up">
        <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/15 text-white/80 text-xs font-bold px-4 py-1.5 rounded-full mb-6 uppercase tracking-wider">Notre histoire</div>
        <h1 class="text-4xl sm:text-5xl font-extrabold text-white tracking-tight">À propos de <span class="shimmer-text">Maëlya Gestion</span></h1>
        <p class="text-lg text-white/50 mt-5 max-w-2xl mx-auto leading-relaxed">Une solution née en Côte d'Ivoire, pensée par et pour les instituts de beauté africains.</p>
    </div>

    <div class="hero-fade"></div>
</section>

{{-- ═══ NOTRE MISSION ═══ --}}
<section class="py-20 bg-primary-50/30 dark:bg-gray-900">
    <div class="max-w-5xl mx-auto px-4 sm:px-6">
        <div class="grid md:grid-cols-2 gap-12 lg:gap-16 items-center">
            <div class="space-y-6 animate-fade-in-up">
                <div class="inline-flex items-center gap-2 bg-primary-50 text-primary-700 text-xs font-bold px-4 py-1.5 rounded-full uppercase tracking-wider">Notre mission</div>
                <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight leading-tight">Donner aux professionnels le temps de se concentrer sur l’essentiel</h2>
                <div class="space-y-4 text-gray-600 dark:text-gray-300 leading-relaxed">
                    <p>
                        <strong class="text-gray-900 dark:text-white">Maëlya Gestion</strong> est né d’un constat simple : les gérants d’instituts de beauté en Côte d’Ivoire passent trop de temps à gérer leurs cahiers, leurs stocks et leurs finances à la main.
                    </p>
                    <p>
                        Nous avons créé une application simple, rapide et pensée pour le contexte local. Paiements Mobile Money, interface 100% en français, conçue pour fonctionner même avec une connexion internet modeste.
                    </p>
                    <p>
                        Notre promesse : <strong class="text-primary-700">vous donner plus de temps pour prendre soin de vos clients</strong>.
                    </p>
                </div>
            </div>
            <div class="relative animate-fade-in-right delay-200">
                <div class="absolute -inset-4 bg-gradient-to-br from-primary-200/40 to-secondary-200/40 rounded-3xl blur-2xl"></div>
                <div class="relative bg-gradient-to-br from-primary-50 to-secondary-50 rounded-3xl p-10 text-center border border-primary-100/60 shadow-sm">
                    <div class="w-20 h-20 bg-gradient-to-br from-primary-500 to-secondary-500 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-xl shadow-primary-200/50">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    </div>
                    <blockquote class="text-gray-700 dark:text-gray-300 italic text-base leading-relaxed">
                        "Nous croyons que chaque professionnel mérite des outils adaptés, peu importe la taille de son salon."
                    </blockquote>
                    <p class="mt-5 text-sm font-bold text-primary-600">— L'équipe Maëlya Gestion</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══ CHIFFRES ═══ --}}
<section class="py-16 bg-primary-50/60 dark:bg-gray-800/60">
    <div class="max-w-5xl mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            @foreach([
                ['500+', 'Instituts inscrits', 'from-primary-500 to-primary-600'],
                ['100%', 'En français', 'from-secondary-500 to-secondary-600'],
                ['FCFA', 'Monnaie locale', 'from-emerald-500 to-emerald-600'],
                ['24/7', 'Disponibilité', 'from-amber-500 to-amber-600'],
            ] as [$stat, $label, $gradient])
                <div>
                    <p class="text-3xl sm:text-4xl font-extrabold bg-gradient-to-r {{ $gradient }} bg-clip-text text-transparent">{{ $stat }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1.5 font-medium">{{ $label }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══ NOS VALEURS ═══ --}}
<section class="py-24 bg-primary-50/20 dark:bg-gray-950">
    <div class="max-w-5xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-16">
            <div class="inline-flex items-center gap-2 bg-emerald-50 text-emerald-700 text-xs font-bold px-4 py-1.5 rounded-full mb-4 uppercase tracking-wider">Nos valeurs</div>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 dark:text-white tracking-tight">Ce qui nous guide au quotidien</h2>
        </div>

        <div class="grid sm:grid-cols-3 gap-6 lg:gap-8">
            @foreach([
                ['from-primary-500 to-violet-600', 'M13 10V3L4 14h7v7l9-11h-7z', 'Simplicité', 'Chaque fonctionnalité est pensée pour être utilisée en quelques secondes, même sans formation.'],
                ['from-secondary-500 to-pink-600', 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'Local d\'abord', 'Conçu pour la réalité ivoirienne : Mobile Money, FCFA, interface en français.'],
                ['from-emerald-500 to-teal-600', 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', 'Sécurité', 'Vos données sont chiffrées, hébergées en sécurité et jamais partagées à des tiers.'],
            ] as [$gradient, $icon, $title, $desc])
            <div class="group bg-white/70 dark:bg-gray-800 rounded-2xl p-7 border border-primary-100/60 dark:border-gray-700 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-500 text-center">
                <div class="w-14 h-14 bg-gradient-to-br {{ $gradient }} rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-lg shadow-gray-200/50 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $icon }}"/></svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-2">{{ $title }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">{{ $desc }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══ CTA ═══ --}}
<section class="relative py-20 overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-primary-600 via-primary-700 to-secondary-700"></div>
    <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 30px 30px;"></div>

    <div class="relative max-w-2xl mx-auto px-4 sm:px-6 text-center">
        <h2 class="text-3xl sm:text-4xl font-extrabold text-white mb-5 leading-tight">Envie d'en savoir plus ?</h2>
        <p class="text-white/70 text-lg mb-8">Testez gratuitement pendant 14 jours, sans engagement.</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('inscription') }}" class="group inline-flex items-center justify-center gap-2.5 bg-white text-gray-900 hover:bg-gray-50 px-7 py-3.5 rounded-2xl font-bold text-base shadow-2xl transition-all duration-300 hover:-translate-y-0.5">
                Créer mon compte
                <svg class="w-5 h-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
            <a href="{{ route('contact') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-2xl font-semibold text-white/80 border border-white/20 hover:bg-white/10 transition-all duration-300">
                Nous contacter
            </a>
        </div>
    </div>
</section>

</x-landing-layout>
