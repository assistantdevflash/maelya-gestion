<x-landing-layout :title="$title" :meta-description="$metaDescription">

@push('jsonld')
<script type="application/ld+json">
{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
</script>
@endpush

@php
    $points = $contenu['points'] ?? [];
    $emoji = $contenu['emoji'] ?? '🏢';

    $fonctionnalites = [
        ['Caisse rapide', 'Encaissez une prestation en moins de 30 secondes. Espèces, Mobile Money, carte ou paiement mixte, avec ticket remis au client.'],
        ['Clients et historique', 'Chaque client garde son historique : visites, achats, crédits en cours et préférences.'],
        ['Rendez-vous', 'Un agenda partagé entre employés, avec confirmation et rappel automatique envoyés au client.'],
        ['Stock et alertes', 'Suivez vos produits et vos consommables, avec une alerte avant la rupture.'],
        ['Finances', 'Chiffre d\'affaires, dépenses, marge et résultat du mois, calculés sans tableur.'],
        ['Équipe', 'Chaque employé se connecte avec ses propres identifiants et un accès limité à son rôle.'],
    ];

    $questions = [];

    if (filled($contenu['question'] ?? null) && filled($contenu['reponse'] ?? null)) {
        $questions[] = [$contenu['question'], $contenu['reponse']];
    }

    foreach ($questionsCommunes as [$question, $reponse]) {
        $questions[] = [$question, $reponse];
    }
@endphp

{{-- ═══ HERO ═══ --}}
<section class="relative overflow-hidden bg-gradient-to-br from-gray-950 via-primary-950 to-gray-900">
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-[500px] h-[500px] bg-primary-500/15 rounded-full blur-[100px] animate-float"></div>
        <div class="absolute -bottom-40 -left-40 w-[400px] h-[400px] bg-secondary-500/15 rounded-full blur-[100px] animate-float-delayed"></div>
    </div>

    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 pt-28 pb-20 lg:pt-32 lg:pb-24 w-full">

        <nav aria-label="Fil d'Ariane" class="mb-8 text-sm text-white/40">
            <a href="{{ route('home') }}" class="hover:text-white transition-colors">Accueil</a>
            <span class="mx-2" aria-hidden="true">/</span>
            <span class="text-white/70">{{ $type->libelle }}</span>
        </nav>

        <div class="inline-flex items-center gap-2.5 bg-white/10 border border-white/15 text-white text-xs font-bold px-4 py-2 rounded-full mb-6 uppercase tracking-wider">
            <span class="text-base leading-none" aria-hidden="true">{{ $emoji }}</span>
            {{ $type->libelle }}
        </div>

        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold leading-[1.15] text-white mb-6 tracking-tight">
            {{ $titre }}
        </h1>

        <p class="text-lg text-white/60 mb-10 leading-relaxed max-w-2xl">
            {{ $contenu['intro'] ?? '' }}
        </p>

        <div class="flex flex-col sm:flex-row gap-4">
            <a href="{{ route('inscription') }}" class="group inline-flex items-center justify-center gap-2.5 px-7 py-3.5 rounded-2xl text-base font-bold text-white shadow-xl shadow-primary-900/40 transition-all duration-300 hover:shadow-2xl hover:-translate-y-0.5"
               style="background: linear-gradient(135deg, #9333ea 0%, #ec4899 100%);">
                Essayer gratuitement
                <svg class="w-5 h-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
            <a href="{{ route('contact') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-2xl text-base font-semibold text-white/80 border border-white/20 hover:bg-white/10 hover:border-white/30 transition-all duration-300">
                Poser une question
            </a>
        </div>

        <div class="mt-10 flex flex-wrap items-center gap-x-8 gap-y-3">
            @foreach(['14 jours d\'essai gratuit', 'Sans engagement', '100% en français'] as $badge)
            <div class="flex items-center gap-2 text-sm text-white/50">
                <svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                {{ $badge }}
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══ CE QUE ÇA CHANGE ═══ --}}
<section class="py-24 bg-white dark:bg-gray-950">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-14">
            <div class="inline-flex items-center gap-2 bg-primary-50 text-primary-700 text-xs font-bold px-4 py-1.5 rounded-full mb-4 uppercase tracking-wider">Concrètement</div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                Ce que Maëlya Gestion change au quotidien
            </h2>
        </div>

        <ul class="grid sm:grid-cols-2 gap-4 sm:gap-5">
            @foreach($points as $point)
            <li class="flex items-start gap-3 bg-gradient-to-br from-primary-50/60 to-secondary-50/30 dark:from-gray-800 dark:to-gray-800/50 rounded-2xl p-5 border border-primary-100/60 dark:border-gray-700">
                <div class="w-6 h-6 bg-emerald-100 dark:bg-emerald-900/40 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                    <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                </div>
                <span class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ $point }}</span>
            </li>
            @endforeach
        </ul>
    </div>
</section>

{{-- ═══ FONCTIONNALITÉS ═══ --}}
<section class="py-24 bg-gradient-to-b from-primary-50/30 to-primary-50/60 dark:from-gray-950 dark:to-gray-900">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-14">
            <div class="inline-flex items-center gap-2 bg-primary-50 text-primary-700 text-xs font-bold px-4 py-1.5 rounded-full mb-4 uppercase tracking-wider">Fonctionnalités</div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                Tout est déjà inclus, dès le premier jour
            </h2>
            <p class="text-gray-500 dark:text-gray-400 mt-4 max-w-2xl mx-auto">
                Pas d'option à cocher, pas de module à installer : les fonctions essentielles sont disponibles dès l'inscription.
            </p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($fonctionnalites as [$titreFonction, $texteFonction])
            <div class="bg-white/70 dark:bg-gray-800 rounded-2xl p-6 border border-primary-100/60 dark:border-gray-700 shadow-sm hover:shadow-lg transition-shadow duration-300">
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-2">{{ $titreFonction }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">{{ $texteFonction }}</p>
            </div>
            @endforeach
        </div>

        <div class="mt-12 text-center">
            <a href="{{ route('inscription') }}" class="inline-flex items-center gap-2 text-primary-600 dark:text-primary-400 font-semibold hover:gap-3 transition-all">
                Créer mon compte gratuitement
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
        </div>
    </div>
</section>

{{-- ═══ AUTRES SECTEURS ═══ --}}
@if($autres->isNotEmpty())
<section class="py-24 bg-white dark:bg-gray-950">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-12">
            <div class="inline-flex items-center gap-2 bg-secondary-50 text-secondary-700 text-xs font-bold px-4 py-1.5 rounded-full mb-4 uppercase tracking-wider">Aussi disponible</div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                Maëlya Gestion s'adapte aussi à
            </h2>
        </div>

        <div class="flex flex-wrap justify-center gap-2.5">
            @foreach($autres as $autre)
            <a href="{{ route('secteur', $autre->slug) }}"
               class="inline-flex items-center gap-2 bg-primary-50/70 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm font-medium px-4 py-2.5 rounded-xl border border-primary-100/60 dark:border-gray-700 hover:text-primary-700 dark:hover:text-primary-300 hover:border-primary-300 hover:-translate-y-0.5 transition-all duration-200">
                <span aria-hidden="true">{{ config('secteurs.' . $autre->code . '.emoji', '🏢') }}</span>
                {{ $autre->libelle }}
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ═══ QUESTIONS FRÉQUENTES ═══ --}}
<section class="py-24 bg-primary-50/20 dark:bg-gray-950">
    <div class="max-w-3xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-12">
            <div class="inline-flex items-center gap-2 bg-emerald-50 text-emerald-700 text-xs font-bold px-4 py-1.5 rounded-full mb-4 uppercase tracking-wider">Questions fréquentes</div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                Vos questions sur Maëlya Gestion
            </h2>
        </div>

        <div class="space-y-3">
            @foreach($questions as $index => [$question, $reponse])
            <details class="group bg-white dark:bg-gray-800 rounded-2xl border border-primary-100/60 dark:border-gray-700 overflow-hidden" @if($index === 0) open @endif>
                <summary class="flex items-center justify-between gap-4 px-5 py-4 cursor-pointer list-none">
                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $question }}</span>
                    <svg class="w-4 h-4 text-primary-500 flex-shrink-0 transition-transform duration-200 group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="px-5 pb-5 -mt-1 text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                    {{ $reponse }}
                </div>
            </details>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══ CTA FINAL ═══ --}}
<section class="relative py-24 overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-primary-600 via-primary-700 to-secondary-700"></div>
    <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 30px 30px;"></div>

    <div class="relative max-w-3xl mx-auto px-4 sm:px-6 text-center">
        <h2 class="text-3xl sm:text-4xl font-extrabold text-white mb-6 leading-tight">
            Prêt(e) à reprendre la main sur votre gestion ?
        </h2>
        <p class="text-white/70 text-lg mb-10 max-w-xl mx-auto leading-relaxed">
            Essai gratuit de 14 jours, sans engagement. Vous gardez vos données si vous décidez de rester.
        </p>
        <a href="{{ route('inscription') }}" class="group inline-flex items-center gap-3 bg-white text-gray-900 hover:bg-gray-50 px-8 py-4 rounded-2xl font-bold text-base shadow-2xl transition-all duration-300 hover:-translate-y-1">
            Créer mon compte gratuitement
            <svg class="w-5 h-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
        </a>
    </div>
</section>

</x-landing-layout>
