<x-dashboard-layout>
    <x-slot name="title">Modifier l'inventaire</x-slot>

    <div class="space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-display font-bold text-gray-900 dark:text-slate-100 tracking-tight">
                    Inventaire du {{ $inventaire->date_inventaire->format('d/m/Y') }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">
                    Corrigez les quantités comptées. Le stock n'est pas encore modifié.
                </p>
            </div>
            <a href="{{ route('dashboard.inventaires.show', $inventaire) }}" class="btn-outline self-start">← Retour</a>
        </div>

        <form method="POST" action="{{ route('dashboard.inventaires.update', $inventaire) }}">
            @csrf @method('PUT')

            @include('dashboard.inventaires._form', ['produits' => $produits, 'comptes' => $comptes, 'inventaire' => $inventaire])
        </form>
    </div>
</x-dashboard-layout>
