@extends('layouts.commercial')
@section('title', 'Mon profil')

@section('content')

{{-- En-tête --}}
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Mon profil</h1>
    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Gérez vos informations personnelles et votre sécurité</p>
</div>

{{-- Carte identité --}}
<div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 mb-5 overflow-hidden">

    {{-- Bandeau gradient + avatar --}}
    <div class="h-20 relative" style="background: linear-gradient(135deg, rgba(147,51,234,0.15), rgba(236,72,153,0.10));"></div>
    <div class="px-5 pb-5">
        <div class="flex items-end gap-4 -mt-8 mb-4">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-white text-2xl font-bold shadow-lg ring-4 ring-white dark:ring-gray-800 flex-shrink-0"
                 style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                {{ mb_strtoupper(mb_substr(Auth::user()->prenom ?? 'C', 0, 1)) }}
            </div>
            <div class="pb-1 min-w-0">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white truncate">{{ Auth::user()->nom_complet }}</h2>
                <div class="flex items-center gap-2 flex-wrap mt-0.5">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-purple-500/15 text-purple-600 dark:text-purple-400 text-[11px] font-semibold">
                        <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/></svg>
                        Commercial
                    </span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-[11px] font-mono font-semibold">
                        {{ $profil->code }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Stats rapides --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
            <div class="rounded-xl p-3 bg-gray-50 dark:bg-gray-700/50">
                <p class="text-[11px] text-gray-500 dark:text-gray-400">Parrainages</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $profil->parrainages_count }}</p>
            </div>
            <div class="rounded-xl p-3 bg-gray-50 dark:bg-gray-700/50">
                <p class="text-[11px] text-gray-500 dark:text-gray-400">Commissions</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $profil->commissions_count }}</p>
            </div>
            <div class="rounded-xl p-3 bg-green-50 dark:bg-green-900/20">
                <p class="text-[11px] text-green-600 dark:text-green-400">Total reçu</p>
                <p class="text-lg font-bold text-green-700 dark:text-green-400">{{ number_format($totalGagne, 0, ',', ' ') }}<span class="text-xs ml-0.5">F</span></p>
            </div>
            <div class="rounded-xl p-3 bg-purple-50 dark:bg-purple-900/20">
                <p class="text-[11px] text-purple-600 dark:text-purple-400">À recevoir</p>
                <p class="text-lg font-bold text-purple-700 dark:text-purple-400">{{ number_format($totalEnAttente, 0, ',', ' ') }}<span class="text-xs ml-0.5">F</span></p>
            </div>
        </div>

        {{-- Lien de parrainage --}}
        @php $lienParrainage = url('/inscription') . '?ref=' . $profil->code; @endphp
        <div class="rounded-xl p-4 border border-purple-200 dark:border-purple-800"
             style="background: linear-gradient(135deg, rgba(147,51,234,0.05), rgba(236,72,153,0.03));">
            <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2.5">Votre lien de parrainage</p>
            <div x-data="{ copiedLink: false, copiedCode: false }">
                <div class="flex items-center gap-2 mb-2">
                    <div class="flex-1 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 font-mono text-xs text-gray-600 dark:text-gray-400 truncate">
                        {{ $lienParrainage }}
                    </div>
                    <button @click="navigator.clipboard.writeText('{{ $lienParrainage }}'); copiedLink=true; setTimeout(()=>copiedLink=false,2000)"
                            class="shrink-0 flex items-center gap-1.5 px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-xs font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <svg x-show="!copiedLink" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        <svg x-show="copiedLink" class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span x-text="copiedLink ? 'Copié !' : 'Copier'"></span>
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 dark:text-gray-400">Code court :</span>
                    <button @click="navigator.clipboard.writeText('{{ $profil->code }}'); copiedCode=true; setTimeout(()=>copiedCode=false,2000)"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-white text-sm font-mono font-bold transition-all"
                            style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                        {{ $profil->code }}
                        <svg x-show="!copiedCode" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        <svg x-show="copiedCode" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </button>
                </div>
                @if($config)
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                    Commission : <strong class="text-gray-600 dark:text-gray-300">{{ $config->taux }}%</strong>
                    pendant <strong class="text-gray-600 dark:text-gray-300">{{ $config->duree_mois }} mois</strong> par abonnement validé.
                </p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Formulaires en onglets Alpine --}}
<div x-data="{ tab: '{{ session('tab', 'info') }}' }">

    {{-- Onglets --}}
    <div class="flex gap-1 mb-4 bg-gray-100 dark:bg-gray-800 rounded-xl p-1">
        <button @click="tab = 'info'"
                class="flex-1 py-2 text-sm font-medium rounded-lg transition-all"
                :class="tab === 'info' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'">
            <span class="flex items-center justify-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Informations
            </span>
        </button>
        <button @click="tab = 'password'"
                class="flex-1 py-2 text-sm font-medium rounded-lg transition-all"
                :class="tab === 'password' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'">
            <span class="flex items-center justify-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                Sécurité
            </span>
        </button>
    </div>

    {{-- Onglet Informations --}}
    <div x-show="tab === 'info'" x-cloak>

        @if(session('success'))
        <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 text-sm flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
        @endif

        <form method="POST" action="{{ route('commercial.profil.update') }}"
              class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Prénom --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Prénom</label>
                    <input type="text" name="prenom" value="{{ old('prenom', Auth::user()->prenom) }}"
                           class="w-full rounded-xl border px-3.5 py-2.5 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-purple-500
                                  {{ $errors->has('prenom') ? 'border-red-400 dark:border-red-500' : 'border-gray-200 dark:border-gray-600' }}"
                           placeholder="Votre prénom">
                    @error('prenom')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                {{-- Nom --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Nom de famille</label>
                    <input type="text" name="nom_famille" value="{{ old('nom_famille', Auth::user()->nom_famille) }}"
                           class="w-full rounded-xl border px-3.5 py-2.5 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-purple-500
                                  {{ $errors->has('nom_famille') ? 'border-red-400 dark:border-red-500' : 'border-gray-200 dark:border-gray-600' }}"
                           placeholder="Votre nom">
                    @error('nom_famille')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Email --}}
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Adresse e-mail</label>
                <input type="email" name="email" value="{{ old('email', Auth::user()->email) }}"
                       class="w-full rounded-xl border px-3.5 py-2.5 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-purple-500
                              {{ $errors->has('email') ? 'border-red-400 dark:border-red-500' : 'border-gray-200 dark:border-gray-600' }}"
                       placeholder="votre@email.com">
                @error('email')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            {{-- Téléphone --}}
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Téléphone</label>
                <input type="tel" name="telephone" value="{{ old('telephone', Auth::user()->telephone) }}"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-600 px-3.5 py-2.5 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-purple-500"
                       placeholder="+225 07 00 00 00 00">
            </div>

            {{-- Code commercial (lecture seule) --}}
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                    Code commercial
                    <span class="ml-1 text-gray-400 font-normal">(non modifiable)</span>
                </label>
                <div class="flex items-center gap-2 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 px-3.5 py-2.5">
                    <span class="font-mono text-sm font-bold text-purple-600 dark:text-purple-400">{{ $profil->code }}</span>
                    <span class="text-xs text-gray-400 dark:text-gray-500">— identifiant unique de parrainage</span>
                </div>
            </div>

            <div class="pt-1">
                <button type="submit"
                        class="w-full sm:w-auto flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl text-white text-sm font-semibold transition-all hover:opacity-90 active:scale-95"
                        style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Enregistrer les modifications
                </button>
            </div>
        </form>
    </div>

    {{-- Onglet Sécurité --}}
    <div x-show="tab === 'password'" x-cloak>

        @if(session('success_password'))
        <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 text-sm flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success_password') }}
        </div>
        @endif

        <form method="POST" action="{{ route('commercial.profil.password') }}"
              class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 space-y-4">
            @csrf

            {{-- Mot de passe actuel --}}
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Mot de passe actuel</label>
                <input type="password" name="current_password" autocomplete="current-password"
                       class="w-full rounded-xl border px-3.5 py-2.5 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-purple-500
                              {{ $errors->has('current_password') ? 'border-red-400 dark:border-red-500' : 'border-gray-200 dark:border-gray-600' }}">
                @error('current_password')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            {{-- Nouveau --}}
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Nouveau mot de passe</label>
                <input type="password" name="password" autocomplete="new-password"
                       class="w-full rounded-xl border px-3.5 py-2.5 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-purple-500
                              {{ $errors->has('password') ? 'border-red-400 dark:border-red-500' : 'border-gray-200 dark:border-gray-600' }}"
                       placeholder="Minimum 8 caractères">
                @error('password')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            {{-- Confirmation --}}
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Confirmer le nouveau mot de passe</label>
                <input type="password" name="password_confirmation" autocomplete="new-password"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-600 px-3.5 py-2.5 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <div class="pt-1">
                <button type="submit"
                        class="w-full sm:w-auto flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl text-white text-sm font-semibold transition-all hover:opacity-90 active:scale-95"
                        style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    Modifier le mot de passe
                </button>
            </div>
        </form>
    </div>

</div>

@endsection
