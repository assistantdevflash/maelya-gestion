@extends('layouts.commercial')
@section('title', 'Mes parrainages')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Mes parrainages</h1>
    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Établissements inscrits via votre code <span class="font-mono font-bold text-primary-600">{{ $profil->code }}</span></p>
    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1.5">
        La date de commission correspond à votre période de rémunération.
        L'échéance d'abonnement, elle, indique quand le client doit renouveler.
    </p>
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
            <th>Abonnement</th>
            <th>Ma commission</th>
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
                <span class="text-xs text-gray-400">({{ \App\Helpers\TexteHelper::pluriel($p->commissions->count(), 'abonnement', 'abonnements') }})</span>
            </td>
            {{-- Échéance réelle de l'abonnement du filleul --}}
            @php
                $abo = $p->proprietaire?->abonnements?->first();
                $joursAbo = ($abo && $abo->expire_le)
                    ? (int) today()->diffInDays($abo->expire_le->copy()->startOfDay(), false)
                    : null;
            @endphp
            <td>
                @if($abo && $abo->expire_le)
                    <span class="text-gray-700 dark:text-gray-300">{{ $abo->expire_le->format('d/m/Y') }}</span>
                    @if($joursAbo !== null && $joursAbo < 0)
                        <span class="ml-1.5 px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">
                            expiré
                        </span>
                    @elseif($joursAbo !== null && $joursAbo <= 7)
                        <span class="ml-1.5 px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                            {{ $joursAbo === 0 ? "aujourd'hui" : 'dans ' . $joursAbo . ' j' }}
                        </span>
                    @elseif($joursAbo !== null)
                        <span class="ml-1.5 text-[11px] text-gray-400">dans {{ $joursAbo }} j</span>
                    @endif
                @else
                    <span class="text-gray-400">—</span>
                @endif
            </td>
            {{-- Période de commission du commercial --}}
            <td>
                <span class="badge {{ $p->isActif() ? 'badge-success' : 'badge-gray' }} text-[11px]">
                    {{ $p->isActif() ? 'Active' : 'Terminée' }}
                </span>
                <p class="text-[11px] text-gray-400 mt-0.5">jusqu'au {{ $p->expire_le->format('d/m/Y') }}</p>
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
