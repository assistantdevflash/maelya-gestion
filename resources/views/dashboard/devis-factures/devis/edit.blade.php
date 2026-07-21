<x-dashboard-layout>
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('dashboard.devis.show', ['devis' => $devis->id]) }}" class="p-2 text-gray-400 hover:text-gray-600 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 transition">←</a>
        <div><h1 class="text-2xl font-bold text-gray-900 dark:text-white">Modifier {{ $devis->numero }}</h1></div>
    </div>

    <form method="POST" action="{{ route('dashboard.devis.update', ['devis' => $devis->id]) }}" x-data="lignesManager(@js($devis->items->map(fn($i) => ['designation'=>$i->designation,'quantite'=>$i->quantite,'prix_unitaire'=>$i->prix_unitaire,'remise_type'=>$i->remise_type,'remise_valeur'=>$i->remise_valeur,'tva_taux'=>$i->tva_taux, 'pickerOpen'=>false, 'pickerSearch'=>''])->toArray()), @js($catalogue->toArray()))">
        @csrf @method('PUT')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-5">
                <div class="card">
                    <div class="card-header flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Articles / Prestations</h2>
                        <button type="button" @click="ajouter()" class="text-sm text-primary-600 hover:text-primary-700 font-medium">+ Ajouter une ligne</button>
                    </div>
                    <div class="card-body space-y-3">
                        <template x-for="(ligne, i) in lignes" :key="i">
                            <div class="flex flex-wrap items-center gap-2 p-3 bg-gray-50 dark:bg-slate-800 rounded-xl">
                                {{-- Sélecteur catalogue --}}
                                <div class="relative" @click.outside="ligne.pickerOpen = false">
                                    <button type="button" @click="ligne.pickerOpen = !ligne.pickerOpen"
                                        class="p-1.5 text-gray-400 hover:text-primary-600 hover:bg-white rounded-lg transition"
                                        title="Choisir une prestation/produit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    </button>
                                    <div x-show="ligne.pickerOpen" x-cloak
                                        class="absolute left-0 top-full mt-1 w-64 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-gray-200 dark:border-slate-700 z-20 overflow-hidden">
                                        <div class="p-2">
                                            <input type="text" x-model="ligne.pickerSearch" placeholder="Rechercher..."
                                                class="form-input text-xs w-full" @keydown.escape="ligne.pickerOpen = false">
                                        </div>
                                        <div class="max-h-40 overflow-y-auto divide-y divide-gray-100 dark:divide-slate-700">
                                            <template x-for="item in catalogueFiltered(ligne)" :key="item.id">
                                                <button type="button" @click="choisirCatalogue(ligne, item)"
                                                    class="w-full text-left px-3 py-2 text-xs hover:bg-gray-50 dark:hover:bg-slate-700 flex justify-between items-center">
                                                    <span>
                                                        <span x-text="item.designation"></span>
                                                        <span class="ml-1.5 text-[10px] px-1 py-0.5 rounded"
                                                            :class="item.type === 'prestation' ? 'bg-purple-100 text-purple-700' : 'bg-amber-100 text-amber-700'"
                                                            x-text="item.type === 'prestation' ? 'Prest.' : 'Prod.'"></span>
                                                    </span>
                                                    <span class="font-semibold text-gray-900" x-text="format(item.prix) + ' F'"></span>
                                                </button>
                                            </template>
                                            <p x-show="catalogueFiltered(ligne).length === 0" class="px-3 py-2 text-xs text-gray-400 text-center">Aucun résultat</p>
                                        </div>
                                    </div>
                                </div>
                                <input type="text" :name="'ligne_'+i+'_designation'" x-model="ligne.designation" placeholder="Désignation" class="form-input text-sm flex-1 min-w-[140px]" required>
                                <input type="number" :name="'ligne_'+i+'_quantite'" x-model.number="ligne.quantite" min="1" class="form-input text-sm w-16 text-center" required>
                                <input type="number" :name="'ligne_'+i+'_prix'" x-model.number="ligne.prix_unitaire" min="0" placeholder="PU" class="form-input text-sm w-24" required>
                                <button type="button" @click="lignes.splice(i,1)" class="p-1.5 text-red-400 hover:text-red-600" title="Supprimer">✕</button>
                            </div>
                        </template>
                        <p x-show="lignes.length === 0" class="text-sm text-gray-400 text-center py-4">Ajoutez au moins un article ou une prestation.</p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h2 class="text-lg font-semibold">Totaux</h2></div>
                    <div class="card-body space-y-3">
                        <div class="flex justify-between text-sm"><span class="text-gray-500">Sous-total</span><span class="font-bold" x-text="format(sousTotal) + ' F'"></span></div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-gray-500">Remise globale</span>
                            <select name="remise_globale_type" x-model="remiseGlobaleType" class="form-input text-sm w-32">
                                <option value="" {{ $devis->remise_globale_type === null ? 'selected' : '' }}>Aucune</option>
                                <option value="pourcentage" {{ $devis->remise_globale_type === 'pourcentage' ? 'selected' : '' }}>%</option>
                                <option value="montant_fixe" {{ $devis->remise_globale_type === 'montant_fixe' ? 'selected' : '' }}>Fixe</option>
                            </select>
                            <input type="number" name="remise_globale_valeur" x-model.number="remiseGlobaleValeur" min="0" value="{{ $devis->remise_globale_valeur }}" placeholder="0" class="form-input text-sm w-24">
                        </div>
                        <div class="flex justify-between text-sm"><span class="text-gray-500">Total HT</span><span class="font-bold" x-text="format(totalHT) + ' F'"></span></div>
                        <div class="flex items-center gap-3">
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="tva_applicable" value="1" x-model="tvaApplicable" @change="tva = $el.checked ? {{ $devis->tva_taux ?: 18 }} : 0" {{ $devis->tva_applicable ? 'checked' : '' }}> TVA
                            </label>
                            <template x-if="tvaApplicable">
                                <span class="inline-flex items-center gap-1">
                                    <input type="number" name="tva_taux" x-model="tva" min="0" max="100" step="0.01" class="form-input text-sm w-20">
                                    <span class="text-sm text-gray-500">%</span>
                                </span>
                            </template>
                        </div>
                        <div class="flex justify-between text-lg font-bold pt-2 border-t"><span>Total TTC</span><span class="text-primary-600" x-text="format(totalTTC) + ' F'"></span></div>
                    </div>
                </div>
            </div>

            <div class="space-y-5">
                <div class="card">
                    <div class="card-header"><h2 class="text-lg font-semibold">Dates</h2></div>
                    <div class="card-body space-y-3">
                        <div><label class="form-label">Date d'expiration</label><input type="date" name="date_expiration" value="{{ $devis->date_expiration->toDateString() }}" class="form-input" required></div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h2 class="text-lg font-semibold">Notes</h2></div>
                    <div class="card-body"><textarea name="notes" rows="3" placeholder="Notes internes..." class="form-textarea text-sm">{{ $devis->notes }}</textarea></div>
                </div>
                <input type="hidden" name="lignes" :value="JSON.stringify(lignes)">
                <button type="submit" class="btn-primary w-full">Enregistrer</button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('lignesManager', (initial, catalogueInit) => ({
        lignes: initial.length ? initial : [{designation:'', quantite:1, prix_unitaire:0, remise_type:'', remise_valeur:0, tva_taux:null, pickerOpen: false, pickerSearch: ''}],
        tva: {{ $devis->tva_taux ?? 0 }},
        tvaApplicable: {{ $devis->tva_applicable ? 'true' : 'false' }},
        remiseGlobaleType: '{{ $devis->remise_globale_type }}',
        remiseGlobaleValeur: {{ $devis->remise_globale_valeur ?? 0 }},
        catalogue: catalogueInit,
        ajouter() { this.lignes.push({designation:'', quantite:1, prix_unitaire:0, remise_type:'', remise_valeur:0, tva_taux:null, pickerOpen: false, pickerSearch: ''}); },
        catalogueFiltered(ligne) {
            const q = (ligne.pickerSearch || '').toLowerCase();
            if (q.length < 1) return this.catalogue.slice(0, 10);
            return this.catalogue.filter(c => c.search.includes(q)).slice(0, 10);
        },
        choisirCatalogue(ligne, item) {
            ligne.designation = item.designation;
            ligne.prix_unitaire = item.prix;
            ligne.pickerOpen = false;
            ligne.pickerSearch = '';
        },
        get sousTotal() { return this.lignes.reduce((s, l) => s + ((l.prix_unitaire||0) * (l.quantite||1)), 0); },
        get remiseGlobale() {
            if (!this.remiseGlobaleType || !this.remiseGlobaleValeur) return 0;
            if (this.remiseGlobaleType === 'pourcentage') return Math.round(this.sousTotal * this.remiseGlobaleValeur / 100);
            return this.remiseGlobaleValeur;
        },
        get totalHT() { return Math.max(0, this.sousTotal - this.remiseGlobale); },
        get totalTTC() { return Math.round(this.totalHT * (1 + (this.tva || 0) / 100)); },
        format(v) { return new Intl.NumberFormat('fr-FR').format(v); }
    }));
});
</script>
@endpush
</x-dashboard-layout>
