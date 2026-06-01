<x-dashboard-layout>
<x-slot name="title">Trésorerie prévisionnelle</x-slot>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-slate-100">Trésorerie prévisionnelle</h1>
        <form method="GET" class="flex gap-2 items-center">
            <label class="text-sm text-gray-600 dark:text-slate-300">Horizon</label>
            <select name="jours" class="form-input" onchange="this.form.submit()">
                @foreach([7, 14, 30, 60, 90] as $j)
                    <option value="{{ $j }}" @selected($jours === $j)>{{ $j }} jours</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="card p-4">
            <p class="text-xs text-gray-500 dark:text-slate-400 uppercase">RDV à venir</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1">{{ number_format($revenusPrevu, 0, ',', ' ') }} F</p>
            <p class="text-xs text-gray-400 mt-1">{{ $rdvFuturs->count() }} RDV planifiés</p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-gray-500 dark:text-slate-400 uppercase">Ventes prévues</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($projectionVentes, 0, ',', ' ') }} F</p>
            <p class="text-xs text-gray-400 mt-1">Projection 30j</p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-gray-500 dark:text-slate-400 uppercase">Dépenses prévues</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ number_format($projectionDepenses, 0, ',', ' ') }} F</p>
            <p class="text-xs text-gray-400 mt-1">Projection 90j</p>
        </div>
        <div class="card p-4 {{ $solde >= 0 ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
            <p class="text-xs text-gray-500 dark:text-slate-400 uppercase">Solde net projeté</p>
            <p class="text-2xl font-bold mt-1 {{ $solde >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                {{ ($solde >= 0 ? '+' : '') . number_format($solde, 0, ',', ' ') }} F
            </p>
        </div>
    </div>

    <div class="card p-4">
        <h2 class="font-semibold text-gray-800 dark:text-slate-100 mb-4">Évolution jour par jour</h2>
        <canvas id="chartTreso" height="80"></canvas>
    </div>

    <div class="card p-4">
        <h2 class="font-semibold text-gray-800 dark:text-slate-100 mb-3">RDV à venir détaillés</h2>
        @if($rdvFuturs->count())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-slate-800">
                        <tr>
                            <th class="px-3 py-2 text-left">Date</th>
                            <th class="px-3 py-2 text-left">Client</th>
                            <th class="px-3 py-2 text-left">Prestations</th>
                            <th class="px-3 py-2 text-right">Montant</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                        @foreach($rdvFuturs->sortBy('debut_le')->take(20) as $r)
                            <tr>
                                <td class="px-3 py-2">{{ $r->debut_le->format('d/m H:i') }}</td>
                                <td class="px-3 py-2">{{ $r->client_nom }}</td>
                                <td class="px-3 py-2">{{ $r->prestations->pluck('nom')->implode(', ') }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format($r->prestations->sum('prix'), 0, ',', ' ') }} F</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500 text-sm">Aucun RDV planifié.</p>
        @endif
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
new Chart(document.getElementById('chartTreso'), {
    type: 'line',
    data: {
        labels: @json($jourLabel),
        datasets: [
            { label: 'Entrées (F)', data: @json($jourEntrees), borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', tension: 0.3 },
            { label: 'Sorties (F)', data: @json($jourSorties), borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.1)', tension: 0.3 },
        ]
    },
    options: { responsive: true, plugins: { legend: { position: 'top' } } }
});
</script>
</x-dashboard-layout>
