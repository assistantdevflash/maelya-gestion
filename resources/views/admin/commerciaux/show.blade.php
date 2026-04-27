@extends('layouts.admin')
@section('title', $commercial->nom_complet . ' — Commercial')

@section('content')
<div class="mb-6 flex items-center gap-3">
    <a href="{{ route('admin.commerciaux.index') }}"
       class="p-1.5 rounded-lg border border-gray-200 text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <div>
        <h1 class="page-title">{{ $commercial->nom_complet }}</h1>
        <p class="page-subtitle">{{ $commercial->email }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Colonne gauche : infos + actions --}}
    <div class="space-y-4">
        {{-- Infos --}}
        <div class="card p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-gray-900">Informations</h2>
                <span class="badge {{ $commercial->actif ? 'badge-success' : 'badge-gray' }} text-xs">
                    {{ $commercial->actif ? 'Actif' : 'Inactif' }}
                </span>
            </div>

            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Code</dt>
                    <dd class="font-mono font-bold text-primary-600">{{ $profil->code }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Téléphone</dt>
                    <dd class="text-gray-700">{{ $profil->telephone ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Parrainages</dt>
                    <dd class="font-semibold text-gray-900">{{ $profil->parrainages->count() }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Total gagné</dt>
                    <dd class="font-semibold text-emerald-600">{{ number_format($profil->totalGagne(), 0, ',', ' ') }} FCFA</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">En attente</dt>
                    <dd class="font-semibold text-amber-600">{{ number_format($profil->totalEnAttente(), 0, ',', ' ') }} FCFA</dd>
                </div>
            </dl>

            @if($profil->notes)
            <div class="mt-3 pt-3 border-t border-gray-100">
                <p class="text-xs text-gray-500 mb-1">Notes</p>
                <p class="text-gray-600 text-sm">{{ $profil->notes }}</p>
            </div>
            @endif
        </div>

        {{-- Actions --}}
        <div class="card p-5 space-y-2">
            <form method="POST" action="{{ route('admin.commerciaux.toggle', $commercial) }}">
                @csrf @method('PATCH')
                <button type="submit"
                        class="w-full px-4 py-2 rounded-xl border border-gray-200 text-gray-600 hover:text-gray-900 hover:bg-gray-50 text-sm font-medium transition-all text-left flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $commercial->actif ? 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' }}"/></svg>
                    {{ $commercial->actif ? 'Désactiver le compte' : 'Activer le compte' }}
                </button>
            </form>

            <form method="POST" action="{{ route('admin.commerciaux.destroy', $commercial) }}"
                  onsubmit="return confirm('Supprimer ce commercial ? Cette action est irréversible.')">
                @csrf @method('DELETE')
                <button type="submit"
                        class="w-full px-4 py-2 rounded-xl border border-red-200 text-red-600 hover:bg-red-50 text-sm font-medium transition-all text-left flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Supprimer
                </button>
            </form>
        </div>
    </div>

    {{-- Colonne droite : parrainages + commissions --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Parrainages --}}
        <div class="card overflow-hidden">
            <h2 class="text-sm font-semibold text-gray-900 px-5 py-4 border-b border-gray-100">Parrainages ({{ $profil->parrainages->count() }})</h2>
            @forelse($profil->parrainages as $p)
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100 last:border-0 hover:bg-gray-50 transition-colors">
                <div>
                    <p class="font-medium text-gray-900 text-sm">{{ $p->proprietaire?->nom_complet }}</p>
                    <p class="text-gray-400 text-xs">{{ $p->proprietaire?->institut?->nom ?? '—' }} · {{ $p->proprietaire?->email }}</p>
                </div>
                <div class="text-right">
                    <span class="badge {{ $p->isActif() ? 'badge-success' : 'badge-gray' }} text-[11px]">
                        {{ $p->isActif() ? 'Actif' : 'Expiré' }}
                    </span>
                    <p class="text-gray-400 text-[11px] mt-0.5">{{ $p->expire_le->format('d/m/Y') }}</p>
                </div>
            </div>
            @empty
            <p class="px-5 py-4 text-gray-400 text-sm">Aucun parrainage.</p>
            @endforelse
        </div>

        {{-- Commissions --}}
        <div class="card overflow-hidden">
            <h2 class="text-sm font-semibold text-gray-900 px-5 py-4 border-b border-gray-100">
                Commissions ({{ $profil->commissions->count() }})
            </h2>
            @forelse($profil->commissions as $c)
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100 last:border-0 hover:bg-gray-50 transition-colors">
                <div>
                    <p class="text-gray-900 text-sm">{{ $c->parrainage?->proprietaire?->institut?->nom ?? '—' }}</p>
                    <p class="text-gray-400 text-xs">{{ $c->abonnement?->plan?->nom ?? '—' }} · {{ $c->created_at->format('d/m/Y') }}</p>
                </div>
                <div class="text-right flex items-center gap-3">
                    <div>
                        <p class="font-bold text-sm {{ $c->statut === 'payee' ? 'text-emerald-600' : 'text-amber-600' }}">
                            {{ $c->montant_formatte }}
                        </p>
                        <span class="badge {{ $c->statut === 'payee' ? 'badge-success' : 'badge-warning' }} text-[11px]">
                            {{ $c->statut === 'payee' ? 'Payée' : 'En attente' }}
                        </span>
                    </div>
                    @if($c->statut === 'en_attente')
                    <form method="POST" action="{{ route('admin.commerciaux.commissions.payer', $c) }}">
                        @csrf @method('PATCH')
                        <button type="submit"
                                class="px-2 py-1 rounded-lg border border-emerald-200 text-emerald-600 bg-emerald-50 hover:bg-emerald-100 text-[11px] font-semibold transition-colors">
                            Marquer payée
                        </button>
                    </form>
                    @else
                    <form method="POST" action="{{ route('admin.commerciaux.commissions.annuler', $c) }}">
                        @csrf @method('PATCH')
                        <button type="submit"
                                class="px-2 py-1 rounded-lg border border-gray-200 text-gray-500 bg-gray-50 hover:bg-gray-100 text-[11px] font-semibold transition-colors">
                            Annuler
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            @empty
            <p class="px-5 py-4 text-gray-400 text-sm">Aucune commission.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

