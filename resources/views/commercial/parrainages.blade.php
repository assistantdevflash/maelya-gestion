@extends('layouts.commercial')
@section('title', 'Mes parrainages')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Mes parrainages</h1>
    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Instituts inscrits via votre code <span class="font-mono font-bold text-purple-600">{{ $profil->code }}</span></p>
</div>

<div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
    @if($parrainages->isEmpty())
    <div class="p-12 text-center">
        <p class="text-gray-500 dark:text-gray-400 text-sm">Aucun parrainage pour l'instant.</p>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Partagez votre lien d'inscription pour commencer.</p>
    </div>
    @else
    <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Institut</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden md:table-cell">Propriétaire</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden md:table-cell">Commission générée</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Expire le</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Statut</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach($parrainages as $p)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                <td class="px-5 py-3">
                    <p class="font-medium text-gray-900 dark:text-white">{{ $p->proprietaire?->institut?->nom ?? '—' }}</p>
                    <p class="text-xs text-gray-400">{{ $p->proprietaire?->institut?->ville ?? '' }}</p>
                </td>
                <td class="px-5 py-3 hidden md:table-cell text-gray-700 dark:text-gray-300">
                    {{ $p->proprietaire?->nom_complet }}
                </td>
                <td class="px-5 py-3 hidden md:table-cell font-medium text-gray-900 dark:text-white">
                    {{ number_format($p->commissions->sum('montant'), 0, ',', ' ') }} FCFA
                    <span class="text-xs text-gray-400">({{ $p->commissions->count() }} abonnement(s))</span>
                </td>
                <td class="px-5 py-3 text-gray-600 dark:text-gray-400">
                    {{ $p->expire_le->format('d/m/Y') }}
                </td>
                <td class="px-5 py-3">
                    @if($p->isActif())
                    <span class="inline-flex px-2 py-0.5 rounded-md bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 text-[11px] font-semibold">Actif</span>
                    @else
                    <span class="inline-flex px-2 py-0.5 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-500 text-[11px] font-semibold">Expiré</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($parrainages->hasPages())
    <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
        {{ $parrainages->links() }}
    </div>
    @endif
    @endif
</div>
@endsection
