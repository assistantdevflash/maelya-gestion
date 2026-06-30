<x-dashboard-layout>
    <x-slot name="title">Bons de commande</x-slot>

    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl sm:text-2xl font-display font-bold text-gray-900">Bons de commande</h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-0.5 sm:mt-1">{{ $bons->total() }} bon(s) de commande</p>
            </div>
            <a href="{{ route('dashboard.bons-commande.create') }}" class="btn-primary self-start sm:self-auto">+ Nouveau bon</a>
        </div>

        @if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif

        <div class="card overflow-hidden">
            <div class="overflow-x-auto -webkit-overflow-scrolling:touch">
            <table class="w-full text-sm min-w-[550px]">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-3 sm:px-4 py-3 text-left">Numéro</th>
                        <th class="px-3 sm:px-4 py-3 text-left">Fournisseur</th>
                        <th class="px-3 sm:px-4 py-3 text-left">Date</th>
                        <th class="px-3 sm:px-4 py-3 text-left">Statut</th>
                        <th class="px-3 sm:px-4 py-3 text-right">Total HT</th>
                        <th class="px-3 sm:px-4 py-3 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($bons as $bc)
                        <tr>
                            <td class="px-3 sm:px-4 py-3 font-mono text-xs font-semibold whitespace-nowrap">{{ $bc->numero }}</td>
                            <td class="px-3 sm:px-4 py-3 text-xs sm:text-sm whitespace-nowrap">{{ $bc->fournisseur->nom ?? '—' }}</td>
                            <td class="px-3 sm:px-4 py-3 text-xs whitespace-nowrap">{{ $bc->date_commande->format('d/m/Y') }}</td>
                            <td class="px-3 sm:px-4 py-3 whitespace-nowrap">
                                @php
                                    $cls = match($bc->statut) {
                                        'brouillon' => 'badge-warning',
                                        'envoye' => 'badge-info',
                                        'recu_partiel' => 'badge-warning',
                                        'recu' => 'badge-success',
                                        'annule' => 'badge-danger',
                                        default => 'badge-primary',
                                    };
                                @endphp
                                <span class="{{ $cls }} text-[10px] sm:text-xs">{{ $bc->statut_label }}</span>
                            </td>
                            <td class="px-3 sm:px-4 py-3 text-right font-semibold text-xs sm:text-sm whitespace-nowrap">{{ number_format($bc->total_ht, 0, ',', ' ') }} F</td>
                            <td class="px-3 sm:px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('dashboard.bons-commande.show', $bc) }}" class="text-primary-600 text-xs hover:underline">Voir</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">Aucun bon de commande</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
        {{ $bons->links() }}
    </div>
</x-dashboard-layout>
