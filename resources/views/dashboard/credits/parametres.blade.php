<x-dashboard-layout>
    <x-slot name="title">Paramètres crédits</x-slot>

    <div class="space-y-5 max-w-3xl">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard.credits.index') }}" class="btn-icon flex-shrink-0" title="Retour">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-display font-bold text-gray-900 dark:text-white">Paramètres crédits</h1>
                <p class="text-sm text-gray-500 mt-0.5">Plafond d'encours et relances automatiques.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/40 text-sm text-emerald-800 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('dashboard.credits.parametres.store') }}" class="card">
            @csrf
            <div class="card-body space-y-5">

                <div>
                    <label class="form-label">Plafond d'encours par défaut (FCFA)</label>
                    <input type="number" name="credit_plafond_defaut" min="0" step="1000"
                           value="{{ old('credit_plafond_defaut', $institut->credit_plafond_defaut) }}"
                           placeholder="Laisser vide pour aucun plafond"
                           class="form-input">
                    @error('credit_plafond_defaut')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    <p class="text-xs text-gray-500 mt-1">
                        Appliqué à tous les clients qui n'ont pas de plafond spécifique.
                        Un dépassement bloquera la création d'un crédit, sauf confirmation explicite.
                    </p>
                </div>

                <div class="flex items-start gap-3 p-3 rounded-xl bg-gray-50 dark:bg-slate-800">
                    <input type="checkbox" name="credit_relance_active" id="relance_active" value="1"
                           {{ $institut->credit_relance_active ? 'checked' : '' }}
                           class="mt-0.5 rounded border-gray-300 text-primary-600">
                    <label for="relance_active" class="text-sm">
                        <span class="font-semibold text-gray-900 dark:text-white">Relances automatiques</span>
                        <span class="block text-xs text-gray-500 mt-0.5">
                            Envoi à J-3, J+1, J+7, J+15 et J+30 : e-mail au client (si adresse connue)
                            + notification à l'établissement avec le lien WhatsApp pré-rempli.
                        </span>
                    </label>
                </div>

                <div>
                    <label class="form-label">Modèle de message de relance</label>
                    <textarea name="credit_message_relance" rows="7" class="form-input font-mono text-xs"
                              placeholder="Laisser vide pour utiliser le message par défaut">{{ old('credit_message_relance', $institut->credit_message_relance) }}</textarea>
                    @error('credit_message_relance')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    <p class="text-xs text-gray-500 mt-1">
                        Variables disponibles :
                        <code class="px-1 bg-gray-100 dark:bg-slate-700 rounded">{client}</code>
                        <code class="px-1 bg-gray-100 dark:bg-slate-700 rounded">{prenom}</code>
                        <code class="px-1 bg-gray-100 dark:bg-slate-700 rounded">{montant}</code>
                        <code class="px-1 bg-gray-100 dark:bg-slate-700 rounded">{reste}</code>
                        <code class="px-1 bg-gray-100 dark:bg-slate-700 rounded">{date}</code>
                        <code class="px-1 bg-gray-100 dark:bg-slate-700 rounded">{lien}</code>
                        <code class="px-1 bg-gray-100 dark:bg-slate-700 rounded">{institut}</code>
                    </p>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="btn-primary">Enregistrer</button>
                </div>
            </div>
        </form>
    </div>
</x-dashboard-layout>
