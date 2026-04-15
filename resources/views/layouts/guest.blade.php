<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Maëlya Gestion') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 relative overflow-hidden"
             style="background: linear-gradient(135deg, #1e1b4b 0%, #4c1d95 35%, #831843 100%);">

            {{-- Background decorations --}}
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute -top-40 -right-40 w-[500px] h-[500px] bg-primary-500/20 rounded-full blur-[100px]"></div>
                <div class="absolute -bottom-40 -left-20 w-[400px] h-[400px] bg-secondary-500/15 rounded-full blur-[100px]"></div>
            </div>

            <div class="relative z-10">
                <a href="/" class="inline-flex items-center gap-2.5 group mb-6">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-white text-lg shadow-glow transition-transform group-hover:scale-105"
                         style="background: linear-gradient(135deg, #9333ea, #ec4899);">M</div>
                    <span class="text-xl font-display font-bold text-white tracking-tight">Maëlya Gestion</span>
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-4 px-5 sm:px-7 py-6 bg-white shadow-float overflow-hidden rounded-2xl relative z-10">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
