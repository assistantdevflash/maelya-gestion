<x-dashboard-layout>
    <x-slot name="title">Comparatif instituts</x-slot>

    <div class="space-y-4">
        <div>
            <h1 class="text-2xl font-display font-bold text-gray-900">Comparatif multi-instituts</h1>
            <p class="text-sm text-gray-500 mt-1">{{ now()->translatedFormat('F Y') }} — {{ count($stats) }} institut(s) actif(s)</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="card p-5">
                <div class="text-xs text-gray-500">CA total du mois</div>
                <div class="text-3xl font-bold text-primary-600 mt-1">{{ number_format($totalCa, 0, ',', ' ') }} F</div>
            </div>
            <div class="card p-5">
                <div class="text-xs text-gray-500">Ventes totales du mois</div>
                <div class="text-3xl font-bold text-emerald-600 mt-1">{{ $totalVentes }}</div>
            </div>
        </div>

        <div class="card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Institut</th>
                        <th class="px-4 py-3 text-right">CA mois</th>
                        <th class="px-4 py-3 text-right">% du total</th>
                        <th class="px-4 py-3 text-right">Ventes</th>
                        <th class="px-4 py-3 text-right">Panier moyen</th>
                        <th class="px-4 py-3 text-right">Clients (total / nouv.)</th>
                        <th class="px-4 py-3 text-left">Top prestation</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($stats as $idx => $s)
                        @php $pct = $totalCa > 0 ? round($s['ca_mois'] * 100 / $totalCa, 1) : 0; @endphp
                        <tr class="{{ $idx === 0 && $s['ca_mois'] > 0 ? 'bg-emerald-50' : '' }}">
                            <td class="px-4 py-3 font-semibold">
                                {{ $s['institut']->nom }}
                                @if($idx === 0 && $s['ca_mois'] > 0)<span class="ml-2 badge-success text-[10px]">★ #1</span>@endif
                            </td>
                            <td class="px-4 py-3 text-right font-bold">{{ number_format($s['ca_mois'], 0, ',', ' ') }} F</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <div class="w-16 h-1.5 bg-gray-200 rounded">
                                        <div class="h-1.5 bg-primary-500 rounded" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-500">{{ $pct }}%</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">{{ $s['nb_ventes'] }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($s['panier_moyen'], 0, ',', ' ') }} F</td>
                            <td class="px-4 py-3 text-right text-xs">{{ $s['nb_clients'] }} / <span class="text-emerald-600 font-semibold">+{{ $s['nb_clients_nouv'] }}</span></td>
                            <td class="px-4 py-3 text-xs text-gray-600">{{ $s['top_presta'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-dashboard-layout>
