<x-dashboard-layout>
<div class="max-w-2xl mx-auto space-y-5">

    <div class="flex items-start justify-between gap-4">
        <div>
            <a href="{{ route('dashboard.rdv.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-700 mb-3 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Retour aux RDV
            </a>
            <h1 class="page-title">{{ $rdv->client_nom }}</h1>
            <p class="page-subtitle">{{ $rdv->debut_le->translatedFormat('l d F Y') }} à {{ $rdv->debut_le->format('H\hi') }}</p>
        </div>
        @php $badge = $rdv->statut_badge; @endphp
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold flex-shrink-0 mt-7
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

    {{-- Flash --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
         class="flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-4 py-3 text-sm font-medium">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Détails --}}
    <div class="card p-5 space-y-4">
        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Informations</p>

        <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
            <div>
                <dt class="text-xs text-gray-400 font-medium mb-0.5">Date &amp; heure</dt>
                <dd class="font-semibold text-gray-900">{{ $rdv->debut_le->format('d/m/Y à H\hi') }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 font-medium mb-0.5">Durée</dt>
                <dd class="font-semibold text-gray-900">{{ $rdv->duree_minutes }} min
                    <span class="text-gray-400 font-normal">(fin vers {{ $rdv->fin_le->format('H\hi') }})</span>
                </dd>
            </div>
            @if($rdv->client_telephone)
            <div>
                <dt class="text-xs text-gray-400 font-medium mb-0.5">Téléphone</dt>
                <dd><a href="tel:{{ $rdv->client_telephone }}" class="text-primary-600 hover:underline font-medium">{{ $rdv->client_telephone }}</a></dd>
            </div>
            @endif
            @if($rdv->client_email)
            <div>
                <dt class="text-xs text-gray-400 font-medium mb-0.5">E-mail</dt>
                <dd><a href="mailto:{{ $rdv->client_email }}" class="text-primary-600 hover:underline font-medium text-xs">{{ $rdv->client_email }}</a></dd>
            </div>
            @endif
            @if($rdv->employe)
            <div>
                <dt class="text-xs text-gray-400 font-medium mb-0.5">Employé(e)</dt>
                <dd class="font-medium text-gray-900">{{ $rdv->employe->prenom }} {{ $rdv->employe->nom }}</dd>
            </div>
            @endif
            @if($rdv->rappel_envoye)
            <div>
                <dt class="text-xs text-gray-400 font-medium mb-0.5">Rappel</dt>
                <dd class="text-emerald-600 font-medium text-xs">✔ Envoyé</dd>
            </div>
            @endif
        </dl>

        @if($rdv->prestations->isNotEmpty())
        <div class="pt-2 border-t border-gray-100">
            <p class="text-xs text-gray-400 font-medium mb-2">Prestation(s)</p>
            <div class="flex flex-wrap gap-2">
                @foreach($rdv->prestations as $p)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold" style="background:#f3e8ff;color:#7e22ce;">
                    {{ $p->nom }}
                </span>
                @endforeach
            </div>
        </div>
        @elseif($rdv->prestation_libre)
        <div class="pt-2 border-t border-gray-100">
            <p class="text-xs text-gray-400 font-medium mb-1">Prestation</p>
            <p class="text-sm text-gray-700">{{ $rdv->prestation_libre }}</p>
        </div>
        @endif

        @if($rdv->notes)
        <div class="pt-2 border-t border-gray-100">
            <p class="text-xs text-gray-400 font-medium mb-1">Notes</p>
            <p class="text-sm text-gray-700 whitespace-pre-line">{{ $rdv->notes }}</p>
        </div>
        @endif

        @if($rdv->client)
        <div class="pt-2 border-t border-gray-100">
            <a href="{{ route('dashboard.clients.show', $rdv->client) }}" class="text-sm text-primary-600 hover:underline font-medium">
                → Voir la fiche client complète
            </a>
        </div>
        @endif
    </div>

    {{-- Actions --}}
    @if(!in_array($rdv->statut, ['termine', 'annule']))
    <div class="card p-5" x-data="{ confirmAnnule: false }">
        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4">Actions</p>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('dashboard.rdv.edit', $rdv) }}" class="btn-outline">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Modifier
            </a>

            @if($rdv->statut !== 'termine')
            <form method="POST" action="{{ route('dashboard.rdv.terminer', $rdv) }}">
                @csrf
                <button type="submit" class="btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Marquer terminé
                </button>
            </form>
            @endif

            <button type="button" x-on:click="confirmAnnule = true"
                    class="btn-outline text-red-600 border-red-200 hover:bg-red-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Annuler le RDV
            </button>
        </div>

        {{-- Confirmation annulation --}}
        <div x-show="confirmAnnule" x-cloak
             class="mt-4 p-4 bg-red-50 border border-red-200 rounded-xl text-sm">
            <p class="text-red-700 font-medium mb-3">Confirmer l'annulation ? Le client sera notifié par e-mail.</p>
            <div class="flex gap-3">
                <form method="POST" action="{{ route('dashboard.rdv.annuler', $rdv) }}">
                    @csrf
                    <button type="submit" class="btn-primary bg-red-600 border-red-600 hover:bg-red-700">Oui, annuler</button>
                </form>
                <button type="button" x-on:click="confirmAnnule = false" class="btn-outline">Retour</button>
            </div>
        </div>
    </div>
    @endif

</div>
</x-dashboard-layout>
