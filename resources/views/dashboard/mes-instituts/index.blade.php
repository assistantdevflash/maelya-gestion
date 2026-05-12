<x-dashboard-layout>
    <div class="space-y-5">

        {{-- En-tête --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-display font-bold text-gray-900 tracking-tight">Mes établissements</h1>
                <p class="text-sm text-gray-500 mt-1">
                    @if($maxInstituts === null)
                        Plan Premium+ — établissements illimités
                    @else
                        {{ $instituts->count() }} / {{ $maxInstituts }} institut{{ $maxInstituts > 1 ? 's' : '' }}
                    @endif
                </p>
            </div>
            @if($peutCreer)
            <button x-data @click="$dispatch('open-create-modal')" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Créer un établissement
            </button>
            @endif
        </div>

        @if(session('success'))
        <div class="flex items-center gap-3 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-sm text-emerald-700">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('success') }}
        </div>
        @endif

        {{-- Bannière plan si pas Entreprise --}}
        @if(!$peutCreer && $maxInstituts !== null && $instituts->count() >= $maxInstituts)
        <div class="p-4 rounded-xl border border-primary-200 flex items-center gap-4"
             style="background: linear-gradient(135deg, rgba(147,51,234,0.04), rgba(236,72,153,0.04));">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background: linear-gradient(135deg, rgba(147,51,234,0.12), rgba(236,72,153,0.12));">
                <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-sm font-semibold text-gray-900">Passez au plan Premium+ pour plusieurs établissements</p>
                <p class="text-xs text-gray-500 mt-0.5">Votre plan actuel autorise {{ $maxInstituts }} établissement. Le plan Premium+ permet des établissements illimités avec employés illimités.</p>
            </div>
            <a href="{{ route('abonnement.plans') }}" class="btn-primary text-sm flex-shrink-0">Voir les plans</a>
        </div>

        {{-- Onboarding Entreprise : 1 seul institut, peut en créer plus --}}
        @elseif($peutCreer && $instituts->count() <= 1)
        <div class="rounded-2xl p-5 border"
             style="background: linear-gradient(135deg, rgba(147,51,234,0.04) 0%, rgba(236,72,153,0.04) 100%); border-color: rgba(147,51,234,0.15);">
            <div class="flex flex-col sm:flex-row sm:items-center gap-5">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-sm"
                     style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <p class="font-bold text-gray-900">Gérez plusieurs établissements depuis un seul compte</p>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full text-white flex-shrink-0"
                              style="background: linear-gradient(135deg, #9333ea, #ec4899);">Premium+</span>
                    </div>
                    <p class="text-sm text-gray-500 mb-3">
                        Votre plan Premium+ vous permet de créer autant d'établissements que vous souhaitez. Chaque établissement a ses propres clients, stock, ventes et employés indépendants.
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-lg bg-violet-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-3.5 h-3.5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                </svg>
                            </div>
                            <p class="text-xs text-gray-600">Basculer entre établissements</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-lg bg-pink-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-3.5 h-3.5 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                            <p class="text-xs text-gray-600">Données séparées par établissement</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <p class="text-xs text-gray-600">Employés illimités par établissement</p>
                        </div>
                    </div>
                </div>
                <button x-data @click="$dispatch('open-create-modal')"
                        class="flex items-center gap-2 text-sm font-bold px-4 py-2.5 rounded-xl text-white transition-opacity hover:opacity-90 flex-shrink-0 self-center"
                        style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Créer un 2<sup>e</sup> établissement
                </button>
            </div>
        </div>
        @endif

        {{-- Grille des instituts --}}
        @php
        $typeLabels = [
            'salon_coiffure'  => 'Salon de coiffure',
            'institut_beaute' => 'Institut de beauté',
            'nail_bar'        => 'Nail bar',
            'spa'             => 'Spa',
            'barbier'         => 'Barbier',
            'autre'           => 'Autre',
        ];
        @endphp
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($instituts as $institut)
            @php $isActif = $institut->id === $currentInstitutId; @endphp
            <div x-data="{
                    editOpen: false,
                    form: {
                        nom: @js($institut->nom),
                        ville: @js($institut->ville ?? ''),
                        telephone: @js($institut->telephone ?? ''),
                        email: @js($institut->email ?? ''),
                        type: @js($institut->type ?? 'institut_beaute')
                    }
                 }"
                 class="card p-5 relative {{ $isActif ? 'ring-2 ring-primary-400' : '' }} transition-all">

                {{-- En-tête carte : badge actif + bouton crayon --}}
                <div class="flex items-start justify-between mb-4 gap-2">
                    {{-- Logo / Initiales --}}
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        @if($institut->logo)
                        <img src="{{ asset('storage/' . $institut->logo) }}" alt="{{ $institut->nom }}"
                             class="w-12 h-12 rounded-xl object-cover flex-shrink-0">
                        @else
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-lg flex-shrink-0 shadow-sm"
                             style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                            {{ strtoupper(substr($institut->nom, 0, 2)) }}
                        </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-gray-900 dark:text-white truncate">{{ $institut->nom }}</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">{{ $typeLabels[$institut->type] ?? $institut->type }}</p>
                            @if($institut->ville)
                            <p class="text-xs text-gray-400 flex items-center gap-1 mt-0.5">
                                <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                {{ $institut->ville }}
                            </p>
                            @endif
                        </div>
                    </div>
                    {{-- Boutons top-right --}}
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        @if($isActif)
                        <span class="inline-flex items-center gap-1 text-xs font-bold px-2 py-0.5 rounded-full text-white"
                              style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            Actif
                        </span>
                        @endif
                        <button @click="editOpen = true" title="Modifier la fiche"
                                class="p-1.5 rounded-lg text-gray-400 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Stats rapides --}}
                <div class="grid grid-cols-2 gap-2 mb-4">
                    <div class="bg-gray-50 dark:bg-slate-800/60 rounded-xl px-3 py-2.5 text-center">
                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $institut->users_count ?? $institut->users()->count() }}</p>
                        <p class="text-xs text-gray-400">Employé(s)</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-slate-800/60 rounded-xl px-3 py-2.5 text-center">
                        <p class="text-lg font-bold {{ $institut->actif ? 'text-emerald-600' : 'text-red-500' }}">
                            {{ $institut->actif ? 'Actif' : 'Inactif' }}
                        </p>
                        <p class="text-xs text-gray-400">Statut</p>
                    </div>
                </div>

                {{-- Action switch --}}
                @if($isActif)
                <div class="w-full flex items-center justify-center gap-2 py-2.5 text-sm font-semibold rounded-xl text-primary-600 bg-primary-50 dark:bg-primary-900/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Établissement en cours de gestion
                </div>
                @else
                <form method="POST" action="{{ route('dashboard.mes-instituts.switch', $institut) }}">
                    @csrf
                    <button type="submit" class="w-full btn-outline justify-center text-sm py-2.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                        Gérer cet établissement
                    </button>
                </form>
                @endif

                {{-- ═══ Modal édition fiche ═══ --}}
                <div x-show="editOpen" x-cloak class="modal-backdrop" @keydown.escape.window="editOpen = false" @click.self="editOpen = false">
                    <div class="modal max-w-md" x-transition @click.stop>
                        <div class="modal-header">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, rgba(147,51,234,0.1), rgba(236,72,153,0.1));">
                                    <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </div>
                                <h3 class="modal-title">Modifier la fiche</h3>
                            </div>
                            <button @click="editOpen = false" class="btn-icon">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" action="{{ route('dashboard.mes-instituts.update', $institut) }}" class="space-y-4">
                                @csrf
                                @method('PUT')
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="col-span-2 form-group mb-0">
                                        <label class="form-label">Nom de l'établissement *</label>
                                        <input type="text" name="nom" required maxlength="100" class="form-input"
                                               x-model="form.nom">
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="form-label">Ville</label>
                                        <input type="text" name="ville" maxlength="100" class="form-input"
                                               x-model="form.ville">
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="form-label">Téléphone</label>
                                        <input type="text" name="telephone" maxlength="20" class="form-input"
                                               x-model="form.telephone">
                                    </div>
                                    <div class="col-span-2 form-group mb-0">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" maxlength="150" class="form-input"
                                               x-model="form.email">
                                    </div>
                                    <div class="col-span-2 form-group mb-0">
                                        <label class="form-label">Type d'établissement *</label>
                                        <select name="type" required class="form-input" x-model="form.type">
                                            <option value="salon_coiffure">Salon de coiffure</option>
                                            <option value="institut_beaute">Institut de beauté</option>
                                            <option value="nail_bar">Nail Bar / Onglerie</option>
                                            <option value="spa">Spa / Bien-être</option>
                                            <option value="barbier">Barbier</option>
                                            <option value="hammam">Hammam</option>
                                            <option value="centre_esthetique">Centre esthétique</option>
                                            <option value="soins_capillaires">Soins capillaires</option>
                                            <option value="tatouage">Tatouage / Piercing</option>
                                            <option value="boutique_mode">Boutique de mode</option>
                                            <option value="autre">Autre</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="flex gap-3 pt-1">
                                    <button type="button" @click="editOpen = false" class="btn btn-outline flex-1 justify-center">Annuler</button>
                                    <button type="submit" class="btn-primary flex-1 justify-center">Enregistrer</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

    </div>

    {{-- ═══ Modal création ═══ --}}
    @if($peutCreer)
    <div x-data="{ show: false, init() { window.addEventListener('open-create-modal', () => this.show = true); } }"
         x-show="show" x-cloak class="modal-backdrop" @keydown.escape.window="show = false">
        <div class="modal max-w-md" x-transition @click.stop>
            <div class="modal-header">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, rgba(147,51,234,0.1), rgba(236,72,153,0.1));">
                        <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <h3 class="modal-title">Créer un nouvel établissement</h3>
                </div>
                <button @click="show = false" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                @if($errors->any())
                <div class="mb-4 p-3 bg-red-50 rounded-xl text-sm text-red-600">
                    {{ $errors->first() }}
                </div>
                @endif
                <form method="POST" action="{{ route('dashboard.mes-instituts.store') }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-2 form-group mb-0">
                            <label class="form-label">Nom de l'établissement *</label>
                            <input type="text" name="nom" required maxlength="100" class="form-input"
                                   placeholder="Ex: Maëlya Cocody" value="{{ old('nom') }}">
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Ville</label>
                            <input type="text" name="ville" maxlength="100" class="form-input"
                                   placeholder="Ex: Abidjan" value="{{ old('ville') }}">
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Téléphone</label>
                            <input type="text" name="telephone" maxlength="20" class="form-input"
                                   placeholder="Ex: +225 07 00 00 00" value="{{ old('telephone') }}">
                        </div>
                        <div class="col-span-2 form-group mb-0">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" maxlength="150" class="form-input"
                                   placeholder="contact@maelya-cocody.ci" value="{{ old('email') }}">
                        </div>
                        <div class="col-span-2 form-group mb-0">
                            <label class="form-label">Type d'établissement *</label>
                            <select name="type" required class="form-input">
                                <option value="">-- Choisir un type --</option>
                                <option value="salon_coiffure"     {{ old('type') === 'salon_coiffure'     ? 'selected' : '' }}>Salon de coiffure</option>
                                <option value="institut_beaute"    {{ old('type') === 'institut_beaute'    ? 'selected' : '' }}>Institut de beauté</option>
                                <option value="nail_bar"           {{ old('type') === 'nail_bar'           ? 'selected' : '' }}>Nail Bar / Onglerie</option>
                                <option value="spa"                {{ old('type') === 'spa'                ? 'selected' : '' }}>Spa / Bien-être</option>
                                <option value="barbier"            {{ old('type') === 'barbier'            ? 'selected' : '' }}>Barbier</option>
                                <option value="hammam"             {{ old('type') === 'hammam'             ? 'selected' : '' }}>Hammam</option>
                                <option value="centre_esthetique" {{ old('type') === 'centre_esthetique' ? 'selected' : '' }}>Centre esthétique</option>
                                <option value="soins_capillaires" {{ old('type') === 'soins_capillaires' ? 'selected' : '' }}>Soins capillaires</option>
                                <option value="tatouage"           {{ old('type') === 'tatouage'           ? 'selected' : '' }}>Tatouage / Piercing</option>
                                <option value="boutique_mode"      {{ old('type') === 'boutique_mode'      ? 'selected' : '' }}>Boutique de mode</option>
                                <option value="autre"              {{ old('type') === 'autre'              ? 'selected' : '' }}>Autre</option>
                            </select>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400">Le nouvel établissement sera automatiquement activé et vous basculerez dessus.</p>
                    <div class="flex gap-3 pt-1">
                        <button type="button" @click="show = false" class="btn btn-outline flex-1 justify-center">Annuler</button>
                        <button type="submit" class="btn-primary flex-1 justify-center">Créer l'établissement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if($errors->any())
    <script>
        document.addEventListener('alpine:init', () => {
            setTimeout(() => window.dispatchEvent(new CustomEvent('open-create-modal')), 100);
        });
    </script>
    @endif
    @endif
</x-dashboard-layout>
