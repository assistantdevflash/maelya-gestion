<x-dashboard-layout>
<div class="space-y-5" x-data="{
    showConfig: false,
    showAjuster: false,
    ajusterClient: null,
    ajusterNom: '',
    showRecompenser: false,
    recompenserClient: null,
    recompenserNom: '',
    recompenserPoints: 0,
}">

    {{-- En-tête --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-display font-bold text-gray-900 dark:text-white tracking-tight">Programme de fidélité</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Récompensez vos meilleurs clients avec des codes de réduction</p>
        </div>
        <button @click="showConfig = true" class="btn-primary flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Configurer
        </button>
    </div>

    {{-- Statut programme --}}
    @if(!$programme || !$programme->actif)
    <div class="card p-6 text-center">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center">
            <span class="text-3xl">⭐</span>
        </div>
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Programme de fidélité inactif</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4 max-w-md mx-auto">
            Activez le programme pour récompenser automatiquement vos clients à chaque achat et les fidéliser avec des codes de réduction.
        </p>
        <button @click="showConfig = true" class="btn-primary">
            Activer le programme
        </button>
    </div>
    @else

    {{-- KPIs --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="card p-4 sm:p-5 flex items-center gap-3 sm:gap-4">
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center flex-shrink-0">
                <span class="text-base sm:text-lg">⭐</span>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] sm:text-xs text-gray-500 dark:text-gray-400 font-medium truncate">Points en circulation</p>
                <p class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">{{ number_format($totalPoints, 0, ',', ' ') }}</p>
            </div>
        </div>
        <div class="card p-4 sm:p-5 flex items-center gap-3 sm:gap-4">
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center flex-shrink-0">
                <span class="text-base sm:text-lg">👥</span>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] sm:text-xs text-gray-500 dark:text-gray-400 font-medium truncate">Clients fidèles</p>
                <p class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">{{ $clientsAvecPoints }}</p>
            </div>
        </div>
        <div class="card p-4 sm:p-5 flex items-center gap-3 sm:gap-4">
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center flex-shrink-0">
                <span class="text-base sm:text-lg">🎁</span>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] sm:text-xs text-gray-500 dark:text-gray-400 font-medium truncate">Récompenses données</p>
                <p class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">{{ $recompensesDistribuees }}</p>
            </div>
        </div>
        <div class="card p-4 sm:p-5 flex items-center gap-3 sm:gap-4">
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center flex-shrink-0">
                <span class="text-base sm:text-lg">🏆</span>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] sm:text-xs text-gray-500 dark:text-gray-400 font-medium truncate">Seuil récompense</p>
                <p class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">{{ $programme->seuil_recompense }} pts</p>
                <p class="text-[10px] text-gray-400">→ {{ $programme->valeur_recompense }}{{ $programme->type_recompense === 'pourcentage' ? '%' : ' FCFA' }}</p>
            </div>
        </div>
    </div>

    {{-- Résumé du programme --}}
    <div class="card p-3 sm:p-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/10 dark:to-pink-900/10 border-purple-100 dark:border-purple-800/30">
        <div class="flex items-start sm:items-center gap-2 sm:gap-3 text-xs sm:text-sm">
            <span class="text-base sm:text-lg flex-shrink-0">📋</span>
            <span class="text-gray-700 dark:text-gray-300">
                <strong>Règle :</strong> {{ $programme->points_par_tranche }} point{{ $programme->points_par_tranche > 1 ? 's' : '' }} pour chaque {{ number_format($programme->tranche_fcfa, 0, ',', ' ') }} FCFA dépensé.
                À {{ $programme->seuil_recompense }} points → code de {{ $programme->valeur_recompense }}{{ $programme->type_recompense === 'pourcentage' ? '%' : ' FCFA' }} de réduction.
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        {{-- Classement clients --}}
        <div class="lg:col-span-2">
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-700 flex items-center justify-between">
                    <h2 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span>🏅</span> Classement des clients
                    </h2>
                    <span class="text-xs text-gray-400">{{ $clients->total() }} client{{ $clients->total() > 1 ? 's' : '' }}</span>
                </div>

                @if($clients->isEmpty())
                <div class="text-center py-12">
                    <p class="text-gray-400 text-sm">Aucun client n'a encore gagné de points.</p>
                    <p class="text-xs text-gray-400 mt-1">Les points s'accumuleront automatiquement à chaque vente.</p>
                </div>
                @else
                <div class="overflow-x-auto">
                    <table class="table-auto w-full">
                        <thead>
                            <tr>
                                <th class="text-left">#</th>
                                <th class="text-left">Client</th>
                                <th class="text-right">Points</th>
                                <th class="text-right">Progression</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($clients as $index => $client)
                            @php
                                $rang = $clients->firstItem() + $index;
                                $medals = ['🥇', '🥈', '🥉'];
                                $pct = $programme->seuil_recompense > 0 ? min(100, round($client->points_fidelite / $programme->seuil_recompense * 100)) : 0;
                                $eligible = $client->points_fidelite >= $programme->seuil_recompense;
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50 {{ $eligible ? 'bg-emerald-50/50 dark:bg-emerald-900/10' : '' }}">
                                <td class="text-center">
                                    @if($rang <= 3)
                                    <span class="text-lg">{{ $medals[$rang - 1] }}</span>
                                    @else
                                    <span class="text-sm text-gray-400 font-medium">{{ $rang }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-start gap-3">
                                        <div class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0 mt-0.5">
                                            <span class="text-xs font-bold text-primary-700 dark:text-primary-400">{{ strtoupper(substr($client->prenom, 0, 1) . substr($client->nom, 0, 1)) }}</span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $client->nom_complet }}</p>
                                            <p class="text-xs text-gray-400">{{ $client->telephone }}</p>
                                            @if($client->codesReductionFidelite && $client->codesReductionFidelite->count())
                                            <div class="flex flex-wrap gap-1.5 mt-1.5">
                                                @foreach($client->codesReductionFidelite as $codeReduc)
                                                <div class="inline-flex items-center gap-1.5 px-2 py-1 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700/40 rounded-md">
                                                    <span class="text-[10px]">🎟️</span>
                                                    <span class="text-[11px] font-mono font-bold text-amber-700 dark:text-amber-400">{{ $codeReduc->code }}</span>
                                                    <span class="text-[10px] text-gray-400">{{ $codeReduc->valeur }}{{ $codeReduc->type === 'pourcentage' ? '%' : ' FCFA' }} • exp. {{ $codeReduc->date_fin?->format('d/m/Y') ?? '∞' }}</span>
                                                    <button onclick="window.open('{{ route('dashboard.fidelite.imprimer-code', $codeReduc) }}', '_blank')" class="p-0.5 rounded hover:bg-amber-100 dark:hover:bg-amber-900/30 text-amber-500 dark:text-amber-400 transition-colors" title="Imprimer">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                                    </button>
                                                </div>
                                                @endforeach
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="text-right">
                                    <span class="text-sm font-bold {{ $eligible ? 'text-emerald-600' : 'text-gray-900 dark:text-white' }}">{{ $client->points_fidelite }}</span>
                                    <span class="text-xs text-gray-400">pts</span>
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="w-20 h-1.5 bg-gray-100 dark:bg-slate-700 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full {{ $eligible ? 'bg-emerald-500' : 'bg-purple-500' }}" style="width: {{ $pct }}%;"></div>
                                        </div>
                                        <span class="text-xs text-gray-400 w-8">{{ $pct }}%</span>
                                    </div>
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        @if($eligible)
                                        <button
                                            @click="recompenserClient = '{{ $client->id }}'; recompenserNom = '{{ addslashes($client->nom_complet) }}'; recompenserPoints = {{ $client->points_fidelite }}; showRecompenser = true"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-lg bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 transition-colors" title="Récompenser">
                                            🎁 Récompenser
                                        </button>
                                        @endif
                                        <button
                                            @click="ajusterClient = '{{ $client->id }}'; ajusterNom = '{{ addslashes($client->nom_complet) }}'; showAjuster = true"
                                            class="p-1.5 rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-700 hover:text-gray-600 transition-colors"
                                            title="Ajuster les points">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($clients->hasPages())
                <div class="px-5 py-3 border-t border-gray-100 dark:border-slate-700">
                    {{ $clients->links() }}
                </div>
                @endif
                @endif
            </div>
        </div>

        {{-- Derniers mouvements --}}
        <div class="lg:col-span-1">
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-700">
                    <h2 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span>📊</span> Derniers mouvements
                    </h2>
                </div>

                @if($derniersMouvements->isEmpty())
                <div class="text-center py-8">
                    <p class="text-gray-400 text-sm">Aucun mouvement</p>
                </div>
                @else
                <div class="divide-y divide-gray-100 dark:divide-slate-700">
                    @foreach($derniersMouvements as $mv)
                    <div class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0
                                {{ $mv->points > 0 ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-red-100 dark:bg-red-900/30' }}">
                                @if($mv->type === 'gain')
                                    <span class="text-xs">⬆️</span>
                                @elseif($mv->type === 'recompense')
                                    <span class="text-xs">🎁</span>
                                @else
                                    <span class="text-xs">🔧</span>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-gray-900 dark:text-white truncate">{{ $mv->client->nom_complet ?? '—' }}</p>
                                <p class="text-[10px] text-gray-400 truncate">{{ $mv->description }}</p>
                            </div>
                            <span class="text-xs font-bold flex-shrink-0 {{ $mv->points > 0 ? 'text-emerald-600' : 'text-red-500' }}">
                                {{ $mv->points > 0 ? '+' : '' }}{{ $mv->points }}
                            </span>
                        </div>
                        <p class="text-[10px] text-gray-300 dark:text-gray-600 mt-1 pl-10">{{ $mv->created_at->diffForHumans() }}</p>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- ═══ Modal Configuration ═══ --}}
    <div x-show="showConfig" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showConfig = false"></div>
        <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-lg p-6" @click.stop>
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Configurer le programme</h2>
                <button @click="showConfig = false" class="p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('dashboard.fidelite.configurer') }}" class="space-y-4">
                @csrf

                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-slate-700/50 rounded-xl" x-data="{ actif: {{ ($programme && $programme->actif) ? 'true' : (!$programme ? 'true' : 'false') }} }">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Programme actif</p>
                        <p class="text-xs" :class="actif ? 'text-emerald-500' : 'text-red-400'" x-text="actif ? '✓ Les points s\'accumuleront à chaque vente' : '✗ Le programme sera mis en pause'"></p>
                    </div>
                    <input type="hidden" name="actif" :value="actif ? 1 : 0">
                    <button type="button" @click="actif = !actif" role="switch" :aria-checked="actif"
                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                        :style="actif ? 'background-color: #10b981' : 'background-color: #d1d5db'">
                        <span class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition-transform duration-200 ease-in-out"
                              :class="actif ? 'translate-x-5' : 'translate-x-0'"></span>
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">Tranche (FCFA)</label>
                        <input type="number" name="tranche_fcfa" value="{{ $programme->tranche_fcfa ?? 1000 }}" min="100" class="form-input w-full" placeholder="1000">
                        <p class="text-[10px] text-gray-400 mt-1">Montant pour gagner des points</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">Points / tranche</label>
                        <input type="number" name="points_par_tranche" value="{{ $programme->points_par_tranche ?? 1 }}" min="1" max="100" class="form-input w-full" placeholder="1">
                        <p class="text-[10px] text-gray-400 mt-1">Points gagnés par tranche</p>
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">Seuil de récompense (points)</label>
                    <input type="number" name="seuil_recompense" value="{{ $programme->seuil_recompense ?? 100 }}" min="1" class="form-input w-full" placeholder="100">
                    <p class="text-[10px] text-gray-400 mt-1">Nombre de points nécessaires pour convertir en code</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">Type de récompense</label>
                        <select name="type_recompense" class="form-input w-full">
                            <option value="pourcentage" {{ ($programme->type_recompense ?? 'pourcentage') === 'pourcentage' ? 'selected' : '' }}>Pourcentage (%)</option>
                            <option value="montant_fixe" {{ ($programme->type_recompense ?? '') === 'montant_fixe' ? 'selected' : '' }}>Montant fixe (FCFA)</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">Valeur récompense</label>
                        <input type="number" name="valeur_recompense" value="{{ $programme->valeur_recompense ?? 10 }}" min="1" class="form-input w-full" placeholder="10">
                        <p class="text-[10px] text-gray-400 mt-1">% ou FCFA de réduction</p>
                    </div>
                </div>

                @if($programme)
                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl text-xs text-blue-700 dark:text-blue-400">
                    <strong>Exemple :</strong> Un client qui dépense {{ number_format(($programme->tranche_fcfa ?? 1000) * ($programme->seuil_recompense ?? 100) / ($programme->points_par_tranche ?? 1), 0, ',', ' ') }} FCFA atteint le seuil et reçoit un code de {{ $programme->valeur_recompense ?? 10 }}{{ ($programme->type_recompense ?? 'pourcentage') === 'pourcentage' ? '%' : ' FCFA' }}.
                </div>
                @endif

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showConfig = false" class="btn-secondary">Annuler</button>
                    <button type="submit" class="btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══ Modal Récompenser ═══ --}}
    <div x-show="showRecompenser" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showRecompenser = false"></div>
        <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-sm p-6" @click.stop>
            <div class="flex items-center justify-center w-14 h-14 mx-auto mb-4 rounded-full bg-emerald-50 dark:bg-emerald-900/20">
                <span class="text-3xl">🎁</span>
            </div>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white text-center mb-1">Récompenser le client</h2>
            <p class="text-sm text-gray-500 text-center mb-2" x-text="recompenserNom"></p>
            <div class="bg-gray-50 dark:bg-slate-700/50 rounded-xl p-3 mb-4 text-center space-y-1">
                <p class="text-xs text-gray-500">Points actuels : <span class="font-bold text-gray-900 dark:text-white" x-text="recompenserPoints"></span></p>
                <p class="text-xs text-gray-500">Points à convertir : <span class="font-bold text-emerald-600">{{ $programme->seuil_recompense ?? 0 }}</span></p>
                <p class="text-xs text-gray-500">Récompense : <span class="font-bold text-purple-600">{{ ($programme->valeur_recompense ?? 10) . (($programme->type_recompense ?? 'pourcentage') === 'pourcentage' ? '%' : ' FCFA') }}</span> de réduction</p>
            </div>
            <p class="text-xs text-gray-400 text-center mb-5">Un code de réduction personnel sera généré et valable 30 jours.</p>
            <div class="flex gap-3">
                <button @click="showRecompenser = false" class="btn-secondary flex-1">Annuler</button>
                <form method="POST" :action="'/dashboard/fidelite/' + recompenserClient + '/recompenser'" class="flex-1">
                    @csrf
                    <button type="submit" class="btn-primary w-full">Confirmer</button>
                </form>
            </div>
        </div>
    </div>

    {{-- ═══ Modal Ajuster Points ═══ --}}
    <div x-show="showAjuster" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showAjuster = false"></div>
        <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-sm p-6" @click.stop>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Ajuster les points</h2>
            <p class="text-sm text-gray-500 mb-4" x-text="ajusterNom"></p>

            <form method="POST" :action="'/dashboard/fidelite/' + ajusterClient + '/ajuster'" class="space-y-4">
                @csrf
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">Points (+/-)</label>
                    <input type="number" name="points" class="form-input w-full" placeholder="Ex: 10 ou -5" required>
                    <p class="text-[10px] text-gray-400 mt-1">Positif = ajouter, négatif = retirer</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">Raison</label>
                    <input type="text" name="description" class="form-input w-full" placeholder="Ex: Bonus anniversaire" required maxlength="255">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showAjuster = false" class="btn-secondary">Annuler</button>
                    <button type="submit" class="btn-primary">Appliquer</button>
                </div>
            </form>
        </div>
    </div>

</div>
</x-dashboard-layout>
