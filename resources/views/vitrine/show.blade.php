<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $institut->nom }} — Vitrine</title>
    <meta name="description" content="Découvrez les prestations et produits de {{ $institut->nom }}{{ $institut->ville ? ', ' . $institut->ville : '' }}.">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; background: #0f0f0f; color: #f5f5f5; min-height: 100vh; color-scheme: dark; }
        /* Force les champs de formulaire à rester en mode sombre */
        .rdv-input {
            background: rgba(255,255,255,0.08) !important;
            color: #f9fafb !important;
            border: 1px solid rgba(255,255,255,0.15) !important;
            color-scheme: dark;
        }
        .rdv-input::placeholder { color: #9ca3af !important; }
        .rdv-input:focus { outline: none; border-color: #a855f7 !important; box-shadow: 0 0 0 2px rgba(168,85,247,0.25); }
        .rdv-input option { background: #1f1f1f; color: #f9fafb; }
        /* Overlay modal */
        .rdv-overlay { background: rgba(0,0,0,0.75); backdrop-filter: blur(4px); }
    </style>
</head>
<body class="bg-gray-950 text-white min-h-screen"
      x-data="{ rdvOpen: {{ (session('success') || $errors->any()) && isset($prestationsFlat) && $prestationsFlat->isNotEmpty() ? 'true' : 'false' }} }"
      @keydown.escape.window="rdvOpen = false">

    {{-- ── HEADER ────────────────────────────────────────────────────────── --}}
    <header class="sticky top-0 z-50 bg-gray-950/90 backdrop-blur-md border-b border-white/5">
        <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                @if($institut->logo)
                <img src="{{ $institut->logo_url }}" alt="Logo {{ $institut->nom }}"
                     class="w-10 h-10 rounded-xl object-cover ring-1 ring-white/10">
                @else
                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-white text-base"
                     style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                    {{ strtoupper(substr($institut->nom, 0, 1)) }}
                </div>
                @endif
                <div>
                    <h1 class="font-bold text-base text-white leading-tight">{{ $institut->nom }}</h1>
                    @if($institut->ville)
                    <p class="text-xs text-gray-400">📍 {{ $institut->ville }}</p>
                    @endif
                </div>
            </div>
            @if($institut->telephone)
            <div class="flex items-center gap-2">
                <a href="tel:{{ $institut->telephone }}"
                   class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-2 rounded-xl text-white border border-white/20 hover:bg-white/5 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    Appeler
                </a>
                @if(isset($prestationsFlat) && $prestationsFlat->isNotEmpty())
                <button type="button"
                        @click="rdvOpen = true"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-2 rounded-xl text-white"
                        style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Prendre un RDV
                </button>
                @endif
            </div>
            @elseif(isset($prestationsFlat) && $prestationsFlat->isNotEmpty())
            <button type="button"
                    @click="rdvOpen = true"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-2 rounded-xl text-white"
                    style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Prendre un RDV
            </button>
            @endif
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-8 space-y-10">

        {{-- ── PRESTATIONS ───────────────────────────────────────────────── --}}
        @if($prestations->isNotEmpty())
        <section>
            <div class="flex items-center gap-3 mb-5">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                     style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                    <svg class="w-4.5 h-4.5 text-white w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-white">Prestations</h2>
            </div>

            @foreach($prestations as $categorie => $items)
            <div class="mb-6">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">{{ $categorie }}</p>
                <div class="space-y-2">
                    @foreach($items as $p)
                    <div class="bg-gray-900 border border-white/5 rounded-xl p-4 flex items-center justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-white text-sm">{{ $p->nom }}</p>
                            @if($p->description)
                            <p class="text-xs text-gray-400 mt-0.5 truncate">{{ $p->description }}</p>
                            @endif
                            @if($p->duree)
                            <p class="text-xs text-gray-500 mt-1">⏱ {{ $p->duree }} min</p>
                            @endif
                        </div>
                        <div class="flex-shrink-0 text-right">
                            <p class="font-bold text-white">{{ number_format($p->prix, 0, ',', ' ') }} F</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </section>
        @endif

        {{-- ── PRODUITS ──────────────────────────────────────────────────── --}}
        @if($produits->isNotEmpty())
        <section>
            <div class="flex items-center gap-3 mb-5">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center bg-emerald-600">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-white">Produits</h2>
            </div>

            @foreach($produits as $categorie => $items)
            <div class="mb-6">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">{{ $categorie }}</p>
                <div class="space-y-2">
                    @foreach($items as $p)
                    <div class="bg-gray-900 border border-white/5 rounded-xl p-4 flex items-center justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-white text-sm">{{ $p->nom }}</p>
                            @if($p->description)
                            <p class="text-xs text-gray-400 mt-0.5 truncate">{{ $p->description }}</p>
                            @endif
                        </div>
                        <div class="flex-shrink-0 text-right">
                            <p class="font-bold text-white">{{ number_format($p->prix_vente, 0, ',', ' ') }} F</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </section>
        @endif

        @if($prestations->isEmpty() && $produits->isEmpty())
        <div class="text-center py-20 text-gray-500">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-sm">Aucun article disponible pour le moment.</p>
        </div>
        @endif

        {{-- ── MODAL RÉSERVATION ─────────────────────────────────────── --}}
        @if(isset($prestationsFlat) && $prestationsFlat->isNotEmpty())
        <div x-show="rdvOpen"
             x-cloak
             class="fixed inset-0 z-50 flex items-end sm:items-center justify-center rdv-overlay"
             @click.self="rdvOpen = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            <div class="w-full sm:max-w-lg bg-gray-900 sm:rounded-2xl rounded-t-2xl shadow-2xl border border-white/10 overflow-hidden"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                {{-- En-tête du modal --}}
                <div class="flex items-center justify-between px-5 py-4 border-b border-white/10"
                     style="background: linear-gradient(135deg, rgba(147,51,234,0.2), rgba(236,72,153,0.2));">
                    <div>
                        <h2 class="text-base font-bold text-white">Réserver un rendez-vous</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Votre demande sera confirmée par l'institut.</p>
                    </div>
                    <button @click="rdvOpen = false"
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-white hover:bg-white/10 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Corps du formulaire --}}
                <div class="px-5 py-5 max-h-[80vh] overflow-y-auto">
                    @if(session('success'))
                        <div class="mb-4 p-3 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if($errors->any())
                        <div class="mb-4 p-3 rounded-lg bg-red-500/10 border border-red-500/30 text-red-300 text-sm">
                            @foreach($errors->all() as $err)<div>• {{ $err }}</div>@endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('vitrine.reserver', $institut->slug) }}"
                          x-data="rdvVitrineForm({{ $prestationsFlat->toJson() }}, @json(old('prestations', [])))"
                          class="space-y-3">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-gray-300 mb-1 font-medium">Nom complet *</label>
                                <input type="text" name="client_nom" required
                                       value="{{ old('client_nom') }}"
                                       placeholder="Ex : Awa Koné"
                                       class="rdv-input w-full px-3 py-2.5 rounded-lg text-sm">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-300 mb-1 font-medium">Téléphone *</label>
                                <input type="tel" name="client_telephone" required
                                       value="{{ old('client_telephone') }}"
                                       placeholder="Ex : 07 00 00 00 00"
                                       class="rdv-input w-full px-3 py-2.5 rounded-lg text-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-300 mb-1 font-medium">Email (optionnel)</label>
                            <input type="email" name="client_email"
                                   value="{{ old('client_email') }}"
                                   placeholder="votre@email.com"
                                   class="rdv-input w-full px-3 py-2.5 rounded-lg text-sm">
                        </div>

                        {{-- ── Sélecteur de prestations (search + checkbox) ── --}}
                        <div>
                            <label class="block text-xs text-gray-300 mb-1 font-medium">Prestation(s) souhaitée(s) *</label>

                            {{-- Hidden inputs pour soumission --}}
                            <template x-for="id in selectedIds" :key="'hi-'+id">
                                <input type="hidden" name="prestations[]" :value="id">
                            </template>

                            {{-- Chips sélectionnées --}}
                            <div x-show="selectedIds.length > 0" x-cloak class="flex flex-wrap gap-1.5 mb-2">
                                <template x-for="id in selectedIds" :key="'chip-'+id">
                                    <span class="inline-flex items-center gap-1 pl-2.5 pr-1 py-1 rounded-full text-xs font-semibold"
                                          style="background:rgba(147,51,234,0.25);color:#d8b4fe;border:1px solid rgba(147,51,234,0.4);">
                                        <span x-text="getNom(id)"></span>
                                        <button type="button" @click="toggle(id)"
                                                class="p-0.5 rounded-full hover:bg-white/10 transition">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </span>
                                </template>
                            </div>

                            {{-- Search + dropdown --}}
                            <div @click.outside="open = false" class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none" style="color:#6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <input type="text" x-model="search"
                                       @focus="open = true"
                                       @input="open = true"
                                       placeholder="Rechercher ou choisir une prestation…"
                                       class="rdv-input w-full pl-9 pr-3 py-2.5 rounded-lg text-sm"
                                       autocomplete="off">

                                {{-- Dropdown --}}
                                <div x-show="open" x-cloak
                                     class="absolute z-[60] w-full mt-1 rounded-xl shadow-2xl max-h-52 overflow-y-auto"
                                     style="background:#1e1e2e;border:1px solid rgba(255,255,255,0.12);">
                                    <template x-for="p in filtered" :key="p.id">
                                        <label class="flex items-center gap-3 px-3 py-2.5 cursor-pointer transition-colors border-b last:border-0"
                                               :class="selectedIds.includes(String(p.id))
                                                   ? 'bg-purple-900/30'
                                                   : 'hover:bg-white/5'"
                                               style="border-color:rgba(255,255,255,0.06);">
                                            <input type="checkbox"
                                                   :checked="selectedIds.includes(String(p.id))"
                                                   @change="toggle(String(p.id))"
                                                   class="w-4 h-4 flex-shrink-0 rounded"
                                                   style="accent-color:#9333ea;">
                                            <span class="flex-1 min-w-0">
                                                <span class="block text-sm font-medium" style="color:#f9fafb;" x-text="p.nom"></span>
                                                <span class="text-xs" style="color:#9ca3af;"
                                                      x-text="[p.categorie?.nom, p.duree ? p.duree + ' min' : null, p.prix ? new Intl.NumberFormat('fr-CI').format(p.prix) + ' F' : null].filter(Boolean).join(' · ')"></span>
                                            </span>
                                            <svg x-show="selectedIds.includes(String(p.id))"
                                                 class="w-4 h-4 flex-shrink-0" style="color:#a855f7;"
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </label>
                                    </template>
                                    <p x-show="filtered.length === 0"
                                       class="text-sm text-center py-3" style="color:#6b7280;">Aucune prestation trouvée.</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs text-gray-300 mb-1 font-medium">Date et heure *</label>
                            <input type="datetime-local" name="debut_le" required
                                   min="{{ now()->addHour()->format('Y-m-d\TH:i') }}"
                                   value="{{ old('debut_le') }}"
                                   class="rdv-input w-full px-3 py-2.5 rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-300 mb-1 font-medium">Notes (optionnel)</label>
                            <textarea name="notes" rows="2" maxlength="500"
                                      placeholder="Précisions, demandes particulières…"
                                      class="rdv-input w-full px-3 py-2.5 rounded-lg text-sm resize-none">{{ old('notes') }}</textarea>
                        </div>
                        <button type="submit"
                                class="w-full px-4 py-3 rounded-xl text-white font-semibold text-sm hover:opacity-90 active:scale-[0.98] transition"
                                style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                            Envoyer ma demande
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif

    </main>

    {{-- ── FOOTER ────────────────────────────────────────────────────────── --}}
    <footer class="mt-16 border-t border-white/5 py-6 text-center text-xs text-gray-600">
        <p>Propulsé par <a href="{{ url('/') }}" class="text-primary-400 hover:underline">Maëlya Gestion</a></p>
    </footer>

<script>
function rdvVitrineForm(prestations, selectedIds) {
    return {
        prestations: prestations,
        selectedIds: (selectedIds || []).map(String),
        search: '',
        open: false,

        get filtered() {
            if (!this.search) return this.prestations;
            const q = this.search.toLowerCase();
            return this.prestations.filter(p => p.nom.toLowerCase().includes(q));
        },

        toggle(id) {
            const sid = String(id);
            const idx = this.selectedIds.indexOf(sid);
            idx === -1 ? this.selectedIds.push(sid) : this.selectedIds.splice(idx, 1);
        },

        getNom(id) {
            const p = this.prestations.find(p => String(p.id) === String(id));
            return p ? p.nom : '';
        },
    }
}
</script>

</body>
</html>
