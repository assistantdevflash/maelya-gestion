<x-dashboard-layout>
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('dashboard.devis.show', ['devis' => $devis->id]) }}" class="p-2 text-gray-400 hover:text-gray-600 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 transition">←</a>
        <div><h1 class="text-2xl font-bold text-gray-900 dark:text-white">Modifier {{ $devis->numero }}</h1></div>
    </div>

    <form method="POST" action="{{ route('dashboard.devis.update', ['devis' => $devis->id]) }}" x-data="lignesManager(@js($devis->items->map(fn($i) => ['designation'=>$i->designation,'quantite'=>$i->quantite,'prix_unitaire'=>$i->prix_unitaire,'remise_type'=>$i->remise_type,'remise_valeur'=>$i->remise_valeur,'tva_taux'=>$i->tva_taux])->toArray()))">
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
                            <select name="remise_globale_type" class="form-input text-sm w-32">
                                <option value="" {{ $devis->remise_globale_type === null ? 'selected' : '' }}>Aucune</option>
                                <option value="pourcentage" {{ $devis->remise_globale_type === 'pourcentage' ? 'selected' : '' }}>%</option>
                                <option value="montant_fixe" {{ $devis->remise_globale_type === 'montant_fixe' ? 'selected' : '' }}>Fixe</option>
                            </select>
                            <input type="number" name="remise_globale_valeur" min="0" value="{{ $devis->remise_globale_valeur }}" placeholder="0" class="form-input text-sm w-24">
                        </div>
                        <div class="flex justify-between text-sm"><span class="text-gray-500">Total HT</span><span class="font-bold" x-text="format(totalHT) + ' F'"></span></div>
                        <div class="flex items-center gap-3">
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="tva_applicable" value="1" @change="tva = $el.checked ? {{ $devis->tva_taux }} : 0" {{ $devis->tva_applicable ? 'checked' : '' }}> TVA
                            </label>
                            <input type="number" name="tva_taux" x-model="tva" min="0" max="100" step="0.01" class="form-input text-sm w-20">
                            <span class="text-sm text-gray-500">%</span>
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
    Alpine.data('lignesManager', (initial) => ({
        lignes: initial.length ? initial : [{designation:'', quantite:1, prix_unitaire:0, remise_type:'', remise_valeur:0, tva_taux:null}],
        tva: {{ $devis->tva_taux ?? 0 }},
        ajouter() { this.lignes.push({designation:'', quantite:1, prix_unitaire:0, remise_type:'', remise_valeur:0, tva_taux:null}); },
        get sousTotal() { return this.lignes.reduce((s, l) => s + ((l.prix_unitaire||0) * (l.quantite||1)), 0); },
        get totalHT() { return this.sousTotal; },
        get totalTTC() { return Math.round(this.totalHT * (1 + (this.tva || 0) / 100)); },
        format(v) { return new Intl.NumberFormat('fr-FR').format(v); }
    }));
});
</script>
@endpush
</x-dashboard-layout>
