@extends('layouts.admin')
@section('title', 'Commerciaux')

@section('content')
<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
        <h1 class="text-xl font-bold text-white">Commerciaux</h1>
        <p class="text-gray-400 text-sm mt-0.5">Réseau de vente Maëlya Gestion</p>
    </div>
    <button onclick="document.getElementById('modal-nouveau').classList.remove('hidden')"
            class="flex items-center gap-2 px-4 py-2 rounded-xl text-white text-sm font-semibold transition-all"
            style="background: linear-gradient(135deg, #9333ea, #ec4899);">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Nouveau commercial
    </button>
</div>

{{-- Stats --}}
<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-white/[0.03] border border-white/[0.06] rounded-2xl p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wider">Commerciaux</p>
        <p class="text-2xl font-bold text-white mt-1">{{ $stats['total'] }}</p>
    </div>
    <div class="bg-white/[0.03] border border-white/[0.06] rounded-2xl p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wider">Commissions totales</p>
        <p class="text-xl font-bold text-green-400 mt-1">{{ number_format($stats['commissions'], 0, ',', ' ') }} FCFA</p>
    </div>
    <div class="bg-white/[0.03] border border-white/[0.06] rounded-2xl p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wider">En attente</p>
        <p class="text-xl font-bold text-yellow-400 mt-1">{{ number_format($stats['en_attente'], 0, ',', ' ') }} FCFA</p>
    </div>
</div>

{{-- Config taux --}}
<div class="bg-white/[0.03] border border-white/[0.06] rounded-2xl p-5 mb-6">
    <h2 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
        <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        Paramètres de commission
    </h2>
    <form method="POST" action="{{ route('admin.commerciaux.config') }}" class="flex items-end gap-4 flex-wrap">
        @csrf @method('PATCH')
        <div>
            <label class="block text-xs text-gray-400 mb-1">Taux de commission (%)</label>
            <input type="number" name="taux" min="1" max="100"
                   value="{{ $config->taux ?? 20 }}"
                   class="px-3 py-2 rounded-xl bg-white/[0.06] border border-white/[0.1] text-white text-sm w-28 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20">
        </div>
        <div>
            <label class="block text-xs text-gray-400 mb-1">Durée (mois)</label>
            <input type="number" name="duree_mois" min="1" max="60"
                   value="{{ $config->duree_mois ?? 6 }}"
                   class="px-3 py-2 rounded-xl bg-white/[0.06] border border-white/[0.1] text-white text-sm w-28 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20">
        </div>
        <button type="submit"
                class="px-4 py-2 rounded-xl text-white text-sm font-semibold bg-purple-600 hover:bg-purple-500 transition-colors">
            Enregistrer
        </button>
    </form>
</div>

{{-- Recherche --}}
<form method="GET" class="mb-4">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher un commercial..."
           class="w-full md:w-80 px-4 py-2 rounded-xl bg-white/[0.06] border border-white/[0.1] text-white placeholder:text-gray-500 text-sm focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20">
</form>

{{-- Table --}}
<div class="bg-white/[0.03] border border-white/[0.06] rounded-2xl overflow-hidden">
    @if($commerciaux->isEmpty())
    <div class="p-12 text-center text-gray-400 text-sm">Aucun commercial pour l'instant.</div>
    @else
    <table class="w-full text-sm">
        <thead class="border-b border-white/[0.06]">
            <tr>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Commercial</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider hidden md:table-cell">Code</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider hidden lg:table-cell">Parrainages</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider hidden lg:table-cell">Commissions</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Statut</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/[0.04]">
            @foreach($commerciaux as $c)
            <tr class="hover:bg-white/[0.02] transition-colors">
                <td class="px-5 py-3">
                    <p class="text-white font-medium">{{ $c->nom_complet }}</p>
                    <p class="text-gray-400 text-xs">{{ $c->email }}</p>
                </td>
                <td class="px-5 py-3 hidden md:table-cell">
                    <span class="font-mono text-purple-400 text-sm font-bold">{{ $c->commercialProfile?->code ?? '—' }}</span>
                </td>
                <td class="px-5 py-3 hidden lg:table-cell text-gray-300">
                    {{ $c->commercialProfile?->parrainages()->count() ?? 0 }}
                </td>
                <td class="px-5 py-3 hidden lg:table-cell text-gray-300">
                    {{ number_format($c->commercialProfile?->commissions()->sum('montant') ?? 0, 0, ',', ' ') }} FCFA
                </td>
                <td class="px-5 py-3">
                    @if($c->actif)
                    <span class="inline-flex px-2 py-0.5 rounded-md bg-green-500/10 text-green-400 text-[11px] font-semibold">Actif</span>
                    @else
                    <span class="inline-flex px-2 py-0.5 rounded-md bg-gray-500/10 text-gray-400 text-[11px] font-semibold">Inactif</span>
                    @endif
                </td>
                <td class="px-5 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('admin.commerciaux.show', $c) }}"
                           class="px-3 py-1 rounded-lg bg-white/[0.06] text-gray-300 hover:text-white hover:bg-white/[0.1] text-xs transition-colors">
                            Détail
                        </a>
                        <form method="POST" action="{{ route('admin.commerciaux.toggle', $c) }}">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    class="px-3 py-1 rounded-lg bg-white/[0.06] text-gray-300 hover:text-white hover:bg-white/[0.1] text-xs transition-colors">
                                {{ $c->actif ? 'Désactiver' : 'Activer' }}
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if($commerciaux->hasPages())
    <div class="px-5 py-4 border-t border-white/[0.06]">
        {{ $commerciaux->links() }}
    </div>
    @endif
    @endif
</div>

{{-- Modal nouveau commercial --}}
<div id="modal-nouveau" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60">
    <div class="bg-gray-900 border border-white/[0.1] rounded-2xl p-6 w-full max-w-md shadow-2xl">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-white font-bold">Nouveau commercial</h3>
            <button onclick="document.getElementById('modal-nouveau').classList.add('hidden')"
                    class="text-gray-400 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.commerciaux.store') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Prénom *</label>
                    <input type="text" name="prenom" required
                           class="w-full px-3 py-2 rounded-xl bg-white/[0.06] border border-white/[0.1] text-white text-sm placeholder:text-gray-500 focus:border-purple-500">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Nom *</label>
                    <input type="text" name="nom_famille" required
                           class="w-full px-3 py-2 rounded-xl bg-white/[0.06] border border-white/[0.1] text-white text-sm placeholder:text-gray-500 focus:border-purple-500">
                </div>
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Email *</label>
                <input type="email" name="email" required
                       class="w-full px-3 py-2 rounded-xl bg-white/[0.06] border border-white/[0.1] text-white text-sm placeholder:text-gray-500 focus:border-purple-500">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Téléphone</label>
                <input type="text" name="telephone"
                       class="w-full px-3 py-2 rounded-xl bg-white/[0.06] border border-white/[0.1] text-white text-sm placeholder:text-gray-500 focus:border-purple-500">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Mot de passe *</label>
                <input type="password" name="password" required minlength="8"
                       class="w-full px-3 py-2 rounded-xl bg-white/[0.06] border border-white/[0.1] text-white text-sm placeholder:text-gray-500 focus:border-purple-500">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Notes internes</label>
                <textarea name="notes" rows="2"
                          class="w-full px-3 py-2 rounded-xl bg-white/[0.06] border border-white/[0.1] text-white text-sm placeholder:text-gray-500 focus:border-purple-500 resize-none"></textarea>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="document.getElementById('modal-nouveau').classList.add('hidden')"
                        class="flex-1 px-4 py-2 rounded-xl border border-white/[0.1] text-gray-300 hover:text-white text-sm font-medium transition-colors">
                    Annuler
                </button>
                <button type="submit"
                        class="flex-1 px-4 py-2 rounded-xl text-white text-sm font-semibold transition-all"
                        style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                    Créer le compte
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
