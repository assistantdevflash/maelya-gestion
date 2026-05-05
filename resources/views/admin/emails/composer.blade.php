@extends('layouts.admin')
@section('page-title', 'Composer un email')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
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
        min-height: 220px;
    }
    .ql-editor { min-height: 220px; color: #111827; }
    .ql-editor.ql-blank::before { color: #9ca3af; font-style: normal; }

    /* Dark mode */
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
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="{
    mode: 'tous',
    selectedInstituts: [],
    institutMap: {{ $instituts->pluck('nom', 'id')->toJson() }},
    emailPerso: '',
    nomPerso: '',
    toggleAll(instituts) {
        if (this.selectedInstituts.length === instituts.length) {
            this.selectedInstituts = [];
        } else {
            this.selectedInstituts = instituts.map(i => i.id);
        }
    }
}" x-init="$watch('mode', () => { selectedInstituts = [] })">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="page-title">Composer un email</h1>
            <p class="page-subtitle">Composez et envoyez un email à un ou plusieurs établissements.</p>
        </div>
        <a href="{{ route('admin.emails.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/10 transition-all shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            Historique
        </a>
    </div>

    @if(session('success'))
    <div class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-2xl text-green-800 dark:text-green-300 text-sm font-medium">
        <svg class="w-5 h-5 flex-shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="flex items-start gap-3 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl text-red-800 dark:text-red-300 text-sm">
        <svg class="w-5 h-5 flex-shrink-0 text-red-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.emails.send') }}" class="space-y-6" id="email-form">
        @csrf

        {{-- Choix du mode --}}
        <div class="card-admin p-6">
            <h2 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Destinataires</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
                <label class="relative cursor-pointer" @click="mode = 'tous'">
                    <input type="radio" name="mode" value="tous" class="sr-only" :checked="mode === 'tous'" checked>
                    <div :class="mode === 'tous' ? 'ring-2 ring-purple-500 bg-purple-50 dark:bg-purple-900/20' : 'ring-1 ring-gray-200 dark:ring-white/10 hover:ring-purple-300'" class="rounded-2xl p-4 transition-all">
                        <div class="flex items-center gap-3">
                            <div :class="mode === 'tous' ? 'bg-purple-100 dark:bg-purple-800' : 'bg-gray-100 dark:bg-white/10'" class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5" :class="mode === 'tous' ? 'text-purple-600 dark:text-purple-300' : 'text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold" :class="mode === 'tous' ? 'text-purple-700 dark:text-purple-300' : 'text-gray-700 dark:text-gray-300'">Tous les établissements</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $instituts->count() }} établissements</p>
                            </div>
                        </div>
                    </div>
                </label>

                <label class="relative cursor-pointer" @click="mode = 'selection'">
                    <input type="radio" name="mode" value="selection" class="sr-only" :checked="mode === 'selection'">
                    <div :class="mode === 'selection' ? 'ring-2 ring-purple-500 bg-purple-50 dark:bg-purple-900/20' : 'ring-1 ring-gray-200 dark:ring-white/10 hover:ring-purple-300'" class="rounded-2xl p-4 transition-all">
                        <div class="flex items-center gap-3">
                            <div :class="mode === 'selection' ? 'bg-purple-100 dark:bg-purple-800' : 'bg-gray-100 dark:bg-white/10'" class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5" :class="mode === 'selection' ? 'text-purple-600 dark:text-purple-300' : 'text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold" :class="mode === 'selection' ? 'text-purple-700 dark:text-purple-300' : 'text-gray-700 dark:text-gray-300'">Sélection multiple</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Choisir des établissements</p>
                            </div>
                        </div>
                    </div>
                </label>

                <label class="relative cursor-pointer" @click="mode = 'un'">
                    <input type="radio" name="mode" value="un" class="sr-only" :checked="mode === 'un'">
                    <div :class="mode === 'un' ? 'ring-2 ring-purple-500 bg-purple-50 dark:bg-purple-900/20' : 'ring-1 ring-gray-200 dark:ring-white/10 hover:ring-purple-300'" class="rounded-2xl p-4 transition-all">
                        <div class="flex items-center gap-3">
                            <div :class="mode === 'un' ? 'bg-purple-100 dark:bg-purple-800' : 'bg-gray-100 dark:bg-white/10'" class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5" :class="mode === 'un' ? 'text-purple-600 dark:text-purple-300' : 'text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold" :class="mode === 'un' ? 'text-purple-700 dark:text-purple-300' : 'text-gray-700 dark:text-gray-300'">Un seul établissement</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Ciblage précis</p>
                            </div>
                        </div>
                    </div>
                </label>

                <label class="relative cursor-pointer" @click="mode = 'personnalise'">
                    <input type="radio" name="mode" value="personnalise" class="sr-only" :checked="mode === 'personnalise'">
                    <div :class="mode === 'personnalise' ? 'ring-2 ring-pink-500 bg-pink-50 dark:bg-pink-900/20' : 'ring-1 ring-gray-200 dark:ring-white/10 hover:ring-pink-300'" class="rounded-2xl p-4 transition-all">
                        <div class="flex items-center gap-3">
                            <div :class="mode === 'personnalise' ? 'bg-pink-100 dark:bg-pink-800' : 'bg-gray-100 dark:bg-white/10'" class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5" :class="mode === 'personnalise' ? 'text-pink-600 dark:text-pink-300' : 'text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold" :class="mode === 'personnalise' ? 'text-pink-700 dark:text-pink-300' : 'text-gray-700 dark:text-gray-300'">Email personnalisé</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Saisir une adresse</p>
                            </div>
                        </div>
                    </div>
                </label>
            </div>

            {{-- Liste de sélection multiple --}}
            <div x-show="mode === 'selection'" x-cloak x-transition class="space-y-3"
                 x-data="{ search: '' }">

                {{-- Barre de recherche + compteur + tout sélectionner --}}
                <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">
                    <div class="relative flex-1">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                        </svg>
                        <input type="text" x-model="search" placeholder="Rechercher un établissement…"
                               class="w-full pl-9 pr-4 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white placeholder-gray-400 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all">
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                            <span class="font-bold text-purple-600 dark:text-purple-400" x-text="selectedInstituts.length"></span> sélectionné(s)
                        </span>
                        <button type="button"
                                @click="toggleAll({{ $instituts->map(fn($i) => ['id' => $i->id])->values()->toJson() }})"
                                class="text-xs font-medium px-3 py-1.5 rounded-lg border border-purple-200 dark:border-purple-800 text-purple-600 dark:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/30 transition-colors">
                            Tout sélectionner
                        </button>
                        <button type="button" x-show="selectedInstituts.length > 0" x-cloak
                                @click="selectedInstituts = []"
                                class="text-xs font-medium px-3 py-1.5 rounded-lg border border-gray-200 dark:border-white/10 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                            Effacer
                        </button>
                    </div>
                </div>

                @if($errors->has('instituts'))
                <p class="text-xs text-red-500">{{ $errors->first('instituts') }}</p>
                @endif

                {{-- Grille avec scroll fixe --}}
                <div class="h-64 overflow-y-auto rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/[0.02] p-2">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-1.5">
                        @foreach($instituts as $institut)
                        <label class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg cursor-pointer transition-colors"
                               x-show="search === '' || '{{ strtolower($institut->nom . ' ' . $institut->ville) }}'.includes(search.toLowerCase())"
                               :class="selectedInstituts.includes({{ $institut->id }}) ? 'bg-purple-100 dark:bg-purple-900/40 ring-1 ring-purple-300 dark:ring-purple-700' : 'hover:bg-white dark:hover:bg-white/5'">
                            <input type="checkbox" name="instituts[]" value="{{ $institut->id }}"
                                   x-model="selectedInstituts" :value="{{ $institut->id }}"
                                   class="w-4 h-4 rounded text-purple-600 border-gray-300 focus:ring-purple-500 flex-shrink-0">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-gray-900 dark:text-white truncate leading-tight">{{ $institut->nom }}</p>
                                <p class="text-[11px] text-gray-400 truncate">{{ $institut->ville }}</p>
                            </div>
                        </label>
                        @endforeach
                    </div>
                    {{-- Message si aucun résultat --}}
                    <p class="text-center text-sm text-gray-400 py-6 hidden"
                       x-show="{{ $instituts->count() }} > 0 && search !== '' && document.querySelectorAll('[x-show]:not([style*=\'none\'])').length === 0">
                        Aucun établissement ne correspond à votre recherche.
                    </p>
                </div>

                {{-- Chips des sélectionnés --}}
                <div x-show="selectedInstituts.length > 0" x-cloak class="flex flex-wrap gap-1.5 pt-1">
                    <template x-for="id in selectedInstituts" :key="id">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300">
                            <span x-text="institutMap[id] || id"></span>
                            <button type="button" @click="selectedInstituts = selectedInstituts.filter(i => i !== id)"
                                    class="ml-0.5 hover:text-purple-900 dark:hover:text-white leading-none">✕</button>
                        </span>
                    </template>
                </div>
            </div>

            {{-- Un seul établissement --}}
            <div x-show="mode === 'un'" x-cloak x-transition>
                @if($errors->has('institut_id'))
                <p class="text-xs text-red-500 mb-1">{{ $errors->first('institut_id') }}</p>
                @endif
                <select name="institut_id" class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all">
                    <option value="">— Choisir un établissement —</option>
                    @foreach($instituts as $institut)
                    <option value="{{ $institut->id }}" {{ old('institut_id') == $institut->id ? 'selected' : '' }}>
                        {{ $institut->nom }} – {{ $institut->ville }} ({{ $institut->proprietaire?->email }})
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Email personnalisé --}}
            <div x-show="mode === 'personnalise'" x-cloak x-transition class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Adresse email <span class="text-red-500">*</span></label>
                    @if($errors->has('email_personnalise'))
                    <p class="text-xs text-red-500 mb-1">{{ $errors->first('email_personnalise') }}</p>
                    @endif
                    <input type="email" name="email_personnalise" x-model="emailPerso"
                           value="{{ old('email_personnalise') }}"
                           placeholder="exemple@domaine.com"
                           class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white placeholder-gray-400 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Prénom / Nom <span class="text-gray-400 font-normal">(optionnel)</span></label>
                    <input type="text" name="nom_personnalise" x-model="nomPerso"
                           value="{{ old('nom_personnalise') }}"
                           placeholder="Ex : Marie Dupont"
                           class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white placeholder-gray-400 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 transition-all">
                </div>
            </div>
        </div>

        {{-- Composition du message --}}
        <div class="card-admin p-6 space-y-5">
            <h2 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">Message</h2>

            {{-- Modèles prédéfinis --}}
            <div>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Modèles prédéfinis</p>
                <div class="flex flex-wrap gap-2" id="presets-container">
                    <button type="button" data-preset="bienvenue"
                            class="preset-btn inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors">
                        👋 Bienvenue & prise en main
                    </button>
                    <button type="button" data-preset="abonnement-expire"
                            class="preset-btn inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800 hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors">
                        ⏰ Abonnement expiré
                    </button>
                    <button type="button" data-preset="satisfaction"
                            class="preset-btn inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 hover:bg-amber-100 dark:hover:bg-amber-900/40 transition-colors">
                        ⭐ Sondage satisfaction
                    </button>
                    <button type="button" data-preset="nouveaute"
                            class="preset-btn inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800 hover:bg-purple-100 dark:hover:bg-purple-900/40 transition-colors">
                        🚀 Annonce nouveauté
                    </button>
                    <button type="button" data-preset="inactif"
                            class="preset-btn inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-gray-50 dark:bg-gray-700/40 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700/60 transition-colors">
                        💤 Compte inactif
                    </button>
                </div>
            </div>

            {{-- Sujet --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Sujet</label>
                @if($errors->has('sujet'))
                <p class="text-xs text-red-500 mb-1">{{ $errors->first('sujet') }}</p>
                @endif
                <input type="text" name="sujet" id="sujet-input" value="{{ old('sujet') }}"
                       placeholder="Objet de votre email…"
                       class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white placeholder-gray-400 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all">
            </div>

            {{-- Éditeur riche --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Corps du message</label>
                @if($errors->has('corps'))
                <p class="text-xs text-red-500 mb-1">{{ $errors->first('corps') }}</p>
                @endif
                <div id="quill-editor"></div>
                <input type="hidden" name="corps" id="corps-input" value="{{ old('corps') }}">
                <p class="text-xs text-gray-400 mt-2">Utilisez la barre d'outils pour mettre en forme votre message.</p>
            </div>
        </div>

        {{-- Bouton d'envoi --}}
        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                <span x-show="mode === 'tous'">Envoi à <strong class="text-gray-700 dark:text-gray-200">{{ $instituts->count() }} établissements</strong></span>
                <span x-show="mode === 'selection'" x-cloak>Envoi à <strong class="text-gray-700 dark:text-gray-200" x-text="selectedInstituts.length + ' établissement(s)'"></strong></span>
                <span x-show="mode === 'un'" x-cloak>Envoi à <strong class="text-gray-700 dark:text-gray-200">1 établissement</strong></span>
                <span x-show="mode === 'personnalise'" x-cloak>Envoi à <strong class="text-pink-600 dark:text-pink-400" x-text="emailPerso || '—'"></strong></span>
            </p>

            <button type="submit"
                    style="background: linear-gradient(135deg, #9333ea, #ec4899);"
                    class="inline-flex items-center gap-2 px-6 py-3 text-white font-semibold text-sm rounded-xl shadow-lg hover:opacity-90 transition-all active:scale-95">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="22" y1="2" x2="11" y2="13"/>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
                Envoyer l'email
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Init Quill ──────────────────────────────────────────────────────────
    const quill = new Quill('#quill-editor', {
        theme: 'snow',
        placeholder: 'Rédigez votre message ici…',
        modules: {
            toolbar: [
                [{ header: [1, 2, 3, false] }],
                ['bold', 'italic', 'underline'],
                [{ color: [] }, { background: [] }],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['link'],
                ['clean']
            ]
        }
    });

    // Pré-remplir si retour d'erreur (old value)
    const oldCorps = document.getElementById('corps-input').value;
    if (oldCorps) {
        quill.clipboard.dangerouslyPasteHTML(oldCorps);
    }

    // Synchroniser le hidden input avant soumission
    document.getElementById('email-form').addEventListener('submit', function () {
        document.getElementById('corps-input').value = quill.root.innerHTML;
    });

    // ── Modèles prédéfinis ──────────────────────────────────────────────────
    const presets = {
        'bienvenue': {
            sujet: 'Comment se passe votre prise en main de Maëlya Gestion ?',
            corps: `<p>Bonjour,</p>
<p>Vous avez rejoint <strong>Maëlya Gestion</strong> il y a quelques jours et nous espérons que tout se passe bien !</p>
<p>Nous voulions simplement prendre de vos nouvelles et savoir si :</p>
<ul>
  <li>Vous avez bien réussi à configurer votre établissement ?</li>
  <li>Vous avez des questions sur les fonctionnalités disponibles ?</li>
  <li>Vous rencontrez des difficultés ou avez besoin d'aide ?</li>
</ul>
<p>Notre équipe est disponible pour vous accompagner. N'hésitez pas à nous répondre directement à cet email.</p>
<p>Bonne continuation,<br><strong>L'équipe Maëlya Gestion</strong></p>`
        },
        'abonnement-expire': {
            sujet: 'Votre abonnement Maëlya Gestion a expiré – besoin d\'aide ?',
            corps: `<p>Bonjour,</p>
<p>Nous avons remarqué que votre abonnement <strong>Maëlya Gestion</strong> est arrivé à expiration.</p>
<p>Nous voulions nous assurer que tout s'est bien passé et comprendre si vous avez rencontré des difficultés pour renouveler :</p>
<ul>
  <li>Avez-vous rencontré un problème lors du paiement ?</li>
  <li>Souhaitez-vous changer de plan ?</li>
  <li>Avez-vous des questions sur nos offres ?</li>
</ul>
<p>Votre accès à la plateforme est temporairement limité, mais il suffit de renouveler votre abonnement pour retrouver toutes vos fonctionnalités.</p>
<p>Répondez à cet email et nous vous aiderons personnellement.</p>
<p>Cordialement,<br><strong>L'équipe Maëlya Gestion</strong></p>`
        },
        'satisfaction': {
            sujet: 'Votre avis compte pour nous – évaluez Maëlya Gestion',
            corps: `<p>Bonjour,</p>
<p>Cela fait un moment que vous utilisez <strong>Maëlya Gestion</strong> et votre retour nous est précieux !</p>
<p>Pourriez-vous prendre 2 minutes pour nous partager votre expérience ?</p>
<ul>
  <li>Êtes-vous satisfait(e) des fonctionnalités ?</li>
  <li>Y a-t-il des améliorations que vous souhaiteriez voir ?</li>
  <li>Recommanderiez-vous Maëlya Gestion à d'autres professionnels ?</li>
</ul>
<p>Vos retours nous permettent d'améliorer la plateforme pour mieux répondre à vos besoins. Répondez simplement à cet email !</p>
<p>Merci pour votre confiance,<br><strong>L'équipe Maëlya Gestion</strong></p>`
        },
        'nouveaute': {
            sujet: '🚀 Découvrez les nouveautés de Maëlya Gestion',
            corps: `<p>Bonjour,</p>
<p>Nous avons le plaisir de vous annoncer de nouvelles fonctionnalités sur <strong>Maëlya Gestion</strong> !</p>
<p><strong>Au programme :</strong></p>
<ul>
  <li>✨ [Décrivez la nouveauté 1]</li>
  <li>🛠️ [Décrivez la nouveauté 2]</li>
  <li>📊 [Décrivez la nouveauté 3]</li>
</ul>
<p>Connectez-vous dès maintenant sur votre espace pour découvrir ces nouvelles fonctionnalités.</p>
<p>Bonne utilisation !<br><strong>L'équipe Maëlya Gestion</strong></p>`
        },
        'inactif': {
            sujet: 'Votre établissement vous manque sur Maëlya Gestion',
            corps: `<p>Bonjour,</p>
<p>Nous avons remarqué que vous n'avez pas utilisé <strong>Maëlya Gestion</strong> depuis un moment.</p>
<p>Nous voulions simplement vous rappeler que votre espace est toujours actif et que de nombreuses fonctionnalités vous attendent :</p>
<ul>
  <li>📅 Gestion de vos rendez-vous et prestations</li>
  <li>👥 Suivi de votre clientèle</li>
  <li>💰 Caisse et point financier</li>
  <li>📦 Gestion des stocks</li>
</ul>
<p>Si vous rencontrez des difficultés ou avez besoin d'un coup de main, nous sommes là. Répondez à cet email !</p>
<p>À très bientôt,<br><strong>L'équipe Maëlya Gestion</strong></p>`
        }
    };

    document.querySelectorAll('.preset-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const key = this.dataset.preset;
            const preset = presets[key];
            if (!preset) return;

            document.getElementById('sujet-input').value = preset.sujet;
            quill.clipboard.dangerouslyPasteHTML(preset.corps);

            // Feedback visuel
            document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('ring-2', 'ring-purple-400'));
            this.classList.add('ring-2', 'ring-purple-400');
        });
    });

});
</script>
@endpush
