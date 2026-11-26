@extends('layouts.admin')
@section('page-title', 'Composer un message')

@push('styles')
<style>
    .ql-toolbar.ql-snow {
        background: var(--ql-toolbar-bg, #f9fafb);
        border-color: #e5e7eb;
        border-radius: 12px 12px 0 0;
    }
    .ql-container.ql-snow {
        background: var(--ql-bg, #ffffff);
        border-color: #e5e7eb;
        border-radius: 0 0 12px 12px;
        font-size: 14px;
        min-height: 200px;
    }
    .ql-editor { min-height: 200px; color: #111827; }
    .ql-editor.ql-blank::before { color: #9ca3af; font-style: normal; }

    .dark .ql-toolbar.ql-snow  { --ql-toolbar-bg: rgb(255 255 255 / 0.05); border-color: rgb(255 255 255 / 0.1); }
    .dark .ql-container.ql-snow { --ql-bg: rgb(255 255 255 / 0.03); border-color: rgb(255 255 255 / 0.1); }
    .dark .ql-editor { color: #f3f4f6; }
    .dark .ql-toolbar button svg .ql-stroke { stroke: #d1d5db; }
    .dark .ql-toolbar button svg .ql-fill { fill: #d1d5db; }
    .dark .ql-toolbar .ql-picker-label { color: #d1d5db; }
    .dark .ql-toolbar .ql-picker-options { background: #1f2937; border-color: rgb(255 255 255 / 0.1); }
    .dark .ql-toolbar button:hover svg .ql-stroke,
    .dark .ql-toolbar button.ql-active svg .ql-stroke { stroke: #a855f7; }
    .dark .ql-toolbar button:hover svg .ql-fill,
    .dark .ql-toolbar button.ql-active svg .ql-fill { fill: #a855f7; }
    .dark .ql-snow .ql-picker.ql-expanded .ql-picker-label { color: #a855f7; border-color: rgb(255 255 255 / 0.1); }

    .apercu-email p { margin-bottom: 10px; }
    .apercu-email ul, .apercu-email ol { padding-left: 18px; margin-bottom: 10px; list-style: disc; }
    .apercu-email a { color: #9333ea; text-decoration: underline; }
    .apercu-email strong { font-weight: 700; }
</style>
@endpush

@section('content')
@php
    $donneesInit = [
        'instituts'      => $instituts->map(fn ($i) => [
            'id'    => $i->id,
            'nom'   => $i->nom,
            'ville' => $i->ville,
        ])->values(),
        'emails'         => $emailsParInstitut,
        'tailleLot'      => $tailleLot,
        'routes'         => [
            'demarrer' => route('admin.emails.campagne.demarrer'),
            'lot'      => route('admin.emails.campagne.lot', ['campagne' => '__ID__']),
            'test'     => route('admin.emails.test'),
            'historique' => route('admin.emails.index'),
        ],
        'brouillon'      => $brouillon ? [
            'sujet' => $brouillon->sujet,
            'corps' => $brouillon->corps,
        ] : null,
        'anciennes'      => old(),
    ];
@endphp

<div x-data="composerEmail(@js($donneesInit))" x-cloak class="pb-28">

    {{-- ═══ En-tête ═══ --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-6">
        <div class="flex-1 min-w-0">
            <h1 class="page-title">Composer un message</h1>
            <p class="page-subtitle hidden sm:block">Un email, une notification, une bannière — ou les deux à la fois.</p>
        </div>
        <a href="{{ route('admin.emails.index') }}"
           class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/10 transition-all shadow-sm flex-shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            Historique
        </a>
    </div>

    @if($brouillon)
    <div class="mb-5 flex items-start gap-3 p-4 rounded-2xl bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800">
        <svg class="w-5 h-5 flex-shrink-0 text-purple-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div class="text-sm text-purple-800 dark:text-purple-200">
            <p class="font-semibold">Message repris d'une campagne précédente</p>
            <p class="text-xs mt-0.5">Le sujet et le corps sont pré-remplis. Les destinataires sont à choisir à nouveau — aucune adresse n'est reprise pour éviter un renvoi accidentel.</p>
        </div>
    </div>
    @endif

    {{-- ═══ Fil des étapes ═══ --}}
    <div class="card-admin p-1.5 mb-6">
        <div class="flex gap-1">
            <template x-for="s in etapes" :key="s.numero">
                <button type="button" @click="allerA(s.numero)"
                        :class="etape === s.numero
                            ? 'bg-white dark:bg-white/10 text-gray-900 dark:text-white shadow-sm'
                            : (etape > s.numero ? 'text-purple-600 dark:text-purple-400' : 'text-gray-400 dark:text-gray-500')"
                        class="flex-1 px-3 py-2.5 rounded-lg text-sm font-semibold transition-all flex items-center justify-center gap-2">
                    <span class="w-5 h-5 rounded-full text-[11px] flex items-center justify-center flex-shrink-0"
                          :class="etape === s.numero ? 'bg-gradient-to-br from-purple-600 to-pink-500 text-white' : (etape > s.numero ? 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300' : 'bg-gray-100 dark:bg-white/10')"
                          x-text="etape > s.numero ? '✓' : s.numero"></span>
                    <span class="truncate" x-text="s.titre"></span>
                </button>
            </template>
        </div>
    </div>

    <div class="grid lg:grid-cols-5 gap-6 items-start">

        {{-- ═══ Colonne formulaire ═══ --}}
        <div class="lg:col-span-3 space-y-6">

            {{-- ───── Étape 1 : canal ───── --}}
            <div x-show="etape === 1" class="card-admin p-6">
                <h2 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-1">Par quel canal ?</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Le reste du formulaire s'adapte à votre choix.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <template x-for="c in canaux" :key="c.code">
                        <button type="button" @click="canal = c.code"
                                :class="canal === c.code ? 'ring-2 ring-purple-500 bg-purple-50 dark:bg-purple-900/20' : 'ring-1 ring-gray-200 dark:ring-white/10 hover:ring-purple-300'"
                                class="rounded-2xl p-4 text-left transition-all">
                            <div class="flex items-start gap-3">
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                                     :class="canal === c.code ? 'bg-purple-100 dark:bg-purple-800 text-purple-600 dark:text-purple-300' : 'bg-gray-100 dark:bg-white/10 text-gray-500'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="c.icone"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold" :class="canal === c.code ? 'text-purple-700 dark:text-purple-300' : 'text-gray-700 dark:text-gray-300'" x-text="c.titre"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="c.detail"></p>
                                </div>
                            </div>
                        </button>
                    </template>
                </div>

                <div class="flex justify-end mt-6">
                    <button type="button" @click="allerA(2)"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-all active:scale-95"
                            style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                        Choisir les destinataires
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>

            {{-- ───── Étape 2 : destinataires ───── --}}
            <div x-show="etape === 2" class="card-admin p-6">
                <h2 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Qui doit recevoir ?</h2>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
                    <template x-for="m in modes" :key="m.code">
                        <button type="button" @click="mode = m.code"
                                :class="mode === m.code ? (m.code === 'personnalise' ? 'ring-2 ring-pink-500 bg-pink-50 dark:bg-pink-900/20' : 'ring-2 ring-purple-500 bg-purple-50 dark:bg-purple-900/20') : 'ring-1 ring-gray-200 dark:ring-white/10 hover:ring-purple-300'"
                                class="rounded-2xl p-3.5 text-left transition-all">
                            <p class="text-sm font-semibold" :class="mode === m.code ? (m.code === 'personnalise' ? 'text-pink-700 dark:text-pink-300' : 'text-purple-700 dark:text-purple-300') : 'text-gray-700 dark:text-gray-300'" x-text="m.titre"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="m.detail"></p>
                        </button>
                    </template>
                </div>

                {{-- Sélection multiple --}}
                <div x-show="mode === 'selection'" x-cloak class="space-y-3">
                    <div class="flex flex-col sm:flex-row gap-2 items-start sm:items-center">
                        <div class="relative flex-1 w-full">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
                            <input type="text" x-model="recherche" placeholder="Rechercher un établissement…"
                                   class="w-full pl-9 pr-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white placeholder-gray-400 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button type="button" @click="toutBasculer()"
                                    class="text-xs font-medium px-3 py-1.5 rounded-lg border border-purple-200 dark:border-purple-800 text-purple-600 dark:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/30 transition-colors">
                                <span x-text="tousFiltresSelectionnes ? 'Tout désélectionner' : 'Tout sélectionner'"></span>
                            </button>
                            <button type="button" x-show="selectedInstituts.length > 0" @click="selectedInstituts = []"
                                    class="text-xs font-medium px-3 py-1.5 rounded-lg border border-gray-200 dark:border-white/10 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                Effacer
                            </button>
                        </div>
                    </div>

                    <div class="h-56 overflow-y-auto rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/[0.02] p-2">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                            <template x-for="i in institutsFiltres" :key="i.id">
                                <label class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg cursor-pointer transition-colors"
                                       :class="selectedInstituts.includes(i.id) ? 'bg-purple-100 dark:bg-purple-900/40 ring-1 ring-purple-300 dark:ring-purple-700' : 'hover:bg-white dark:hover:bg-white/5'">
                                    <input type="checkbox" :value="i.id" x-model="selectedInstituts"
                                           class="w-4 h-4 rounded text-purple-600 border-gray-300 focus:ring-purple-500 flex-shrink-0">
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold text-gray-900 dark:text-white truncate leading-tight" x-text="i.nom"></p>
                                        <p class="text-[11px] text-gray-400 truncate" x-text="i.ville"></p>
                                    </div>
                                </label>
                            </template>
                        </div>
                        <p x-show="institutsFiltres.length === 0" x-cloak class="text-center text-sm text-gray-400 py-8">
                            Aucun établissement ne correspond à « <span x-text="recherche"></span> ».
                        </p>
                    </div>

                    <div x-show="selectedInstituts.length > 0" class="flex flex-wrap gap-1.5">
                        <template x-for="id in selectedInstituts" :key="id">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300">
                                <span x-text="nomInstitut(id)"></span>
                                <button type="button" @click="selectedInstituts = selectedInstituts.filter(x => x !== id)" class="ml-0.5 hover:text-purple-900 dark:hover:text-white leading-none">✕</button>
                            </span>
                        </template>
                    </div>
                </div>

                {{-- Un seul --}}
                <div x-show="mode === 'un'" x-cloak>
                    <select x-model="institutUnique" class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">— Choisir un établissement —</option>
                        <template x-for="i in instituts" :key="i.id">
                            <option :value="i.id" x-text="i.nom + ' – ' + i.ville"></option>
                        </template>
                    </select>
                </div>

                {{-- Personnalisé --}}
                <div x-show="mode === 'personnalise'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Adresse email <span class="text-red-500">*</span></label>
                        <input type="email" x-model="emailPerso" placeholder="exemple@domaine.com"
                               class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white placeholder-gray-400 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Prénom / Nom <span class="text-gray-400 font-normal">(optionnel)</span></label>
                        <input type="text" x-model="nomPerso" placeholder="Ex : Marie Dupont"
                               class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white placeholder-gray-400 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>
                </div>

                {{-- Compteur de doublons --}}
                <div class="mt-5 flex flex-wrap items-center gap-2 text-xs">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-gray-100 dark:bg-white/10 text-gray-700 dark:text-gray-300 font-medium">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span x-text="resumeDestinataires"></span>
                    </span>
                    <span x-show="nbDoublons > 0" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 font-medium">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span x-text="nbDoublons + (nbDoublons > 1 ? ' doublons évités' : ' doublon évité')"></span>
                    </span>
                </div>

                <div class="flex justify-between gap-3 mt-6">
                    <button type="button" @click="allerA(1)" class="px-4 py-2.5 rounded-xl text-sm font-medium border border-gray-200 dark:border-white/10 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">Retour</button>
                    <button type="button" @click="allerA(3)"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-all active:scale-95"
                            style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                        Rédiger le message
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>

            {{-- ───── Étape 3 : contenu ───── --}}
            <div x-show="etape === 3" class="space-y-6">

                {{-- Bannière --}}
                <div x-show="estBanniere" x-cloak class="card-admin p-6 space-y-4">
                    <div>
                        <h2 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">Bannière du tableau de bord</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Affichée en haut du tableau de bord des établissements ciblés, jusqu'à ce qu'ils cliquent sur « C'est compris ».</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Titre <span class="text-red-500">*</span></label>
                        <input type="text" id="banner-titre" x-model="bannerTitre" maxlength="60" placeholder="Ex : ✨ Nouvelle fonctionnalité disponible"
                               class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white placeholder-gray-400 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <p class="text-right text-xs text-gray-400 mt-1" x-text="bannerTitre.length + '/60'"></p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Message <span class="text-red-500">*</span></label>
                        <textarea id="banner-message" x-model="bannerMessage" maxlength="500" rows="3" placeholder="Message affiché dans la bannière…"
                                  class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white placeholder-gray-400 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 resize-none"></textarea>
                        <p class="text-right text-xs text-gray-400 mt-1" x-text="bannerMessage.length + '/500'"></p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Type</label>
                            <select x-model="bannerType" class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                                <option value="info">ℹ️ Information (bleu)</option>
                                <option value="success">✅ Succès (vert)</option>
                                <option value="warning">⚠️ Avertissement (orange)</option>
                                <option value="danger">🚨 Important (rouge)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Durée d'affichage</label>
                            <input type="number" x-model="bannerDuree" min="1" max="365" placeholder="Illimité"
                                   class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white placeholder-gray-400 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                            <p class="text-xs text-gray-400 mt-1">En jours — vide = sans expiration</p>
                        </div>
                    </div>
                </div>

                {{-- Email --}}
                <div x-show="besoinEmail" x-cloak class="card-admin p-6 space-y-5">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">Message email</h2>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="p in presets" :key="p.cle">
                                <button type="button" @click="appliquerPreset(p)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-medium bg-gray-50 dark:bg-white/5 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-white/10 hover:border-purple-300 hover:text-purple-700 dark:hover:text-purple-300 transition-colors"
                                        x-text="p.libelle"></button>
                            </template>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Sujet <span class="text-red-500">*</span></label>
                        <input type="text" id="sujet-input" x-model="sujet" maxlength="255" placeholder="Objet de votre email…"
                               class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white placeholder-gray-400 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Corps du message <span class="text-red-500">*</span></label>
                        <div id="quill-editor"></div>
                        <p class="text-xs text-gray-400 mt-2">La mise en forme est reprise telle quelle dans l'email.</p>
                    </div>
                </div>

                {{-- Push --}}
                <div x-show="besoinPush" x-cloak class="card-admin p-6 space-y-4">
                    <div>
                        <h2 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">Notification push</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Affichée sur le téléphone, sans ouvrir l'application.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Titre <span class="text-red-500">*</span></label>
                        <input type="text" id="push-titre" x-model="pushTitre" maxlength="60" placeholder="Ex : 📢 Mise à jour importante"
                               class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white placeholder-gray-400 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <p class="text-right text-xs text-gray-400 mt-1" x-text="pushTitre.length + '/60'"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Message <span class="text-gray-400 font-normal">(facultatif — dérivé du corps sinon)</span></label>
                        <textarea x-model="pushMessage" maxlength="120" rows="2" placeholder="Message court affiché dans la notification…"
                                  class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white placeholder-gray-400 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                        <p class="text-right text-xs text-gray-400 mt-1" x-text="pushMessage.length + '/120'"></p>
                    </div>
                </div>

                <div class="flex justify-between gap-3">
                    <button type="button" @click="allerA(2)" class="px-4 py-2.5 rounded-xl text-sm font-medium border border-gray-200 dark:border-white/10 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">Retour</button>
                    <button type="button" @click="envoyerTest()" :disabled="enCours || !besoinEmail"
                            :class="besoinEmail ? '' : 'opacity-40 cursor-not-allowed'"
                            class="px-4 py-2.5 rounded-xl text-sm font-medium border border-gray-200 dark:border-white/10 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                        M'envoyer un test
                    </button>
                </div>
            </div>
        </div>

        {{-- ═══ Colonne aperçu ═══ --}}
        <div class="lg:col-span-2 space-y-4 lg:sticky lg:top-4">
            <div class="card-admin overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 dark:border-white/10 flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Aperçu</h3>
                </div>

                <div class="p-4 space-y-4">

                    {{-- Aperçu email --}}
                    <div x-show="besoinEmail" class="rounded-xl overflow-hidden border border-gray-200 dark:border-white/10">
                        <div class="px-4 py-5 text-center" style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                            <div class="w-9 h-9 mx-auto rounded-xl bg-white/20 flex items-center justify-center text-base mb-2">✉️</div>
                            <p class="text-white font-bold text-sm">{{ config('app.name') }}</p>
                            <p class="text-white/80 text-[11px] mt-0.5">Message de votre équipe</p>
                        </div>
                        <div class="bg-white dark:bg-slate-800 p-4">
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Sujet</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white mb-3 break-words" x-text="sujet || 'Sans objet'"></p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white mb-2">Bonjour 👋</p>
                            <div class="text-[13px] text-gray-600 dark:text-gray-300 leading-relaxed apercu-email max-h-48 overflow-y-auto"
                                 x-html="corpsHtmlApercu || '<span class=\'text-gray-400 italic\'>Le message apparaîtra ici…</span>'"></div>
                        </div>
                    </div>

                    {{-- Aperçu push --}}
                    <div x-show="besoinPush" x-cloak class="rounded-xl border border-gray-200 dark:border-white/10 bg-gray-900 p-3">
                        <div class="flex items-start gap-2.5">
                            <img src="{{ asset('images/logo.png') }}" alt="Maëlya Gestion"
                                 class="w-8 h-8 rounded-lg flex-shrink-0 bg-white p-0.5">
                            <div class="min-w-0 flex-1">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-0.5">Maëlya · maintenant</p>
                                <p class="text-xs font-semibold text-white break-words" x-text="pushTitre || sujet || 'Titre de la notification'"></p>
                                <p class="text-[11px] text-gray-300 mt-0.5 line-clamp-2 break-words" x-text="pushMessage || messagePushApercu"></p>
                            </div>
                        </div>
                    </div>

                    {{-- Aperçu bannière --}}
                    <div x-show="estBanniere" x-cloak>
                        <div class="rounded-xl p-3 border" :class="classesBanniere">
                            <p class="text-sm font-semibold" x-text="bannerTitre || 'Titre de la bannière'"></p>
                            <p class="text-xs mt-0.5" x-text="bannerMessage || 'Le message de la bannière apparaîtra ici.'"></p>
                        </div>
                    </div>

                    {{-- Récapitulatif destinataires --}}
                    <div class="pt-3 border-t border-gray-100 dark:border-white/10 space-y-1.5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-500 dark:text-gray-400">Destinataires</span>
                            <span class="font-bold text-gray-900 dark:text-white" x-text="nbDestinataires"></span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-500 dark:text-gray-400">Canal</span>
                            <span class="font-semibold text-purple-600 dark:text-purple-400" x-text="libelleCanal"></span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-500 dark:text-gray-400">Durée estimée</span>
                            <span class="font-semibold text-gray-700 dark:text-gray-300" x-text="dureeEstimee"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ Barre d'action collante ═══ --}}
    {{-- Sur grand écran la barre démarre après la sidebar (260 px) : avec un
         simple padding, le premier caractère passait sous le menu fixe. --}}
    <div class="fixed bottom-[3.5rem] lg:bottom-0 left-0 lg:left-[260px] right-0 z-30 border-t border-gray-200 dark:border-white/10 bg-white/95 dark:bg-slate-900/95 backdrop-blur px-4 py-3">
        <div class="max-w-7xl mx-auto flex items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                    <span x-text="nbDestinataires"></span>
                    <span x-text="nbDestinataires > 1 ? 'destinataires' : 'destinataire'"></span>
                    · <span class="text-purple-600 dark:text-purple-400" x-text="libelleCanal"></span>
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="sousTitreAction"></p>
            </div>
            <button type="button" @click="demanderConfirmation()" :disabled="enCours"
                    class="inline-flex items-center gap-2 px-5 py-2.5 text-white font-semibold text-sm rounded-xl shadow-lg hover:opacity-90 transition-all active:scale-95 disabled:opacity-50 flex-shrink-0"
                    style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                <span x-text="estBanniere ? 'Créer la bannière' : 'Envoyer'"></span>
            </button>
        </div>
    </div>

    {{-- ═══ Modale de confirmation ═══ --}}
    <div x-show="confirmOuverte" x-cloak @keydown.escape.window="confirmOuverte = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4" @click.self="confirmOuverte = false">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md p-6" x-transition>
            <div class="flex items-start gap-3 mb-4">
                <div class="w-11 h-11 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white" x-text="titreConfirmation"></h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-text="texteConfirmation"></p>
                </div>
            </div>

            <div class="rounded-xl bg-gray-50 dark:bg-white/5 p-3 space-y-1 text-xs mb-5">
                <div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">Destinataires</span><span class="font-semibold text-gray-900 dark:text-white" x-text="nbDestinataires"></span></div>
                <div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">Canal</span><span class="font-semibold text-gray-900 dark:text-white" x-text="libelleCanal"></span></div>
                <div class="flex justify-between" x-show="besoinEmail"><span class="text-gray-500 dark:text-gray-400">Sujet</span><span class="font-semibold text-gray-900 dark:text-white truncate ml-3" x-text="sujet || '—'"></span></div>
            </div>

            <div class="flex gap-3">
                <button type="button" @click="confirmOuverte = false" class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300 font-medium text-sm hover:bg-gray-50 dark:hover:bg-white/5 transition">Annuler</button>
                <button type="button" @click="lancer()"
                        class="flex-1 px-4 py-2.5 rounded-xl text-white font-semibold text-sm transition active:scale-95"
                        style="background: linear-gradient(135deg, #9333ea, #ec4899);"
                        x-text="estBanniere ? 'Créer' : 'Envoyer'"></button>
            </div>
        </div>
    </div>

    {{-- ═══ Modale de progression ═══ --}}
    <div x-show="progressionOuverte" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md p-6" x-transition>
            <div class="text-center mb-5">
                <div class="w-12 h-12 rounded-full mx-auto mb-3 flex items-center justify-center"
                     :class="termine ? 'bg-green-100 dark:bg-green-900/30' : 'bg-purple-100 dark:bg-purple-900/30'">
                    <svg x-show="!termine" class="w-6 h-6 text-purple-600 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <svg x-show="termine" class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white" x-text="titreProgression"></h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-text="texteProgression"></p>
            </div>

            <div class="h-2.5 bg-gray-100 dark:bg-white/10 rounded-full overflow-hidden mb-2">
                <div class="h-full rounded-full transition-all duration-500" :style="'width:' + progression + '%'" style="background: linear-gradient(90deg, #9333ea, #ec4899);"></div>
            </div>
            <p class="text-center text-xs text-gray-500 dark:text-gray-400 mb-4">
                <span x-text="progression"></span> %
            </p>

            <div x-show="erreursLot.length > 0" class="mb-4">
                <p class="text-xs font-semibold text-red-500 mb-1">Erreurs</p>
                <div class="max-h-24 overflow-y-auto rounded-lg bg-red-50 dark:bg-red-900/20 p-2 text-[11px] text-red-700 dark:text-red-300 space-y-0.5">
                    <template x-for="(e, idx) in erreursLot" :key="idx"><p x-text="e"></p></template>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="button" x-show="termine" @click="terminer()"
                        class="flex-1 px-4 py-2.5 rounded-xl text-white font-semibold text-sm"
                        style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                    Voir l'historique
                </button>
                <button type="button" x-show="termine && arret" @click="reprendre()"
                        class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300 font-medium text-sm">
                    Reprendre l'envoi
                </button>
                <button type="button" x-show="!termine" @click="interrompre()"
                        class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300 font-medium text-sm">
                    Interrompre
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@vite('resources/js/quill-editor.js')
<script>
function composerEmail(init) {
    return {
        // ── données serveur ──────────────────────────────────────────────
        instituts: init.instituts,
        emailsParInstitut: init.emails ?? {},
        routes: init.routes,
        tailleLot: init.tailleLot || 5,
        anciennes: init.anciennes ?? {},

        // ── état ─────────────────────────────────────────────────────────
        etape: 1,
        canal: 'email',
        mode: 'tous',
        selectedInstituts: [],
        institutUnique: '',
        emailPerso: '',
        nomPerso: '',
        recherche: '',
        sujet: init.brouillon?.sujet ?? '',
        corps: init.brouillon?.corps ?? '',
        corpsHtmlApercu: init.brouillon?.corps ?? '',
        pushTitre: '',
        pushMessage: '',
        bannerTitre: '',
        bannerMessage: '',
        bannerType: 'info',
        bannerDuree: '',

        confirmOuverte: false,
        progressionOuverte: false,
        enCours: false,
        arret: false,
        campagneId: null,
        progression: 0,
        termine: false,
        envoyes: 0,
        echecs: 0,
        erreursLot: [],
        messageFinal: '',

        // ── référentiels ─────────────────────────────────────────────────
        etapes: [
            { numero: 1, titre: 'Canal' },
            { numero: 2, titre: 'Destinataires' },
            { numero: 3, titre: 'Message' },
        ],
        canaux: [
            { code: 'email', titre: 'Email seul', detail: 'Sans notification', icone: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
            { code: 'both', titre: 'Email + notification', detail: 'Les deux à la fois', icone: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9' },
            { code: 'push', titre: 'Notification seule', detail: 'Sans email', icone: 'M12 18h.01M8 21h8a2 2 0 002-2v-1H6v1a2 2 0 002 2zM12 2a6 6 0 016 6v3.159c0 .538.214 1.055.595 1.436L20 14H4l1.405-1.405A2.032 2.032 0 006 11.158V8a6 6 0 016-6z' },
            { code: 'banner', titre: 'Bannière dashboard', detail: 'Affichée en haut', icone: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4' },
        ],
        modes: [
            { code: 'tous', titre: 'Tous', detail: 'Tout le monde' },
            { code: 'selection', titre: 'Sélection', detail: 'Choisir' },
            { code: 'un', titre: 'Un seul', detail: 'Ciblage précis' },
            { code: 'personnalise', titre: 'Adresse libre', detail: 'Hors plateforme' },
        ],
        presets: [
            { cle: 'bienvenue', libelle: '👋 Bienvenue', sujet: 'Comment se passe votre prise en main de Maëlya Gestion ?', corps: "<p>Bonjour,</p><p>Vous avez rejoint <strong>Maëlya Gestion</strong> il y a quelques jours et nous espérons que tout se passe bien !</p><p>Nous voulions prendre de vos nouvelles :</p><ul><li>Avez-vous réussi à configurer votre établissement ?</li><li>Avez-vous des questions sur les fonctionnalités ?</li><li>Rencontrez-vous une difficulté ?</li></ul><p>Notre équipe est disponible pour vous accompagner. Répondez directement à cet email.</p><p>Bonne continuation,<br><strong>L'équipe Maëlya Gestion</strong></p>" },
            { cle: 'expire', libelle: '⏰ Abonnement expiré', sujet: 'Votre abonnement Maëlya Gestion a expiré – besoin d\'aide ?', corps: "<p>Bonjour,</p><p>Votre abonnement <strong>Maëlya Gestion</strong> est arrivé à expiration.</p><p>Nous voulions nous assurer que tout s'est bien passé :</p><ul><li>Avez-vous rencontré un problème lors du paiement ?</li><li>Souhaitez-vous changer de plan ?</li></ul><p>Répondez à cet email et nous vous aiderons personnellement.</p><p>Cordialement,<br><strong>L'équipe Maëlya Gestion</strong></p>" },
            { cle: 'satisfaction', libelle: '⭐ Satisfaction', sujet: 'Votre avis compte pour nous', corps: "<p>Bonjour,</p><p>Votre retour nous est précieux !</p><ul><li>Êtes-vous satisfait(e) des fonctionnalités ?</li><li>Quelles améliorations souhaiteriez-vous ?</li><li>Recommanderiez-vous Maëlya Gestion ?</li></ul><p>Répondez simplement à cet email.</p><p>Merci pour votre confiance,<br><strong>L'équipe Maëlya Gestion</strong></p>" },
            { cle: 'nouveaute', libelle: '🚀 Nouveauté', sujet: '🚀 Découvrez les nouveautés de Maëlya Gestion', corps: "<p>Bonjour,</p><p>De nouvelles fonctionnalités arrivent sur <strong>Maëlya Gestion</strong> !</p><ul><li>✨ [Nouveauté 1]</li><li>🛠️ [Nouveauté 2]</li></ul><p>Connectez-vous pour les découvrir.</p><p><strong>L'équipe Maëlya Gestion</strong></p>" },
            { cle: 'inactif', libelle: '💤 Inactif', sujet: 'Votre établissement vous manque sur Maëlya Gestion', corps: "<p>Bonjour,</p><p>Nous avons remarqué que vous n'aviez pas utilisé <strong>Maëlya Gestion</strong> depuis un moment.</p><p>Votre espace est toujours actif :</p><ul><li>📅 Rendez-vous et prestations</li><li>👥 Suivi clientèle</li><li>💰 Caisse et finances</li><li>📦 Stocks</li></ul><p>Besoin d'un coup de main ? Répondez à cet email.</p><p>À très bientôt,<br><strong>L'équipe Maëlya Gestion</strong></p>" },
        ],

        // ── calculs ──────────────────────────────────────────────────────
        get besoinEmail() { return this.canal === 'email' || this.canal === 'both'; },
        get besoinPush() { return this.canal === 'push' || this.canal === 'both'; },
        get estBanniere() { return this.canal === 'banner'; },

        get libelleCanal() {
            return { email: 'Email seul', both: 'Email + push', push: 'Push seule', banner: 'Bannière' }[this.canal];
        },

        get institutsFiltres() {
            const q = this.recherche.trim().toLowerCase();
            if (!q) return this.instituts;
            return this.instituts.filter(i =>
                (i.nom + ' ' + (i.ville || '')).toLowerCase().includes(q)
            );
        },

        get tousFiltresSelectionnes() {
            const filtres = this.institutsFiltres;
            return filtres.length > 0 && filtres.every(i => this.selectedInstituts.includes(i.id));
        },

        get emailsSelection() {
            if (this.mode === 'tous') {
                return this.instituts.map(i => this.emailsParInstitut[i.id]).filter(Boolean);
            }
            if (this.mode === 'selection') {
                return this.selectedInstituts.map(id => this.emailsParInstitut[id]).filter(Boolean);
            }
            if (this.mode === 'un') {
                return this.institutUnique ? [this.emailsParInstitut[this.institutUnique]].filter(Boolean) : [];
            }
            return this.emailPerso ? [this.emailPerso.toLowerCase()] : [];
        },

        get nbDestinataires() {
            return new Set(this.emailsSelection).size;
        },

        get nbDoublons() {
            return Math.max(0, this.emailsSelection.length - this.nbDestinataires);
        },

        get resumeDestinataires() {
            if (this.mode === 'personnalise') {
                return this.emailPerso ? '1 destinataire' : 'Aucune adresse saisie';
            }
            const n = this.nbDestinataires;
            return n + (n > 1 ? ' destinataires uniques' : ' destinataire');
        },

        get messagePushApercu() {
            const txt = (this.corpsHtmlApercu || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
            return txt ? txt.substring(0, 120) : 'Message court affiché dans la notification…';
        },

        get dureeEstimee() {
            const n = this.nbDestinataires;
            if (n === 0) return '—';
            const s = Math.ceil(n / this.tailleLot) * 2;
            return s < 60 ? '~' + s + ' s' : '~' + Math.ceil(s / 60) + ' min';
        },

        get sousTitreAction() {
            if (this.estBanniere) return 'Visible jusqu\'à clic sur « C\'est compris »';
            if (this.besoinEmail) return this.sujet || 'Aucun sujet défini';
            return 'Notification sans email';
        },

        get classesBanniere() {
            return {
                info:    'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 text-blue-900 dark:text-blue-200',
                success: 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-900 dark:text-green-200',
                warning: 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800 text-amber-900 dark:text-amber-200',
                danger:  'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-900 dark:text-red-200',
            }[this.bannerType] || 'bg-gray-50 border-gray-200 text-gray-900';
        },

        get titreConfirmation() {
            return this.estBanniere ? 'Créer cette bannière ?' : 'Confirmer l\'envoi';
        },

        get texteConfirmation() {
            const n = this.nbDestinataires;
            if (this.estBanniere) {
                return 'Elle sera affichée à ' + n + (n > 1 ? ' établissements' : ' établissement') + '.';
            }
            return 'Un message partira vers ' + n + (n > 1 ? ' destinataires' : ' destinataire') + '. Cette action est irréversible.';
        },

        get titreProgression() {
            if (!this.termine) return 'Envoi en cours…';
            if (this.arret) return 'Envoi interrompu';
            return this.echecs > 0 ? 'Envoi terminé avec des erreurs' : 'Envoi terminé';
        },

        get texteProgression() {
            return this.envoyes + ' envoyé(s) · ' + this.echecs + ' échec(s)' +
                (this.termine ? '' : ' · ' + (this.nbDestinataires - this.envoyes - this.echecs) + ' restant(s)');
        },

        // ── actions ──────────────────────────────────────────────────────
        nomInstitut(id) {
            const i = this.instituts.find(x => x.id === id);
            return i ? i.nom : id;
        },

        allerA(n) {
            if (n > 1 && this.etape === 1 && this.canal === 'personnalise') this.canal = 'email';
            if (n === 3) {
                if (this.mode === 'selection' && this.selectedInstituts.length === 0) return;
                if (this.mode === 'un' && !this.institutUnique) return;
                if (this.mode === 'personnalise' && !this.emailPerso) return;
            }
            this.etape = n;
        },

        toutBasculer() {
            const ids = this.institutsFiltres.map(i => i.id);
            if (this.tousFiltresSelectionnes) {
                this.selectedInstituts = this.selectedInstituts.filter(id => !ids.includes(id));
            } else {
                this.selectedInstituts = [...new Set([...this.selectedInstituts, ...ids])];
            }
        },

        appliquerPreset(p) {
            this.sujet = p.sujet;
            this.corps = p.corps;
            this.corpsHtmlApercu = p.corps;
            if (window.__quill) {
                window.__quill.clipboard.dangerouslyPasteHTML(p.corps);
            }
            if (this.besoinPush && !this.pushTitre) {
                this.pushTitre = p.sujet.substring(0, 60);
            }
        },

        demanderConfirmation() {
            // Contrôles minimaux côté client ; le serveur reste juge.
            if (this.nbDestinataires === 0 && this.mode !== 'personnalise') return this.alerte('Sélectionnez au moins un destinataire.');
            if (this.mode === 'personnalise' && !this.emailPerso) return this.alerte('Saisissez une adresse email.');
            if (this.besoinEmail && !this.sujet.trim()) return this.alerte('Renseignez un sujet.');
            if (this.besoinEmail && !(this.corpsHtmlApercu || '').replace(/<[^>]*>/g, '').trim()) return this.alerte('Le corps du message est vide.');
            if (this.besoinPush && !this.pushTitre.trim() && !this.sujet.trim()) return this.alerte('Renseignez un titre de notification.');
            if (this.estBanniere && (!this.bannerTitre.trim() || !this.bannerMessage.trim())) return this.alerte('Titre et message de la bannière sont requis.');

            this.confirmOuverte = true;
        },

        alerte(message) {
            this.erreursLot = [message];
            this.termine = true;
            this.progressionOuverte = true;
            this.envoyes = 0;
            this.echecs = 0;
            this.progression = 0;
        },

        async lancer() {
            this.confirmOuverte = false;
            this.enCours = true;
            this.arret = false;
            this.erreursLot = [];
            this.progression = 0;
            this.envoyes = 0;
            this.echecs = 0;
            this.termine = false;
            this.progressionOuverte = true;

            const charge = {
                mode: this.mode,
                send_mode: this.canal,
                sujet: this.sujet,
                corps: this.corps,
                push_titre: this.pushTitre,
                push_message: this.pushMessage,
                banner_titre: this.bannerTitre,
                banner_message: this.bannerMessage,
                banner_type: this.bannerType,
                banner_duree: this.bannerDuree,
                instituts: this.selectedInstituts,
                institut_id: this.institutUnique,
                email_personnalise: this.emailPerso,
                nom_personnalise: this.nomPerso,
            };

            try {
                const reponse = await this.post(this.routes.demarrer, charge);
                if (!reponse.ok) {
                    this.erreursLot = [reponse.erreur || 'Envoi impossible.'];
                    this.termine = true;
                    this.enCours = false;
                    return;
                }

                if (reponse.redirect) {
                    window.location.href = reponse.redirect;
                    return;
                }

                this.campagneId = reponse.campagne_id;
                await this.boucle();
            } catch (e) {
                this.erreursLot = [e.message];
                this.termine = true;
                this.enCours = false;
            }
        },

        async boucle() {
            while (!this.arret) {
                const url = this.routes.lot.replace('__ID__', this.campagneId);
                let etat;
                try {
                    etat = await this.post(url, {});
                } catch (e) {
                    this.erreursLot.push(e.message);
                    this.termine = true;
                    break;
                }
                if (!etat.ok) {
                    this.erreursLot.push(etat.erreur || 'Erreur pendant l\'envoi.');
                    this.termine = true;
                    break;
                }

                this.envoyes = etat.envoyes;
                this.echecs = etat.echecs;
                this.progression = etat.progression;
                this.erreursLot = etat.erreurs || [];

                if (etat.termine) {
                    this.termine = true;
                    break;
                }
            }
            this.enCours = false;
        },

        interrompre() {
            this.arret = true;
            this.enCours = false;
        },

        async reprendre() {
            this.arret = false;
            this.termine = false;
            this.enCours = true;
            await this.boucle();
        },

        terminer() {
            window.location.href = this.routes.historique;
        },

        async envoyerTest() {
            if (!this.sujet.trim() || !(this.corpsHtmlApercu || '').replace(/<[^>]*>/g, '').trim()) {
                return this.alerte('Renseignez un sujet et un message avant d\'envoyer un test.');
            }
            try {
                const r = await this.post(this.routes.test, { sujet: this.sujet, corps: this.corps });
                this.erreursLot = [];
                this.envoyes = 0;
                this.echecs = 0;
                this.progression = 0;
                this.termine = true;
                this.messageFinal = r.message || 'Test envoyé.';
                this.progressionOuverte = true;
            } catch (e) {
                this.alerte(e.message);
            }
        },

        async post(url, charge) {
            const reponse = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify(charge),
            });

            let donnees = {};
            try { donnees = await reponse.json(); } catch (e) { /* réponse vide */ }

            if (!reponse.ok) {
                if (donnees.errors) {
                    const premier = Object.values(donnees.errors)[0];
                    throw new Error(Array.isArray(premier) ? premier[0] : premier);
                }
                if (donnees.erreur) throw new Error(donnees.erreur);
                if (reponse.status === 419) throw new Error('Session expirée : rechargez la page.');
                throw new Error('Erreur ' + reponse.status);
            }

            return donnees;
        },

        // ── init ─────────────────────────────────────────────────────────
        init() {
            this.$watch('canal', (v) => {
                if (v === 'push' || v === 'both') {
                    if (!this.pushTitre && this.sujet) this.pushTitre = this.sujet.substring(0, 60);
                }
                if (this.mode === 'personnalise' && (v === 'push' || v === 'both')) {
                    this.mode = 'tous';
                }
            });

            this.$watch('sujet', (v) => {
                if (this.besoinPush && !this.pushTitre) this.pushTitre = (v || '').substring(0, 60);
            });

            document.addEventListener('DOMContentLoaded', () => this.initQuill());
            if (document.readyState !== 'loading') this.initQuill();
        },

        initQuill() {
            const cible = document.getElementById('quill-editor');
            if (!cible || typeof window.Quill === 'undefined' || window.__quill) return;

            const quill = new window.Quill('#quill-editor', {
                theme: 'snow',
                placeholder: 'Rédigez votre message ici…',
                modules: {
                    toolbar: [
                        [{ header: [2, 3, false] }],
                        ['bold', 'italic', 'underline'],
                        [{ color: [] }],
                        [{ list: 'ordered' }, { list: 'bullet' }],
                        ['link'],
                        ['clean'],
                    ],
                },
            });

            window.__quill = quill;

            if (this.corps) {
                quill.clipboard.dangerouslyPasteHTML(this.corps);
            }

            quill.on('text-change', () => {
                this.corps = quill.root.innerHTML;
                this.corpsHtmlApercu = quill.root.innerHTML;
            });
        },
    };
}
</script>
@endpush
