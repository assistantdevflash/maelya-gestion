<x-dashboard-layout>
    <div class="space-y-5" x-data="{ onglet: new URLSearchParams(window.location.search).get('onglet') || 'depenses' }">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-display font-bold text-gray-900 tracking-tight">Finances</h1>
                <p class="text-sm text-gray-500 mt-1">Suivi des revenus et dépenses.</p>
            </div>
            <div class="flex gap-2">
                <button x-data @click="$dispatch('open-depense-modal')" class="btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Ajouter dépense
                </button>
                <a href="{{ route('dashboard.finances.rapport') }}" class="btn-outline">Rapport PDF</a>
            </div>
        </div>

        {{-- Filtres période --}}
        <div class="card p-4">
            <form method="GET" action="{{ route('dashboard.finances.index') }}" id="form-finances-filtre"
                  class="flex flex-wrap gap-3 items-end"
                  x-data="{ debut: '{{ $debut->format('Y-m-d') }}', fin: '{{ $fin->format('Y-m-d') }}' }">
                <input type="hidden" name="periode" value="custom">
                <input type="hidden" name="onglet" :value="onglet">
                <div class="form-group mb-0">
                    <label class="form-label text-xs">Début</label>
                    <input type="date" name="debut" x-model="debut" value="{{ $debut->format('Y-m-d') }}" class="form-input" @change="$el.form.submit()">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label text-xs">Fin</label>
                    <input type="date" name="fin" x-model="fin" :min="debut" value="{{ $fin->format('Y-m-d') }}" class="form-input" @change="$el.form.submit()">
                </div>
                @unless($debut->isSameDay(now()->startOfMonth()) && $fin->isSameDay(now()->endOfMonth()))
                <a :href="'{{ route('dashboard.finances.index') }}' + (onglet !== 'depenses' ? '?onglet=' + onglet : '')" class="btn-outline text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Réinitialiser
                </a>
                @endunless                <div class="flex gap-1 ml-auto">
                    @foreach([
                        ['today', "Aujourd'hui"],
                        ['week', 'Cette semaine'],
                        ['month', 'Ce mois'],
                    ] as [$period, $label])
                        <a :href="'{{ route('dashboard.finances.index', ['periode' => $period]) }}' + (onglet !== 'depenses' ? '&onglet=' + onglet : '')"
                           class="btn text-xs px-3 py-1.5 {{ request('periode') === $period ? 'bg-primary-100 text-primary-700' : 'text-gray-500 hover:bg-gray-100' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </form>
        </div>

        {{-- ═══ Résultat de la période ═══ --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="stat-card">
                <p class="text-xs text-gray-500 mb-1">Chiffre d'affaires encaissé</p>
                <p class="text-xl font-bold text-emerald-600">{{ number_format($resultat['ca'], 0, ',', ' ') }}</p>
                <p class="text-xs text-gray-400">
                    FCFA · {{ $resultat['nb_ventes'] }} vente{{ $resultat['nb_ventes'] > 1 ? 's' : '' }}
                    @if($comparaison($resultat['ca'], $resultatPrec['ca']) !== null)
                        <span class="font-semibold {{ $comparaison($resultat['ca'], $resultatPrec['ca']) >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                            {{ $comparaison($resultat['ca'], $resultatPrec['ca']) >= 0 ? '▲' : '▼' }}{{ abs($comparaison($resultat['ca'], $resultatPrec['ca'])) }} %
                        </span>
                    @endif
                </p>
            </div>
            <div class="stat-card">
                <p class="text-xs text-gray-500 mb-1">Coût des marchandises</p>
                <p class="text-xl font-bold text-amber-600">{{ number_format($resultat['couts_marchandises'], 0, ',', ' ') }}</p>
                <p class="text-xs text-gray-400">FCFA · ce qui a été vendu</p>
            </div>
            <div class="stat-card">
                <p class="text-xs text-gray-500 mb-1">Marge brute</p>
                <p class="text-xl font-bold text-primary-600">{{ number_format($resultat['marge_brute'], 0, ',', ' ') }}</p>
                <p class="text-xs text-gray-400">FCFA · {{ $resultat['taux_marge_brute'] }} % du CA</p>
            </div>
            <div class="stat-card">
                <p class="text-xs text-gray-500 mb-1">Dépenses d'exploitation</p>
                <p class="text-xl font-bold text-red-600">{{ number_format($resultat['depenses'], 0, ',', ' ') }}</p>
                <p class="text-xs text-gray-400">
                    FCFA
                    @if(($sansJustificatif ?? 0) > 0)
                        · <span class="text-amber-600 font-semibold">{{ $sansJustificatif }} sans justificatif</span>
                    @endif
                </p>
            </div>
        </div>

        {{-- ═══ Résultat net (chaîne explicite) ═══ --}}
        <div class="card p-4 sm:p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                    <span class="text-gray-500">CA encaissé <strong class="text-gray-900 dark:text-white">{{ number_format($resultat['ca'], 0, ',', ' ') }}</strong></span>
                    <span class="text-gray-300">−</span>
                    <span class="text-gray-500">Marchandises <strong class="text-gray-900 dark:text-white">{{ number_format($resultat['couts_marchandises'], 0, ',', ' ') }}</strong></span>
                    <span class="text-gray-300">=</span>
                    <span class="text-gray-500">Marge brute <strong class="text-primary-600">{{ number_format($resultat['marge_brute'], 0, ',', ' ') }}</strong></span>
                    <span class="text-gray-300">−</span>
                    <span class="text-gray-500">Dépenses <strong class="text-gray-900 dark:text-white">{{ number_format($resultat['depenses'], 0, ',', ' ') }}</strong></span>
                </div>
                <div class="text-right">
                    <p class="text-[11px] uppercase tracking-wider text-gray-400 font-semibold">Résultat net</p>
                    <p class="text-2xl font-extrabold {{ $resultat['resultat'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ number_format($resultat['resultat'], 0, ',', ' ') }} <span class="text-sm font-bold">FCFA</span>
                    </p>
                    @if($comparaison($resultat['resultat'], $resultatPrec['resultat']) !== null)
                        <p class="text-[11px] font-semibold {{ $comparaison($resultat['resultat'], $resultatPrec['resultat']) >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                            {{ $comparaison($resultat['resultat'], $resultatPrec['resultat']) >= 0 ? '▲' : '▼' }}
                            {{ abs($comparaison($resultat['resultat'], $resultatPrec['resultat'])) }} % vs période précédente
                        </p>
                    @endif
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-slate-700 grid sm:grid-cols-3 gap-3 text-xs">
                <div>
                    <p class="text-gray-400">Panier moyen</p>
                    <p class="font-bold text-gray-900 dark:text-white">{{ number_format($resultat['panier_moyen'], 0, ',', ' ') }} F</p>
                </div>
                <div>
                    <p class="text-gray-400">Seuil de rentabilité</p>
                    <p class="font-bold text-gray-900 dark:text-white">
                        @if($resultat['seuil_rentabilite'] !== null)
                            {{ number_format($resultat['seuil_rentabilite'], 0, ',', ' ') }} F de CA
                        @else
                            —
                        @endif
                    </p>
                    @if($resultat['seuil_rentabilite'] !== null)
                        <p class="text-[10px] text-gray-400">pour couvrir les dépenses de la période</p>
                    @endif
                </div>
                <div>
                    <p class="text-gray-400">Encours crédit client</p>
                    <p class="font-bold text-gray-900 dark:text-white">{{ number_format($encoursCredit['encours'] ?? 0, 0, ',', ' ') }} F</p>
                    @if(($encoursCredit['en_retard'] ?? 0) > 0)
                        <p class="text-[10px] text-red-500 font-semibold">dont {{ number_format($encoursCredit['en_retard'], 0, ',', ' ') }} F en retard</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- ═══ Stock : photo à date, pas un flux de la période ═══ --}}
        @if(($valeurStock ?? 0) > 0 || ($margePotentielleStock ?? 0) > 0)
        <div class="card p-4">
            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400 mb-2">Stock à aujourd'hui</p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-gray-500 mb-1">Valeur du stock (CMP)</p>
                    <p class="text-lg font-bold text-blue-600">{{ number_format($valeurStock ?? 0, 0, ',', ' ') }}</p>
                    <p class="text-[10px] text-gray-400">FCFA immobilisés</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">Marge si tout est vendu</p>
                    <p class="text-lg font-bold text-emerald-600">{{ number_format($margePotentielleStock ?? 0, 0, ',', ' ') }}</p>
                    <p class="text-[10px] text-gray-400">projection, hors période</p>
                </div>
            </div>
        </div>
        @endif

        {{-- Onglets Dépenses / Par catégorie / Trésorerie --}}
        <div class="flex gap-1 border-b border-gray-200 dark:border-slate-700">
            <button @click="onglet = 'depenses'"
                    :class="onglet === 'depenses' ? 'border-b-2 border-primary-600 text-primary-700 dark:text-primary-400 font-semibold' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700'"
                    class="px-4 py-2 text-sm transition-colors">
                Dépenses
            </button>
            <button @click="onglet = 'categories'"
                    :class="onglet === 'categories' ? 'border-b-2 border-primary-600 text-primary-700 dark:text-primary-400 font-semibold' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700'"
                    class="px-4 py-2 text-sm transition-colors">
                Par catégorie
            </button>
            <button @click="onglet = 'tresorerie'"
                    :class="onglet === 'tresorerie' ? 'border-b-2 border-primary-600 text-primary-700 dark:text-primary-400 font-semibold' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700'"
                    class="px-4 py-2 text-sm transition-colors">
                💰 Trésorerie prévisionnelle
            </button>
        </div>

        {{-- Dépenses --}}
        <div x-show="onglet === 'depenses'" class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-bold text-gray-900">Dépenses</h2>
                <div class="flex gap-2">
                    <a href="{{ route('dashboard.finances.export-depenses') . (request()->getQueryString() ? '?'.request()->getQueryString() : '') }}" class="btn-outline text-xs px-3 py-1.5">
                        ↓ CSV
                    </a>
                </div>
            </div>

            @if($depenses->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Description</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 hidden md:table-cell">Catégorie</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 hidden sm:table-cell">Date</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">Montant</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($depenses as $depense)
                        <tr class="hover:bg-gray-50 group">
                            <td class="px-4 py-3 text-gray-900 font-medium">{{ $depense->description }}</td>
                            <td class="px-4 py-3 hidden md:table-cell">
                                <span class="badge badge-secondary text-xs">{{ \App\Models\Depense::categorieLabel($depense->categorie) }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs hidden sm:table-cell">{{ $depense->date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-red-600">
                                {{ number_format($depense->montant, 0, ',', ' ') }} FCFA
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100">
                                    <button
                                        x-data
                                        @click="$dispatch('edit-depense', { id: '{{ $depense->id }}', description: '{{ addslashes($depense->description) }}', categorie: '{{ $depense->categorie }}', montant: {{ $depense->montant }}, date: '{{ $depense->date->format('Y-m-d') }}', notes: '{{ addslashes($depense->notes ?? '') }}' })"
                                        class="btn-icon">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <form id="form-depense-{{ $depense->id }}" method="POST" action="{{ route('dashboard.depenses.destroy', $depense) }}">
                                        @csrf @method('DELETE')
                                        <button type="button" class="btn-icon text-red-400 hover:text-red-600"
                                                onclick="window.dispatchEvent(new CustomEvent('confirm-action',{detail:{formId:'form-depense-{{ $depense->id }}',title:'Supprimer cette dépense',message:'Cette action est irréversible.',confirmLabel:'Supprimer',confirmClass:'!bg-red-600 hover:!bg-red-700',danger:true}}))">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $depenses->withQueryString()->links() }}
            </div>
            @else
            <div class="p-10 text-center text-gray-400 text-sm">
                Aucune dépense sur cette période.
            </div>
            @endif
        </div>

        {{-- Par catégorie --}}
        <div x-show="onglet === 'categories'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Prestations par catégorie --}}
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-700">
                    <h2 class="font-bold text-gray-900 dark:text-slate-100">Prestations par catégorie</h2>
                </div>
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase text-gray-500 dark:text-slate-400 bg-gray-50 dark:bg-slate-800/50">
                            <th class="px-4 py-2">Catégorie</th>
                            <th class="px-4 py-2 text-right">Qté</th>
                            <th class="px-4 py-2 text-right">CA (FCFA)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                        @forelse($prestationsParCategorie as $r)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/30">
                                <td class="px-4 py-2 text-gray-800 dark:text-slate-200">{{ $r->categorie_nom ?? 'Sans catégorie' }}</td>
                                <td class="px-4 py-2 text-right text-gray-600 dark:text-slate-400">{{ (int) $r->quantite }}</td>
                                <td class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-slate-100">{{ number_format($r->chiffre_affaires, 0, ',', ' ') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-8 text-center text-gray-400 text-sm">Aucune donnée.</td></tr>
                        @endforelse
                    </tbody>
                    @if($prestationsParCategorie->count() > 0)
                    <tfoot>
                        <tr class="border-t-2 border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/50">
                            <td class="px-4 py-2 font-bold text-gray-700 dark:text-slate-300">Total</td>
                            <td class="px-4 py-2 text-right font-bold text-gray-700 dark:text-slate-300">{{ (int) $prestationsParCategorie->sum('quantite') }}</td>
                            <td class="px-4 py-2 text-right font-bold text-primary-700 dark:text-primary-400">{{ number_format($prestationsParCategorie->sum('chiffre_affaires'), 0, ',', ' ') }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>

            {{-- Produits par catégorie --}}
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-700">
                    <h2 class="font-bold text-gray-900 dark:text-slate-100">Produits par catégorie</h2>
                </div>
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase text-gray-500 dark:text-slate-400 bg-gray-50 dark:bg-slate-800/50">
                            <th class="px-4 py-2">Catégorie</th>
                            <th class="px-4 py-2 text-right">Qté</th>
                            <th class="px-4 py-2 text-right">CA (FCFA)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                        @forelse($produitsParCategorie as $r)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/30">
                                <td class="px-4 py-2 text-gray-800 dark:text-slate-200">{{ $r->categorie_nom ?? 'Sans catégorie' }}</td>
                                <td class="px-4 py-2 text-right text-gray-600 dark:text-slate-400">{{ (int) $r->quantite }}</td>
                                <td class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-slate-100">{{ number_format($r->chiffre_affaires, 0, ',', ' ') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-8 text-center text-gray-400 text-sm">Aucune donnée.</td></tr>
                        @endforelse
                    </tbody>
                    @if($produitsParCategorie->count() > 0)
                    <tfoot>
                        <tr class="border-t-2 border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/50">
                            <td class="px-4 py-2 font-bold text-gray-700 dark:text-slate-300">Total</td>
                            <td class="px-4 py-2 text-right font-bold text-gray-700 dark:text-slate-300">{{ (int) $produitsParCategorie->sum('quantite') }}</td>
                            <td class="px-4 py-2 text-right font-bold text-primary-700 dark:text-primary-400">{{ number_format($produitsParCategorie->sum('chiffre_affaires'), 0, ',', ' ') }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- Onglet Trésorerie prévisionnelle --}}
        <div x-show="onglet === 'tresorerie'" class="space-y-6">
            {{-- Sélecteur horizon --}}
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-gray-800 dark:text-slate-100">Projection financière</h2>
                <form method="GET" action="{{ route('dashboard.finances.index') }}" class="flex gap-2 items-center">
                    <input type="hidden" name="periode" value="{{ $periode }}">
                    @if(request('debut'))<input type="hidden" name="debut" value="{{ request('debut') }}">@endif
                    @if(request('fin'))<input type="hidden" name="fin" value="{{ request('fin') }}">@endif
                    <input type="hidden" name="onglet" value="tresorerie">
                    <label class="text-sm text-gray-600 dark:text-slate-300">Horizon</label>
                    <select name="jours_previ" class="form-input text-sm" onchange="this.form.submit()">
                        @foreach([7, 14, 30, 60, 90] as $j)
                            <option value="{{ $j }}" @selected($joursPrevi === $j)>{{ $j }} jours</option>
                        @endforeach
                    </select>
                </form>
            </div>

            {{-- KPI trésorerie, classés par degré de certitude --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="card p-4 border-l-4 border-emerald-500">
                    <p class="text-xs text-gray-500 dark:text-slate-400 uppercase">Certain</p>
                    <p class="text-2xl font-bold text-emerald-600 mt-1">{{ number_format($revenusCertains, 0, ',', ' ') }} F</p>
                    <p class="text-xs text-gray-400 mt-1">
                        Créances clients et commandes déjà engagées
                    </p>
                    <p class="text-[11px] text-gray-500 dark:text-slate-400 mt-1">
                        @if(($creditsPrevu ?? 0) > 0) {{ $nbEcheancesPrevu }} échéance{{ $nbEcheancesPrevu > 1 ? 's' : '' }} de crédit @endif
                        @if($nbCommandesPrevues > 0) · {{ $nbCommandesPrevues }} commande{{ $nbCommandesPrevues > 1 ? 's' : '' }} @endif
                    </p>
                </div>
                <div class="card p-4 border-l-4 border-blue-500">
                    <p class="text-xs text-gray-500 dark:text-slate-400 uppercase">Probable</p>
                    <p class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($revenusProbables, 0, ',', ' ') }} F</p>
                    <p class="text-xs text-gray-400 mt-1">
                        {{ $rdvFuturs->count() }} rendez-vous pris — le client peut encore annuler
                    </p>
                </div>
                <div class="card p-4 border-l-4 border-amber-500">
                    <p class="text-xs text-gray-500 dark:text-slate-400 uppercase">Espéré</p>
                    <p class="text-2xl font-bold text-amber-600 mt-1">{{ number_format($revenusEsperes, 0, ',', ' ') }} F</p>
                    <p class="text-xs text-gray-400 mt-1">
                        {{ $nbDevisPrevu }} devis envoyé{{ $nbDevisPrevu > 1 ? 's' : '' }}, pas encore accepté{{ $nbDevisPrevu > 1 ? 's' : '' }}
                    </p>
                </div>
            </div>

            {{-- Solde prudent vs solde complet --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="card p-4">
                    <p class="text-xs text-gray-500 dark:text-slate-400 uppercase">Dépenses prévues</p>
                    <p class="text-2xl font-bold text-red-600 mt-1">{{ number_format($projectionDepenses, 0, ',', ' ') }} F</p>
                    <p class="text-xs text-gray-400 mt-1">Projection {{ $joursPrevi }} j (moyenne 90 j)</p>
                </div>
                <div class="card p-4 {{ $soldePrudent >= 0 ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                    <p class="text-xs text-gray-500 dark:text-slate-400 uppercase">Solde prudent</p>
                    <p class="text-2xl font-bold mt-1 {{ $soldePrudent >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                        {{ ($soldePrudent >= 0 ? '+' : '') . number_format($soldePrudent, 0, ',', ' ') }} F
                    </p>
                    <p class="text-[11px] text-gray-500 mt-1">Certain + probable − dépenses</p>
                </div>
                <div class="card p-4">
                    <p class="text-xs text-gray-500 dark:text-slate-400 uppercase">Solde avec les devis</p>
                    <p class="text-2xl font-bold mt-1 {{ $soldePrevi >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                        {{ ($soldePrevi >= 0 ? '+' : '') . number_format($soldePrevi, 0, ',', ' ') }} F
                    </p>
                    <p class="text-[11px] text-gray-500 mt-1">Si tous les devis aboutissent</p>
                </div>
            </div>

            <p class="text-[11px] text-gray-400 dark:text-gray-500">
                La tendance statistique des ventes ({{ number_format($projectionVentes, 0, ',', ' ') }} F sur {{ $joursPrevi }} j)
                n'est pas additionnée à ces soldes : elle recouvre déjà les rendez-vous, devis, commandes et échéances comptés ci-dessus.
            </p>

            {{-- Graphique --}}
            <div class="card p-4">
                <h3 class="font-semibold text-gray-800 dark:text-slate-100 mb-4">Évolution jour par jour</h3>
                <canvas id="chartTreso" height="80"></canvas>
            </div>

            {{-- Tableau RDV futurs --}}
            <div class="card p-4">
                <h3 class="font-semibold text-gray-800 dark:text-slate-100 mb-3">RDV à venir détaillés</h3>
                @if($rdvFuturs->count())
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-slate-800">
                                <tr>
                                    <th class="px-3 py-2 text-left">Date</th>
                                    <th class="px-3 py-2 text-left">Client</th>
                                    <th class="px-3 py-2 text-left">Prestations</th>
                                    <th class="px-3 py-2 text-right">Montant</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                                @foreach($rdvFuturs->sortBy('debut_le')->take(20) as $r)
                                    <tr>
                                        <td class="px-3 py-2">{{ $r->debut_le->format('d/m H:i') }}</td>
                                        <td class="px-3 py-2">{{ $r->client->nom_complet ?? 'Inconnu' }}</td>
                                        <td class="px-3 py-2">{{ $r->prestations->pluck('nom')->implode(', ') }}</td>
                                        <td class="px-3 py-2 text-right">{{ number_format($r->prestations->sum('prix'), 0, ',', ' ') }} F</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-gray-500 text-sm">Aucun RDV planifié.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal dépense --}}
    <div x-data="{
            show: false,
            formAction: '{{ route('dashboard.depenses.store') }}',
            method: 'POST',
            form: { description: '', categorie: 'autres', montant: '', date: '{{ today()->format('Y-m-d') }}', notes: '' },
            init() {
                window.addEventListener('open-depense-modal', () => {
                    this.formAction = '{{ route('dashboard.depenses.store') }}';
                    this.method = 'POST';
                    this.form = { description: '', categorie: 'autres', montant: '', date: '{{ today()->format('Y-m-d') }}', notes: '' };
                    this.show = true;
                });
                window.addEventListener('edit-depense', (e) => {
                    this.formAction = `/dashboard/depenses/${e.detail.id}`;
                    this.method = 'PUT';
                    this.form = { description: e.detail.description, categorie: e.detail.categorie, montant: e.detail.montant, date: e.detail.date, notes: e.detail.notes };
                    this.show = true;
                });
            }
         }"
         x-show="show"
         x-cloak
         class="modal-backdrop"
         @keydown.escape.window="show = false">
        <div class="modal" x-transition @click.stop>
            <div class="modal-header">
                <h3 class="modal-title">Dépense</h3>
                <button @click="show = false" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <form :action="formAction" method="POST" class="space-y-4">
                    @csrf
                    <template x-if="method === 'PUT'">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div class="form-group">
                        <label class="form-label">Description *</label>
                        <input type="text" name="description" required maxlength="200"
                               x-model="form.description" class="form-input"
                               placeholder="Achat produits soins">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Catégorie *</label>
                        <select name="categorie" required x-model="form.categorie" class="form-select">
                            <option value="produits">Achat produits</option>
                            <option value="salaires">Salaires</option>
                            <option value="loyer">Loyer</option>
                            <option value="fournitures">Fournitures (eau/élec)</option>
                            <option value="equipement">Matériel / équipement</option>
                            <option value="marketing">Marketing</option>
                            <option value="autres">Autres charges</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Montant (FCFA) *</label>
                            <input type="number" name="montant" required min="1" step="1"
                                   x-model="form.montant" class="form-input" placeholder="15000">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date *</label>
                            <input type="date" name="date" required
                                   x-model="form.date" class="form-input">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="2" maxlength="500"
                                  x-model="form.notes" class="form-textarea"></textarea>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="show = false" class="btn btn-outline flex-1 justify-center">Annuler</button>
                        <button type="submit" class="btn-primary flex-1 justify-center">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('chartTreso');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($jourLabel),
                    datasets: [
                        {
                            label: 'Entrées (F)',
                            data: @json($jourEntrees),
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16,185,129,0.1)',
                            tension: 0.3
                        },
                        {
                            label: 'Sorties (F)',
                            data: @json($jourSorties),
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239,68,68,0.1)',
                            tension: 0.3
                        },
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'top' }
                    }
                }
            });
        }
    });
    </script>
    @endpush
</x-dashboard-layout>
