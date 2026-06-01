<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carte fidélité — {{ $client->prenom }}</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-gradient-to-br from-rose-100 via-pink-50 to-amber-50 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden">
        {{-- Header --}}
        <div class="bg-gradient-to-r from-pink-500 to-rose-500 text-white p-6 text-center">
            @if($institut)
                <p class="text-sm opacity-90 mb-1">{{ $institut->nom }}</p>
            @endif
            <h1 class="text-2xl font-bold">Carte fidélité</h1>
        </div>

        {{-- Solde --}}
        <div class="p-6 text-center border-b border-gray-100">
            <p class="text-gray-500 text-sm uppercase tracking-wide">Solde</p>
            <p class="text-5xl font-bold text-pink-600 my-2">{{ $client->points_fidelite }}</p>
            <p class="text-gray-700 font-semibold">{{ $client->prenom }} {{ $client->nom }}</p>
        </div>

        {{-- QR --}}
        <div class="p-6 flex justify-center border-b border-gray-100">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode(route('public.carte-fidelite', $client->fidelite_token)) }}"
                 alt="QR carte fidélité"
                 width="200" height="200"
                 class="rounded-lg border border-gray-200">
        </div>

        {{-- Dernières visites --}}
        @if($derniereVisites->count())
        <div class="p-6">
            <h2 class="text-sm font-semibold text-gray-600 uppercase mb-3">Dernières visites</h2>
            <ul class="space-y-2 text-sm">
                @foreach($derniereVisites as $v)
                    <li class="flex justify-between text-gray-700">
                        <span>{{ $v->created_at->format('d/m/Y') }}</span>
                        <span class="font-medium">{{ number_format($v->total, 0, ',', ' ') }} F</span>
                    </li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="bg-gray-50 px-6 py-3 text-center text-xs text-gray-500">
            Présentez ce QR à votre prochaine visite ✨
        </div>
    </div>
</body>
</html>
