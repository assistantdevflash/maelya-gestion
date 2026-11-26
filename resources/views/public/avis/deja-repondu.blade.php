<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avis déjà envoyé</title>
    @include('partials.favicons')
    @php
        $titrePartage = $institut ? 'Avis déjà enregistré — ' . $institut->nom : 'Avis déjà enregistré — Maëlya Gestion';
        $descriptionPartage = 'Vous avez déjà répondu à ce sondage. Merci d\'avoir partagé votre avis.';
    @endphp
    @include('public.partials.partage', compact('institut', 'titrePartage', 'descriptionPartage'))
    @vite('resources/css/app.css')
</head>
<body class="bg-gradient-to-br from-pink-50 to-rose-50 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-8 text-center">
        <div class="text-6xl mb-4">✅</div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Avis déjà enregistré</h1>
        <p class="text-gray-600">Vous avez déjà répondu à ce sondage. Merci !</p>
    </div>
</body>
</html>
