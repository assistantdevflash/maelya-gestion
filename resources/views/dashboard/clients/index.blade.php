<x-dashboard-layout>
@php
    // Repopulation du modal édition après erreur de validation
    $editErrorClientId = ($errors->any() && old('_form') === 'edit') ? old('_client_id') : null;
    $editErrorClient   = $editErrorClientId ? $clients->firstWhere('id', $editErrorClientId) : null;
    if ($editErrorClientId && !$editErrorClient) {
        $editErrorClient = \App\Models\Client::find($editErrorClientId);
    }
@endphp
<div x-data="{
        createOpen: {{ ($errors->any() && old('_form') === 'create') ? 'true' : 'false' }},
        editOpen:   {{ ($editErrorClient ? 'true' : 'false') }},
        edit: {
            id:             @js(old('_client_id', $editErrorClient?->id ?? '')),
            action:         @js($editErrorClient ? route('dashboard.clients.update', $editErrorClient) : ''),
            prenom:         @js(old('prenom',         $editErrorClient?->prenom ?? '')),
            nom:            @js(old('nom',            $editErrorClient?->nom ?? '')),
            telephone:      @js(old('telephone',      $editErrorClient?->telephone ?? '')),
            email:          @js(old('email',          $editErrorClient?->email ?? '')),
            date_naissance: @js(old('date_naissance', $editErrorClient?->date_naissance?->format('Y-m-d') ?? '')),
            notes:          @js(old('notes',          $editErrorClient?->notes ?? '')),
        },
        openEdit(client) {
            this.edit = client;
            this.editOpen = true;
        }
     }" class="space-y-5">

    {{-- En-tête --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-display font-bold text-gray-900 tracking-tight">Clients</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $clients->total() }} client(s) au total</p>
        </div>
        <button @click="createOpen = true" class="btn-primary group">
            <svg class="w-4 h-4 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Nouveau client
        </button>
    </div>

    {{-- Flash --}}
    @if(session('success'))
    <div class="flex items-center gap-3 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-sm text-emerald-700">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Recherche --}}
    <div class="card p-4">
        <form method="GET" action="{{ route('dashboard.clients.index') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Nom, téléphone, email..."
                       class="form-input pl-9">
            </div>
            <button type="submit" class="btn-outline">Rechercher</button>
            @if(request('q'))
                <a href="{{ route('dashboard.clients.index') }}" class="btn btn-ghost">Effacer</a>
            @endif
        </form>
    </div>

    {{-- Liste --}}
    @if($clients->count() > 0)
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Client</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 hidden sm:table-cell">Téléphone</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600 hidden md:table-cell">Visites</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600 hidden lg:table-cell">Total dépensé</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($clients as $client)
                    <tr class="hover:bg-gray-50 transition-colors group">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-gradient-to-br from-primary-400 to-secondary-400 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                    {{ strtoupper(substr($client->prenom, 0, 1)) }}{{ strtoupper(substr($client->nom, 0, 1)) }}
                                </div>
                                <div>
                                    <a href="{{ route('dashboard.clients.show', $client) }}" class="font-semibold text-gray-900 hover:text-primary-600 transition-colors">
                                        {{ $client->nom_complet }}
                                    </a>
                                    @if($client->email)
                                        <p class="text-xs text-gray-400">{{ $client->email }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-600 hidden sm:table-cell">{{ $client->telephone ?? '—' }}</td>
                        <td class="px-4 py-3 text-right hidden md:table-cell">
                            <span class="badge badge-secondary">{{ $client->ventes_count ?? 0 }}</span>
                        </td>
                        <td class="px-4 py-3 text-right hidden lg:table-cell font-semibold text-gray-900">
                            {{ number_format($client->total_depense ?? 0, 0, ',', ' ') }} FCFA
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1.5">
                                {{-- Voir --}}
                                <a href="{{ route('dashboard.clients.show', $client) }}" class="btn-icon" title="Voir fiche">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                {{-- Modifier → ouvre modal édition --}}
                                <button type="button" title="Modifier"
                                        @click="openEdit({
                                            id:             @js($client->id),
                                            action:         @js(route('dashboard.clients.update', $client)),
                                            prenom:         @js($client->prenom),
                                            nom:            @js($client->nom),
                                            telephone:      @js($client->telephone ?? ''),
                                            email:          @js($client->email ?? ''),
                                            date_naissance: @js($client->date_naissance?->format('Y-m-d') ?? ''),
                                            notes:          @js($client->notes ?? '')
                                        })"
                                        class="btn-icon">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                {{-- Archiver / Réactiver --}}
                                <form method="POST" action="{{ route('dashboard.clients.archiver', $client) }}"
                                      onsubmit="return confirm('{{ $client->actif ? 'Archiver' : 'Réactiver' }} ce client ?')">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                            class="btn-icon text-amber-500 hover:text-amber-700"
                                            title="{{ $client->actif ? 'Archiver' : 'Réactiver' }}">
                                        @if($client->actif)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8l1 12a2 2 0 002 2h8a2 2 0 002-2L19 8m-9 4v4m4-4v4"/>
                                        </svg>
                                        @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        @endif
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($clients->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $clients->withQueryString()->links() }}
        </div>
        @endif
    </div>
    @else
    <div class="card p-12 text-center">
        <div class="w-16 h-16 bg-gradient-to-br from-primary-50 to-secondary-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <p class="font-semibold text-gray-900 mb-1">Aucun client</p>
        <p class="text-sm text-gray-500 mb-4">
            {{ request('q') ? 'Aucun résultat pour "'.request('q').'"' : 'Commencez par ajouter votre premier client.' }}
        </p>
        <button @click="createOpen = true" class="btn-primary">Ajouter un client</button>
    </div>
    @endif

    {{-- ═══ MODAL CRÉATION ═══ --}}
    <div x-show="createOpen" x-cloak class="modal-backdrop" @keydown.escape.window="createOpen = false" @click.self="createOpen = false">
        <div class="modal max-w-lg" x-transition @click.stop>
            <div class="modal-header">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, rgba(147,51,234,0.1), rgba(236,72,153,0.1));">
                        <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                    </div>
                    <h3 class="modal-title">Nouveau client</h3>
                </div>
                <button @click="createOpen = false" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                @if($errors->any() && old('_form') === 'create')
                <div class="mb-4 p-3 bg-red-50 rounded-xl text-sm text-red-600 space-y-0.5">
                    @foreach($errors->all() as $e)<p>• {{ $e }}</p>@endforeach
                </div>
                @endif
                <form method="POST" action="{{ route('dashboard.clients.store') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="_form" value="create">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="form-group mb-0">
                            <label class="form-label">Prénom *</label>
                            <input type="text" name="prenom" required maxlength="50" class="form-input"
                                   value="{{ old('_form') === 'create' ? old('prenom') : '' }}" placeholder="Fatou">
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Nom *</label>
                            <input type="text" name="nom" required maxlength="50" class="form-input"
                                   value="{{ old('_form') === 'create' ? old('nom') : '' }}" placeholder="Traoré">
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Téléphone *</label>
                            <input type="text" name="telephone" required maxlength="30" class="form-input"
                                   value="{{ old('_form') === 'create' ? old('telephone') : '' }}" placeholder="+225 07 00 00 00">
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" maxlength="255" class="form-input"
                                   value="{{ old('_form') === 'create' ? old('email') : '' }}" placeholder="fatou@exemple.ci">
                        </div>
                        <div class="col-span-2 form-group mb-0">
                            <label class="form-label">Date de naissance</label>
                            <input type="date" name="date_naissance" class="form-input"
                                   value="{{ old('_form') === 'create' ? old('date_naissance') : '' }}">
                        </div>
                        <div class="col-span-2 form-group mb-0">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" rows="2" maxlength="1000" class="form-input resize-none"
                                      placeholder="Allergies, préférences...">{{ old('_form') === 'create' ? old('notes') : '' }}</textarea>
                        </div>
                    </div>
                    <div class="flex gap-3 pt-1">
                        <button type="button" @click="createOpen = false" class="btn btn-outline flex-1 justify-center">Annuler</button>
                        <button type="submit" class="btn-primary flex-1 justify-center">Enregistrer</button>
                    </div>
                </form>
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
                @if($errors->any() && old('_form') === 'edit')
                <div class="mb-4 p-3 bg-red-50 rounded-xl text-sm text-red-600 space-y-0.5">
                    @foreach($errors->all() as $e)<p>• {{ $e }}</p>@endforeach
                </div>
                @endif
                <form method="POST" :action="edit.action" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_form" value="edit">
                    <input type="hidden" name="_client_id" :value="edit.id">
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
                            <label class="form-label">Date de naissance</label>
                            <input type="date" name="date_naissance" class="form-input" x-model="edit.date_naissance">
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
