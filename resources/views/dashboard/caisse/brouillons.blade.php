<x-dashboard-layout>
    <div class="space-y-4" x-data="{ confirmId: null }">

        {{-- Modal de confirmation suppression --}}
        <div x-show="confirmId !== null" x-cloak
             class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
             @keydown.escape.window="confirmId = null">
            <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-sm w-full p-6 space-y-4 shadow-xl" @click.stop>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-slate-100">Supprimer le brouillon</h3>
                        <p class="text-sm text-gray-500 dark:text-slate-400 mt-0.5">Cette action est irréversible.</p>
                    </div>
                </div>
                <div class="flex gap-2 justify-end">
                    <button @click="confirmId = null" class="btn-outline text-sm">Annuler</button>
                    <button @click="$refs['form-' + confirmId].submit()" class="px-4 py-2 text-sm font-semibold rounded-xl text-white bg-red-600 hover:bg-red-700 transition">
                        Supprimer
                    </button>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <div>
                <h1 class="page-title">Brouillons de caisse</h1>
                <p class="page-subtitle">Paniers mis en attente — reprenez-les à tout moment.</p>
            </div>
            <a href="{{ route('dashboard.caisse') }}" class="btn-outline text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Retour caisse
            </a>
        </div>

        @if(session('success'))
            <div class="bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 rounded-xl px-4 py-3 text-sm font-medium">
                {{ session('success') }}
            </div>
        @endif

        @if($brouillons->isEmpty())
            <div class="card p-12 text-center">
                <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14-4H5m14 8H5m14 4H5"/></svg>
                <p class="text-gray-500 dark:text-slate-400">Aucun panier en attente.</p>
            </div>
        @else
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($brouillons as $b)
                    <div class="card p-4 flex flex-col gap-3">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-900 dark:text-slate-100 truncate">
                                    {{ $b->libelle ?: 'Panier '. \Illuminate\Support\Str::limit($b->id, 6, '') }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-slate-400">
                                    {{ $b->created_at->format('d/m/Y H:i') }} ·
                                    {{ optional($b->user)->prenom }} {{ optional($b->user)->nom_famille }}
                                </p>
                                @if($b->client)
                                    <p class="text-xs text-purple-600 dark:text-purple-400 mt-0.5">
                                        Client : {{ $b->client->nom_affichage }}
                                    </p>
                                @endif
                            </div>
                            <span class="text-sm font-bold text-gray-900 dark:text-slate-100 whitespace-nowrap">
                                {{ number_format($b->total_indicatif, 0, ',', ' ') }} F
                            </span>
                        </div>

                        <div class="text-xs text-gray-600 dark:text-slate-300 space-y-0.5 border-t border-gray-100 dark:border-slate-700 pt-2">
                            @foreach((array) $b->panier as $it)
                                <div class="flex justify-between gap-2">
                                    <span class="truncate">{{ $it['quantite'] ?? 1 }}× {{ $it['nom'] ?? '?' }}</span>
                                    <span class="text-gray-400 dark:text-slate-500">{{ number_format(($it['prix'] ?? 0) * ($it['quantite'] ?? 1), 0, ',', ' ') }} F</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex gap-2 mt-auto">
                            <a href="{{ route('dashboard.caisse', ['brouillon' => $b->id]) }}"
                               class="flex-1 text-center px-3 py-2 text-xs font-semibold rounded-lg text-white shadow-sm transition"
                               style="background: linear-gradient(135deg, #9333ea 0%, #ec4899 100%);">
                                Reprendre
                            </a>
                            <form x-ref="form-{{ $b->id }}"
                                  method="POST" action="{{ route('dashboard.caisse.brouillons.destroy', $b->id) }}">
                                @csrf
                                @method('DELETE')
                                <button type="button"
                                        @click="confirmId = '{{ $b->id }}'"
                                        class="px-3 py-2 text-xs font-semibold rounded-lg text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 transition">
                                    Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-dashboard-layout>
