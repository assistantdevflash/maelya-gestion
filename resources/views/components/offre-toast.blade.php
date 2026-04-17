@php
    $offresANotifier = \App\Models\OffrePromotionnelle::aNotifier()
        ->orderByDesc('priorite')
        ->get();
    $plansActifs = \App\Models\PlanAbonnement::where('actif', true)->where('prix', '>', 0)->orderBy('ordre')->get();
    $offre = $offresANotifier->first();
@endphp

@if($offre)
@php
    $plansApplicables = $plansActifs->filter(fn($p) => $offre->appliquableAuPlan($p));
@endphp
<div x-data="{
        visible: true,
        storageKey: 'offre_toast_{{ $offre->id }}_' + new Date().toISOString().split('T')[0],
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
     x-cloak
     x-transition:enter="transition ease-out duration-400"
     x-transition:enter-start="opacity-0 translate-y-4 scale-95"
     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
     x-transition:leave-end="opacity-0 translate-y-4 scale-95"
     class="fixed bottom-4 right-4 z-50 w-[340px] max-w-[calc(100vw-2rem)] bg-white dark:bg-slate-800 rounded-2xl shadow-2xl shadow-purple-500/10 border border-purple-200 dark:border-purple-700/50 overflow-hidden"
>
    {{-- Barre supérieure colorée --}}
    <div class="h-1 bg-gradient-to-r {{ $offre->badge_class }}"></div>

    <div class="p-4">
        {{-- Header --}}
        <div class="flex items-start justify-between gap-2 mb-3">
            <div class="flex items-center gap-2">
                <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center bg-gradient-to-r {{ $offre->badge_class }}">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>
                </div>
                <div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-gradient-to-r {{ $offre->badge_class }} text-white text-[9px] font-bold uppercase tracking-wide">
                        {{ $offre->badge_texte }}
                    </span>
                    <p class="text-sm font-bold text-gray-900 dark:text-white mt-0.5">{{ $offre->nom }}</p>
                </div>
            </div>
            <button @click="dismiss()" class="p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Prix par plan --}}
        <div class="space-y-1.5 mb-3">
            @foreach($plansApplicables as $planApp)
            <div class="flex items-center justify-between text-xs">
                <span class="text-gray-600 dark:text-gray-300 font-medium">{{ $planApp->nom }}</span>
                <div class="flex items-center gap-1.5">
                    <span class="line-through text-gray-400 text-[11px]">{{ number_format($planApp->prix, 0, ',', ' ') }}</span>
                    <span class="font-bold text-emerald-600">{{ number_format($offre->calculerPrix($planApp->prix), 0, ',', ' ') }} F/mois</span>
                </div>
            </div>
            @endforeach
        </div>

        <p class="text-[10px] text-gray-400 mb-3">Jusqu'au {{ $offre->date_fin->format('d/m/Y') }}</p>

        {{-- CTA --}}
        <a href="{{ auth()->check() ? route('abonnement.plans') : route('inscription') }}" class="flex items-center justify-center gap-1.5 w-full px-4 py-2 rounded-xl text-xs font-bold text-white transition-all hover:-translate-y-0.5 hover:shadow-lg"
           style="background: linear-gradient(135deg, #9333ea, #ec4899);">
            Profiter de l'offre
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
        </a>
    </div>
</div>
@endif
