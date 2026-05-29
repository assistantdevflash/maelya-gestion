<x-dashboard-layout>
    <x-slot name="title">Nouvel inventaire</x-slot>

    <div class="space-y-4">
        <h1 class="text-2xl font-display font-bold text-gray-900">Nouvel inventaire physique</h1>
        <p class="text-sm text-gray-500">Comptez physiquement chaque produit et saisissez la quantité réelle. Les écarts seront calculés automatiquement.</p>

        <form method="POST" action="{{ route('dashboard.inventaires.store') }}" class="space-y-4">
            @csrf
            <div class="card p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Date inventaire *</label>
                    <input type="date" name="date_inventaire" value="{{ now()->toDateString() }}" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-input">
                </div>
            </div>

            <div class="card p-5">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-3 py-2 text-left">Produit</th>
                            <th class="px-3 py-2 text-right">Stock théorique</th>
                            <th class="px-3 py-2 text-right">CMP</th>
                            <th class="px-3 py-2 text-right w-32">Stock compté</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($produits as $p)
                            <tr>
                                <td class="px-3 py-2 font-semibold">{{ $p->nom }} <span class="text-xs text-gray-400">({{ $p->unite }})</span></td>
                                <td class="px-3 py-2 text-right">{{ $p->stock }}</td>
                                <td class="px-3 py-2 text-right text-xs">{{ number_format($p->cout_moyen_pondere ?: $p->prix_achat, 0, ',', ' ') }} F</td>
                                <td class="px-3 py-2">
                                    <input type="number" name="comptes[{{ $p->id }}]" value="{{ $p->stock }}" min="0" required class="form-input text-right">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('dashboard.inventaires.index') }}" class="btn-outline">Annuler</a>
                <button type="submit" class="btn-primary">Enregistrer (brouillon)</button>
            </div>
        </form>
    </div>
</x-dashboard-layout>
