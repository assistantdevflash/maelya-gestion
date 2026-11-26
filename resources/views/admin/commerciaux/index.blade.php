@extends('layouts.admin')
@section('title', 'Commerciaux')

@section('content')
<div x-data="{ open: {{ $errors->any() ? 'true' : 'false' }} }" class="space-y-6">

    {{-- ═══ En-tête ═══ --}}
    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <h1 class="page-title">Commerciaux</h1>
            <p class="page-subtitle">Réseau de vente Maëlya Gestion</p>
        </div>
        <button @click="open = true" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nouveau commercial
        </button>
    </div>

    {{-- ═══ Indicateurs ═══ --}}
    @php
        $inactifs = $stats['total'] - $stats['actifs'];
    @endphp
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="card p-4 sm:p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">Commerciaux</p>
            <p class="text-2xl font-bold text-gray-900 mt-1 leading-tight">{{ $stats['total'] }}</p>
            <p class="text-[11px] text-gray-400 mt-1">
                {{ $stats['actifs'] }} actif{{ $stats['actifs'] > 1 ? 's' : '' }}
                @if($inactifs > 0)
                    · {{ $inactifs }} désactivé{{ $inactifs > 1 ? 's' : '' }}
                @endif
            </p>
        </div>

        <div class="card p-4 sm:p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">Commissions versées</p>
            <p class="text-xl sm:text-2xl font-bold text-emerald-600 mt-1 leading-tight">{{ number_format($stats['payees'], 0, ',', ' ') }}</p>
            <p class="text-[11px] text-gray-400 mt-1">FCFA effectivement payés</p>
        </div>

        <div class="card p-4 sm:p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">En attente</p>
            <p class="text-xl sm:text-2xl font-bold text-amber-600 mt-1 leading-tight">{{ number_format($stats['en_attente'], 0, ',', ' ') }}</p>
            <p class="text-[11px] text-gray-400 mt-1">FCFA restant à verser</p>
        </div>

        <div class="card p-4 sm:p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">Total dû</p>
            <p class="text-xl sm:text-2xl font-bold text-gray-900 mt-1 leading-tight">{{ number_format($stats['du'], 0, ',', ' ') }}</p>
            <p class="text-[11px] text-gray-400 mt-1">versé + en attente</p>
        </div>
    </div>

    {{-- ═══ Paramètres de commission ═══ --}}
    <div class="card p-5">
        <div class="flex items-start gap-3 mb-4">
            <div class="w-9 h-9 rounded-xl bg-primary-50 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div class="min-w-0">
                <h2 class="text-sm font-semibold text-gray-900">Paramètres de commission</h2>
                <p class="text-[11px] text-gray-400 mt-0.5">
                    La durée fixe à la fois l'échéance du parrainage et le plafond de commission versé.
                </p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.commerciaux.config') }}" class="flex items-end gap-4 flex-wrap">
            @csrf @method('PATCH')
            <div>
                <label class="form-label">Taux de commission (%)</label>
                <input type="number" name="taux" min="1" max="100"
                       value="{{ old('taux', $config->taux ?? 20) }}"
                       class="form-input w-28">
            </div>
            <div>
                <label class="form-label">Durée (mois)</label>
                <input type="number" name="duree_mois" min="1" max="60"
                       value="{{ old('duree_mois', $config->duree_mois ?? 6) }}"
                       class="form-input w-28">
            </div>
            <button type="submit" class="btn-primary">Enregistrer</button>
        </form>

        @error('taux')
            <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
        @enderror
        @error('duree_mois')
            <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
        @enderror
    </div>

    {{-- ═══ Barre d'outils ═══ --}}
    <div class="flex flex-wrap items-center gap-3">
        <form method="GET" class="flex-1 min-w-[220px] max-w-sm">
            <div class="relative">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Nom, email ou code…"
                       class="form-input pl-9">
            </div>
            @if(request('statut'))
                <input type="hidden" name="statut" value="{{ request('statut') }}">
            @endif
        </form>

        <div class="flex items-center gap-1.5">
            @foreach(['' => 'Tous', 'actif' => 'Actifs', 'inactif' => 'Désactivés'] as $valeur => $libelle)
                <a href="{{ route('admin.commerciaux.index', array_filter(['q' => request('q'), 'statut' => $valeur])) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ request('statut', '') === $valeur ? 'bg-primary-600 text-white' : 'bg-white text-gray-500 border border-gray-200 hover:bg-gray-50' }}">
                    {{ $libelle }}
                </a>
            @endforeach
        </div>

        @if(request('q') || request('statut'))
            <a href="{{ route('admin.commerciaux.index') }}" class="text-xs font-medium text-gray-400 hover:text-gray-700 ml-auto">Réinitialiser</a>
        @endif
    </div>

    {{-- ═══ Liste ═══ --}}
    <div class="card overflow-hidden">
        @if($commerciaux->isEmpty())
            <div class="p-12 text-center">
                <div class="w-12 h-12 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <p class="text-sm text-gray-500">
                    @if(request('q') || request('statut'))
                        Aucun commercial ne correspond à ce filtre.
                    @else
                        Aucun commercial pour l'instant.
                    @endif
                </p>
                @if(!request('q') && !request('statut'))
                    <button @click="open = true" class="btn-primary mt-4">Créer le premier commercial</button>
                @endif
            </div>
        @else

        {{-- Mobile --}}
        <div class="sm:hidden divide-y divide-gray-100">
            @foreach($commerciaux as $c)
            @php
                $profil = $c->commercialProfile;
                $payees = (int) ($profil?->commissions_payees ?? 0);
                $total  = (int) ($profil?->commissions_total ?? 0);
                $attente = $total - $payees;
            @endphp
            <div class="p-4 space-y-3">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                         style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                        {{ Str::upper(Str::substr($c->prenom ?? '?', 0, 1)) }}{{ Str::upper(Str::substr($c->nom_famille ?? '', 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-sm text-gray-900 truncate">{{ $c->nom_complet }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ $c->email }}</p>
                    </div>
                    <span class="badge {{ $c->actif ? 'badge-success' : 'badge-gray' }} text-xs flex-shrink-0">
                        {{ $c->actif ? 'Actif' : 'Désactivé' }}
                    </span>
                </div>

                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-lg bg-gray-50 py-2">
                        <p class="text-sm font-bold text-gray-900 font-mono">{{ $profil?->code ?? '—' }}</p>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide">Code</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 py-2">
                        <p class="text-sm font-bold text-gray-900">{{ $profil?->parrainages_count ?? 0 }}</p>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide">Parrainages</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 py-2">
                        <p class="text-sm font-bold {{ $attente > 0 ? 'text-amber-600' : 'text-gray-900' }}">{{ number_format($attente, 0, ',', ' ') }}</p>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide">FCFA dus</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.commerciaux.show', $c) }}"
                       class="flex-1 text-center px-3 py-2 text-xs font-semibold rounded-lg bg-primary-50 text-primary-700 hover:bg-primary-100 transition-colors">
                        Voir le détail
                    </a>
                    <form method="POST" action="{{ route('admin.commerciaux.toggle', $c) }}">
                        @csrf @method('PATCH')
                        <button type="submit"
                                class="px-3 py-2 text-xs font-semibold rounded-lg border transition-colors {{ $c->actif ? 'border-red-200 text-red-600 bg-red-50 hover:bg-red-100' : 'border-emerald-200 text-emerald-600 bg-emerald-50 hover:bg-emerald-100' }}">
                            {{ $c->actif ? 'Désactiver' : 'Activer' }}
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Desktop --}}
        <div class="hidden sm:block overflow-x-auto">
        <table class="table-auto w-full">
            <thead>
            <tr>
                <th>Commercial</th>
                <th class="hidden md:table-cell">Code</th>
                <th class="hidden lg:table-cell text-center">Parrainages</th>
                <th class="hidden lg:table-cell text-right">Commissions</th>
                <th>Statut</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach($commerciaux as $c)
            @php
                $profil = $c->commercialProfile;
                $payees = (int) ($profil?->commissions_payees ?? 0);
                $total  = (int) ($profil?->commissions_total ?? 0);
                $attente = $total - $payees;
            @endphp
            <tr class="hover:bg-gray-50 transition-colors">
                <td>
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                             style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                            {{ Str::upper(Str::substr($c->prenom ?? '?', 0, 1)) }}{{ Str::upper(Str::substr($c->nom_famille ?? '', 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <div class="font-medium text-gray-900 whitespace-nowrap">{{ $c->nom_complet }}</div>
                            <div class="text-xs text-gray-400">{{ $c->email }}</div>
                        </div>
                    </div>
                </td>
                <td class="hidden md:table-cell">
                    <span class="font-mono font-bold text-primary-600 text-sm">{{ $profil?->code ?? '—' }}</span>
                </td>
                <td class="hidden lg:table-cell text-center">
                    <span class="text-sm font-semibold text-gray-900">{{ $profil?->parrainages_count ?? 0 }}</span>
                    @if(($profil?->parrainages_actifs_count ?? 0) > 0)
                        <div class="text-[11px] text-gray-400">{{ $profil->parrainages_actifs_count }} actif{{ $profil->parrainages_actifs_count > 1 ? 's' : '' }}</div>
                    @endif
                </td>
                <td class="hidden lg:table-cell text-right">
                    @if($total > 0)
                        <div class="text-sm font-semibold text-gray-900">{{ number_format($total, 0, ',', ' ') }} FCFA</div>
                        <div class="text-[11px] text-gray-400">
                            {{ number_format($payees, 0, ',', ' ') }} versés
                            @if($attente > 0)
                                · <span class="text-amber-600 font-medium">{{ number_format($attente, 0, ',', ' ') }} en attente</span>
                            @endif
                        </div>
                    @else
                        <span class="text-sm text-gray-300">—</span>
                    @endif
                </td>
                <td>
                    <span class="badge {{ $c->actif ? 'badge-success' : 'badge-gray' }} text-xs">
                        {{ $c->actif ? 'Actif' : 'Désactivé' }}
                    </span>
                </td>
                <td>
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('admin.commerciaux.show', $c) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-primary-50 text-primary-700 hover:bg-primary-100 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            Détail
                        </a>
                        <form method="POST" action="{{ route('admin.commerciaux.toggle', $c) }}">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition-colors {{ $c->actif ? 'border-red-200 text-red-600 bg-red-50 hover:bg-red-100' : 'border-emerald-200 text-emerald-600 bg-emerald-50 hover:bg-emerald-100' }}">
                                {{ $c->actif ? 'Désactiver' : 'Activer' }}
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>

        <div class="p-4 border-t border-gray-100">{{ $commerciaux->links() }}</div>
        @endif
    </div>

    {{-- ═══ Modale de création ═══ --}}
    <div x-show="open" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
         @keydown.escape.window="open = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" @click.outside="open = false">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-900">Nouveau commercial</h2>
                <button @click="open = false" class="p-1 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.commerciaux.store') }}" class="p-6 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Prénom *</label>
                        <input type="text" name="prenom" value="{{ old('prenom') }}" required class="form-input">
                        @error('prenom') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Nom *</label>
                        <input type="text" name="nom_famille" value="{{ old('nom_famille') }}" required class="form-input">
                        @error('nom_famille') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="form-input">
                    @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Téléphone</label>
                    <input type="text" name="telephone" value="{{ old('telephone') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Mot de passe *</label>
                    <input type="password" name="password" required minlength="8" class="form-input">
                    @error('password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Notes internes</label>
                    <textarea name="notes" rows="2" class="form-input resize-none">{{ old('notes') }}</textarea>
                </div>
                <p class="text-[11px] text-gray-400">
                    Un code de parrainage est généré automatiquement à partir du prénom et du nom.
                </p>
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="open = false" class="flex-1 btn-secondary">Annuler</button>
                    <button type="submit" class="flex-1 btn-primary">Créer le compte</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
