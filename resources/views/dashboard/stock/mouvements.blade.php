<x-dashboard-layout>
    <x-slot name="title">Mouvements de stock</x-slot>

    @php
        $badges = [
            'entree'           => ['Entrée', 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'],
            'annulation_vente' => ['Annulation', 'bg-sky-50 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300'],
            'sortie_vente'     => ['Sortie', 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'],
            'correction'       => ['Correction', 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'],
        ];
        $motifs = [
            'reservation'   => 'Réservation commande',
            'liberation'    => 'Libération commande',
            'bon_commande'  => 'Bon de commande',
            'inventaire'    => 'Inventaire',
            'reconciliation' => 'Réconciliation',
        ];
    @endphp

    <div class="space-y-5">

        {{-- En-tête --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-display font-bold text-gray-900 dark:text-slate-100 tracking-tight">
                    Mouvements de stock
                </h1>
                <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">
                    Chaque entrée, sortie et correction, avec le stock avant et après.
                </p>
            </div>
            <a href="{{ route('dashboard.stock.index') }}" class="btn-outline whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Ajuster le stock
            </a>
        </div>

        {{-- Totaux de la sélection --}}
        <div class="grid grid-cols-2 gap-4">
            <div class="card p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Entrées</p>
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">+{{ $entrees }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Sorties</p>
                <p class="text-2xl font-bold text-rose-600 dark:text-rose-400 mt-1">−{{ $sorties }}</p>
            </div>
        </div>

        {{-- Filtres --}}
        <div class="card p-4">
            <form method="GET" action="{{ route('dashboard.stock.mouvements') }}" class="flex flex-wrap items-end gap-3">
                <div class="relative flex-1 min-w-52">
                    <label for="q" class="form-label">Produit</label>
                    <input type="text" name="q" id="q" value="{{ $recherche }}"
                           placeholder="Nom, référence, code-barre…" class="form-input">
                </div>

                <div>
                    <label for="type" class="form-label">Type</label>
                    <select name="type" id="type" class="form-input">
                        <option value="">Tous</option>
                        @foreach(\App\Http\Controllers\Dashboard\StockController::TYPES as $code => $libelle)
                        <option value="{{ $code }}" {{ $type === $code ? 'selected' : '' }}>{{ $libelle }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="du" class="form-label">Du</label>
                    <input type="date" name="du" id="du" value="{{ $du }}" class="form-input">
                </div>

                <div>
                    <label for="au" class="form-label">Au</label>
                    <input type="date" name="au" id="au" value="{{ $au }}" class="form-input">
                </div>

                <button type="submit" class="btn-primary">Filtrer</button>

                @if($recherche !== '' || $type !== '' || $du || $au)
                <a href="{{ route('dashboard.stock.mouvements') }}" class="text-sm font-semibold text-gray-500 hover:text-gray-700 underline">
                    Réinitialiser
                </a>
                @endif
            </form>
        </div>

        {{-- Tableau --}}
        @if($mouvements->isEmpty())
        <div class="card p-12 text-center">
            <div class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
            </div>
            <p class="font-semibold text-gray-700 dark:text-slate-200">Aucun mouvement pour ces critères</p>
            <p class="text-sm text-gray-400 mt-1">Les entrées, ventes et corrections apparaîtront ici.</p>
        </div>
        @else
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-slate-800/60 border-b border-gray-100 dark:border-slate-700">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-slate-300">Date</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-slate-300">Produit</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-slate-300">Type</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-slate-300">Qté</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-slate-300 hidden sm:table-cell">Stock</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-slate-300 hidden lg:table-cell">Détail</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-slate-300 hidden xl:table-cell">Par</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-slate-700/60">
                        @foreach($mouvements as $m)
                        @php
                            [$libelle, $classes] = $badges[$m->type] ?? [$m->type, 'bg-gray-100 text-gray-600'];
                            $entree = in_array($m->type, ['entree', 'annulation_vente'], true);
                        @endphp
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="px-4 py-3 whitespace-nowrap text-gray-500 dark:text-slate-400">
                                <span class="block text-gray-700 dark:text-slate-200 font-medium">{{ $m->created_at?->format('d/m/Y') }}</span>
                                <span class="text-xs">{{ $m->created_at?->format('H:i') }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if($m->produit)
                                <a href="{{ route('dashboard.produits.show', $m->produit_id) }}"
                                   class="font-medium text-gray-900 dark:text-slate-100 hover:text-primary-600 dark:hover:text-primary-400">
                                    {{ $m->produit->nom }}
                                </a>
                                <span class="block text-xs text-gray-400">{{ $m->produit->reference ?? '—' }}</span>
                                @else
                                <span class="text-gray-400 italic">Produit supprimé</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $classes }}">
                                    {{ $libelle }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-semibold {{ $entree ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                {{ $entree ? '+' : '−' }}{{ $m->quantite }}
                            </td>
                            <td class="px-4 py-3 text-center hidden sm:table-cell text-gray-500 dark:text-slate-400 font-mono text-xs whitespace-nowrap">
                                {{ $m->stock_avant }} → <span class="font-semibold text-gray-800 dark:text-slate-200">{{ $m->stock_apres }}</span>
                            </td>
                            <td class="px-4 py-3 hidden lg:table-cell text-xs text-gray-500 dark:text-slate-400">
                                @php $motif = $m->motif ? ($motifs[$m->motif] ?? $m->motif) : null; @endphp
                                @if($motif)<span class="block font-medium text-gray-600 dark:text-slate-300">{{ $motif }}</span>@endif
                                {{ $m->note ?? '—' }}
                            </td>
                            <td class="px-4 py-3 hidden xl:table-cell text-xs text-gray-500 dark:text-slate-400">
                                {{ $m->user ? $m->user->prenom . ' ' . $m->user->nom_famille : 'Système' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-2">
            {{ $mouvements->links() }}
        </div>
        @endif
    </div>
</x-dashboard-layout>
