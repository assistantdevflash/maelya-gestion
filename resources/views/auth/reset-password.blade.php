<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau mot de passe — Maëlya Gestion</title>
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
<body class="auth min-h-screen flex items-center justify-center p-4 relative overflow-hidden"
      style="background: linear-gradient(135deg, #1e1b4b 0%, #4c1d95 35%, #831843 100%)">

    {{-- Background decorations --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-[500px] h-[500px] bg-primary-500/20 rounded-full blur-[100px]"></div>
        <div class="absolute -bottom-40 -left-20 w-[400px] h-[400px] bg-secondary-500/15 rounded-full blur-[100px]"></div>
        <div class="absolute top-1/3 left-1/4 w-[300px] h-[300px] bg-primary-400/10 rounded-full blur-[80px]"></div>
    </div>

    <div class="w-full max-w-sm relative z-10">

        {{-- Logo --}}
        <div class="text-center mb-8">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5 group">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-white text-lg shadow-glow transition-transform group-hover:scale-105"
                     style="background: linear-gradient(135deg, #9333ea, #ec4899);">M</div>
                <span class="text-xl font-display font-bold text-white tracking-tight">Maëlya Gestion</span>
            </a>
        </div>

        {{-- Card --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-7 shadow-float" style="border: 1px solid rgba(255,255,255,0.1);">
            <h1 class="text-xl font-display font-bold text-gray-900 dark:text-white mb-1">Nouveau mot de passe</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Choisissez un mot de passe sécurisé pour votre compte.</p>

            {{-- Erreurs --}}
            @if($errors->any())
                <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 rounded-xl p-3 mb-5 text-sm">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div class="form-group">
                    <label for="email" class="form-label">Adresse e-mail</label>
                    <input type="email" id="email" name="email" required autocomplete="username"
                           value="{{ old('email', $request->email) }}"
                           class="form-input @error('email') border-red-400 @enderror"
                           placeholder="votre@email.com">
                </div>

                <div class="form-group" x-data="{ show: false }">
                    <label for="password" class="form-label">Nouveau mot de passe</label>
                    <div class="relative">
                        <input :type="show ? 'text' : 'password'" id="password" name="password" required
                               autocomplete="new-password"
                               class="form-input pr-10 @error('password') border-red-400 @enderror"
                               placeholder="••••••••">
                        <button type="button" @click="show = !show"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="form-group" x-data="{ show: false }">
                    <label for="password_confirmation" class="form-label">Confirmer le mot de passe</label>
                    <div class="relative">
                        <input :type="show ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" required
                               autocomplete="new-password"
                               class="form-input pr-10 @error('password_confirmation') border-red-400 @enderror"
                               placeholder="••••••••">
                        <button type="button" @click="show = !show"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="w-full justify-center py-3 text-sm font-bold rounded-xl text-white shadow-lg transition-all duration-200 hover:shadow-xl active:scale-[0.98] flex items-center gap-2 mt-2"
                        style="background: linear-gradient(135deg, #9333ea 0%, #ec4899 100%);">
                    Réinitialiser mon mot de passe
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-white/30 mt-6">© {{ date('Y') }} Maëlya Gestion</p>
    </div>
</body>
</html>
