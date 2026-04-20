@extends('layouts.admin')
@section('page-title', 'Abonnement')

@section('content')
<div class="space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ route('admin.abonnements.index') }}" class="text-gray-400 hover:text-gray-700 text-sm">← Abonnements</a>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">

        {{-- Détails abonnement --}}
        <div class="card p-6 space-y-4">
            <h2 class="font-bold text-lg text-gray-900">Détails de l'abonnement</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-400">Plan</span>
                    <p class="font-medium">{{ $abonnement->plan->nom ?? '—' }}</p>
                </div>
                <div>
                    <span class="text-gray-400">Statut</span>
                    <p>
                        @php
                            $colors = ['en_attente' => 'bg-amber-100 text-amber-700', 'actif' => 'badge-success', 'expire' => 'bg-red-100 text-red-700', 'rejete' => 'bg-gray-100 text-gray-500'];
                        @endphp
                        <span class="badge {{ $colors[$abonnement->statut] ?? 'bg-gray-100 text-gray-500' }} text-xs capitalize">
                            {{ $abonnement->statut === 'en_attente' ? 'En attente' : ucfirst($abonnement->statut) }}
                        </span>
                    </p>
                </div>
                <div>
                    <span class="text-gray-400">Montant</span>
                    <p class="font-medium">
                        {{ number_format($abonnement->montant, 0, ',', ' ') }} FCFA
                        @if($abonnement->plan && $abonnement->montant < $abonnement->plan->prix)
                            <span class="inline-flex items-center gap-1 ml-1 px-2 py-0.5 rounded-full bg-gradient-to-r from-amber-400 to-orange-500 text-white text-[10px] font-bold uppercase">🔥 Offre lancement</span>
                            <span class="block text-xs text-gray-400 mt-0.5">au lieu de {{ number_format($abonnement->plan->prix, 0, ',', ' ') }} FCFA</span>
                        @endif
                    </p>
                </div>
                <div>
                    <span class="text-gray-400">Période</span>
                    <p class="font-medium capitalize">{{ $abonnement->periode }}</p>
                </div>
                <div>
                    <span class="text-gray-400">Début</span>
                    <p class="font-medium">{{ $abonnement->debut_le?->format('d/m/Y') ?? '—' }}</p>
                </div>
                <div>
                    <span class="text-gray-400">Expiration</span>
                    <p class="font-medium">{{ $abonnement->expire_le?->format('d/m/Y') ?? '—' }}</p>
                </div>
                <div>
                    <span class="text-gray-400">Référence transfert</span>
                    <p class="font-medium text-xs font-mono">{{ $abonnement->reference_transfert ?? '—' }}</p>
                </div>
                <div>
                    <span class="text-gray-400">Créé le</span>
                    <p class="font-medium">{{ $abonnement->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>

            @if($abonnement->notes_admin)
            <div class="bg-gray-50 rounded-xl p-3 text-sm">
                <span class="text-gray-400 text-xs">Notes admin :</span>
                <p class="text-gray-700 mt-1">{{ $abonnement->notes_admin }}</p>
            </div>
            @endif

            @if($abonnement->validePar)
            <p class="text-xs text-gray-400">Validé par {{ $abonnement->validePar->nom_complet ?? $abonnement->validePar->name }}</p>
            @endif
        </div>

        {{-- Utilisateur + Institut --}}
        <div class="space-y-6">
            <div class="card p-6 space-y-4">
                <h2 class="font-bold text-lg text-gray-900">Propriétaire</h2>
                @if($abonnement->user)
                    <div class="text-sm space-y-2">
                        <p class="font-medium text-lg">{{ $abonnement->user->nom_complet ?? $abonnement->user->name }}</p>
                        <p class="text-gray-500">{{ $abonnement->user->email }}</p>
                        <p class="text-gray-500">{{ $abonnement->user->telephone ?? '' }}</p>
                        @if($abonnement->user->institut)
                        <p class="text-gray-500">Institut : <strong>{{ $abonnement->user->institut->nom }}</strong> ({{ $abonnement->user->institut->ville ?? '' }})</p>
                        @endif
                    </div>
                @else
                    <p class="text-sm text-gray-400">Utilisateur non trouvé.</p>
                @endif
            </div>

            {{-- Établissements liés --}}
            @if($abonnement->user && $abonnement->user->mesInstituts->count())
            <div class="card p-6 space-y-3">
                <h2 class="font-bold text-lg text-gray-900">Établissements couverts</h2>
                <ul class="divide-y divide-gray-100">
                    @foreach($abonnement->user->mesInstituts as $inst)
                    <li class="py-2.5 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-primary-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $inst->nom }}</p>
                                <p class="text-xs text-gray-400">{{ $inst->ville ?? '' }}@if($inst->ville && $inst->quartier), @endif{{ $inst->quartier ?? '' }}</p>
                            </div>
                        </div>
                        <a href="{{ route('admin.instituts.show', $inst) }}" class="btn-outline btn-sm inline-flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            Voir
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Preuve de paiement --}}
            @if($abonnement->preuve_paiement)
            <div class="card p-6 space-y-3">
                <h2 class="font-bold text-lg text-gray-900">Preuve de paiement</h2>
                <a href="{{ asset('storage/' . $abonnement->preuve_paiement) }}" target="_blank" class="block">
                    <img src="{{ asset('storage/' . $abonnement->preuve_paiement) }}" alt="Preuve" class="rounded-xl border border-gray-200 max-h-64 object-contain w-full">
                </a>
            </div>
            @endif
        </div>
    </div>

    {{-- Parrainage --}}
    @if(isset($parrainage) && $parrainage)
    <div class="card p-6 space-y-4">
        <h2 class="font-bold text-lg text-gray-900 flex items-center gap-2">
            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            Parrainage
        </h2>

        <div class="bg-purple-50 rounded-xl p-4 space-y-3">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold uppercase
                    {{ $parrainage->statut === 'valide' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                    {{ $parrainage->statut === 'valide' ? '✅ Validé' : '⏳ En attente' }}
                </span>
            </div>

            <div class="grid sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">Parrainé par</span>
                    <p class="font-medium text-gray-900">{{ $parrainage->parrain->nom_complet ?? $parrainage->parrain->name ?? '—' }}</p>
                    <p class="text-xs text-gray-400">{{ $parrainage->parrain->email ?? '' }}</p>
                    @if($parrainage->parrain->institut)
                    <p class="text-xs text-gray-400">{{ $parrainage->parrain->institut->nom }}</p>
                    @endif
                </div>
                <div>
                    <span class="text-gray-500">Bonus à appliquer</span>
                    <div class="mt-1 space-y-1">
                        <p class="text-sm">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-purple-100 text-purple-700 text-xs font-bold">🎁 Filleul</span>
                            <strong>+{{ $parrainage->jours_offerts_filleul }} jours</strong> pour {{ $abonnement->user->nom_complet ?? $abonnement->user->name }}
                        </p>
                        <p class="text-sm">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs font-bold">👑 Parrain</span>
                            <strong>+{{ $parrainage->jours_offerts_parrain }} jours</strong> pour {{ $parrainage->parrain->nom_complet ?? $parrainage->parrain->name }}
                        </p>
                    </div>
                </div>
            </div>

            @if($parrainage->statut === 'en_attente')
            <p class="text-xs text-purple-700 bg-purple-100 rounded-lg p-2">
                <strong>ℹ️ Note :</strong> En validant cet abonnement, le bonus sera automatiquement appliqué aux deux parties.
            </p>
            @endif
        </div>
    </div>
    @endif

    {{-- Actions --}}
    @if($abonnement->statut === 'en_attente')
    <div class="card p-6">
        <h2 class="font-bold text-lg text-gray-900 mb-4">Actions</h2>
        <div class="flex flex-wrap gap-4">
            <form id="form-valider-{{ $abonnement->id }}" action="{{ route('admin.abonnements.valider', $abonnement) }}" method="POST">
                @csrf @method('PATCH')
                <button type="button" class="btn-primary"
                        onclick="window.dispatchEvent(new CustomEvent('confirm-action',{detail:{formId:'form-valider-{{ $abonnement->id }}',title:'Valider cet abonnement',message:'L\'abonnement sera activé et le client notifié.',confirmLabel:'Valider',confirmClass:'!bg-emerald-600 hover:!bg-emerald-700',danger:false}}))">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Valider l'abonnement
                </button>
            </form>

            <form id="form-rejeter-{{ $abonnement->id }}" action="{{ route('admin.abonnements.rejeter', $abonnement) }}" method="POST" x-data="{ showReason: false }">
                @csrf @method('PATCH')
                <div class="flex items-center gap-3">
                    <button type="button" @click="showReason = !showReason" class="btn-danger">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Rejeter
                    </button>
                    <div x-show="showReason" x-cloak class="flex items-center gap-2">
                        <input type="text" name="notes_admin" placeholder="Raison du rejet (optionnel)" class="form-input max-w-xs">
                        <button type="button" class="btn-danger text-sm"
                                onclick="window.dispatchEvent(new CustomEvent('confirm-action',{detail:{formId:'form-rejeter-{{ $abonnement->id }}',title:'Rejeter cet abonnement',message:'L\'abonnement sera rejeté et le client notifié.',confirmLabel:'Rejeter',confirmClass:'!bg-red-600 hover:!bg-red-700',danger:true}}))">Confirmer</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Prolonger essai --}}
    @if($abonnement->plan && $abonnement->plan->slug === 'essai')
    <div class="card p-6">
        <h2 class="font-bold text-lg text-gray-900 mb-4">Prolonger l'essai</h2>
        <form action="{{ route('admin.abonnements.prolonger', $abonnement) }}" method="POST" class="flex items-end gap-4">
            @csrf @method('PATCH')
            <div>
                <label class="form-label">Jours supplémentaires</label>
                <input type="number" name="jours" class="form-input w-32" value="14" min="1" max="90">
            </div>
            <button class="btn-primary">Prolonger</button>
        </form>
    </div>
    @endif
</div>
@endsection
