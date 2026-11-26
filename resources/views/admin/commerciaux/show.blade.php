@extends('layouts.admin')
@section('title', $commercial->nom_complet . ' — Commercial')

@section('content')
<div class="space-y-5">

    {{-- ═══ Fil d'Ariane ═══ --}}
    <nav class="flex items-center gap-2 text-sm text-gray-400 min-w-0">
        <a href="{{ route('admin.commerciaux.index') }}" class="hover:text-primary-600 transition-colors whitespace-nowrap">Commerciaux</a>
        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-700 font-medium truncate">{{ $commercial->nom_complet }}</span>
    </nav>

    {{-- ═══ En-tête ═══ --}}
    <div class="card overflow-hidden">
        <div class="relative px-5 sm:px-6 pt-5 pb-5" style="background: linear-gradient(135deg, #9333ea 0%, #ec4899 100%);">
            <div class="flex flex-wrap items-start justify-between gap-3 pl-[76px] sm:pl-20 min-h-[48px]">
                <div class="min-w-0">
                    <h1 class="text-xl sm:text-2xl font-bold text-white leading-tight truncate">{{ $commercial->nom_complet }}</h1>
                    <p class="text-sm text-white/85 mt-0.5 truncate">{{ $commercial->email }}</p>
                </div>
                <div class="flex-shrink-0">
                    @if($commercial->actif)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-white/20 text-white backdrop-blur">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            Actif
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-white" style="background: rgba(0,0,0,0.25);">
                            Désactivé
                        </span>
                    @endif
                </div>
            </div>

            <div class="absolute left-5 sm:left-6 -bottom-8 w-16 h-16 rounded-2xl flex items-center justify-center text-white text-lg font-bold shadow-lg ring-4 ring-white"
                 style="background: linear-gradient(135deg, #7e22ce, #db2777);">
                {{ Str::upper(Str::substr($commercial->prenom ?? '?', 0, 1)) }}{{ Str::upper(Str::substr($commercial->nom_famille ?? '', 0, 1)) }}
            </div>
        </div>

        <div class="pl-[96px] sm:pl-24 pr-5 sm:pr-6 pt-3 pb-4 flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-[11px] uppercase tracking-wider text-gray-400 font-semibold">Code</span>
                <span class="font-mono font-bold text-primary-600 text-sm">{{ $profil->code }}</span>
                @if($profil->telephone)
                    <span class="text-gray-300">·</span>
                    <span class="text-sm text-gray-600">{{ $profil->telephone }}</span>
                @endif
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <form method="POST" action="{{ route('admin.commerciaux.toggle', $commercial) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn-secondary text-sm py-2 px-3">
                        {{ $commercial->actif ? 'Désactiver' : 'Réactiver' }}
                    </button>
                </form>

                <form method="POST" action="{{ route('admin.commerciaux.destroy', $commercial) }}"
                      id="form-del-commercial-{{ $commercial->id }}">
                    @csrf @method('DELETE')
                    <button type="button"
                            onclick="window.dispatchEvent(new CustomEvent('confirm-action',{detail:{formId:'form-del-commercial-{{ $commercial->id }}',title:'Supprimer ce commercial ?',message:'L\'historique des commissions sera perdu. Préférez la désactivation, qui coupe l\'accès sans rien effacer.',confirmLabel:'Supprimer',confirmClass:'!bg-red-600 hover:!bg-red-700',danger:true}}))"
                            class="px-3 py-2 rounded-xl border border-red-200 text-red-600 hover:bg-red-50 text-sm font-semibold transition-colors">
                        Supprimer
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ═══ Indicateurs ═══ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="card p-4 sm:p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">Parrainages</p>
            <p class="text-2xl font-bold text-gray-900 mt-1 leading-tight">{{ $totaux['parrainages'] }}</p>
            <p class="text-[11px] text-gray-400 mt-1">{{ $totaux['parrainages_actifs'] }} en période de commission</p>
        </div>
        <div class="card p-4 sm:p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">Commissions versées</p>
            <p class="text-xl sm:text-2xl font-bold text-emerald-600 mt-1 leading-tight">{{ number_format($totaux['payees'], 0, ',', ' ') }}</p>
            <p class="text-[11px] text-gray-400 mt-1">FCFA payés</p>
        </div>
        <div class="card p-4 sm:p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">En attente</p>
            <p class="text-xl sm:text-2xl font-bold {{ $totaux['en_attente'] > 0 ? 'text-amber-600' : 'text-gray-900' }} mt-1 leading-tight">{{ number_format($totaux['en_attente'], 0, ',', ' ') }}</p>
            <p class="text-[11px] text-gray-400 mt-1">{{ \App\Helpers\TexteHelper::pluriel($totaux['commissions'], 'commission au total', 'commissions au total') }}</p>
        </div>
        <div class="card p-4 sm:p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">Reste à verser</p>
            <p class="text-xl sm:text-2xl font-bold text-gray-900 mt-1 leading-tight">{{ number_format($totaux['payees'] + $totaux['en_attente'], 0, ',', ' ') }}</p>
            <p class="text-[11px] text-gray-400 mt-1">FCFA cumulés</p>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-5">

        {{-- ═══ Colonne principale : commissions ═══ --}}
        <div class="lg:col-span-2 space-y-5">
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold text-gray-900">Commissions</h2>
                    <span class="text-xs text-gray-400">
                        {{ $totaux['commissions'] > $commissions->count() ? 'Les ' . $commissions->count() . ' plus récentes' : \App\Helpers\TexteHelper::pluriel($totaux['commissions'], 'commission', 'commissions') }}
                    </span>
                </div>

                @forelse($commissions as $c)
                <div class="px-5 py-3.5 border-b border-gray-100 last:border-0 hover:bg-gray-50/60 transition-colors">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">
                                {{ $c->parrainage?->institut?->nom ?? $c->parrainage?->proprietaire?->institut?->nom ?? '—' }}
                            </p>
                            <p class="text-xs text-gray-400">
                                {{ $c->abonnement?->plan?->nom ?? '—' }}
                                · base {{ number_format($c->montant_base, 0, ',', ' ') }} FCFA × {{ $c->taux }} %
                                · {{ $c->created_at->format('d/m/Y') }}
                            </p>
                            @if($c->estPayee() && $c->payee_le)
                                <p class="text-[11px] text-emerald-700 mt-0.5">
                                    Payée le {{ $c->payee_le->format('d/m/Y') }}
                                    @if($c->payePar) par {{ $c->payePar->nom_complet }} @endif
                                </p>
                            @endif
                            @if($c->notes_paiement)
                                <p class="text-[11px] text-gray-400 italic mt-0.5">{{ $c->notes_paiement }}</p>
                            @endif
                        </div>

                        <div class="text-right flex-shrink-0">
                            <p class="font-bold text-sm {{ $c->estPayee() ? 'text-emerald-600' : 'text-amber-600' }}">
                                {{ $c->montant_formatte }}
                            </p>
                            <span class="badge {{ $c->estPayee() ? 'badge-success' : 'badge-warning' }} text-[10px]">
                                {{ $c->estPayee() ? 'Payée' : 'En attente' }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 mt-2">
                        @if($c->estPayee())
                            <form method="POST" action="{{ route('admin.commerciaux.commissions.annuler', $c) }}" class="flex items-center gap-1.5">
                                @csrf @method('PATCH')
                                <input type="text" name="motif" maxlength="255" placeholder="Motif (optionnel)"
                                       class="form-input text-xs py-1 max-w-[180px]">
                                <button type="submit" class="px-2 py-1 rounded-lg border border-gray-200 text-gray-500 bg-gray-50 hover:bg-gray-100 text-[11px] font-semibold transition-colors whitespace-nowrap">
                                    Annuler le paiement
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.commerciaux.commissions.payer', $c) }}" class="flex items-center gap-1.5">
                                @csrf @method('PATCH')
                                <input type="text" name="note" maxlength="255" placeholder="Référence du versement (optionnel)"
                                       class="form-input text-xs py-1 max-w-[210px]">
                                <button type="submit" class="px-2 py-1 rounded-lg border border-emerald-200 text-emerald-700 bg-emerald-50 hover:bg-emerald-100 text-[11px] font-semibold transition-colors whitespace-nowrap">
                                    Marquer payée
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                @empty
                <div class="px-5 py-10 text-center">
                    <p class="text-sm text-gray-400">Aucune commission pour l'instant.</p>
                    <p class="text-xs text-gray-400 mt-1">Une commission est générée à chaque abonnement payant d'un client rattaché.</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- ═══ Colonne latérale ═══ --}}
        <div class="space-y-5">

            {{-- Attribution rapide --}}
            <div class="card">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Rattacher un établissement</h2>
                    <p class="text-[11px] text-gray-400 mt-0.5">Le compte client entier rejoint ce commercial.</p>
                </div>
                <div class="p-5">
                    @if($commercial->actif)
                    <form method="POST" action="{{ route('admin.commerciaux.attribuer', $commercial) }}" class="space-y-3">
                        @csrf
                        <select name="institut_id" required class="form-input text-sm">
                            <option value="">Choisir un établissement…</option>
                            @foreach($etablissementsDisponibles as $e)
                                <option value="{{ $e->id }}">
                                    {{ $e->nom }}@if($e->ville) — {{ $e->ville }}@endif
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn-primary w-full text-sm py-2">Rattacher</button>
                    </form>
                    @else
                        <p class="text-xs text-gray-500">
                            Réactivez le commercial pour lui attribuer un établissement.
                        </p>
                    @endif
                </div>
            </div>

            {{-- Parrainages --}}
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between gap-2">
                    <h2 class="text-sm font-semibold text-gray-900">Parrainages</h2>
                    <span class="text-xs text-gray-400">{{ $totaux['parrainages'] }}</span>
                </div>
                @forelse($parrainages as $p)
                <div class="px-5 py-3 border-b border-gray-100 last:border-0">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $p->proprietaire?->nom_complet ?? '—' }}</p>
                            <p class="text-[11px] text-gray-400 truncate">{{ $p->institut?->nom ?? $p->proprietaire?->institut?->nom ?? '—' }}</p>
                            @if($p->estManuel())
                                <p class="text-[10px] text-purple-600 mt-0.5"
                                   title="Attribué le {{ $p->created_at?->format('d/m/Y') }} par {{ $p->attribuePar?->nom_complet ?? 'un super admin' }}">
                                    Attribution manuelle
                                </p>
                            @endif
                        </div>
                        <div class="text-right flex-shrink-0">
                            <span class="badge {{ $p->isActif() ? 'badge-success' : 'badge-gray' }} text-[10px]">
                                {{ $p->isActif() ? 'Actif' : 'Expiré' }}
                            </span>
                            <p class="text-[10px] text-gray-400 mt-0.5">{{ $p->expire_le?->format('d/m/Y') }}</p>
                        </div>
                    </div>
                    @if((int) $p->commissions_total > 0)
                        <p class="text-[11px] text-gray-500 mt-1">{{ number_format((int) $p->commissions_total, 0, ',', ' ') }} FCFA générés</p>
                    @endif
                </div>
                @empty
                <p class="px-5 py-6 text-center text-sm text-gray-400">Aucun parrainage.</p>
                @endforelse

                @if($totaux['parrainages'] > $parrainages->count())
                    <p class="px-5 py-2 text-[11px] text-gray-400 border-t border-gray-100 bg-gray-50">
                        + {{ $totaux['parrainages'] - $parrainages->count() }} autre(s) non affiché(s)
                    </p>
                @endif
            </div>

            {{-- Notes internes --}}
            @if($profil->notes)
            <div class="card p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-2">Notes internes</h2>
                <p class="text-sm text-gray-600 whitespace-pre-line">{{ $profil->notes }}</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
