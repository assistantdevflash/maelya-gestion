<x-dashboard-layout>
@php
    // Repère de complétion : encourage à renseigner les champs qui servent
    // réellement sur les documents (factures, devis, vitrine).
    $champsFiche = [
        'Nom'       => $institut->nom,
        'Ville'     => $institut->ville,
        'Adresse'   => $institut->adresse,
        'Téléphone' => $institut->telephone,
        'Email'     => $institut->email,
        'Logo'      => $institut->logo,
        'Horaires'  => $institut->heure_ouverture && $institut->heure_fermeture,
        'RCCM'      => $institut->rccm,
        'NCC'       => $institut->numero_fiscal,
    ];
    $renseignes = collect($champsFiche)->filter(fn ($v) => filled($v))->count();
    $pctFiche   = (int) round($renseignes / max(1, count($champsFiche)) * 100);
    $typeLibelle = $etablissementTypes->firstWhere('code', $institut->type)?->libelle ?? $institut->type;
@endphp
<div class="space-y-6" x-data="{
    primaire: '{{ $institut->couleur_primaire }}',
    secondaire: '{{ $institut->couleur_secondaire }}',
    accent: '{{ $institut->couleur_accent }}',
    modifie: false
}">

    {{-- ─── En-tête ─── --}}
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div class="flex items-start gap-3 min-w-0">
            <a href="{{ route('dashboard.mes-instituts.index') }}"
               class="mt-1 p-2 text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 transition"
               aria-label="Retour aux établissements">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-2xl font-display font-bold text-gray-900 dark:text-white truncate">{{ $institut->nom }}</h1>
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">{{ $typeLibelle }}</span>
                    @if($institut->actif)
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">Actif</span>
                    @else
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-600 dark:bg-slate-800 dark:text-slate-300">Inactif</span>
                    @endif
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Fiche de l'établissement
                    @if($institut->ville) · {{ $institut->ville }} @endif
                    @if($institut->telephone) · {{ $institut->telephone }} @endif
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3 flex-shrink-0">
            <span x-show="modifie" x-cloak
                  class="hidden sm:inline-flex items-center gap-1.5 text-xs font-semibold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/50 px-3 py-1.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                Modifications non enregistrées
            </span>
            <button type="submit" form="form-etablissement" class="btn-primary whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Enregistrer
            </button>
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

    @if($errors->any())
    <div class="bg-red-50 dark:bg-red-950/40 border-2 border-red-200 dark:border-red-800/40 rounded-2xl p-5 flex items-start gap-4">
        <div class="w-10 h-10 bg-red-500 dark:bg-red-600 rounded-xl flex items-center justify-center text-white flex-shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <div class="text-red-800 dark:text-red-200 text-sm font-medium pt-1.5 space-y-1">
            @foreach($errors->all() as $erreur)
            <p>{{ $erreur }}</p>
            @endforeach
        </div>
    </div>
    @endif

    <form id="form-etablissement" method="POST" action="{{ route('dashboard.mes-instituts.update', $institut) }}"
          @input="modifie = true" @change="modifie = true" @submit="modifie = false">
    @csrf @method('PUT')
    {{-- Formulaire unique : toutes les sections modifiables sont ici. Le
         drapeau `_facturation` autorise l'écriture des réglages de facturation. --}}
    <input type="hidden" name="_facturation" value="1">

    <div class="grid lg:grid-cols-3 gap-6 items-start">
        {{-- Colonne principale --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- ─── Identité ─── --}}
            <section id="identite" class="card scroll-mt-24">
                <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 dark:border-slate-700">
                    <span class="w-9 h-9 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Identité de l'établissement</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Nom, coordonnées et type d'activité.</p>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="form-label">Nom de l'établissement *</label>
                            <input type="text" name="nom" required maxlength="100" class="form-input" value="{{ old('nom', $institut->nom) }}">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="form-label">Type d'établissement *</label>
                            <select name="type" required class="form-input">
                                @foreach($etablissementTypes as $type)
                                    <option value="{{ $type->code }}" {{ old('type', $institut->type) === $type->code ? 'selected' : '' }}>
                                        {{ $type->libelle }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Ville</label>
                            <input type="text" name="ville" maxlength="100" class="form-input" value="{{ old('ville', $institut->ville) }}">
                        </div>
                        <div>
                            <label class="form-label">Téléphone</label>
                            <input type="tel" name="telephone" maxlength="20" class="form-input" value="{{ old('telephone', $institut->telephone) }}" placeholder="07 XX XX XX XX">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="form-label">Adresse</label>
                            <input type="text" name="adresse" maxlength="255" class="form-input"
                                   value="{{ old('adresse', $institut->adresse) }}"
                                   placeholder="Ex : 8ème, en face de la Pharmacie des Arums">
                            <p class="text-xs text-gray-400 mt-1">Reprise sur vos factures, devis et documents PDF.</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" maxlength="150" class="form-input" value="{{ old('email', $institut->email) }}">
                        </div>
                    </div>
                </div>
            </section>

            {{-- ─── Logo ─── --}}
            <section id="logo" class="card scroll-mt-24">
                <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 dark:border-slate-700">
                    <span class="w-9 h-9 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Logo</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Affiché sur vos documents, vos emails et votre boutique.</p>
                    </div>
                </div>
                <div class="p-6 flex flex-col sm:flex-row sm:items-center gap-5">
                    @if($institut->logo)
                    <img src="{{ asset('storage/' . $institut->logo) }}" alt="Logo de {{ $institut->nom }}"
                         class="w-20 h-20 rounded-2xl object-cover ring-1 ring-gray-200 dark:ring-slate-700 flex-shrink-0">
                    @else
                    <div class="w-20 h-20 rounded-2xl flex items-center justify-center text-white font-bold text-2xl shadow-sm flex-shrink-0"
                         :style="'background: linear-gradient(135deg, ' + primaire + ', ' + secondaire + ')'">
                        {{ strtoupper(substr($institut->nom, 0, 2)) }}
                    </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <label class="form-label">Nouvelle image</label>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <input type="file" name="logo" accept="image/*" form="form-logo"
                                   class="form-input flex-1 text-sm">
                            <button type="submit" form="form-logo" class="btn-outline flex-shrink-0 whitespace-nowrap">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                Uploader
                            </button>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Carrée recommandée · JPG, PNG · Max 2 Mo</p>
                    </div>
                </div>
            </section>
        </div>

        {{-- Colonne latérale : apparence et repères --}}
        <aside class="space-y-6 lg:sticky lg:top-6 self-start">

            {{-- ─── Apparence ─── --}}
            <section id="apparence" class="card scroll-mt-24">
                <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100 dark:border-slate-700">
                    <span class="w-9 h-9 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Apparence</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Factures, emails, boutique.</p>
                    </div>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="form-label">Couleur principale</label>
                        <div class="flex items-center gap-2">
                            <input type="color" x-model="primaire" aria-label="Couleur principale"
                                   class="w-11 h-11 rounded-xl cursor-pointer border border-gray-200 dark:border-slate-600 p-0.5 bg-white dark:bg-slate-800">
                            <input type="text" x-model="primaire" name="couleur_primaire" maxlength="7" placeholder="#7c3aed"
                                   class="form-input w-28 font-mono text-sm">
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">Boutons, en-têtes, titres</p>
                    </div>
                    <div>
                        <label class="form-label">Couleur secondaire</label>
                        <div class="flex items-center gap-2">
                            <input type="color" x-model="secondaire" aria-label="Couleur secondaire"
                                   class="w-11 h-11 rounded-xl cursor-pointer border border-gray-200 dark:border-slate-600 p-0.5 bg-white dark:bg-slate-800">
                            <input type="text" x-model="secondaire" name="couleur_secondaire" maxlength="7" placeholder="#ec4899"
                                   class="form-input w-28 font-mono text-sm">
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">Badges, accents, survols</p>
                    </div>
                    <div>
                        <label class="form-label">Couleur d'accent</label>
                        <div class="flex items-center gap-2">
                            <input type="color" x-model="accent" aria-label="Couleur d'accent"
                                   class="w-11 h-11 rounded-xl cursor-pointer border border-gray-200 dark:border-slate-600 p-0.5 bg-white dark:bg-slate-800">
                            <input type="text" x-model="accent" name="couleur_accent" maxlength="7" placeholder="#f59e0b"
                                   class="form-input w-28 font-mono text-sm">
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">Icônes, liens, mises en avant</p>
                    </div>

                    {{-- Aperçu en direct --}}
                    <div class="pt-3 border-t border-gray-100 dark:border-slate-700 space-y-3">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Aperçu</p>
                        <div class="flex flex-wrap items-center gap-2">
                            <span :style="'background:' + primaire" class="text-white px-3 py-1.5 rounded-lg text-xs font-semibold">Bouton</span>
                            <span :style="'background:' + secondaire" class="text-white px-2.5 py-1 rounded-full text-[11px] font-bold">Badge</span>
                            <span :style="'color:' + accent" class="text-xs font-semibold underline">Lien</span>
                        </div>
                        <div :style="'background: linear-gradient(135deg, ' + primaire + ', ' + secondaire + ')'" class="p-3 rounded-xl">
                            <p class="text-white font-bold text-sm">En-tête facture</p>
                            <p class="text-white/70 text-xs">Information client</p>
                        </div>
                        @php $w = !\App\Helpers\CouleurHelper::estAccessible($institut->couleur_primaire); @endphp
                        @if($w)
                        <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-700 rounded-lg p-2.5 flex items-start gap-2">
                            <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <p class="text-[11px] text-amber-700 dark:text-amber-300">Couleur principale trop claire pour du texte blanc.</p>
                        </div>
                        @endif
                    </div>

                    <button type="submit" form="form-reset-couleurs" class="text-xs font-semibold text-gray-500 hover:text-gray-700 dark:text-slate-400 underline">
                        Réinitialiser les couleurs
                    </button>
                </div>
            </section>

            {{-- ─── Complétion ─── --}}
            <section class="card p-5">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Fiche complétée</h2>
                    <span class="text-sm font-bold text-primary-600 dark:text-primary-400">{{ $pctFiche }} %</span>
                </div>
                <div class="h-2 rounded-full bg-gray-100 dark:bg-slate-700 overflow-hidden">
                    <div class="h-full rounded-full bg-primary-500 transition-all" style="width: {{ $pctFiche }}%"></div>
                </div>
                <p class="text-[11px] text-gray-400 mt-2">{{ $renseignes }} / {{ count($champsFiche) }} informations renseignées</p>
                <ul class="mt-3 grid grid-cols-2 gap-1.5">
                    @foreach($champsFiche as $libelle => $valeur)
                    <li class="flex items-center gap-1.5 text-[11px] {{ filled($valeur) ? 'text-gray-500 dark:text-slate-400' : 'text-gray-400 dark:text-slate-500' }}">
                        @if(filled($valeur))
                        <svg class="w-3 h-3 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        @else
                        <span class="w-3 h-3 rounded-full border border-gray-300 dark:border-slate-600 flex-shrink-0"></span>
                        @endif
                        {{ $libelle }}
                    </li>
                    @endforeach
                </ul>
            </section>
        </aside>
    </div>

    {{-- ─── Exploitation et facturation ─── --}}
    <section id="exploitation" class="card scroll-mt-24 p-6 mt-6 space-y-5">
        <div class="flex items-start gap-3">
            <div class="w-9 h-9 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-base font-bold text-gray-900 dark:text-white">Horaires d'ouverture</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Utilisés pour refuser les rendez-vous hors créneaux (y compris les demandes venant de votre vitrine).
                    Laissez vide pour ne pas appliquer de restriction.
                </p>
            </div>
        </div>

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

            {{-- ─── Mentions légales ─── --}}
            <div class="pt-4 mt-2 border-t border-gray-100 dark:border-slate-700 space-y-3">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Mentions légales</p>
                <p class="text-[11px] text-gray-400 -mt-1">
                    Imprimées sur vos devis et factures. Obligatoires pour un document commercial
                    présenté à une administration ou à une entreprise.
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="rccm" class="form-label">RCCM</label>
                        <input type="text" name="rccm" id="rccm"
                               value="{{ old('rccm', $institut->rccm) }}"
                               placeholder="CI-ABJ-2020-B-12345"
                               class="form-input text-sm">
                    </div>
                    <div>
                        <label for="numero_fiscal" class="form-label">NCC (compte contribuable)</label>
                        <input type="text" name="numero_fiscal" id="numero_fiscal"
                               value="{{ old('numero_fiscal', $institut->numero_fiscal) }}"
                               placeholder="2012345678 A"
                               class="form-input text-sm">
                    </div>
                </div>
            </div>

            {{-- ─── Facturation ─── --}}
            <div class="pt-4 mt-2 border-t border-gray-100 dark:border-slate-700 space-y-3">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Facturation</p>

                <div>
                    <label class="form-label">Numérotation des factures</label>
                    <select name="facturation_sequence" class="form-input text-sm">
                        <option value="journaliere" {{ old('facturation_sequence', $institut->facturation_sequence ?? 'journaliere') === 'journaliere' ? 'selected' : '' }}>
                            Journalière — FAC-{{ now()->format('Ymd') }}-000001
                        </option>
                        <option value="annuelle" {{ old('facturation_sequence', $institut->facturation_sequence ?? 'journaliere') === 'annuelle' ? 'selected' : '' }}>
                            Annuelle — FAC-{{ now()->format('Y') }}-000001
                        </option>
                    </select>
                    <p class="text-[11px] text-gray-400 mt-1">La numérotation annuelle est celle attendue par la plupart des administrations fiscales.</p>
                </div>

                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" name="facture_relance_active" value="1"
                           {{ old('facture_relance_active', $institut->facture_relance_active) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Relancer automatiquement les factures impayées</span>
                </label>
                <p class="text-[11px] text-gray-400 -mt-2">Relances envoyées à J-3, puis J+1, J+7, J+15 et J+30 après l'échéance (une seule fois par palier).</p>

                <div class="pt-3 mt-1 border-t border-gray-100 dark:border-slate-700 space-y-2">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Gestion de stock</p>
                    <label class="flex items-start gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="stock_negatif_autorise" value="1"
                               {{ old('stock_negatif_autorise', $institut->stock_negatif_autorise) ? 'checked' : '' }}
                               class="mt-0.5 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span>
                            <span class="block text-sm text-gray-700 dark:text-gray-300">Autoriser la vente sans stock disponible</span>
                            <span class="block text-[11px] text-gray-400">Décoché (recommandé) : une vente est refusée si le stock est insuffisant. Coché : la vente passe et le stock devient négatif, à régulariser.</span>
                        </span>
                    </label>
                </div>

                <div>
                    <label class="form-label">Message de relance (facultatif)</label>
                    <textarea name="facture_relance_message" rows="3" class="form-textarea text-sm"
                              placeholder="Laissez vide pour utiliser le texte standard.">{{ old('facture_relance_message', $institut->facture_relance_message) }}</textarea>
                </div>
            </div>

    </section>

    {{-- ─── Barre d'action ─── --}}
    <div class="card p-4 mt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p class="text-xs text-gray-500 dark:text-gray-400">
            Les champs marqués <span class="font-semibold text-gray-700 dark:text-slate-300">*</span> sont obligatoires.
        </p>
        <div class="flex items-center gap-3">
            <span x-show="modifie" x-cloak class="text-xs font-semibold text-amber-600 dark:text-amber-400">
                Modifications non enregistrées
            </span>
            <button type="submit" class="btn-primary whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Enregistrer les modifications
            </button>
        </div>
    </div>
    </form>

    <form id="form-logo" method="POST" action="{{ route('dashboard.mes-instituts.logo', $institut) }}" enctype="multipart/form-data" class="hidden">
        @csrf
    </form>

    <form id="form-reset-couleurs" method="POST" action="{{ route('dashboard.mes-instituts.apparence.reset') }}" class="hidden">
        @csrf
        <input type="hidden" name="institut_id" value="{{ $institut->id }}">
    </form>
</div>
</x-dashboard-layout>
