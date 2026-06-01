<x-dashboard-layout>

@php
    $isAdmin    = auth()->user()->isAdmin();
    $hasLogData = $isAdmin && isset($logs);
    $hasFilter  = request()->hasAny(['log_action', 'log_type', 'log_q']);
    $defaultTab = ($hasFilter || request('_tab') === 'journal') ? 'journal' : 'profil';
@endphp

<div x-data="{ tab: '{{ $defaultTab }}' }" class="space-y-6">

    {{-- ═══ HERO PROFIL (toujours visible) ═══ --}}
    <div class="card overflow-hidden">
        <div class="h-28 relative" style="background: linear-gradient(135deg, #9333ea 0%, #ec4899 50%, #f97316 100%);">
            <div class="absolute inset-0 opacity-20" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23ffffff&quot; fill-opacity=&quot;0.15&quot;%3E%3Ccircle cx=&quot;30&quot; cy=&quot;30&quot; r=&quot;4&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
        </div>
        <div class="px-6 pb-5 pt-14 relative">
            <div class="absolute -top-10 left-6">
                <div class="w-20 h-20 rounded-2xl bg-white shadow-lg border-4 border-white flex items-center justify-center shrink-0">
                    <span class="text-2xl font-bold" style="background: linear-gradient(135deg, #9333ea, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        {{ strtoupper(substr(auth()->user()->prenom ?? auth()->user()->name, 0, 1)) }}{{ strtoupper(substr(auth()->user()->nom_famille ?? '', 0, 1)) }}
                    </span>
                </div>
            </div>
            <div class="flex items-end justify-between flex-wrap gap-3">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">{{ auth()->user()->nom_complet }}</h1>
                    <div class="flex flex-wrap items-center gap-2 mt-1">
                        <span class="text-sm text-gray-500">{{ auth()->user()->email }}</span>
                        <span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full"
                              style="background: linear-gradient(135deg, #9333ea20, #ec489920); color: #9333ea;">
                            {{ match(auth()->user()->role) { 'super_admin' => 'Super Admin', 'admin' => 'Admin', 'employe' => 'Employé', default => auth()->user()->role } }}
                        </span>
                    </div>
                </div>
                {{-- Onglets (admin uniquement) --}}
                @if($hasLogData)
                <div class="flex gap-1 bg-gray-100 rounded-xl p-1">
                    <button type="button" @click="tab = 'profil'"
                        :class="tab === 'profil' ? 'bg-white shadow text-gray-900 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                        class="flex items-center gap-1.5 px-4 py-1.5 rounded-lg text-sm transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Mon profil
                    </button>
                    <button type="button" @click="tab = 'journal'"
                        :class="tab === 'journal' ? 'bg-white shadow text-gray-900 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                        class="flex items-center gap-1.5 px-4 py-1.5 rounded-lg text-sm transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        Journal d'activité
                        @if($hasFilter)
                            <span class="w-2 h-2 rounded-full bg-primary-500 shrink-0"></span>
                        @endif
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══ ONGLET PROFIL ═══ --}}
    <div x-show="tab === 'profil'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
         x-data="{ showPassword: false }"
         class="max-w-2xl mx-auto space-y-6">

        @if(session('success'))
            <div class="alert-success flex items-center gap-2">
                <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div class="alert-danger text-sm">
                @foreach($errors->all() as $e) <p>• {{ $e }}</p> @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('dashboard.profil.update') }}" class="space-y-6">
            @csrf @method('PUT')

            {{-- Informations personnelles --}}
            <div class="card p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-9 h-9 rounded-xl bg-primary-50 flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-gray-900">Informations personnelles</h2>
                        <p class="text-xs text-gray-400">Vos coordonnées et identifiants</p>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">
                                <svg class="w-3.5 h-3.5 inline -mt-0.5 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                Prénom
                            </label>
                            <input type="text" name="prenom" maxlength="80"
                                   value="{{ old('prenom', auth()->user()->prenom) }}"
                                   placeholder="Votre prénom"
                                   class="form-input @error('prenom') border-red-400 @enderror">
                            @error('prenom') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                <svg class="w-3.5 h-3.5 inline -mt-0.5 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                Nom de famille
                            </label>
                            <input type="text" name="nom_famille" maxlength="80"
                                   value="{{ old('nom_famille', auth()->user()->nom_famille) }}"
                                   placeholder="Votre nom"
                                   class="form-input @error('nom_famille') border-red-400 @enderror">
                            @error('nom_famille') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            <svg class="w-3.5 h-3.5 inline -mt-0.5 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Adresse email
                        </label>
                        <input type="email" name="email" required
                               value="{{ old('email', auth()->user()->email) }}"
                               placeholder="vous@exemple.com"
                               class="form-input @error('email') border-red-400 @enderror">
                        @error('email') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            <svg class="w-3.5 h-3.5 inline -mt-0.5 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            Téléphone
                        </label>
                        <input type="tel" name="telephone" maxlength="20"
                               value="{{ old('telephone', auth()->user()->telephone) }}"
                               placeholder="+225 07 00 00 00 00"
                               class="form-input">
                    </div>
                </div>
            </div>

            {{-- Sécurité --}}
            <div class="card p-6">
                <button type="button" @click="showPassword = !showPassword" class="w-full flex items-center justify-between group">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center">
                            <svg class="w-4.5 h-4.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <div class="text-left">
                            <h2 class="text-sm font-bold text-gray-900">Sécurité</h2>
                            <p class="text-xs text-gray-400">Modifier votre mot de passe</p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="showPassword && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="showPassword" x-collapse x-cloak class="mt-5 pt-5 border-t border-gray-100 space-y-4">
                    <div class="form-group">
                        <label class="form-label">
                            <svg class="w-3.5 h-3.5 inline -mt-0.5 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                            Mot de passe actuel
                        </label>
                        <input type="password" name="password_actuel" placeholder="••••••••"
                               class="form-input @error('password_actuel') border-red-400 @enderror">
                        @error('password_actuel') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Nouveau mot de passe</label>
                            <input type="password" name="password" minlength="8" placeholder="Min. 8 caractères"
                                   class="form-input @error('password') border-red-400 @enderror">
                            @error('password') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirmer</label>
                            <input type="password" name="password_confirmation" placeholder="Retapez le mot de passe" class="form-input">
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Laissez vide si vous ne souhaitez pas changer de mot de passe.
                    </p>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary px-8 py-2.5 justify-center text-sm font-semibold shadow-lg shadow-primary-200/50 hover:shadow-primary-300/50 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Enregistrer les modifications
                </button>
            </div>
        </form>
    </div>

    {{-- ═══ ONGLET JOURNAL D'ACTIVITÉ (admin only) ═══ --}}
    @if($hasLogData)
    @php
        $actionLabels = [
            'created'   => ['label' => 'Création',     'class' => 'badge-success'],
            'updated'   => ['label' => 'Modification',  'class' => 'badge-info'],
            'deleted'   => ['label' => 'Suppression',   'class' => 'badge-danger'],
            'login'     => ['label' => 'Connexion',     'class' => 'badge-primary'],
            'logout'    => ['label' => 'Déconnexion',   'class' => 'badge-gray'],
            'validated' => ['label' => 'Validation',    'class' => 'badge-success'],
            'cancelled' => ['label' => 'Annulation',    'class' => 'badge-warning'],
            'sent'      => ['label' => 'Envoi',         'class' => 'badge-primary'],
            'paid'      => ['label' => 'Paiement',      'class' => 'badge-success'],
            'restored'  => ['label' => 'Restauration',  'class' => 'badge-warning'],
        ];
        $modelLabels = [
            'Vente'         => 'Vente',
            'Client'        => 'Client',
            'Rdv'           => 'Rendez-vous',
            'Prestation'    => 'Prestation',
            'Produit'       => 'Produit',
            'Abonnement'    => 'Abonnement',
            'Employe'       => 'Employé',
            'Institut'      => 'Institut',
            'User'          => 'Utilisateur',
            'BonCommande'   => 'Bon de commande',
            'Inventaire'    => 'Inventaire',
            'Depense'       => 'Dépense',
            'CodeReduction' => 'Code promo',
            'Fournisseur'   => 'Fournisseur',
            'PlanFidelite'  => 'Programme fidélité',
            'CategoriePrestation' => 'Catégorie prestation',
            'CategorieProduit'    => 'Catégorie produit',
        ];
        $fieldLabels = [
            'statut'           => 'Statut',
            'montant'          => 'Montant',
            'total'            => 'Total',
            'nom'              => 'Nom',
            'prenom'           => 'Prénom',
            'nom_famille'      => 'Nom de famille',
            'email'            => 'E-mail',
            'telephone'        => 'Téléphone',
            'date'             => 'Date',
            'heure'            => 'Heure',
            'heure_debut'      => 'Heure de début',
            'heure_fin'        => 'Heure de fin',
            'notes'            => 'Notes',
            'motif_annulation' => 'Motif d\'annulation',
            'annulee_le'       => 'Annulée le',
            'annulee_par'      => 'Annulée par',
            'mode_paiement'    => 'Mode de paiement',
            'remise'           => 'Remise',
            'quantite'         => 'Quantité',
            'prix_unitaire'    => 'Prix unitaire',
            'prix'             => 'Prix',
            'duree'            => 'Durée (min)',
            'actif'            => 'Actif',
            'validated_at'     => 'Validé le',
            'created_at'       => 'Créé le',
            'updated_at'       => 'Modifié le',
            'deleted_at'       => 'Supprimé le',
            'expire_le'        => 'Expire le',
            'debut_le'         => 'Début le',
            'numero'           => 'Numéro',
            'titre'            => 'Titre',
            'description'      => 'Description',
            'adresse'          => 'Adresse',
            'type'             => 'Type',
            'categorie_id'     => 'Catégorie',
            'employe_id'       => 'Employé assigné',
            'client_id'        => 'Client',
            'password'         => '(mot de passe)',
            'remember_token'   => '(token session)',
        ];
        $skipFields = ['created_at', 'updated_at', 'deleted_at', 'remember_token', 'two_factor_secret'];
    @endphp

    <div x-show="tab === 'journal'"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         id="journal"
         class="space-y-4">

        <div class="flex items-center justify-between">
            <p class="text-xs text-gray-500">{{ $logs->total() }} entrée{{ $logs->total() > 1 ? 's' : '' }} au total</p>
            @if($hasFilter)
                <a href="{{ route('dashboard.profil.edit') }}?_tab=journal" class="btn-outline text-sm flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Effacer les filtres
                </a>
            @endif
        </div>

        <form method="GET" action="{{ route('dashboard.profil.edit') }}" id="journal-filter-form" class="card p-4 grid grid-cols-1 md:grid-cols-3 gap-3">
            <input type="hidden" name="_tab" value="journal">
            <div>
                <label class="form-label">Action</label>
                <select name="log_action" class="form-select" onchange="document.getElementById('journal-filter-form').submit()">
                    <option value="">Toutes les actions</option>
                    @foreach($logActions as $a)
                        <option value="{{ $a }}" @selected(request('log_action') === $a)>
                            {{ $actionLabels[$a]['label'] ?? ucfirst($a) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Type d'objet</label>
                <select name="log_type" class="form-select" onchange="document.getElementById('journal-filter-form').submit()">
                    <option value="">Tous les types</option>
                    @foreach($logSubjects as $s)
                        <option value="{{ $s }}" @selected(request('log_type') === $s)>
                            {{ $modelLabels[class_basename($s)] ?? class_basename($s) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div x-data="{ timer: null }">
                <label class="form-label">Recherche</label>
                <input type="text" name="log_q"
                       value="{{ request('log_q') }}"
                       @input="clearTimeout(timer); timer = setTimeout(() => $el.form.submit(), 600)"
                       placeholder="Libellé ou identifiant…"
                       class="form-input">
            </div>
        </form>

        @php
            $fmt = fn($v) => is_bool($v) ? ($v ? 'Oui' : 'Non')
                : (is_null($v) ? '—'
                : (is_array($v) ? implode(', ', $v) : $v));
        @endphp

        {{-- ── VUE MOBILE : cartes (< md) ── --}}
        <div class="md:hidden space-y-3">
            @forelse($logs as $log)
                @php
                    $actionInfo = $actionLabels[$log->action] ?? ['label' => ucfirst($log->action), 'class' => 'badge-primary'];
                    $modelName  = $modelLabels[class_basename($log->subject_type ?? '')] ?? class_basename($log->subject_type ?? '');
                    $changes    = $log->changes;
                    $hasOldNew  = is_array($changes) && isset($changes['old'], $changes['new']);
                @endphp
                <div class="card p-4 space-y-3">
                    {{-- Ligne 1 : date + badge action --}}
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs text-gray-500">
                            {{ $log->created_at->format('d/m/Y') }}
                            <span class="text-gray-400 ml-1">{{ $log->created_at->format('H:i') }}</span>
                        </span>
                        <span class="badge {{ $actionInfo['class'] }}">{{ $actionInfo['label'] }}</span>
                    </div>
                    {{-- Ligne 2 : utilisateur + objet --}}
                    <div class="flex items-start justify-between gap-3 text-xs">
                        <div>
                            <p class="text-gray-400 text-[10px] uppercase tracking-wide font-semibold mb-0.5">Par</p>
                            <p class="font-medium text-gray-700">
                                @if($log->user)
                                    {{ $log->user->prenom }} {{ $log->user->nom_famille }}
                                @else
                                    <span class="italic text-gray-400">Système</span>
                                @endif
                            </p>
                        </div>
                        @if($modelName)
                        <div class="text-right">
                            <p class="text-gray-400 text-[10px] uppercase tracking-wide font-semibold mb-0.5">Objet</p>
                            <p class="font-medium text-gray-700">{{ $modelName }}</p>
                            @if($log->label)
                                <p class="text-gray-500 text-[11px]">{{ $log->label }}</p>
                            @endif
                        </div>
                        @endif
                    </div>
                    {{-- Ligne 3 : modifications --}}
                    @if($changes)
                    <details class="text-xs">
                        <summary class="cursor-pointer text-primary-600 font-medium select-none">▶ Voir les modifications</summary>
                        <div class="mt-2 space-y-1.5 bg-gray-50 rounded-lg p-2.5 border border-gray-100">
                            @if($hasOldNew)
                                @php
                                    $changedKeys = collect($changes['new'])
                                        ->filter(fn($v, $k) => !in_array($k, $skipFields) && ($changes['old'][$k] ?? null) !== $v)
                                        ->keys();
                                @endphp
                                @forelse($changedKeys as $key)
                                    @php
                                        $oldVal = $changes['old'][$key] ?? null;
                                        $newVal = $changes['new'][$key] ?? null;
                                        $label  = $fieldLabels[$key] ?? str_replace('_', ' ', ucfirst($key));
                                    @endphp
                                    <div class="flex items-start gap-1.5 text-[11px]">
                                        <span class="text-gray-400 shrink-0 w-24 truncate" title="{{ $label }}">{{ $label }}</span>
                                        <span class="text-red-400 line-through shrink-0 max-w-[70px] truncate" title="{{ $fmt($oldVal) }}">{{ $fmt($oldVal) }}</span>
                                        <span class="text-gray-300">→</span>
                                        <span class="text-emerald-600 font-medium shrink-0 max-w-[70px] truncate" title="{{ $fmt($newVal) }}">{{ $fmt($newVal) }}</span>
                                    </div>
                                @empty
                                    <p class="text-[11px] text-gray-400 italic">Aucun champ modifié détecté</p>
                                @endforelse
                            @elseif(isset($changes['new']) && is_array($changes['new']))
                                @foreach($changes['new'] as $key => $val)
                                    @if(!in_array($key, $skipFields) && !in_array($key, ['institut_id','user_id','id']) && !is_null($val))
                                        @php
                                            $label   = $fieldLabels[$key] ?? str_replace('_', ' ', ucfirst($key));
                                            $display = $fmt($val);
                                        @endphp
                                        <div class="flex gap-1.5 text-[11px]">
                                            <span class="text-gray-400 shrink-0 w-24 truncate">{{ $label }}</span>
                                            <span class="text-gray-700 font-medium truncate max-w-[140px]" title="{{ $display }}">{{ $display }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            @else
                                @foreach($changes as $key => $val)
                                    @if(!in_array($key, $skipFields))
                                        @php
                                            $label   = $fieldLabels[$key] ?? str_replace('_', ' ', ucfirst($key));
                                            $display = $fmt($val);
                                        @endphp
                                        <div class="flex gap-1.5 text-[11px]">
                                            <span class="text-gray-400 shrink-0 w-24 truncate">{{ $label }}</span>
                                            <span class="text-gray-700 font-medium truncate max-w-[140px]" title="{{ $display }}">{{ $display }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            @endif
                        </div>
                    </details>
                    @endif
                </div>
            @empty
                <div class="card px-4 py-12 text-center">
                    <div class="text-gray-300 mb-2">
                        <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <p class="text-sm text-gray-400">Aucune activité enregistrée</p>
                </div>
            @endforelse
        </div>

        {{-- ── VUE DESKTOP : tableau (≥ md) ── --}}
        <div class="hidden md:block card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Date</th>
                        <th class="px-4 py-3 text-left font-semibold">Utilisateur</th>
                        <th class="px-4 py-3 text-left font-semibold">Action</th>
                        <th class="px-4 py-3 text-left font-semibold">Objet concerné</th>
                        <th class="px-4 py-3 text-left font-semibold">Modifications</th>
                        <th class="px-4 py-3 text-left font-semibold">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                        @php
                            $actionInfo = $actionLabels[$log->action] ?? ['label' => ucfirst($log->action), 'class' => 'badge-primary'];
                            $modelName  = $modelLabels[class_basename($log->subject_type ?? '')] ?? class_basename($log->subject_type ?? '');
                            $changes    = $log->changes;
                            $hasOldNew  = is_array($changes) && isset($changes['old'], $changes['new']);
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500">
                                {{ $log->created_at->format('d/m/Y') }}<br>
                                <span class="text-gray-400">{{ $log->created_at->format('H:i:s') }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs font-medium text-gray-700">
                                @if($log->user)
                                    {{ $log->user->prenom }} {{ $log->user->nom_famille }}
                                @else
                                    <span class="text-gray-400 italic">Système</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge {{ $actionInfo['class'] }}">{{ $actionInfo['label'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @if($modelName)
                                    <span class="font-medium text-gray-700">{{ $modelName }}</span>
                                @endif
                                @if($log->label)
                                    <br><span class="text-gray-500">{{ $log->label }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs max-w-sm">
                                @if($changes)
                                    <details>
                                        <summary class="cursor-pointer text-primary-600 hover:text-primary-700 font-medium select-none">
                                            ▶ Voir les détails
                                        </summary>
                                        <div class="mt-2 space-y-1.5 bg-gray-50 rounded-lg p-2.5 border border-gray-100">
                                            @if($hasOldNew)
                                                @php
                                                    $changedKeys = collect($changes['new'])
                                                        ->filter(fn($v, $k) => !in_array($k, $skipFields) && ($changes['old'][$k] ?? null) !== $v)
                                                        ->keys();
                                                @endphp
                                                @forelse($changedKeys as $key)
                                                    @php
                                                        $oldVal = $changes['old'][$key] ?? null;
                                                        $newVal = $changes['new'][$key] ?? null;
                                                        $label  = $fieldLabels[$key] ?? str_replace('_', ' ', ucfirst($key));
                                                    @endphp
                                                    <div class="flex items-start gap-1.5 text-[11px]">
                                                        <span class="text-gray-400 shrink-0 w-24 truncate" title="{{ $label }}">{{ $label }}</span>
                                                        <span class="text-red-400 line-through shrink-0 max-w-[80px] truncate" title="{{ $fmt($oldVal) }}">{{ $fmt($oldVal) }}</span>
                                                        <span class="text-gray-300">→</span>
                                                        <span class="text-emerald-600 font-medium shrink-0 max-w-[80px] truncate" title="{{ $fmt($newVal) }}">{{ $fmt($newVal) }}</span>
                                                    </div>
                                                @empty
                                                    <p class="text-[11px] text-gray-400 italic">Aucun champ modifié détecté</p>
                                                @endforelse
                                            @elseif(isset($changes['new']) && is_array($changes['new']))
                                                @foreach($changes['new'] as $key => $val)
                                                    @if(!in_array($key, $skipFields) && !in_array($key, ['institut_id','user_id','id']) && !is_null($val))
                                                        @php
                                                            $label   = $fieldLabels[$key] ?? str_replace('_', ' ', ucfirst($key));
                                                            $display = $fmt($val);
                                                        @endphp
                                                        <div class="flex gap-1.5 text-[11px]">
                                                            <span class="text-gray-400 shrink-0 w-24 truncate">{{ $label }}</span>
                                                            <span class="text-gray-700 font-medium truncate max-w-[140px]" title="{{ $display }}">{{ $display }}</span>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            @else
                                                @foreach($changes as $key => $val)
                                                    @if(!in_array($key, $skipFields))
                                                        @php
                                                            $label   = $fieldLabels[$key] ?? str_replace('_', ' ', ucfirst($key));
                                                            $display = $fmt($val);
                                                        @endphp
                                                        <div class="flex gap-1.5 text-[11px]">
                                                            <span class="text-gray-400 shrink-0 w-24 truncate">{{ $label }}</span>
                                                            <span class="text-gray-700 font-medium truncate max-w-[140px]" title="{{ $display }}">{{ $display }}</span>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            @endif
                                        </div>
                                    </details>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-[11px] text-gray-400 font-mono">{{ $log->ip_address ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <div class="text-gray-300 mb-2">
                                    <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <p class="text-sm text-gray-400">Aucune activité enregistrée</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $logs->links() }}
    </div>
    @endif

</div>{{-- fin x-data tab --}}
</x-dashboard-layout>
