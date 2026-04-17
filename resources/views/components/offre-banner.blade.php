@props(['class' => '', 'variant' => 'light', 'ctaRoute' => null, 'ctaLabel' => 'Voir les plans'])

@php
    $offresANotifier = \App\Models\OffrePromotionnelle::aNotifier()
        ->orderByDesc('priorite')
        ->get();
    $plansActifs = \App\Models\PlanAbonnement::where('actif', true)->where('prix', '>', 0)->orderBy('ordre')->get();
@endphp

@if($offresANotifier->isNotEmpty())
<div class="{{ $class }}">
    @foreach($offresANotifier as $offre)
    @php
        $plansApplicables = $plansActifs->filter(fn($p) => $offre->appliquableAuPlan($p));
    @endphp
    <div x-data="{
            visible: true,
            storageKey: 'offre_banner_{{ $offre->id }}_' + new Date().toISOString().split('T')[0],
            init() {
                if (localStorage.getItem(this.storageKey)) {
                    this.visible = false;
                }
            },
            dismiss() {
                this.visible = false;
                localStorage.setItem(this.storageKey, '1');
            }
         }"
         x-show="visible"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="relative overflow-hidden rounded-xl border p-4 {{ $variant === 'dark' ? 'bg-white/10 border-white/20' : 'bg-gradient-to-r from-purple-50 to-pink-50 border-purple-200 dark:from-purple-900/20 dark:to-pink-900/20 dark:border-purple-700/50' }}"
    >
        <div class="flex items-center gap-4">
            {{-- Icône cadeau --}}
            <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center bg-gradient-to-r {{ $offre->badge_class }}">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>
            </div>

            {{-- Contenu --}}
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-gradient-to-r {{ $offre->badge_class }} text-white text-[10px] font-bold uppercase tracking-wide">
                        {{ $offre->badge_texte }}
                    </span>
                    <span class="text-sm font-bold {{ $variant === 'dark' ? 'text-white' : 'text-gray-900 dark:text-white' }}">{{ $offre->nom }}</span>
                </div>
                <div class="flex items-center gap-3 mt-1.5 flex-wrap">
                    @foreach($plansApplicables as $planApp)
                    <span class="text-xs {{ $variant === 'dark' ? 'text-white/70' : 'text-gray-600 dark:text-gray-300' }}">
                        <strong>{{ $planApp->nom }}</strong> :
                        <span class="line-through text-gray-400">{{ number_format($planApp->prix, 0, ',', ' ') }}</span>
                        →
                        <span class="font-bold text-emerald-600">{{ number_format($offre->calculerPrix($planApp->prix), 0, ',', ' ') }} FCFA/mois</span>
                    </span>
                    @if(!$loop->last)<span class="text-gray-300">|</span>@endif
                    @endforeach
                </div>
                <p class="text-[11px] {{ $variant === 'dark' ? 'text-white/50' : 'text-gray-400' }} mt-1">Jusqu'au {{ $offre->date_fin->format('d/m/Y') }}</p>
            </div>

            {{-- CTA --}}
            @if($ctaRoute)
            <a href="{{ $ctaRoute }}" class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white transition-all hover:-translate-y-0.5"
               style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                {{ $ctaLabel }}
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
            @endif

            {{-- Bouton fermer --}}
            <button @click="dismiss()" class="flex-shrink-0 p-1.5 rounded-lg transition-colors {{ $variant === 'dark' ? 'text-white/40 hover:text-white/70 hover:bg-white/10' : 'text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>
    @endforeach
</div>
@endif
