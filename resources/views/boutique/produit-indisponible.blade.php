@php $ogUrl = url('/shop/' . $institut->slug); @endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $institut->nom }} - Produit indisponible</title>
    <meta property="og:title" content="{{ $institut->nom }} - Produit indisponible">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-purple-50 dark:bg-slate-900 min-h-screen flex items-center justify-center p-4">
    <div class="text-center max-w-md">
        <div class="w-20 h-20 bg-purple-100 dark:bg-purple-900/30 rounded-3xl flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
        </div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Produit indisponible</h1>
        <p class="text-gray-500 dark:text-slate-400 mb-6">Ce produit n'est plus disponible ou a été retiré de la boutique.</p>
        <a href="/shop/{{ $institut->slug }}" class="inline-flex items-center gap-2 px-6 py-3 bg-purple-600 text-white rounded-xl font-semibold hover:bg-purple-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Voir la boutique
        </a>
        <p class="text-xs text-gray-400 mt-6">{{ $institut->nom }} — Boutique en ligne</p>
    </div>
</body>
</html>
