<x-dashboard-layout>
    <div class="space-y-5">
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
                    <option value="cash" {{ request('mode') === 'cash' ? 'selected' : '' }}>Especes</option>
                    <option value="carte" {{ request('mode') === 'carte' ? 'selected' : '' }}>Carte</option>
                    <option value="mobile_money" {{ request('mode') === 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                    <option value="mixte" {{ request('mode') === 'mixte' ? 'selected' : '' }}>Mixte</option>
                    <option value="credit" {{ request('mode') === 'credit' ? 'selected' : '' }}>Credit</option>
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
                        <tr class="hover:bg-gray-50 group transition-colors cursor-pointer" 
                            onclick="window.location.href='{{ route('dashboard.ventes.show', $vente) }}'">
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs text-gray-600">{{ $vente->numero }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 hidden sm:table-cell text-xs">
                                {{ $vente->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-3 hidden lg:table-cell text-gray-600">
                                {{ $vente->client?->nom_affichage ?? 'Anonyme' }}
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
                                @elseif($vente->mode_paiement === 'credit')
                                    <span class="badge text-xs" style="background:#f3e8ff;color:#7c3aed;">📅 Credit</span>
                                @else
                                    <span class="badge badge-secondary text-xs">💵 Especes</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell text-gray-500 text-xs">
                                {{ $vente->items->count() }} article(s)
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($vente->statut === 'annulee')
                                    <span class="badge badge-danger text-xs">Annulee</span>
                                @elseif($vente->mode_paiement === 'credit')
                                    @if($vente->credit_statut === 'solde')
                                        <span class="badge badge-success text-xs">Solde</span>
                                    @elseif($vente->credit_statut === 'retard')
                                        <span class="badge badge-danger text-xs">Retard</span>
                                    @else
                                        <span class="badge text-xs" style="background:#ede9fe;color:#7c3aed;">En cours</span>
                                    @endif
                                @else
                                    <span class="badge badge-success text-xs">Payee</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-bold {{ $vente->statut === 'annulee' ? 'line-through text-gray-400' : 'text-gray-900' }}">
                                @if($vente->mode_paiement === 'credit')
                                    <div>
                                        <span class="text-emerald-600">{{ number_format($vente->montant_paye ?? 0, 0, ',', ' ') }}</span>
                                        <span class="text-gray-400"> / </span>
                                        <span>{{ number_format($vente->total, 0, ',', ' ') }}</span>
                                    </div>
                                @else
                                    {{ number_format($vente->total, 0, ',', ' ') }} FCFA
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
                <p class="text-xs text-gray-400">
                    Total periode : <strong class="text-gray-900">{{ number_format($ventes->sum(fn($v) => $v->mode_paiement === 'credit' ? ($v->montant_paye ?? 0) : $v->total), 0, ',', ' ') }} FCFA</strong>
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

        </div>{{-- /flex --}}        @else
        <div class="card p-12 text-center">
            <div class="text-4xl mb-3">🛍️</div>
            <p class="font-semibold text-gray-900 mb-1">Aucune vente</p>
            <p class="text-sm text-gray-500">Commencez par enregistrer votre première vente.</p>
        </div>
        @endif

    </div>
</x-dashboard-layout>
