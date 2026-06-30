<x-dashboard-layout>
    <x-slot name="title">Inventaire {{ $inventaire->date_inventaire->format('d/m/Y') }}</x-slot>

    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-lg sm:text-2xl font-display font-bold text-gray-900 truncate">Inventaire {{ $inventaire->date_inventaire->format('d/m/Y') }}</h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-0.5 sm:mt-1">
                    Par {{ $inventaire->user->name ?? '—' }} •
                    @php $cls = ['en_cours'=>'badge-warning','valide'=>'badge-success','annule'=>'badge-danger'][$inventaire->statut] ?? 'badge-primary'; @endphp
                    <span class="{{ $cls }} text-[10px] sm:text-xs">{{ ucfirst(str_replace('_',' ',$inventaire->statut)) }}</span>
                </p>
            </div>
            <a href="{{ route('dashboard.inventaires.index') }}" class="btn-outline text-xs self-start sm:self-auto">← Retour</a>
        </div>

        @if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert-error">{{ session('error') }}</div>@endif

        <div class="card p-3 sm:p-5 overflow-hidden">
            <div class="overflow-x-auto -webkit-overflow-scrolling:touch">
            <table class="w-full text-sm min-w-[500px]">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-2 sm:px-3 py-2 text-left">Produit</th>
                        <th class="px-2 sm:px-3 py-2 text-right">Théorique</th>
                        <th class="px-2 sm:px-3 py-2 text-right">Compté</th>
                        <th class="px-2 sm:px-3 py-2 text-right">Écart</th>
                        <th class="px-2 sm:px-3 py-2 text-right">Valeur écart</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($inventaire->lignes as $l)
                        <tr class="{{ $l->ecart != 0 ? ($l->ecart < 0 ? 'bg-red-50 dark:bg-red-900/10' : 'bg-emerald-50 dark:bg-emerald-900/10') : '' }}">
                            <td class="px-2 sm:px-3 py-2 font-semibold text-xs sm:text-sm">{{ $l->produit->nom ?? '—' }}</td>
                            <td class="px-2 sm:px-3 py-2 text-right text-xs sm:text-sm">{{ $l->stock_theorique }}</td>
                            <td class="px-2 sm:px-3 py-2 text-right text-xs sm:text-sm">{{ $l->stock_compte }}</td>
                            <td class="px-2 sm:px-3 py-2 text-right font-semibold text-xs sm:text-sm whitespace-nowrap {{ $l->ecart < 0 ? 'text-red-600' : ($l->ecart > 0 ? 'text-emerald-600' : 'text-gray-400') }}">
                                {{ $l->ecart > 0 ? '+' : '' }}{{ $l->ecart }}
                            </td>
                            <td class="px-2 sm:px-3 py-2 text-right font-semibold text-xs sm:text-sm whitespace-nowrap {{ $l->valeur_ecart < 0 ? 'text-red-600' : ($l->valeur_ecart > 0 ? 'text-emerald-600' : 'text-gray-400') }}">
                                {{ number_format($l->valeur_ecart, 0, ',', ' ') }} F
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr class="font-bold">
                        <td colspan="4" class="px-2 sm:px-3 py-3 text-right text-xs sm:text-sm">Total écart :</td>
                        <td class="px-2 sm:px-3 py-3 text-right text-base sm:text-lg whitespace-nowrap {{ $inventaire->total_ecart_valeur < 0 ? 'text-red-600' : ($inventaire->total_ecart_valeur > 0 ? 'text-emerald-600' : '') }}">
                            {{ number_format($inventaire->total_ecart_valeur, 0, ',', ' ') }} F
                        </td>
                    </tr>
                </tfoot>
            </table>
            </div>
        </div>

        @if($inventaire->notes)<div class="card p-4 text-sm"><strong>Notes :</strong> {{ $inventaire->notes }}</div>@endif

        @if($inventaire->statut === 'en_cours')
            <div class="flex flex-wrap gap-2">
                <form method="POST" action="{{ route('dashboard.inventaires.valider', $inventaire) }}" id="form-valider-inv-{{ $inventaire->id }}">
                    @csrf
                    <button type="button"
                            onclick="window.dispatchEvent(new CustomEvent('confirm-action',{detail:{formId:'form-valider-inv-{{ $inventaire->id }}',title:'Valider cet inventaire ?',message:'Les écarts seront appliqués sur le stock. Action irr\u00e9versible.',confirmLabel:'Valider',confirmClass:'!bg-emerald-600 hover:!bg-emerald-700',danger:false}}))"
                            class="btn-primary text-xs">Valider l'inventaire</button>
                </form>
                <form method="POST" action="{{ route('dashboard.inventaires.destroy', $inventaire) }}" id="form-del-inv-{{ $inventaire->id }}">
                    @csrf @method('DELETE')
                    <button type="button"
                            onclick="window.dispatchEvent(new CustomEvent('confirm-delete',{detail:{formId:'form-del-inv-{{ $inventaire->id }}',title:'Supprimer cet inventaire ?',message:'Cet inventaire sera d\u00e9finitivement supprim\u00e9.'}}))"
                            class="btn-outline text-red-600 text-xs">Supprimer</button>
                </form>
            </div>
        @endif
    </div>
</x-dashboard-layout>
