<x-dashboard-layout>
    <div class="space-y-5">
        {{-- Bannière anniversaire --}}
        @if($client->isAnniversaire())
        <x-banniere-anniversaire :clients="collect([$client])" />
        @endif

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard.clients.index') }}" class="btn-icon text-gray-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                    </svg>
                </a>
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-primary-400 to-secondary-400 rounded-full flex items-center justify-center text-white text-lg font-bold">
                        {{ strtoupper(substr($client->prenom, 0, 1)) }}{{ strtoupper(substr($client->nom, 0, 1)) }}
                    </div>
                    <div>
                        <h1 class="text-xl font-display font-bold text-gray-900">{{ $client->nom_complet }}</h1>
                        @if($client->date_naissance)
                            <p class="text-sm text-gray-500">{{ $client->naissance_formatee }}</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('dashboard.caisse') }}?client={{ $client->id }}" class="btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Nouvelle vente
                </a>
                <button type="button" x-data @click="$dispatch('open-edit-show')" class="btn-outline">Modifier</button>
                @if($client->fidelite_token)
                    <a href="{{ route('public.carte-fidelite', $client->fidelite_token) }}" target="_blank"
                       class="btn-outline" title="Carte de fidélité (QR)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm14 0h2v2h-2v-2zm-4 0h2v2h-2v-2zm0 4h2v2h-2v-2zm4 0h2v2h-2v-2z"/>
                        </svg>
                        Carte fidélité
                    </a>
                @endif
            </div>
        </div>

        {{-- KPI -- }}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="stat-card text-center">
                <p class="text-2xl font-bold text-primary-600">{{ $client->nombre_visites }}</p>
                <p class="text-xs text-gray-500 mt-1">Visites</p>
            </div>
            <div class="stat-card text-center">
                <p class="text-2xl font-bold text-secondary-600">{{ number_format($client->total_depense, 0, ',', ' ') }}</p>
                <p class="text-xs text-gray-500 mt-1">FCFA dépensés</p>
            </div>
            <div class="stat-card text-center">
                <p class="text-sm font-semibold text-gray-900">{{ $client->derniere_visite?->diffForHumans() ?? 'Jamais' }}</p>
                <p class="text-xs text-gray-500 mt-1">Dernière visite</p>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-5">
            {{-- Infos client --}}
            <div class="card p-5">
                <h2 class="font-semibold text-gray-900 mb-4 text-sm">📋 Informations</h2>
                <div class="space-y-3 text-sm">
                    @if($client->telephone)
                    <div class="flex items-center gap-2 text-gray-600">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        {{ $client->telephone }}
                    </div>
                    @endif
                    @if($client->email)
                    <div class="flex items-center gap-2 text-gray-600">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        {{ $client->email }}
                    </div>
                    @endif
                    @if($client->notes)
                    <div class="text-gray-600 bg-gray-50 rounded-lg p-3 text-xs leading-relaxed">
                        {{ $client->notes }}
                    </div>
                    @endif
                </div>
            </div>

            {{-- Onglets Achats / Rendez-vous --}}
            <div class="lg:col-span-2 card overflow-hidden" x-data="{ onglet: 'achats' }">
                {{-- En-tête onglets --}}
                <div class="p-3 border-b border-gray-100 dark:border-slate-700 flex items-center gap-1 bg-gray-50/60 dark:bg-slate-800/50">
                    <button type="button" x-on:click="onglet = 'achats'"
                            class="px-4 py-2 rounded-xl text-xs font-semibold transition-all"
                            :class="onglet === 'achats' ? 'bg-white dark:bg-slate-700 shadow-sm text-primary-700 dark:text-primary-300' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200'">
                        🛍️ Achats
                        <span class="ml-1 text-[10px] font-bold text-gray-400">{{ $ventes->total() }}</span>
                    </button>
                    @if(auth()->user()?->aFonctionnalite('rdv'))
                    <button type="button" x-on:click="onglet = 'rdv'"
                            class="px-4 py-2 rounded-xl text-xs font-semibold transition-all"
                            :class="onglet === 'rdv' ? 'bg-white dark:bg-slate-700 shadow-sm text-primary-700 dark:text-primary-300' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200'">
                        📅 Rendez-vous
                        <span class="ml-1 text-[10px] font-bold text-gray-400">{{ $rdvAVenir->count() + $rdvPasses->count() }}</span>
                    </button>
                    @endif
                </div>

                {{-- Onglet Achats --}}
                <div x-show="onglet === 'achats'" class="divide-y divide-gray-50 dark:divide-slate-700 max-h-96 overflow-y-auto">
                    @forelse($ventes as $vente)
                    <div class="px-5 py-3 flex items-center justify-between text-sm">
                        <div>
                            <p class="font-medium text-gray-900">{{ number_format($vente->total, 0, ',', ' ') }} FCFA</p>
                            <p class="text-xs text-gray-400">{{ $vente->created_at->format('d/m/Y à H:i') }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="badge {{ $vente->mode_paiement === 'mobile_money' ? 'badge-primary' : 'badge-secondary' }}">
                                {{ $vente->mode_paiement === 'mobile_money' ? 'Mobile' : 'Cash' }}
                            </span>
                            <a href="{{ route('dashboard.ventes.show', $vente) }}" class="btn-icon text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                    @empty
                    <div class="px-5 py-8 text-center text-sm text-gray-400">Aucun achat enregistré.</div>
                    @endforelse
                </div>

                {{-- Onglet RDV --}}
                @if(auth()->user()?->aFonctionnalite('rdv'))
                <div x-show="onglet === 'rdv'" x-cloak class="divide-y divide-gray-50 dark:divide-slate-700 max-h-96 overflow-y-auto">
                    @if($rdvAVenir->isNotEmpty())
                    <div class="px-5 py-2 text-[10px] font-bold uppercase tracking-wider text-primary-600 dark:text-primary-400 bg-primary-50/40 dark:bg-primary-900/20">
                        À venir ({{ $rdvAVenir->count() }})
                    </div>
                    @foreach($rdvAVenir as $rdv)
                    <div class="px-5 py-3 flex items-center justify-between text-sm">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-slate-100">{{ $rdv->debut_le->translatedFormat('d F Y') }} à {{ $rdv->debut_le->format('H\hi') }}</p>
                            <p class="text-xs text-gray-400 dark:text-slate-500">{{ $rdv->label_prestations }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            @php $badge = $rdv->statut_badge; @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold
                                @switch($badge['color'])
                                    @case('amber') bg-amber-100 text-amber-700 @break
                                    @case('blue') bg-blue-100 text-blue-700 @break
                                    @default bg-gray-100 text-gray-700
                                @endswitch">{{ $badge['label'] }}</span>
                            <a href="{{ route('dashboard.rdv.show', $rdv) }}" class="btn-icon text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                    @endforeach
                    @endif

                    @if($rdvPasses->isNotEmpty())
                    <div class="px-5 py-2 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-slate-500 bg-gray-50/60 dark:bg-slate-800/60">
                        Historique
                    </div>
                    @foreach($rdvPasses as $rdv)
                    <div class="px-5 py-3 flex items-center justify-between text-sm opacity-70">
                        <div>
                            <p class="font-medium text-gray-700 dark:text-slate-300">{{ $rdv->debut_le->translatedFormat('d F Y') }} à {{ $rdv->debut_le->format('H\hi') }}</p>
                            <p class="text-xs text-gray-400 dark:text-slate-500">{{ $rdv->label_prestations }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            @php $badge = $rdv->statut_badge; @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold
                                @switch($badge['color'])
                                    @case('emerald') bg-emerald-100 text-emerald-700 @break
                                    @case('red') bg-red-100 text-red-700 @break
                                    @default bg-gray-100 text-gray-600
                                @endswitch">{{ $badge['label'] }}</span>
                            <a href="{{ route('dashboard.rdv.show', $rdv) }}" class="btn-icon text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                    @endforeach
                    @endif

                    @if($rdvAVenir->isEmpty() && $rdvPasses->isEmpty())
                    <div class="px-5 py-8 text-center text-sm text-gray-400">Aucun rendez-vous enregistré.</div>
                    @endif

                    <div class="px-5 py-3">
                        <a href="{{ route('dashboard.rdv.create') }}?client_id={{ $client->id }}"
                           class="text-xs text-primary-600 hover:underline font-medium">+ Créer un RDV pour ce client</a>
                    </div>
                </div>
                @endif

            </div>
        </div>

        {{-- ═══ MODAL ÉDITION ═══ --}}
        <div x-data="{ show: false }"
             @open-edit-show.window="show = true"
             x-init="{{ $errors->any() ? 'show = true' : '' }}"
             x-show="show" x-cloak
             class="modal-backdrop"
             @keydown.escape.window="show = false"
             @click.self="show = false">
            <div class="modal max-w-lg" x-transition @click.stop>
                <div class="modal-header">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, rgba(147,51,234,0.1), rgba(236,72,153,0.1));">
                            <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                        <h3 class="modal-title">Modifier le client</h3>
                    </div>
                    <button @click="show = false" class="btn-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    @if($errors->any())
                    <div class="mb-4 p-3 bg-red-50 rounded-xl text-sm text-red-600 space-y-0.5">
                        @foreach($errors->all() as $e)<p>&bull; {{ $e }}</p>@endforeach
                    </div>
                    @endif
                    <form method="POST" action="{{ route('dashboard.clients.update', $client) }}" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-2 gap-3">
                            <div class="form-group mb-0">
                                <label class="form-label">Prénom *</label>
                                <input type="text" name="prenom" required maxlength="50" class="form-input"
                                       value="{{ old('prenom', $client->prenom) }}">
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">Nom *</label>
                                <input type="text" name="nom" required maxlength="50" class="form-input"
                                       value="{{ old('nom', $client->nom) }}">
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">Téléphone *</label>
                                <input type="text" name="telephone" required maxlength="30" class="form-input"
                                       value="{{ old('telephone', $client->telephone) }}">
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" maxlength="255" class="form-input"
                                       value="{{ old('email', $client->email) }}">
                            </div>
                            <div class="col-span-2 form-group mb-0">
                                <label class="form-label">Anniversaire (jour et mois)</label>
                                <div class="grid grid-cols-2 gap-2">
                                    @php
                                        $dn = old('date_naissance', $client->date_naissance ?? '');
                                        $dnMois = $dn ? substr($dn, 0, 2) : '';
                                        $dnJour = $dn ? substr($dn, 3, 2) : '';
                                        $mois = ['01'=>'Janvier','02'=>'Février','03'=>'Mars','04'=>'Avril','05'=>'Mai','06'=>'Juin','07'=>'Juillet','08'=>'Août','09'=>'Septembre','10'=>'Octobre','11'=>'Novembre','12'=>'Décembre'];
                                    @endphp
                                    <select name="date_naissance_mois" class="form-input">
                                        <option value="">Mois</option>
                                        @foreach($mois as $n => $m)
                                        <option value="{{ $n }}" @selected($dnMois === $n)>{{ $m }}</option>
                                        @endforeach
                                    </select>
                                    <select name="date_naissance_jour" class="form-input">
                                        <option value="">Jour</option>
                                        @for($d = 1; $d <= 31; $d++)
                                        @php $ds = str_pad($d, 2, '0', STR_PAD_LEFT) @endphp
                                        <option value="{{ $ds }}" @selected($dnJour === $ds)>{{ $d }}</option>
                                        @endfor
                                    </select>
                                </div>
                                {{-- Champ hidden calculé via JS simple --}}
                                <input type="hidden" name="date_naissance" id="show-dn-hidden" value="{{ $dn }}">
                                <script>
                                (function() {
                                    document.addEventListener('DOMContentLoaded', function() {
                                        var moisSel = document.querySelector('[name=date_naissance_mois]');
                                        var jourSel = document.querySelector('[name=date_naissance_jour]');
                                        var hidden  = document.getElementById('show-dn-hidden');
                                        function update() {
                                            hidden.value = (moisSel.value && jourSel.value) ? moisSel.value + '-' + jourSel.value : '';
                                        }
                                        if (moisSel) moisSel.addEventListener('change', update);
                                        if (jourSel) jourSel.addEventListener('change', update);
                                    });
                                })();
                                </script>
                            </div>
                            <div class="col-span-2 form-group mb-0">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" rows="2" maxlength="1000" class="form-input resize-none"
                                          placeholder="Allergies, préférences...">{{ old('notes', $client->notes) }}</textarea>
                            </div>
                        </div>
                        <div class="flex gap-3 pt-1">
                            <button type="button" @click="show = false" class="btn btn-outline flex-1 justify-center">Annuler</button>
                            <button type="submit" class="btn-primary flex-1 justify-center">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ═══ GALERIE PHOTOS AVANT/APRÈS ═══ --}}
        @php
            $photosData = $client->photos->map(fn($p) => [
                'url'     => $p->url,
                'legende' => $p->legende ?? '',
                'type'    => $p->type,
                'label'   => match($p->type) {
                    'avant'       => 'Avant',
                    'apres'       => 'Après',
                    'avant_apres' => 'Avant/Après',
                    default       => 'Autre',
                },
                'color'   => match($p->type) {
                    'avant'       => 'bg-amber-500',
                    'apres'       => 'bg-emerald-500',
                    'avant_apres' => 'bg-blue-500',
                    default       => 'bg-gray-500',
                },
            ])->values()->all();
        @endphp
        <div class="card p-5 mt-8"
             x-data="{
                 photos: @js($photosData),
                 current: 0,
                 open: false,
                 openAt(i) { this.current = i; this.open = true; },
                 prev() { this.current = (this.current - 1 + this.photos.length) % this.photos.length; },
                 next() { this.current = (this.current + 1) % this.photos.length; }
             }"
             @keydown.arrow-left.window="if(open) prev()"
             @keydown.arrow-right.window="if(open) next()"
             @keydown.escape.window="open = false">

            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="font-bold text-gray-900 dark:text-slate-100">Galerie photos</h2>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $client->photos->count() }} photo(s)</p>
                </div>
                <button x-data type="button" @click="$dispatch('open-photos-modal')" class="btn-primary text-xs">
                    + Ajouter
                </button>
            </div>

            @if($client->photos->count() > 0)
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                    @foreach($client->photos as $i => $photo)
                        <div class="flex flex-col gap-1">
                            <div class="relative group cursor-pointer" @click="openAt({{ $i }})">
                                <img src="{{ $photo->url }}" alt="{{ $photo->legende }}"
                                     class="w-full aspect-square object-cover rounded-lg hover:opacity-80 transition">
                                {{-- Badge type --}}
                                @php
                                    $typeColor = match($photo->type) {
                                        'avant' => 'bg-amber-500',
                                        'apres' => 'bg-emerald-500',
                                        'avant_apres' => 'bg-blue-500',
                                        default => 'bg-gray-500',
                                    };
                                    $typeLabel = match($photo->type) {
                                        'avant' => 'Avant',
                                        'apres' => 'Après',
                                        'avant_apres' => 'Avant/Après',
                                        default => 'Autre',
                                    };
                                @endphp
                                <div class="absolute top-1.5 left-1.5">
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold text-white {{ $typeColor }} uppercase shadow">
                                        {{ $typeLabel }}
                                    </span>
                                </div>
                                {{-- Bouton supprimer --}}
                                @if(auth()->user()->isAdmin())
                                <form method="POST" action="{{ route('dashboard.clients.photos.destroy', [$client, $photo]) }}"
                                      id="delete-photo-{{ $photo->id }}"
                                      class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition"
                                      @click.stop>
                                    @csrf @method('DELETE')
                                    <button type="button"
                                            onclick="window.dispatchEvent(new CustomEvent('confirm-delete',{detail:{formId:'delete-photo-{{ $photo->id }}',title:'Supprimer cette photo ?',message:'Cette photo sera d\u00e9finitivement supprim\u00e9e.'}}))"
                                            class="w-6 h-6 bg-red-600 text-white rounded flex items-center justify-center hover:bg-red-700">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </form>
                                @endif
                                {{-- Légende overlay au survol --}}
                                @if($photo->legende)
                                <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent rounded-b-lg px-2 pt-6 pb-1.5
                                            opacity-0 group-hover:opacity-100 transition pointer-events-none">
                                    <p class="text-white text-[10px] leading-tight truncate">{{ $photo->legende }}</p>
                                </div>
                                @endif
                            </div>
                            {{-- Légende sous la vignette (toujours visible) --}}
                            @if($photo->legende)
                            <p class="text-[10px] text-gray-500 dark:text-slate-400 truncate leading-tight px-0.5">{{ $photo->legende }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400 text-center py-8">Aucune photo. Ajoutez-en pour suivre l'évolution.</p>
            @endif

            {{-- ── Lightbox / Slider ── --}}
            <div x-show="open" x-cloak
                 class="fixed inset-0 z-[80] bg-black/95 flex items-center justify-center"
                 @click="open = false">
                <div class="relative w-full max-w-3xl mx-4 flex flex-col items-center" @click.stop>

                    {{-- Bouton fermer --}}
                    <button @click="open = false"
                            class="absolute -top-10 right-0 text-white/70 hover:text-white transition text-3xl leading-none">&times;</button>

                    {{-- Photo --}}
                    <img :src="photos[current].url"
                         :alt="photos[current].legende"
                         class="max-h-[72vh] w-auto max-w-full object-contain rounded-xl shadow-2xl">

                    {{-- Badge + légende --}}
                    <div class="mt-4 flex flex-col items-center gap-1.5">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold text-white uppercase"
                              :class="{
                                  'bg-amber-500':   photos[current].type === 'avant',
                                  'bg-emerald-500': photos[current].type === 'apres',
                                  'bg-blue-500':    photos[current].type === 'avant_apres',
                                  'bg-gray-500':    !['avant','apres','avant_apres'].includes(photos[current].type)
                              }"
                              x-text="photos[current].label"></span>
                        <p class="text-white/80 text-sm text-center" x-show="photos[current].legende" x-text="photos[current].legende"></p>
                        <p class="text-gray-500 text-xs" x-text="(current + 1) + ' / ' + photos.length"></p>
                    </div>

                    {{-- Prev --}}
                    <button x-show="photos.length > 1"
                            @click.stop="prev()"
                            class="absolute left-0 top-1/3 -translate-y-1/2 -translate-x-12 w-10 h-10 rounded-full bg-white/15 hover:bg-white/30 text-white flex items-center justify-center transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    {{-- Next --}}
                    <button x-show="photos.length > 1"
                            @click.stop="next()"
                            class="absolute right-0 top-1/3 -translate-y-1/2 translate-x-12 w-10 h-10 rounded-full bg-white/15 hover:bg-white/30 text-white flex items-center justify-center transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>

                    {{-- Miniatures --}}
                    <div class="flex gap-2 mt-4 overflow-x-auto max-w-full pb-1" x-show="photos.length > 1">
                        <template x-for="(p, i) in photos" :key="i">
                            <button @click.stop="current = i"
                                    class="flex-shrink-0 w-12 h-12 rounded-lg overflow-hidden border-2 transition"
                                    :class="i === current ? 'border-white' : 'border-transparent opacity-50 hover:opacity-80'">
                                <img :src="p.url" :alt="p.legende" class="w-full h-full object-cover">
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal d'ajout photos --}}
        <div x-data="{ show: false }" x-cloak
             @open-photos-modal.window="show = true">
            <div x-show="show" class="fixed inset-0 z-[70] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50" @click="show = false"></div>
                <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-2xl w-full max-w-md p-6" @click.stop>
                    <h3 class="font-bold text-gray-900 dark:text-gray-100 mb-4">Ajouter des photos</h3>
                    <form method="POST" action="{{ route('dashboard.clients.photos.store', $client) }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <div>
                            <label class="form-label">Type *</label>
                            <select name="type" required class="form-select">
                                <option value="avant">Avant</option>
                                <option value="apres">Après</option>
                                <option value="avant_apres" selected>Avant / Après</option>
                                <option value="autre">Autre</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Date</label>
                            <input type="date" name="date_prise" value="{{ now()->toDateString() }}" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Légende</label>
                            <input type="text" name="legende" maxlength="255" class="form-input" placeholder="Ex: Soin du visage">
                        </div>
                        <div>
                            <label class="form-label">Photos (jpg/png/webp, max 5 Mo) *</label>
                            <input type="file" name="photos[]" multiple required accept="image/*" class="form-input">
                        </div>
                        <div class="flex gap-3 pt-2">
                            <button type="button" @click="show = false" class="btn btn-outline flex-1 justify-center">Annuler</button>
                            <button type="submit" class="btn-primary flex-1 justify-center">Téléverser</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-dashboard-layout>
