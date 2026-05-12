<x-dashboard-layout>
<div class="space-y-5">

    {{-- En-tête --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="page-title">Rendez-vous</h1>
            <p class="page-subtitle">Gérez l'agenda de votre établissement.</p>
        </div>
        <a href="{{ route('dashboard.rdv.create') }}" class="btn-primary flex-shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Nouveau RDV
        </a>
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
        <form method="GET" action="{{ route('dashboard.rdv.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="flex gap-1 p-1 bg-gray-100/80 rounded-xl">
                @foreach(['today' => "Aujourd'hui", 'semaine' => 'Semaine', 'mois' => 'Mois', 'tous' => 'Tous'] as $val => $label)
                <a href="{{ route('dashboard.rdv.index', array_merge(request()->query(), ['filtre' => $val])) }}"
                   class="px-3 py-2 rounded-lg text-xs font-semibold transition-all {{ $filtre === $val ? 'bg-white shadow-sm text-primary-700' : 'text-gray-500 hover:text-gray-700' }}">
                    {{ $label }}
                </a>
                @endforeach
            </div>
            <select name="statut" onchange="this.form.submit()"
                    class="form-input text-sm py-2 pr-8 flex-shrink-0">
                <option value="">Tous les statuts</option>
                <option value="en_attente" {{ $statut === 'en_attente' ? 'selected' : '' }}>En attente</option>
                <option value="confirme" {{ $statut === 'confirme' ? 'selected' : '' }}>Confirmé</option>
                <option value="termine" {{ $statut === 'termine' ? 'selected' : '' }}>Terminé</option>
                <option value="annule" {{ $statut === 'annule' ? 'selected' : '' }}>Annulé</option>
            </select>
        </form>
    </div>

    {{-- Prochain RDV (bandeau) --}}
    @if($prochainRdv && $filtre !== 'today')
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
        <a href="{{ route('dashboard.rdv.create') }}" class="btn-primary mt-4 inline-flex">Créer un RDV</a>
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
                <div class="card p-4 flex items-center gap-4 hover:shadow-md transition-shadow cursor-pointer"
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
                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $rdv->client_nom }}</p>
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
                        </div>
                        <p class="text-xs text-gray-400 truncate mt-0.5">{{ $rdv->label_prestations }}</p>
                        @if($rdv->employe)
                        <p class="text-[10px] text-gray-300 mt-0.5">👤 {{ $rdv->employe->prenom }}</p>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-1 flex-shrink-0" onclick="event.stopPropagation()">
                        <a href="{{ route('dashboard.rdv.edit', $rdv) }}" class="btn-icon text-gray-400 hover:text-blue-600" title="Modifier">
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
    @endif

</div>
</x-dashboard-layout>
