@extends('layouts.admin')
@section('page-title', 'Envoyer un email')

@section('content')
<div class="space-y-6" x-data="{
    mode: 'tous',
    selectedInstituts: [],
    toggleAll(instituts) {
        if (this.selectedInstituts.length === instituts.length) {
            this.selectedInstituts = [];
        } else {
            this.selectedInstituts = instituts.map(i => i.id);
        }
    }
}" x-init="$watch('mode', () => { selectedInstituts = [] })">

    <div>
        <h1 class="page-title">Envoyer un email</h1>
        <p class="page-subtitle">Composez et envoyez un email à un ou plusieurs établissements.</p>
    </div>

    @if(session('success'))
    <div class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-2xl text-green-800 dark:text-green-300 text-sm font-medium">
        <svg class="w-5 h-5 flex-shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ session('success') }}
    </div>
    @endif

    <form method="POST" action="{{ route('admin.emails.send') }}" class="space-y-6">
        @csrf

        {{-- Choix du mode --}}
        <div class="card-admin p-6">
            <h2 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Destinataires</h2>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
                {{-- Tous --}}
                <label class="relative cursor-pointer" @click="mode = 'tous'">
                    <input type="radio" name="mode" value="tous" class="sr-only" :checked="mode === 'tous'" checked>
                    <div :class="mode === 'tous' ? 'ring-2 ring-purple-500 bg-purple-50 dark:bg-purple-900/20' : 'ring-1 ring-gray-200 dark:ring-white/10 hover:ring-purple-300'"
                         class="rounded-2xl p-4 transition-all">
                        <div class="flex items-center gap-3">
                            <div :class="mode === 'tous' ? 'bg-purple-100 dark:bg-purple-800' : 'bg-gray-100 dark:bg-white/10'"
                                 class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5" :class="mode === 'tous' ? 'text-purple-600 dark:text-purple-300' : 'text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold" :class="mode === 'tous' ? 'text-purple-700 dark:text-purple-300' : 'text-gray-700 dark:text-gray-300'">Tous les établissements</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $instituts->count() }} établissements</p>
                            </div>
                        </div>
                    </div>
                </label>

                {{-- Sélection --}}
                <label class="relative cursor-pointer" @click="mode = 'selection'">
                    <input type="radio" name="mode" value="selection" class="sr-only" :checked="mode === 'selection'">
                    <div :class="mode === 'selection' ? 'ring-2 ring-purple-500 bg-purple-50 dark:bg-purple-900/20' : 'ring-1 ring-gray-200 dark:ring-white/10 hover:ring-purple-300'"
                         class="rounded-2xl p-4 transition-all">
                        <div class="flex items-center gap-3">
                            <div :class="mode === 'selection' ? 'bg-purple-100 dark:bg-purple-800' : 'bg-gray-100 dark:bg-white/10'"
                                 class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5" :class="mode === 'selection' ? 'text-purple-600 dark:text-purple-300' : 'text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold" :class="mode === 'selection' ? 'text-purple-700 dark:text-purple-300' : 'text-gray-700 dark:text-gray-300'">Sélection multiple</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Choisir des établissements</p>
                            </div>
                        </div>
                    </div>
                </label>

                {{-- Un seul --}}
                <label class="relative cursor-pointer" @click="mode = 'un'">
                    <input type="radio" name="mode" value="un" class="sr-only" :checked="mode === 'un'">
                    <div :class="mode === 'un' ? 'ring-2 ring-purple-500 bg-purple-50 dark:bg-purple-900/20' : 'ring-1 ring-gray-200 dark:ring-white/10 hover:ring-purple-300'"
                         class="rounded-2xl p-4 transition-all">
                        <div class="flex items-center gap-3">
                            <div :class="mode === 'un' ? 'bg-purple-100 dark:bg-purple-800' : 'bg-gray-100 dark:bg-white/10'"
                                 class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5" :class="mode === 'un' ? 'text-purple-600 dark:text-purple-300' : 'text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold" :class="mode === 'un' ? 'text-purple-700 dark:text-purple-300' : 'text-gray-700 dark:text-gray-300'">Un seul établissement</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Ciblage précis</p>
                            </div>
                        </div>
                    </div>
                </label>
            </div>

            {{-- Liste de sélection multiple --}}
            <div x-show="mode === 'selection'" x-cloak x-transition class="space-y-3">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        <span x-text="selectedInstituts.length"></span> sélectionné(s)
                    </p>
                    <button type="button"
                            @click="toggleAll({{ $instituts->map(fn($i) => ['id' => $i->id])->values()->toJson() }})"
                            class="text-xs text-purple-600 hover:text-purple-800 dark:text-purple-400 font-medium">
                        Tout sélectionner / désélectionner
                    </button>
                </div>

                @if($errors->has('instituts'))
                <p class="text-xs text-red-500">{{ $errors->first('instituts') }}</p>
                @endif

                <div class="max-h-72 overflow-y-auto space-y-1.5 pr-1">
                    @foreach($instituts as $institut)
                    <label class="flex items-center gap-3 p-3 rounded-xl cursor-pointer transition-colors"
                           :class="selectedInstituts.includes({{ $institut->id }}) ? 'bg-purple-50 dark:bg-purple-900/20' : 'hover:bg-gray-50 dark:hover:bg-white/5'">
                        <input type="checkbox"
                               name="instituts[]"
                               value="{{ $institut->id }}"
                               x-model="selectedInstituts"
                               :value="{{ $institut->id }}"
                               class="w-4 h-4 rounded text-purple-600 border-gray-300 focus:ring-purple-500">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $institut->nom }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $institut->ville }} · {{ $institut->proprietaire?->email }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Un seul établissement --}}
            <div x-show="mode === 'un'" x-cloak x-transition>
                @if($errors->has('institut_id'))
                <p class="text-xs text-red-500 mb-1">{{ $errors->first('institut_id') }}</p>
                @endif
                <select name="institut_id"
                        class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all">
                    <option value="">— Choisir un établissement —</option>
                    @foreach($instituts as $institut)
                    <option value="{{ $institut->id }}" {{ old('institut_id') == $institut->id ? 'selected' : '' }}>
                        {{ $institut->nom }} – {{ $institut->ville }} ({{ $institut->proprietaire?->email }})
                    </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Composition du message --}}
        <div class="card-admin p-6 space-y-5">
            <h2 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">Message</h2>

            {{-- Sujet --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Sujet</label>
                @if($errors->has('sujet'))
                <p class="text-xs text-red-500 mb-1">{{ $errors->first('sujet') }}</p>
                @endif
                <input type="text"
                       name="sujet"
                       value="{{ old('sujet') }}"
                       placeholder="Objet de votre email…"
                       class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white placeholder-gray-400 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all">
            </div>

            {{-- Corps --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Corps du message</label>
                @if($errors->has('corps'))
                <p class="text-xs text-red-500 mb-1">{{ $errors->first('corps') }}</p>
                @endif
                <textarea name="corps"
                          rows="10"
                          placeholder="Rédigez votre message ici…"
                          class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white placeholder-gray-400 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all resize-y">{{ old('corps') }}</textarea>
                <p class="text-xs text-gray-400 mt-1">Les sauts de ligne sont préservés dans l'email envoyé.</p>
            </div>
        </div>

        {{-- Bouton d'envoi --}}
        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                <span x-show="mode === 'tous'">Envoi à <strong class="text-gray-700 dark:text-gray-200">{{ $instituts->count() }} établissements</strong></span>
                <span x-show="mode === 'selection'" x-cloak>Envoi à <strong class="text-gray-700 dark:text-gray-200" x-text="selectedInstituts.length + ' établissement(s)'"></strong></span>
                <span x-show="mode === 'un'" x-cloak>Envoi à <strong class="text-gray-700 dark:text-gray-200">1 établissement</strong></span>
            </p>

            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-500 hover:from-purple-700 hover:to-pink-600 text-white font-semibold text-sm rounded-xl shadow transition-all active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
                Envoyer l'email
            </button>
        </div>
    </form>
</div>
@endsection
