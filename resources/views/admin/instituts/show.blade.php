@extends('layouts.admin')
@section('page-title', $institut->nom)

@section('content')
<div class="space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ route('admin.instituts.index') }}" class="text-gray-400 hover:text-gray-700 text-sm">← Instituts</a>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">

        {{-- Fiche institut --}}
        <div class="lg:col-span-2 space-y-5 min-w-0">
            <div class="card p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="page-title mb-0.5">{{ $institut->nom }}</h1>
    @php
                        $typeLabels = [
                            'salon_coiffure'  => 'Salon de coiffure',
                            'institut_beaute' => 'Institut de beauté',
                            'barbier'         => 'Barbier',
                            'centre_esthetique' => 'Centre esthétique',
                            'boutique_mode'   => 'Boutique de mode',
                            'imprimerie'      => 'Imprimerie',
                            'lavage_auto'     => 'Lavage auto',
                            'pressing'        => 'Pressing / Laverie',
                            'business_center' => 'Business center',
                            'depot_gaz'       => 'Dépôt de gaz',
                            'commerce'        => 'Commerce / Alimentation',
                            'evenementiel'   => 'Évènementiel',
                            'informatique_telephonie' => 'Informatique / Téléphonie',
                            'autre'           => 'Autre',
                        ];
                    @endphp
                    <p class="text-sm text-gray-500">{{ $typeLabels[$institut->type] ?? ($institut->type ?? 'Institut') }} — {{ $institut->ville ?? '' }}</p>
                    </div>
                    <span class="badge {{ $institut->actif ? 'badge-success' : 'bg-red-100 text-red-700' }}">
                        {{ $institut->actif ? 'Actif' : 'Suspendu' }}
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div class="min-w-0"><span class="text-gray-400">Email</span><p class="font-medium break-all">{{ $institut->email ?? '—' }}</p></div>
                    <div class="min-w-0"><span class="text-gray-400">Téléphone</span><p class="font-medium">{{ $institut->telephone ?? '—' }}</p></div>
                    <div class="min-w-0"><span class="text-gray-400">Ville</span><p class="font-medium break-words">{{ $institut->ville ?? '—' }}</p></div>
                    <div class="min-w-0"><span class="text-gray-400">Inscrit le</span><p class="font-medium">{{ $institut->created_at->format('d/m/Y') }}</p></div>
                </div>

                <form action="{{ route('admin.instituts.toggle', $institut) }}" method="POST" class="pt-2 border-t border-gray-100">
                    @csrf @method('PATCH')
                    <button class="{{ $institut->actif ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'badge-success hover:opacity-80' }} px-4 py-2 rounded-lg text-sm font-medium transition">
                        {{ $institut->actif ? "Suspendre l'accès" : "Réactiver l'accès" }}
                    </button>
                </form>
            </div>

            {{-- Utilisateurs --}}
            <div class="card overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 font-medium text-sm">Utilisateurs ({{ $institut->users->count() }})</div>
                <div class="overflow-x-auto">
                <table class="table-auto w-full">
                    <thead><tr><th>Nom</th><th>Rôle</th><th class="hidden sm:table-cell">Email</th><th class="hidden md:table-cell">Inscrit</th></tr></thead>
                    <tbody>
                    @foreach($institut->users as $u)
                    <tr>
                        <td class="font-medium">{{ $u->prenom }} {{ $u->nom_famille }}</td>
                        <td><span class="badge bg-indigo-100 text-indigo-700 text-xs capitalize">{{ $u->role }}</span></td>
                        <td class="text-sm text-gray-500 hidden sm:table-cell">{{ $u->email }}</td>
                        <td class="text-sm text-gray-400 hidden md:table-cell">{{ $u->created_at->format('d/m/Y') }}</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
                </div>
            </div>

            {{-- 📊 Statistiques de l'établissement --}}
            <div class="card p-5" x-data="statsFilter()">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-bold text-sm text-gray-700 dark:text-gray-200">Statistiques</h2>
                    <div class="flex rounded-lg bg-gray-100 dark:bg-slate-700 p-0.5 text-xs">
                        <button @click="periode = 'jour'" :class="periode === 'jour' ? 'bg-white dark:bg-slate-500 shadow-sm font-semibold text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 dark:hover:text-gray-200'" class="px-3 py-1.5 rounded-md transition">Jour</button>
                        <button @click="periode = 'mois'" :class="periode === 'mois' ? 'bg-white dark:bg-slate-500 shadow-sm font-semibold text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 dark:hover:text-gray-200'" class="px-3 py-1.5 rounded-md transition">Mois</button>
                        <button @click="periode = 'total'" :class="periode === 'total' ? 'bg-white dark:bg-slate-500 shadow-sm font-semibold text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 dark:hover:text-gray-200'" class="px-3 py-1.5 rounded-md transition">Total</button>
                    </div>
                </div>

                {{-- Ligne 1 : Produits, Prestations, Clients — valeurs fixes (pas de période) --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-3">
                    <div class="bg-indigo-50 dark:bg-indigo-900/30 rounded-xl p-3">
                        <p class="text-xs text-indigo-600 dark:text-indigo-200 font-medium mb-1">Produits</p>
                        <p class="text-xl font-bold text-indigo-700 dark:text-white">{{ $totalProduits }}</p>
                    </div>
                    <div class="bg-violet-50 dark:bg-violet-900/30 rounded-xl p-3">
                        <p class="text-xs text-violet-600 dark:text-violet-200 font-medium mb-1">Prestations</p>
                        <p class="text-xl font-bold text-violet-700 dark:text-white">{{ $totalPrestations }}</p>
                    </div>
                    <div class="bg-cyan-50 dark:bg-cyan-900/30 rounded-xl p-3">
                        <p class="text-xs text-cyan-600 dark:text-cyan-200 font-medium mb-1">Clients</p>
                        <p class="text-xl font-bold text-cyan-700 dark:text-white">
                            <span x-show="periode === 'total'" x-cloak>{{ $totalClients }}</span>
                            <span x-show="periode === 'mois'" x-cloak>{{ $clientsMois }}</span>
                            <span x-show="periode === 'jour'" x-cloak>{{ $clientsJour }}</span>
                        </p>
                    </div>
                </div>

                {{-- Ligne 2 : Ventes (filtrable) --}}
                <div class="bg-emerald-50 dark:bg-emerald-900/30 rounded-xl p-4 mb-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-emerald-600 dark:text-emerald-200 font-medium mb-0.5">💰 Ventes validées</p>
                            <p class="text-2xl font-bold text-emerald-700 dark:text-white">
                                <span x-show="periode === 'total'" x-cloak>{{ number_format($statsVentes->ca_total, 0, ',', ' ') }} FCFA</span>
                                <span x-show="periode === 'mois'" x-cloak>{{ number_format($statsVentes->ca_mois, 0, ',', ' ') }} FCFA</span>
                                <span x-show="periode === 'jour'" x-cloak>{{ number_format($statsVentes->ca_jour, 0, ',', ' ') }} FCFA</span>
                            </p>
                            <p class="text-xs text-emerald-600 dark:text-emerald-200/80 mt-0.5">
                                <span x-show="periode === 'total'" x-cloak>{{ $statsVentes->nb_total }} vente(s)</span>
                                <span x-show="periode === 'mois'" x-cloak>{{ $statsVentes->nb_mois }} vente(s) ce mois</span>
                                <span x-show="periode === 'jour'" x-cloak>{{ $statsVentes->nb_jour }} vente(s) aujourd'hui</span>
                            </p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-emerald-200 dark:bg-emerald-500/30 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                </div>

                {{-- Ligne 3 : Boutique en ligne (filtrable) --}}
                <div class="bg-amber-50 dark:bg-amber-900/30 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <p class="text-xs text-amber-600 dark:text-amber-200 font-medium">🛒 Boutique en ligne</p>
                            <span class="badge text-[10px] {{ $boutiqueActive ? 'badge-success' : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-200' }}">
                                {{ $boutiqueActive ? 'Activée' : 'Désactivée' }}
                            </span>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-amber-200 dark:bg-amber-500/30 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-amber-700 dark:text-white">
                        <span x-show="periode === 'total'" x-cloak>{{ number_format($statsCommandes->ca_total, 0, ',', ' ') }} FCFA</span>
                        <span x-show="periode === 'mois'" x-cloak>{{ number_format($statsCommandes->ca_mois, 0, ',', ' ') }} FCFA</span>
                        <span x-show="periode === 'jour'" x-cloak>{{ number_format($statsCommandes->ca_jour, 0, ',', ' ') }} FCFA</span>
                    </p>
                    <p class="text-xs text-amber-600 dark:text-amber-200/80 mt-0.5">
                        <span x-show="periode === 'total'" x-cloak>{{ $statsCommandes->nb_total }} commande(s)</span>
                        <span x-show="periode === 'mois'" x-cloak>{{ $statsCommandes->nb_mois }} commande(s) ce mois</span>
                        <span x-show="periode === 'jour'" x-cloak>{{ $statsCommandes->nb_jour }} commande(s) aujourd'hui</span>
                    </p>
                    @if($boutiqueActive)
                    <div class="mt-2 pt-2 border-t border-amber-200 dark:border-amber-700/50 flex items-center gap-2" x-data="{ copied: false }">
                        <a href="{{ route('shop.index', $institut->slug) }}" target="_blank" class="text-xs text-amber-700 dark:text-amber-300 font-mono underline truncate flex-1">{{ route('shop.index', $institut->slug) }}</a>
                        <button @click="navigator.clipboard.writeText('{{ route('shop.index', $institut->slug) }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                class="text-xs px-2 py-1 rounded-lg bg-amber-200 dark:bg-amber-700/50 text-amber-700 dark:text-amber-200 hover:bg-amber-300 dark:hover:bg-amber-600/50 transition flex-shrink-0">
                            <span x-show="!copied">📋 Copier</span>
                            <span x-show="copied" x-cloak>✓ Copié</span>
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Abonnements --}}
        <div class="space-y-5 min-w-0">
            <div class="card p-5">
                <h2 class="font-bold text-sm text-gray-700 mb-3">Abonnement actuel</h2>

                @if(!$owner)
                    <p class="text-sm text-gray-400 italic">Aucun compte propriétaire (admin) lié à cet établissement.</p>

                @elseif($abonnementActif)
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <p class="font-bold text-primary-600 text-lg">{{ $abonnementActif->plan->nom }}</p>
                        <span class="badge badge-success text-xs flex-shrink-0">Actif</span>
                    </div>
                    <p class="text-xs text-gray-500">Expire le <strong>{{ $abonnementActif->expire_le?->format('d/m/Y') ?? '—' }}</strong></p>
                    <p class="text-xs text-gray-400 mt-2 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Propriétaire&nbsp;: <strong>{{ $owner->nom_complet }}</strong>
                    </p>

                @elseif($abonnementSursis)
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <p class="font-bold text-amber-600 text-lg">{{ $abonnementSursis->plan->nom }}</p>
                        <span class="badge bg-amber-100 text-amber-700 text-xs flex-shrink-0">Sursis</span>
                    </div>
                    <p class="text-xs text-amber-700">
                        Expiré depuis {{ $abonnementSursis->joursDepuisExpiration() }} jour(s)
                        ({{ $abonnementSursis->expire_le?->format('d/m/Y') }}).
                    </p>
                    <p class="text-xs text-gray-500 mt-1">Période de grâce de 2 jours — accès restreint en écriture.</p>
                    <p class="text-xs text-gray-400 mt-2 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Propriétaire&nbsp;: <strong>{{ $owner->nom_complet }}</strong>
                    </p>

                @else
                    <p class="text-sm text-gray-400">Aucun abonnement actif.</p>
                    <p class="text-xs text-gray-400 mt-2 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Propriétaire&nbsp;: <strong>{{ $owner->nom_complet }}</strong>
                    </p>
                @endif
            </div>

            <div class="card p-5">
                <h2 class="font-bold text-sm text-gray-700 mb-3">Attribuer un abonnement</h2>
                <form action="{{ route('admin.instituts.offrir', $institut) }}" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label class="form-label">Plan</label>
                        <select name="plan_id" class="form-input" required>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->nom }} — {{ number_format($plan->prix, 0, ',', ' ') }} FCFA/mois</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Durée (jours)</label>
                        <input type="number" name="jours" class="form-input" value="30" min="1" max="1095" required>
                    </div>
                    <button class="btn-primary w-full">Attribuer</button>
                </form>
            </div>

            <div class="card overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 text-sm font-medium">Historique</div>
                <div class="divide-y divide-gray-50">
                    @forelse($historique as $ab)
                    <div class="px-4 py-3 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="font-medium">{{ $ab->plan->nom ?? '—' }}</span>
                            @php
                                $c = ['en_attente' => 'bg-amber-100 text-amber-700', 'actif' => 'badge-success', 'expire' => 'bg-red-100 text-red-700', 'rejete' => 'bg-gray-100 text-gray-500'];
                            @endphp
                            <span class="badge {{ $c[$ab->statut] ?? 'bg-gray-100 text-gray-500' }} text-xs">{{ $ab->statut }}</span>
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $ab->debut_le?->format('d/m/Y') ?? '—' }} → {{ $ab->expire_le?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                    @empty
                    <p class="px-4 py-4 text-sm text-gray-400 text-center">Aucun historique.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Zone de danger --}}
    <div class="card border border-red-200 p-6" x-data="{ confirmer: false }">
        <h2 class="font-bold text-red-700 text-sm mb-1">Zone de danger</h2>
        <p class="text-sm text-gray-500 mb-4">La suppression est <strong>irréversible</strong>. Elle efface l'établissement, tous ses utilisateurs, clients, ventes et abonnements.</p>

        <div x-show="!confirmer">
            <button @click="confirmer = true" type="button"
                class="px-4 py-2 rounded-lg text-sm font-medium bg-red-600 text-white hover:bg-red-700 transition">
                Supprimer définitivement cet établissement
            </button>
        </div>

        <div x-show="confirmer" class="space-y-3">
            <p class="text-sm font-semibold text-red-700">
                Confirmez-vous la suppression de <em>{{ $institut->nom }}</em> et de <strong>toutes ses données</strong> ?
            </p>
            <div class="flex gap-3">
                <form action="{{ route('admin.instituts.destroy', $institut) }}" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit"
                        class="px-4 py-2 rounded-lg text-sm font-medium bg-red-600 text-white hover:bg-red-700 transition">
                        Oui, supprimer définitivement
                    </button>
                </form>
                <button @click="confirmer = false" type="button"
                    class="px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
                    Annuler
                </button>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('statsFilter', () => ({
            periode: 'mois'
        }));
    });
</script>
@endpush
@endsection
