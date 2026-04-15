@extends('layouts.admin')
@section('page-title', 'Tableau de bord Admin')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="page-title">Plateforme Maëlya Gestion</h1>
        <p class="page-subtitle">Vue globale des instituts, abonnements et revenus.</p>
    </div>

    {{-- Alerte demandes en attente --}}
    @if($abonnementsEnAttente > 0)
    <a href="{{ route('admin.abonnements.index', ['statut' => 'en_attente']) }}" class="flex items-center gap-4 p-4 bg-gradient-to-r from-amber-50 to-orange-50 rounded-2xl ring-1 ring-amber-200/60 hover:ring-amber-300 transition-all group">
        <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        </div>
        <div class="flex-1">
            <p class="font-bold text-amber-900">{{ $abonnementsEnAttente }} demande(s) d'abonnement en attente</p>
            <p class="text-sm text-amber-700">Cliquez pour examiner et valider les paiements</p>
        </div>
        <svg class="w-5 h-5 text-amber-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </a>
    @endif

    {{-- KPI principaux --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="stat-card">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-primary-50">
                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $totalInstituts }}</p>
            <p class="text-xs text-gray-500 mt-1">Instituts inscrits</p>
        </div>
        <div class="stat-card">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-emerald-50">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-emerald-600">{{ $abonnementsActifs }}</p>
            <p class="text-xs text-gray-500 mt-1">Abonnements actifs</p>
        </div>
        <div class="stat-card">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, rgba(147,51,234,0.1), rgba(168,85,247,0.15));">
                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($revenusMois, 0, ',', ' ') }}<span class="text-sm font-normal text-gray-400 ml-1">FCFA</span></p>
            <p class="text-xs text-gray-500 mt-1">Revenus du mois</p>
            <p class="text-xs text-gray-400">Total : {{ number_format($revenusTotal, 0, ',', ' ') }} FCFA</p>
        </div>
        <div class="stat-card">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-secondary-50">
                    <svg class="w-5 h-5 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-secondary-600">{{ $nouveauxInscrits }}</p>
            <p class="text-xs text-gray-500 mt-1">Nouveaux inscrits</p>
            <p class="text-xs text-gray-400">ces 30 derniers jours</p>
        </div>
    </div>

    {{-- KPI secondaires --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <a href="{{ route('admin.abonnements.index', ['statut' => 'en_attente']) }}" class="card p-4 hover:shadow-md transition-all group {{ $abonnementsEnAttente > 0 ? 'ring-2 ring-amber-200' : '' }}">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-amber-600">{{ $abonnementsEnAttente }}</p>
                    <p class="text-xs text-gray-500">En attente de validation</p>
                </div>
            </div>
        </a>
        <a href="{{ route('admin.abonnements.index', ['statut' => 'expire']) }}" class="card p-4 hover:shadow-md transition-all group">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-red-600">{{ $abonnementsExpires }}</p>
                    <p class="text-xs text-gray-500">Abonnements expirés</p>
                </div>
            </div>
        </a>
        <a href="{{ route('admin.messages.index') }}" class="card p-4 hover:shadow-md transition-all group">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-blue-600">{{ $messagesNonLus }}</p>
                    <p class="text-xs text-gray-500">Messages non lus</p>
                </div>
            </div>
        </a>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Graphique inscriptions --}}
        <div class="lg:col-span-2 card p-6">
            <h2 class="font-bold text-gray-900 mb-4">Inscriptions — 30 derniers jours</h2>
            <canvas id="inscriptionsChart" height="180"></canvas>
        </div>

        {{-- Demandes récentes --}}
        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-bold text-gray-900 text-sm">Demandes récentes</h2>
                @if($abonnementsEnAttente > 0)
                    <span class="inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white rounded-full" style="background: linear-gradient(135deg, #f59e0b, #ef4444);">{{ $abonnementsEnAttente }}</span>
                @endif
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($demandesEnAttente as $demande)
                <a href="{{ route('admin.abonnements.show', $demande) }}" class="block px-4 py-3 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-gray-900 text-sm truncate">{{ $demande->user->nom_complet ?? $demande->user->name ?? '—' }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $demande->plan->nom ?? '' }} · {{ $demande->periode }} · {{ number_format($demande->montant ?? 0, 0, ',', ' ') }} F</p>
                        </div>
                        <div class="flex-shrink-0 ml-3">
                            <span class="badge bg-amber-100 text-amber-700 text-[10px]">En attente</span>
                        </div>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-1">{{ $demande->created_at->diffForHumans() }}</p>
                </a>
                @empty
                <div class="px-4 py-8 text-center">
                    <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-2">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <p class="text-xs text-gray-400">Aucune demande en attente</p>
                </div>
                @endforelse
            </div>
            <div class="px-4 py-3 border-t border-gray-100">
                <a href="{{ route('admin.abonnements.index') }}" class="text-xs text-primary-600 hover:underline">Voir tous les abonnements →</a>
            </div>
        </div>
    </div>

    {{-- Derniers instituts --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-bold text-gray-900 text-sm">Derniers instituts inscrits</h2>
            <a href="{{ route('admin.instituts.index') }}" class="text-xs text-primary-600 hover:underline">Voir tout →</a>
        </div>
        <table class="table-auto">
            <thead>
            <tr>
                <th>Institut</th>
                <th>Ville</th>
                <th>Abonnement</th>
                <th>Statut</th>
                <th>Inscription</th>
            </tr>
            </thead>
            <tbody>
            @foreach($derniersInstituts as $inst)
            @php $ownerAbo = $inst->users->firstWhere('role', 'admin')?->abonnementActif; @endphp
            <tr class="hover:bg-gray-50">
                <td>
                    <a href="{{ route('admin.instituts.show', $inst) }}" class="font-medium text-gray-900 hover:text-primary-600">{{ $inst->nom }}</a>
                    <div class="text-xs text-gray-400">{{ $inst->users_count ?? $inst->users->count() }} utilisateur(s)</div>
                </td>
                <td class="text-sm text-gray-600">{{ $inst->ville ?? '—' }}</td>
                <td>
                    @if($ownerAbo)
                        <span class="badge badge-success text-xs">{{ $ownerAbo->plan->nom ?? 'Actif' }}</span>
                        <div class="text-xs text-gray-400 mt-0.5">expire {{ $ownerAbo->expire_le?->format('d/m/Y') ?? '—' }}</div>
                    @else
                        <span class="badge bg-gray-100 text-gray-500 text-xs">Aucun</span>
                    @endif
                </td>
                <td>
                    <span class="badge {{ $inst->actif ? 'badge-success' : 'bg-red-100 text-red-700' }} text-xs">
                        {{ $inst->actif ? 'Actif' : 'Suspendu' }}
                    </span>
                </td>
                <td class="text-sm text-gray-500">{{ $inst->created_at->format('d/m/Y') }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    const ctx = document.getElementById('inscriptionsChart').getContext('2d');
    const data = @json($chartData ?? ['labels' => [], 'values' => []]);
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{ label: 'Inscriptions', data: data.values, backgroundColor: 'rgba(139, 92, 246, 0.7)', borderRadius: 4 }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } }
        }
    });
</script>
@endpush
