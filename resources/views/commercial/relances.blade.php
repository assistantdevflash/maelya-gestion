@extends('layouts.commercial')
@section('title', 'Relances')

@section('content')
@php
    $niveaux = [
        'expire'      => ['label' => 'Expiré',            'classe' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300'],
        'critique'    => ['label' => 'Sous 3 jours',      'classe' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'],
        'urgent'      => ['label' => 'Sous 7 jours',      'classe' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'],
        'proche'      => ['label' => 'Sous 15 jours',     'classe' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'],
        'a_surveiller'=> ['label' => 'À surveiller',      'classe' => 'bg-slate-100 text-slate-600 dark:bg-slate-700/50 dark:text-slate-300'],
    ];
@endphp

<div class="mb-5">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Relances</h1>
    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
        Vos prospects à recontacter et les abonnements qui arrivent à échéance.
    </p>
</div>

@if(session('success'))
<div class="mb-4 flex items-start gap-2.5 px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-sm text-emerald-700 dark:text-emerald-300">
    <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    {{ session('success') }}
</div>
@endif

{{-- ═══════════════ Bandeau de synthèse ═══════════════ --}}
<div class="rounded-2xl p-5 mb-5 border border-purple-200 dark:border-purple-800"
     style="background: linear-gradient(135deg, rgba(147,51,234,0.06), rgba(236,72,153,0.04));">
    <div class="grid grid-cols-3 gap-4 text-center">
        <div>
            <p class="text-2xl font-extrabold {{ $stats['relances_retard'] > 0 ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">
                {{ $stats['relances_retard'] }}
            </p>
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mt-0.5">En retard</p>
        </div>
        <div>
            <p class="text-2xl font-extrabold text-amber-600">{{ $relancesDues->count() - $stats['relances_retard'] }}</p>
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mt-0.5">Aujourd'hui</p>
        </div>
        <div>
            <p class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ $echeances->count() }}</p>
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mt-0.5">Abonnements à suivre</p>
        </div>
    </div>
</div>

{{-- ═══════════════ 1. Relances à faire ═══════════════ --}}
<h2 class="text-sm font-bold text-gray-900 dark:text-white mb-2.5 flex items-center gap-2">
    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
    À relancer maintenant
    <span class="text-xs font-normal text-gray-400">({{ $relancesDues->count() }})</span>
</h2>

@if($relancesDues->isEmpty())
    <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 p-6 text-center mb-6">
        <p class="text-sm text-gray-500 dark:text-gray-400">Aucune relance en attente. Votre pipeline est à jour 🎉</p>
    </div>
@else
    <div class="space-y-2.5 mb-6">
        @foreach($relancesDues as $prospect)
            @php $jours = $prospect->joursAvantRelance(); @endphp
            <div class="rounded-2xl bg-white dark:bg-gray-800 border p-4
                        {{ $jours !== null && $jours < 0 ? 'border-red-200 dark:border-red-800' : 'border-amber-200 dark:border-amber-800' }}">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 text-white text-xs font-bold"
                         style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                        {{ mb_strtoupper(mb_substr($prospect->nom_etablissement, 0, 1)) }}
                    </div>

                    <div class="flex-1 min-w-[160px]">
                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $prospect->nom_etablissement }}</p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            {{ $prospect->libelleStatut() }}
                            @if($prospect->contact_nom) · {{ $prospect->contact_nom }} @endif
                            @if($prospect->contact_telephone) · {{ $prospect->contact_telephone }} @endif
                        </p>
                    </div>

                    <span class="px-2 py-1 rounded-lg text-[10px] font-bold
                        {{ $jours !== null && $jours < 0 ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' }}">
                        @if($jours !== null && $jours < 0)
                            {{ abs($jours) }} j de retard
                        @else
                            Aujourd'hui
                        @endif
                    </span>

                    <div class="flex items-center gap-1.5 ml-auto">
                        @if($prospect->contact_telephone)
                        <a href="tel:{{ $prospect->contact_telephone }}"
                           class="px-2.5 py-1.5 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-[11px] font-semibold">Appeler</a>
                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $prospect->contact_telephone) }}" target="_blank" rel="noopener"
                           class="px-2.5 py-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 text-[11px] font-semibold">WhatsApp</a>
                        @endif

                        {{-- Enregistrer l'appel + replanifier --}}
                        <form method="POST" action="{{ route('commercial.prospection.activite', $prospect) }}" class="flex items-center gap-1.5">
                            @csrf
                            <input type="hidden" name="type" value="relance">
                            <input type="hidden" name="contenu" value="Relance effectuée">
                            <input type="hidden" name="prochaine_relance_le" value="{{ today()->addDays(7)->format('Y-m-d') }}">
                            <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-[11px] font-semibold">
                                Fait (+7 j)
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- ═══════════════ 2. Abonnements à échéance ═══════════════ --}}
<h2 class="text-sm font-bold text-gray-900 dark:text-white mb-2.5 flex items-center gap-2">
    <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
    Abonnements à renouveler
    <span class="text-xs font-normal text-gray-400">({{ $echeances->count() }})</span>
</h2>
<p class="text-[11px] text-gray-500 dark:text-gray-400 mb-2.5 -mt-1">
    Vos filleuls dont l'abonnement expire bientôt : un client qui renouvelle, c'est une commission de plus.
</p>

@if($echeances->isEmpty())
    <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 p-6 text-center mb-6">
        <p class="text-sm text-gray-500 dark:text-gray-400">Aucun abonnement n'arrive à échéance dans les 45 prochains jours.</p>
    </div>
@else
    <div class="space-y-2.5 mb-6">
        @foreach($echeances as $ligne)
            @php
                $abo = $ligne['abonnement'];
                $niveau = $niveaux[$ligne['niveau']] ?? $niveaux['a_surveiller'];
                $tel = $abo->user?->institut?->telephone ?? $abo->user?->telephone;
            @endphp
            <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 p-4">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>

                    <div class="flex-1 min-w-[160px]">
                        <p class="text-sm font-bold text-gray-900 dark:text-white">
                            {{ $abo->user?->institut?->nom ?? $abo->user?->nom_complet ?? 'Établissement' }}
                        </p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            {{ $abo->plan?->nom ?? 'Abonnement' }}
                            · échéance {{ $abo->expire_le?->format('d/m/Y') }}
                            @if($tel) · {{ $tel }} @endif
                        </p>
                    </div>

                    <span class="px-2 py-1 rounded-lg text-[10px] font-bold {{ $niveau['classe'] }}">
                        @if($ligne['jours'] < 0)
                            {{ $niveau['label'] }} depuis {{ abs($ligne['jours']) }} j
                        @elseif($ligne['jours'] === 0)
                            Expire aujourd'hui
                        @else
                            {{ $niveau['label'] }} — {{ $ligne['jours'] }} j
                        @endif
                    </span>

                    <div class="flex items-center gap-1.5 ml-auto">
                        @if($tel)
                        <a href="tel:{{ $tel }}"
                           class="px-2.5 py-1.5 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-[11px] font-semibold">Appeler</a>
                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $tel) }}" target="_blank" rel="noopener"
                           class="px-2.5 py-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 text-[11px] font-semibold">WhatsApp</a>
                        @endif
                        <a href="{{ route('commercial.parrainages') }}"
                           class="px-2.5 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-[11px] font-semibold">Voir</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- ═══════════════ 3. Cette semaine ═══════════════ --}}
@if($relancesAVenir->isNotEmpty())
<h2 class="text-sm font-bold text-gray-900 dark:text-white mb-2.5 flex items-center gap-2">
    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
    Cette semaine
    <span class="text-xs font-normal text-gray-400">({{ $relancesAVenir->count() }})</span>
</h2>
<div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700 mb-6">
    @foreach($relancesAVenir as $prospect)
    <div class="px-4 py-2.5 flex items-center gap-3">
        <span class="text-[11px] font-semibold text-gray-400 whitespace-nowrap w-14">
            {{ $prospect->prochaine_relance_le->format('d/m') }}
        </span>
        <span class="text-sm text-gray-800 dark:text-gray-200 flex-1 truncate">{{ $prospect->nom_etablissement }}</span>
        <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-300 font-semibold">
            {{ $prospect->libelleStatut() }}
        </span>
    </div>
    @endforeach
</div>
@endif

{{-- ═══════════════ 4. Sans relance planifiée ═══════════════ --}}
@if($sansRelance->isNotEmpty())
<h2 class="text-sm font-bold text-gray-900 dark:text-white mb-2.5 flex items-center gap-2">
    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
    Sans relance planifiée
    <span class="text-xs font-normal text-gray-400">({{ $sansRelance->count() }})</span>
</h2>
<p class="text-[11px] text-gray-500 dark:text-gray-400 mb-2.5 -mt-1">
    Ces prospects risquent de sortir de votre radar : fixez une date.
</p>
<div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700">
    @foreach($sansRelance as $prospect)
    <div class="px-4 py-3 flex flex-wrap items-center gap-3">
        <span class="text-sm text-gray-800 dark:text-gray-200 flex-1 truncate">{{ $prospect->nom_etablissement }}</span>
        <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-300 font-semibold">
            {{ $prospect->libelleStatut() }}
        </span>
        <form method="POST" action="{{ route('commercial.prospection.relance', $prospect) }}" class="flex items-center gap-1.5">
            @csrf @method('PATCH')
            <input type="date" name="prochaine_relance_le" value="{{ today()->addDays(3)->format('Y-m-d') }}"
                   class="form-input text-xs py-1 px-2 w-36">
            <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-[11px] font-semibold whitespace-nowrap">
                Planifier
            </button>
        </form>
    </div>
    @endforeach
</div>
@endif
@endsection
