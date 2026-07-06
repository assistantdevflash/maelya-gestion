<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>@yield('title', $institut->nom . ' - Boutique en ligne')</title>
    
    {{-- Meta tags de base --}}
    <meta name="description" content="{{ $description ?? 'Boutique en ligne - ' . $institut->nom }}">
    
    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $ogTitle ?? $institut->nom . ' - Boutique en ligne' }}">
    <meta property="og:description" content="{{ $ogDescription ?? 'Découvrez nos produits et passez commande en ligne' }}">
    <meta property="og:image" content="{{ $ogImage ?? asset('storage/' . $institut->logo) }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="{{ $institut->nom }}">
    <meta property="og:locale" content="fr_FR">
    
    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ url()->current() }}">
    <meta name="twitter:title" content="{{ $ogTitle ?? $institut->nom . ' - Boutique en ligne' }}">
    <meta name="twitter:description" content="{{ $ogDescription ?? 'Découvrez nos produits et passez commande en ligne' }}">
    <meta name="twitter:image" content="{{ $ogImage ?? asset('storage/' . $institut->logo) }}">
    
    {{-- WhatsApp / Mobile --}}
    <meta property="og:image:alt" content="{{ $institut->nom }}">
    <meta name="theme-color" content="#6366f1">
    
    {{-- Favicon --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>
    
    @stack('styles')
</head>
<body class="antialiased bg-gray-50 dark:bg-gray-900">
    @yield('content')
    
    @stack('scripts')
</body>
</html>
