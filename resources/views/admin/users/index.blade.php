@extends('layouts.admin')
@section('page-title', 'Utilisateurs')

@section('content')
<div x-data="{ open: {{ $errors->any() ? 'true' : 'false' }} }" class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="page-title">Utilisateurs</h1>
            <p class="page-subtitle">Tous les comptes inscrits sur la plateforme.</p>
        </div>
        <button @click="open = true" class="btn-primary">+ Nouveau compte</button>
    </div>

    {{-- Filtres --}}
    <form method="GET" class="flex flex-wrap gap-3">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Nom, email, téléphone…" class="form-input max-w-xs">
        <select name="role" class="form-input max-w-[180px]">
            <option value="">Tous les rôles</option>
            <option value="admin" @selected(request('role') === 'admin')>Admin</option>
            <option value="employe" @selected(request('role') === 'employe')>Employé</option>
        </select>
        <button class="btn-primary">Filtrer</button>
        @if(request()->hasAny(['q', 'role']))
            <a href="{{ route('admin.users.index') }}" class="btn-secondary">Réinitialiser</a>
        @endif
    </form>

    <div class="card overflow-hidden">
        <table class="table-auto">
            <thead>
            <tr>
                <th>Utilisateur</th>
                <th>Rôle</th>
                <th>Institut</th>
                <th>Téléphone</th>
                <th>Inscrit le</th>
                <th>Statut</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($users as $user)
            <tr class="hover:bg-gray-50">
                <td>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center flex-shrink-0 text-sm font-bold text-primary-700">
                            {{ strtoupper(substr($user->nom_complet ?? $user->name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="font-medium text-gray-900">{{ $user->nom_complet ?? $user->name }}</div>
                            <div class="text-xs text-gray-400">{{ $user->email }}</div>
                        </div>
                    </div>
                </td>
                <td>
                    @php
                        $roleColors = ['admin' => 'bg-violet-100 text-violet-700', 'employe' => 'bg-blue-100 text-blue-700', 'super_admin' => 'bg-amber-100 text-amber-700'];
                        $roleLabels = ['admin' => 'Admin', 'employe' => 'Employé', 'super_admin' => 'Super Admin'];
                    @endphp
                    <span class="badge {{ $roleColors[$user->role] ?? 'bg-gray-100 text-gray-500' }} text-xs">
                        {{ $roleLabels[$user->role] ?? $user->role }}
                    </span>
                </td>
                <td class="text-sm text-gray-600">
                    @if($user->institut)
                        <a href="{{ route('admin.instituts.show', $user->institut) }}" class="hover:text-primary-600 hover:underline">
                            {{ $user->institut->nom }}
                        </a>
                    @else
                        <span class="text-gray-400">—</span>
                    @endif
                </td>
                <td class="text-sm text-gray-600">{{ $user->telephone ?? '—' }}</td>
                <td class="text-sm text-gray-500">{{ $user->created_at->format('d/m/Y') }}</td>
                <td>
                    <span class="badge {{ $user->actif ? 'badge-success' : 'bg-gray-100 text-gray-500' }} text-xs">
                        {{ $user->actif ? 'Actif' : 'Inactif' }}
                    </span>
                </td>
                <td>
                    @if(!$user->isSuperAdmin())
                    <form id="form-user-{{ $user->id }}" action="{{ route('admin.users.toggle', $user) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border transition
                            {{ $user->actif
                                ? 'border-red-200 text-red-600 bg-red-50 hover:bg-red-100'
                                : 'border-emerald-200 text-emerald-600 bg-emerald-50 hover:bg-emerald-100' }}"
                            onclick="window.dispatchEvent(new CustomEvent('confirm-action',{detail:{formId:'form-user-{{ $user->id }}',title:'{{ $user->actif ? 'Désactiver' : 'Activer' }} ce compte',message:'{{ $user->actif ? 'Ce compte sera désactivé et l\'utilisateur ne pourra plus se connecter.' : 'Ce compte sera réactivé.' }}',confirmLabel:'{{ $user->actif ? 'Désactiver' : 'Activer' }}',confirmClass:'{{ $user->actif ? '!bg-red-600 hover:!bg-red-700' : '!bg-emerald-600 hover:!bg-emerald-700' }}',danger:{{ $user->actif ? 'true' : 'false' }}}}))">
                            @if($user->actif)
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                Désactiver
                            @else
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Activer
                            @endif
                        </button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center py-10 text-gray-400">Aucun utilisateur.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $users->withQueryString()->links() }}

    {{-- Modal création --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
         @keydown.escape.window="open = false">
        <div @click.outside="open = false" class="bg-white rounded-2xl shadow-xl w-full max-w-md p-7">
            <h2 class="font-bold text-gray-900 text-lg mb-1">Nouveau collaborateur</h2>
            <p class="text-sm text-gray-400 mb-5">Ce compte aura les droits Super Admin.</p>

            @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 rounded-xl p-3 text-sm text-red-700 space-y-1">
                @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
            </div>
            @endif

            <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-4">
                @csrf

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Prénom <span class="text-red-500">*</span></label>
                        <input type="text" name="prenom" value="{{ old('prenom') }}" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">Nom <span class="text-red-500">*</span></label>
                        <input type="text" name="nom_famille" value="{{ old('nom_famille') }}" class="form-input" required>
                    </div>
                </div>

                <div>
                    <label class="form-label">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-input" required>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Téléphone</label>
                        <input type="text" name="telephone" value="{{ old('telephone') }}" class="form-input">
                    </div>
                </div>

                <div>
                    <label class="form-label">Mot de passe <span class="text-red-500">*</span></label>
                    <input type="password" name="password" class="form-input" required minlength="8">
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="open = false" class="btn-secondary">Annuler</button>
                    <button class="btn-primary" type="submit">Créer</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
