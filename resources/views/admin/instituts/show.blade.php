@extends('layouts.admin')
@section('page-title', $institut->nom)

@section('content')
<div class="space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ route('admin.instituts.index') }}" class="text-gray-400 hover:text-gray-700 text-sm">← Instituts</a>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">

        {{-- Fiche institut --}}
        <div class="lg:col-span-2 space-y-5">
            <div class="card p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="page-title mb-0.5">{{ $institut->nom }}</h1>
    @php
                        $typeLabels = [
                            'salon_coiffure'  => 'Salon de coiffure',
                            'institut_beaute' => 'Institut de beauté',
                            'nail_bar'        => 'Nail bar',
                            'spa'             => 'Spa',
                            'barbier'         => 'Barbier',
                            'autre'           => 'Autre',
                        ];
                    @endphp
                    <p class="text-sm text-gray-500">{{ $typeLabels[$institut->type] ?? ($institut->type ?? 'Institut') }} — {{ $institut->ville ?? '' }}</p>
                    </div>
                    <span class="badge {{ $institut->actif ? 'badge-success' : 'bg-red-100 text-red-700' }}">
                        {{ $institut->actif ? 'Actif' : 'Suspendu' }}
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div><span class="text-gray-400">Email</span><p class="font-medium">{{ $institut->email ?? '—' }}</p></div>
                    <div><span class="text-gray-400">Téléphone</span><p class="font-medium">{{ $institut->telephone ?? '—' }}</p></div>
                    <div><span class="text-gray-400">Ville</span><p class="font-medium">{{ $institut->ville ?? '—' }}</p></div>
                    <div><span class="text-gray-400">Inscrit le</span><p class="font-medium">{{ $institut->created_at->format('d/m/Y') }}</p></div>
                </div>

                <form action="{{ route('admin.instituts.toggle', $institut) }}" method="POST" class="pt-2 border-t border-gray-100">
                    @csrf @method('PATCH')
                    <button class="{{ $institut->actif ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'badge-success hover:opacity-80' }} px-4 py-2 rounded-lg text-sm font-medium transition">
                        {{ $institut->actif ? "Suspendre l'accès" : "Réactiver l'accès" }}
                    </button>
                </form>
            </div>

            {{-- Utilisateurs --}}
            <div class="card overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 font-medium text-sm">Utilisateurs ({{ $institut->users->count() }})</div>
                <table class="table-auto">
                    <thead><tr><th>Nom</th><th>Rôle</th><th>Email</th><th>Inscrit</th></tr></thead>
                    <tbody>
                    @foreach($institut->users as $u)
                    <tr>
                        <td class="font-medium">{{ $u->prenom }} {{ $u->nom_famille }}</td>
                        <td><span class="badge bg-indigo-100 text-indigo-700 text-xs capitalize">{{ $u->role }}</span></td>
                        <td class="text-sm text-gray-500">{{ $u->email }}</td>
                        <td class="text-sm text-gray-400">{{ $u->created_at->format('d/m/Y') }}</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Abonnements --}}
        <div class="space-y-5">
            <div class="card p-5">
                <h2 class="font-bold text-sm text-gray-700 mb-3">Abonnement actuel</h2>

                @if(!$owner)
                    <p class="text-sm text-gray-400 italic">Aucun compte propriétaire (admin) lié à cet établissement.</p>

                @elseif($abonnementActif)
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <p class="font-bold text-primary-600 text-lg">{{ $abonnementActif->plan->nom }}</p>
                        <span class="badge badge-success text-xs flex-shrink-0">Actif</span>
                    </div>
                    <p class="text-xs text-gray-500">Expire le <strong>{{ $abonnementActif->expire_le?->format('d/m/Y') ?? '—' }}</strong></p>
                    <p class="text-xs text-gray-400 mt-2 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Propriétaire&nbsp;: <strong>{{ $owner->nom_complet }}</strong>
                    </p>

                @elseif($abonnementSursis)
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <p class="font-bold text-amber-600 text-lg">{{ $abonnementSursis->plan->nom }}</p>
                        <span class="badge bg-amber-100 text-amber-700 text-xs flex-shrink-0">Sursis</span>
                    </div>
                    <p class="text-xs text-amber-700">
                        Expiré depuis {{ $abonnementSursis->joursDepuisExpiration() }} jour(s)
                        ({{ $abonnementSursis->expire_le?->format('d/m/Y') }}).
                    </p>
                    <p class="text-xs text-gray-500 mt-1">Période de grâce de 2 jours — accès restreint en écriture.</p>
                    <p class="text-xs text-gray-400 mt-2 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Propriétaire&nbsp;: <strong>{{ $owner->nom_complet }}</strong>
                    </p>

                @else
                    <p class="text-sm text-gray-400">Aucun abonnement actif.</p>
                    <p class="text-xs text-gray-400 mt-2 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Propriétaire&nbsp;: <strong>{{ $owner->nom_complet }}</strong>
                    </p>
                @endif
            </div>

            <div class="card p-5">
                <h2 class="font-bold text-sm text-gray-700 mb-3">Attribuer un abonnement</h2>
                <form action="{{ route('admin.instituts.offrir', $institut) }}" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label class="form-label">Plan</label>
                        <select name="plan_id" class="form-input" required>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->nom }} — {{ number_format($plan->prix, 0, ',', ' ') }} FCFA/mois</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Durée (jours)</label>
                        <input type="number" name="jours" class="form-input" value="30" min="1" max="1095" required>
                    </div>
                    <button class="btn-primary w-full">Attribuer</button>
                </form>
            </div>

            <div class="card overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 text-sm font-medium">Historique</div>
                <div class="divide-y divide-gray-50">
                    @forelse($historique as $ab)
                    <div class="px-4 py-3 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="font-medium">{{ $ab->plan->nom ?? '—' }}</span>
                            @php
                                $c = ['en_attente' => 'bg-amber-100 text-amber-700', 'actif' => 'badge-success', 'expire' => 'bg-red-100 text-red-700', 'rejete' => 'bg-gray-100 text-gray-500'];
                            @endphp
                            <span class="badge {{ $c[$ab->statut] ?? 'bg-gray-100 text-gray-500' }} text-xs">{{ $ab->statut }}</span>
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $ab->debut_le?->format('d/m/Y') ?? '—' }} → {{ $ab->expire_le?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                    @empty
                    <p class="px-4 py-4 text-sm text-gray-400 text-center">Aucun historique.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
