@extends('layouts.admin')
@section('page-title', 'Établissements')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="page-title">Établissements</h1>
            <p class="page-subtitle">{{ $paginator->total() }} inscrits au total</p>
        </div>
    </div>

    {{-- Barre de recherche --}}
    <form method="GET" id="filter-form" class="flex flex-wrap gap-3">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher par nom, ville…"
               class="form-input max-w-xs"
               onchange="document.getElementById('filter-form').submit()">
        <select name="status" class="form-input max-w-[160px]" onchange="document.getElementById('filter-form').submit()">
            <option value="">Tous les statuts</option>
            <option value="actif"   @selected(request('status') === 'actif')>Actif</option>
            <option value="inactif" @selected(request('status') === 'inactif')>Inactif</option>
        </select>
        <select name="plan" class="form-input max-w-[180px]" onchange="document.getElementById('filter-form').submit()">
            <option value="">Tous les plans</option>
            <option value="__aucun__" @selected(request('plan') === '__aucun__')>Sans abonnement</option>
            @foreach($plans as $plan)
                <option value="{{ $plan->slug }}" @selected(request('plan') === $plan->slug)>{{ $plan->nom }}</option>
            @endforeach
        </select>
        @if(request('q') || request('status') || request('plan'))
            <a href="{{ route('admin.instituts.index') }}" class="btn-secondary">Réinitialiser</a>
        @endif
    </form>

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

    @forelse($grouped as $proprietaireId => $instituts)
    @php $proprio = $instituts->first()->proprietaire; @endphp

    {{-- En-tête propriétaire --}}
    <div class="flex items-center gap-3 pt-2 min-w-0">
        <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
             style="background: linear-gradient(135deg, #9333ea, #ec4899);">
            {{ strtoupper(substr($proprio?->prenom ?? '?', 0, 1)) }}{{ strtoupper(substr($proprio?->nom_famille ?? '', 0, 1)) }}
        </div>
        <div class="min-w-0 flex-1">
            <p class="font-semibold text-gray-900 text-sm truncate">{{ $proprio?->nom_complet ?? 'Propriétaire inconnu' }}</p>
            <p class="text-xs text-gray-400 truncate">{{ $proprio?->email }} — {{ $instituts->count() }} établissement(s)</p>
        </div>
        @if($proprio)
            @php $abo = $proprio->abonnementActif; @endphp
            @if($abo)
                <span class="ml-auto badge badge-success text-xs flex-shrink-0">{{ $abo->plan->nom }}</span>
            @else
                <span class="ml-auto badge bg-gray-100 text-gray-500 text-xs flex-shrink-0">Sans abo</span>
            @endif
        @endif
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
        <table class="table-auto w-full">
            <thead>
            <tr>
                <th>Établissement</th>
                <th class="hidden sm:table-cell">Type</th>
                <th class="hidden sm:table-cell">Ville</th>
                <th>Statut</th>
                <th class="hidden md:table-cell">Inscrit le</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach($instituts as $inst)
            <tr class="hover:bg-gray-50">
                <td>
                    <div class="font-medium text-gray-900">{{ $inst->nom }}</div>
                    <div class="text-xs text-gray-400">{{ $inst->users_count }} utilisateur(s)</div>
                </td>
                <td class="text-sm text-gray-600 hidden sm:table-cell">{{ $typeLabels[$inst->type] ?? ($inst->type ?? '—') }}</td>
                <td class="text-sm text-gray-600 hidden sm:table-cell">{{ $inst->ville ?? '—' }}</td>
                <td>
                    <span class="badge {{ $inst->actif ? 'badge-success' : 'bg-red-100 text-red-700' }} text-xs">
                        {{ $inst->actif ? 'Actif' : 'Suspendu' }}
                    </span>
                </td>
                <td class="text-sm text-gray-500 hidden md:table-cell">{{ $inst->created_at->format('d/m/Y') }}</td>
                <td>
                    <a href="{{ route('admin.instituts.show', $inst) }}" class="btn-outline btn-sm text-xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        Voir
                    </a>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>
    </div>
    @empty
    <div class="card p-10 text-center text-gray-400">Aucun établissement trouvé.</div>
    @endforelse

    {{ $paginator->withQueryString()->links() }}
</div>
@endsection
