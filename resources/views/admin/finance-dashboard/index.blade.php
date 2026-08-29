@extends('layouts.admin')
@section('page-title', 'Dashboard Finance')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- En-tête + filtre année --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="page-title">Dashboard Finance</h1>
            <p class="page-subtitle">Paiements en ligne, CA boutique et transactions</p>
        </div>
        <form method="GET" class="flex items-center gap-2">
            <select name="annee" class="form-input text-sm w-32" onchange="this.form.submit()">
                @for($y = now()->year; $y >= now()->year - 3; $y--)
                <option value="{{ $y }}" @selected($annee === $y)>{{ $y }}</option>
                @endfor
            </select>
            <select name="mois" class="form-input text-sm w-36" onchange="this.form.submit()">
                <option value="">Toute l'année</option>
                @foreach(['1'=>'Janvier','2'=>'Février','3'=>'Mars','4'=>'Avril','5'=>'Mai','6'=>'Juin','7'=>'Juillet','8'=>'Août','9'=>'Septembre','10'=>'Octobre','11'=>'Novembre','12'=>'Décembre'] as $m => $label)
                <option value="{{ $m }}" @selected((string)$moisFiltre === $m)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @if(session('success'))
    <div class="alert-success">{{ session('success') }}</div>
    @endif

    {{-- KPIs --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- CA total --}}
        <div class="card p-5">
            <div class="flex items-center justify-between">
                <p class="text-xs text-gray-500 dark:text-slate-400 font-semibold uppercase tracking-wider">CA total {{ $annee }}</p>
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-bold mt-3 text-gray-900 dark:text-white">{{ number_format($caTotalAnnee, 0, ',', ' ') }} F</p>
            @if($progression !== null)
            <p class="text-xs mt-1 {{ $progression >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                {{ $progression >= 0 ? '▲' : '▼' }} {{ abs($progression) }}% vs {{ $annee - 1 }}
            </p>
            @else
            <p class="text-xs mt-1 text-gray-400">Année précédente : {{ number_format($caTotalAnneePrecedente, 0, ',', ' ') }} F</p>
            @endif
        </div>

        {{-- CA plateforme --}}
        <div class="card p-5">
            <div class="flex items-center justify-between">
                <p class="text-xs text-gray-500 dark:text-slate-400 font-semibold uppercase tracking-wider">CA plateforme</p>
                <div class="w-9 h-9 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 dark:text-purple-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-bold mt-3 text-gray-900 dark:text-white">{{ number_format($caPlateformeAnnee, 0, ',', ' ') }} F</p>
            <p class="text-xs mt-1 text-gray-500 dark:text-slate-400">{{ number_format($nbTransactionsAnnee, 0) }} transactions · {{ number_format($totalFrais, 0, ',', ' ') }} F de frais</p>
        </div>

        {{-- CA boutique --}}
        <div class="card p-5">
            <div class="flex items-center justify-between">
                <p class="text-xs text-gray-500 dark:text-slate-400 font-semibold uppercase tracking-wider">CA boutique en ligne</p>
                <div class="w-9 h-9 rounded-xl bg-pink-100 dark:bg-pink-900/30 flex items-center justify-center text-pink-600 dark:text-pink-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-bold mt-3 text-gray-900 dark:text-white">{{ number_format($caBoutiqueAnnee, 0, ',', ' ') }} F</p>
            <p class="text-xs mt-1 text-gray-500 dark:text-slate-400">{{ number_format($nbCommandesPayees, 0) }} commandes payées</p>
        </div>

        {{-- Taux de succès --}}
        <div class="card p-5">
            <div class="flex items-center justify-between">
                <p class="text-xs text-gray-500 dark:text-slate-400 font-semibold uppercase tracking-wider">Taux de succès</p>
                <div class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-bold mt-3 text-gray-900 dark:text-white">{{ $tauxSucces }}%</p>
            <p class="text-xs mt-1 text-gray-500 dark:text-slate-400">{{ $nbReussies }} réussies / {{ $nbTotal }} totales</p>
        </div>
    </div>

    {{-- Graphique CA par mois --}}
    <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold text-lg text-gray-900 dark:text-white">Évolution du CA {{ $annee }}</h2>
            <div class="flex items-center gap-4 text-xs">
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-purple-500"></span> Plateforme</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-pink-400"></span> Boutique</span>
            </div>
        </div>
        <div class="h-72">
            <canvas id="chartCA"></canvas>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Répartition par type --}}
        <div class="card p-6">
            <h2 class="font-bold text-lg text-gray-900 dark:text-white mb-4">Par type</h2>
            @if($parType->count())
            <div class="space-y-4">
                @php $parTypeMax = $parType->max('total') ?: 1; @endphp
                @foreach($parType as $item)
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600 dark:text-slate-300 capitalize">{{ str_replace('_', ' ', $item->type) }}</span>
                        <span class="font-semibold">{{ number_format($item->total, 0, ',', ' ') }} F</span>
                    </div>
                    <div class="h-2 bg-gray-100 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div class="h-full bg-purple-500 rounded-full" style="width: {{ round($item->total / $parTypeMax * 100) }}%"></div>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $item->nb }} transaction(s)</p>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-gray-400">Aucune donnée pour {{ $annee }}.</p>
            @endif
        </div>

        {{-- Par méthode --}}
        <div class="card p-6">
            <h2 class="font-bold text-lg text-gray-900 dark:text-white mb-4">Par méthode</h2>
            @if($parMethode->count())
            <div class="space-y-4">
                @php $parMethodeMax = $parMethode->max('total') ?: 1; @endphp
                @foreach($parMethode as $item)
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600 dark:text-slate-300">{{ $item->payment_method_code === 'geniuspay' ? '🟣 GeniusPay' : '🏦 Virement bancaire' }}</span>
                        <span class="font-semibold">{{ number_format($item->total, 0, ',', ' ') }} F</span>
                    </div>
                    <div class="h-2 bg-gray-100 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div class="h-full bg-pink-500 rounded-full" style="width: {{ round($item->total / $parMethodeMax * 100) }}%"></div>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $item->nb }} transaction(s)</p>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-gray-400">Aucune donnée pour {{ $annee }}.</p>
            @endif

            {{-- Remboursements --}}
            <div class="mt-6 pt-4 border-t border-gray-100 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-slate-300">↩ Remboursements</span>
                    <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ number_format($montantRembourse, 0, ',', ' ') }} F</span>
                </div>
                <p class="text-xs text-gray-400 mt-0.5">{{ $nbRemboursements }} transaction(s) remboursée(s)</p>
            </div>
        </div>

        {{-- Top instituts boutique --}}
        <div class="card p-6">
            <h2 class="font-bold text-lg text-gray-900 dark:text-white mb-4">🏆 Top CA boutique</h2>
            @if($topInstitutsBoutique->count())
            <div class="space-y-3">
                @php $topMax = $topInstitutsBoutique->max('ca_en_ligne') ?: 1; @endphp
                @foreach($topInstitutsBoutique as $i => $inst)
                <div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-gray-800 dark:text-slate-200 truncate">
                            <span class="text-xs {{ $i === 0 ? 'text-amber-500' : 'text-gray-400' }}">#{{ $i + 1 }}</span> {{ $inst->nom }}
                        </span>
                        <span class="font-semibold">{{ number_format($inst->ca_en_ligne, 0, ',', ' ') }} F</span>
                    </div>
                    <div class="h-1.5 bg-gray-100 dark:bg-slate-700 rounded-full overflow-hidden mt-1">
                        <div class="h-full {{ $i === 0 ? 'bg-gradient-to-r from-amber-400 to-orange-500' : 'bg-purple-400' }} rounded-full" style="width: {{ round($inst->ca_en_ligne / $topMax * 100) }}%"></div>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $inst->nb_commandes }} commandes · {{ $inst->ville ?? '' }}</p>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-gray-400">Aucune commande payée en ligne.</p>
            @endif
        </div>
    </div>

    {{-- Transactions récentes --}}
    <div class="card overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-slate-700">
            <h2 class="font-bold text-lg text-gray-900 dark:text-white">Transactions récentes</h2>
            <a href="{{ route('admin.payment-transactions.index') }}" class="text-xs font-semibold text-purple-600 dark:text-purple-400 hover:underline">Voir tout →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-slate-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Référence</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Client</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Montant</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    @forelse($recentes as $tx)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/30 transition-colors">
                        <td class="px-6 py-3 font-mono text-xs font-semibold">{{ $tx->reference }}</td>
                        <td class="px-6 py-3">
                            <span class="font-medium">{{ $tx->user?->name }}</span>
                            <span class="text-xs text-gray-400 block">{{ $tx->user?->email }}</span>
                        </td>
                        <td class="px-6 py-3 capitalize text-gray-600 dark:text-slate-300">{{ str_replace('_', ' ', $tx->type) }}</td>
                        <td class="px-6 py-3 text-right font-semibold">{{ number_format($tx->amount, 0, ',', ' ') }} F</td>
                        <td class="px-6 py-3">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold
                                {{ $tx->status === 'completed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400' :
                                   ($tx->status === 'refunded' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400' :
                                   ($tx->status === 'pending' || $tx->status === 'processing' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400' :
                                   'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400')) }}">
                                @if($tx->status === 'completed') ✓ @endif
                                @if($tx->status === 'refunded') ↩ @endif
                                {{ $tx->status === 'refunded' ? 'Remboursé' : ucfirst($tx->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-gray-600 dark:text-slate-400">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-slate-400">Aucune transaction.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('chartCA');
    if (!ctx) return;

    const dark = document.documentElement.classList.contains('dark');
    const gridColor = dark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
    const labelColor = dark ? '#94a3b8' : '#6b7280';

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($moisLabels) !!},
            datasets: [
                {
                    label: 'Plateforme',
                    data: {!! json_encode($plateformeData) !!},
                    backgroundColor: '#9333ea',
                    borderRadius: 6,
                    stack: 'stack'
                },
                {
                    label: 'Boutique',
                    data: {!! json_encode($boutiqueData) !!},
                    backgroundColor: '#ec4899',
                    borderRadius: 6,
                    stack: 'stack'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    grid: { color: 'transparent' },
                    ticks: { color: labelColor, font: { size: 11 } }
                },
                y: {
                    grid: { color: gridColor },
                    ticks: {
                        color: labelColor,
                        font: { size: 11 },
                        callback: (v) => v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v
                    }
                }
            }
        }
    });
});
</script>
@endpush
