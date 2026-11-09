<x-dashboard-layout>
    <x-slot name="title">{{ $produit->nom }}</x-slot>

    @php
        $cmp    = (int) ($produit->cout_moyen_pondere ?: $produit->prix_achat);
        $marge  = (int) $produit->prix_vente - $cmp;
        $taux   = $produit->prix_vente > 0 ? round($marge / $produit->prix_vente * 100) : 0;
        $alerte = $produit->stock <= $produit->seuil_alerte;
        $photo  = $produit->photo ?: $produit->imagePrincipale?->chemin;
        $badges = [
            'entree'           => ['Entrée', 'text-emerald-600 dark:text-emerald-400'],
            'annulation_vente' => ['Annulation', 'text-sky-600 dark:text-sky-400'],
            'sortie_vente'     => ['Sortie', 'text-rose-600 dark:text-rose-400'],
            'correction'       => ['Correction', 'text-amber-600 dark:text-amber-400'],
        ];
    @endphp

    <div class="space-y-5">

        {{-- En-tête --}}
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div class="flex items-start gap-4 min-w-0">
                <a href="{{ route('dashboard.produits.index') }}"
                   class="mt-1 p-2 text-gray-400 hover:text-gray-600 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 transition"
                   aria-label="Retour aux produits">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-2xl font-display font-bold text-gray-900 dark:text-slate-100 truncate">{{ $produit->nom }}</h1>
                        @if($alerte)
                        <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">Stock bas</span>
                        @endif
                        @if($produit->visible_boutique)
                        <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">En boutique</span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">
                        {{ $produit->categorie?->nom ?? 'Sans catégorie' }}
                        @if($produit->reference) · Réf. {{ $produit->reference }} @endif
                        @if($produit->code_barre) · {{ $produit->code_barre }} @endif
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-shrink-0">
                <a href="{{ route('dashboard.stock.index', ['q' => $produit->nom]) }}" class="btn-outline whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Stock
                </a>
                <a href="{{ route('dashboard.produits.edit', $produit) }}" class="btn-primary whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Modifier
                </a>
            </div>
        </div>

        {{-- Indicateurs --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="card p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Stock</p>
                <p class="text-2xl font-bold mt-1 {{ $alerte ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-slate-100' }}">
                    {{ $produit->stock }} <span class="text-sm font-normal text-gray-400">{{ $produit->unite }}</span>
                </p>
                <p class="text-[11px] text-gray-400 mt-0.5">Seuil d'alerte : {{ $produit->seuil_alerte }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Valeur du stock</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-slate-100 mt-1">{{ number_format($valeurStock, 0, ',', ' ') }} <span class="text-sm font-normal text-gray-400">F</span></p>
                <p class="text-[11px] text-gray-400 mt-0.5">au coût moyen de {{ number_format($cmp, 0, ',', ' ') }} F</p>
            </div>
            <div class="card p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Marge unitaire</p>
                <p class="text-2xl font-bold mt-1 {{ $marge > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                    {{ number_format($marge, 0, ',', ' ') }} <span class="text-sm font-normal text-gray-400">F</span>
                </p>
                <p class="text-[11px] text-gray-400 mt-0.5">{{ $taux }} % du prix de vente</p>
            </div>
            <div class="card p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Unités vendues</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-slate-100 mt-1">{{ $totalSorti }}</p>
                <p class="text-[11px] text-gray-400 mt-0.5">depuis la mise en stock</p>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-5">
            {{-- Image --}}
            <div class="card p-5">
                @if($photo)
                <img src="{{ \App\Services\ImageVariants::url($photo, 'medium') }}"
                     alt="{{ $produit->nom }}"
                     class="w-full aspect-square object-cover rounded-xl">
                @else
                <div class="w-full aspect-square rounded-xl bg-gray-100 dark:bg-slate-800 flex items-center justify-center">
                    <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                @endif

                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-slate-400">Prix de vente</dt>
                        <dd class="font-semibold text-gray-900 dark:text-slate-100">{{ number_format($produit->prix_vente, 0, ',', ' ') }} F</dd>
                    </div>
                    @if($produit->prix_promo)
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-slate-400">Prix promo</dt>
                        <dd class="font-semibold text-rose-600 dark:text-rose-400">{{ number_format($produit->prix_promo, 0, ',', ' ') }} F</dd>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-slate-400">Coût moyen</dt>
                        <dd class="font-semibold text-gray-900 dark:text-slate-100">{{ number_format($cmp, 0, ',', ' ') }} F</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-slate-400">Statut</dt>
                        <dd class="font-semibold {{ $produit->actif ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400' }}">
                            {{ $produit->actif ? 'Actif' : 'Inactif' }}
                        </dd>
                    </div>
                </dl>

                @if($produit->description)
                <p class="mt-4 text-sm text-gray-600 dark:text-slate-300 leading-relaxed">{{ $produit->description }}</p>
                @endif
            </div>

            {{-- Mouvements --}}
            <div class="card lg:col-span-2 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-slate-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-slate-100">Derniers mouvements</h2>
                    <a href="{{ route('dashboard.stock.mouvements', ['q' => $produit->nom]) }}"
                       class="text-xs font-semibold text-primary-600 dark:text-primary-400 hover:underline">Tout voir</a>
                </div>

                @if($mouvements->isEmpty())
                <p class="p-6 text-sm text-gray-400 text-center">Aucun mouvement enregistré pour ce produit.</p>
                @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-slate-800/60">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-semibold text-gray-600 dark:text-slate-300">Date</th>
                                <th class="px-4 py-2.5 text-left font-semibold text-gray-600 dark:text-slate-300">Type</th>
                                <th class="px-4 py-2.5 text-right font-semibold text-gray-600 dark:text-slate-300">Qté</th>
                                <th class="px-4 py-2.5 text-center font-semibold text-gray-600 dark:text-slate-300">Stock</th>
                                <th class="px-4 py-2.5 text-left font-semibold text-gray-600 dark:text-slate-300 hidden sm:table-cell">Par</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-slate-700/60">
                            @foreach($mouvements as $m)
                            @php
                                [$libelle, $couleur] = $badges[$m->type] ?? [$m->type, 'text-gray-500'];
                                $entree = in_array($m->type, ['entree', 'annulation_vente'], true);
                            @endphp
                            <tr>
                                <td class="px-4 py-2.5 text-gray-500 dark:text-slate-400 whitespace-nowrap">{{ $m->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-2.5">
                                    <span class="font-medium {{ $couleur }}">{{ $libelle }}</span>
                                    @if($m->note)
                                    <span class="block text-[11px] text-gray-400">{{ $m->note }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono {{ $entree ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                    {{ $entree ? '+' : '−' }}{{ $m->quantite }}
                                </td>
                                <td class="px-4 py-2.5 text-center font-mono text-xs text-gray-500">{{ $m->stock_avant }} → {{ $m->stock_apres }}</td>
                                <td class="px-4 py-2.5 hidden sm:table-cell text-xs text-gray-500">
                                    {{ $m->user ? $m->user->prenom . ' ' . $m->user->nom_famille : 'Système' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

        {{-- Ventes --}}
        @if($ventes->isNotEmpty())
        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-700">
                <h2 class="text-base font-semibold text-gray-900 dark:text-slate-100">Dernières ventes</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-slate-800/60">
                        <tr>
                            <th class="px-4 py-2.5 text-left font-semibold text-gray-600 dark:text-slate-300">Date</th>
                            <th class="px-4 py-2.5 text-left font-semibold text-gray-600 dark:text-slate-300">Facture</th>
                            <th class="px-4 py-2.5 text-right font-semibold text-gray-600 dark:text-slate-300">Qté</th>
                            <th class="px-4 py-2.5 text-right font-semibold text-gray-600 dark:text-slate-300">Montant</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-slate-700/60">
                        @foreach($ventes as $ligne)
                        <tr>
                            <td class="px-4 py-2.5 text-gray-500 dark:text-slate-400">{{ $ligne->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2.5 font-mono text-xs text-gray-700 dark:text-slate-200">{{ $ligne->vente?->numero_facture ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-right">{{ $ligne->quantite }}</td>
                            <td class="px-4 py-2.5 text-right font-semibold">{{ number_format($ligne->sous_total, 0, ',', ' ') }} F</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</x-dashboard-layout>
