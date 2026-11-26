{{--
    Icônes du site — source unique.

    Google (résultats de recherche) et Chrome Android ne lisent PAS les
    favicons SVG : ils génèrent alors une pastille avec l'initiale du site,
    d'où le « M » sur fond violet qui apparaissait partout. On déclare donc
    d'abord l'ICO et les PNG, puis le SVG pour les navigateurs qui le gèrent.
--}}
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
<link rel="icon" type="image/png" sizes="48x48" href="{{ asset('favicon-48x48.png') }}">
<link rel="icon" type="image/png" sizes="96x96" href="{{ asset('favicon-96x96.png') }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('favicon-192x192.png') }}">
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/apple-touch-icon.png') }}">
