@extends('layouts.commercial')
@section('title', 'Mes commissions')

@section('content')
<div class="mb-6 flex items-center justify-between gap-4 flex-wrap">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Mes commissions</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Historique complet de vos gains</p>
    </div>
    <form method="GET">
        <select name="statut" onchange="this.form.submit()" class="form-input text-sm py-1.5">
            <option value="" {{ !request('statut') ? 'selected' : '' }}>Toutes</option>
            <option value="en_attente" {{ request('statut') === 'en_attente' ? 'selected' : '' }}>En attente</option>
            <option value="payee" {{ request('statut') === 'payee' ? 'selected' : '' }}>Payées</option>
        </select>
    </form>
</div>

<div class="grid grid-cols-2 gap-4 mb-6">
    <div class="stat-card">
        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Total reçu</p>
        <p class="text-xl font-bold text-emerald-600">{{ number_format($totalGagne, 0, ',', ' ') }} FCFA</p>
    </div>
    <div class="stat-card">
        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">À recevoir</p>
        <p class="text-xl font-bold text-primary-600">{{ number_format($totalEnAttente, 0, ',', ' ') }} FCFA</p>
    </div>
</div>

<div class="card overflow-hidden">
    @if($commissions->isEmpty())
    <div class="p-12 text-center">
        <p class="text-gray-500 text-sm">Aucune commission {{ request('statut') ? 'avec ce filtre' : 'pour l\'instant' }}.</p>
    </div>
    @else
    <table class="table-auto">
        <thead>
        <tr>
            <th>Institut</th>
            <th class="hidden md:table-cell">Plan</th>
            <th class="hidden md:table-cell">Base × taux</th>
            <th class="text-right">Montant</th>
            <th>Statut</th>
        </tr>
        </thead>
        <tbody>
        @foreach($commissions as $c)
        <tr>
            <td>
                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $c->parrainage?->proprietaire?->institut?->nom ?? '—' }}</p>
                <p class="text-xs text-gray-400">{{ $c->created_at->format('d/m/Y') }}</p>
            </td>
            <td class="hidden md:table-cell text-gray-600">{{ $c->abonnement?->plan?->nom ?? '—' }}</td>
            <td class="hidden md:table-cell text-gray-600">{{ number_format($c->montant_base, 0, ',', ' ') }} × {{ $c->taux }}%</td>
            <td class="text-right font-bold {{ $c->statut === 'payee' ? 'text-emerald-600' : 'text-primary-600' }}">
                {{ $c->montant_formatte }}
            </td>
            <td>
                <span class="badge {{ $c->statut === 'payee' ? 'badge-success' : 'badge-warning' }} text-[11px]">
                    {{ $c->statut === 'payee' ? 'Payée' : 'En attente' }}
                </span>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>

    @if($commissions->hasPages())
    <div class="p-4 border-t border-gray-100">{{ $commissions->links() }}</div>
    @endif
    @endif
</div>
@endsection
