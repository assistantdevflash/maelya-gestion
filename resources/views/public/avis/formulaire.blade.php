<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donnez votre avis</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-gradient-to-br from-pink-50 to-rose-50 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">
            Votre avis compte ! 💖
        </h1>
        @if($institut)
            <p class="text-gray-600 mb-6">
                Aidez <strong>{{ $institut->nom }}</strong> à s'améliorer en notant votre dernière visite.
            </p>
        @endif

        <form method="POST" action="{{ route('public.avis.submit', $avis->token) }}" class="space-y-5">
            @csrf
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Note (1 à 5) *</label>
                <div class="flex gap-3" x-data="{ n: 0 }">
                    @for($i=1; $i<=5; $i++)
                        <label class="cursor-pointer">
                            <input type="radio" name="note" value="{{ $i }}" required class="sr-only" @change="n={{ $i }}">
                            <span class="text-4xl" :class="n>={{ $i }} ? 'opacity-100' : 'opacity-30'">⭐</span>
                        </label>
                    @endfor
                </div>
                @error('note') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Commentaire (optionnel)</label>
                <textarea name="commentaire" rows="4" maxlength="1000"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-pink-400 focus:outline-none">{{ old('commentaire') }}</textarea>
            </div>
            <button type="submit"
                class="w-full bg-pink-500 hover:bg-pink-600 text-white font-semibold py-3 rounded-lg transition">
                Envoyer mon avis
            </button>
        </form>
    </div>
    <script src="//unpkg.com/alpinejs" defer></script>
</body>
</html>
