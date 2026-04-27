@extends('layouts.admin')
@section('title', $commercial->nom_complet . ' — Commercial')

@section('content')
<div class="mb-6 flex items-center gap-3">
    <a href="{{ route('admin.commerciaux.index') }}"
       class="p-1.5 rounded-lg bg-white/[0.06] text-gray-400 hover:text-white transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <div>
        <h1 class="text-xl font-bold text-white">{{ $commercial->nom_complet }}</h1>
        <p class="text-gray-400 text-sm">{{ $commercial->email }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Colonne gauche : infos + actions --}}
    <div class="space-y-4">
        {{-- Infos --}}
        <div class="bg-white/[0.03] border border-white/[0.06] rounded-2xl p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-white">Informations</h2>
                @if($commercial->actif)
                <span class="inline-flex px-2 py-0.5 rounded-md bg-green-500/10 text-green-400 text-[11px] font-semibold">Actif</span>
                @else
                <span class="inline-flex px-2 py-0.5 rounded-md bg-gray-500/10 text-gray-400 text-[11px] font-semibold">Inactif</span>
                @endif
            </div>

            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-400">Code</dt>
                    <dd class="font-mono font-bold text-purple-400">{{ $profil->code }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-400">Téléphone</dt>
                    <dd class="text-gray-300">{{ $profil->telephone ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-400">Parrainages</dt>
                    <dd class="text-white font-semibold">{{ $profil->parrainages->count() }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-400">Total gagné</dt>
                    <dd class="text-green-400 font-semibold">{{ number_format($profil->totalGagne(), 0, ',', ' ') }} FCFA</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-400">En attente</dt>
                    <dd class="text-yellow-400 font-semibold">{{ number_format($profil->totalEnAttente(), 0, ',', ' ') }} FCFA</dd>
                </div>
            </dl>

            @if($profil->notes)
            <div class="mt-3 pt-3 border-t border-white/[0.06]">
                <p class="text-xs text-gray-400 mb-1">Notes</p>
                <p class="text-gray-300 text-sm">{{ $profil->notes }}</p>
            </div>
            @endif
        </div>

        {{-- Actions --}}
        <div class="bg-white/[0.03] border border-white/[0.06] rounded-2xl p-5 space-y-2">
            <form method="POST" action="{{ route('admin.commerciaux.toggle', $commercial) }}">
                @csrf @method('PATCH')
                <button type="submit"
                        class="w-full px-4 py-2 rounded-xl border border-white/[0.1] text-gray-300 hover:text-white hover:bg-white/[0.06] text-sm font-medium transition-all text-left flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $commercial->actif ? 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' }}"/></svg>
                    {{ $commercial->actif ? 'Désactiver le compte' : 'Activer le compte' }}
                </button>
            </form>

            <form method="POST" action="{{ route('admin.commerciaux.destroy', $commercial) }}"
                  onsubmit="return confirm('Supprimer ce commercial ? Cette action est irréversible.')">
                @csrf @method('DELETE')
                <button type="submit"
                        class="w-full px-4 py-2 rounded-xl border border-red-500/30 text-red-400 hover:bg-red-500/10 text-sm font-medium transition-all text-left flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Supprimer
                </button>
            </form>
        </div>
    </div>

    {{-- Colonne droite : parrainages + commissions --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Parrainages --}}
        <div class="bg-white/[0.03] border border-white/[0.06] rounded-2xl overflow-hidden">
            <h2 class="text-sm font-semibold text-white px-5 py-4 border-b border-white/[0.06]">Parrainages ({{ $profil->parrainages->count() }})</h2>
            @forelse($profil->parrainages as $p)
            <div class="flex items-center justify-between px-5 py-3 border-b border-white/[0.04] last:border-0">
                <div>
                    <p class="text-gray-200 font-medium text-sm">{{ $p->proprietaire?->nom_complet }}</p>
                    <p class="text-gray-500 text-xs">{{ $p->proprietaire?->institut?->nom ?? '—' }} · {{ $p->proprietaire?->email }}</p>
                </div>
                <div class="text-right">
                    @if($p->isActif())
                    <span class="text-green-400 text-[11px] font-semibold">Actif</span>
                    @else
                    <span class="text-gray-500 text-[11px]">Expiré</span>
                    @endif
                    <p class="text-gray-500 text-[11px]">{{ $p->expire_le->format('d/m/Y') }}</p>
                </div>
            </div>
            @empty
            <p class="px-5 py-4 text-gray-500 text-sm">Aucun parrainage.</p>
            @endforelse
        </div>

        {{-- Commissions --}}
        <div class="bg-white/[0.03] border border-white/[0.06] rounded-2xl overflow-hidden">
            <h2 class="text-sm font-semibold text-white px-5 py-4 border-b border-white/[0.06]">
                Commissions ({{ $profil->commissions->count() }})
            </h2>
            @forelse($profil->commissions as $c)
            <div class="flex items-center justify-between px-5 py-3 border-b border-white/[0.04] last:border-0">
                <div>
                    <p class="text-gray-200 text-sm">{{ $c->parrainage?->proprietaire?->institut?->nom ?? '—' }}</p>
                    <p class="text-gray-500 text-xs">{{ $c->abonnement?->plan?->nom ?? '—' }} · {{ $c->created_at->format('d/m/Y') }}</p>
                </div>
                <div class="text-right flex items-center gap-3">
                    <div>
                        <p class="font-bold text-sm {{ $c->statut === 'payee' ? 'text-green-400' : 'text-yellow-400' }}">
                            {{ $c->montant_formatte }}
                        </p>
                        <span class="text-[11px] {{ $c->statut === 'payee' ? 'text-green-500' : 'text-yellow-500' }}">
                            {{ $c->statut === 'payee' ? 'Payée' : 'En attente' }}
                        </span>
                    </div>
                    @if($c->statut === 'en_attente')
                    <form method="POST" action="{{ route('admin.commerciaux.commissions.payer', $c) }}">
                        @csrf @method('PATCH')
                        <button type="submit"
                                class="px-2 py-1 rounded-lg bg-green-500/10 text-green-400 hover:bg-green-500/20 text-[11px] font-semibold transition-colors">
                            Marquer payée
                        </button>
                    </form>
                    @else
                    <form method="POST" action="{{ route('admin.commerciaux.commissions.annuler', $c) }}">
                        @csrf @method('PATCH')
                        <button type="submit"
                                class="px-2 py-1 rounded-lg bg-white/[0.06] text-gray-400 hover:bg-white/[0.1] text-[11px] font-semibold transition-colors">
                            Annuler
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            @empty
            <p class="px-5 py-4 text-gray-500 text-sm">Aucune commission.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
