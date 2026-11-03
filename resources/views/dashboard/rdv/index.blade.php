<x-dashboard-layout>
<div class="space-y-5">

    {{-- En-tête --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="page-title">Rendez-vous</h1>
            <p class="page-subtitle">Gérez l'agenda de votre établissement.</p>
        </div>
        <div class="flex gap-2 flex-shrink-0">
            <a href="{{ route('dashboard.rdv.calendrier') }}" class="btn-outline">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <rect x="3" y="4" width="18" height="18" rx="2" stroke-width="2"/><line x1="3" y1="10" x2="21" y2="10" stroke-width="2"/>
                </svg>
                Calendrier
            </a>
            <a href="{{ route('dashboard.rdv.create') }}" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Nouveau RDV
            </a>
        </div>
    </div>

    {{-- Message succès --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
         class="flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-4 py-3 text-sm font-medium">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Filtres --}}
    <div class="card p-4">
        <form method="GET" action="{{ route('dashboard.rdv.index') }}"
              class="flex flex-col gap-2 lg:flex-row lg:flex-nowrap lg:items-center">
            {{-- La période doit survivre au changement de statut --}}
            <input type="hidden" name="filtre" value="{{ $filtre }}">

            {{-- Sélecteur de période : 4 colonnes pleine largeur sur mobile --}}
            <div class="grid grid-cols-4 gap-1 p-1 bg-gray-100/80 dark:bg-slate-700/50 rounded-xl w-full lg:flex lg:w-auto lg:flex-shrink-0">
                @foreach([
                    'today'   => ['court' => 'Jour', 'long' => "Aujourd'hui"],
                    'semaine' => ['court' => 'Sem.', 'long' => 'Semaine'],
                    'mois'    => ['court' => 'Mois', 'long' => 'Mois'],
                    'tous'    => ['court' => 'Tous', 'long' => 'Tous'],
                ] as $val => $libelle)
                <a href="{{ route('dashboard.rdv.index', array_merge(request()->query(), ['filtre' => $val])) }}"
                   class="flex items-center justify-center gap-1 lg:gap-1.5 px-1 lg:px-3 py-2 rounded-lg text-[11px] lg:text-xs font-semibold whitespace-nowrap min-w-0 transition-all {{ $filtre === $val ? 'bg-white dark:bg-slate-800 shadow-sm text-primary-700 dark:text-primary-300' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}">
                    <span class="lg:hidden">{{ $libelle['court'] }}</span>
                    <span class="hidden lg:inline">{{ $libelle['long'] }}</span>
                    @php $cpt = $counts[$val] ?? 0; @endphp
                    @if($cpt > 0)
                    <span class="inline-flex items-center justify-center min-w-[16px] h-4 lg:min-w-[18px] lg:h-[18px] px-1 rounded-full text-[9px] lg:text-[10px] font-bold {{ $filtre === $val ? 'bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-200' : 'bg-gray-200 dark:bg-slate-600 text-gray-600 dark:text-gray-300' }}">
                        {{ $cpt }}
                    </span>
                    @endif
                </a>
                @endforeach
            </div>

            {{-- Statut + collaborateur : côte à côte sur mobile --}}
            <div class="grid {{ $employes->isNotEmpty() ? 'grid-cols-1 sm:grid-cols-2' : 'grid-cols-1' }} gap-2 w-full lg:contents">
                <select name="statut" onchange="this.form.submit()"
                        class="form-input text-sm py-2 pr-8 w-full lg:w-auto lg:min-w-[150px] lg:flex-shrink-0">
                    <option value="">Tous les statuts</option>
                    <option value="en_attente" {{ $statut === 'en_attente' ? 'selected' : '' }}>En attente</option>
                    <option value="confirme" {{ $statut === 'confirme' ? 'selected' : '' }}>Confirmé</option>
                    <option value="termine" {{ $statut === 'termine' ? 'selected' : '' }}>Terminé</option>
                    <option value="annule" {{ $statut === 'annule' ? 'selected' : '' }}>Annulé</option>
                </select>

                {{-- Filtre par collaborateur (masqué pour un employé : il ne voit que lui) --}}
                @if($employes->isNotEmpty())
                <select name="employe" onchange="this.form.submit()"
                        class="form-input text-sm py-2 pr-8 w-full lg:w-auto lg:min-w-[160px] lg:flex-shrink-0">
                    <option value="">Tous les employés</option>
                    @foreach($employes as $e)
                    <option value="{{ $e->id }}" {{ $employe === $e->id ? 'selected' : '' }}>{{ $e->prenom }} {{ $e->nom_famille }}</option>
                    @endforeach
                </select>
                @endif
            </div>

            {{-- Recherche par nom, téléphone ou e-mail --}}
            <div class="flex items-center gap-2 w-full lg:flex-1 lg:min-w-[200px]">
                <input type="text" name="q" value="{{ $search }}"
                       placeholder="Nom, téléphone, e-mail…"
                       class="form-input text-sm py-2 flex-1 min-w-0">
                <button type="submit" class="btn-outline text-sm py-2 px-3 flex-shrink-0" title="Rechercher">
                    <svg class="w-4 h-4 lg:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="7" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="m16.5 16.5 4 4"/>
                    </svg>
                    <span class="hidden lg:inline">Rechercher</span>
                </button>
                @if($search !== '' || $statut || $employe)
                <a href="{{ route('dashboard.rdv.index', ['filtre' => $filtre]) }}"
                   class="text-xs text-gray-400 hover:text-gray-600 flex-shrink-0" title="Réinitialiser les filtres">
                    <span class="lg:hidden">✕</span>
                    <span class="hidden lg:inline">Réinitialiser</span>
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Prochain RDV (bandeau) --}}
    @if($prochainRdv)
    <div class="card p-4 flex items-center gap-4 border-l-4" style="border-left-color: #9333ea;">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 text-white text-lg"
             style="background: linear-gradient(135deg,#9333ea,#ec4899);">📅</div>
        <div class="flex-1 min-w-0">
            <p class="text-xs font-bold text-primary-600 uppercase tracking-wider mb-0.5">Prochain RDV</p>
            <p class="text-sm font-semibold text-gray-900">
                {{ $prochainRdv->client_nom }} — {{ $prochainRdv->debut_le->translatedFormat('l d F') }} à {{ $prochainRdv->debut_le->format('H\hi') }}
            </p>
            <p class="text-xs text-gray-400">{{ $prochainRdv->label_prestations }}</p>
        </div>
        <a href="{{ route('dashboard.rdv.show', $prochainRdv) }}" class="btn-outline text-xs flex-shrink-0">Voir →</a>
    </div>
    @endif

    {{-- Liste groupée par jour --}}
    @if($rdvs->isEmpty())
    <div class="card py-16 text-center">
        <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-4 text-3xl">📅</div>
        <p class="text-gray-500 font-medium">Aucun rendez-vous sur cette période.</p>
        @if($filtre !== 'tous' && ($counts['tous'] ?? 0) > 0)
        <p class="text-sm text-gray-400 mt-1">
            {{ \App\Helpers\TexteHelper::pluriel($counts['tous'], 'rendez-vous existe', 'rendez-vous existent') }}
            au total sur une autre période.
        </p>
        <a href="{{ route('dashboard.rdv.index', ['filtre' => 'tous', 'statut' => $statut, 'q' => $search]) }}"
           class="btn-outline mt-3 inline-flex">Voir les {{ $counts['tous'] }} rendez-vous</a>
        @endif
        <div class="mt-4">
            <a href="{{ route('dashboard.rdv.create') }}" class="btn-primary inline-flex">Créer un RDV</a>
        </div>
    </div>
    @else
    <div class="space-y-6">
        @foreach($rdvs as $date => $jourdees)
        @php $jour = \Carbon\Carbon::parse($date); @endphp
        <div>
            <div class="flex items-center gap-3 mb-3">
                <div class="flex-shrink-0 w-10 h-10 rounded-xl flex flex-col items-center justify-center text-white text-xs font-bold leading-tight"
                     style="background: linear-gradient(135deg,#9333ea,#ec4899);">
                    <span>{{ $jour->format('d') }}</span>
                    <span class="text-[9px] font-medium uppercase">{{ $jour->translatedFormat('M') }}</span>
                </div>
                <div>
                    <p class="text-sm font-bold text-gray-900">{{ $jour->isToday() ? "Aujourd'hui" : ($jour->isTomorrow() ? 'Demain' : $jour->translatedFormat('l d F Y')) }}</p>
                    <p class="text-xs text-gray-400">{{ $jourdees->count() }} rendez-vous</p>
                </div>
            </div>

            <div class="space-y-2">
                @foreach($jourdees as $rdv)
                @php $badge = $rdv->statut_badge; @endphp
                <div class="card p-3 sm:p-4 flex flex-wrap items-center gap-3 sm:gap-4 hover:shadow-md transition-shadow cursor-pointer"
                     onclick="window.location='{{ route('dashboard.rdv.show', $rdv) }}'">
                    {{-- Heure --}}
                    <div class="flex-shrink-0 text-center w-14">
                        <p class="text-base font-bold text-gray-900">{{ $rdv->debut_le->format('H\hi') }}</p>
                        <p class="text-[10px] text-gray-400">{{ $rdv->duree_minutes }}min</p>
                    </div>

                    {{-- Séparateur --}}
                    <div class="w-px h-10 bg-gray-200 flex-shrink-0"></div>

                    {{-- Infos --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="flex-1 min-w-0 text-sm font-semibold text-gray-900 truncate">{{ $rdv->client_nom }}</p>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold
                                @switch($badge['color'])
                                    @case('amber') bg-amber-100 text-amber-700 @break
                                    @case('blue') bg-blue-100 text-blue-700 @break
                                    @case('emerald') bg-emerald-100 text-emerald-700 @break
                                    @case('red') bg-red-100 text-red-700 @break
                                    @default bg-gray-100 text-gray-700
                                @endswitch">
                                {{ $badge['label'] }}
                            </span>
                            @if($rdv->source === 'vitrine')
                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-700" title="Demande via la vitrine publique">
                                🌐 Vitrine
                            </span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-400 truncate mt-0.5">{{ $rdv->label_prestations }}</p>
                        @if($rdv->employe)
                        <p class="text-[10px] text-gray-300 mt-0.5">👤 {{ $rdv->employe->prenom }}</p>
                        @endif
                    </div>

                    {{-- Actions rapides : 2e rangée pleine largeur sur mobile,
                         alignées à droite sur une seule ligne dès sm --}}
                    @php
                        $btnAction = 'inline-flex items-center justify-center gap-1.5 h-9 px-2 sm:px-0 sm:w-8 rounded-lg font-semibold transition-colors flex-shrink-0';
                    @endphp
                    <div class="flex flex-wrap items-center justify-end gap-1 w-full sm:w-auto flex-shrink-0 mt-1 pt-2 border-t border-gray-100 dark:border-slate-700 sm:mt-0 sm:pt-0 sm:border-0"
                         onclick="event.stopPropagation()">
                        @if($rdv->statut === 'en_attente')
                        <form method="POST" action="{{ route('dashboard.rdv.confirmer', $rdv) }}">
                            @csrf
                            <button type="submit" class="{{ $btnAction }} text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/30" title="Confirmer ce RDV">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="sm:hidden text-[11px]">Confirmer</span>
                            </button>
                        </form>
                        @endif

                        @if(in_array($rdv->statut, ['en_attente', 'confirme']))
                        <form method="POST" action="{{ route('dashboard.rdv.terminer', $rdv) }}"
                              onsubmit="return confirm('Marquer ce rendez-vous comme terminé ?')">
                            @csrf
                            <button type="submit" class="{{ $btnAction }} text-gray-400 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/30" title="Marquer terminé">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="sm:hidden text-[11px]">Terminer</span>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('dashboard.rdv.annuler', $rdv) }}"
                              onsubmit="return confirm('Annuler ce rendez-vous ? Le client sera prévenu par e-mail.')">
                            @csrf
                            <button type="submit" class="{{ $btnAction }} text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30" title="Annuler le RDV">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </form>
                        @endif

                        @if($rdv->client)
                        <a href="{{ route('dashboard.clients.show', $rdv->client) }}"
                           class="{{ $btnAction }} text-gray-400 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/30" title="Fiche client">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </a>
                        @endif

                        <a href="{{ route('dashboard.rdv.edit', $rdv) }}" class="{{ $btnAction }} text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30" title="Modifier">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
        {{-- Pagination réelle (l'ancienne limite tronquait la liste) --}}
        @if($pagines->hasPages())
        <div class="pt-2">
            {{ $pagines->links() }}
        </div>
        @endif
    @endif

</div>
</x-dashboard-layout>
