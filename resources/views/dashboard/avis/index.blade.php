<x-dashboard-layout>
<x-slot name="title">Avis clients</x-slot>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-slate-100">Avis clients</h1>
        <form method="GET" class="flex gap-2">
            <select name="statut" class="form-input" onchange="this.form.submit()">
                <option value="">Tous</option>
                <option value="en_attente" @selected($statut==='en_attente')>En attente</option>
                <option value="approuve" @selected($statut==='approuve')>Approuvés</option>
                <option value="rejete" @selected($statut==='rejete')>Rejetés</option>
            </select>
        </form>
    </div>

    @if($avis->count())
        <div class="space-y-4">
            @foreach($avis as $a)
                <div class="card p-4">
                    <div class="flex justify-between items-start gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-yellow-500">{{ str_repeat('★', (int)$a->note) }}{{ str_repeat('☆', 5 - (int)$a->note) }}</span>
                                <span class="text-sm text-gray-500">— {{ $a->client_nom_snap ?: 'Anonyme' }}</span>
                                <span class="text-xs text-gray-400">· {{ $a->repondu_le?->format('d/m/Y H:i') }}</span>
                            </div>
                            @if($a->commentaire)
                                <p class="text-gray-700 dark:text-slate-200">{{ $a->commentaire }}</p>
                            @endif
                            <span class="inline-block mt-2 px-2 py-0.5 text-xs rounded
                                {{ $a->statut==='approuve' ? 'bg-green-100 text-green-800' :
                                   ($a->statut==='rejete' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                {{ ucfirst(str_replace('_', ' ', $a->statut)) }}
                            </span>
                        </div>
                        <div class="flex flex-col gap-2 shrink-0">
                            @if($a->statut !== 'approuve')
                                <form method="POST" action="{{ route('dashboard.avis.approuver', $a) }}">
                                    @csrf
                                    <button class="btn-primary text-xs px-3 py-1">Approuver</button>
                                </form>
                            @endif
                            @if($a->statut !== 'rejete')
                                <form method="POST" action="{{ route('dashboard.avis.rejeter', $a) }}">
                                    @csrf
                                    <button class="btn-outline text-xs px-3 py-1">Rejeter</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div>{{ $avis->links() }}</div>
    @else
        <div class="card p-8 text-center text-gray-500">Aucun avis reçu pour le moment.</div>
    @endif
</div>
</x-dashboard-layout>
