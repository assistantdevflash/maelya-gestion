<x-dashboard-layout>
<x-slot name="title">Rapport par catégorie</x-slot>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100 mb-6">Rapport par catégorie</h1>

    <form method="GET" class="card mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
            <div class="form-group">
                <label class="form-label">Du</label>
                <input type="date" name="debut" value="{{ $debut->toDateString() }}" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">Au</label>
                <input type="date" name="fin" value="{{ $fin->toDateString() }}" class="form-input">
            </div>
            <button class="btn-primary">Filtrer</button>
        </div>
    </form>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card">
            <h2 class="text-lg font-bold text-gray-900 dark:text-slate-100 mb-4">Prestations</h2>
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase text-gray-500 dark:text-slate-400">
                        <th class="py-2">Catégorie</th>
                        <th class="py-2 text-right">Quantité</th>
                        <th class="py-2 text-right">CA (FCFA)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                    @forelse($prestations as $r)
                        <tr>
                            <td class="py-2 text-gray-800 dark:text-slate-200">{{ $r->categorie_nom ?? 'Sans catégorie' }}</td>
                            <td class="py-2 text-right">{{ (int) $r->quantite }}</td>
                            <td class="py-2 text-right font-semibold">{{ number_format($r->chiffre_affaires, 0, ',', ' ') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-4 text-center text-gray-500">Aucune donnée.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-gray-300 dark:border-slate-700">
                        <td class="py-2 font-bold">Total</td>
                        <td></td>
                        <td class="py-2 text-right font-bold text-purple-700 dark:text-purple-300">{{ number_format($totalPrestations, 0, ',', ' ') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="card">
            <h2 class="text-lg font-bold text-gray-900 dark:text-slate-100 mb-4">Produits</h2>
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase text-gray-500 dark:text-slate-400">
                        <th class="py-2">Catégorie</th>
                        <th class="py-2 text-right">Quantité</th>
                        <th class="py-2 text-right">CA (FCFA)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                    @forelse($produits as $r)
                        <tr>
                            <td class="py-2 text-gray-800 dark:text-slate-200">{{ $r->categorie_nom ?? 'Sans catégorie' }}</td>
                            <td class="py-2 text-right">{{ (int) $r->quantite }}</td>
                            <td class="py-2 text-right font-semibold">{{ number_format($r->chiffre_affaires, 0, ',', ' ') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-4 text-center text-gray-500">Aucune donnée.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-gray-300 dark:border-slate-700">
                        <td class="py-2 font-bold">Total</td>
                        <td></td>
                        <td class="py-2 text-right font-bold text-purple-700 dark:text-purple-300">{{ number_format($totalProduits, 0, ',', ' ') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
</x-dashboard-layout>
