<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page introuvable — Maëlya Gestion</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
    </style>
</head>
<body class="bg-gray-950 text-white min-h-screen flex flex-col">

    <div class="flex-1 flex flex-col items-center justify-center px-4 py-16 text-center">

        {{-- Gradient blob décoratif --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
            <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[600px] h-[600px] rounded-full opacity-10"
                 style="background: radial-gradient(circle, #9333ea 0%, transparent 70%);"></div>
        </div>

        {{-- Icône --}}
        <div class="relative w-20 h-20 rounded-2xl flex items-center justify-center mb-6 shadow-xl"
             style="background: linear-gradient(135deg, #9333ea, #ec4899);">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                      d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>

        {{-- Code --}}
        <p class="text-6xl font-black text-transparent bg-clip-text mb-2"
           style="background-image: linear-gradient(135deg, #9333ea, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            404
        </p>

        {{-- Titre --}}
        <h1 class="text-2xl font-bold text-white mb-3">
            Cette page est introuvable
        </h1>

        {{-- Description --}}
        <p class="text-gray-400 text-sm max-w-sm mb-8">
            La page que vous cherchez n'existe pas, a été désactivée ou l'adresse a changé.
        </p>

        {{-- Actions --}}
        <div class="flex flex-col sm:flex-row items-center gap-3">
            <a href="{{ url('/') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white shadow-lg transition-opacity hover:opacity-90"
               style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Retour à l'accueil
            </a>

            <button onclick="history.back()"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-gray-300 bg-gray-800 border border-white/10 hover:bg-gray-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Page précédente
            </button>
        </div>
    </div>

    {{-- Footer --}}
    <footer class="py-5 text-center text-xs text-gray-600 border-t border-white/5">
        Propulsé par
        <span class="font-semibold text-transparent bg-clip-text"
              style="background-image: linear-gradient(135deg, #9333ea, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            Maëlya Gestion
        </span>
    </footer>

</body>
</html>
