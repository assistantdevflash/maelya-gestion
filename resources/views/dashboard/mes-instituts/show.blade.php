<x-dashboard-layout>
    <x-slot name="title">{{ $institut->nom }}</x-slot>

    <div class="space-y-5">

        {{-- ═══ Fil d'Ariane ═══ --}}
        <nav class="flex items-center gap-2 text-sm text-gray-400 min-w-0">
            <a href="{{ route('dashboard.mes-instituts.index') }}" class="hover:text-primary-600 transition-colors whitespace-nowrap">Mes établissements</a>
            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-700 dark:text-gray-300 font-medium truncate">{{ $institut->nom }}</span>
        </nav>

        {{-- ═══ En-tête ═══ --}}
        <div class="card overflow-hidden">
            {{-- Bandeau : nom + action principale, en blanc sur le dégradé --}}
            <div class="relative px-5 sm:px-6 pt-5 pb-5" style="background: linear-gradient(135deg, #9333ea 0%, #ec4899 100%);">
                <div class="flex flex-wrap items-start justify-between gap-3 pl-[88px] sm:pl-24 min-h-[52px]">
                    <div class="min-w-0">
                        <h1 class="text-xl sm:text-2xl font-display font-bold text-white leading-tight truncate">{{ $institut->nom }}</h1>
                        <p class="text-sm text-white/85 mt-0.5 truncate">
                            {{ $typeLabel }}@if($institut->ville) · {{ $institut->ville }}@endif
                        </p>
                    </div>

                    <div class="flex-shrink-0">
                        @if($estCourant)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-white/20 text-white backdrop-blur">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                Établissement courant
                            </span>
                        @else
                            <form method="POST" action="{{ route('dashboard.mes-instituts.switch', $institut) }}">
                                @csrf
                                <button type="submit"
                                        style="background:#ffffff;"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-primary-700 hover:opacity-90 transition-opacity">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                    Gérer cet établissement
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                {{-- Logo posé à cheval sur le bord bas du bandeau --}}
                <div class="absolute left-5 sm:left-6 -bottom-9 w-[72px] h-[72px] rounded-2xl overflow-hidden flex items-center justify-center text-white font-bold text-xl shadow-lg ring-4 ring-white dark:ring-slate-900"
                     style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                    @if($institut->logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($institut->logo))
                        <img src="{{ $institut->logo_url }}" alt="{{ $institut->nom }}" class="w-full h-full object-cover">
                    @else
                        {{ mb_strtoupper(mb_substr($institut->nom, 0, 2)) }}
                    @endif
                </div>
            </div>

            {{-- Ligne basse : état + actions secondaires --}}
            <div class="pl-[104px] sm:pl-28 pr-5 sm:pr-6 pt-3 pb-4 flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
                <div class="flex items-center gap-2 flex-wrap">
                    @if($institut->en_sommeil)
                        <span class="badge badge-warning text-xs">En sommeil</span>
                    @elseif($institut->actif)
                        <span class="badge badge-success text-xs">Actif</span>
                    @else
                        <span class="badge badge-danger text-xs">Inactif</span>
                    @endif

                    @if($institut->vitrine_active)
                        <span class="badge badge-info text-xs">Vitrine en ligne</span>
                    @endif
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    @if($institut->vitrine_active)
                        <a href="{{ $vitrineUrl }}" target="_blank" rel="noopener"
                           class="btn-outline text-sm py-2 px-3">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            Voir la page publique
                        </a>
                    @endif

                    <a href="{{ route('dashboard.mes-instituts.edit', $institut) }}" class="btn-primary text-sm py-2 px-3">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Modifier
                    </a>
                </div>
            </div>
        </div>

        {{-- ═══ Indicateurs ═══ --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div class="card p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Chiffre d'affaires du mois</p>
                <p class="text-lg sm:text-2xl font-bold text-gray-900 dark:text-white mt-1 leading-tight">
                    {{ number_format($stats['ca_mois'], 0, ',', ' ') }} <span class="text-sm font-medium text-gray-400">F</span>
                </p>
                @if($stats['evolution'] !== null)
                    <p class="text-[11px] font-semibold mt-1 {{ $stats['evolution'] >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                        {{ $stats['evolution'] >= 0 ? '▲' : '▼' }} {{ abs($stats['evolution']) }} % vs mois dernier
                    </p>
                @else
                    <p class="text-[11px] text-gray-400 mt-1">Pas d'historique le mois dernier</p>
                @endif
            </div>

            <div class="card p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Ventes du mois</p>
                <p class="text-lg sm:text-2xl font-bold text-gray-900 dark:text-white mt-1 leading-tight">{{ number_format($stats['ventes_mois'], 0, ',', ' ') }}</p>
                <p class="text-[11px] text-gray-400 mt-1">{{ \App\Helpers\TexteHelper::pluriel($stats['rdv_a_venir'], 'rendez-vous à venir', 'rendez-vous à venir') }}</p>
            </div>

            <div class="card p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Clients actifs</p>
                <p class="text-lg sm:text-2xl font-bold text-gray-900 dark:text-white mt-1 leading-tight">{{ number_format($stats['clients'], 0, ',', ' ') }}</p>
                <p class="text-[11px] text-gray-400 mt-1">{{ \App\Helpers\TexteHelper::pluriel($stats['nouveaux_clients'], 'nouveau ce mois', 'nouveaux ce mois') }}</p>
            </div>

            <div class="card p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Crédits en cours</p>
                <p class="text-lg sm:text-2xl font-bold {{ $stats['credits_encours'] > 0 ? 'text-amber-600' : 'text-gray-900 dark:text-white' }} mt-1 leading-tight">
                    {{ number_format($stats['credits_encours'], 0, ',', ' ') }} <span class="text-sm font-medium text-gray-400">F</span>
                </p>
                <p class="text-[11px] text-gray-400 mt-1">reste à encaisser</p>
            </div>
        </div>

        {{-- ═══ Repères secondaires ═══ --}}
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 px-1 text-xs text-gray-400">
            <span class="inline-flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                {{ \App\Helpers\TexteHelper::pluriel($stats['produits'], 'article au catalogue', 'articles au catalogue') }}
            </span>
            <span class="text-gray-300 dark:text-slate-600">•</span>
            <span class="inline-flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0"/></svg>
                {{ \App\Helpers\TexteHelper::pluriel($equipe->count(), 'membre dans l\'équipe', 'membres dans l\'équipe') }}
            </span>
            @if($institut->created_at)
                <span class="text-gray-300 dark:text-slate-600">•</span>
                <span class="inline-flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Ouvert depuis le {{ $institut->created_at->format('d/m/Y') }}
                </span>
            @endif
        </div>

        <div class="grid lg:grid-cols-3 gap-5">

            {{-- ═══ Colonne principale ═══ --}}
            <div class="lg:col-span-2 space-y-5">

                {{-- Coordonnées --}}
                <div class="card">
                    <div class="card-header">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Coordonnées</h2>
                    </div>
                    <div class="card-body">
                        <dl class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <dt class="text-[11px] uppercase tracking-wide text-gray-400 font-semibold">Téléphone</dt>
                                <dd class="text-sm text-gray-900 dark:text-gray-200 mt-0.5">
                                    @if($institut->telephone)
                                        <a href="tel:{{ $institut->telephone }}" class="hover:text-primary-600">{{ $institut->telephone }}</a>
                                    @else
                                        <span class="text-gray-400">Non renseigné</span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-[11px] uppercase tracking-wide text-gray-400 font-semibold">Email</dt>
                                <dd class="text-sm text-gray-900 dark:text-gray-200 mt-0.5 break-all">
                                    @if($institut->email)
                                        <a href="mailto:{{ $institut->email }}" class="hover:text-primary-600">{{ $institut->email }}</a>
                                    @else
                                        <span class="text-gray-400">Non renseigné</span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-[11px] uppercase tracking-wide text-gray-400 font-semibold">Ville</dt>
                                <dd class="text-sm text-gray-900 dark:text-gray-200 mt-0.5">{{ $institut->ville ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] uppercase tracking-wide text-gray-400 font-semibold">Adresse</dt>
                                <dd class="text-sm text-gray-900 dark:text-gray-200 mt-0.5">{{ $institut->adresse ?: '—' }}</dd>
                            </div>
                            @if($institut->rccm || $institut->numero_fiscal)
                            <div>
                                <dt class="text-[11px] uppercase tracking-wide text-gray-400 font-semibold">RCCM</dt>
                                <dd class="text-sm font-mono text-gray-900 dark:text-gray-200 mt-0.5">{{ $institut->rccm ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] uppercase tracking-wide text-gray-400 font-semibold">Numéro fiscal</dt>
                                <dd class="text-sm font-mono text-gray-900 dark:text-gray-200 mt-0.5">{{ $institut->numero_fiscal ?: '—' }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>

                {{-- Horaires --}}
                <div class="card">
                    <div class="card-header">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Horaires d'ouverture</h2>
                    </div>
                    <div class="card-body">
                        @if($institut->heure_ouverture && $institut->heure_fermeture)
                            <p class="text-sm text-gray-900 dark:text-gray-200 mb-3">
                                De <strong>{{ substr($institut->heure_ouverture, 0, 5) }}</strong>
                                à <strong>{{ substr($institut->heure_fermeture, 0, 5) }}</strong>
                            </p>
                        @else
                            <p class="text-sm text-gray-400 mb-3">Aucune restriction horaire configurée.</p>
                        @endif

                        @php
                            $jours = array_map('intval', (array) ($institut->jours_ouverture ?: [1,2,3,4,5,6]));
                            $noms = [1 => 'Lun', 2 => 'Mar', 3 => 'Mer', 4 => 'Jeu', 5 => 'Ven', 6 => 'Sam', 7 => 'Dim'];
                        @endphp

                        <div class="flex flex-wrap gap-1.5">
                            @foreach($noms as $numero => $nom)
                                <span class="px-2.5 py-1 rounded-lg text-xs font-semibold {{ in_array($numero, $jours, true) ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-gray-100 text-gray-400 dark:bg-slate-800 dark:text-slate-500' }}">
                                    {{ $nom }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Équipe --}}
                <div class="card">
                    <div class="card-header flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Équipe</h2>
                        <span class="text-xs text-gray-400">{{ \App\Helpers\TexteHelper::pluriel($equipe->count(), 'membre', 'membres') }}</span>
                    </div>
                    <div class="card-body p-0">
                        @if($equipe->isEmpty())
                            <p class="px-5 py-8 text-center text-sm text-gray-400">Aucun employé rattaché à cet établissement.</p>
                        @else
                        <ul class="divide-y divide-gray-100 dark:divide-slate-800">
                            @foreach($equipe as $membre)
                            @php
                                $roleLabel = [
                                    'admin'      => 'Administrateur',
                                    'gerant'     => 'Gérant',
                                    'employe'    => 'Employé',
                                    'caissier'   => 'Caissier',
                                    'commercial' => 'Commercial',
                                ][$membre->role] ?? ucfirst((string) $membre->role);
                            @endphp
                            <li class="flex items-center gap-3 px-5 py-3">
                                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-primary-100 to-secondary-100 dark:from-primary-900/40 dark:to-secondary-900/40 flex items-center justify-center text-primary-700 dark:text-primary-300 text-xs font-bold flex-shrink-0">
                                    {{ mb_strtoupper(mb_substr($membre->prenom ?? '?', 0, 1) . mb_substr($membre->nom_famille ?? '', 0, 1)) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ trim(($membre->prenom ?? '') . ' ' . ($membre->nom_famille ?? '')) ?: $membre->email }}
                                    </p>
                                    <p class="text-[11px] text-gray-400 truncate">{{ $membre->email }}</p>
                                </div>
                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 dark:bg-slate-800 dark:text-slate-300 flex-shrink-0">{{ $roleLabel }}</span>
                                @unless($membre->actif)
                                    <span class="badge badge-danger text-[10px] flex-shrink-0">Inactif</span>
                                @endunless
                            </li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ═══ Colonne latérale ═══ --}}
            <div class="space-y-5">

                {{-- Page publique --}}
                <div class="card">
                    <div class="card-header">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Page publique</h2>
                    </div>
                    <div class="card-body space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-300">Vitrine</span>
                            @if($institut->vitrine_active)
                                <span class="badge badge-success text-xs">En ligne</span>
                            @else
                                <span class="badge text-xs bg-gray-100 text-gray-500 dark:bg-slate-800 dark:text-slate-400">Hors ligne</span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-300">Réservation en ligne</span>
                            @if($institut->reservation_en_ligne)
                                <span class="badge badge-info text-xs">Activée</span>
                            @else
                                <span class="badge text-xs bg-gray-100 text-gray-500 dark:bg-slate-800 dark:text-slate-400">Désactivée</span>
                            @endif
                        </div>

                        @if($institut->vitrine_active)
                            <div class="pt-3 border-t border-gray-100 dark:border-slate-800 space-y-2">
                                <a href="{{ $vitrineUrl }}" target="_blank" rel="noopener"
                                   class="flex items-center gap-2 text-xs font-medium text-primary-600 hover:underline break-all">
                                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    {{ $vitrineUrl }}
                                </a>
                                <div class="text-center pt-2">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&margin=0&data={{ urlencode($vitrineUrl) }}"
                                         alt="QR code de la page publique de {{ $institut->nom }}"
                                         width="120" height="120"
                                         style="background:#ffffff;"
                                         class="mx-auto rounded-lg border border-gray-200 dark:border-slate-700 p-1.5">
                                    <p class="text-[10px] text-gray-400 mt-1">Scannez pour ouvrir la page</p>
                                </div>
                            </div>
                        @else
                            <p class="text-xs text-gray-400 pt-2 border-t border-gray-100 dark:border-slate-800">
                                La page publique est désactivée : aucun lien à partager.
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Activité récente --}}
                <div class="card">
                    <div class="card-header flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Activité récente</h2>
                    </div>
                    <div class="card-body p-0">
                        @if($dernieresVentes->isEmpty())
                            <p class="px-5 py-8 text-center text-sm text-gray-400">Aucune vente enregistrée.</p>
                        @else
                        <ul class="divide-y divide-gray-100 dark:divide-slate-800">
                            @foreach($dernieresVentes as $vente)
                            <li class="px-5 py-3">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                            {{ $vente->client?->nom_complet ?? 'Client de passage' }}
                                        </p>
                                        <p class="text-[11px] text-gray-400">
                                            {{ $vente->numero ?? '#' . substr($vente->id, 0, 8) }} · {{ $vente->created_at?->format('d/m/Y H:i') }}
                                        </p>
                                    </div>
                                    <span class="text-sm font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                        {{ number_format((int) $vente->total, 0, ',', ' ') }} F
                                    </span>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout>
