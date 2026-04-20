<x-dashboard-layout>
    <div class="space-y-5" x-data="{
        editOpen: false,
        edit: {
            id: @js($client->id),
            action: @js(route('dashboard.clients.update', $client)),
            prenom: @js($client->prenom),
            nom: @js($client->nom),
            telephone: @js($client->telephone ?? ''),
            email: @js($client->email ?? ''),
            date_naissance: @js($client->date_naissance ?? ''),
            notes: @js($client->notes ?? '')
        }
    }">
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
                <button type="button" @click="editOpen = true" class="btn-outline">Modifier</button>
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

            {{-- Historique ventes --}}
            <div class="lg:col-span-2 card overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-900 text-sm">🛍️ Historique des achats</h2>
                </div>
                <div class="divide-y divide-gray-50 max-h-96 overflow-y-auto">
                    @forelse($client->ventes()->latest()->take(20)->get() as $vente)
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
            </div>
        </div>

        {{-- ═══ MODAL ÉDITION ═══ --}}
        <div x-show="editOpen" x-cloak class="modal-backdrop" @keydown.escape.window="editOpen = false" @click.self="editOpen = false">
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
                    <button @click="editOpen = false" class="btn-icon">
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
                                <input type="text" name="prenom" required maxlength="50" class="form-input" x-model="edit.prenom">
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">Nom *</label>
                                <input type="text" name="nom" required maxlength="50" class="form-input" x-model="edit.nom">
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">Téléphone *</label>
                                <input type="text" name="telephone" required maxlength="30" class="form-input" x-model="edit.telephone">
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" maxlength="255" class="form-input" x-model="edit.email">
                            </div>
                            <div class="col-span-2 form-group mb-0">
                                <label class="form-label">Anniversaire (jour et mois)</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <select name="date_naissance_mois" class="form-input"
                                            @change="edit.date_naissance = $el.value ? $el.value + '-' + ($refs.jourshow?.value||'01') : ''">
                                        <option value="">Mois</option>
                                        @foreach(['01'=>'Janvier','02'=>'Février','03'=>'Mars','04'=>'Avril','05'=>'Mai','06'=>'Juin','07'=>'Juillet','08'=>'Août','09'=>'Septembre','10'=>'Octobre','11'=>'Novembre','12'=>'Décembre'] as $n=>$m)
                                        <option value="{{ $n }}" :selected="edit.date_naissance && edit.date_naissance.substring(0,2) === '{{ $n }}'">{{ $m }}</option>
                                        @endforeach
                                    </select>
                                    <select name="date_naissance_jour" x-ref="jourshow" class="form-input"
                                            @change="edit.date_naissance = (edit.date_naissance?.substring(0,2)||'01') + '-' + $el.value">
                                        <option value="">Jour</option>
                                        @for($d=1;$d<=31;$d++)
                                        @php $ds=str_pad($d,2,'0',STR_PAD_LEFT) @endphp
                                        <option value="{{ $ds }}" :selected="edit.date_naissance && edit.date_naissance.substring(3,5) === '{{ $ds }}'">{{ $d }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <input type="hidden" name="date_naissance" :value="edit.date_naissance">
                            </div>
                            <div class="col-span-2 form-group mb-0">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" rows="2" maxlength="1000" class="form-input resize-none"
                                          x-model="edit.notes" placeholder="Allergies, préférences..."></textarea>
                            </div>
                        </div>
                        <div class="flex gap-3 pt-1">
                            <button type="button" @click="editOpen = false" class="btn btn-outline flex-1 justify-center">Annuler</button>
                            <button type="submit" class="btn-primary flex-1 justify-center">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-dashboard-layout>
