<x-dashboard-layout>
    <x-slot name="title">Nouveau bon de commande</x-slot>

    <div x-data="bcCreate({{ Js::from($produits) }})" class="space-y-4">
        <h1 class="text-2xl font-display font-bold text-gray-900">Nouveau bon de commande</h1>

        <form method="POST" action="{{ route('dashboard.bons-commande.store') }}" class="space-y-4">
            @csrf
            <div class="card p-5 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="form-label">Fournisseur</label>
                    <select name="fournisseur_id" class="form-select">
                        <option value="">— Aucun —</option>
                        @foreach($fournisseurs as $f)
                            <option value="{{ $f->id }}">{{ $f->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Date commande *</label>
                    <input type="date" name="date_commande" value="{{ now()->toDateString() }}" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Livraison prévue</label>
                    <input type="date" name="date_livraison_prevue" class="form-input">
                </div>
            </div>

            <div class="card p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-bold text-gray-900">Lignes</h2>
                    <button type="button" @click="ajouterLigne()" class="btn-outline text-xs">+ Ajouter une ligne</button>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-2 py-2 text-left">Produit</th>
                            <th class="px-2 py-2 text-left">Libellé</th>
                            <th class="px-2 py-2 text-right w-24">Qté</th>
                            <th class="px-2 py-2 text-right w-32">Prix HT</th>
                            <th class="px-2 py-2 text-right w-32">Sous-total</th>
                            <th class="w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(l, i) in lignes" :key="i">
                            <tr>
                                <td class="px-2 py-2">
                                    <select :name="`lignes[${i}][produit_id]`" x-model="l.produit_id" @change="onProduitChange(i)" class="form-select text-xs">
                                        <option value="">— Libre —</option>
                                        <template x-for="p in produits" :key="p.id">
                                            <option :value="p.id" x-text="p.nom"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="px-2 py-2">
                                    <input type="text" :name="`lignes[${i}][libelle]`" x-model="l.libelle" required class="form-input text-xs">
                                </td>
                                <td class="px-2 py-2">
                                    <input type="number" min="1" :name="`lignes[${i}][quantite]`" x-model.number="l.quantite" required class="form-input text-xs text-right">
                                </td>
                                <td class="px-2 py-2">
                                    <input type="number" min="0" :name="`lignes[${i}][prix]`" x-model.number="l.prix" required class="form-input text-xs text-right">
                                </td>
                                <td class="px-2 py-2 text-right font-semibold" x-text="formatNum(l.quantite * l.prix) + ' F'"></td>
                                <td><button type="button" @click="lignes.splice(i, 1)" class="text-red-500">×</button></td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr class="font-bold">
                            <td colspan="4" class="px-2 py-3 text-right">Total HT :</td>
                            <td class="px-2 py-3 text-right text-lg" x-text="formatNum(total) + ' F'"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="card p-5">
                <label class="form-label">Notes</label>
                <textarea name="notes" rows="2" class="form-input"></textarea>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('dashboard.bons-commande.index') }}" class="btn-outline">Annuler</a>
                <button type="submit" class="btn-primary" :disabled="!lignes.length">Créer le bon</button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        function bcCreate(produits) {
            return {
                produits,
                lignes: [{ produit_id: '', libelle: '', quantite: 1, prix: 0 }],
                get total() { return this.lignes.reduce((s, l) => s + (l.quantite * l.prix), 0); },
                ajouterLigne() { this.lignes.push({ produit_id: '', libelle: '', quantite: 1, prix: 0 }); },
                onProduitChange(i) {
                    const l = this.lignes[i];
                    const p = this.produits.find(x => x.id === l.produit_id);
                    if (p) { l.libelle = p.nom; if (!l.prix) l.prix = p.prix_achat || 0; }
                },
                formatNum(n) { return new Intl.NumberFormat('fr-FR').format(n); },
            };
        }
    </script>
    @endpush
</x-dashboard-layout>
