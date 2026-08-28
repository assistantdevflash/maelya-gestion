@extends('layouts.admin')

@section('title', 'Types d\'établissements')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-display font-bold text-gray-900 dark:text-white tracking-tight">
                Types d'établissements
            </h1>
            <p class="text-gray-500 dark:text-slate-400 mt-1">
                Gérez les types d'établissements disponibles lors de l'inscription
            </p>
        </div>
        <a href="{{ route('admin.etablissement-types.create') }}" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Ajouter un type
        </a>
    </div>

    @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    {{-- Liste des types --}}
    <div class="card">
        <div class="card-body p-0">

            {{-- Mobile : cartes --}}
            <div class="sm:hidden divide-y divide-gray-100 dark:divide-slate-700">
                @forelse($types as $type)
                <div class="p-4 flex items-center gap-3">
                    <span class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-slate-700 flex items-center justify-center text-xs font-bold text-gray-500 flex-shrink-0">{{ $type->position }}</span>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-sm text-gray-900 dark:text-white">{{ $type->libelle }}</p>
                        <code class="text-xs bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-300 px-1.5 py-0.5 rounded">{{ $type->code }}</code>
                    </div>
                    <button type="button" onclick="toggleStatus({{ $type->id }}, this)"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium transition-colors flex-shrink-0 {{ $type->actif ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400' }}">
                        {{ $type->actif ? 'Actif' : 'Inactif' }}
                    </button>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <a href="{{ route('admin.etablissement-types.edit', $type) }}" class="text-primary-600 text-xs hover:underline">Modifier</a>
                        <form action="{{ route('admin.etablissement-types.destroy', $type) }}" method="POST" class="inline" onsubmit="return confirm('Supprimer ce type ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 text-xs hover:underline">Suppr.</button>
                        </form>
                    </div>
                </div>
                @empty
                <div class="p-10 text-center text-gray-400 text-sm">Aucun type.</div>
                @endforelse
            </div>

            {{-- Desktop : tableau --}}
            <div class="hidden sm:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-800">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Position</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Code</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Libellé</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Statut</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-700">
                        @forelse($types as $type)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $type->position }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <code class="text-xs bg-gray-100 dark:bg-slate-800 text-gray-800 dark:text-slate-300 px-2 py-1 rounded">{{ $type->code }}</code>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                {{ $type->libelle }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button type="button" 
                                        onclick="toggleStatus({{ $type->id }}, this)"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium transition-colors {{ $type->actif ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400' }}">
                                    {{ $type->actif ? 'Actif' : 'Inactif' }}
                                </button>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <a href="{{ route('admin.etablissement-types.edit', $type) }}" class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300">
                                    Modifier
                                </a>
                                <form action="{{ route('admin.etablissement-types.destroy', $type) }}" method="POST" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce type ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                        Supprimer
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-slate-400">
                                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                                <p class="mt-2">Aucun type d'établissement</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
function toggleStatus(typeId, button) {
    fetch(`/admin/etablissement-types/${typeId}/toggle`, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mettre à jour les classes et le texte
            if (data.actif) {
                button.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium transition-colors bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
                button.textContent = 'Actif';
            } else {
                button.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium transition-colors bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400';
                button.textContent = 'Inactif';
            }
        }
    })
    .catch(error => console.error('Erreur:', error));
}
</script>
@endpush

@endsection
