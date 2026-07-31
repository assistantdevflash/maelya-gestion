<x-dashboard-layout>
<div class="space-y-8">
    <div>
        <h1 class="text-3xl font-display font-bold text-gray-900 dark:text-white tracking-tight">Apparence</h1>
        <p class="text-gray-500 dark:text-slate-400 mt-2">Personnalisez les couleurs de vos factures, emails et boutique en ligne</p>
    </div>

    @if(session('success'))
    <div class="bg-emerald-50 dark:bg-emerald-950/40 border-2 border-emerald-200 dark:border-emerald-800/40 rounded-2xl p-5 flex items-start gap-4">
        <div class="w-10 h-10 bg-emerald-500 dark:bg-emerald-600 rounded-xl flex items-center justify-center text-white flex-shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <p class="text-emerald-800 dark:text-emerald-200 font-medium pt-1.5">{{ session('success') }}</p>
    </div>
    @endif

    <div x-data="{
        primaire: '{{ $institut->couleur_primaire }}',
        secondaire: '{{ $institut->couleur_secondaire }}',
        accent: '{{ $institut->couleur_accent }}'
    }">
        <form method="POST" action="{{ route('dashboard.mes-instituts.apparence.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Couleur principale --}}
                <div class="card">
                    <div class="card-body text-center space-y-4">
                        <div class="w-16 h-16 rounded-2xl mx-auto flex items-center justify-center" :style="'background:' + primaire">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Principale</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Boutons, en-têtes, titres</p>
                        </div>
                        <input type="color" x-model="primaire" name="couleur_primaire" class="w-full h-12 rounded-xl cursor-pointer border-0">
                        <code class="text-sm font-mono text-gray-500 dark:text-gray-400" x-text="primaire"></code>
                    </div>
                </div>

                {{-- Couleur secondaire --}}
                <div class="card">
                    <div class="card-body text-center space-y-4">
                        <div class="w-16 h-16 rounded-2xl mx-auto flex items-center justify-center" :style="'background:' + secondaire">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Secondaire</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Badges, accents, survols</p>
                        </div>
                        <input type="color" x-model="secondaire" name="couleur_secondaire" class="w-full h-12 rounded-xl cursor-pointer border-0">
                        <code class="text-sm font-mono text-gray-500 dark:text-gray-400" x-text="secondaire"></code>
                    </div>
                </div>

                {{-- Couleur d'accent --}}
                <div class="card">
                    <div class="card-body text-center space-y-4">
                        <div class="w-16 h-16 rounded-2xl mx-auto flex items-center justify-center" :style="'background:' + accent">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Accent</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Icônes, liens, mises en avant</p>
                        </div>
                        <input type="color" x-model="accent" name="couleur_accent" class="w-full h-12 rounded-xl cursor-pointer border-0">
                        <code class="text-sm font-mono text-gray-500 dark:text-gray-400" x-text="accent"></code>
                    </div>
                </div>
            </div>

            {{-- Aperçu --}}
            <div class="card">
                <div class="card-header">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">📱 Aperçu en temps réel</h2>
                </div>
                <div class="card-body space-y-4">
                    <div class="flex flex-wrap gap-3">
                        <div :style="'background:' + primaire" class="text-white px-5 py-2.5 rounded-xl font-semibold text-sm transition">Bouton principal</div>
                        <div :style="'background:' + secondaire" class="text-white px-4 py-1.5 rounded-full text-xs font-bold">Badge</div>
                        <a href="#" :style="'color:' + accent" class="text-sm font-medium hover:underline">Lien accent</a>
                    </div>
                    <div class="p-4 rounded-xl" :style="'background: linear-gradient(135deg, ' + primaire + ', ' + secondaire + ')'">
                        <p class="text-white font-bold text-lg">En-tête de facture</p>
                        <p class="text-white/80 text-sm">Sous-titre ou information client</p>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Cet aperçu montre comment vos couleurs apparaîtront sur les factures, emails et la boutique.
                    </div>
                </div>
            </div>

            @php
                $warning = !\App\Helpers\CouleurHelper::estAccessible($institut->couleur_primaire);
            @endphp
            @if($warning)
            <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-700 rounded-xl p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                <div>
                    <p class="text-sm font-medium text-amber-800 dark:text-amber-200">Contraste insuffisant</p>
                    <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">La couleur principale est trop claire pour du texte blanc. Choisissez une couleur plus foncée pour une meilleure lisibilité.</p>
                </div>
            </div>
            @endif

            <div class="flex gap-3">
                <button type="submit" class="btn-primary">💾 Enregistrer les couleurs</button>
                <form method="POST" action="{{ route('dashboard.mes-instituts.apparence.reset') }}" class="inline">
                    @csrf
                    <button type="submit" class="btn-outline">↺ Réinitialiser aux défauts</button>
                </form>
            </div>
        </form>
    </div>
</div>
</x-dashboard-layout>
