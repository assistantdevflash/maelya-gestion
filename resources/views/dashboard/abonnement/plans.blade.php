<x-dashboard-layout>
    <div class="max-w-5xl mx-auto space-y-8 py-4">

        {{-- En-tête --}}
        <div class="text-center">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary-50 text-primary-700 text-xs font-semibold">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                Abonnement
            </span>
            <h1 class="text-3xl font-bold text-gray-900 mt-4">Choisissez votre formule</h1>
            <p class="text-gray-500 mt-2 max-w-lg mx-auto">Accédez à toutes les fonctionnalités de Maëlya Gestion pour gérer votre établissement en toute simplicité.</p>
            <a href="{{ route('abonnement.historique') }}" class="inline-flex items-center gap-1.5 text-sm text-primary-600 hover:text-primary-700 font-medium mt-3 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Voir l'historique de mes transactions
            </a>
        </div>

        @if(session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert-danger">{{ session('error') }}</div>
        @endif

        {{-- Bonus parrainage filleul --}}
        @if(Auth::user()->parraine_par && Auth::user()->parrainageRecu && Auth::user()->parrainageRecu->statut === 'en_attente')
        <div class="card p-4 bg-purple-50 border-purple-200 flex items-start gap-3">
            <div class="w-9 h-9 bg-purple-100 rounded-full flex items-center justify-center text-purple-600 flex-shrink-0 mt-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>
            </div>
            <div>
                <p class="font-semibold text-purple-900">🎁 Bonus parrainage : +{{ Auth::user()->parrainageRecu->jours_offerts_filleul }} jours offerts !</p>
                <p class="text-sm text-purple-700 mt-0.5">Vous avez été parrainé par <strong>{{ Auth::user()->parrain->nom_complet ?? Auth::user()->parrain->name }}</strong>. En souscrivant à un abonnement payant, vous bénéficierez automatiquement de <strong>{{ Auth::user()->parrainageRecu->jours_offerts_filleul }} jours gratuits supplémentaires</strong>.</p>
            </div>
        </div>
        @endif

        {{-- Abonnement actif --}}
        @if($abonnementActif)
        <div class="card p-5 bg-emerald-50 border-emerald-200 flex items-center gap-4">
            <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600 flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="font-semibold text-emerald-900">Abonnement actif : {{ $abonnementActif->plan->nom }}</p>
                <p class="text-sm text-emerald-700">Expire le {{ $abonnementActif->expire_le->format('d/m/Y') }} ({{ $abonnementActif->joursRestants() }} jours restants)</p>
            </div>
        </div>
        @endif

        {{-- Demande en attente --}}
        @if($demandeEnAttente)
        <div class="card p-5 bg-amber-50 border-amber-200 flex items-center gap-4">
            <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center text-amber-600 flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="font-semibold text-amber-900">Demande en attente de validation</p>
                <p class="text-sm text-amber-700">Plan {{ $demandeEnAttente->plan->nom }} — {{ number_format($demandeEnAttente->montant, 0, ',', ' ') }} FCFA — envoyée le {{ $demandeEnAttente->created_at->format('d/m/Y') }}</p>
            </div>
        </div>
        @endif

        {{-- Toggle période --}}
        <div x-data="pricingToggle()" class="space-y-8">
            <div class="flex justify-center">
                <div class="inline-flex items-center bg-gray-100 rounded-xl p-1 gap-1">
                    <button @click="periode = 'mensuel'" :class="periode === 'mensuel' ? 'bg-white shadow-sm text-gray-900 font-semibold' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-2 rounded-lg text-sm transition-all">
                        Mensuel
                    </button>
                    <button @click="periode = 'annuel'" :class="periode === 'annuel' ? 'bg-white shadow-sm text-gray-900 font-semibold' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-2 rounded-lg text-sm transition-all relative">
                        1 an
                        <span class="absolute -top-2 -right-2 bg-emerald-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full">-10%</span>
                    </button>
                    <button @click="periode = 'triennal'" :class="periode === 'triennal' ? 'bg-white shadow-sm text-gray-900 font-semibold' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-2 rounded-lg text-sm transition-all relative">
                        3 ans
                        <span class="absolute -top-2 -right-2 bg-emerald-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full">-20%</span>
                    </button>
                </div>
            </div>

            {{-- Cartes plans --}}
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-5 max-w-6xl mx-auto">
                @foreach($plans as $plan)
                @php
                    $estPlanActuel = $abonnementActif && $abonnementActif->plan_id === $plan->id;
                    $estMiseANiveau = $abonnementActif && !$estPlanActuel && $plan->prix > $abonnementActif->plan->prix;
                    $estDowngrade = $abonnementActif && !$estPlanActuel && $plan->prix < $abonnementActif->plan->prix;
                @endphp
                <div class="relative card overflow-visible flex flex-col {{ $plan->mis_en_avant ? 'border-2 border-primary-400 scale-[1.02]' : '' }} {{ $estPlanActuel ? 'ring-2 ring-emerald-400' : '' }}">
                    @if($estPlanActuel)
                    <div class="absolute -top-4 left-0 right-0 flex justify-center z-10">
                        <span class="bg-emerald-500 text-white text-xs font-bold px-4 py-1.5 rounded-full shadow-lg flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            Plan actuel
                        </span>
                    </div>
                    @elseif($plan->mis_en_avant && !$estPlanActuel)
                    <div class="absolute -top-4 left-0 right-0 flex justify-center">
                        <span class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white text-xs font-bold px-4 py-1.5 rounded-full shadow-lg">
                            Recommandé
                        </span>
                    </div>
                    @endif

                    <div class="p-6 flex-1">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center {{ $plan->mis_en_avant ? 'bg-gradient-to-br from-primary-500 to-secondary-500 text-white' : 'bg-primary-50 text-primary-600' }}">
                                @if($plan->mis_en_avant)
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                @else
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                                @endif
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900">{{ $plan->nom }}</h3>
                                @if($plan->description)
                                <p class="text-xs text-gray-500">{{ $plan->description }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="mb-6">
                            @php $offrePlan = $plan->meilleureOffre(); @endphp
                            @if($offrePlan)
                            <div class="flex items-center gap-1.5 mb-2">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-gradient-to-r {{ $offrePlan->badge_class }} text-white text-[10px] font-bold uppercase tracking-wide animate-pulse">
                                    {{ $offrePlan->badge_texte }}
                                </span>
                                <span class="text-[10px] text-gray-400">jusqu'au {{ $offrePlan->date_fin->format('d/m/Y') }}</span>
                            </div>
                            @endif
                            <div class="flex items-baseline gap-1">
                                <span class="text-3xl sm:text-4xl font-bold whitespace-nowrap {{ $plan->mis_en_avant ? 'gradient-text' : 'text-gray-900' }}"
                                      x-text="formatPrice({{ $plan->prixEffectif() }}, {{ $plan->prixPourPeriode('annuel') }}, {{ $plan->prixPourPeriode('triennal') }})">
                                    {{ number_format($plan->prixEffectif(), 0, ',', "\u{00A0}") }}
                                </span>
                                <span class="text-gray-400 text-sm" x-text="periodeLabel()">FCFA / mois</span>
                            </div>
                            @if($offrePlan)
                            <p class="text-xs text-gray-400 mt-0.5">
                                au lieu de <span class="line-through">{{ number_format($plan->prix, 0, ',', ' ') }} FCFA</span>
                            </p>
                            @endif
                            <template x-if="periode !== 'mensuel'">
                                <p class="text-xs text-emerald-600 font-medium mt-1">
                                    <span x-text="'Soit ' + formatTotal({{ $plan->prixEffectif() }}, {{ $plan->prixPourPeriode('annuel') }}, {{ $plan->prixPourPeriode('triennal') }}) + ' FCFA au total'"></span>
                                    <span class="line-through text-gray-400 ml-1" x-text="formatSans({{ $plan->prix }})"></span>
                                </p>
                            </template>
                        </div>

                        <ul class="space-y-2.5 text-sm text-gray-600">
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                {{ $plan->max_instituts === null ? 'Établissements illimités' : $plan->max_instituts . ' établissement' }}
                            </li>
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                {{ $plan->max_employes === null ? 'Employés illimités' : $plan->max_employes . ' employé(s)' }}
                            </li>
                            @if($plan->slug === 'basic')
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                Caisse simple
                            </li>
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                Catalogue prestations
                            </li>
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                Historique des ventes
                            </li>
                            <li class="flex items-center gap-2.5 text-gray-300">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                <span class="line-through">Stock, clients, finances</span>
                            </li>
                            @else
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                Caisse illimitée
                            </li>
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                Gestion stock & clients
                            </li>
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                Rapports financiers
                            </li>
                            @if($plan->slug === 'premium-plus' || $plan->slug === 'ultra')
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                Vente a credit & echeanciers
                            </li>
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span class="font-medium text-gray-900">Support prioritaire</span>
                            </li>
                            @endif
                            @endif
                        </ul>
                    </div>

                    <div class="p-6 pt-0">
                        @if($demandeEnAttente)
                            <button disabled class="btn-secondary w-full justify-center opacity-50 cursor-not-allowed">Demande en cours...</button>
                        @elseif($estPlanActuel && $abonnementActif->joursRestants() <= 7)
                            <a href="{{ route('abonnement.souscrire.show', $plan) }}?periode=mensuel"
                               class="w-full justify-center inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition-all"
                               style="background:linear-gradient(135deg,#9333ea,#ec4899);">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Renouveler
                            </a>
                        @elseif($estPlanActuel)
                            <button disabled class="btn-secondary w-full justify-center opacity-50 cursor-not-allowed">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Votre plan actuel
                            </button>
                        @elseif($estMiseANiveau)
                            <a href="{{ route('abonnement.souscrire.show', $plan) }}?periode=mensuel"
                               class="btn-primary w-full justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                Mise à niveau
                            </a>
                        @elseif($estDowngrade)
                            <button disabled class="btn-secondary w-full justify-center opacity-40 cursor-not-allowed text-xs">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                Rétrogradation non disponible
                            </button>
                        @else
                            <a href="{{ route('abonnement.souscrire.show', $plan) }}?periode=mensuel"
                               class="{{ $plan->slug === 'premium-plus' ? 'btn-primary' : 'btn-outline' }} w-full justify-center">
                                S'abonner
                            </a>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

        </div>

        <p class="text-center text-sm text-gray-400">
            Paiement par transfert mobile (Orange Money ou Wave).
            <br>Votre abonnement est activé sous 24h après vérification.
        </p>
    </div>

    <x-slot:scripts>
    <script>
    function pricingToggle() {
        return {
            periode: 'mensuel',
            optionBoutique: {{ request('ajouter') === 'boutique' ? 'true' : 'false' }},

            goSouscrire(planId) {
                let url = '/abonnement/souscrire/' + planId + '?periode=' + this.periode;
                if (this.optionBoutique) url += '&ajouter=boutique';
                window.location.href = url;
            },

            formatPrice(mensuel, annuel, triennal) {
                let total = this.periode === 'annuel' ? annuel : this.periode === 'triennal' ? triennal : mensuel;
                if (this.periode === 'annuel') total = Math.round(total / 12);
                if (this.periode === 'triennal') total = Math.round(total / 36);
                return new Intl.NumberFormat('fr-FR').format(total);
            },

            formatTotal(mensuel, annuel, triennal) {
                let total = this.periode === 'annuel' ? annuel : this.periode === 'triennal' ? triennal : mensuel;
                return new Intl.NumberFormat('fr-FR').format(total);
            },

            formatSans(mensuel) {
                let mois = this.periode === 'annuel' ? 12 : 36;
                return new Intl.NumberFormat('fr-FR').format(mensuel * mois) + ' FCFA';
            }
        }
    }
    </script>
    </x-slot:scripts>
</x-dashboard-layout>
