<x-dashboard-layout>
    <x-slot name="title">Réapprovisionnement</x-slot>

    <div class="space-y-5">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-display font-bold text-gray-900 dark:text-slate-100 tracking-tight">Réapprovisionnement</h1>
                <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">
                    Produits sous leur seuil d'alerte, regroupés par fournisseur.
                </p>
            </div>
            <a href="{{ route('dashboard.stock.index') }}" class="btn-outline self-start">← Retour au stock</a>
        </div>

        @if(session('error'))<div class="alert-error">{{ session('error') }}</div>@endif

        @if($produits->isEmpty())
        <div class="card p-12 text-center">
            <div class="w-16 h-16 rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <p class="font-semibold text-gray-700 dark:text-slate-200">Aucun produit sous le seuil</p>
            <p class="text-sm text-gray-400 mt-1">Tous vos stocks sont au-dessus du seuil d'alerte.</p>
        </div>
        @else

        <form method="POST" action="{{ route('dashboard.stock.generer-bc') }}"
              x-data="{ tout: true }">
            @csrf

            <div class="card p-4 mb-4 flex flex-wrap items-center justify-between gap-3">
                <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-slate-300">
                    <input type="checkbox" x-model="tout"
                           @change="$root.querySelectorAll('input[type=number][name^=lignes]').forEach(i => i.disabled = !tout)"
                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    Tout sélectionner
                </label>
                <button type="submit" class="btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Générer les bons de commande
                </button>
            </div>

            @foreach($groupes as $fournisseurId => $lignes)
            @php
                $fournisseur = $lignes->first()->fournisseur;
                $total = $lignes->sum(fn ($p) => ($suggestions[$p->id] ?? 1) * (int) $p->prix_achat);
            @endphp
            <div class="card overflow-hidden mb-4">
                <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100 dark:border-slate-700 bg-gray-50/60 dark:bg-slate-800/40">
                    <div class="flex items-center gap-2 min-w-0">
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/></svg>
                        <span class="font-semibold text-gray-900 dark:text-slate-100 truncate">
                            {{ $fournisseur?->nom ?? 'Sans fournisseur' }}
                        </span>
                        <span class="text-xs text-gray-400">({{ $lignes->count() }})</span>
                    </div>
                    <span class="text-sm font-semibold text-gray-700 dark:text-slate-200 whitespace-nowrap">
                        ≈ {{ number_format($total, 0, ',', ' ') }} F
                    </span>
                </div>

                @if(! $fournisseur)
                <p class="px-5 py-2 text-xs text-amber-700 dark:text-amber-300 bg-amber-50/60 dark:bg-amber-950/20">
                    Ces produits n'ont pas de fournisseur : ils seront ignorés. Renseignez le fournisseur dans la fiche produit.
                </p>
                @endif

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-xs uppercase text-gray-500 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-2 text-left w-10"></th>
                                <th class="px-4 py-2 text-left">Produit</th>
                                <th class="px-4 py-2 text-right">Stock</th>
                                <th class="px-4 py-2 text-right">Seuil</th>
                                <th class="px-4 py-2 text-right w-28">À commander</th>
                                <th class="px-4 py-2 text-right hidden sm:table-cell">Coût estimé</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-slate-700/60">
                            @foreach($lignes as $p)
                            @php $suggere = $suggestions[$p->id] ?? 1; @endphp
                            <tr>
                                <td class="px-4 py-2.5">
                                    <input type="checkbox" checked
                                           @if(! $fournisseur) disabled @endif
                                           @change="$el.closest('tr').querySelector('input[type=number]').disabled = !$el.checked"
                                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                           aria-label="Commander {{ $p->nom }}">
                                </td>
                                <td class="px-4 py-2.5">
                                    <a href="{{ route('dashboard.produits.show', $p) }}"
                                       class="font-medium text-gray-900 dark:text-slate-100 hover:text-primary-600">
                                        {{ $p->nom }}
                                    </a>
                                    <span class="block text-xs text-gray-400">{{ $p->categorie?->nom ?? '—' }}</span>
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono {{ $p->stock <= 0 ? 'text-rose-600 font-semibold' : 'text-amber-600' }}">
                                    {{ $p->stock }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-gray-400">{{ $p->seuil_alerte }}</td>
                                <td class="px-4 py-2.5 text-right">
                                    <input type="number" min="1" max="9999" value="{{ $suggere }}"
                                           @if($fournisseur) name="lignes[{{ $p->id }}]" @else disabled @endif
                                           class="form-input text-right w-20 py-1.5">
                                </td>
                                <td class="px-4 py-2.5 text-right text-xs text-gray-500 hidden sm:table-cell font-mono">
                                    {{ number_format($suggere * (int) $p->prix_achat, 0, ',', ' ') }} F
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endforeach
        </form>
        @endif
    </div>
</x-dashboard-layout>
