{{--
  Bannière des prochains rendez-vous (dismissable).
  Usage: <x-banniere-rdv :rdvs="$rdvsAvenir" />
  Paramètres :
    - $rdvs : Collection de rendez-vous à venir
  Logique de masquage : chaque RDV masqué ne réapparaît que le lendemain
  (clé localStorage scellée à la date du jour).
--}}
@props(['rdvs'])

@if($rdvs->count() > 0)
<div
    x-data="{
        open: true,
        dismissed: {},
        init() {
            const stored = JSON.parse(localStorage.getItem('rdv_dismissed') || '{}');
            this.dismissed = stored;
        },
        isDismissed(id) { return this.dismissed[id] === '{{ now()->format('Y-m-d') }}'; },
        dismiss(id) {
            this.dismissed[id] = '{{ now()->format('Y-m-d') }}';
            localStorage.setItem('rdv_dismissed', JSON.stringify(this.dismissed));
        },
        allDismissed() {
            return @js($rdvs->pluck('id')->values()).every(id => this.isDismissed(id));
        }
    }"
    x-show="!allDismissed()"
    x-cloak
    class="space-y-2 mb-5"
>
    @foreach($rdvs as $rdv)
    <div
        x-show="!isDismissed('{{ $rdv->id }}')"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="flex items-center gap-3 px-4 py-3 rounded-2xl border border-violet-200 dark:border-violet-800/30 bg-gradient-to-r from-violet-50 to-fuchsia-50 dark:from-violet-950/40 dark:to-fuchsia-950/40 shadow-sm"
    >
        <span class="text-2xl flex-shrink-0">📅</span>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-bold text-gray-900 dark:text-gray-100">
                RDV de <span class="text-primary-600 dark:text-primary-400">{{ $rdv->client_nom }}</span>
                @if($rdv->debut_le->isToday())
                    — <span class="text-emerald-600 dark:text-emerald-400">Aujourd'hui</span> à {{ $rdv->debut_le->format('H\hi') }}
                @elseif($rdv->debut_le->isTomorrow())
                    — <span class="text-emerald-600 dark:text-emerald-400">Demain</span> à {{ $rdv->debut_le->format('H\hi') }}
                @else
                    — {{ $rdv->debut_le->translatedFormat('l d F') }} à {{ $rdv->debut_le->format('H\hi') }}
                @endif
            </p>
            @if($rdv->label_prestations)
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $rdv->label_prestations }}</p>
            @endif
        </div>
        <a href="{{ route('dashboard.rdv.show', $rdv) }}"
           class="flex-shrink-0 px-3 py-1.5 text-xs font-bold rounded-xl text-white transition-all hover:shadow-md"
           style="background: linear-gradient(135deg, #7c3aed, #d946ef);">
            Voir →
        </a>
        <button @click="dismiss('{{ $rdv->id }}')" class="flex-shrink-0 p-1.5 rounded-lg text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-white/60 dark:hover:bg-white/10 transition-all" title="Masquer jusqu'à demain">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
    @endforeach
</div>
@endif
