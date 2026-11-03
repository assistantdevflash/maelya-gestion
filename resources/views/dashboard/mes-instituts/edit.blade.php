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

    <form method="POST" action="{{ route('dashboard.mes-instituts.update', $institut) }}">
    @csrf @method('PUT')
    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Colonne gauche : Infos + Logo --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="text-lg font-semibold text-gray-900 dark:text-white">Informations générales</h2></div>
                <div class="card-body space-y-4">
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
                            <label class="form-label">Adresse</label>
                            <input type="text" name="adresse" maxlength="255" class="form-input"
                                   value="{{ old('adresse', $institut->adresse) }}"
                                   placeholder="Ex : 8ème, en face de la Pharmacie des Arums">
                            <p class="text-xs text-gray-400 mt-1">Reprise sur vos factures, devis et documents PDF.</p>
                        </div>
                        <div class="col-span-2">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" maxlength="150" class="form-input" value="{{ old('email', $institut->email) }}">
                        </div>
                        <div class="col-span-2">
                            <label class="form-label">Type d'établissement *</label>
                            <select name="type" required class="form-input">
                                @foreach($etablissementTypes as $type)
                                    <option value="{{ $type->code }}" {{ old('type', $institut->type) === $type->code ? 'selected' : '' }}>
                                        {{ $type->libelle }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="pt-2">
                        <button type="submit" class="btn-primary">💾 Enregistrer les modifications</button>
                    </div>
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
                        <div class="w-20 h-20 rounded-xl flex items-center justify-center text-white font-bold text-2xl shadow-sm"
                             :style="'background: linear-gradient(135deg, ' + primaire + ', ' + secondaire + ')'">
                            {{ strtoupper(substr($institut->nom, 0, 2)) }}
                        </div>
                        @endif
                        <div class="flex-1">
                            <label class="form-label">Nouvelle image</label>
                            <div class="flex gap-3">
                                <input type="file" name="logo" accept="image/*" form="form-logo"
                                       class="form-input flex-1">
                                <button type="submit" form="form-logo" class="btn-outline flex-shrink-0">📤 Uploader</button>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Carrée recommandée · JPG, PNG · Max 2 Mo</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Colonne droite : Couleurs + Aperçu --}}
        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="text-lg font-semibold text-gray-900 dark:text-white">🎨 Couleurs</h2></div>
                <div class="card-body space-y-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Appliquées sur vos factures, emails et boutique en ligne.</p>

                    <div class="space-y-4">
                        <div>
                            <label class="form-label">Principale</label>
                            <div class="flex items-center gap-3">
                                <input type="color" x-model="primaire" class="w-12 h-12 rounded-xl cursor-pointer border-0 p-0">
                                <input type="text" x-model="primaire" name="couleur_primaire" maxlength="7" placeholder="#7c3aed"
                                       class="form-input w-28 font-mono text-sm">
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Boutons, en-têtes, titres</p>
                        </div>
                        <div>
                            <label class="form-label">Secondaire</label>
                            <div class="flex items-center gap-3">
                                <input type="color" x-model="secondaire" class="w-12 h-12 rounded-xl cursor-pointer border-0 p-0">
                                <input type="text" x-model="secondaire" name="couleur_secondaire" maxlength="7" placeholder="#ec4899"
                                       class="form-input w-28 font-mono text-sm">
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Badges, accents, survols</p>
                        </div>
                        <div>
                            <label class="form-label">Accent</label>
                            <div class="flex items-center gap-3">
                                <input type="color" x-model="accent" class="w-12 h-12 rounded-xl cursor-pointer border-0 p-0">
                                <input type="text" x-model="accent" name="couleur_accent" maxlength="7" placeholder="#f59e0b"
                                       class="form-input w-28 font-mono text-sm">
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Icônes, liens, mises en avant</p>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="btn-primary text-sm flex-1">💾 Tout enregistrer</button>
                        <button type="submit" form="form-reset-couleurs" class="btn-outline text-sm flex-1">↺ Défaut</button>
                    </div>
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
    </form>

    {{-- Horaires d'ouverture (contrôle des rendez-vous) --}}
    <div class="card p-5 space-y-4">
        <div class="flex items-start gap-3">
            <div class="w-9 h-9 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-bold text-gray-900 dark:text-white">Horaires d'ouverture</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Utilisés pour refuser les rendez-vous hors créneaux (y compris les demandes venant de votre vitrine).
                    Laissez vide pour ne pas appliquer de restriction.
                </p>
            </div>
        </div>

        <form method="POST" action="{{ route('dashboard.mes-instituts.update', $institut) }}" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="nom" value="{{ $institut->nom }}">
            <input type="hidden" name="type" value="{{ $institut->type }}">
            <input type="hidden" name="ville" value="{{ $institut->ville }}">
            <input type="hidden" name="adresse" value="{{ $institut->adresse }}">
            <input type="hidden" name="telephone" value="{{ $institut->telephone }}">
            <input type="hidden" name="email" value="{{ $institut->email }}">
            <input type="hidden" name="couleur_primaire" value="{{ $institut->couleur_primaire }}">
            <input type="hidden" name="couleur_secondaire" value="{{ $institut->couleur_secondaire }}">
            <input type="hidden" name="couleur_accent" value="{{ $institut->couleur_accent }}">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label">Ouverture</label>
                    <input type="time" name="heure_ouverture" value="{{ old('heure_ouverture', $institut->heure_ouverture ? substr($institut->heure_ouverture, 0, 5) : '') }}" class="form-input text-sm">
                </div>
                <div>
                    <label class="form-label">Fermeture</label>
                    <input type="time" name="heure_fermeture" value="{{ old('heure_fermeture', $institut->heure_fermeture ? substr($institut->heure_fermeture, 0, 5) : '') }}" class="form-input text-sm">
                </div>
            </div>

            <div>
                <label class="form-label">Jours d'ouverture</label>
                <div class="flex flex-wrap gap-2 mt-1">
                    @foreach([1 => 'Lun', 2 => 'Mar', 3 => 'Mer', 4 => 'Jeu', 5 => 'Ven', 6 => 'Sam', 7 => 'Dim'] as $num => $libelle)
                    @php $coches = old('jours_ouverture', $institut->jours_ouverture ?: [1,2,3,4,5,6]); @endphp
                    <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-gray-200 dark:border-slate-600 text-xs font-semibold cursor-pointer has-[:checked]:bg-primary-50 has-[:checked]:border-primary-300 has-[:checked]:text-primary-700">
                        <input type="checkbox" name="jours_ouverture[]" value="{{ $num }}"
                               {{ in_array($num, array_map('intval', (array) $coches), true) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        {{ $libelle }}
                    </label>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary text-sm">Enregistrer les horaires</button>
            </div>
        </form>
    </div>

    <form id="form-logo" method="POST" action="{{ route('dashboard.mes-instituts.logo', $institut) }}" enctype="multipart/form-data" class="hidden">
        @csrf
    </form>

    <form id="form-reset-couleurs" method="POST" action="{{ route('dashboard.mes-instituts.apparence.reset') }}" class="hidden">
        @csrf
        <input type="hidden" name="institut_id" value="{{ $institut->id }}">
    </form>
</div>
</x-dashboard-layout>
