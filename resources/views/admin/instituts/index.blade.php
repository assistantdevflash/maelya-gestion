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
        <select name="type" class="form-input max-w-[200px]" onchange="document.getElementById('filter-form').submit()">
            <option value="">Tous les types</option>
            @foreach($typeLabels as $code => $libelle)
                <option value="{{ $code }}" @selected(request('type') === $code)>{{ $libelle }}</option>
            @endforeach
        </select>
        <select name="activite" class="form-input max-w-[200px]" onchange="document.getElementById('filter-form').submit()">
            <option value="">Toute activité</option>
            <option value="actif"   @selected(request('activite') === 'actif')>Actif (≤ 7 jours)</option>
            <option value="inactif" @selected(request('activite') === 'inactif')>Dormant (&gt; 30 jours)</option>
            <option value="jamais"  @selected(request('activite') === 'jamais')>Jamais utilisé</option>
        </select>
        @if(request('q') || request('status') || request('plan') || request('type') || request('activite'))
            <a href="{{ route('admin.instituts.index') }}" class="btn-secondary">Réinitialiser</a>
        @endif
    </form>

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
            <p class="text-xs text-gray-400 truncate">{{ $proprio?->email }} — {{ \App\Helpers\TexteHelper::pluriel($instituts->count(), 'établissement', 'établissements') }}</p>
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
        {{-- Mobile : cartes --}}
        <div class="sm:hidden divide-y divide-gray-100">
            @foreach($instituts as $inst)
            <div class="flex items-center gap-3 p-3">
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-sm text-gray-900 truncate">{{ $inst->nom }}</p>
                    <p class="text-xs text-gray-400">{{ $inst->ville ?? '' }} · {{ \App\Helpers\TexteHelper::pluriel($inst->users_count, 'utilisateur', 'utilisateurs') }}</p>
                </div>
                <span class="badge {{ $inst->actif ? 'badge-success' : 'bg-red-100 text-red-700' }} text-xs flex-shrink-0">
                    {{ $inst->actif ? 'Actif' : 'Suspendu' }}
                </span>
                <a href="{{ route('admin.instituts.show', $inst) }}" class="text-xs text-primary-600 font-medium hover:underline flex-shrink-0">Voir</a>
            </div>
            @endforeach
        </div>
        {{-- Desktop : tableau --}}
        <div class="hidden sm:block overflow-x-auto">
        <table class="table-auto w-full">
            <thead>
            <tr>
                <th>Établissement</th>
                <th class="hidden sm:table-cell">Type</th>
                <th class="hidden sm:table-cell">Ville</th>
                <th>Statut</th>
                <th class="hidden md:table-cell">Inscrit le</th>
                <th class="hidden lg:table-cell">Dernière activité</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach($instituts as $inst)
            <tr class="hover:bg-gray-50">
                <td>
                    <div class="font-medium text-gray-900">{{ $inst->nom }}</div>
                    <div class="text-xs text-gray-400">{{ \App\Helpers\TexteHelper::pluriel($inst->users_count, 'utilisateur', 'utilisateurs') }}</div>
                </td>
                <td class="text-sm text-gray-600 hidden sm:table-cell">{{ $typeLabels[$inst->type] ?? ($inst->type ?? '—') }}</td>
                <td class="text-sm text-gray-600 hidden sm:table-cell">{{ $inst->ville ?? '—' }}</td>
                <td>
                    <span class="badge {{ $inst->actif ? 'badge-success' : 'bg-red-100 text-red-700' }} text-xs">
                        {{ $inst->actif ? 'Actif' : 'Suspendu' }}
                    </span>
                </td>
                <td class="text-sm text-gray-500 hidden md:table-cell">{{ $inst->created_at->format('d/m/Y') }}</td>
                <td class="hidden lg:table-cell">
                    @if($inst->derniere_activite_le)
                        <div class="text-sm text-gray-700 dark:text-gray-300">{{ $inst->derniere_activite_le->format('d/m/Y') }}</div>
                        <div class="text-xs text-gray-400">
                            {{ $inst->derniere_activite_le->format('H:i') }} · {{ $inst->derniere_activite_le->diffForHumans() }}
                        </div>
                    @else
                        <span class="text-xs text-gray-400 italic">Jamais</span>
                    @endif
                </td>
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
