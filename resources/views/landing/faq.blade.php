<x-landing-layout :title="$title ?? null" :meta-description="$metaDescription ?? null">

{{-- ═══ HERO HEADER ═══ --}}
<section class="relative pt-32 pb-20 overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-gray-950 via-primary-950 to-gray-900"></div>
    <div class="absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 40px 40px;"></div>
    <div class="absolute -top-20 -left-40 w-96 h-96 bg-secondary-500/20 rounded-full blur-[80px]"></div>

    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 text-center animate-fade-in-up">
        <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/15 text-white/80 text-xs font-bold px-4 py-1.5 rounded-full mb-6 uppercase tracking-wider">FAQ</div>
        <h1 class="text-4xl sm:text-5xl font-extrabold text-white tracking-tight">Tout ce que vous <span class="shimmer-text">voulez savoir</span></h1>
        <p class="text-lg text-white/50 mt-5">Vous ne trouvez pas votre réponse ? <a href="{{ route('contact') }}" class="text-white/80 hover:text-white underline underline-offset-4 decoration-white/30 hover:decoration-white/60 transition-colors font-medium">Contactez-nous</a></p>
    </div>

    <div class="hero-fade"></div>
</section>

{{-- ═══ CATÉGORIES FAQ ═══ --}}
<section class="py-20 bg-primary-50/30 dark:bg-gray-900">
    <div class="max-w-3xl mx-auto px-4 sm:px-6">

        @php
        $categories = [
            ['Démarrage & Inscription', 'from-primary-500 to-violet-600', 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z', [
                ['Est-ce que je peux essayer gratuitement ?', 'Oui ! À l\'inscription, vous bénéficiez de <strong>14 jours d\'essai gratuit</strong> avec accès à toutes les fonctionnalités de base. Pendant cet essai, la limite est de <strong>1 employé(e)</strong>. Après cette période, choisissez un plan payant pour continuer.'],
                ['Comment créer mon compte ?', 'Cliquez sur « Créer mon compte », renseignez votre nom, email, mot de passe et les informations de votre établissement (nom, ville). Votre compte sera actif immédiatement avec la période d\'essai gratuit de 14 jours.'],
                ['Combien de temps prend la configuration ?', 'Moins de 2 minutes ! Après l\'inscription, ajoutez vos prestations, vos produits et invitez votre équipe. L\'interface est intuitive, aucune formation n\'est nécessaire.'],
            ]],
            ['Abonnements & Paiement', 'from-secondary-500 to-pink-600', 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', [
                ['Quels sont les plans disponibles ?', 'Nous proposons 4 plans :<br>• <strong>Essai gratuit</strong> — 0 FCFA/mois (14 jours, 1 employé, toutes les fonctionnalités)<br>• <strong>Basic</strong> — 2 000 FCFA/mois (1 établissement, 0 employé, caisse simple)<br>• <strong>Premium</strong> — 4 900 FCFA/mois (1 établissement, jusqu\'à 3 employés, caisse illimitée, agenda & RDV, stock)<br>• <strong>Premium+</strong> — 9 900 FCFA/mois (établissements &amp; employés illimités, toutes les fonctionnalités)<br>Des réductions sont disponibles : <strong>−10% en annuel</strong> et <strong>−20% en triennal</strong>.'],
                ['Comment fonctionne le paiement ?', 'Le paiement se fait par <strong>transfert Mobile Money</strong> (Orange Money, Wave, MTN Money). Vous envoyez le montant, joignez la référence ou capture de la transaction, et notre équipe valide votre abonnement sous 24h.'],
                ['Que se passe-t-il si mon abonnement expire ?', 'Votre accès sera <strong>restreint</strong> : vous ne pourrez plus enregistrer de nouvelles ventes, ajouter des clients ou modifier vos données. Pour reprendre l\'activité complète, il suffit de <strong>renouveler votre abonnement</strong>.'],
                ['Puis-je changer de plan ?', 'Oui ! Vous pouvez <strong>passer à un plan supérieur</strong> (mise à niveau) à tout moment depuis la page « Plans ». La rétrogradation vers un plan inférieur n\'est pas disponible.'],
            ]],
            ['Fonctionnalités', 'from-emerald-500 to-teal-600', 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z', [
                ['Combien d\'employés puis-je ajouter ?', 'Cela dépend de votre plan :<br>• <strong>Essai gratuit</strong> : 1 employé<br>• <strong>Basic</strong> : 0 employé (plan solo)<br>• <strong>Premium</strong> : jusqu\'à 3 employés<br>• <strong>Premium+</strong> : employés illimités<br>Chaque membre de l\'équipe a ses propres identifiants et un accès limité selon votre configuration.'],
                ['L\'application fonctionne-t-elle sur mobile ?', 'Oui ! Maëlya Gestion est conçue <strong>mobile-first</strong>. L\'interface est entièrement responsive et optimisée pour une utilisation confortable sur smartphone Android ou iPhone, même avec un petit écran.'],
                ['Comment imprimer les tickets de caisse ?', 'Connectez une imprimante thermique Bluetooth ou WiFi à votre appareil. Maëlya Gestion génère des <strong>tickets en PDF</strong> que vous pouvez imprimer en un clic. Compatible avec la plupart des imprimantes de caisse.'],
                ['Puis-je gérer plusieurs établissements ?', 'Oui, avec le plan <strong>Premium+</strong> ! Vous pouvez créer et gérer plusieurs établissements depuis le même compte, chacun avec ses propres employés, produits et statistiques.'],
            ]],
            ['Technique & Sécurité', 'from-amber-500 to-orange-600', 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', [
                ['Mes données sont-elles en sécurité ?', 'Absolument. Toutes les communications sont <strong>chiffrées en HTTPS</strong>. Vos données sont hébergées sur des serveurs sécurisés et ne sont <strong>jamais partagées avec des tiers</strong>. Des sauvegardes automatiques protègent vos informations.'],
                ['L\'application fonctionne-t-elle sans internet ?', 'Une connexion internet est nécessaire pour utiliser Maëlya Gestion. Cependant, l\'application est <strong>optimisée pour les connexions modestes</strong> courantes en Côte d\'Ivoire. Les pages sont légères et rapides à charger.'],
                ['Dans quelle langue est l\'application ?', 'L\'application est <strong>100% en français</strong>. Tous les menus, messages, rapports et documents sont en français pour faciliter l\'utilisation par les professionnels de la beauté en Côte d\'Ivoire.'],
            ]],
        ];
        @endphp

        <div class="space-y-10" x-data="{ openItem: null }">
            @foreach($categories as $catIdx => [$catTitle, $gradient, $icon, $questions])
            <div>
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 bg-gradient-to-br {{ $gradient }} rounded-xl flex items-center justify-center shadow-lg shadow-gray-200/50">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $icon }}"/></svg>
                    </div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ $catTitle }}</h2>
                </div>

                <div class="space-y-3">
                    @foreach($questions as $qIdx => [$q, $a])
                    @php $key = $catIdx . '_' . $qIdx; @endphp
                    <div class="bg-primary-50/30 dark:bg-gray-800/50 rounded-2xl border border-primary-100/60 dark:border-gray-700 shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-300">
                        <button
                            class="w-full flex items-center justify-between p-5 text-left focus:outline-none focus:ring-2 focus:ring-primary-500/20 rounded-2xl transition-colors"
                            @click="openItem === '{{ $key }}' ? openItem = null : openItem = '{{ $key }}'"
                            :aria-expanded="openItem === '{{ $key }}'"
                        >
                            <span class="font-semibold text-gray-900 dark:text-white pr-4 text-sm">{{ $q }}</span>
                            <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 transition-colors duration-200"
                                 :class="openItem === '{{ $key }}' ? 'bg-primary-100 text-primary-600' : 'bg-gray-100 text-gray-400'">
                                <svg class="w-4 h-4 transition-transform duration-300"
                                     :class="openItem === '{{ $key }}' ? 'rotate-180' : ''"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </button>
                        <div
                            x-show="openItem === '{{ $key }}'"
                            x-cloak
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                            class="px-5 pb-5"
                        >
                            <div class="text-gray-600 dark:text-gray-300 text-sm leading-relaxed border-t border-gray-100 dark:border-gray-700 pt-4">{!! $a !!}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>

        {{-- CTA --}}
        <div class="mt-16 text-center bg-gradient-to-br from-primary-50/50 to-primary-100/30 dark:from-gray-800 dark:to-gray-800 rounded-3xl p-10 border border-primary-100/60 dark:border-gray-700">
            <div class="w-14 h-14 bg-gradient-to-br from-primary-500 to-secondary-500 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-xl shadow-primary-200/50">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Vous avez une autre question ?</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6 text-sm">Notre équipe vous répond sous 24 heures.</p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('contact') }}" class="group inline-flex items-center justify-center gap-2 px-6 py-3 rounded-2xl text-sm font-bold text-white shadow-xl shadow-primary-200/50 transition-all duration-300 hover:shadow-2xl hover:-translate-y-0.5"
                   style="background: linear-gradient(135deg, #9333ea 0%, #ec4899 100%);">
                    Nous contacter
                    <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
                <a href="{{ route('inscription') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-2xl text-sm font-semibold text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-300">
                    Créer mon compte
                </a>
            </div>
        </div>
    </div>
</section>

@push('jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
        @php $allQuestions = collect($categories)->flatMap(fn($c) => collect($c[3])); @endphp
        @foreach($allQuestions as $i => $qa)
        {
            "@@type": "Question",
            "name": @json($qa[0]),
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": @json(strip_tags($qa[1]))
            }
        }@if(!$loop->last),@endif
        @endforeach
    ]
}
</script>
@endpush

</x-landing-layout>
