<x-dashboard-layout>
    <x-slot name="title">Nouveau crédit</x-slot>

    <div class="max-w-2xl mx-auto space-y-5"
         x-data="creditForm(@json($produits), @json($prestations))">
        <div>
            <a href="{{ route('dashboard.credits.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-700 mb-3 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Retour aux crédits
            </a>
            <h1 class="page-title">Nouveau crédit client</h1>
            <p class="text-sm text-gray-500 mt-1">Créez un crédit directement avec échéancier</p>
        </div>

        <form method="POST" action="{{ route('dashboard.credits.store') }}" class="space-y-4"
              x-on:submit="prepareSubmit">
            @csrf

            @if($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-700 space-y-1">
                @foreach($errors->all() as $e)<p>• {{ $e }}</p>@endforeach
            </div>
            @endif

            {{-- CLIENT --}}
            <div class="card p-5 space-y-4">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Client <span class="text-red-500">*</span></p>
                <select name="client_id" required class="form-input"
                        x-ref="clientSelect"
                        x-on:change="if($el.value){ const o=$el.options[$el.selectedIndex]; clientNom=o.textContent.trim(); }">
                    <option value="">— Choisir un client —</option>
                    @foreach($clients as $c)
                    <option value="{{ $c->id }}" {{ old('client_id') == $c->id ? 'selected' : '' }}>
                        {{ $c->nom_complet }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- ARTICLES --}}
            <div class="card p-5 space-y-4">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Articles <span class="text-red-500">*</span></p>
                    <button type="button" @click="addArticle()"
                            class="text-xs text-primary-600 hover:text-primary-700 font-medium">
                        + Ajouter
                    </button>
                </div>

                <template x-for="(art, i) in articles" :key="i">
                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                        {{-- Type --}}
                        <select x-model="art.type" class="form-input text-xs w-28" @change="art.item_id=null;art.nom='';art.prix=0">
                            <option value="libre">Libre</option>
                            <option value="produit">Produit</option>
                            <option value="prestation">Prestation</option>
                        </select>

                        {{-- Sélecteur produit/prestation --}}
                        <template x-if="art.type === 'produit'">
                            <select x-model="art.item_id" class="form-input text-xs flex-1"
                                    @change="fillFromCatalog(art, 'produit')">
                                <option value="">— Produit —</option>
                                <template x-for="p in produitsData" :key="p.id">
                                    <option :value="p.id" x-text="p.nom + ' (' + p.prix_vente + ' F)'"></option>
                                </template>
                            </select>
                        </template>
                        <template x-if="art.type === 'prestation'">
                            <select x-model="art.item_id" class="form-input text-xs flex-1"
                                    @change="fillFromCatalog(art, 'prestation')">
                                <option value="">— Prestation —</option>
                                <template x-for="p in prestationsData" :key="p.id">
                                    <option :value="p.id" x-text="p.nom + ' (' + p.prix + ' F)'"></option>
                                </template>
                            </select>
                        </template>

                        {{-- Nom libre --}}
                        <template x-if="art.type === 'libre'">
                            <input type="text" x-model="art.nom" placeholder="Nom article" class="form-input text-xs flex-1">
                        </template>

                        {{-- Prix --}}
                        <input type="number" x-model="art.prix" min="1" class="form-input text-xs w-24" placeholder="Prix">

                        {{-- Qté --}}
                        <input type="number" x-model="art.quantite" min="1" class="form-input text-xs w-16" placeholder="Qté">

                        {{-- Sous-total --}}
                        <span class="text-xs font-semibold text-gray-700 w-20 text-right"
                              x-text="formatMoney(art.prix * art.quantite) + ' F'"></span>

                        {{-- Supprimer --}}
                        <button type="button" @click="articles.splice(i,1)" class="text-red-400 hover:text-red-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </template>

                <p x-show="articles.length === 0" class="text-sm text-gray-400 text-center py-4">Aucun article ajouté</p>

                {{-- Total --}}
                <div class="flex justify-between items-center pt-3 border-t border-gray-100">
                    <span class="text-sm font-semibold text-gray-700">Total</span>
                    <span class="text-lg font-bold text-gray-900" x-text="formatMoney(totalBrut) + ' FCFA'"></span>
                </div>
            </div>

            <input type="hidden" name="articles" x-model="articlesJson">

            {{-- APPORT + ÉCHÉANCIER --}}
            <div class="card p-5 space-y-4">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Modalités de crédit</p>

                <div>
                    <label class="form-label">Apport initial (FCFA)</label>
                    <input type="number" name="apport_initial" x-model="apport" min="0" :max="totalBrut"
                           class="form-input" value="{{ old('apport_initial', 0) }}">
                    <p class="text-xs text-gray-400 mt-1">
                        Reste à payer : <strong x-text="formatMoney(reste) + ' FCFA'"></strong>
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Nombre d'échéances</label>
                        <input type="number" name="nb_echeances" min="1" max="24"
                               value="{{ old('nb_echeances', 3) }}"
                               class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Fréquence</label>
                        <select name="frequence" class="form-input">
                            <option value="mensuel" {{ old('frequence') === 'mensuel' ? 'selected' : '' }}>Mensuelle</option>
                            <option value="hebdomadaire" {{ old('frequence') === 'hebdomadaire' ? 'selected' : '' }}>Hebdomadaire</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- NOTES --}}
            <div class="card p-5">
                <label class="form-label">Notes (optionnel)</label>
                <textarea name="notes" rows="2" class="form-input" placeholder="Détails du crédit...">{{ old('notes') }}</textarea>
            </div>

            <button type="submit" class="btn-primary w-full" :disabled="articles.length === 0 || totalBrut <= 0">
                Créer le crédit
            </button>
        </form>
    </div>

    <script>
    function creditForm(produits, prestations) {
        return {
            produitsData: produits,
            prestationsData: prestations,
            articles: [],
            apport: {{ old('apport_initial', 0) }},
            clientNom: '',

            get totalBrut() {
                return this.articles.reduce((sum, a) => sum + ((a.prix || 0) * (a.quantite || 1)), 0);
            },

            get reste() {
                return Math.max(0, this.totalBrut - (this.apport || 0));
            },

            get articlesJson() {
                return JSON.stringify(this.articles.map(a => ({
                    type: a.type,
                    item_id: a.item_id || null,
                    nom: a.nom || '',
                    prix: a.prix || 0,
                    quantite: a.quantite || 1,
                })));
            },

            addArticle() {
                this.articles.push({ type: 'libre', item_id: null, nom: '', prix: 0, quantite: 1 });
            },

            fillFromCatalog(art, catType) {
                const id = art.item_id;
                if (!id) return;
                if (catType === 'produit') {
                    const p = this.produitsData.find(x => x.id === id);
                    if (p) { art.nom = p.nom; art.prix = p.prix_vente; }
                } else {
                    const p = this.prestationsData.find(x => x.id === id);
                    if (p) { art.nom = p.nom; art.prix = p.prix; }
                }
            },

            formatMoney(n) {
                return new Intl.NumberFormat('fr-FR').format(n || 0);
            },

            prepareSubmit() {
                // rien de spécial
            }
        }
    }
    </script>
</x-dashboard-layout>
