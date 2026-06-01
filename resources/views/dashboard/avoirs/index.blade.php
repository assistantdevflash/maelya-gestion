<x-dashboard-layout>
<x-slot name="title">Avoirs</x-slot>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">Avoirs</h1>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-800 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead class="bg-gray-50 dark:bg-slate-800">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-slate-300 uppercase">Numéro</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-slate-300 uppercase">Date</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-slate-300 uppercase">Client</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-slate-300 uppercase">Vente</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600 dark:text-slate-300 uppercase">Montant</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-slate-300 uppercase">Code</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-slate-300 uppercase">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                    @forelse($avoirs as $avoir)
                        <tr>
                            <td class="px-4 py-2 text-sm font-mono text-gray-900 dark:text-slate-100">{{ $avoir->numero }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700 dark:text-slate-300">{{ $avoir->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700 dark:text-slate-300">
                                {{ $avoir->client ? $avoir->client->prenom . ' ' . $avoir->client->nom : '—' }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-700 dark:text-slate-300 font-mono">{{ $avoir->vente->numero ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm text-right font-bold text-gray-900 dark:text-slate-100">
                                {{ number_format($avoir->montant, 0, ',', ' ') }} FCFA
                            </td>
                            <td class="px-4 py-2 text-sm font-mono text-purple-700 dark:text-purple-300">
                                {{ $avoir->codeReduction->code ?? '—' }}
                            </td>
                            <td class="px-4 py-2 text-sm">
                                <span class="badge badge-{{ $avoir->statut === 'emis' ? 'success' : 'gray' }}">
                                    {{ ucfirst($avoir->statut) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-slate-400">
                                Aucun avoir pour le moment.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-800">
            {{ $avoirs->links() }}
        </div>
    </div>
</div>
</x-dashboard-layout>
