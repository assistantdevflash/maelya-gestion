@extends('layouts.admin')
@section('page-title', 'Abonnements')

@section('content')
<div class="space-y-6">

    <div>
        <h1 class="page-title">Abonnements plateforme</h1>
        <p class="page-subtitle">Gérez les demandes et abonnements actifs.</p>
    </div>

    {{-- Stats rapides --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <a href="{{ route('admin.abonnements.index', ['statut' => 'en_attente']) }}" class="card p-4 hover:shadow-md transition {{ request('statut') === 'en_attente' ? 'ring-2 ring-amber-400' : '' }}">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-amber-600">{{ $stats['en_attente'] }}</p>
                    <p class="text-xs text-gray-500">En attente</p>
                </div>
            </div>
        </a>
        <a href="{{ route('admin.abonnements.index', ['statut' => 'actif']) }}" class="card p-4 hover:shadow-md transition {{ request('statut') === 'actif' ? 'ring-2 ring-emerald-400' : '' }}">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-emerald-600">{{ $stats['actif'] }}</p>
                    <p class="text-xs text-gray-500">Actifs</p>
                </div>
            </div>
        </a>
        <a href="{{ route('admin.abonnements.index', ['statut' => 'expire']) }}" class="card p-4 hover:shadow-md transition {{ request('statut') === 'expire' ? 'ring-2 ring-red-400' : '' }}">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-red-600">{{ $stats['expire'] }}</p>
                    <p class="text-xs text-gray-500">Expirés</p>
                </div>
            </div>
        </a>
    </div>

    {{-- Filtres --}}
    <form method="GET" class="flex flex-wrap gap-3">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Nom ou email…" class="form-input max-w-xs">
        <select name="statut" class="form-input max-w-[160px]">
            <option value="">Tous les statuts</option>
            <option value="en_attente" @selected(request('statut') === 'en_attente')>En attente</option>
            <option value="actif" @selected(request('statut') === 'actif')>Actif</option>
            <option value="expire" @selected(request('statut') === 'expire')>Expiré</option>
            <option value="rejete" @selected(request('statut') === 'rejete')>Rejeté</option>
        </select>
        <button class="btn-primary">Filtrer</button>
        @if(request()->hasAny(['q', 'statut']))
            <a href="{{ route('admin.abonnements.index') }}" class="btn-secondary">Réinitialiser</a>
        @endif
    </form>

    <div class="card overflow-hidden">
        <table class="table-auto">
            <thead>
            <tr>
                <th>Utilisateur</th>
                <th>Plan</th>
                <th>Période</th>
                <th>Montant</th>
                <th>Dates</th>
                <th>Statut</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($abonnements as $ab)
            <tr class="hover:bg-gray-50">
                <td>
                    <div class="font-medium text-gray-900">{{ $ab->user->nom_complet ?? $ab->user->name ?? '—' }}</div>
                    <div class="text-xs text-gray-400">
                        {{ $ab->user->email ?? '' }} · {{ $ab->user->institut->nom ?? '' }}
                        @if($ab->user->parraine_par)
                            <span class="inline-flex items-center gap-0.5 ml-1 px-1.5 py-0.5 rounded-full bg-purple-100 text-purple-700 text-[9px] font-bold">🤝 Parrainé</span>
                        @endif
                    </div>
                </td>
                <td class="text-sm font-medium">{{ $ab->plan->nom ?? '—' }}</td>
                <td class="text-sm text-gray-600 capitalize">{{ $ab->periode }}</td>
                <td class="text-sm font-semibold">
                    {{ number_format($ab->montant ?? 0, 0, ',', ' ') }} <span class="text-gray-400 font-normal">FCFA</span>
                    @if($ab->plan && $ab->montant < $ab->plan->prix)
                        <div class="mt-0.5">
                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full bg-gradient-to-r from-amber-400 to-orange-500 text-white text-[9px] font-bold uppercase">🔥 Offre</span>
                        </div>
                    @endif
                </td>
                <td class="text-sm text-gray-600">
                    @if($ab->debut_le)
                        {{ $ab->debut_le->format('d/m/Y') }} → {{ $ab->expire_le?->format('d/m/Y') ?? '—' }}
                    @else
                        <span class="text-gray-400">—</span>
                    @endif
                </td>
                <td>
                    @php
                        // Un abonnement marqué "actif" en BDD mais dont la date est dépassée
                        // est traité comme expiré (le cron de mise à jour peut avoir du retard).
                        $statutEffectif = ($ab->statut === 'actif' && $ab->expire_le?->isPast())
                            ? 'expire'
                            : $ab->statut;
                        $colors = [
                            'en_attente' => 'bg-amber-100 text-amber-700',
                            'actif' => 'badge-success',
                            'expire' => 'bg-red-100 text-red-700',
                            'rejete' => 'bg-gray-100 text-gray-500',
                            'annule' => 'bg-gray-100 text-gray-500',
                        ];
                        $labels = [
                            'en_attente' => 'En attente',
                            'actif' => 'Actif',
                            'expire' => 'Expiré',
                            'rejete' => 'Rejeté',
                            'annule' => 'Annulé',
                        ];
                    @endphp
                    <span class="badge {{ $colors[$statutEffectif] ?? 'bg-gray-100 text-gray-500' }} text-xs">
                        {{ $labels[$statutEffectif] ?? $statutEffectif }}
                    </span>
                </td>
                <td>
                    <a href="{{ route('admin.abonnements.show', $ab) }}" class="btn-outline btn-sm inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        Voir
                    </a>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center py-10 text-gray-400">Aucun abonnement.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $abonnements->withQueryString()->links() }}
</div>
@endsection
