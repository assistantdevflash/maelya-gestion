<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification email — Maëlya Gestion</title>
    <script>
        (function() {
            try {
                var t = localStorage.getItem('maelya-theme');
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (t === 'dark' || (t !== 'light' && prefersDark)) document.documentElement.classList.add('dark');
            } catch(e) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen bg-purple-50 dark:bg-gray-900 flex items-center justify-center p-4">

<div class="w-full max-w-md" x-data="{ step: '{{ $codeEnvoye || $errors->has('code') || old('code') ? 'saisir' : 'intro' }}' }">

    {{-- Logo --}}
    <div class="text-center mb-8">
        <div class="inline-flex items-center gap-2 mb-2">
            <div class="w-10 h-10 rounded-2xl flex items-center justify-center text-white font-black text-lg shadow-lg"
                 style="background: linear-gradient(135deg, #9333ea, #ec4899);">M</div>
            <span class="text-xl font-black text-gray-900 dark:text-white tracking-tight">Maëlya<span class="font-light text-gray-500"> Gestion</span></span>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8">

        {{-- CAS EMPLOYÉ : bloqué par le propriétaire --}}
        @if(session('bloque_par_proprietaire'))
        <div class="text-center">
            <div class="w-16 h-16 rounded-2xl mx-auto mb-4 flex items-center justify-center bg-red-50 dark:bg-red-900/20">
                <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-3">Accès temporairement bloqué</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed mb-6">
                Le propriétaire de votre établissement n'a pas encore vérifié son adresse email.<br>
                L'accès sera rétabli dès que la vérification sera effectuée.
            </p>
            <p class="text-xs text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3">
                Contactez le propriétaire de votre établissement pour qu'il vérifie son email sur Maëlya Gestion.
            </p>
        </div>

        {{-- CAS PROPRIÉTAIRE : vérification normale --}}
        @else

        {{-- Alerte 3 jours dépassés --}}
        @if($joursRestants <= 0)
        <div class="flex items-start gap-3 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl mb-6">
            <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <div>
                <p class="text-sm font-bold text-red-800 dark:text-red-300">Accès bloqué</p>
                <p class="text-xs text-red-700 dark:text-red-400 mt-0.5">Vous devez vérifier votre adresse email pour accéder à votre espace.</p>
            </div>
        </div>
        @else
        <div class="flex items-start gap-3 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl mb-6">
            <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <div>
                <p class="text-sm font-bold text-amber-800 dark:text-amber-300">Vérification requise sous {{ $joursRestants }} jour(s)</p>
                <p class="text-xs text-amber-700 dark:text-amber-400 mt-0.5">Après ce délai, l'accès sera bloqué jusqu'à la vérification.</p>
            </div>
        </div>
        @endif

        @if(session('info'))
        <div class="flex items-center gap-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-2xl mb-4 text-sm text-blue-700 dark:text-blue-300">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('info') }}
        </div>
        @endif

        {{-- Étape 1 : Envoyer le code --}}
        <div x-show="step === 'intro'">
            <div class="text-center mb-6">
                <div class="w-16 h-16 rounded-2xl mx-auto mb-4 flex items-center justify-center"
                     style="background: linear-gradient(135deg, rgba(147,51,234,0.1), rgba(236,72,153,0.1));">
                    <svg class="w-8 h-8" style="color: #9333ea;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Vérifiez votre email</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Nous allons envoyer un code à 4 chiffres à l'adresse :<br>
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ auth()->user()->email }}</span>
                </p>
            </div>

            <form method="POST" action="{{ route('verification.email.envoyer') }}">
                @csrf
                <button type="submit" @click="step = 'saisir'"
                        class="w-full py-3.5 rounded-2xl text-white font-bold text-sm shadow-lg transition-all active:scale-95 hover:opacity-90"
                        style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                    Envoyer le code par email
                </button>
            </form>
        </div>

        {{-- Étape 2 : Saisir le code --}}
        <div x-show="step === 'saisir'" x-cloak>
            @if(session('code_envoye'))
            <div class="flex items-center gap-2 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-2xl mb-5 text-sm font-medium text-green-700 dark:text-green-300">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Code envoyé ! Consultez votre boîte mail.
            </div>
            @endif

            <div class="text-center mb-6">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Saisissez le code</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Entrez le code à 4 chiffres envoyé à<br>
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ auth()->user()->email }}</span>
                </p>
                @if($expireA && $expireA->isFuture())
                <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">Expire à {{ $expireA->format('H:i') }}</p>
                @endif
            </div>

            <form method="POST" action="{{ route('verification.email.verifier') }}" class="space-y-4">
                @csrf
                <div>
                    <input type="text" name="code" value="{{ old('code') }}"
                           maxlength="4" inputmode="numeric" pattern="[0-9]{4}" autocomplete="one-time-code"
                           placeholder="_ _ _ _"
                           class="w-full text-center text-4xl font-black tracking-[1rem] py-4 rounded-2xl border-2 transition-colors bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-300
                               {{ $errors->has('code') ? 'border-red-400 focus:border-red-500' : 'border-gray-200 dark:border-gray-600 focus:border-purple-500' }}
                               focus:outline-none focus:ring-0">
                    @error('code')
                    <p class="text-xs text-red-500 mt-1.5 text-center">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit"
                        class="w-full py-3.5 rounded-2xl text-white font-bold text-sm shadow-lg transition-all active:scale-95 hover:opacity-90"
                        style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                    Valider le code
                </button>
            </form>

            <div class="mt-4 text-center">
                <form method="POST" action="{{ route('verification.email.envoyer') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-xs text-purple-600 dark:text-purple-400 hover:underline">
                        Renvoyer un nouveau code
                    </button>
                </form>
            </div>
        </div>

        @endif

    </div>

    {{-- Déconnexion --}}
    <div class="mt-4 text-center">
        <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button type="submit" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                Se déconnecter
            </button>
        </form>
    </div>

</div>
</body>
</html>
