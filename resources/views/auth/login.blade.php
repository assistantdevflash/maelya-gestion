<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — Maëlya Gestion</title>
    <script>
        (function() {
            try {
                var t = localStorage.getItem('maelya-theme');
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (t === 'dark' || (t !== 'light' && prefersDark)) {
                    document.documentElement.classList.add('dark');
                }
            } catch(e) {}
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth min-h-screen bg-purple-50 dark:bg-gray-900">

    <div class="flex flex-col lg:flex-row min-h-screen">

        {{-- ═══ Panneau gauche — Branding ═══ --}}
        <div class="lg:flex-1 flex items-center justify-center px-6 py-12 lg:py-0">
            <div class="max-w-md text-center lg:text-left">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3 group mb-6">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center font-bold text-white text-2xl shadow-lg transition-transform group-hover:scale-105"
                         style="background: linear-gradient(135deg, #9333ea, #ec4899);">M</div>
                    <span class="text-2xl font-display font-bold text-gray-900 dark:text-white tracking-tight">Maëlya Gestion</span>
                </a>
                <h1 class="text-3xl lg:text-4xl font-display font-bold text-gray-900 dark:text-white leading-tight">
                    Gérez votre institut de beauté en toute simplicité.
                </h1>
                <p class="text-lg text-gray-500 dark:text-gray-400 mt-4">
                    Prestations, caisse, stocks, finances — tout dans un seul espace.
                </p>
            </div>
        </div>

        {{-- ═══ Panneau droit — Formulaire ═══ --}}
        <div class="lg:flex-1 flex items-center justify-center px-6 py-12 lg:py-0">
            <div class="w-full max-w-md">

                {{-- Card --}}
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-xl border border-gray-200 dark:border-slate-700">

                    <h2 class="text-2xl font-display font-bold text-gray-900 dark:text-white text-center">Se connecter</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center mt-1 mb-6">Accédez à votre espace de gestion</p>

                    @if(session('status'))
                        <div class="alert-success mb-4 text-sm">{{ session('status') }}</div>
                    @endif

                    @if(session('error'))
                        <div class="alert-danger mb-4 text-sm">{{ session('error') }}</div>
                    @endif

                    @if($errors->any())
                        <div class="alert-danger mb-4 text-sm">
                            @foreach($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="space-y-4">
                        @csrf

                        {{-- Honeypot anti-bot : champ invisible, rejeté si rempli --}}
                        <div aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px;height:0;width:0;overflow:hidden;">
                            <label for="website">Ne pas remplir</label>
                            <input type="text" name="website" id="website" tabindex="-1" autocomplete="off" value="">
                        </div>

                        <div>
                            <input type="email" id="email" name="email" required autofocus autocomplete="username"
                                   value="{{ old('email') }}"
                                   class="w-full px-4 py-3.5 rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white text-base placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:border-primary-400 focus:ring-2 focus:ring-primary-100 dark:focus:ring-primary-900 transition-all @error('email') border-red-400 @enderror"
                                   placeholder="E-mail ou numéro de mobile">
                        </div>

                        <div class="relative" x-data="{ show: false }">
                            <input :type="show ? 'text' : 'password'" id="password" name="password" required
                                   autocomplete="current-password"
                                   class="w-full px-4 py-3.5 rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white text-base placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:border-primary-400 focus:ring-2 focus:ring-primary-100 dark:focus:ring-primary-900 pr-12 transition-all @error('password') border-red-400 @enderror"
                                   placeholder="Mot de passe">
                            <button type="button" @click="show = !show"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>

                        <button type="submit" class="w-full py-3.5 text-base font-bold rounded-xl text-white shadow-lg transition-all duration-200 hover:shadow-xl hover:brightness-110 active:scale-[0.98]"
                                style="background: linear-gradient(135deg, #9333ea 0%, #ec4899 100%);">
                            Se connecter
                        </button>
                    </form>

                    @if(Route::has('password.request'))
                        <div class="text-center mt-4">
                            <a href="{{ route('password.request') }}" class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 font-medium">Mot de passe oublié ?</a>
                        </div>
                    @endif

                    <div class="relative my-6">
                        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200 dark:border-slate-600"></div></div>
                        <div class="relative flex justify-center"><span class="bg-white dark:bg-slate-800 px-3 text-xs text-gray-400">ou</span></div>
                    </div>

                    <a href="{{ route('inscription') }}"
                       class="block w-full py-3.5 text-center text-base font-bold rounded-xl border-2 border-emerald-500 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-all duration-200 active:scale-[0.98]">
                        Créer un nouveau compte
                    </a>
                </div>

                <p class="text-center text-xs text-gray-400 dark:text-gray-500 mt-6">© {{ date('Y') }} Maëlya Gestion</p>
            </div>
        </div>
    </div>
</body>
</html>
