<x-dashboard-layout>
<div class="space-y-8">
    {{-- Header --}}
    <div>
        <h1 class="text-3xl font-display font-bold text-gray-900 dark:text-white tracking-tight">Configuration boutique en ligne</h1>
        <p class="text-gray-500 dark:text-slate-400 mt-2">Paramétrez votre boutique et gérez son activation</p>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 dark:bg-emerald-950/40 border-2 border-emerald-200 dark:border-emerald-800/40 rounded-2xl p-5 flex items-start gap-4">
            <div class="w-10 h-10 bg-emerald-500 dark:bg-emerald-600 rounded-xl flex items-center justify-center text-white flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <p class="text-emerald-800 dark:text-emerald-200 font-medium pt-1.5">{{ session('success') }}</p>
        </div>
    @endif

    @php
        $hasBoutique = auth()->user()->hasBoutiqueAccess();
        $isEssai = auth()->user()->abonnementActif?->plan?->slug === 'essai';
    @endphp

    @if(!$hasBoutique && !$isEssai)

    {{-- Bannière demande en attente --}}
    @if($demandeEnAttente ?? false)
    <div class="p-6 bg-blue-50 dark:bg-blue-950/30 border-2 border-blue-300 dark:border-blue-700 rounded-2xl mb-6">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-blue-500 dark:bg-blue-600 rounded-xl flex items-center justify-center text-white flex-shrink-0">
                <svg class="w-7 h-7 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-blue-900 dark:text-blue-200">🕐 Demande d'activation en cours</h3>
                <p class="text-blue-800 dark:text-blue-300 mt-2">
                    Votre demande d'activation de la boutique en ligne est en attente de validation.
                    Montant : <strong>{{ number_format($demandeEnAttente->montant, 0, ',', ' ') }} FCFA</strong>.
                </p>
                <p class="text-sm text-blue-700 dark:text-blue-400 mt-3">
                    ⏱️ Validation généralement sous 24h. Vous recevrez une notification dès l'activation.
                </p>
            </div>
        </div>
    </div>
    @endif

    {{-- Bandeau d'upgrade : l'option boutique n'est pas activée --}}
    <div class="p-6 bg-amber-50 dark:bg-amber-950/30 border-2 border-amber-300 dark:border-amber-700 rounded-2xl">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-amber-500 dark:bg-amber-600 rounded-xl flex items-center justify-center text-white flex-shrink-0">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-amber-900 dark:text-amber-200">Module non inclus dans votre abonnement</h3>
                <p class="text-amber-800 dark:text-amber-300 mt-2">
                    Le module <strong>Boutique en ligne</strong> est une option payante à <strong>3 900 F/mois</strong>.
                    Ajoutez-le à votre abonnement actuel pour permettre à vos clients de commander vos produits en ligne avec livraison à domicile.
                </p>
                @php
                    $aboActif = auth()->user()->abonnementActif;
                    $joursRestants = $aboActif ? $aboActif->joursRestants() : 0;
                    $montantProrata = $joursRestants > 0 ? (int) round((3900 / 30) * $joursRestants) : 0;
                @endphp
                @if($montantProrata > 0)
                <div class="mt-3 p-3 bg-amber-100 dark:bg-amber-900/40 rounded-lg border border-amber-200 dark:border-amber-700">
                    <p class="text-sm text-amber-900 dark:text-amber-200">
                        💡 <strong>Prorata calculé :</strong> {{ number_format($montantProrata, 0, ',', ' ') }} FCFA pour les {{ $joursRestants }} jours restants de votre abonnement actuel.
                        À partir du prochain renouvellement, le montant sera de 3 900 FCFA/mois.
                    </p>
                </div>
                @endif
                <div class="flex gap-3 mt-4" x-data="{ showModal: false }">
                    <button type="button"
                            @click="showModal = true"
                            {{ ($demandeEnAttente ?? false) ? 'disabled' : '' }}
                            class="btn-primary {{ ($demandeEnAttente ?? false) ? 'opacity-50 cursor-not-allowed' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        Activer la boutique (+{{ number_format($montantProrata, 0, ',', ' ') }} F)
                    </button>
                    <a href="{{ route('dashboard.boutique.commandes.index') }}" class="btn-ghost">
                        Voir mes commandes
                    </a>

                    {{-- Modal de paiement --}}
                    <div x-show="showModal"
                         x-cloak
                         @click.self="showModal = false"
                         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
                        <div @click.away="showModal = false"
                             x-data="{ payMethod: 'om', copied: false, fileName: '' }"
                             class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">

                            {{-- Header --}}
                            <div class="sticky top-0 bg-gradient-to-r from-primary-600 to-secondary-600 p-6 rounded-t-3xl z-10">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h2 class="text-2xl font-bold text-white">🛍️ Activer la boutique en ligne</h2>
                                        <p class="text-primary-100 mt-1">Finalisez le paiement pour activer l'option</p>
                                    </div>
                                    <button @click="showModal = false" class="text-white/80 hover:text-white p-2 hover:bg-white/10 rounded-lg transition-colors">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>

                            {{-- Formulaire --}}
                            <form method="POST" action="{{ route('abonnement.ajouter-boutique') }}" enctype="multipart/form-data" class="p-6 space-y-6">
                                @csrf

                                {{-- Récapitulatif montant --}}
                                <div class="p-5 bg-gradient-to-br from-primary-50 to-secondary-50 dark:from-primary-950/30 dark:to-secondary-950/30 border-2 border-primary-200 dark:border-primary-700 rounded-2xl">
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-700 dark:text-slate-200 font-medium">Montant prorata ({{ $joursRestants }} jours restants)</span>
                                        <span class="text-2xl font-bold text-primary-600 dark:text-primary-300">{{ number_format($montantProrata, 0, ',', ' ') }} FCFA</span>
                                    </div>
                                    <p class="text-xs text-gray-600 dark:text-slate-400 mt-2">
                                        💡 À partir du prochain renouvellement : 3 900 FCFA/mois
                                    </p>
                                </div>

                                {{-- Mode de paiement --}}
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
                                        <span class="text-xl font-bold text-primary-600 dark:text-primary-300">{{ number_format($montantProrata, 0, ',', ' ') }} FCFA</span>
                                    </div>
                                </div>

                                {{-- Info --}}
                                <div class="flex items-start gap-3 bg-blue-50 dark:bg-blue-900/30 border-2 border-blue-200 dark:border-blue-700/60 rounded-xl p-4">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-300 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                                    <p class="text-sm text-blue-900 dark:text-blue-100">
                                        Effectuez le transfert au numéro ci-dessus puis fournissez la référence ou le reçu. L'option sera activée <strong class="dark:text-white">après vérification par un administrateur</strong> (généralement sous 24h).
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
                                <div>
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
                                </div>

                                {{-- Bouton soumettre --}}
                                <button type="submit" class="btn-primary w-full py-4 text-base">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Envoyer ma demande
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else

    <form method="POST" action="{{ route('dashboard.boutique.config.update') }}" class="space-y-8">
        @csrf

        {{-- Activation --}}
        <div class="card">
            <div class="card-header">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Activation</h2>
                <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">Activez ou désactivez votre boutique en ligne</p>
            </div>
            <div class="card-body space-y-6">
                <label class="flex items-start gap-4 cursor-pointer group p-4 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors">
                    <input
                        type="checkbox"
                        name="boutique_active"
                        value="1"
                        {{ $institut->boutique_active ? 'checked' : '' }}
                        class="mt-1 w-6 h-6 rounded-lg border-gray-300 dark:border-slate-600 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:bg-slate-900"
                    >
                    <div class="flex-1">
                        <span class="text-base font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">Activer la boutique en ligne</span>
                        <p class="text-sm text-gray-600 dark:text-slate-400 mt-1">Les clients pourront commander vos produits en ligne</p>
                    </div>
                </label>

                @if($institut->boutique_active)
                <div class="p-5 bg-gradient-to-br from-primary-50 to-secondary-50 dark:from-primary-950/30 dark:to-secondary-950/30 border-2 border-primary-200 dark:border-primary-800/40 rounded-2xl">
                    <div class="flex items-center gap-2 mb-3">
                        <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                        <p class="font-semibold text-primary-900 dark:text-primary-300">Lien de votre boutique</p>
                    </div>
                    <div class="flex gap-3">
                        <input
                            type="text"
                            value="{{ url('/shop/' . $institut->slug) }}"
                            readonly
                            class="flex-1 px-4 py-3 bg-white dark:bg-slate-900 border-2 border-primary-200 dark:border-primary-700 rounded-xl text-sm text-gray-800 dark:text-slate-200 font-mono"
                        >
                        <button
                            type="button"
                            onclick="var btn=this;navigator.clipboard.writeText('{{ url('/shop/' . $institut->slug) }}').then(function(){btn.innerHTML='✓ Copié';setTimeout(function(){btn.innerHTML='Copier'},2000)})"
                            class="btn-primary px-5"
                        >
                            Copier
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Livraison --}}
        <div class="card">
            <div class="card-header">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Livraison</h2>
                <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">Configurez les options de livraison</p>
            </div>
            <div class="card-body space-y-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-3">Frais de livraison (FCFA)</label>
                    <input
                        type="number"
                        name="boutique_frais_livraison"
                        value="{{ old('boutique_frais_livraison', $institut->boutique_frais_livraison) }}"
                        placeholder="1500"
                        min="0"
                        step="100"
                        class="input w-full"
                    >
                    <p class="text-xs text-gray-500 dark:text-slate-400 mt-2">Mettez 0 pour une livraison gratuite</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-3">Délai de livraison</label>
                    <input
                        type="text"
                        name="boutique_delai_livraison"
                        value="{{ old('boutique_delai_livraison', $institut->boutique_delai_livraison) }}"
                        placeholder="24h - 48h"
                        maxlength="100"
                        class="input w-full"
                    >
                </div>

                <div x-data="zoneEditor(@js($institut->boutique_zones_livraison ?? []))">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-3">Zones de livraison</label>

                    <template x-for="(zone, i) in zones" :key="i">
                        <div class="flex items-start gap-2 mb-2 p-3 bg-gray-50 dark:bg-slate-800 rounded-xl">
                            <div class="flex-1 space-y-2">
                                <input type="text" :name="'boutique_zones_livraison['+i+'][nom]'" x-model="zone.nom"
                                       placeholder="Nom de la zone" class="form-input text-sm" required>
                                <div class="flex gap-2">
                                    <input type="number" :name="'boutique_zones_livraison['+i+'][frais]'" x-model="zone.frais"
                                           placeholder="Frais (FCFA)" min="0" class="form-input text-sm w-1/2" required>
                                    <input type="text" :name="'boutique_zones_livraison['+i+'][delai]'" x-model="zone.delai"
                                           placeholder="Délai (ex: 1-3 jours)" class="form-input text-sm w-1/2">
                                </div>
                            </div>
                            <button type="button" @click="zones.splice(i, 1)" class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>

                    <button type="button" @click="zones.push({nom:'',frais:0,delai:''})"
                            class="mt-2 text-sm text-primary-600 hover:text-primary-700 font-medium flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        Ajouter une zone
                    </button>
                    <p class="text-xs text-gray-500 dark:text-slate-400 mt-2">Définissez vos zones avec frais et délais personnalisés.</p>
                </div>
            </div>
        </div>

        {{-- Conditions --}}
        <div class="card">
            <div class="card-header">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Conditions de vente</h2>
                <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">Définissez vos conditions générales</p>
            </div>
            <div class="card-body">
                <label class="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-3">Conditions générales de vente</label>
                <textarea
                    name="boutique_conditions"
                    rows="6"
                    placeholder="Ex: Paiement à la livraison, Retour sous 7 jours..."
                    class="input w-full"
                >{{ old('boutique_conditions', $institut->boutique_conditions) }}</textarea>
                <p class="text-xs text-gray-500 dark:text-slate-400 mt-2">Ces conditions seront affichées sur votre boutique</p>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-slate-700">
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard.boutique.commandes.index') }}" class="btn-ghost">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Retour
                </a>
                <form method="POST" action="{{ route('dashboard.boutique.config.vider-cache') }}" class="inline">
                    @csrf
                    <button type="submit" class="btn-ghost text-amber-600 hover:text-amber-700 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Vider le cache
                    </button>
                </form>
            </div>
            <button type="submit" class="btn-primary btn-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Enregistrer les modifications
            </button>
        </div>
    </form>
    @endif
</div>

{{-- Zone editor — inline pour éviter race condition Alpine --}}
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('zoneEditor', (initialZones) => {
        // Normaliser : string → array d'objets, objets existants conservés
        let zones = [];
        if (Array.isArray(initialZones) && initialZones.length > 0) {
            zones = initialZones.map(z =>
                typeof z === 'object' ? {nom: z.nom || '', frais: z.frais || 0, delai: z.delai || ''}
                                      : {nom: String(z).trim(), frais: 0, delai: ''}
            );
        }
        return { zones };
    });
});
</script>
</x-dashboard-layout>
