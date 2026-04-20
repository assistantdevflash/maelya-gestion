<x-dashboard-layout>
    <div class="space-y-5" x-data="{ showVente: null }">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="page-title">Historique des ventes</h1>
            </div>
            <a href="{{ route('dashboard.caisse') }}" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Nouvelle vente
            </a>
        </div>

        {{-- Filtres --}}
        <div class="card p-4">
            <form method="GET" action="{{ route('dashboard.ventes.index') }}" class="flex flex-wrap items-end gap-3"
                  x-data="{ debut: '{{ request('debut') }}' }">
                <div class="flex flex-col gap-1">
                    <label class="text-xs text-gray-500 font-medium">Début</label>
                    <input type="date" name="debut" x-model="debut" value="{{ request('debut') }}" class="form-input">
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-xs text-gray-500 font-medium">Fin</label>
                    <input type="date" name="fin" :min="debut" value="{{ request('fin') }}" class="form-input">
                </div>
                <select name="mode" class="form-select w-auto self-end">
                    <option value="">Tous modes</option>
                    <option value="cash" {{ request('mode') === 'cash' ? 'selected' : '' }}>Espèces</option>
                    <option value="carte" {{ request('mode') === 'carte' ? 'selected' : '' }}>Carte</option>
                    <option value="mobile_money" {{ request('mode') === 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                    <option value="mixte" {{ request('mode') === 'mixte' ? 'selected' : '' }}>Mixte</option>
                </select>
                @if(!Auth::user()->isEmploye() && $membres->count() > 1)
                <select name="employe_id" class="form-select w-auto self-end">
                    <option value="">Tous les vendeurs</option>
                    @foreach($membres as $membre)
                    <option value="{{ $membre->id }}" {{ request('employe_id') === $membre->id ? 'selected' : '' }}>
                        {{ $membre->prenom }} {{ $membre->nom_famille }}
                        @if($membre->role === 'admin') (Admin) @endif
                    </option>
                    @endforeach
                </select>
                @endif
                <button type="submit" class="btn-outline self-end border py-2">Filtrer</button>
                @if(request()->hasAny(['debut','fin','mode','employe_id']))
                    <a href="{{ route('dashboard.ventes.index') }}" class="btn btn-ghost self-end border py-2">Réinitialiser</a>
                @endif
            </form>
        </div>

        @if($ventes->count() > 0)
        <div class="flex flex-col lg:flex-row gap-5 items-start">
        {{-- Colonne de droite sur mobile : Point de caisse en premier sur sm, après sur lg --}}
        {{-- Colonne principale : table --}}
        <div class="w-full lg:flex-1 lg:min-w-0 space-y-4 order-2 lg:order-1">
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">N° Vente</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 hidden sm:table-cell">Date</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 hidden lg:table-cell">Client</th>
                            @if(!Auth::user()->isEmploye())
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 hidden xl:table-cell">Vendeur</th>
                            @endif
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 hidden md:table-cell">Mode</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 hidden md:table-cell">Articles</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600">Statut</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($ventes as $idx => $vente)
                        <tr class="hover:bg-gray-50 group transition-colors cursor-pointer" @click="showVente = showVente === {{ $idx }} ? null : {{ $idx }}">
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs text-gray-600">{{ $vente->numero }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 hidden sm:table-cell text-xs">
                                {{ $vente->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-3 hidden lg:table-cell text-gray-600">
                                {{ $vente->client?->nom_complet ?? 'Anonyme' }}
                            </td>
                            @if(!Auth::user()->isEmploye())
                            <td class="px-4 py-3 hidden xl:table-cell">
                                @if($vente->user)
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="w-5 h-5 rounded-full bg-primary-100 flex items-center justify-center text-[9px] font-bold text-primary-700">
                                        {{ strtoupper(substr($vente->user->prenom, 0, 1)) }}
                                    </span>
                                    <span class="text-xs text-gray-700">{{ $vente->user->prenom }}</span>
                                </span>
                                @else
                                <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            @endif
                            <td class="px-4 py-3 hidden md:table-cell">
                                @if($vente->mode_paiement === 'mobile_money')
                                    <span class="badge badge-warning text-xs">📱 Mobile</span>
                                @elseif($vente->mode_paiement === 'carte')
                                    <span class="badge badge-info text-xs">💳 Carte</span>
                                @elseif($vente->mode_paiement === 'mixte')
                                    <span class="badge badge-secondary text-xs">💵+📱 Mixte</span>
                                @else
                                    <span class="badge badge-secondary text-xs">💵 Espèces</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell text-gray-500 text-xs">
                                {{ $vente->items->count() }} article(s)
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($vente->statut === 'annulee')
                                    <span class="badge badge-danger text-xs">Annulée</span>
                                @else
                                    <span class="badge badge-success text-xs">Payée</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-bold {{ $vente->statut === 'annulee' ? 'line-through text-gray-400' : 'text-gray-900' }}">
                                {{ number_format($vente->total, 0, ',', ' ') }} FCFA
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
                <p class="text-xs text-gray-400">
                    Total période : <strong class="text-gray-900">{{ number_format($ventes->sum('total'), 0, ',', ' ') }} FCFA</strong>
                </p>
                {{ $ventes->withQueryString()->links() }}
            </div>
        </div>
        </div>{{-- /flex-1 --}}

        {{-- Colonne de droite : Point de caisse par vendeur (sticky) --}}
        @if(!Auth::user()->isEmploye() && $statsParEmploye->count() > 0)
        <div class="w-full lg:w-72 lg:flex-shrink-0 lg:sticky lg:top-6 lg:self-start order-1 lg:order-2">
        <div class="card overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100">
                <h2 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                    <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Point de caisse
                    @if(request()->hasAny(['debut','fin','mode']))
                    <span class="text-xs font-normal text-gray-400">— filtré</span>
                    @endif
                </h2>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach($statsParEmploye as $stat)
                @php
                    $totalGlobal = $statsParEmploye->sum('total_ventes');
                    $pct = $totalGlobal > 0 ? round($stat->total_ventes / $totalGlobal * 100) : 0;
                @endphp
                <div class="flex items-center gap-3 px-4 py-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                         style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                        {{ strtoupper(substr($stat->user?->prenom ?? '?', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-gray-900 truncate">
                            {{ $stat->user?->prenom }} {{ $stat->user?->nom_famille }}
                            @if($stat->user?->role === 'admin')
                            <span class="text-[9px] font-normal text-gray-400">Admin</span>
                            @endif
                        </p>
                        <div class="mt-1 flex items-center gap-1.5">
                            <div class="flex-1 h-1 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full" style="width: {{ $pct }}%; background: linear-gradient(90deg, #9333ea, #ec4899);"></div>
                            </div>
                            <span class="text-[9px] text-gray-400">{{ $pct }}%</span>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-xs font-bold text-gray-900">{{ number_format($stat->total_ventes, 0, ',', ' ') }}</p>
                        <p class="text-[9px] text-gray-400">{{ $stat->nb_ventes }} vente(s)</p>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="px-4 py-2.5 bg-gray-50 border-t border-gray-100 text-right">
                <span class="text-xs text-gray-500">Total : <strong class="text-gray-900">{{ number_format($statsParEmploye->sum('total_ventes'), 0, ',', ' ') }} FCFA</strong></span>
            </div>
        </div>
        </div>
        @endif

        </div>{{-- /flex --}}        @foreach($ventes as $idx => $vente)
        <template x-teleport="body">
            <div x-show="showVente === {{ $idx }}"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4"
                 style="background: rgba(0,0,0,0.5);"
                 @click.self="showVente = null"
                 x-cloak>
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto relative"
                     x-data="{ confirmAnnuler: false }"
                     x-show="showVente === {{ $idx }}"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     @click.stop>

                    {{-- Header --}}
                    <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-primary-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900">Commande #{{ $vente->numero }}</h3>
                                <p class="text-xs text-gray-400">{{ $vente->created_at->format('d/m/Y à H:i') }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($vente->statut === 'annulee')
                                <span class="badge badge-danger text-xs">Annulée</span>
                            @else
                                <span class="badge badge-success text-xs">Payée</span>
                            @endif
                            <button @click="showVente = null" class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Vendeur (admin seulement) --}}
                    @if(!Auth::user()->isEmploye() && $vente->user)
                    <div class="px-5 pt-4 pb-0">
                        <div class="flex items-center gap-2.5 p-3 bg-violet-50 rounded-xl">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Vendeur</p>
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-violet-700">
                                <span class="w-5 h-5 rounded-full bg-violet-200 flex items-center justify-center text-[9px] font-bold text-violet-800">{{ strtoupper(substr($vente->user->prenom, 0, 1)) }}</span>
                                {{ $vente->user->prenom }} {{ $vente->user->nom_famille }}
                            </span>
                        </div>
                    </div>
                    @endif

                    {{-- Paiement --}}
                    <div class="px-5 pt-4 pb-2">
                        <div class="flex items-center gap-2.5 p-3 bg-gray-50 rounded-xl">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Paiement</p>
                            @if($vente->mode_paiement === 'carte')
                                <span class="inline-flex items-center gap-1 text-xs font-bold text-blue-700 bg-blue-50 px-2.5 py-1 rounded-lg">
                                    💳 Carte bancaire
                                </span>
                            @elseif($vente->mode_paiement === 'mobile_money')
                                <span class="inline-flex items-center gap-1 text-xs font-bold text-orange-700 bg-orange-50 px-2.5 py-1 rounded-lg">
                                    📱 Mobile Money
                                </span>
                            @elseif($vente->mode_paiement === 'mixte')
                                <span class="inline-flex items-center gap-1 text-xs font-bold text-violet-700 bg-violet-50 px-2.5 py-1 rounded-lg">
                                    💵+📱 Mixte
                                    @php
                                        $parts = [];
                                        if ($vente->montant_cash > 0)   $parts[] = number_format($vente->montant_cash) . ' F esp.';
                                        if ($vente->montant_carte > 0)  $parts[] = number_format($vente->montant_carte) . ' F carte';
                                        if ($vente->montant_mobile > 0) $parts[] = number_format($vente->montant_mobile) . ' F mob.';
                                    @endphp
                                    @if($parts) &mdash; {{ implode(' / ', $parts) }} @endif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs font-bold text-primary-700 bg-primary-50 px-2.5 py-1 rounded-lg">
                                    💵 Espèces
                                </span>
                            @endif
                            @if($vente->client)
                                <span class="ml-auto text-xs text-gray-500">{{ $vente->client->nom_complet }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Articles commandés --}}
                    <div class="px-5 py-3 space-y-2">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            Articles commandés
                        </p>
                        <div class="space-y-1">
                            @foreach($vente->items as $item)
                            <div class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-gray-50 transition-colors">
                                <span class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold text-white shadow-sm"
                                      style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                                    {{ $item->quantite }}
                                </span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $item->nom_snapshot }}</p>
                                    <p class="text-xs text-gray-400">{{ number_format($item->prix_snapshot, 0, ',', ' ') }} FCFA / u.</p>
                                </div>
                                <span class="text-sm font-bold text-gray-900">{{ number_format($item->sous_total, 0, ',', ' ') }} FCFA</span>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Totaux --}}
                    <div class="px-5 pb-3">
                        <div class="border-t border-gray-100 pt-3 space-y-1.5">
                            @if($vente->remise > 0)
                            <div class="flex justify-between text-sm text-gray-500">
                                <span>Sous-total</span>
                                <span>{{ number_format($vente->total + $vente->remise, 0, ',', ' ') }} FCFA</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="flex items-center gap-1.5 text-emerald-600 font-medium">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                    @if($vente->codeReduction)
                                        <span class="font-mono font-bold">{{ $vente->codeReduction->code }}</span>
                                    @else
                                        Réduction
                                    @endif
                                </span>
                                <span class="font-semibold text-emerald-600">-{{ number_format($vente->remise, 0, ',', ' ') }} FCFA</span>
                            </div>
                            @else
                            <div class="flex justify-between text-sm text-gray-500">
                                <span>Sous-total</span>
                                <span>{{ number_format($vente->total, 0, ',', ' ') }} FCFA</span>
                            </div>
                            @endif
                            <div class="flex justify-between text-base font-bold">
                                <span class="text-gray-900">Total</span>
                                <span style="background: linear-gradient(135deg, #9333ea, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">{{ number_format($vente->total, 0, ',', ' ') }} FCFA</span>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="px-5 pb-5 flex gap-2">
                        <a href="{{ route('dashboard.ventes.ticket-pdf', $vente) }}" target="_blank"
                           class="flex-1 btn-outline justify-center text-sm py-2.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                            Imprimer le reçu
                        </a>
                        @if($vente->statut === 'validee' && auth()->user()->isAdmin())
                        <button @click="confirmAnnuler = true"
                                class="flex-1 btn-outline justify-center text-sm py-2.5 !border-red-200 !text-red-600 hover:!bg-red-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Annuler la commande
                        </button>
                        @endif
                    </div>

                    {{-- Confirmation inline (style TopResto) --}}
                    @if($vente->statut === 'validee' && auth()->user()->isAdmin())
                    <div x-show="confirmAnnuler"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-cloak
                         class="absolute inset-x-0 bottom-0 bg-white rounded-b-2xl border-t border-gray-100 p-5 shadow-[0_-10px_30px_rgba(0,0,0,0.1)]">
                        <div class="flex items-start gap-3 mb-4">
                            <div class="w-9 h-9 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4.5 h-4.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-900">Annuler la commande #{{ $vente->numero }} ?</p>
                                <p class="text-xs text-gray-500 mt-0.5">Cette commande d'un montant de <strong>{{ number_format($vente->total, 0, ',', ' ') }} FCFA</strong> sera marquée comme annulée. Cette action ne peut pas être défaite.</p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button @click="confirmAnnuler = false" class="flex-1 btn-outline justify-center text-sm py-2.5">
                                Conserver la commande
                            </button>
                            <form method="POST" action="{{ route('dashboard.ventes.annuler', $vente) }}" class="flex-1">
                                @csrf
                                <button type="submit" class="w-full btn-primary justify-center text-sm py-2.5 !bg-red-600 hover:!bg-red-700">
                                    Confirmer l'annulation
                                </button>
                            </form>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </template>
        @endforeach

        @else
        <div class="card p-12 text-center">
            <div class="text-4xl mb-3">🛍️</div>
            <p class="font-semibold text-gray-900 mb-1">Aucune vente</p>
            <p class="text-sm text-gray-500">Commencez par enregistrer votre première vente.</p>
        </div>
        @endif

    </div>
</x-dashboard-layout>
