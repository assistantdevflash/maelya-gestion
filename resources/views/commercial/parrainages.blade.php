@extends('layouts.commercial')
@section('title', 'Mes parrainages')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Mes parrainages</h1>
    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Établissements inscrits via votre code <span class="font-mono font-bold text-primary-600">{{ $profil->code }}</span></p>
</div>

<div class="card overflow-hidden">
    @if($parrainages->isEmpty())
    <div class="p-12 text-center">
        <p class="text-gray-500 text-sm">Aucun parrainage pour l'instant.</p>
        <p class="text-xs text-gray-400 mt-1">Partagez votre lien d'inscription pour commencer.</p>
    </div>
    @else
    <table class="table-auto">
        <thead>
        <tr>
            <th>Établissement</th>
            <th class="hidden md:table-cell">Propriétaire</th>
            <th class="hidden md:table-cell">Commission générée</th>
            <th>Expire le</th>
            <th>Statut</th>
        </tr>
        </thead>
        <tbody>
        @foreach($parrainages as $p)
        <tr>
            <td>
                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $p->proprietaire?->institut?->nom ?? '—' }}</p>
                <p class="text-xs text-gray-400">{{ $p->proprietaire?->institut?->ville ?? '' }}</p>
            </td>
            <td class="hidden md:table-cell text-gray-600 dark:text-gray-300">{{ $p->proprietaire?->nom_complet }}</td>
            <td class="hidden md:table-cell">
                <span class="font-medium text-gray-900 dark:text-gray-100">{{ number_format($p->commissions->sum('montant'), 0, ',', ' ') }} FCFA</span>
                <span class="text-xs text-gray-400">({{ $p->commissions->count() }} abonnement(s))</span>
            </td>
            <td class="text-gray-600">{{ $p->expire_le->format('d/m/Y') }}</td>
            <td>
                <span class="badge {{ $p->isActif() ? 'badge-success' : 'badge-gray' }} text-[11px]">
                    {{ $p->isActif() ? 'Actif' : 'Expiré' }}
                </span>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>

    @if($parrainages->hasPages())
    <div class="p-4 border-t border-gray-100">{{ $parrainages->links() }}</div>
    @endif
    @endif
</div>
@endsection
