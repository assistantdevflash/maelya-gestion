<x-dashboard-layout>
    <x-slot name="title">Nouvel inventaire</x-slot>

    <div class="space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-display font-bold text-gray-900 dark:text-slate-100 tracking-tight">Nouvel inventaire</h1>
                <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">
                    Comptez chaque produit. Les écarts sont calculés en direct et valorisés au coût moyen.
                </p>
            </div>
            <a href="{{ route('dashboard.inventaires.index') }}" class="btn-outline self-start">← Retour</a>
        </div>

        <form method="POST" action="{{ route('dashboard.inventaires.store') }}">
            @csrf

            @include('dashboard.inventaires._form', ['produits' => $produits])
        </form>
    </div>
</x-dashboard-layout>
