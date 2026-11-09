@extends('boutique.layouts.app')

@section('title', 'Retrouver mes commandes — ' . $institut->nom)

@section('content')
<div class="min-h-screen bg-purple-50 dark:bg-gray-900 py-12">
    <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-6">
            <a href="{{ route('shop.index', $institut->slug) }}"
               class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Retour à la boutique
            </a>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6 sm:p-8">
            <div class="text-center mb-6">
                <div class="w-16 h-16 rounded-2xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Retrouver mes commandes</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    Indiquez l'email ou le numéro de téléphone utilisé lors de la commande :
                    nous vous renvoyons les liens de suivi.
                </p>
            </div>

            @if(session('success'))
            <div class="flex items-start gap-3 p-4 mb-5 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/40 rounded-xl text-sm text-emerald-800 dark:text-emerald-300">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <p>{{ session('success') }}</p>
            </div>
            @endif

            @if($errors->any())
            <div class="flex items-start gap-3 p-4 mb-5 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800/40 rounded-xl text-sm text-red-700 dark:text-red-300">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div>@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
            </div>
            @endif

            <form method="POST" action="{{ route('shop.commandes.envoyer', $institut->slug) }}" class="space-y-4">
                @csrf

                <div>
                    <label for="contact" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1.5">
                        Email ou téléphone
                    </label>
                    <input type="text" name="contact" id="contact" required autofocus
                           value="{{ old('contact') }}"
                           placeholder="awa@exemple.com ou 07 XX XX XX XX"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:border-transparent">
                </div>

                <button type="submit"
                        class="boutique-btn w-full py-3.5 rounded-xl font-semibold text-white shadow-lg transition-all">
                    Recevoir mes liens de suivi
                </button>
            </form>

            <p class="text-xs text-gray-400 dark:text-gray-500 mt-5 text-center leading-relaxed">
                Pour votre sécurité, aucune commande n'est affichée sur cette page :
                les liens sont envoyés à l'adresse email enregistrée lors de l'achat.
            </p>
        </div>

        @if($institut->telephone)
        <p class="text-center text-sm text-gray-500 dark:text-gray-400 mt-6">
            Besoin d'aide ?
            <a href="tel:{{ preg_replace('/\s+/', '', $institut->telephone) }}"
               class="font-semibold text-purple-600 dark:text-purple-400 underline">
                {{ $institut->telephone }}
            </a>
        </p>
        @endif
    </div>
</div>
@endsection
