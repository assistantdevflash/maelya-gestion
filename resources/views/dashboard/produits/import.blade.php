<x-dashboard-layout>
    <x-slot name="title">Importer des produits</x-slot>

    <div class="space-y-5 max-w-2xl">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-display font-bold text-gray-900 dark:text-slate-100 tracking-tight">Importer des produits</h1>
                <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">
                    En une fois, depuis un fichier CSV.
                </p>
            </div>
            <a href="{{ route('dashboard.produits.index') }}" class="btn-outline">← Retour</a>
        </div>

        @if(session('error'))<div class="alert-error">{{ session('error') }}</div>@endif

        <div class="card p-5 space-y-4">
            <div class="flex items-start gap-3 p-3 rounded-xl bg-primary-50/60 dark:bg-primary-950/20">
                <svg class="w-5 h-5 text-primary-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="text-xs text-gray-600 dark:text-slate-300">
                    <p class="font-semibold text-gray-800 dark:text-slate-200 mb-1">Comment ça marche</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        <li>Colonnes : <code class="font-mono">nom</code>, <code class="font-mono">reference</code>, <code class="font-mono">prix_achat</code>, <code class="font-mono">prix_vente</code>, <code class="font-mono">stock</code>, <code class="font-mono">seuil_alerte</code>, <code class="font-mono">unite</code>.</li>
                        <li>Séparateur <code class="font-mono">;</code> ou <code class="font-mono">,</code>, détecté automatiquement.</li>
                        <li>Une ligne dont la <strong>référence</strong> existe déjà met le produit à jour.</li>
                        <li>Le stock n'est appliqué qu'à la création : un import n'écrase pas un stock géré par les mouvements.</li>
                    </ul>
                </div>
            </div>

            <a href="{{ route('dashboard.produits.import.modele') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-primary-600 dark:text-primary-400 hover:underline">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Télécharger le modèle CSV
            </a>

            <form method="POST" action="{{ route('dashboard.produits.import.store') }}" enctype="multipart/form-data" class="space-y-4 pt-3 border-t border-gray-100 dark:border-slate-700">
                @csrf
                <div>
                    <label for="fichier" class="form-label">Fichier CSV *</label>
                    <input type="file" name="fichier" id="fichier" accept=".csv,text/csv" required
                           class="form-input">
                    <p class="text-xs text-gray-400 mt-1">4 Mo maximum.</p>
                    @error('fichier')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('dashboard.produits.index') }}" class="btn-outline">Annuler</a>
                    <button type="submit" class="btn-primary">Importer</button>
                </div>
            </form>
        </div>
    </div>
</x-dashboard-layout>
