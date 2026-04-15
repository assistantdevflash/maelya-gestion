<x-dashboard-layout>
    <div class="max-w-5xl mx-auto space-y-8 py-4">

        {{-- En-tête --}}
        <div class="text-center">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary-50 text-primary-700 text-xs font-semibold">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                Abonnement
            </span>
            <h1 class="text-3xl font-bold text-gray-900 mt-4">Choisissez votre formule</h1>
            <p class="text-gray-500 mt-2 max-w-lg mx-auto">Accédez à toutes les fonctionnalités de Maëlya Gestion pour gérer votre institut en toute simplicité.</p>
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
            <div class="grid md:grid-cols-2 gap-6 max-w-3xl mx-auto">
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
                            <div class="flex items-baseline gap-1">
                                <span class="text-4xl font-bold {{ $plan->mis_en_avant ? 'gradient-text' : 'text-gray-900' }}"
                                      x-text="formatPrice({{ $plan->prix }}, {{ $plan->prixPourPeriode('annuel') }}, {{ $plan->prixPourPeriode('triennal') }})">
                                    {{ number_format($plan->prix, 0, ',', ' ') }}
                                </span>
                                <span class="text-gray-400 text-sm" x-text="periodeLabel()">FCFA / mois</span>
                            </div>
                            <template x-if="periode !== 'mensuel'">
                                <p class="text-xs text-emerald-600 font-medium mt-1">
                                    <span x-text="'Soit ' + formatTotal({{ $plan->prix }}, {{ $plan->prixPourPeriode('annuel') }}, {{ $plan->prixPourPeriode('triennal') }}) + ' FCFA au total'"></span>
                                    <span class="line-through text-gray-400 ml-1" x-text="formatSans({{ $plan->prix }})"></span>
                                </p>
                            </template>
                        </div>

                        <ul class="space-y-2.5 text-sm text-gray-600">
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                {{ $plan->max_instituts === null ? 'Instituts illimités' : $plan->max_instituts . ' institut' }}
                            </li>
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                {{ $plan->max_employes === null ? 'Employés illimités' : $plan->max_employes . ' employé(s)' }}
                            </li>
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
                            @if($plan->slug === 'entreprise')
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span class="font-medium text-gray-900">Support prioritaire</span>
                            </li>
                            @endif
                        </ul>
                    </div>

                    <div class="p-6 pt-0">
                        @if($demandeEnAttente)
                            <button disabled class="btn-secondary w-full justify-center opacity-50 cursor-not-allowed">Demande en cours...</button>
                        @elseif($estPlanActuel)
                            <button disabled class="btn-secondary w-full justify-center opacity-50 cursor-not-allowed">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Votre plan actuel
                            </button>
                        @elseif($estMiseANiveau)
                            <button @click="openModal('{{ $plan->id }}', '{{ $plan->nom }}', {{ $plan->prix }}, {{ $plan->prixPourPeriode('annuel') }}, {{ $plan->prixPourPeriode('triennal') }})"
                                    class="btn-primary w-full justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                Mise à niveau
                            </button>
                        @elseif($estDowngrade)
                            <button disabled class="btn-secondary w-full justify-center opacity-40 cursor-not-allowed text-xs">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                Rétrogradation non disponible
                            </button>
                        @else
                            <button @click="openModal('{{ $plan->id }}', '{{ $plan->nom }}', {{ $plan->prix }}, {{ $plan->prixPourPeriode('annuel') }}, {{ $plan->prixPourPeriode('triennal') }})"
                                    class="{{ $plan->slug === 'entreprise' ? 'btn-primary' : 'btn-outline' }} w-full justify-center">
                                S'abonner
                            </button>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Modal paiement par transfert (style TopResto) --}}
            <div x-show="showModal" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @keydown.escape.window="showModal = false">

                <div @click.outside="showModal = false"
                     class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100">

                    {{-- Header avec flèche retour --}}
                    <div class="px-5 pt-5 pb-3 flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <button @click="step = 1" x-show="step === 2" class="p-1 text-gray-400 hover:text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                            <div>
                                <h2 class="font-bold text-lg text-gray-900" x-text="step === 1 ? 'Instructions de transfert' : 'Preuve de paiement'"></h2>
                                <p class="text-sm text-gray-500 mt-0.5" x-show="step === 1">Effectuez le transfert selon les informations ci-dessous puis fournissez la preuve.</p>
                            </div>
                        </div>
                        <button @click="showModal = false; step = 1" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors flex-shrink-0 ml-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form :action="'/abonnement/souscrire/' + selectedPlanId" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="periode" :value="periode">

                        {{-- Étape 1 : Infos bénéficiaire --}}
                        <div x-show="step === 1" class="px-5 pb-5 space-y-4">

                            {{-- Sélecteur mode de paiement --}}
                            <div class="flex items-center gap-2 bg-gray-50 rounded-xl p-1">
                                <button type="button" @click="payMethod = 'om'"
                                        :class="payMethod === 'om' ? 'bg-white shadow-sm text-orange-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                                        class="flex-1 flex items-center justify-center gap-1.5 py-2 rounded-lg text-xs transition-all">
                                    <span class="font-bold">OM</span> Orange
                                </button>
                                <button type="button" @click="payMethod = 'wave'"
                                        :class="payMethod === 'wave' ? 'bg-white shadow-sm text-blue-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                                        class="flex-1 flex items-center justify-center gap-1.5 py-2 rounded-lg text-xs transition-all">
                                    <span class="font-bold">W</span> Wave
                                </button>
                            </div>

                            {{-- Carte bénéficiaire + montant --}}
                            <div class="bg-gray-50 rounded-xl overflow-hidden">
                                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                                    <span class="text-sm text-gray-500">Bénéficiaire</span>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-gray-900 font-mono tracking-wide">0709874067</span>
                                        <button type="button"
                                                @click="navigator.clipboard.writeText('0709874067'); copied = true; setTimeout(() => copied = false, 2000)"
                                                class="p-1 text-gray-400 hover:text-primary-600 rounded transition-colors" title="Copier">
                                            <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                            <svg x-show="copied" x-cloak class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between px-4 py-3">
                                    <span class="text-sm text-gray-500">Montant</span>
                                    <span class="text-lg font-bold text-primary-600" x-text="selectedTotal() + ' FCFA'"></span>
                                </div>
                            </div>

                            {{-- Nom du bénéficiaire --}}
                            <p class="text-xs text-gray-400 text-center -mt-2">Nom du bénéficiaire : <span class="font-medium text-gray-600">MAELYA GESTION</span></p>

                            {{-- Bannière info --}}
                            <div class="flex items-start gap-3 bg-blue-50 rounded-xl p-3.5">
                                <div class="w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-3 h-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                                </div>
                                <p class="text-xs text-blue-800 leading-relaxed">
                                    Effectuez le transfert au numéro ci-dessus, puis fournissez la preuve ci-dessous (référence ou reçu).
                                    <strong>Votre abonnement sera activé après vérification par un administrateur.</strong>
                                </p>
                            </div>

                            {{-- Référence --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Référence de votre transfert</label>
                                <input type="text" name="reference_transfert" class="form-input" placeholder="Ex: numéro de transaction">
                            </div>

                            {{-- Séparateur OU --}}
                            <div class="flex items-center gap-3">
                                <div class="flex-1 h-px bg-gray-200"></div>
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">ou</span>
                                <div class="flex-1 h-px bg-gray-200"></div>
                            </div>

                            {{-- Upload reçu --}}
                            <div x-data="{ fileName: '' }">
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Reçu de transfert (Image ou PDF)</label>
                                <label class="flex flex-col items-center justify-center px-4 py-5 border-2 border-dashed border-gray-200 rounded-xl hover:border-primary-300 cursor-pointer transition-colors bg-gray-50/50 hover:bg-gray-50">
                                    <template x-if="!fileName">
                                        <div class="text-center">
                                            <svg class="mx-auto w-8 h-8 text-gray-300 mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                            <p class="text-sm text-primary-600 font-medium">Cliquez pour sélectionner un fichier</p>
                                            <p class="text-xs text-gray-400 mt-0.5">JPEG, PNG, WebP ou PDF — max 10 Mo</p>
                                        </div>
                                    </template>
                                    <template x-if="fileName">
                                        <div class="flex items-center gap-2 text-sm text-emerald-700">
                                            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span class="font-medium truncate max-w-[250px]" x-text="fileName"></span>
                                        </div>
                                    </template>
                                    <input type="file" name="preuve_paiement" accept="image/*,.pdf" class="sr-only"
                                           @change="fileName = $event.target.files[0]?.name || ''">
                                </label>
                                <p class="text-xs text-gray-400 mt-1">Fournissez la référence <strong>OU</strong> le reçu — un seul suffit.</p>
                            </div>

                            {{-- Boutons --}}
                            <div class="flex items-center gap-3 pt-1">
                                <button type="button" @click="showModal = false; step = 1"
                                        class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors text-center">
                                    Annuler
                                </button>
                                <button type="submit"
                                        class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition-all"
                                        style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Confirmer ma demande
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
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
            showModal: false,
            step: 1,
            payMethod: 'om',
            copied: false,
            selectedPlanId: null,
            selectedPlanNom: '',
            selectedPrixMensuel: 0,
            selectedPrixAnnuel: 0,
            selectedPrixTriennal: 0,

            openModal(planId, planNom, prixMensuel, prixAnnuel, prixTriennal) {
                this.selectedPlanId = planId;
                this.selectedPlanNom = planNom;
                this.selectedPrixMensuel = prixMensuel;
                this.selectedPrixAnnuel = prixAnnuel;
                this.selectedPrixTriennal = prixTriennal;
                this.step = 1;
                this.copied = false;
                this.showModal = true;
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
            },

            selectedTotal() {
                let total = this.periode === 'annuel' ? this.selectedPrixAnnuel : this.periode === 'triennal' ? this.selectedPrixTriennal : this.selectedPrixMensuel;
                return new Intl.NumberFormat('fr-FR').format(total);
            },

            periodeLabel() {
                return 'FCFA / mois';
            },

            periodeText() {
                return this.periode === 'annuel' ? '1 an (-10%)' : this.periode === 'triennal' ? '3 ans (-20%)' : 'Mensuel';
            }
        }
    }
    </script>
    </x-slot:scripts>
</x-dashboard-layout>
