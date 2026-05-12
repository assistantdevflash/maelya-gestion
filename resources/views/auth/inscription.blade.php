<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer mon compte — Maëlya Gestion</title>
    <script>
        (function() {
            try {
                var t = localStorage.getItem('maelya-theme');
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (t === 'dark' || (t !== 'light' && prefersDark)) {
                    document.documentElement.classList.add('dark');
                }
            } catch(e) {}
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth min-h-screen bg-purple-50 dark:bg-gray-900">

    <div class="flex flex-col lg:flex-row min-h-screen">

        {{-- ═══ Panneau gauche — Branding ═══ --}}
        <div class="lg:flex-1 flex items-center justify-center px-6 py-12 lg:py-0">
            <div class="max-w-md text-center lg:text-left">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3 group mb-6">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center font-bold text-white text-2xl shadow-lg transition-transform group-hover:scale-105"
                         style="background: linear-gradient(135deg, #9333ea, #ec4899);">M</div>
                    <span class="text-2xl font-display font-bold text-gray-900 dark:text-white tracking-tight">Maëlya Gestion</span>
                </a>
                <h1 class="text-3xl lg:text-4xl font-display font-bold text-gray-900 dark:text-white leading-tight">
                    Créez votre espace en quelques minutes.
                </h1>
                <p class="text-lg text-gray-500 dark:text-gray-400 mt-4">
                    Configurez votre établissement et commencez à gérer vos rendez-vous, ventes et finances.
                </p>
            </div>
        </div>

        {{-- ═══ Panneau droit — Formulaire ═══ --}}
        <div class="lg:flex-1 flex items-center justify-center px-6 py-12 lg:py-0">
            <div class="w-full max-w-md">

                {{-- Card --}}
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-xl border border-gray-200 dark:border-slate-700"
                     x-data="{ step: {{ $errors->hasAny(['prenom','nom_famille','email','telephone','password','cgu']) ? 2 : 1 }}, showCgu: false }">

                    {{-- Progress --}}
                    <div class="flex items-center gap-3 mb-6">
                        <div class="flex items-center gap-2 flex-1">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold transition-all"
                                 :class="step >= 1 ? 'bg-primary-600 text-white' : 'bg-gray-200 dark:bg-slate-600 text-gray-500 dark:text-gray-400'">1</div>
                            <span class="text-xs font-medium" :class="step >= 1 ? 'text-primary-700 dark:text-primary-400' : 'text-gray-400'">Votre Établissement</span>
                        </div>
                        <div class="flex-1 h-0.5 rounded transition-colors" :class="step >= 2 ? 'bg-primary-400' : 'bg-gray-200 dark:bg-slate-600'"></div>
                        <div class="flex items-center gap-2 flex-1 justify-end">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold transition-all"
                                 :class="step >= 2 ? 'bg-primary-600 text-white' : 'bg-gray-200 dark:bg-slate-600 text-gray-500 dark:text-gray-400'">2</div>
                            <span class="text-xs font-medium" :class="step >= 2 ? 'text-primary-700 dark:text-primary-400' : 'text-gray-400'">Votre Compte</span>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('inscription.store') }}" id="inscription-form">
                        @csrf

                        {{-- ═══ Étape 1 : Institut ═══ --}}
                        <div x-show="step === 1"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 -translate-x-4"
                             x-transition:enter-end="opacity-100 translate-x-0"
                             x-transition:leave="transition ease-in duration-200"
                             x-transition:leave-start="opacity-100 translate-x-0"
                             x-transition:leave-end="opacity-0 -translate-x-4">
                            <h2 class="text-2xl font-display font-bold text-gray-900 dark:text-white text-center">Votre Établissement</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400 text-center mt-1 mb-6">Dites-nous en plus sur votre établissement.</p>

                            @if($errors->hasAny(['nom_institut','type_institut','ville','telephone_institut']))
                                <div class="alert-danger mb-4 text-sm">
                                    <ul class="space-y-1">
                                        @foreach(array_merge($errors->get('nom_institut'), $errors->get('type_institut'), $errors->get('ville'), $errors->get('telephone_institut')) as $e)
                                            <li>{{ $e }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom de l'établissement *</label>
                                    <input type="text" name="nom_institut" required maxlength="150"
                                           value="{{ old('nom_institut') }}"
                                           class="w-full px-4 py-3.5 rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white text-base placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:border-primary-400 focus:ring-2 focus:ring-primary-100 dark:focus:ring-primary-900 transition-all @error('nom_institut') border-red-400 @enderror"
                                           placeholder="Institut Beauté Prestige">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type d'établissement *</label>
                                    <select name="type_institut" required
                                            class="w-full px-4 py-3.5 rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white text-base focus:border-primary-400 focus:ring-2 focus:ring-primary-100 dark:focus:ring-primary-900 transition-all @error('type_institut') border-red-400 @enderror">
                                        <option value="">Choisir...</option>
                                        <option value="salon_coiffure"      {{ old('type_institut') === 'salon_coiffure'      ? 'selected' : '' }}>Salon de coiffure</option>
                                        <option value="institut_beaute"     {{ old('type_institut') === 'institut_beaute'     ? 'selected' : '' }}>Institut de beauté</option>
                                        <option value="nail_bar"            {{ old('type_institut') === 'nail_bar'            ? 'selected' : '' }}>Nail Bar / Onglerie</option>
                                        <option value="spa"                 {{ old('type_institut') === 'spa'                 ? 'selected' : '' }}>Spa / Bien-être</option>
                                        <option value="barbier"             {{ old('type_institut') === 'barbier'             ? 'selected' : '' }}>Barbier</option>
                                        <option value="hammam"              {{ old('type_institut') === 'hammam'              ? 'selected' : '' }}>Hammam</option>
                                        <option value="centre_esthetique"   {{ old('type_institut') === 'centre_esthetique'   ? 'selected' : '' }}>Centre esthétique</option>
                                        <option value="soins_capillaires"   {{ old('type_institut') === 'soins_capillaires'   ? 'selected' : '' }}>Soins capillaires</option>
                                        <option value="tatouage"            {{ old('type_institut') === 'tatouage'            ? 'selected' : '' }}>Tatouage / Piercing</option>
                                        <option value="boutique_mode"       {{ old('type_institut') === 'boutique_mode'       ? 'selected' : '' }}>Boutique de mode</option>
                                        <option value="autre"               {{ old('type_institut') === 'autre'               ? 'selected' : '' }}>Autre</option>
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ville *</label>
                                        <input type="text" name="ville" required maxlength="100"
                                               value="{{ old('ville') }}"
                                               class="w-full px-4 py-3.5 rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white text-base placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:border-primary-400 focus:ring-2 focus:ring-primary-100 dark:focus:ring-primary-900 transition-all @error('ville') border-red-400 @enderror"
                                               placeholder="Abidjan">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Téléphone</label>
                                        <input type="tel" name="telephone_institut" maxlength="20"
                                               value="{{ old('telephone_institut') }}"
                                               class="w-full px-4 py-3.5 rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white text-base placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:border-primary-400 focus:ring-2 focus:ring-primary-100 dark:focus:ring-primary-900 transition-all"
                                               placeholder="+225 ...">
                                    </div>
                                </div>
                            </div>

                            <button type="button" @click="step = 2"
                                    class="w-full py-3.5 text-base font-bold rounded-xl text-white shadow-lg transition-all duration-200 hover:shadow-xl hover:brightness-110 active:scale-[0.98] mt-6 flex items-center justify-center gap-2"
                                    style="background: linear-gradient(135deg, #9333ea 0%, #ec4899 100%);">
                                Étape suivante
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </button>
                        </div>

                        {{-- ═══ Étape 2 : Compte ═══ --}}
                        <div x-show="step === 2"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 translate-x-4"
                             x-transition:enter-end="opacity-100 translate-x-0"
                             x-transition:leave="transition ease-in duration-200"
                             x-transition:leave-start="opacity-100 translate-x-0"
                             x-transition:leave-end="opacity-0 translate-x-4">
                            <h2 class="text-2xl font-display font-bold text-gray-900 dark:text-white text-center">Votre Compte</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400 text-center mt-1 mb-6">Ce compte vous permettra de gérer votre établissement.</p>

                            @if($errors->hasAny(['prenom','nom_famille','email','telephone','password','cgu']))
                                <div class="alert-danger mb-4 text-sm">
                                    <ul class="space-y-1">
                                        @foreach(array_merge($errors->get('prenom'), $errors->get('nom_famille'), $errors->get('email'), $errors->get('telephone'), $errors->get('password'), $errors->get('cgu')) as $e)
                                            <li>{{ $e }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prénom *</label>
                                        <input type="text" name="prenom" required maxlength="80"
                                               value="{{ old('prenom') }}"
                                               class="w-full px-4 py-3.5 rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white text-base placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:border-primary-400 focus:ring-2 focus:ring-primary-100 dark:focus:ring-primary-900 transition-all @error('prenom') border-red-400 @enderror"
                                               placeholder="Aicha">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom *</label>
                                        <input type="text" name="nom_famille" required maxlength="80"
                                               value="{{ old('nom_famille') }}"
                                               class="w-full px-4 py-3.5 rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white text-base placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:border-primary-400 focus:ring-2 focus:ring-primary-100 dark:focus:ring-primary-900 transition-all @error('nom_famille') border-red-400 @enderror"
                                               placeholder="Koné">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email professionnel *</label>
                                    <input type="email" name="email" required maxlength="150"
                                           value="{{ old('email') }}"
                                           class="w-full px-4 py-3.5 rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white text-base placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:border-primary-400 focus:ring-2 focus:ring-primary-100 dark:focus:ring-primary-900 transition-all @error('email') border-red-400 @enderror"
                                           placeholder="aicha@prestige.ci">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mot de passe *</label>
                                    <div class="relative" x-data="{ show: false }">
                                        <input :type="show ? 'text' : 'password'" name="password" required minlength="8"
                                               class="w-full px-4 py-3.5 rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white text-base placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:border-primary-400 focus:ring-2 focus:ring-primary-100 dark:focus:ring-primary-900 pr-12 transition-all @error('password') border-red-400 @enderror"
                                               placeholder="Minimum 8 caractères">
                                        <button type="button" @click="show = !show"
                                                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                            <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirmer le mot de passe *</label>
                                    <input type="password" name="password_confirmation" required
                                           class="w-full px-4 py-3.5 rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white text-base placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:border-primary-400 focus:ring-2 focus:ring-primary-100 dark:focus:ring-primary-900 transition-all"
                                           placeholder="Répétez le mot de passe">
                                </div>

                                <div class="flex items-start gap-2 text-sm">
                                    <input type="checkbox" id="cgu" name="cgu" required
                                           class="mt-0.5 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                    <label for="cgu" class="text-gray-600 dark:text-gray-400">
                                        J'accepte les <button type="button" @click="showCgu = true" class="text-primary-600 dark:text-primary-400 hover:underline font-medium">conditions d'utilisation</button>.
                                    </label>
                                </div>

                                {{-- Code de parrainage (optionnel) --}}
                                <div x-data="{ showParrain: {{ old('code_parrainage') ? 'true' : 'false' }} }">
                                    <button type="button" @click="showParrain = !showParrain"
                                            class="text-xs text-primary-600 dark:text-primary-400 hover:underline font-medium flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                                        </svg>
                                        J'ai un code de parrainage ou commercial
                                    </button>
                                    <div x-show="showParrain" x-collapse class="mt-2">
                                        <input type="text" name="code_parrainage" maxlength="10"
                                               value="{{ old('code_parrainage', request('ref')) }}"
                                               class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white text-sm font-mono uppercase tracking-wider placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:border-primary-400 focus:ring-2 focus:ring-primary-100 dark:focus:ring-primary-900 transition-all @error('code_parrainage') border-red-400 @enderror"
                                               placeholder="Code parrainage ou commercial"
                                               oninput="this.value=this.value.toUpperCase()">
                                        @error('code_parrainage')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-3 mt-6">
                                <button type="button" @click="step = 1"
                                        class="flex-1 py-3.5 text-base font-bold rounded-xl border-2 border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-all duration-200 active:scale-[0.98] flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                                    </svg>
                                    Retour
                                </button>
                                <button type="submit"
                                        class="flex-1 py-3.5 text-base font-bold rounded-xl text-white shadow-lg transition-all duration-200 hover:shadow-xl hover:brightness-110 active:scale-[0.98] flex items-center justify-center gap-2"
                                        style="background: linear-gradient(135deg, #9333ea 0%, #ec4899 100%);">
                                    Créer mon compte
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="relative my-6">
                        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200 dark:border-slate-600"></div></div>
                        <div class="relative flex justify-center"><span class="bg-white dark:bg-slate-800 px-3 text-xs text-gray-400">ou</span></div>
                    </div>

                    <a href="{{ route('login') }}"
                       class="block w-full py-3.5 text-center text-base font-bold rounded-xl border-2 border-primary-500 text-primary-600 dark:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-all duration-200 active:scale-[0.98]">
                        Se connecter
                    </a>

                    {{-- ═══ Modal Conditions d'utilisation ═══ --}}
                    <div
                        x-show="showCgu"
                        x-cloak
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 z-[200] flex items-center justify-center p-4"
                        style="background: rgba(0,0,0,0.65);"
                        @keydown.escape.window="showCgu = false"
                    >
                        <div
                            @click.outside="showCgu = false"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-xl max-h-[85vh] flex flex-col border border-gray-200 dark:border-slate-700"
                        >
                            {{-- Header --}}
                            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-slate-700 flex-shrink-0">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <h2 class="text-base font-bold text-gray-900 dark:text-white">Conditions d'utilisation</h2>
                                </div>
                                <button @click="showCgu = false"
                                        class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 flex items-center justify-center text-gray-500 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            {{-- Contenu scrollable --}}
                            <div class="overflow-y-auto flex-1 px-6 py-5 space-y-5 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                                @foreach([
                                    ['1. Éditeur du site', 'Le site <strong>maelyagestion.com</strong> est édité par la société <strong>Maëlya Tech SARL</strong>, enregistrée en Côte d\'Ivoire, dont le siège social est situé à Abidjan.<br><br>Contact : <strong>contact@maelyagestion.com</strong>'],
                                    ['2. Hébergement', 'Le site est hébergé sur des serveurs sécurisés. Les données sont conservées en Afrique et/ou en Europe selon les exigences de sécurité et de performance.'],
                                    ['3. Protection des données personnelles', 'Maëlya Gestion collecte des données personnelles nécessaires au fonctionnement du service (nom, email, téléphone, données de gestion d\'institut). Ces données ne sont <strong>jamais revendues à des tiers</strong>.<br><br>Conformément à la législation applicable, vous disposez d\'un droit d\'accès, de rectification et de suppression de vos données. Pour exercer ce droit, contactez-nous à <strong>contact@maelyagestion.com</strong>'],
                                    ['4. Cookies', 'Ce site utilise des cookies techniques indispensables au bon fonctionnement du service (session, sécurité). <strong>Aucun cookie de tracking ou publicitaire</strong> n\'est utilisé sans votre consentement.'],
                                    ['5. Propriété intellectuelle', 'L\'ensemble des contenus du site (textes, logos, images, code) est la <strong>propriété exclusive de Maëlya Tech SARL</strong>. Toute reproduction sans autorisation préalable est interdite.'],
                                    ['6. Limitation de responsabilité', 'Maëlya Gestion ne peut être tenu responsable des dommages résultant d\'une utilisation de ce site ou d\'une indisponibilité temporaire du service. Nous nous efforçons d\'assurer une <strong>disponibilité maximale</strong>.'],
                                ] as [$titre, $texte])
                                <div>
                                    <p class="font-bold text-gray-900 dark:text-white mb-1.5">{{ $titre }}</p>
                                    <p>{!! $texte !!}</p>
                                </div>
                                @endforeach
                            </div>

                            {{-- Footer --}}
                            <div class="px-6 py-4 border-t border-gray-100 dark:border-slate-700 flex-shrink-0">
                                <button @click="showCgu = false; document.getElementById('cgu').checked = true"
                                        class="w-full py-3 text-sm font-bold rounded-xl text-white transition-all duration-200 hover:brightness-110 active:scale-[0.98]"
                                        style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                                    J'ai lu et j'accepte
                                </button>
                            </div>
                        </div>
                    </div>
                    {{-- ═══ Fin Modal ═══ --}}

                </div>

                <p class="text-center text-xs text-gray-400 dark:text-gray-500 mt-6">© {{ date('Y') }} Maëlya Gestion</p>
            </div>
        </div>
    </div>
    {{-- \u2550\u2550\u2550 (fin body) \u2550\u2550\u2550 --}}
</body>
</html>
