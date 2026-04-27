@extends('layouts.admin')
@section('title', 'Commerciaux')

@section('content')
<div x-data="{ open: {{ $errors->any() ? 'true' : 'false' }} }" class="space-y-6">

    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="page-title">Commerciaux</h1>
            <p class="page-subtitle">Réseau de vente Maëlya Gestion</p>
        </div>
        <button @click="open = true" class="btn-primary">+ Nouveau commercial</button>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="stat-card">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Commerciaux</p>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
        </div>
        <div class="stat-card">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Commissions totales</p>
            <p class="text-xl font-bold text-emerald-600">{{ number_format($stats['commissions'], 0, ',', ' ') }} FCFA</p>
        </div>
        <div class="stat-card">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">En attente</p>
            <p class="text-xl font-bold text-amber-600">{{ number_format($stats['en_attente'], 0, ',', ' ') }} FCFA</p>
        </div>
    </div>

    {{-- Config taux --}}
    <div class="card p-5">
        <h2 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Paramètres de commission
        </h2>
        <form method="POST" action="{{ route('admin.commerciaux.config') }}" class="flex items-end gap-4 flex-wrap">
            @csrf @method('PATCH')
            <div>
                <label class="form-label">Taux de commission (%)</label>
                <input type="number" name="taux" min="1" max="100"
                       value="{{ $config->taux ?? 20 }}"
                       class="form-input w-28">
            </div>
            <div>
                <label class="form-label">Durée (mois)</label>
                <input type="number" name="duree_mois" min="1" max="60"
                       value="{{ $config->duree_mois ?? 6 }}"
                       class="form-input w-28">
            </div>
            <button type="submit" class="btn-primary">Enregistrer</button>
        </form>
    </div>

    {{-- Recherche --}}
    <form method="GET">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher un commercial..."
               class="form-input max-w-xs">
    </form>

    {{-- Table --}}
    <div class="card overflow-hidden">
        @if($commerciaux->isEmpty())
        <p class="text-center py-10 text-gray-400">Aucun commercial pour l'instant.</p>
        @else
        <table class="table-auto">
            <thead>
            <tr>
                <th>Commercial</th>
                <th class="hidden md:table-cell">Code</th>
                <th class="hidden lg:table-cell">Parrainages</th>
                <th class="hidden lg:table-cell">Commissions</th>
                <th>Statut</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach($commerciaux as $c)
            <tr class="hover:bg-gray-50">
                <td>
                    <div class="font-medium text-gray-900">{{ $c->nom_complet }}</div>
                    <div class="text-xs text-gray-400">{{ $c->email }}</div>
                </td>
                <td class="hidden md:table-cell">
                    <span class="font-mono font-bold text-primary-600">{{ $c->commercialProfile?->code ?? '—' }}</span>
                </td>
                <td class="hidden lg:table-cell text-sm text-gray-600">
                    {{ $c->commercialProfile?->parrainages()->count() ?? 0 }}
                </td>
                <td class="hidden lg:table-cell text-sm text-gray-600">
                    {{ number_format($c->commercialProfile?->commissions()->sum('montant') ?? 0, 0, ',', ' ') }} FCFA
                </td>
                <td>
                    <span class="badge {{ $c->actif ? 'badge-success' : 'badge-gray' }} text-xs">
                        {{ $c->actif ? 'Actif' : 'Inactif' }}
                    </span>
                </td>
                <td>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.commerciaux.show', $c) }}"
                           class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-200 text-gray-600 bg-gray-50 hover:bg-gray-100 transition-colors">
                            Détail
                        </a>
                        <form method="POST" action="{{ route('admin.commerciaux.toggle', $c) }}">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors
                                        {{ $c->actif
                                            ? 'border-red-200 text-red-600 bg-red-50 hover:bg-red-100'
                                            : 'border-emerald-200 text-emerald-600 bg-emerald-50 hover:bg-emerald-100' }}">
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
        <div class="p-4 border-t border-gray-100">{{ $commerciaux->links() }}</div>
        @endif
        @endif
    </div>

    {{-- Modal création --}}
    <div x-show="open" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
         @keydown.escape.window="open = false">
        <div @click.outside="open = false" class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-bold text-gray-900 text-lg">Nouveau commercial</h3>
                <button @click="open = false" class="text-gray-400 hover:text-gray-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 rounded-xl p-3 text-sm text-red-700 space-y-1">
                @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('admin.commerciaux.store') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Prénom *</label>
                        <input type="text" name="prenom" required value="{{ old('prenom') }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Nom *</label>
                        <input type="text" name="nom_famille" required value="{{ old('nom_famille') }}" class="form-input">
                    </div>
                </div>
                <div>
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" required value="{{ old('email') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Téléphone</label>
                    <input type="text" name="telephone" value="{{ old('telephone') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Mot de passe *</label>
                    <input type="password" name="password" required minlength="8" class="form-input">
                </div>
                <div>
                    <label class="form-label">Notes internes</label>
                    <textarea name="notes" rows="2" class="form-input resize-none">{{ old('notes') }}</textarea>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="open = false" class="flex-1 btn-secondary">Annuler</button>
                    <button type="submit" class="flex-1 btn-primary">Créer le compte</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
