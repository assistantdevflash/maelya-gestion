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
        body { font-family: 'Inter', system-ui, sans-serif; background: #0a0a0a; color: #f5f5f5; min-height: 100vh; color-scheme: dark; }
        .rdv-input {
            background: rgba(255,255,255,0.07) !important;
            color: #f9fafb !important;
            border: 1px solid rgba(255,255,255,0.12) !important;
            color-scheme: dark;
        }
        .rdv-input::placeholder { color: #9ca3af !important; }
        .rdv-input:focus { outline: none; border-color: #a855f7 !important; box-shadow: 0 0 0 2px rgba(168,85,247,0.2); }
        .rdv-input option { background: #1f1f1f; color: #f9fafb; }
        .rdv-overlay { background: rgba(0,0,0,0.8); backdrop-filter: blur(6px); }
        .section-card { background: #111111; border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; }
        .item-card { background: #1a1a1a; border: 1px solid rgba(255,255,255,0.06); transition: border-color .15s; }
        .item-card:hover { border-color: rgba(168,85,247,0.3); }
        html { scroll-behavior: smooth; }
    </style>
</head>
<body class="min-h-screen"
      x-data="{ rdvOpen: {{ (session('success') || $errors->any()) && isset($prestationsFlat) && $prestationsFlat->isNotEmpty() ? 'true' : 'false' }} }"
      @keydown.escape.window="rdvOpen = false">

{{-- ╔══════════════════════════════════════════════════════════════╗
     ║  HEADER STICKY                                               ║
     ╚══════════════════════════════════════════════════════════════╝ --}}
<header class="sticky top-0 z-50 border-b" style="background:rgba(10,10,10,0.92);backdrop-filter:blur(16px);border-color:rgba(255,255,255,0.07);">
    <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between gap-3">
        {{-- Logo + nom --}}
        <div class="flex items-center gap-2.5 min-w-0">
            @if($institut->logo)
            <img src="{{ $institut->logo_url }}" alt="Logo {{ $institut->nom }}"
                 class="w-9 h-9 rounded-xl object-cover flex-shrink-0" style="border:1px solid rgba(255,255,255,0.1);">
            @else
            <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-white text-sm flex-shrink-0"
                 style="background:linear-gradient(135deg,#9333ea,#ec4899);">
                {{ strtoupper(substr($institut->nom, 0, 1)) }}
            </div>
            @endif
            <div class="min-w-0">
                <p class="font-bold text-sm text-white truncate leading-tight">{{ $institut->nom }}</p>
                @if($noteMoyenne)
                <p class="text-xs leading-tight" style="color:#eab308;">★ {{ number_format($noteMoyenne, 1) }} <span style="color:#6b7280;">({{ $nbAvis }})</span></p>
                @elseif($institut->ville)
                <p class="text-xs truncate" style="color:#6b7280;">📍 {{ $institut->ville }}</p>
                @endif
            </div>
        </div>
        {{-- Actions --}}
        <div class="flex items-center gap-2 flex-shrink-0">
            @if($institut->telephone)
            <a href="tel:{{ $institut->telephone }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-2 rounded-xl transition"
               style="color:#f9fafb;border:1px solid rgba(255,255,255,0.15);background:rgba(255,255,255,0.04);">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                <span class="hidden sm:inline">Appeler</span>
            </a>
            @endif
            @if(isset($prestationsFlat) && $prestationsFlat->isNotEmpty())
            <button type="button" @click="rdvOpen = true"
                    class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-2 rounded-xl text-white transition hover:opacity-90"
                    style="background:linear-gradient(135deg,#9333ea,#ec4899);">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                RDV
            </button>
            @endif
        </div>
    </div>
</header>

<main class="max-w-3xl mx-auto px-4 pb-16">

    {{-- ╔══════════════════════════════════════════════════════════════╗
         ║  HERO                                                        ║
         ╚══════════════════════════════════════════════════════════════╝ --}}
    <section class="mt-6 rounded-2xl overflow-hidden" style="background:linear-gradient(135deg,rgba(147,51,234,0.15),rgba(236,72,153,0.1));border:1px solid rgba(147,51,234,0.2);">
        <div class="px-6 py-7 flex flex-col sm:flex-row sm:items-center gap-5">
            {{-- Avatar --}}
            @if($institut->logo)
            <img src="{{ $institut->logo_url }}" alt="Logo {{ $institut->nom }}"
                 class="w-20 h-20 rounded-2xl object-cover flex-shrink-0 ring-2 ring-purple-500/30">
            @else
            <div class="w-20 h-20 rounded-2xl flex items-center justify-center font-black text-white text-3xl flex-shrink-0"
                 style="background:linear-gradient(135deg,#9333ea,#ec4899);">
                {{ strtoupper(substr($institut->nom, 0, 1)) }}
            </div>
            @endif

            {{-- Infos --}}
            <div class="flex-1 min-w-0">
                <h1 class="text-2xl font-black text-white leading-tight mb-1">{{ $institut->nom }}</h1>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm" style="color:#9ca3af;">
                    @if($institut->ville)
                    <span class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        {{ $institut->ville }}
                    </span>
                    @endif
                    @if($institut->telephone)
                    <span class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        {{ $institut->telephone }}
                    </span>
                    @endif
                    @if($noteMoyenne)
                    <span class="flex items-center gap-1" style="color:#eab308;">
                        <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        {{ number_format($noteMoyenne, 1) }} / 5 <span style="color:#6b7280;">({{ $nbAvis }} avis)</span>
                    </span>
                    @endif
                </div>

                {{-- Stats rapides --}}
                <div class="flex flex-wrap gap-3 mt-4">
                    @php
                        $nbPrestations = $prestations->flatten()->count();
                        $nbProduits    = $produits->flatten()->count();
                    @endphp
                    @if($nbPrestations > 0)
                    <a href="#prestations" class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full transition"
                       style="background:rgba(147,51,234,0.2);color:#d8b4fe;border:1px solid rgba(147,51,234,0.3);">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                        {{ $nbPrestations }} prestation{{ $nbPrestations > 1 ? 's' : '' }}
                    </a>
                    @endif
                    @if($nbProduits > 0)
                    <a href="#produits" class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full transition"
                       style="background:rgba(16,185,129,0.15);color:#6ee7b7;border:1px solid rgba(16,185,129,0.25);">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        {{ $nbProduits }} produit{{ $nbProduits > 1 ? 's' : '' }}
                    </a>
                    @endif
                    @if($nbAvis > 0)
                    <a href="#avis" class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full transition"
                       style="background:rgba(234,179,8,0.12);color:#fde047;border:1px solid rgba(234,179,8,0.25);">
                        <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        {{ $nbAvis }} avis
                    </a>
                    @endif
                    @if(isset($prestationsFlat) && $prestationsFlat->isNotEmpty())
                    <button type="button" @click="rdvOpen = true"
                            class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-full text-white transition hover:opacity-90"
                            style="background:linear-gradient(135deg,#9333ea,#ec4899);">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Prendre un RDV
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- ╔══════════════════════════════════════════════════════════════╗
         ║  PRESTATIONS                                                 ║
         ╚══════════════════════════════════════════════════════════════╝ --}}
    @if($prestations->isNotEmpty())
    <section id="prestations" class="mt-8">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background:linear-gradient(135deg,#9333ea,#ec4899);">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
            </div>
            <h2 class="text-lg font-bold text-white">Prestations</h2>
        </div>

        @foreach($prestations as $categorie => $items)
        <div class="mb-5">
            <div class="flex items-center gap-2 mb-3">
                <span class="text-xs font-bold uppercase tracking-widest px-2.5 py-1 rounded-full"
                      style="background:rgba(147,51,234,0.15);color:#c084fc;border:1px solid rgba(147,51,234,0.2);">
                    {{ $categorie }}
                </span>
                <div class="flex-1 h-px" style="background:rgba(255,255,255,0.06);"></div>
            </div>
            <div class="space-y-2">
                @foreach($items as $p)
                <div class="item-card rounded-xl px-4 py-3.5 flex items-center justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-white text-sm leading-snug">{{ $p->nom }}</p>
                        @if($p->description)
                        <p class="text-xs mt-0.5 truncate" style="color:#6b7280;">{{ $p->description }}</p>
                        @endif
                        @if($p->duree)
                        <p class="text-xs mt-1.5 flex items-center gap-1" style="color:#9ca3af;">
                            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ $p->duree }} min
                        </p>
                        @endif
                    </div>
                    <p class="font-bold text-white text-sm flex-shrink-0">{{ number_format($p->prix, 0, ',', '\u00a0') }} F</p>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </section>
    @endif

    {{-- ╔══════════════════════════════════════════════════════════════╗
         ║  PRODUITS                                                    ║
         ╚══════════════════════════════════════════════════════════════╝ --}}
    @if($produits->isNotEmpty())
    <section id="produits" class="mt-8">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background:linear-gradient(135deg,#059669,#10b981);">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
            <h2 class="text-lg font-bold text-white">Produits</h2>
        </div>

        @foreach($produits as $categorie => $items)
        <div class="mb-5">
            <div class="flex items-center gap-2 mb-3">
                <span class="text-xs font-bold uppercase tracking-widest px-2.5 py-1 rounded-full"
                      style="background:rgba(16,185,129,0.12);color:#6ee7b7;border:1px solid rgba(16,185,129,0.2);">
                    {{ $categorie }}
                </span>
                <div class="flex-1 h-px" style="background:rgba(255,255,255,0.06);"></div>
            </div>
            <div class="space-y-2">
                @foreach($items as $p)
                <div class="item-card rounded-xl px-4 py-3.5 flex items-center justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-white text-sm leading-snug">{{ $p->nom }}</p>
                        @if($p->description)
                        <p class="text-xs mt-0.5 truncate" style="color:#6b7280;">{{ $p->description }}</p>
                        @endif
                    </div>
                    <p class="font-bold text-white text-sm flex-shrink-0">{{ number_format($p->prix_vente, 0, ',', '\u00a0') }} F</p>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </section>
    @endif

    {{-- Vide --}}
    @if($prestations->isEmpty() && $produits->isEmpty())
    <div class="mt-8 text-center py-20" style="color:#4b5563;">
        <svg class="w-12 h-12 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <p class="text-sm">Aucun article disponible pour le moment.</p>
    </div>
    @endif

    {{-- ╔══════════════════════════════════════════════════════════════╗
         ║  AVIS CLIENTS                                                ║
         ╚══════════════════════════════════════════════════════════════╝ --}}
    @if($avis->count() > 0)
    <section id="avis" class="mt-8">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0"
                     style="background:rgba(234,179,8,0.2);border:1px solid rgba(234,179,8,0.3);">
                    <svg class="w-4 h-4 fill-current" style="color:#eab308;" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-white">Avis clients</h2>
            </div>
            @if($noteMoyenne)
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl" style="background:rgba(234,179,8,0.12);border:1px solid rgba(234,179,8,0.2);">
                <span class="text-xl font-black" style="color:#eab308;">{{ number_format($noteMoyenne, 1) }}</span>
                <div>
                    <div class="flex gap-0.5">
                        @for($i = 1; $i <= 5; $i++)
                        <svg class="w-3 h-3 fill-current {{ $i <= round($noteMoyenne) ? '' : 'opacity-25' }}" style="color:#eab308;" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        @endfor
                    </div>
                    <p class="text-xs" style="color:#9ca3af;">{{ $nbAvis }} avis</p>
                </div>
            </div>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach($avis as $a)
            <div class="item-card rounded-xl p-4">
                <div class="flex gap-0.5 mb-2">
                    @for($i = 1; $i <= 5; $i++)
                    <svg class="w-4 h-4 fill-current {{ $i <= (int)$a->note ? '' : 'opacity-20' }}" style="color:#eab308;" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    @endfor
                </div>
                @if($a->commentaire)
                <p class="text-sm mb-3 leading-relaxed" style="color:#e5e7eb;">« {{ $a->commentaire }} »</p>
                @endif
                <p class="text-xs font-medium" style="color:#6b7280;">
                    {{ $a->client_nom_snap ?: 'Client anonyme' }}
                    <span style="color:#374151;"> · </span>
                    {{ $a->repondu_le?->translatedFormat('d M Y') }}
                </p>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- ╔══════════════════════════════════════════════════════════════╗
         ║  MODAL RDV                                                   ║
         ╚══════════════════════════════════════════════════════════════╝ --}}
    @if(isset($prestationsFlat) && $prestationsFlat->isNotEmpty())
    <div x-show="rdvOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center rdv-overlay"
         @click.self="rdvOpen = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="w-full sm:max-w-lg sm:rounded-2xl rounded-t-2xl shadow-2xl overflow-hidden"
             style="background:#111;border:1px solid rgba(255,255,255,0.1);"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

            <div class="flex items-center justify-between px-5 py-4"
                 style="border-bottom:1px solid rgba(255,255,255,0.08);background:linear-gradient(135deg,rgba(147,51,234,0.15),rgba(236,72,153,0.15));">
                <div>
                    <h2 class="text-base font-bold text-white">Réserver un rendez-vous</h2>
                    <p class="text-xs mt-0.5" style="color:#9ca3af;">Votre demande sera confirmée par l'institut.</p>
                </div>
                <button @click="rdvOpen = false"
                        class="w-8 h-8 flex items-center justify-center rounded-lg transition"
                        style="color:#6b7280;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#6b7280'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="px-5 py-5 max-h-[80vh] overflow-y-auto">
                @if(session('success'))
                <div class="mb-4 p-3 rounded-xl text-sm" style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.25);color:#6ee7b7;">
                    {{ session('success') }}
                </div>
                @endif
                @if($errors->any())
                <div class="mb-4 p-3 rounded-xl text-sm" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:#fca5a5;">
                    @foreach($errors->all() as $err)<div>• {{ $err }}</div>@endforeach
                </div>
                @endif

                <form method="POST" action="{{ route('vitrine.reserver', $institut->slug) }}"
                      x-data="rdvVitrineForm({{ $prestationsFlat->toJson() }}, @json(old('prestations', [])))"
                      class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium mb-1" style="color:#d1d5db;">Nom complet *</label>
                            <input type="text" name="client_nom" required
                                   value="{{ old('client_nom') }}"
                                   placeholder="Ex : Awa Koné"
                                   class="rdv-input w-full px-3 py-2.5 rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1" style="color:#d1d5db;">Téléphone *</label>
                            <input type="tel" name="client_telephone" required
                                   value="{{ old('client_telephone') }}"
                                   placeholder="Ex : 07 00 00 00 00"
                                   class="rdv-input w-full px-3 py-2.5 rounded-lg text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1" style="color:#d1d5db;">Email (optionnel)</label>
                        <input type="email" name="client_email"
                               value="{{ old('client_email') }}"
                               placeholder="votre@email.com"
                               class="rdv-input w-full px-3 py-2.5 rounded-lg text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-medium mb-1" style="color:#d1d5db;">Prestation(s) souhaitée(s) *</label>
                        <template x-for="id in selectedIds" :key="'hi-'+id">
                            <input type="hidden" name="prestations[]" :value="id">
                        </template>
                        <div x-show="selectedIds.length > 0" x-cloak class="flex flex-wrap gap-1.5 mb-2">
                            <template x-for="id in selectedIds" :key="'chip-'+id">
                                <span class="inline-flex items-center gap-1 pl-2.5 pr-1 py-1 rounded-full text-xs font-semibold"
                                      style="background:rgba(147,51,234,0.25);color:#d8b4fe;border:1px solid rgba(147,51,234,0.4);">
                                    <span x-text="getNom(id)"></span>
                                    <button type="button" @click="toggle(id)"
                                            class="p-0.5 rounded-full hover:bg-white/10 transition">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </span>
                            </template>
                        </div>
                        <div @click.outside="open = false" class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none" style="color:#6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text" x-model="search"
                                   @focus="open = true"
                                   @input="open = true"
                                   placeholder="Rechercher ou choisir…"
                                   class="rdv-input w-full pl-9 pr-3 py-2.5 rounded-lg text-sm"
                                   autocomplete="off">
                            <div x-show="open" x-cloak
                                 class="absolute z-[60] w-full mt-1 rounded-xl shadow-2xl max-h-52 overflow-y-auto"
                                 style="background:#1a1a2e;border:1px solid rgba(255,255,255,0.1);">
                                <template x-for="p in filtered" :key="p.id">
                                    <label class="flex items-center gap-3 px-3 py-2.5 cursor-pointer transition-colors border-b last:border-0"
                                           :class="selectedIds.includes(String(p.id)) ? 'bg-purple-900/30' : 'hover:bg-white/5'"
                                           style="border-color:rgba(255,255,255,0.05);">
                                        <input type="checkbox"
                                               :checked="selectedIds.includes(String(p.id))"
                                               @change="toggle(String(p.id))"
                                               class="w-4 h-4 flex-shrink-0 rounded"
                                               style="accent-color:#9333ea;">
                                        <span class="flex-1 min-w-0">
                                            <span class="block text-sm font-medium" style="color:#f9fafb;" x-text="p.nom"></span>
                                            <span class="text-xs" style="color:#9ca3af;"
                                                  x-text="[p.categorie?.nom, p.duree ? p.duree + ' min' : null, p.prix ? new Intl.NumberFormat('fr-CI').format(p.prix) + ' F' : null].filter(Boolean).join(' · ')"></span>
                                        </span>
                                        <svg x-show="selectedIds.includes(String(p.id))"
                                             class="w-4 h-4 flex-shrink-0" style="color:#a855f7;"
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </label>
                                </template>
                                <p x-show="filtered.length === 0" class="text-sm text-center py-3" style="color:#6b7280;">Aucune prestation trouvée.</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium mb-1" style="color:#d1d5db;">Date et heure *</label>
                        <input type="datetime-local" name="debut_le" required
                               min="{{ now()->addHour()->format('Y-m-d\TH:i') }}"
                               value="{{ old('debut_le') }}"
                               class="rdv-input w-full px-3 py-2.5 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1" style="color:#d1d5db;">Notes (optionnel)</label>
                        <textarea name="notes" rows="2" maxlength="500"
                                  placeholder="Précisions, demandes particulières…"
                                  class="rdv-input w-full px-3 py-2.5 rounded-lg text-sm resize-none">{{ old('notes') }}</textarea>
                    </div>
                    <button type="submit"
                            class="w-full px-4 py-3 rounded-xl text-white font-bold text-sm transition hover:opacity-90 active:scale-[0.98]"
                            style="background:linear-gradient(135deg,#9333ea,#ec4899);">
                        Envoyer ma demande
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endif

</main>

{{-- FOOTER --}}
<footer class="mt-8 border-t py-6" style="border-color:rgba(255,255,255,0.06);">
    <div class="max-w-3xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs" style="color:#4b5563;">
        @if($institut->telephone)
        <a href="tel:{{ $institut->telephone }}" class="flex items-center gap-1.5 transition hover:text-white">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
            </svg>
            {{ $institut->telephone }}
        </a>
        @endif
        <p>Propulsé par <a href="{{ url('/') }}" class="hover:underline" style="color:#a855f7;">Maëlya Gestion</a></p>
    </div>
</footer>

<script>
function rdvVitrineForm(prestations, selectedIds) {
    return {
        prestations: prestations,
        selectedIds: (selectedIds || []).map(String),
        search: '',
        open: false,
        get filtered() {
            if (!this.search) return this.prestations;
            const q = this.search.toLowerCase();
            return this.prestations.filter(p => p.nom.toLowerCase().includes(q));
        },
        toggle(id) {
            const sid = String(id);
            const idx = this.selectedIds.indexOf(sid);
            idx === -1 ? this.selectedIds.push(sid) : this.selectedIds.splice(idx, 1);
        },
        getNom(id) {
            const p = this.prestations.find(p => String(p.id) === String(id));
            return p ? p.nom : '';
        },
    }
}
</script>

</body>
</html>
    <meta name="description" content="Découvrez les prestations et produits de {{ $institut->nom }}{{ $institut->ville ? ', ' . $institut->ville : '' }}.">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; background: #0f0f0f; color: #f5f5f5; min-height: 100vh; color-scheme: dark; }
        /* Force les champs de formulaire à rester en mode sombre */
        .rdv-input {
            background: rgba(255,255,255,0.08) !important;
            color: #f9fafb !important;
            border: 1px solid rgba(255,255,255,0.15) !important;
            color-scheme: dark;
        }
        .rdv-input::placeholder { color: #9ca3af !important; }
        .rdv-input:focus { outline: none; border-color: #a855f7 !important; box-shadow: 0 0 0 2px rgba(168,85,247,0.25); }
        .rdv-input option { background: #1f1f1f; color: #f9fafb; }
        /* Overlay modal */
        .rdv-overlay { background: rgba(0,0,0,0.75); backdrop-filter: blur(4px); }
    </style>
</head>
<body class="bg-gray-950 text-white min-h-screen"
      x-data="{ rdvOpen: {{ (session('success') || $errors->any()) && isset($prestationsFlat) && $prestationsFlat->isNotEmpty() ? 'true' : 'false' }} }"
      @keydown.escape.window="rdvOpen = false">

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
            <div class="flex items-center gap-2">
                <a href="tel:{{ $institut->telephone }}"
                   class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-2 rounded-xl text-white border border-white/20 hover:bg-white/5 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    Appeler
                </a>
                @if(isset($prestationsFlat) && $prestationsFlat->isNotEmpty())
                <button type="button"
                        @click="rdvOpen = true"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-2 rounded-xl text-white"
                        style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Prendre un RDV
                </button>
                @endif
            </div>
            @elseif(isset($prestationsFlat) && $prestationsFlat->isNotEmpty())
            <button type="button"
                    @click="rdvOpen = true"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-2 rounded-xl text-white"
                    style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Prendre un RDV
            </button>
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

        {{-- ── MODAL RÉSERVATION ─────────────────────────────────────── --}}
        @if(isset($prestationsFlat) && $prestationsFlat->isNotEmpty())
        <div x-show="rdvOpen"
             x-cloak
             class="fixed inset-0 z-50 flex items-end sm:items-center justify-center rdv-overlay"
             @click.self="rdvOpen = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            <div class="w-full sm:max-w-lg bg-gray-900 sm:rounded-2xl rounded-t-2xl shadow-2xl border border-white/10 overflow-hidden"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                {{-- En-tête du modal --}}
                <div class="flex items-center justify-between px-5 py-4 border-b border-white/10"
                     style="background: linear-gradient(135deg, rgba(147,51,234,0.2), rgba(236,72,153,0.2));">
                    <div>
                        <h2 class="text-base font-bold text-white">Réserver un rendez-vous</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Votre demande sera confirmée par l'institut.</p>
                    </div>
                    <button @click="rdvOpen = false"
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-white hover:bg-white/10 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Corps du formulaire --}}
                <div class="px-5 py-5 max-h-[80vh] overflow-y-auto">
                    @if(session('success'))
                        <div class="mb-4 p-3 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if($errors->any())
                        <div class="mb-4 p-3 rounded-lg bg-red-500/10 border border-red-500/30 text-red-300 text-sm">
                            @foreach($errors->all() as $err)<div>• {{ $err }}</div>@endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('vitrine.reserver', $institut->slug) }}"
                          x-data="rdvVitrineForm({{ $prestationsFlat->toJson() }}, @json(old('prestations', [])))"
                          class="space-y-3">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-gray-300 mb-1 font-medium">Nom complet *</label>
                                <input type="text" name="client_nom" required
                                       value="{{ old('client_nom') }}"
                                       placeholder="Ex : Awa Koné"
                                       class="rdv-input w-full px-3 py-2.5 rounded-lg text-sm">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-300 mb-1 font-medium">Téléphone *</label>
                                <input type="tel" name="client_telephone" required
                                       value="{{ old('client_telephone') }}"
                                       placeholder="Ex : 07 00 00 00 00"
                                       class="rdv-input w-full px-3 py-2.5 rounded-lg text-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-300 mb-1 font-medium">Email (optionnel)</label>
                            <input type="email" name="client_email"
                                   value="{{ old('client_email') }}"
                                   placeholder="votre@email.com"
                                   class="rdv-input w-full px-3 py-2.5 rounded-lg text-sm">
                        </div>

                        {{-- ── Sélecteur de prestations (search + checkbox) ── --}}
                        <div>
                            <label class="block text-xs text-gray-300 mb-1 font-medium">Prestation(s) souhaitée(s) *</label>

                            {{-- Hidden inputs pour soumission --}}
                            <template x-for="id in selectedIds" :key="'hi-'+id">
                                <input type="hidden" name="prestations[]" :value="id">
                            </template>

                            {{-- Chips sélectionnées --}}
                            <div x-show="selectedIds.length > 0" x-cloak class="flex flex-wrap gap-1.5 mb-2">
                                <template x-for="id in selectedIds" :key="'chip-'+id">
                                    <span class="inline-flex items-center gap-1 pl-2.5 pr-1 py-1 rounded-full text-xs font-semibold"
                                          style="background:rgba(147,51,234,0.25);color:#d8b4fe;border:1px solid rgba(147,51,234,0.4);">
                                        <span x-text="getNom(id)"></span>
                                        <button type="button" @click="toggle(id)"
                                                class="p-0.5 rounded-full hover:bg-white/10 transition">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </span>
                                </template>
                            </div>

                            {{-- Search + dropdown --}}
                            <div @click.outside="open = false" class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none" style="color:#6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <input type="text" x-model="search"
                                       @focus="open = true"
                                       @input="open = true"
                                       placeholder="Rechercher ou choisir une prestation…"
                                       class="rdv-input w-full pl-9 pr-3 py-2.5 rounded-lg text-sm"
                                       autocomplete="off">

                                {{-- Dropdown --}}
                                <div x-show="open" x-cloak
                                     class="absolute z-[60] w-full mt-1 rounded-xl shadow-2xl max-h-52 overflow-y-auto"
                                     style="background:#1e1e2e;border:1px solid rgba(255,255,255,0.12);">
                                    <template x-for="p in filtered" :key="p.id">
                                        <label class="flex items-center gap-3 px-3 py-2.5 cursor-pointer transition-colors border-b last:border-0"
                                               :class="selectedIds.includes(String(p.id))
                                                   ? 'bg-purple-900/30'
                                                   : 'hover:bg-white/5'"
                                               style="border-color:rgba(255,255,255,0.06);">
                                            <input type="checkbox"
                                                   :checked="selectedIds.includes(String(p.id))"
                                                   @change="toggle(String(p.id))"
                                                   class="w-4 h-4 flex-shrink-0 rounded"
                                                   style="accent-color:#9333ea;">
                                            <span class="flex-1 min-w-0">
                                                <span class="block text-sm font-medium" style="color:#f9fafb;" x-text="p.nom"></span>
                                                <span class="text-xs" style="color:#9ca3af;"
                                                      x-text="[p.categorie?.nom, p.duree ? p.duree + ' min' : null, p.prix ? new Intl.NumberFormat('fr-CI').format(p.prix) + ' F' : null].filter(Boolean).join(' · ')"></span>
                                            </span>
                                            <svg x-show="selectedIds.includes(String(p.id))"
                                                 class="w-4 h-4 flex-shrink-0" style="color:#a855f7;"
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </label>
                                    </template>
                                    <p x-show="filtered.length === 0"
                                       class="text-sm text-center py-3" style="color:#6b7280;">Aucune prestation trouvée.</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs text-gray-300 mb-1 font-medium">Date et heure *</label>
                            <input type="datetime-local" name="debut_le" required
                                   min="{{ now()->addHour()->format('Y-m-d\TH:i') }}"
                                   value="{{ old('debut_le') }}"
                                   class="rdv-input w-full px-3 py-2.5 rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-300 mb-1 font-medium">Notes (optionnel)</label>
                            <textarea name="notes" rows="2" maxlength="500"
                                      placeholder="Précisions, demandes particulières…"
                                      class="rdv-input w-full px-3 py-2.5 rounded-lg text-sm resize-none">{{ old('notes') }}</textarea>
                        </div>
                        <button type="submit"
                                class="w-full px-4 py-3 rounded-xl text-white font-semibold text-sm hover:opacity-90 active:scale-[0.98] transition"
                                style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                            Envoyer ma demande
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif

        {{-- ── AVIS CLIENTS ──────────────────────────────────────────── --}}
        @if($avis->count() > 0)
        <section id="avis" class="mt-12">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold text-white">Avis clients</h2>
                @if($noteMoyenne)
                    <div class="text-yellow-400 font-semibold">
                        ★ {{ number_format($noteMoyenne, 1) }} / 5
                        <span class="text-gray-400 text-sm">({{ $nbAvis }} avis)</span>
                    </div>
                @endif
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($avis as $a)
                    <div class="bg-white/5 border border-white/10 rounded-lg p-4">
                        <div class="text-yellow-400 mb-1">{{ str_repeat('★', (int)$a->note) }}{{ str_repeat('☆', 5 - (int)$a->note) }}</div>
                        @if($a->commentaire)
                            <p class="text-gray-200 text-sm mb-2">« {{ $a->commentaire }} »</p>
                        @endif
                        <p class="text-xs text-gray-400">— {{ $a->client_nom_snap ?: 'Client anonyme' }} · {{ $a->repondu_le?->format('d/m/Y') }}</p>
                    </div>
                @endforeach
            </div>
        </section>
        @endif

    </main>

    {{-- ── FOOTER ────────────────────────────────────────────────────────── --}}
    <footer class="mt-16 border-t border-white/5 py-6 text-center text-xs text-gray-600">
        <p>Propulsé par <a href="{{ url('/') }}" class="text-primary-400 hover:underline">Maëlya Gestion</a></p>
    </footer>

<script>
function rdvVitrineForm(prestations, selectedIds) {
    return {
        prestations: prestations,
        selectedIds: (selectedIds || []).map(String),
        search: '',
        open: false,

        get filtered() {
            if (!this.search) return this.prestations;
            const q = this.search.toLowerCase();
            return this.prestations.filter(p => p.nom.toLowerCase().includes(q));
        },

        toggle(id) {
            const sid = String(id);
            const idx = this.selectedIds.indexOf(sid);
            idx === -1 ? this.selectedIds.push(sid) : this.selectedIds.splice(idx, 1);
        },

        getNom(id) {
            const p = this.prestations.find(p => String(p.id) === String(id));
            return p ? p.nom : '';
        },
    }
}
</script>

</body>
</html>
