<x-landing-layout :title="$title ?? null" :meta-description="$metaDescription ?? null" :noindex="$noindex ?? false">

{{-- ═══ HERO HEADER ═══ --}}
<section class="relative pt-32 pb-20 overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-gray-950 via-gray-900 to-gray-950"></div>
    <div class="absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 40px 40px;"></div>

    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 text-center animate-fade-in-up">
        <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/15 text-white/80 text-xs font-bold px-4 py-1.5 rounded-full mb-6 uppercase tracking-wider">Légal</div>
        <h1 class="text-4xl sm:text-5xl font-extrabold text-white tracking-tight">Mentions légales</h1>
        <p class="text-lg text-white/40 mt-4">Dernière mise à jour : {{ now()->format('d/m/Y') }}</p>
    </div>

    <div class="hero-fade"></div>
</section>

{{-- ═══ CONTENU ═══ --}}
<section class="py-20 bg-primary-50/30 dark:bg-gray-900">
    <div class="max-w-3xl mx-auto px-4 sm:px-6">
        <div class="space-y-8">
            @foreach([
                ['from-primary-500 to-violet-600', 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', '1. Éditeur du site', '
                    <p>Le site <strong>maelyagestion.com</strong> est édité par la société <strong>Maëlya Tech SARL</strong>,
                    enregistrée en Côte d\'Ivoire, dont le siège social est situé à Abidjan.</p>
                    <p class="mt-2">Contact : <strong>contact@maelyagestion.com</strong></p>
                '],
                ['from-secondary-500 to-pink-600', 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01', '2. Hébergement', '
                    <p>Le site est hébergé sur des serveurs sécurisés. Les données sont conservées en Afrique et/ou en Europe
                    selon les exigences de sécurité et de performance.</p>
                '],
                ['from-emerald-500 to-teal-600', 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', '3. Protection des données personnelles', '
                    <p>Maëlya Gestion collecte des données personnelles nécessaires au fonctionnement du service (nom, email, téléphone,
                    données de gestion d\'établissement). Ces données ne sont <strong>jamais revendues à des tiers</strong>.</p>
                    <p class="mt-2">Conformément à la législation applicable, vous disposez d\'un droit d\'accès, de rectification et de suppression
                    de vos données. Pour exercer ce droit, contactez-nous à <strong>contact@maelyagestion.com</strong>.</p>
                '],
                ['from-amber-500 to-orange-600', 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', '4. Cookies', '
                    <p>Ce site utilise des cookies techniques indispensables au bon fonctionnement du service (session, sécurité).
                    <strong>Aucun cookie de tracking ou publicitaire</strong> n\'est utilisé sans votre consentement.</p>
                '],
                ['from-blue-500 to-indigo-600', 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', '5. Propriété intellectuelle', '
                    <p>L\'ensemble des contenus du site (textes, logos, images, code) est la <strong>propriété exclusive de Maëlya Tech SARL</strong>.
                    Toute reproduction sans autorisation préalable est interdite.</p>
                '],
                ['from-rose-500 to-red-500', 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', '6. Limitation de responsabilité', '
                    <p>Maëlya Gestion ne peut être tenu responsable des dommages résultant d\'une utilisation de ce site ou d\'une
                    indisponibilité temporaire du service. Nous nous efforçons d\'assurer une <strong>disponibilité maximale</strong>.</p>
                '],
            ] as [$gradient, $icon, $title, $content])
            <div class="group bg-primary-50/30 dark:bg-gray-800/50 rounded-2xl p-6 border border-primary-100/60 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow duration-300">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-gradient-to-br {{ $gradient }} rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg shadow-gray-200/50">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $icon }}"/></svg>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-base font-bold text-gray-900 dark:text-white mb-3">{{ $title }}</h2>
                        <div class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">{!! $content !!}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-12 text-center">
            <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-primary-600 hover:text-primary-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Une question sur ces mentions ? Contactez-nous
            </a>
        </div>
    </div>
</section>

</x-landing-layout>
