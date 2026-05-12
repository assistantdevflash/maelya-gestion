<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $institut->nom }} — Vitrine</title>
    <meta name="description" content="Découvrez les prestations et produits de {{ $institut->nom }}{{ $institut->ville ? ', ' . $institut->ville : '' }}.">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; background: #0f0f0f; color: #f5f5f5; min-height: 100vh; }
    </style>
</head>
<body class="bg-gray-950 text-white min-h-screen">

    {{-- ── HEADER ────────────────────────────────────────────────────────── --}}
    <header class="sticky top-0 z-50 bg-gray-950/90 backdrop-blur-md border-b border-white/5">
        <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                @if($institut->logo)
                <img src="{{ $institut->logo_url }}" alt="Logo {{ $institut->nom }}"
                     class="w-10 h-10 rounded-xl object-cover ring-1 ring-white/10">
                @else
                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-white text-base"
                     style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                    {{ strtoupper(substr($institut->nom, 0, 1)) }}
                </div>
                @endif
                <div>
                    <h1 class="font-bold text-base text-white leading-tight">{{ $institut->nom }}</h1>
                    @if($institut->ville)
                    <p class="text-xs text-gray-400">📍 {{ $institut->ville }}</p>
                    @endif
                </div>
            </div>
            @if($institut->telephone)
            <a href="tel:{{ $institut->telephone }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-2 rounded-xl text-white"
               style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                Appeler
            </a>
            @endif
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-8 space-y-10">

        {{-- ── PRESTATIONS ───────────────────────────────────────────────── --}}
        @if($prestations->isNotEmpty())
        <section>
            <div class="flex items-center gap-3 mb-5">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                     style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                    <svg class="w-4.5 h-4.5 text-white w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-white">Prestations</h2>
            </div>

            @foreach($prestations as $categorie => $items)
            <div class="mb-6">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">{{ $categorie }}</p>
                <div class="space-y-2">
                    @foreach($items as $p)
                    <div class="bg-gray-900 border border-white/5 rounded-xl p-4 flex items-center justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-white text-sm">{{ $p->nom }}</p>
                            @if($p->description)
                            <p class="text-xs text-gray-400 mt-0.5 truncate">{{ $p->description }}</p>
                            @endif
                            @if($p->duree)
                            <p class="text-xs text-gray-500 mt-1">⏱ {{ $p->duree }} min</p>
                            @endif
                        </div>
                        <div class="flex-shrink-0 text-right">
                            <p class="font-bold text-white">{{ number_format($p->prix, 0, ',', ' ') }} F</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </section>
        @endif

        {{-- ── PRODUITS ──────────────────────────────────────────────────── --}}
        @if($produits->isNotEmpty())
        <section>
            <div class="flex items-center gap-3 mb-5">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center bg-emerald-600">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-white">Produits</h2>
            </div>

            @foreach($produits as $categorie => $items)
            <div class="mb-6">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">{{ $categorie }}</p>
                <div class="space-y-2">
                    @foreach($items as $p)
                    <div class="bg-gray-900 border border-white/5 rounded-xl p-4 flex items-center justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-white text-sm">{{ $p->nom }}</p>
                            @if($p->description)
                            <p class="text-xs text-gray-400 mt-0.5 truncate">{{ $p->description }}</p>
                            @endif
                        </div>
                        <div class="flex-shrink-0 text-right">
                            <p class="font-bold text-white">{{ number_format($p->prix_vente, 0, ',', ' ') }} F</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </section>
        @endif

        @if($prestations->isEmpty() && $produits->isEmpty())
        <div class="text-center py-20 text-gray-500">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-sm">Aucun article disponible pour le moment.</p>
        </div>
        @endif

    </main>

    {{-- ── FOOTER ────────────────────────────────────────────────────────── --}}
    <footer class="mt-16 border-t border-white/5 py-6 text-center text-xs text-gray-600">
        <p>Propulsé par <a href="{{ url('/') }}" class="text-primary-400 hover:underline">Maëlya Gestion</a></p>
    </footer>

</body>
</html>
