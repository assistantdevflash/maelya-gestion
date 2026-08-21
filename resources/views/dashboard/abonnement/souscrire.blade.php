<x-dashboard-layout>
<div class="max-w-3xl mx-auto space-y-8 py-4" x-data="souscrire({{ $plan->prixEffectif() }}, {{ $plan->prixPourPeriode('trimestre') }}, {{ $plan->prixPourPeriode('semestre') }}, {{ $plan->prixPourPeriode('annuel') }}, {{ $plan->prixPourPeriode('triennal') }}, '{{ $periode }}', {{ request('ajouter') === 'boutique' ? 'true' : 'false' }})">

    {{-- Fil d'Ariane --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-slate-400">
        <a href="{{ route('abonnement.plans') }}" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Abonnement</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-900 dark:text-white font-medium">Souscrire à {{ $plan->nom }}</span>
    </nav>

    {{-- Titre --}}
    <div>
        <h1 class="text-2xl sm:text-3xl font-display font-bold text-gray-900 dark:text-white tracking-tight">
            Finaliser votre abonnement
        </h1>
        <p class="text-gray-500 dark:text-slate-400 mt-2">
            Remplissez les informations ci-dessous pour activer votre abonnement <strong>{{ $plan->nom }}</strong>.
        </p>
    </div>

    @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert-danger">{{ session('error') }}</div>
    @endif

    @if($demandeEnAttente)
        <div class="card p-5 bg-amber-50 dark:bg-amber-950/30 border-amber-200 dark:border-amber-800">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <p class="font-semibold text-amber-900 dark:text-amber-200">Vous avez déjà une demande en attente de validation</p>
                    <p class="text-sm text-amber-700 dark:text-amber-300">{{ $demandeEnAttente->plan->nom }} — {{ number_format($demandeEnAttente->montant, 0, ',', ' ') }} FCFA — envoyée le {{ $demandeEnAttente->created_at->format('d/m/Y') }}</p>
                </div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('abonnement.souscrire', $plan) }}" enctype="multipart/form-data" class="space-y-8">
        @csrf
        <input type="hidden" name="periode" :value="periode">

        {{-- Section 1 : Plan et période --}}
        <div class="card">
            <div class="card-header">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">1. Votre formule</h2>
            </div>
            <div class="card-body space-y-5">
                {{-- Plan sélectionné (non modifiable) --}}
                <div class="p-6 bg-gradient-to-br from-primary-50 to-pink-50 dark:from-primary-900/30 dark:to-pink-900/30 border-2 border-primary-200 dark:border-primary-700 rounded-2xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-primary-600 dark:text-primary-400 mb-2">Plan sélectionné</label>
                            <h3 class="text-2xl font-display font-bold text-gray-900 dark:text-white">{{ $plan->nom }}</h3>
                            <p class="text-sm text-gray-600 dark:text-slate-200 mt-1">{{ number_format($plan->prixEffectif(), 0, ',', ' ') }} FCFA / mois</p>
                        </div>
                        <div class="flex items-center justify-center w-16 h-16 rounded-2xl bg-white dark:bg-slate-700 shadow-md border border-gray-100 dark:border-slate-600">
                            <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                    </div>
                    @if($plan->description)
                    <p class="text-sm text-gray-600 dark:text-slate-300 mt-3 pt-3 border-t border-primary-200 dark:border-primary-700">{{ $plan->description }}</p>
                    @endif
                </div>

                {{-- Sélecteur de période --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-slate-200 mb-3">Période d'engagement</label>
                    <div class="inline-flex bg-gray-100 dark:bg-slate-800/80 rounded-xl p-1.5 gap-1.5 shadow-inner flex-wrap">
                        <button type="button" @click="setPeriode('mensuel')"
                                :class="periode === 'mensuel' ? 'bg-white dark:bg-slate-700 shadow-md text-gray-900 dark:text-white font-semibold border border-gray-200 dark:border-slate-600' : 'text-gray-600 dark:text-slate-300 hover:text-gray-800 dark:hover:text-slate-100'"
                                class="px-4 py-2.5 rounded-lg text-sm transition-all flex items-center gap-2">
                            📅 Mensuel
                        </button>
                        <button type="button" @click="setPeriode('trimestre')"
                                :class="periode === 'trimestre' ? 'bg-white dark:bg-slate-700 shadow-md text-gray-900 dark:text-white font-semibold border border-gray-200 dark:border-slate-600' : 'text-gray-600 dark:text-slate-300 hover:text-gray-800 dark:hover:text-slate-100'"
                                class="px-4 py-2.5 rounded-lg text-sm transition-all flex items-center gap-2 relative">
                            📅 3 mois
                            <span class="absolute -top-2 -right-2 bg-emerald-500 dark:bg-emerald-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full shadow-sm">-5%</span>
                        </button>
                        <button type="button" @click="setPeriode('semestre')"
                                :class="periode === 'semestre' ? 'bg-white dark:bg-slate-700 shadow-md text-gray-900 dark:text-white font-semibold border border-gray-200 dark:border-slate-600' : 'text-gray-600 dark:text-slate-300 hover:text-gray-800 dark:hover:text-slate-100'"
                                class="px-4 py-2.5 rounded-lg text-sm transition-all flex items-center gap-2 relative">
                            📆 6 mois
                            <span class="absolute -top-2 -right-2 bg-emerald-500 dark:bg-emerald-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full shadow-sm">-10%</span>
                        </button>
                        <button type="button" @click="setPeriode('annuel')"
                                :class="periode === 'annuel' ? 'bg-white dark:bg-slate-700 shadow-md text-gray-900 dark:text-white font-semibold border border-gray-200 dark:border-slate-600' : 'text-gray-600 dark:text-slate-300 hover:text-gray-800 dark:hover:text-slate-100'"
                                class="px-4 py-2.5 rounded-lg text-sm transition-all flex items-center gap-2 relative">
                            📆 1 an
                            <span class="absolute -top-2 -right-2 bg-emerald-500 dark:bg-emerald-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full shadow-sm">-15%</span>
                        </button>
                        <button type="button" @click="setPeriode('triennal')"
                                :class="periode === 'triennal' ? 'bg-white dark:bg-slate-700 shadow-md text-gray-900 dark:text-white font-semibold border border-gray-200 dark:border-slate-600' : 'text-gray-600 dark:text-slate-300 hover:text-gray-800 dark:hover:text-slate-100'"
                                class="px-4 py-2.5 rounded-lg text-sm transition-all flex items-center gap-2 relative">
                            📆 3 ans
                            <span class="absolute -top-2 -right-2 bg-emerald-500 dark:bg-emerald-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full shadow-sm">-20%</span>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-slate-400 mt-2" x-text="periodeInfo()"></p>
                </div>
            </div>
        </div>

        {{-- Section 2 : Récapitulatif et prix --}}
        <div class="card">
            <div class="card-header">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">2. Récapitulatif</h2>
            </div>
            <div class="card-body space-y-4">
                <div class="p-5 bg-gray-50 dark:bg-slate-800 rounded-2xl border-2 border-gray-200 dark:border-slate-600 space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-slate-200 font-medium">Plan</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ $plan->nom }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-slate-200 font-medium">Période</span>
                        <span class="font-semibold text-gray-900 dark:text-white" x-text="periodeLabel()"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-slate-200 font-medium">Prix du plan</span>
                        <span class="font-semibold text-gray-900 dark:text-white" x-text="formatPlanPrice() + ' FCFA'"></span>
                    </div>

                    {{-- Option boutique --}}
                    <div class="pt-3 border-t-2 border-gray-200 dark:border-slate-600">
                        <label class="flex items-start gap-3 cursor-pointer p-4 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-700/50 transition-colors border-2 border-transparent hover:border-primary-200 dark:hover:border-primary-700/70">
                            <input type="checkbox" name="option_boutique" value="1"
                                   x-model="optionBoutique"
                                   class="mt-1 w-5 h-5 rounded border-gray-300 dark:border-slate-500 text-primary-600 focus:ring-primary-500 dark:bg-slate-700 dark:focus:ring-primary-400">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-semibold text-gray-900 dark:text-white text-base">🛍️ Boutique en ligne</span>
                                    <span class="text-sm font-bold text-primary-600 dark:text-primary-300 bg-primary-50 dark:bg-primary-900/50 px-2 py-0.5 rounded">+3 900 F/mois</span>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-slate-200 mt-1">Vos clients pourront commander en ligne avec livraison</p>
                                <p x-show="optionBoutique" x-cloak class="text-xs font-semibold text-primary-700 dark:text-primary-200 mt-2 bg-primary-50 dark:bg-primary-900/40 px-2 py-1 rounded inline-block">
                                    +<span x-text="boutiquePrice()"></span> FCFA pour la période
                                </p>
                            </div>
                        </label>
                    </div>

                    {{-- Total --}}
                    <div class="pt-4 mt-1 border-t-2 border-primary-300 dark:border-primary-600 flex justify-between items-center">
                        <span class="text-lg font-bold text-gray-900 dark:text-white">Total à payer</span>
                        <span class="text-2xl font-bold text-primary-600 dark:text-primary-300" x-text="totalPrice() + ' FCFA'"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 3 : Paiement --}}
        <div class="card">
            <div class="card-header">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">3. Moyen de paiement</h2>
            </div>
            <div class="card-body space-y-5">

                @php $geniuspay = $paymentMethods->firstWhere('code', 'geniuspay'); @endphp
                @php $bankTransfer = $paymentMethods->firstWhere('code', 'bank_transfer'); @endphp

                {{-- Sélecteur de méthode de paiement --}}
                @if($geniuspay)
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-slate-200 mb-3">Choisissez votre moyen de paiement</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        {{-- GeniusPay --}}
                        <label class="relative cursor-pointer">
                            <input type="radio" name="methode_paiement" value="geniuspay"
                                   x-model="methodePaiement"
                                   class="sr-only peer">
                            <div class="p-4 border-2 rounded-2xl transition-all peer-checked:border-primary-500 peer-checked:bg-primary-50 dark:peer-checked:bg-primary-900/20 border-gray-200 dark:border-slate-700 hover:border-primary-300 dark:hover:border-primary-700">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white text-sm">GeniusPay</p>
                                        <p class="text-xs text-gray-500 dark:text-slate-400">Wave, Orange, MTN, Carte</p>
                                    </div>
                                    <div class="ml-auto">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Instantané</span>
                                    </div>
                                </div>
                            </div>
                        </label>

                        {{-- Transfert bancaire --}}
                        @if($bankTransfer)
                        <label class="relative cursor-pointer">
                            <input type="radio" name="methode_paiement" value="bank_transfer"
                                   x-model="methodePaiement"
                                   class="sr-only peer" checked>
                            <div class="p-4 border-2 rounded-2xl transition-all peer-checked:border-primary-500 peer-checked:bg-primary-50 dark:peer-checked:bg-primary-900/20 border-gray-200 dark:border-slate-700 hover:border-primary-300 dark:hover:border-primary-700">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-slate-700 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-gray-600 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white text-sm">Transfert manuel</p>
                                        <p class="text-xs text-gray-500 dark:text-slate-400">Wave / Orange Money</p>
                                    </div>
                                    <div class="ml-auto">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">24h</span>
                                    </div>
                                </div>
                            </div>
                        </label>
                        @endif
                    </div>
                </div>
                @else
                <input type="hidden" name="methode_paiement" value="bank_transfer">
                @endif

                {{-- Contenu conditionnel selon la méthode --}}
                <div x-show="methodePaiement === 'geniuspay'" x-cloak>
                    <div class="rounded-xl bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 p-4 text-sm text-purple-800 dark:text-purple-200">
                        <p class="font-semibold mb-1">🚀 Paiement sécurisé via GeniusPay</p>
                        <p>Vous serez redirigé vers la page de paiement GeniusPay. Choisissez Wave, Orange Money, MTN ou votre carte bancaire. L'activation est <strong>instantanée</strong> dès la confirmation du paiement.</p>
                    </div>
                </div>

                <div x-show="methodePaiement === 'bank_transfer'" x-cloak>
                    {{-- Mode de paiement (OM / Wave) --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-slate-200 mb-3">Mode de paiement</label>
                        <div class="flex items-center gap-2 bg-gray-100 dark:bg-slate-800/80 rounded-xl p-1.5 shadow-inner">
                            <button type="button" @click="payMethod = 'om'"
                                    :class="payMethod === 'om' ? 'bg-white dark:bg-slate-700 shadow-md text-orange-600 dark:text-orange-500 font-semibold border border-gray-200 dark:border-slate-600' : 'text-gray-600 dark:text-slate-300 hover:text-gray-800 dark:hover:text-slate-100'"
                                    class="flex-1 flex items-center justify-center gap-2 py-3 rounded-lg text-sm transition-all">
                                <span class="font-bold text-lg">OM</span> Orange Money
                            </button>
                            <button type="button" @click="payMethod = 'wave'"
                                    :class="payMethod === 'wave' ? 'bg-white dark:bg-slate-700 shadow-md text-blue-600 dark:text-blue-500 font-semibold border border-gray-200 dark:border-slate-600' : 'text-gray-600 dark:text-slate-300 hover:text-gray-800 dark:hover:text-slate-100'"
                                    class="flex-1 flex items-center justify-center gap-2 py-3 rounded-lg text-sm transition-all">
                                <span class="font-bold text-lg">W</span> Wave
                            </button>
                        </div>
                    </div>

                    {{-- Infos bénéficiaire --}}
                    <div class="p-5 bg-gray-50 dark:bg-slate-800 rounded-2xl border-2 border-gray-200 dark:border-slate-600 space-y-4">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-medium text-gray-600 dark:text-slate-200 flex-shrink-0">Bénéficiaire</span>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span class="font-bold text-gray-900 dark:text-white font-mono text-base sm:text-lg tracking-wide whitespace-nowrap">07 09 87 40 67</span>
                                <button type="button"
                                        @click="navigator.clipboard.writeText('0709874067'); copied = true; setTimeout(() => copied = false, 2000)"
                                        class="p-2 text-gray-500 dark:text-slate-300 hover:text-primary-600 dark:hover:text-primary-400 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors flex-shrink-0">
                                    <svg x-show="!copied" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    <svg x-show="copied" x-cloak class="w-5 h-5 text-emerald-500 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="flex items-center justify-between pt-3 border-t border-gray-200 dark:border-slate-600">
                            <span class="text-sm font-medium text-gray-600 dark:text-slate-200">Nom</span>
                            <span class="font-semibold text-gray-900 dark:text-white">MAELYA GESTION</span>
                        </div>
                        <div class="flex items-center justify-between pt-3 border-t-2 border-gray-300 dark:border-slate-500">
                            <span class="text-sm font-medium text-gray-600 dark:text-slate-200">Montant à transférer</span>
                            <span class="text-xl font-bold text-primary-600 dark:text-primary-300" x-text="totalPrice() + ' FCFA'"></span>
                        </div>
                    </div>

                    {{-- Info --}}
                    <div class="flex items-start gap-3 bg-blue-50 dark:bg-blue-900/30 border-2 border-blue-200 dark:border-blue-700/60 rounded-xl p-4">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-300 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                        <p class="text-sm text-blue-900 dark:text-blue-100">
                            Effectuez le transfert au numéro ci-dessus puis fournissez la référence ou le reçu. Votre abonnement sera activé <strong class="dark:text-white">après vérification par un administrateur</strong> (généralement sous 24h).
                        </p>
                    </div>

                    {{-- Référence transfert --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-slate-200 mb-3">Référence de votre transfert</label>
                        <input type="text" name="reference_transfert" class="form-input" placeholder="Ex : numéro de transaction OM/Wave">
                        <p class="text-xs text-gray-600 dark:text-slate-400 mt-1">Le numéro de référence que vous recevez par SMS après le transfert.</p>
                    </div>

                    {{-- OU --}}
                    <div class="flex items-center gap-4">
                        <div class="flex-1 h-px bg-gray-200 dark:bg-slate-700"></div>
                        <span class="text-sm font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">ou</span>
                        <div class="flex-1 h-px bg-gray-200 dark:bg-slate-700"></div>
                    </div>

                    {{-- Upload reçu --}}
                    <div x-data="{ fileName: '' }">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-slate-200 mb-3">Reçu de transfert (image ou PDF)</label>
                        <label class="flex flex-col items-center justify-center p-6 border-2 border-dashed border-gray-300 dark:border-slate-600 rounded-xl hover:border-primary-400 dark:hover:border-primary-500 cursor-pointer transition-colors bg-gray-50/50 dark:bg-slate-900/50">
                            <template x-if="!fileName">
                                <div class="text-center">
                                    <svg class="mx-auto w-10 h-10 text-gray-400 dark:text-slate-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                    <p class="text-sm text-primary-600 dark:text-primary-400 font-medium">Cliquez pour sélectionner un fichier</p>
                                    <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">JPEG, PNG, WebP ou PDF — 10 Mo max</p>
                                </div>
                            </template>
                            <template x-if="fileName">
                                <div class="flex items-center gap-3 text-sm text-emerald-700 dark:text-emerald-400 font-medium">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span class="truncate max-w-[300px]" x-text="fileName"></span>
                                </div>
                            </template>
                            <input type="file" name="preuve_paiement" accept="image/*,.pdf" class="sr-only"
                                   @change="fileName = $event.target.files[0]?.name || ''">
                        </label>
                        <p class="text-xs text-gray-600 dark:text-slate-400 mt-2">Fournissez la référence <strong class="dark:text-slate-300">OU</strong> le reçu — un seul des deux suffit.</p>
                    </div>
                </div>{{-- /x-show bank_transfer --}}>
                            <div class="flex items-center gap-3 text-sm text-emerald-700 dark:text-emerald-400 font-medium">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="truncate max-w-[300px]" x-text="fileName"></span>
                            </div>
                        </template>
                        <input type="file" name="preuve_paiement" accept="image/*,.pdf" class="sr-only"
                               @change="fileName = $event.target.files[0]?.name || ''">
                    </label>
                    <p class="text-xs text-gray-600 dark:text-slate-400 mt-2">Fournissez la référence <strong class="dark:text-slate-300">OU</strong> le reçu — un seul des deux suffit.</p>
                </div>
                </div>{{-- /x-show bank_transfer --}}
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2">
            <a href="{{ route('abonnement.plans') }}" class="btn-ghost px-6 py-2.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Retour
            </a>
            <button type="submit"
                    class="btn-primary px-8 py-3 text-base !bg-gradient-to-r !from-purple-600 !to-pink-600 hover:!from-purple-700 hover:!to-pink-700 shadow-lg hover:shadow-xl">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path x-show="methodePaiement === 'geniuspay'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    <path x-show="methodePaiement !== 'geniuspay'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span x-show="methodePaiement === 'geniuspay'" x-cloak>Procéder au paiement</span>
                <span x-show="methodePaiement !== 'geniuspay'" x-cloak>Envoyer ma demande</span>
            </button>
        </div>

    </form>
</div>

<x-slot:scripts>
<script>
function souscrire(prixMensuel, prixTrimestre, prixSemestre, prixAnnuel, prixTriennal, initialPeriode, initialBoutique) {
    return {
        prixMensuel: prixMensuel,
        prixTrimestre: prixTrimestre,
        prixSemestre: prixSemestre,
        prixAnnuel: prixAnnuel,
        prixTriennal: prixTriennal,
        periode: initialPeriode,
        payMethod: 'om',
        copied: false,
        optionBoutique: initialBoutique,
        methodePaiement: 'bank_transfer',

        setPeriode(p) {
            this.periode = p;
        },

        planPrice() {
            if (this.periode === 'trimestre') return this.prixTrimestre;
            if (this.periode === 'semestre') return this.prixSemestre;
            if (this.periode === 'annuel') return this.prixAnnuel;
            if (this.periode === 'triennal') return this.prixTriennal;
            return this.prixMensuel;
        },

        boutiquePrice() {
            const nbMois = this.periode === 'trimestre' ? 3 : this.periode === 'semestre' ? 6 : this.periode === 'annuel' ? 12 : this.periode === 'triennal' ? 36 : 1;
            return new Intl.NumberFormat('fr-FR').format(3900 * nbMois);
        },

        totalPrice() {
            let total = this.planPrice();
            if (this.optionBoutique) {
                const nbMois = this.periode === 'trimestre' ? 3 : this.periode === 'semestre' ? 6 : this.periode === 'annuel' ? 12 : this.periode === 'triennal' ? 36 : 1;
                total += 3900 * nbMois;
            }
            return new Intl.NumberFormat('fr-FR').format(total);
        },

        formatPlanPrice() {
            return new Intl.NumberFormat('fr-FR').format(this.planPrice());
        },

        periodeLabel() {
            if (this.periode === 'trimestre') return '3 mois (-5%)';
            if (this.periode === 'semestre') return '6 mois (-10%)';
            if (this.periode === 'annuel') return '1 an (-15%)';
            if (this.periode === 'triennal') return '3 ans (-20%)';
            return 'Mensuel';
        },

        periodeInfo() {
            const nbMois = this.periode === 'trimestre' ? 3 : this.periode === 'semestre' ? 6 : this.periode === 'annuel' ? 12 : this.periode === 'triennal' ? 36 : 1;
            const pp = this.planPrice() / nbMois;
            return 'Soit ' + new Intl.NumberFormat('fr-FR').format(pp) + ' FCFA / mois';
        }
    }
}
</script>
</x-slot:scripts>
</x-dashboard-layout>
