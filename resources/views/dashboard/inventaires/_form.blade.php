@php
    /**
     * Formulaire de comptage partagé par la création et la modification.
     *
     * @var \Illuminate\Support\Collection $produits
     * @var \Illuminate\Support\Collection|array|null $comptes
     */
    $comptes = $comptes ?? collect();

    $donnees = $produits->map(fn ($p) => [
        'id'          => $p->id,
        'nom'         => $p->nom,
        'unite'       => $p->unite,
        'reference'   => $p->reference,
        'code_barre'  => $p->code_barre,
        'stock'       => (int) $p->stock,
        'categorie_id'=> $p->categorie_id,
        'cmp'         => (int) ($p->cout_moyen_pondere ?: $p->prix_achat),
    ])->values();

    $valeurInitiale = $produits->mapWithKeys(fn ($p) => [
        $p->id => (int) ($comptes[$p->id] ?? $p->stock),
    ]);

    // Une seule requête pour les libellés (évite un N+1 dans le select)
    $categories = \App\Models\CategorieProduit::whereIn(
        'id', $produits->pluck('categorie_id')->filter()->unique()
    )->orderBy('nom')->pluck('nom', 'id');
@endphp

<div class="space-y-4" x-data="comptageInventaire(@js($donnees), @js($valeurInitiale))">

    {{-- Paramètres --}}
    <div class="card p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="date_inventaire" class="form-label">Date de l'inventaire *</label>
            <input type="date" name="date_inventaire" id="date_inventaire"
                   value="{{ old('date_inventaire', isset($inventaire) ? $inventaire->date_inventaire->toDateString() : now()->toDateString()) }}"
                   required class="form-input">
        </div>
        <div>
            <label for="notes" class="form-label">Notes</label>
            <input type="text" name="notes" id="notes" maxlength="255"
                   value="{{ old('notes', $inventaire->notes ?? '') }}"
                   placeholder="Ex : inventaire mensuel, comptage par Awa" class="form-input">
        </div>
    </div>

    {{-- Barre d'outils --}}
    <div class="card p-4 space-y-3">
        <div class="flex flex-wrap items-end gap-3">
            {{-- Scan code-barre --}}
            <div class="w-full sm:w-64">
                <label for="scan" class="form-label">Scanner un produit</label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5a2 2 0 012 2v16a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2zm0 0h5"/></svg>
                    <input type="text" id="scan" x-model="code" @keydown.enter.prevent="scanner()"
                           placeholder="Code-barre puis Entrée"
                           class="form-input pl-9">
                </div>
            </div>

            {{-- Recherche --}}
            <div class="flex-1 min-w-48">
                <label for="recherche" class="form-label">Rechercher</label>
                <input type="text" id="recherche" x-model="recherche"
                       placeholder="Nom ou référence…" class="form-input">
            </div>

            @if($categories->isNotEmpty())
            <div>
                <label for="categorie" class="form-label">Catégorie</label>
                <select id="categorie" x-model="categorie" class="form-input">
                    <option value="">Toutes</option>
                    @foreach($categories as $catId => $nomCat)
                    <option value="{{ $catId }}">{{ $nomCat }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-slate-300 pb-2.5">
                <input type="checkbox" x-model="seulementEcart"
                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                Écarts uniquement
            </label>
        </div>

        <div class="flex flex-wrap items-center gap-4 text-sm pt-2 border-t border-gray-100 dark:border-slate-700">
            <span class="text-gray-500 dark:text-slate-400">
                <strong class="text-gray-900 dark:text-slate-100" x-text="visibles"></strong> produit(s) affiché(s)
            </span>
            <span class="text-gray-500 dark:text-slate-400">
                Écart valorisé :
                <strong :class="totalEcart > 0 ? 'text-emerald-600' : (totalEcart < 0 ? 'text-rose-600' : 'text-gray-900 dark:text-slate-100')"
                        x-text="format(totalEcart) + ' F'"></strong>
            </span>
            <span class="text-gray-500 dark:text-slate-400" x-show="nbEcart > 0">
                <strong class="text-amber-600" x-text="nbEcart"></strong> ligne(s) avec écart
            </span>
        </div>
    </div>

    {{-- Tableau de comptage --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto max-h-[70vh]">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-slate-800/60 sticky top-0 z-10">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-slate-300">Produit</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-slate-300">Stock théorique</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-slate-300 hidden sm:table-cell">CMP</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-slate-300 w-32">Compté</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-slate-300 w-28">Écart</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-slate-700/60">
                    @foreach($produits as $p)
                    <tr x-show="afficher(@js($p->id), @js($p->nom), @js($p->reference), @js($p->categorie_id))"
                        :class="ecart(@js($p->id)) !== 0 ? 'bg-amber-50/40 dark:bg-amber-950/10' : ''">
                        <td class="px-4 py-2.5">
                            <span class="font-medium text-gray-900 dark:text-slate-100">{{ $p->nom }}</span>
                            <span class="block text-xs text-gray-400">
                                {{ $p->unite }}@if($p->reference) · {{ $p->reference }}@endif
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-right font-mono text-gray-500 dark:text-slate-400">{{ (int) $p->stock }}</td>
                        <td class="px-4 py-2.5 text-right text-xs text-gray-400 hidden sm:table-cell font-mono">
                            {{ number_format((int) ($p->cout_moyen_pondere ?: $p->prix_achat), 0, ',', ' ') }}
                        </td>
                        <td class="px-4 py-2.5 text-right">
                            <input type="number" min="0"
                                   id="compte-{{ $p->id }}"
                                   name="comptes[{{ $p->id }}]"
                                   x-model.number="comptes[@js($p->id)]"
                                   class="form-input text-right w-24 py-1.5">
                        </td>
                        <td class="px-4 py-2.5 text-right font-mono font-semibold"
                            :class="ecart(@js($p->id)) > 0 ? 'text-emerald-600' : (ecart(@js($p->id)) < 0 ? 'text-rose-600' : 'text-gray-300 dark:text-slate-600')"
                            x-text="ecart(@js($p->id)) > 0 ? '+' + ecart(@js($p->id)) : ecart(@js($p->id))"></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p class="text-xs text-gray-500 dark:text-slate-400">
            Le stock théorique est celui enregistré à l'ouverture du comptage. Il sera recalculé à la validation.
        </p>
        <div class="flex gap-3">
            <a href="{{ route('dashboard.inventaires.index') }}" class="btn-outline">Annuler</a>
            <button type="submit" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                {{ isset($inventaire) ? 'Enregistrer les modifications' : 'Enregistrer le comptage' }}
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function comptageInventaire(donnees, valeursInitiales) {
    return {
        produits: donnees,
        comptes: {},
        recherche: '',
        categorie: '',
        code: '',
        seulementEcart: false,

        init() {
            this.comptes = { ...valeursInitiales };
        },

        normalise(txt) {
            return String(txt ?? '').toLowerCase();
        },

        afficher(id, nom, reference, categorieId) {
            const terme = this.normalise(this.recherche).trim();

            if (this.categorie !== '' && String(categorieId) !== String(this.categorie)) {
                return false;
            }

            if (terme !== '') {
                const cible = this.normalise(nom) + ' ' + this.normalise(reference);
                if (! cible.includes(terme)) return false;
            }

            if (this.seulementEcart && this.ecart(id) === 0) {
                return false;
            }

            return true;
        },

        ecart(id) {
            const produit = this.produits.find(p => p.id === id);
            if (! produit) return 0;

            const compte = this.comptes[id];
            if (compte === null || compte === undefined || compte === '') return 0;

            return Number(compte) - Number(produit.stock);
        },

        valeurEcart(id) {
            const produit = this.produits.find(p => p.id === id);
            return produit ? this.ecart(id) * produit.cmp : 0;
        },

        get nbEcart() {
            return this.produits.filter(p => this.ecart(p.id) !== 0).length;
        },

        get totalEcart() {
            return this.produits.reduce((somme, p) => somme + this.ecart(p.id) * p.cmp, 0);
        },

        get visibles() {
            return this.produits.filter(p => this.afficher(p.id, p.nom, p.reference, p.categorie_id)).length;
        },

        format(montant) {
            return new Intl.NumberFormat('fr-FR').format(Math.round(montant));
        },

        /**
         * Scan code-barre : on retrouve le produit, on efface les filtres et on
         * place le curseur sur sa case de comptage.
         */
        scanner() {
            const code = this.normalise(this.code).trim();
            if (code === '') return;

            const produit = this.produits.find(p =>
                this.normalise(p.code_barre) === code || this.normalise(p.reference) === code
            );

            this.code = '';

            if (! produit) return;

            this.recherche = '';
            this.categorie = '';
            this.seulementEcart = false;

            this.$nextTick(() => {
                const champ = document.getElementById('compte-' + produit.id);
                if (champ) {
                    champ.focus();
                    champ.select();
                }
            });
        },
    };
}
</script>
@endpush
