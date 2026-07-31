<x-dashboard-layout>
<div class="space-y-8" x-data="{
    primaire: '{{ $institut->couleur_primaire }}',
    secondaire: '{{ $institut->couleur_secondaire }}',
    accent: '{{ $institut->couleur_accent }}'
}">
    <div class="flex items-center gap-4">
        <a href="{{ route('dashboard.mes-instituts.index') }}" class="p-2 text-gray-400 hover:text-gray-600 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 transition">←</a>
        <div>
            <h1 class="text-2xl font-display font-bold text-gray-900 dark:text-white">{{ $institut->nom }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Modifier les informations de l'établissement</p>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-emerald-50 dark:bg-emerald-950/40 border-2 border-emerald-200 dark:border-emerald-800/40 rounded-2xl p-5 flex items-start gap-4">
        <div class="w-10 h-10 bg-emerald-500 dark:bg-emerald-600 rounded-xl flex items-center justify-center text-white flex-shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <p class="text-emerald-800 dark:text-emerald-200 font-medium pt-1.5">{{ session('success') }}</p>
    </div>
    @endif

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Formulaire principal --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="text-lg font-semibold text-gray-900 dark:text-white">Informations générales</h2></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('dashboard.mes-instituts.update', $institut) }}" class="space-y-4">
                        @csrf @method('PUT')
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2">
                                <label class="form-label">Nom de l'établissement *</label>
                                <input type="text" name="nom" required maxlength="100" class="form-input" value="{{ old('nom', $institut->nom) }}">
                            </div>
                            <div>
                                <label class="form-label">Ville</label>
                                <input type="text" name="ville" maxlength="100" class="form-input" value="{{ old('ville', $institut->ville) }}">
                            </div>
                            <div>
                                <label class="form-label">Téléphone</label>
                                <input type="text" name="telephone" maxlength="20" class="form-input" value="{{ old('telephone', $institut->telephone) }}">
                            </div>
                            <div class="col-span-2">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" maxlength="150" class="form-input" value="{{ old('email', $institut->email) }}">
                            </div>
                            <div class="col-span-2">
                                <label class="form-label">Type d'établissement *</label>
                                <select name="type" required class="form-input">
                                    @foreach(['salon_coiffure'=>'Salon de coiffure','institut_beaute'=>'Institut de beauté','barbier'=>'Barbier','centre_esthetique'=>'Centre esthétique','boutique_mode'=>'Boutique de mode','imprimerie'=>'Imprimerie','lavage_auto'=>'Lavage auto','pressing'=>'Pressing / Laverie','business_center'=>'Business center','depot_gaz'=>'Dépôt de gaz','commerce'=>'Commerce / Alimentation','evenementiel'=>'Évènementiel','informatique_telephonie'=>'Informatique / Téléphonie','autre'=>'Autre'] as $v=>$l)
                                    <option value="{{ $v }}" {{ old('type', $institut->type) === $v ? 'selected' : '' }}>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="pt-2">
                            <button type="submit" class="btn-primary">💾 Enregistrer les modifications</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Logo --}}
            <div class="card">
                <div class="card-header"><h2 class="text-lg font-semibold text-gray-900 dark:text-white">Logo</h2></div>
                <div class="card-body">
                    <div class="flex items-center gap-4">
                        @if($institut->logo)
                        <img src="{{ asset('storage/' . $institut->logo) }}" alt="Logo" class="w-20 h-20 rounded-xl object-cover ring-2 ring-gray-200">
                        @else
                        <div class="w-20 h-20 rounded-xl flex items-center justify-center text-white font-bold text-2xl shadow-sm" style="background: linear-gradient(135deg, var(--couleur-primaire), var(--couleur-secondaire));">
                            {{ strtoupper(substr($institut->nom, 0, 2)) }}
                        </div>
                        @endif
                        <form method="POST" action="{{ route('dashboard.mes-instituts.logo', $institut) }}" enctype="multipart/form-data" class="flex-1">
                            @csrf
                            <label class="form-label">Nouvelle image</label>
                            <div class="flex gap-3">
                                <input type="file" name="logo" accept="image/*" required class="form-input flex-1">
                                <button type="submit" class="btn-outline flex-shrink-0">📤 Uploader</button>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Carrée recommandée · JPG, PNG · Max 2 Mo</p>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar : Couleurs --}}
        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="text-lg font-semibold text-gray-900 dark:text-white">🎨 Couleurs</h2></div>
                <div class="card-body space-y-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Appliquées sur vos factures, emails et boutique en ligne.</p>

                    <form method="POST" action="{{ route('dashboard.mes-instituts.apparence.update') }}" class="space-y-4">
                        @csrf @method('PUT')
                        <div>
                            <label class="form-label">Principale</label>
                            <div class="flex items-center gap-3">
                                <input type="color" x-model="primaire" name="couleur_primaire" class="w-12 h-12 rounded-xl cursor-pointer border-0 p-0">
                                <code class="text-sm font-mono text-gray-500 dark:text-gray-400" x-text="primaire"></code>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Boutons, en-têtes, titres</p>
                        </div>
                        <div>
                            <label class="form-label">Secondaire</label>
                            <div class="flex items-center gap-3">
                                <input type="color" x-model="secondaire" name="couleur_secondaire" class="w-12 h-12 rounded-xl cursor-pointer border-0 p-0">
                                <code class="text-sm font-mono text-gray-500 dark:text-gray-400" x-text="secondaire"></code>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Badges, accents, survols</p>
                        </div>
                        <div>
                            <label class="form-label">Accent</label>
                            <div class="flex items-center gap-3">
                                <input type="color" x-model="accent" name="couleur_accent" class="w-12 h-12 rounded-xl cursor-pointer border-0 p-0">
                                <code class="text-sm font-mono text-gray-500 dark:text-gray-400" x-text="accent"></code>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Icônes, liens, mises en avant</p>
                        </div>
                        <div class="flex gap-3 pt-2">
                            <button type="submit" class="btn-primary text-sm">💾 Appliquer</button>
                            <form method="POST" action="{{ route('dashboard.mes-instituts.apparence.reset') }}" class="inline">
                                @csrf
                                <button type="submit" class="btn-outline text-sm">↺ Défaut</button>
                            </form>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Aperçu couleurs --}}
            <div class="card">
                <div class="card-header"><h2 class="text-lg font-semibold text-gray-900 dark:text-white">📱 Aperçu</h2></div>
                <div class="card-body space-y-4">
                    <div class="flex flex-wrap gap-2">
                        <span :style="'background:' + primaire" class="text-white px-4 py-2 rounded-lg text-sm font-semibold transition">Bouton</span>
                        <span :style="'background:' + secondaire" class="text-white px-3 py-1 rounded-full text-xs font-bold">Badge</span>
                        <span :style="'color:' + accent" class="text-sm font-medium cursor-pointer hover:underline">Lien</span>
                    </div>
                    <div :style="'background: linear-gradient(135deg, ' + primaire + ', ' + secondaire + ')'" class="p-4 rounded-xl">
                        <p class="text-white font-bold">En-tête facture</p>
                        <p class="text-white/70 text-sm">Information client</p>
                    </div>
                    @php $w = !\App\Helpers\CouleurHelper::estAccessible($institut->couleur_primaire); @endphp
                    @if($w)
                    <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-700 rounded-lg p-3 flex items-start gap-2">
                        <span class="text-amber-500 text-sm">⚠️</span>
                        <p class="text-xs text-amber-700 dark:text-amber-300">Couleur principale trop claire pour du texte blanc.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
</x-dashboard-layout>
