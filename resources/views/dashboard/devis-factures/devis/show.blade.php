<x-dashboard-layout>
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('dashboard.devis.index') }}" class="p-2 text-gray-400 hover:text-gray-600 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 transition">←</a>
            <div><h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $devis->numero }}</h1>
                <div class="mt-1">@include('dashboard.devis-factures.partials.statut-badge', ['statut' => $devis->statut, 'type' => 'devis'])</div>
            </div>
        </div>
        <div class="flex gap-2">
            @if(in_array($devis->statut, ['brouillon','envoye']))
            <form method="POST" action="{{ route('dashboard.devis.transformer', $devis) }}" class="inline">
                @csrf<button class="btn-primary text-sm">Transformer en facture</button>
            </form>
            @endif
            @if($devis->statut === 'brouillon')
            <form method="POST" action="{{ route('dashboard.devis.envoyer', $devis) }}" class="inline">
                @csrf<button class="btn-outlined text-sm">Envoyer</button>
            </form>
            @endif
            <a href="{{ route('dashboard.devis.pdf', $devis) }}" target="_blank" class="btn-outlined text-sm">📄 PDF</a>
            <a href="{{ route('dashboard.devis.dupliquer', $devis) }}" class="btn-outlined text-sm">📋 Dupliquer</a>
        </div>
    </div>

    {{-- Infos --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="card">
            <div class="card-header"><h2 class="font-semibold">Client</h2></div>
            <div class="card-body text-sm space-y-1">
                <p class="font-bold text-gray-900 dark:text-white">{{ $devis->client_nom_complet ?: ($devis->client->nom_complet ?? '—') }}</p>
                @if($devis->client_telephone)<p>📞 {{ $devis->client_telephone }}</p>@endif
                @if($devis->client_email)<p>✉️ {{ $devis->client_email }}</p>@endif
                @if($devis->client_adresse)<p class="text-gray-500">{{ $devis->client_adresse }}</p>@endif
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h2 class="font-semibold">Dates</h2></div>
            <div class="card-body text-sm space-y-1">
                <p><span class="text-gray-500">Créé le :</span> {{ $devis->date_creation->format('d/m/Y') }}</p>
                <p><span class="text-gray-500">Expire le :</span> {{ $devis->date_expiration->format('d/m/Y') }}</p>
                @if($devis->facture_id)<p class="text-emerald-600">→ Facture <a href="{{ route('dashboard.factures.show', $devis->facture_id) }}" class="font-medium underline">{{ $devis->facture->numero ?? '—' }}</a></p>@endif
            </div>
        </div>
    </div>

    {{-- Lignes --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-slate-800"><tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Désignation</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase w-16">Qté</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">PU</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                @foreach($devis->items as $item)
                <tr>
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $item->designation }}</td>
                    <td class="px-4 py-3 text-center">{{ $item->quantite }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($item->prix_unitaire, 0, ',', ' ') }} F</td>
                    <td class="px-4 py-3 text-right font-bold">{{ number_format($item->total_ligne, 0, ',', ' ') }} F</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>

    {{-- Totaux --}}
    <div class="card ml-auto w-full max-w-sm">
        <div class="card-body space-y-2 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">Total HT</span><span>{{ number_format($devis->total_ht, 0, ',', ' ') }} F</span></div>
            @if($devis->remise_globale > 0)
            <div class="flex justify-between"><span class="text-gray-500">Remise</span><span class="text-red-500">−{{ number_format($devis->remise_globale, 0, ',', ' ') }} F</span></div>
            @endif
            @if($devis->tva_taux > 0)
            <div class="flex justify-between"><span class="text-gray-500">TVA {{ $devis->tva_taux }}%</span><span>{{ number_format($devis->total_tva, 0, ',', ' ') }} F</span></div>
            @endif
            <div class="flex justify-between text-lg font-bold pt-2 border-t"><span>Total TTC</span><span class="text-primary-600">{{ number_format($devis->total_ttc, 0, ',', ' ') }} F</span></div>
        </div>
    </div>

    @if($devis->notes)
    <div class="card"><div class="card-header"><h2 class="font-semibold">Notes</h2></div><div class="card-body text-sm text-gray-500">{{ $devis->notes }}</div></div>
    @endif
</div>
</x-dashboard-layout>
