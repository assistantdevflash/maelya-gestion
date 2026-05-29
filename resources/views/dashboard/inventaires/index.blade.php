<x-dashboard-layout>
    <x-slot name="title">Inventaires</x-slot>

    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-display font-bold text-gray-900">Inventaires physiques</h1>
                <p class="text-sm text-gray-500 mt-1">{{ $inventaires->total() }} inventaire(s)</p>
            </div>
            <a href="{{ route('dashboard.inventaires.create') }}" class="btn-primary">+ Nouvel inventaire</a>
        </div>

        @if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert-error">{{ session('error') }}</div>@endif

        <div class="card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Date</th>
                        <th class="px-4 py-3 text-left">Statut</th>
                        <th class="px-4 py-3 text-left">Réalisé par</th>
                        <th class="px-4 py-3 text-right">Écart total</th>
                        <th class="px-4 py-3 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($inventaires as $inv)
                        <tr>
                            <td class="px-4 py-3 font-semibold">{{ $inv->date_inventaire->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                @php $cls = ['en_cours'=>'badge-warning','valide'=>'badge-success','annule'=>'badge-danger'][$inv->statut] ?? 'badge-primary'; @endphp
                                <span class="{{ $cls }}">{{ ucfirst(str_replace('_',' ',$inv->statut)) }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs">{{ $inv->user->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right font-semibold {{ $inv->total_ecart_valeur < 0 ? 'text-red-600' : ($inv->total_ecart_valeur > 0 ? 'text-emerald-600' : '') }}">
                                {{ number_format($inv->total_ecart_valeur, 0, ',', ' ') }} F
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('dashboard.inventaires.show', $inv) }}" class="text-primary-600 text-xs">Voir</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-gray-400">Aucun inventaire</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $inventaires->links() }}
    </div>
</x-dashboard-layout>
