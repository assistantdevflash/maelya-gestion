@extends('layouts.commercial')
@section('title', 'Prospection')

@php
    use App\Models\CommercialProspect as P;

    $couleursStatut = [
        'a_contacter' => 'bg-slate-100 text-slate-700 dark:bg-slate-700/50 dark:text-slate-300',
        'contacte'    => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
        'interesse'   => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        'essai'       => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
        'converti'    => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
        'perdu'       => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
    ];
@endphp

@section('content')
<div x-data="{ nouveau: false }">

    {{-- ═══════════════ En-tête ═══════════════ --}}
    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Prospection</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                Votre pipeline : {{ $stats['en_cours'] }} prospect{{ $stats['en_cours'] > 1 ? 's' : '' }} en cours
            </p>
        </div>
        <button @click="nouveau = true"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-white text-sm font-semibold shadow-sm transition-all hover:shadow-md active:scale-[0.98]"
                style="background: linear-gradient(135deg, #9333ea, #ec4899);">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Nouveau prospect
        </button>
    </div>

    @if(session('success'))
    <div class="mb-4 flex items-start gap-2.5 px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-sm text-emerald-700 dark:text-emerald-300">
        <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- ═══════════════ Indicateurs ═══════════════ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <a href="{{ route('commercial.prospection', ['statut' => 'actifs']) }}"
           class="rounded-2xl p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 hover:border-purple-300 dark:hover:border-purple-700 transition-colors">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">En cours</p>
            <p class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">{{ $stats['en_cours'] }}</p>
            <p class="text-[11px] text-gray-400 mt-0.5">+{{ $stats['nouveaux_30j'] }} ce mois</p>
        </a>
        <a href="{{ route('commercial.relances') }}"
           class="rounded-2xl p-4 border transition-colors
                  {{ $stats['relances_dues'] > 0 ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800' : 'bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700' }}">
            <p class="text-xs font-semibold uppercase tracking-wider {{ $stats['relances_dues'] > 0 ? 'text-red-500' : 'text-gray-400' }}">À relancer</p>
            <p class="text-2xl font-extrabold mt-1 {{ $stats['relances_dues'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                {{ $stats['relances_dues'] }}
            </p>
            <p class="text-[11px] {{ $stats['relances_retard'] > 0 ? 'text-red-500' : 'text-gray-400' }} mt-0.5">
                {{ $stats['relances_retard'] }} en retard
            </p>
        </a>
        <div class="rounded-2xl p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Convertis</p>
            <p class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1">{{ $stats['convertis'] }}</p>
            <p class="text-[11px] text-gray-400 mt-0.5">+{{ $stats['convertis_30j'] }} ce mois</p>
        </div>
        <div class="rounded-2xl p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Conversion</p>
            <p class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">{{ $stats['taux_conversion'] }}%</p>
            <p class="text-[11px] text-gray-400 mt-0.5">{{ $stats['perdus'] }} perdu{{ $stats['perdus'] > 1 ? 's' : '' }}</p>
        </div>
    </div>

    {{-- ═══════════════ Filtres ═══════════════ --}}
    <form method="GET" action="{{ route('commercial.prospection') }}" class="mb-4">
        <div class="flex flex-col sm:flex-row gap-2">
            <div class="relative flex-1">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="q" value="{{ $recherche }}"
                       placeholder="Établissement, contact, téléphone, ville…"
                       class="form-input pl-10 text-sm">
            </div>
            @if($statut)
            <input type="hidden" name="statut" value="{{ $statut }}">
            @endif
            <button type="submit" class="btn-primary text-sm px-4">Rechercher</button>
        </div>

        <div class="flex gap-1.5 mt-3 overflow-x-auto scrollbar-hide pb-1">
            <a href="{{ route('commercial.prospection') }}"
               class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors
                      {{ ! $statut ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                Tous ({{ $stats['total'] }})
            </a>
            <a href="{{ route('commercial.prospection', ['statut' => 'actifs']) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors
                      {{ $statut === 'actifs' ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                Actifs ({{ $stats['en_cours'] }})
            </a>
            @foreach(P::STATUTS as $code => $libelle)
                @if(! in_array($code, ['converti', 'perdu']))
                <a href="{{ route('commercial.prospection', ['statut' => $code]) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors
                          {{ $statut === $code ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                    {{ $libelle }} ({{ $stats['par_statut'][$code] ?? 0 }})
                </a>
                @endif
            @endforeach
        </div>
    </form>

    {{-- ═══════════════ Liste ═══════════════ --}}
    @if($prospects->isEmpty())
        <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 p-10 text-center">
            <div class="w-14 h-14 rounded-2xl bg-gray-50 dark:bg-gray-700/40 flex items-center justify-center mx-auto mb-3">
                <svg class="w-7 h-7 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
            </div>
            <p class="text-sm font-semibold text-gray-600 dark:text-gray-300">Aucun prospect ici</p>
            <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">
                Notez chaque établissement visité : c'est le seul moyen de ne pas perdre le fil entre deux tournées.
            </p>
            <button @click="nouveau = true"
                    class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-xl text-white text-sm font-semibold"
                    style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                Ajouter mon premier prospect
            </button>
        </div>
    @else
        <div class="space-y-2.5">
            @foreach($prospects as $prospect)
                <div x-data="ficheProspect({
                        statut: @js($prospect->statut),
                        libelleStatut: @js($prospect->libelleStatut()),
                        relance: @js($prospect->libelleRelance()),
                        niveau: @js($prospect->niveauRelance()),
                        routeStatut: @js(route('commercial.prospection.statut', $prospect)),
                        routeActivite: @js(route('commercial.prospection.activite', $prospect)),
                        couleurs: @js($couleursStatut),
                        historique: @js($prospect->activites->take(8)->map(fn ($a) => [
                            'date'   => $a->fait_le?->format('d/m'),
                            'type'   => $a->libelleType(),
                            'contenu' => $a->contenu,
                        ])->values()),
                        nbInteractions: {{ $prospect->activites_count }},
                     })"
                     :class="niveau === 'retard'
                        ? 'border-red-200 dark:border-red-800'
                        : 'border-gray-100 dark:border-gray-700'"
                     class="rounded-2xl bg-white dark:bg-gray-800 border transition-colors">

                    {{-- Ligne principale --}}
                    <div class="p-4 flex flex-wrap items-start gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 text-white text-sm font-bold"
                             style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                            {{ mb_strtoupper(mb_substr($prospect->nom_etablissement, 0, 1)) }}
                        </div>

                        <div class="flex-1 min-w-[180px]">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $prospect->nom_etablissement }}</p>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wide"
                                      :class="couleurs[statut] || couleurs['a_contacter']"
                                      x-text="libelleStatut"></span>
                                @if($prospect->priorite === 'haute')
                                <span class="px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">Prioritaire</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ $prospect->libelleTypeActivite() }}
                                @if($prospect->quartier || $prospect->ville)
                                    · {{ trim(($prospect->quartier ?? '') . ' ' . ($prospect->ville ?? '')) }}
                                @endif
                            </p>
                            <div class="flex flex-wrap items-center gap-3 mt-1.5 text-[11px] text-gray-400">
                                @if($prospect->contact_telephone)
                                <span class="inline-flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    {{ $prospect->contact_telephone }}
                                </span>
                                @endif
                                @if($prospect->contact_nom)
                                <span>{{ $prospect->contact_nom }}</span>
                                @endif
                                <span x-text="nbInteractions + (nbInteractions > 1 ? ' interactions' : ' interaction')"></span>
                                @if($prospect->derniere_interaction_le)
                                <span>Dernier contact : {{ $prospect->derniere_interaction_le->format('d/m/Y') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-2 ml-auto">
                            <span x-show="relance" x-cloak
                                  class="px-2 py-1 rounded-lg text-[10px] font-bold whitespace-nowrap"
                                  :class="niveau === 'retard'
                                     ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'
                                     : (niveau === 'jour'
                                        ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'
                                        : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300')"
                                  x-text="relance"></span>

                            <button @click="ouvert = !ouvert"
                                    class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                Fiche
                            </button>
                        </div>
                    </div>

                    {{-- Panneau détail --}}
                    <div x-show="ouvert" x-cloak x-collapse class="border-t border-gray-100 dark:border-gray-700 p-4 space-y-4">

                        {{-- Actions rapides --}}
                        <div class="flex flex-wrap gap-2">
                            @if($prospect->contact_telephone)
                            <a href="tel:{{ $prospect->contact_telephone }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs font-semibold">
                                Appeler
                            </a>
                            <a href="https://wa.me/{{ preg_replace('/\D/', '', $prospect->contact_telephone) }}"
                               target="_blank" rel="noopener"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 text-xs font-semibold">
                                WhatsApp
                            </a>
                            @endif
                            @if($prospect->contact_email)
                            <a href="mailto:{{ $prospect->contact_email }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-semibold">
                                E-mail
                            </a>
                            @endif
                        </div>

                        <div class="grid md:grid-cols-2 gap-4">
                            {{-- Fiche éditable --}}
                            <form method="POST" action="{{ route('commercial.prospection.update', $prospect) }}" class="space-y-2.5">
                                @csrf @method('PATCH')
                                <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Fiche</p>

                                <input type="text" name="nom_etablissement" value="{{ $prospect->nom_etablissement }}"
                                       class="form-input text-sm" placeholder="Nom de l'établissement" required>

                                <div class="grid grid-cols-2 gap-2">
                                    <select name="type_activite" class="form-input text-sm">
                                        <option value="">Activité…</option>
                                        @foreach($typesActivite as $code => $libelle)
                                        <option value="{{ $code }}" @selected($prospect->type_activite === $code)>{{ $libelle }}</option>
                                        @endforeach
                                    </select>
                                    <select name="priorite" class="form-input text-sm">
                                        @foreach(P::PRIORITES as $code => $libelle)
                                        <option value="{{ $code }}" @selected($prospect->priorite === $code)>{{ $libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <input type="text" name="ville" value="{{ $prospect->ville }}" class="form-input text-sm" placeholder="Ville">
                                    <input type="text" name="quartier" value="{{ $prospect->quartier }}" class="form-input text-sm" placeholder="Quartier">
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <input type="text" name="contact_nom" value="{{ $prospect->contact_nom }}" class="form-input text-sm" placeholder="Nom du contact">
                                    <input type="tel" name="contact_telephone" value="{{ $prospect->contact_telephone }}" class="form-input text-sm" placeholder="Téléphone">
                                </div>

                                <input type="email" name="contact_email" value="{{ $prospect->contact_email }}" class="form-input text-sm" placeholder="E-mail">

                                <div class="grid grid-cols-2 gap-2">
                                    <select name="source" class="form-input text-sm">
                                        @foreach(P::SOURCES as $code => $libelle)
                                        <option value="{{ $code }}" @selected($prospect->source === $code)>{{ $libelle }}</option>
                                        @endforeach
                                    </select>
                                    <input type="date" name="prochaine_relance_le"
                                           value="{{ $prospect->prochaine_relance_le?->format('Y-m-d') }}"
                                           class="form-input text-sm">
                                </div>

                                <textarea name="notes" rows="2" class="form-input text-sm" placeholder="Notes libres…">{{ $prospect->notes }}</textarea>

                                <div class="flex items-center gap-2 pt-1">
                                    <button type="submit" class="btn-primary text-xs px-3 py-1.5">Enregistrer</button>
                                </div>
                            </form>

                            {{-- Statut + activités --}}
                            <div class="space-y-3">
                                {{-- ═══ Étape du pipeline ═══ --}}
                                <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-3">
                                    <div class="flex items-center justify-between gap-2 mb-2">
                                        <p class="text-xs font-bold text-gray-700 dark:text-gray-200">Où en est ce prospect ?</p>
                                        <span x-show="enCoursStatut" x-cloak class="spinner spinner-sm" aria-hidden="true"></span>
                                        <span x-show="!enCoursStatut" class="inline-flex items-center gap-1 text-[10px] font-medium text-emerald-600 dark:text-emerald-400">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            enregistré aussitôt
                                        </span>
                                    </div>
                                    {{-- x-model (et non :value) : en cas d'échec
                                         le retour arrière réécrit aussi le select,
                                         sinon il affichait un statut fantôme. --}}
                                    <select x-model="statut"
                                            @change="changerStatut($event.target.value)"
                                            :disabled="enCoursStatut"
                                            class="form-input text-sm disabled:opacity-60 disabled:cursor-wait">
                                        @foreach(P::STATUTS as $code => $libelle)
                                        <option value="{{ $code }}">{{ $libelle }}</option>
                                        @endforeach
                                    </select>
                                    <p class="text-[11px] text-gray-400 mt-1.5">
                                        À contacter → Contacté → Intéressé → En essai → Converti.
                                        Le changement est pris en compte immédiatement, sans bouton.
                                    </p>
                                </div>

                                {{-- ═══ Journal d'interactions ═══ --}}
                                <form data-ajax
                                      @submit.prevent="ajouterInteraction($event.target)"
                                      class="rounded-xl border border-gray-200 dark:border-gray-700 p-3 space-y-2.5">
                                    <div>
                                        <p class="text-xs font-bold text-gray-700 dark:text-gray-200">Journal des échanges</p>
                                        <p class="text-[11px] text-gray-400 mt-0.5">
                                            Notez chaque appel ou visite : c'est ce qui garde le fil d'une fois sur l'autre.
                                        </p>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[10px] font-semibold uppercase tracking-wider text-gray-400 mb-1">
                                                Type d'échange
                                            </label>
                                            <select name="type" class="form-input text-sm">
                                                @foreach(\App\Models\CommercialActivite::TYPES as $code => $libelle)
                                                <option value="{{ $code }}">{{ $libelle }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold uppercase tracking-wider text-gray-400 mb-1">
                                                Date
                                            </label>
                                            <input type="date" name="fait_le" value="{{ today()->format('Y-m-d') }}"
                                                   class="form-input text-sm">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-[10px] font-semibold uppercase tracking-wider text-gray-400 mb-1">
                                            Ce qui s'est dit
                                        </label>
                                        <textarea name="contenu" rows="2" required class="form-input text-sm"
                                                  placeholder="Ex. : la gérante veut une démo vendredi, rappeler jeudi."></textarea>
                                    </div>

                                    <div>
                                        <label class="block text-[10px] font-semibold uppercase tracking-wider text-gray-400 mb-1">
                                            Prochaine relance <span class="normal-case tracking-normal text-gray-400">(facultatif)</span>
                                        </label>
                                        <input type="date" name="prochaine_relance_le"
                                               value="{{ $prospect->prochaine_relance_le?->format('Y-m-d') }}"
                                               class="form-input text-sm">
                                        <p class="text-[10px] text-gray-400 mt-1">
                                            Modifiez la date pour replanifier la relance : la fiche remontera dans « Relances » ce jour-là.
                                        </p>
                                    </div>

                                    <button type="submit" :disabled="enCoursInteraction"
                                            class="w-full justify-center btn-primary text-xs px-3 py-2 inline-flex items-center gap-1.5 disabled:opacity-60 disabled:cursor-wait">
                                        <span x-show="enCoursInteraction" x-cloak class="spinner spinner-sm" aria-hidden="true"></span>
                                        <span x-text="enCoursInteraction ? 'Enregistrement…' : 'Enregistrer l\'interaction'"></span>
                                    </button>
                                </form>
                            </div>
                        </div>

                        {{-- Historique --}}
                        <div x-show="historique.length > 0" x-cloak>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Historique</p>
                            <ul class="space-y-1.5">
                                <template x-for="(activite, i) in historique" :key="i">
                                    <li class="flex gap-2 text-xs">
                                        <span class="text-gray-400 whitespace-nowrap" x-text="activite.date"></span>
                                        <span class="px-1.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-300 text-[10px] font-semibold whitespace-nowrap"
                                              x-text="activite.type"></span>
                                        <span class="text-gray-600 dark:text-gray-300" x-text="activite.contenu"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        {{-- Suppression --}}
                        <form method="POST" action="{{ route('commercial.prospection.destroy', $prospect) }}"
                              onsubmit="return confirm('Supprimer définitivement ce prospect ?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-[11px] text-red-500 hover:text-red-700 font-medium">
                                Supprimer ce prospect
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">{{ $prospects->links() }}</div>
    @endif

    {{-- ═══════════════ Toasts (feedback sans rechargement) ═══════════════ --}}
    <div x-data="toasts()" @commercial-toast.window="ajouter($event.detail)"
         class="fixed bottom-4 right-4 z-[70] space-y-2 pointer-events-none">
        <template x-for="t in liste" :key="t.id">
            <div class="px-4 py-3 rounded-xl shadow-lg text-sm font-semibold text-white max-w-xs"
                 :class="t.type === 'error' ? 'bg-red-600' : 'bg-gray-900 dark:bg-gray-700'"
                 x-transition.opacity.duration.200ms
                 x-text="t.message"></div>
        </template>
    </div>

    {{-- ═══════════════ Modale nouveau prospect ═══════════════ --}}
    <div x-show="nouveau" x-cloak
         class="fixed inset-0 z-[60] flex items-start justify-center p-4 overflow-y-auto"
         style="background: rgba(0,0,0,0.5);"
         @keydown.escape.window="nouveau = false"
         @click.self="nouveau = false">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg my-8" @click.stop>
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Nouveau prospect</h3>
                <button @click="nouveau = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('commercial.prospection.store') }}" class="p-5 space-y-3">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Nom de l'établissement *</label>
                    <input type="text" name="nom_etablissement" required value="{{ old('nom_etablissement') }}"
                           class="form-input text-sm" placeholder="Salon Élégance">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Activité</label>
                        <select name="type_activite" class="form-input text-sm">
                            <option value="">—</option>
                            @foreach($typesActivite as $code => $libelle)
                            <option value="{{ $code }}" @selected(old('type_activite') === $code)>{{ $libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Priorité</label>
                        <select name="priorite" class="form-input text-sm">
                            @foreach(P::PRIORITES as $code => $libelle)
                            <option value="{{ $code }}" @selected($code === 'normale')>{{ $libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Ville</label>
                        <input type="text" name="ville" class="form-input text-sm" placeholder="Abidjan">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Quartier</label>
                        <input type="text" name="quartier" class="form-input text-sm" placeholder="Cocody">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Contact</label>
                        <input type="text" name="contact_nom" class="form-input text-sm" placeholder="Nom du gérant">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Téléphone</label>
                        <input type="tel" name="contact_telephone" class="form-input text-sm" placeholder="07 00 00 00 00">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">E-mail</label>
                    <input type="email" name="contact_email" class="form-input text-sm" placeholder="contact@salon.ci">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Origine</label>
                        <select name="source" class="form-input text-sm">
                            @foreach(P::SOURCES as $code => $libelle)
                            <option value="{{ $code }}">{{ $libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Relancer le</label>
                        <input type="date" name="prochaine_relance_le"
                               value="{{ today()->addDays(3)->format('Y-m-d') }}" class="form-input text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="form-input text-sm"
                              placeholder="Ce que vous avez observé sur place…"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="nouveau = false" class="btn-secondary text-sm">Annuler</button>
                    <button type="submit" class="btn-primary text-sm">Ajouter au pipeline</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection