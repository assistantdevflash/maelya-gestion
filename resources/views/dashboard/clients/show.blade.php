<x-dashboard-layout>
@php
$tabs = [
    ['id' => 'achats',    'label' => 'Achats',    'icon' => '🛍️', 'count' => $ventes->total()],
    ['id' => 'rdv',       'label' => 'RDV',       'icon' => '📅', 'count' => $rdvAVenir->count() + $rdvPasses->count(), 'feature' => 'rdv'],
    ['id' => 'timeline',  'label' => 'Activité',  'icon' => '🕒', 'count' => $timeline->count()],
    ['id' => 'credits',   'label' => 'Crédits',   'icon' => '🕐', 'count' => $credits->count(), 'feature' => 'credits'],
    ['id' => 'devis',     'label' => 'Devis',      'icon' => '📄', 'count' => $devis->count()],
    ['id' => 'factures',  'label' => 'Factures',   'icon' => '🧾', 'count' => $factures->count()],
    ['id' => 'commandes', 'label' => 'Commandes',  'icon' => '📦', 'count' => $commandes->count()],
    ['id' => 'photos',    'label' => 'Photos',     'icon' => '📎', 'count' => $client->photos->count()],
    ['id' => 'remises',   'label' => 'Remises',    'icon' => '🎫', 'count' => $codesReduction->count() + $avoirs->count()],
];
$currentTab = request('onglet', 'achats');
@endphp

<div x-data="{ tab: '{{ $currentTab }}', setTab(t) { this.tab = t; history.replaceState(null, '', '?onglet=' + t); } }" class="max-w-6xl mx-auto space-y-5 py-4">

    {{-- ═══ IDENTITY CARD ═══ --}}
    <div class="card !overflow-visible">
        <div class="p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center gap-5">
            <div class="flex-shrink-0">
                @if($client->isEntreprise())
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-2xl shadow-lg shadow-purple-500/20">🏢</div>
                @else
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-primary-500 to-secondary-500 flex items-center justify-center text-white text-2xl font-bold shadow-lg shadow-primary-500/20">
                        {{ strtoupper(substr($client->prenom ?? $client->nom ?? '?', 0, 1)) }}
                    </div>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-1.5">
                    <h1 class="text-xl sm:text-2xl font-display font-bold text-gray-900 dark:text-white truncate">{{ $client->nom_affichage }}</h1>
                    @if($client->est_patient)<span class="px-2 py-0.5 text-[10px] font-bold uppercase bg-purple-100 dark:bg-purple-500/20 text-purple-700 dark:text-purple-300 rounded-full">Patient</span>@endif
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400 space-y-1.5">
                    @if($client->telephone)<p class="inline-flex items-center gap-1.5"><span class="w-5 h-5 rounded-md bg-emerald-100 dark:bg-emerald-500/20 flex items-center justify-center text-xs">📞</span>{{ $client->telephone }}</p>@endif
                    @if($client->email)<p class="inline-flex items-center gap-1.5"><span class="w-5 h-5 rounded-md bg-blue-100 dark:bg-blue-500/20 flex items-center justify-center text-xs">✉️</span>{{ $client->email }}</p>@endif
                    @if($client->adresse)<p class="inline-flex items-center gap-1.5 text-xs"><span class="w-5 h-5 rounded-md bg-amber-100 dark:bg-amber-500/20 flex items-center justify-center text-xs">📍</span>{{ $client->isEntreprise() ? ($client->adresse_entreprise ?: $client->adresse) : $client->adresse }}</p>@endif
                    @if($client->isEntreprise() && $client->numero_registre_commerce)<p class="text-xs bg-gray-100 dark:bg-slate-700 px-2 py-0.5 rounded font-mono inline-block">RC: {{ $client->numero_registre_commerce }}</p>@endif
                    @if(!$client->isEntreprise() && $client->date_naissance)<p class="inline-flex items-center gap-1.5"><span class="w-5 h-5 rounded-md bg-pink-100 dark:bg-pink-500/20 flex items-center justify-center text-xs">🎂</span>{{ $client->naissance_formatee }}</p>@endif
                </div>
                @if($client->notes)<p class="mt-2.5 text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-slate-800 rounded-lg p-2.5 leading-relaxed">📝 {!! nl2br(e($client->notes)) !!}</p>@endif
            </div>
            <div class="flex items-center gap-2 flex-shrink-0 flex-wrap">
                <a href="{{ route('dashboard.caisse') }}?client={{ $client->id }}" class="btn-primary text-sm gap-1.5"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>Nouvelle vente</a>
                <button @click="$dispatch('open-edit-show')" class="btn-outline text-sm gap-1.5">✏️ Modifier</button>
                @if($client->fidelite_token)
                <div x-data="{open:false}" class="relative"><button @click="open=!open" class="btn-outline text-sm gap-1 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-600">🎫 Carte</button>
                    <div x-show="open" @click.away="open=false" x-cloak class="absolute left-0 sm:left-auto sm:right-0 mt-2 w-64 max-h-64 overflow-y-auto bg-white dark:bg-slate-800 rounded-xl shadow-lg border dark:border-slate-700 py-1.5 z-50">
                        @php $cu=route('public.carte-fidelite',$client->fidelite_token);$wp=preg_replace('/\D/','',$client->telephone??'');@endphp
                        <a href="{{ $cu }}" target="_blank" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-gray-50 dark:hover:bg-slate-700 transition">🔗 Ouvrir la carte</a>
                        <a href="{{ route('dashboard.clients.fidelite.pdf',$client) }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-gray-50 dark:hover:bg-slate-700 transition">📄 Télécharger PDF</a>
                        @if($wp)<a href="https://wa.me/{{ $wp }}?text={{ urlencode("Bonjour {$client->prenom}, votre carte: {$cu}") }}" target="_blank" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-gray-50 dark:hover:bg-slate-700 text-green-600 transition">💬 WhatsApp</a>@endif
                        <hr class="my-1 dark:border-slate-700"><form method="POST" action="{{ route('dashboard.clients.fidelite.regenerer',$client) }}" onsubmit="return confirm('Régénérer ?')">@csrf<button class="w-full text-left flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-gray-50 dark:hover:bg-slate-700 text-red-600 transition">🔄 Régénérer</button></form>
                    </div>
                </div>
                @endif
            </div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 divide-x dark:divide-slate-700 divide-y sm:divide-y-0 divide-gray-100 border-t border-gray-100 dark:border-slate-700 rounded-b-2xl overflow-hidden">
            <div class="px-5 py-3.5 text-center sm:text-left"><p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $client->nombre_visites }}</p><p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Visites totales</p></div>
            <div class="px-5 py-3.5 text-center sm:text-left"><p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($client->total_depense,0,',',' ') }}</p><p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">FCFA dépensés</p></div>
            <div class="px-5 py-3.5 text-center sm:text-left"><p class="text-2xl font-bold {{ $client->points_fidelite>0?'text-amber-600 dark:text-amber-400':'text-gray-400' }}">{{ number_format($client->points_fidelite,0,',',' ') }}</p><p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Points fidélité</p></div>
            <div class="px-5 py-3.5 text-center sm:text-left"><p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $client->derniere_visite?->diffForHumans(['parts'=>1])??'Jamais' }}</p><p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Dernière visite</p></div>
        </div>
    </div>

    <div class="grid lg:grid-cols-4 gap-5">
        <div class="lg:col-span-1 space-y-4">
            <div class="card p-5"><h3 class="font-semibold text-sm text-gray-700 dark:text-gray-200 mb-4 flex items-center gap-2"><span class="w-6 h-6 rounded-lg bg-gray-100 dark:bg-slate-700 flex items-center justify-center text-xs">📋</span>Informations</h3>
                <dl class="space-y-3">
                    @if($client->piece_identite)<div><dt class="text-[11px] text-gray-400 dark:text-gray-500 uppercase tracking-wide">Pièce ID</dt><dd class="text-sm text-gray-700 dark:text-gray-300 mt-0.5">{{ $client->piece_identite }}</dd></div>@endif
                    <div><dt class="text-[11px] text-gray-400 dark:text-gray-500 uppercase tracking-wide">Code client</dt><dd class="font-mono text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $client->code_client ?? substr($client->id,0,12) }}</dd></div>
                    <div><dt class="text-[11px] text-gray-400 dark:text-gray-500 uppercase tracking-wide">Client depuis</dt><dd class="text-sm text-gray-700 dark:text-gray-300 mt-0.5">{{ $client->created_at->translatedFormat('d F Y') }}</dd></div>
                </dl>
            </div>
            <div class="card p-5"><h3 class="font-semibold text-sm text-gray-700 dark:text-gray-200 mb-3 flex items-center gap-2"><span class="w-6 h-6 rounded-lg bg-primary-100 dark:bg-primary-500/20 flex items-center justify-center text-xs">⚡</span>Actions</h3>
                <div class="space-y-1.5">
                    @if($client->telephone)<a href="tel:{{ $client->telephone }}" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-900/20 text-sm text-gray-600 dark:text-gray-400 hover:text-emerald-700 dark:hover:text-emerald-300 transition group"><span class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400 flex-shrink-0 group-hover:scale-105 transition-transform">📞</span>Appeler</a>@endif
                    @if($client->email)<a href="mailto:{{ $client->email }}" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-blue-50 dark:hover:bg-blue-900/20 text-sm text-gray-600 dark:text-gray-400 hover:text-blue-700 dark:hover:text-blue-300 transition group"><span class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-500/20 flex items-center justify-center text-blue-600 dark:text-blue-400 flex-shrink-0 group-hover:scale-105 transition-transform">✉️</span>Email</a>@endif
                    @php $wq=preg_replace('/\D/','',$client->telephone??'');@endphp
                    @if($wq)<a href="https://wa.me/{{ $wq }}" target="_blank" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-green-50 dark:hover:bg-green-900/20 text-sm text-gray-600 dark:text-gray-400 hover:text-green-700 dark:hover:text-green-300 transition group"><span class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-500/20 flex items-center justify-center text-green-600 dark:text-green-400 flex-shrink-0 group-hover:scale-105 transition-transform">💬</span>WhatsApp</a>@endif
                    @if(auth()->user()?->aFonctionnalite('rdv'))<a href="{{ route('dashboard.rdv.create') }}?client_id={{ $client->id }}" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-violet-50 dark:hover:bg-violet-900/20 text-sm text-gray-600 dark:text-gray-400 hover:text-violet-700 dark:hover:text-violet-300 transition group"><span class="w-8 h-8 rounded-lg bg-violet-100 dark:bg-violet-500/20 flex items-center justify-center text-violet-600 dark:text-violet-400 flex-shrink-0 group-hover:scale-105 transition-transform">📅</span>Nouveau RDV</a>@endif
                </div>
            </div>
            <a href="{{ route('dashboard.clients.index') }}" class="hidden lg:flex items-center justify-center gap-2 p-3 rounded-xl bg-gray-50 dark:bg-slate-800 hover:bg-gray-100 dark:hover:bg-slate-700 text-sm text-gray-500 dark:text-gray-400 transition">← Retour aux clients</a>
        </div>
        <div class="lg:col-span-3 card overflow-hidden">
            <div class="flex items-center gap-0.5 px-3 py-2.5 border-b border-gray-100 dark:border-slate-700 overflow-x-auto bg-gray-50/50 dark:bg-slate-800/50">
                @foreach($tabs as $t)@if(!isset($t['feature'])||auth()->user()->aFonctionnalite($t['feature']))
                <button @click="setTab('{{ $t['id'] }}')" class="flex items-center gap-1 px-3 py-2 rounded-lg text-xs font-semibold flex-shrink-0 whitespace-nowrap transition-all" :class="tab==='{{ $t['id'] }}'?'bg-white dark:bg-slate-700 shadow-sm text-primary-700 dark:text-primary-300':'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200'"><span class="hidden sm:inline">{{ $t['icon'] }}</span> {{ $t['label'] }} <span class="text-[10px] font-bold opacity-60">({{ $t['count'] }})</span></button>
                @endif @endforeach
            </div>
            <div class="max-h-[55vh] overflow-y-auto">

                {{-- Achats --}}
                <div x-show="tab==='achats'" x-transition>
                    @forelse($ventes as $v)<a href="{{ route('dashboard.ventes.show',$v) }}" class="flex items-center justify-between px-5 py-3.5 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition border-b border-gray-50 dark:border-slate-700/50"><div class="min-w-0"><p class="font-semibold text-sm text-gray-900 dark:text-white">{{ number_format($v->total,0,',',' ') }} FCFA</p><p class="text-xs text-gray-400 mt-0.5">{{ $v->created_at->format('d/m/Y H:i') }} · {{ $v->items->first()?->nom_snapshot??'—' }}@if($v->items->count()>1) +{{ $v->items->count()-1 }} art.@endif</p></div><span class="badge text-[10px] {{ $v->mode_paiement==='mobile_money'?'bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-300':'bg-gray-100 text-gray-600 dark:bg-slate-600 dark:text-slate-300' }}">{{ $v->mode_paiement==='mobile_money'?'Mobile':($v->mode_paiement==='credit'?'Crédit':'Cash') }}</span></a>@empty<p class="py-12 text-center text-gray-400 text-sm">Aucun achat enregistré</p>@endforelse
                </div>

                {{-- RDV --}}
                @if(auth()->user()?->aFonctionnalite('rdv'))
                <div x-show="tab==='rdv'" x-transition>
                    @if($rdvAVenir->isNotEmpty())<div class="px-5 py-2 text-[10px] font-bold uppercase tracking-wider text-primary-600 dark:text-primary-400 bg-primary-50/50 dark:bg-primary-900/20">À venir ({{ $rdvAVenir->count() }})</div>@endif
                    @foreach($rdvAVenir as $r)<a href="{{ route('dashboard.rdv.show',$r) }}" class="flex items-center justify-between px-5 py-3.5 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition border-b border-gray-50 dark:border-slate-700/50"><div class="min-w-0"><p class="font-semibold text-sm text-gray-900 dark:text-white">{{ $r->debut_le->translatedFormat('d F Y') }} à {{ $r->debut_le->format('H\hi') }}</p><p class="text-xs text-gray-400 truncate">{{ $r->label_prestations }}</p></div>@php $b=$r->statut_badge;@endphp<span class="badge text-[10px] ml-2 {{ match($b['color']){'amber'=>'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300','blue'=>'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300',default=>'bg-gray-100 text-gray-600 dark:bg-slate-600 dark:text-slate-300'} }}">{{ $b['label'] }}</span></a>@endforeach
                    @if($rdvPasses->isNotEmpty())<div class="px-5 py-2 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-slate-500 bg-gray-50/50 dark:bg-slate-800/50">Passés ({{ $rdvPasses->count() }})</div>@endif
                    @foreach($rdvPasses as $r)<a href="{{ route('dashboard.rdv.show',$r) }}" class="flex items-center justify-between px-5 py-3.5 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition border-b border-gray-50 dark:border-slate-700/50 opacity-70"><div class="min-w-0"><p class="font-medium text-sm text-gray-700 dark:text-slate-300">{{ $r->debut_le->translatedFormat('d F Y') }} à {{ $r->debut_le->format('H\hi') }}</p><p class="text-xs text-gray-400 truncate">{{ $r->label_prestations }}</p></div>@php $b=$r->statut_badge;@endphp<span class="badge text-[10px] ml-2 {{ match($b['color']){'emerald'=>'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300','red'=>'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300',default=>'bg-gray-100 text-gray-600 dark:bg-slate-600 dark:text-slate-300'} }}">{{ $b['label'] }}</span></a>@endforeach
                    @if($rdvAVenir->isEmpty()&&$rdvPasses->isEmpty())<p class="py-12 text-center text-gray-400 text-sm">Aucun rendez-vous</p>@endif
                    <div class="px-5 py-3 bg-gray-50/30 dark:bg-slate-800/30"><a href="{{ route('dashboard.rdv.create') }}?client_id={{ $client->id }}" class="text-xs text-primary-600 hover:underline font-medium">+ Créer un RDV</a></div>
                </div>
                @endif

                {{-- Timeline --}}
                <div x-show="tab==='timeline'" x-transition class="p-5">@if($timeline->count())<div class="relative ml-3 border-l-2 border-gray-100 dark:border-slate-700 space-y-5">@foreach($timeline as $e)<div class="ml-6 relative"><span class="absolute -left-[30px] flex items-center justify-center w-7 h-7 bg-white dark:bg-slate-800 rounded-full ring-2 ring-gray-200 dark:ring-slate-700 text-sm">{{ $e['icon'] }}</span><div><a href="{{ $e['url'] }}" class="font-medium text-sm text-gray-800 dark:text-slate-200 hover:text-primary-600 dark:hover:text-primary-400 transition">{{ $e['titre'] }}</a><p class="text-xs text-gray-400 dark:text-slate-500 truncate mt-0.5">{{ $e['sous'] }}</p><time class="text-[11px] text-gray-400 dark:text-slate-500">{{ $e['date']?->format('d/m/Y H:i') }}</time></div></div>@endforeach</div>@else<p class="py-12 text-center text-gray-400 text-sm">Aucune activité récente</p>@endif</div>

                {{-- Credits --}}
                @if(auth()->user()->aFonctionnalite('credits'))
                <div x-show="tab==='credits'" x-transition>@forelse($credits as $c)<a href="{{ route('dashboard.credits.show',$c) }}" class="flex items-center justify-between px-5 py-3.5 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition border-b border-gray-50 dark:border-slate-700/50"><div class="min-w-0 flex-1"><p class="font-semibold text-sm text-gray-900 dark:text-white truncate">{{ $c->vente->items->pluck('nom_snapshot')->implode(', ')?:'Crédit #'.substr($c->id,0,8) }}</p><p class="text-xs text-gray-400 mt-0.5">{{ $c->date_debut->format('d/m/Y') }} · {{ $c->nb_echeances }} éch.</p></div><div class="text-right ml-3 flex-shrink-0"><p class="font-bold text-sm {{ $c->reste_a_payer>0?'text-red-600 dark:text-red-400':'text-emerald-600 dark:text-emerald-400' }}">{{ number_format($c->reste_a_payer,0,',',' ') }} F</p><span class="badge text-[10px] {{ $c->statut==='solde'?'badge-success':($c->statut==='retard'?'badge-danger':'badge-info') }}">{{ $c->statut==='solde'?'Soldé':($c->statut==='retard'?'Retard':'En cours') }}</span></div></a>@empty<p class="py-12 text-center text-gray-400 text-sm">Aucun crédit</p>@endforelse</div>
                @endif

                {{-- Devis --}}
                <div x-show="tab==='devis'" x-transition>@forelse($devis as $d)<a href="{{ route('dashboard.devis.show',['devis'=>$d->id]) }}" class="flex items-center justify-between px-5 py-3.5 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition border-b border-gray-50 dark:border-slate-700/50"><div class="min-w-0"><p class="font-semibold text-sm text-gray-900 dark:text-white">{{ $d->numero }}</p><p class="text-xs text-gray-400 mt-0.5">{{ $d->date_creation->format('d/m/Y') }} · Exp. {{ $d->date_expiration->format('d/m/Y') }}</p></div><div class="text-right ml-3 flex-shrink-0"><p class="font-bold text-sm text-gray-900 dark:text-white">{{ number_format($d->total_ttc,0,',',' ') }} F</p><span class="badge text-[10px] {{ match($d->statut){'brouillon'=>'bg-gray-100 text-gray-600 dark:bg-slate-600 dark:text-slate-300','envoye'=>'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300','accepte'=>'badge-success','refuse'=>'badge-danger',default=>'bg-gray-100 text-gray-600 dark:bg-slate-600 dark:text-slate-300'} }}">{{ ucfirst($d->statut) }}</span></div></a>@empty<p class="py-12 text-center text-gray-400 text-sm">Aucun devis</p>@endforelse</div>

                {{-- Factures --}}
                <div x-show="tab==='factures'" x-transition>@forelse($factures as $f)<a href="{{ route('dashboard.factures.show',['facture'=>$f->id]) }}" class="flex items-center justify-between px-5 py-3.5 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition border-b border-gray-50 dark:border-slate-700/50"><div class="min-w-0"><p class="font-semibold text-sm text-gray-900 dark:text-white">{{ $f->numero }}</p><p class="text-xs text-gray-400 mt-0.5">{{ $f->date_emission->format('d/m/Y') }} · Éch. {{ $f->date_echeance->format('d/m/Y') }}</p></div><div class="text-right ml-3 flex-shrink-0"><p class="font-bold text-sm {{ $f->estPayee?'text-emerald-600 dark:text-emerald-400':'text-gray-900 dark:text-white' }}">{{ number_format($f->total_ttc,0,',',' ') }} F</p><span class="badge text-[10px] {{ match($f->statut){'payee'=>'badge-success','en_attente'=>'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300','annulee'=>'badge-danger',default=>'bg-gray-100 text-gray-600 dark:bg-slate-600 dark:text-slate-300'} }}">{{ $f->estPayee?'Payée':ucfirst(str_replace('_',' ',$f->statut)) }}</span></div></a>@empty<p class="py-12 text-center text-gray-400 text-sm">Aucune facture</p>@endforelse</div>

                {{-- Commandes --}}
                <div x-show="tab==='commandes'" x-transition>@forelse($commandes as $cmd)<a href="{{ route('dashboard.boutique.commandes.show',$cmd) }}" class="flex items-center justify-between px-5 py-3.5 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition border-b border-gray-50 dark:border-slate-700/50"><div class="min-w-0"><p class="font-semibold text-sm text-gray-900 dark:text-white">{{ $cmd->numero }}</p><p class="text-xs text-gray-400 mt-0.5">{{ $cmd->created_at->format('d/m/Y') }} · {{ $cmd->items->count() }} art.</p></div><div class="text-right ml-3 flex-shrink-0"><p class="font-bold text-sm text-gray-900 dark:text-white">{{ number_format($cmd->total,0,',',' ') }} F</p><span class="badge text-[10px] {{ match($cmd->statut){'livree'=>'badge-success','nouvelle'=>'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300','annulee'=>'badge-danger','refusee'=>'badge-danger',default=>'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300'} }}">{{ ucfirst(str_replace('_',' ',$cmd->statut)) }}</span></div></a>@empty<p class="py-12 text-center text-gray-400 text-sm">Aucune commande</p>@endforelse</div>

                {{-- Photos --}}
                <div x-show="tab==='photos'" x-transition x-data="{photos:@js($client->photos->map(fn($p)=>['url'=>$p->url,'legende'=>$p->legende??'','isPdf'=>$p->isPdf()])->values()->all()),current:0,open:false,openAt(i){if(this.photos[i].isPdf){window.open(this.photos[i].url,'_blank')}else{this.current=i;this.open=true}},prev(){do{this.current=(this.current-1+this.photos.length)%this.photos.length}while(this.photos[this.current].isPdf)},next(){do{this.current=(this.current+1)%this.photos.length}while(this.photos[this.current].isPdf)}}" @keydown.escape.window="open=false" class="p-5">
                    <div class="flex items-center justify-between mb-4"><p class="text-xs text-gray-500">{{ $client->photos->count() }} fichier(s)</p><button type="button" @click="$dispatch('open-photos-modal')" class="btn-primary text-xs">+ Ajouter</button></div>
                    @if($client->photos->count()>0)<div class="grid grid-cols-3 sm:grid-cols-5 gap-3">@foreach($client->photos as $i=>$photo)<div class="flex flex-col gap-1"><div class="relative group cursor-pointer">@if($photo->isPdf())<a href="{{ $photo->url }}" target="_blank" class="block"><div class="w-full aspect-square bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 rounded-lg hover:opacity-80 transition flex flex-col items-center justify-center border-2 border-red-200 dark:border-red-700"><span class="text-2xl">📄</span><span class="text-[10px] font-bold text-red-600 dark:text-red-400 mt-1">PDF</span></div></a>@else<div @click="openAt({{ $i }})"><img src="{{ $photo->url }}" alt="{{ $photo->legende }}" class="w-full aspect-square object-cover rounded-lg hover:opacity-80 transition"></div>@endif<form method="POST" action="{{ route('dashboard.clients.photos.destroy',[$client,$photo]) }}?onglet=photos" class="absolute top-1 right-1 lg:opacity-0 lg:group-hover:opacity-100 transition" @click.stop>@csrf @method('DELETE')<button type="button" onclick="if(confirm('Supprimer ?'))this.form.submit()" class="w-6 h-6 bg-red-600/90 hover:bg-red-700 text-white rounded-full flex items-center justify-center text-xs shadow-lg" title="Supprimer">✕</button></form></div>@if($photo->legende)<p class="text-[10px] text-gray-500 dark:text-slate-400 truncate leading-tight">{{ $photo->legende }}</p>@endif</div>@endforeach</div>
                    <div x-show="open" x-cloak class="fixed inset-0 z-[80] bg-black/95 flex items-center justify-center" @click="open=false"><div class="relative w-full max-w-3xl mx-4 flex flex-col items-center" @click.stop><button @click="open=false" class="absolute -top-10 right-0 text-white/70 hover:text-white text-3xl">&times;</button><img :src="photos[current].url" class="max-h-[70vh] w-auto max-w-full object-contain rounded-xl"><p class="text-white/80 text-sm mt-4" x-show="photos[current].legende" x-text="photos[current].legende"></p><p class="text-gray-500 text-xs mt-1" x-text="(current+1)+' / '+photos.filter(p=>!p.isPdf).length"></p><button x-show="photos.filter(p=>!p.isPdf).length>1" @click.stop="prev()" class="absolute left-0 top-1/3 -translate-x-14 w-10 h-10 rounded-full bg-white/15 hover:bg-white/30 text-white flex items-center justify-center"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button><button x-show="photos.filter(p=>!p.isPdf).length>1" @click.stop="next()" class="absolute right-0 top-1/3 translate-x-14 w-10 h-10 rounded-full bg-white/15 hover:bg-white/30 text-white flex items-center justify-center"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button></div></div>@else<p class="py-12 text-center text-gray-400 text-sm">Aucun fichier</p>@endif
                </div>

                {{-- Remises --}}
                <div x-show="tab==='remises'" x-transition>@php $ca=$codesReduction->filter(fn($c)=>$c->statut()==='actif');@endphp
                    @if($codesReduction->count()>0)<div class="px-5 py-2 text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 bg-emerald-50/50 dark:bg-emerald-900/20">Codes ({{ $ca->count() }} actifs / {{ $codesReduction->count() }})</div>
                    @foreach($codesReduction->sortByDesc(fn($c)=>$c->statut()==='actif') as $code)<div class="px-5 py-3 flex items-center justify-between border-b border-gray-50 dark:border-slate-700/50"><div class="min-w-0 flex-1"><div class="flex items-center gap-2"><span class="font-mono font-bold text-xs text-gray-900 dark:text-white">{{ $code->code }}</span>@php $cs=$code->statut();@endphp<span class="badge text-[9px] {{ match($cs){'actif'=>'badge-success','epuise'=>'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300','expire'=>'badge-danger',default=>'bg-gray-100 text-gray-600 dark:bg-slate-600 dark:text-slate-300'} }}">{{ $cs }}</span></div><p class="text-xs text-gray-500 mt-0.5">{{ $code->type==='pourcentage'?$code->valeur.'%':number_format($code->valeur,0,',',' ').' F' }}</p></div><span class="font-bold text-sm {{ $cs==='actif'?'text-emerald-600 dark:text-emerald-400':'text-gray-400' }}">{{ $code->type==='pourcentage'?'-'.$code->valeur.'%':'-'.number_format($code->valeur,0,',',' ') }}</span></div>@endforeach @else<p class="py-8 text-center text-gray-400 text-sm">Aucun code</p>@endif
                    @if($avoirs->count()>0)<div class="px-5 py-2 text-[10px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400 bg-blue-50/50 dark:bg-blue-900/20">Avoirs ({{ $avoirs->count() }})</div>
                    @foreach($avoirs as $avoir)<div class="px-5 py-3 flex items-center justify-between border-b border-gray-50 dark:border-slate-700/50"><div class="min-w-0 flex-1"><span class="font-mono font-bold text-xs text-gray-900 dark:text-white">{{ $avoir->codeReduction?->code??'Avoir #'.substr($avoir->id,0,6) }}</span><p class="text-xs text-gray-500 mt-0.5">{{ number_format($avoir->montant,0,',',' ') }} F @if($avoir->motif)· {{ $avoir->motif }}@endif</p></div><span class="font-bold text-sm text-blue-600 dark:text-blue-400">{{ number_format($avoir->montant,0,',',' ') }} F</span></div>@endforeach @else<p class="py-8 text-center text-gray-400 text-sm">Aucun avoir</p>@endif
                </div>
            </div>
        </div>
    </div>

    {{-- Retour mobile --}}
    <div class="lg:hidden"><a href="{{ route('dashboard.clients.index') }}" class="flex items-center justify-center gap-2 p-3 rounded-xl bg-gray-50 dark:bg-slate-800 hover:bg-gray-100 dark:hover:bg-slate-700 text-sm text-gray-500 dark:text-gray-400 transition">← Retour aux clients</a></div>

    {{-- Modal Photos --}}
    <div x-data="{show:false}" @open-photos-modal.window="show=true" x-show="show" x-cloak class="modal-backdrop" @keydown.escape.window="show=false" @click.self="show=false">
        <div class="modal max-w-md" x-transition @click.stop>
            <div class="modal-header"><h3 class="modal-title">Ajouter des fichiers</h3><button @click="show=false" class="modal-close">✕</button></div>
            <form method="POST" action="{{ route('dashboard.clients.photos.store', $client) }}?onglet={{ $currentTab }}" enctype="multipart/form-data" class="modal-body space-y-4">
                @csrf
                <div>
                    <label class="form-label">Fichiers (JPG, PNG, PDF)</label>
                    <input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp,application/pdf" class="form-input" required>
                    <p class="text-[11px] text-gray-400 mt-1">Max 10 Mo par fichier</p>
                </div>
                <div>
                    <label class="form-label">Type *</label>
                    <select name="type" required class="form-input">
                        <option value="autre">Autre</option>
                        <option value="cni">CNI</option>
                        <option value="passeport">Passeport</option>
                        <option value="permis_conduire">Permis de conduire</option>
                        <option value="avant">Avant</option>
                        <option value="apres">Après</option>
                        <option value="avant_apres">Avant/Après</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Légende</label>
                    <input type="text" name="legende" maxlength="255" class="form-input" placeholder="Description du fichier…">
                </div>
                <div>
                    <label class="form-label">Date de prise</label>
                    <input type="date" name="date_prise" class="form-input">
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn-primary flex-1">📤 Téléverser</button>
                    <button type="button" @click="show=false" class="btn-outline">Annuler</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Édition --}}
    <div x-data="{show:false}" @open-edit-show.window="show=true" x-init="{{ $errors->any()?'show=true':'' }}" x-show="show" x-cloak class="modal-backdrop" @keydown.escape.window="show=false" @click.self="show=false">
        <div class="modal max-w-lg" x-transition @click.stop>
            <div class="modal-header"><h3 class="modal-title">Modifier le client</h3><button @click="show=false" class="modal-close">✕</button></div>
            <div class="modal-body">
                @if($errors->any())<div class="mb-4 p-3 bg-red-50 dark:bg-red-900/30 rounded-xl text-sm text-red-600 dark:text-red-400">@foreach($errors->all() as $e)<p>• {{ $e }}</p>@endforeach</div>@endif
                <form method="POST" action="{{ route('dashboard.clients.update',$client) }}" class="space-y-4" x-data="{tc:'{{ old('type_client',$client->type_client??'personne_physique') }}'}">@csrf @method('PUT')
                    <div class="flex gap-2 p-1 bg-gray-100 dark:bg-slate-800 rounded-xl"><button type="button" @click="tc='personne_physique'" :class="tc==='personne_physique'?'bg-white dark:bg-slate-700 shadow-sm text-gray-900 dark:text-white':'text-gray-500'" class="flex-1 py-2 px-3 rounded-lg text-sm font-semibold transition">Personne physique</button><button type="button" @click="tc='entreprise'" :class="tc==='entreprise'?'bg-white dark:bg-slate-700 shadow-sm text-gray-900 dark:text-white':'text-gray-500'" class="flex-1 py-2 px-3 rounded-lg text-sm font-semibold transition">Entreprise</button></div>
                    <template x-if="tc==='personne_physique'"><div class="grid grid-cols-2 gap-3"><div><label class="form-label">Prénom *</label><input type="text" name="prenom" maxlength="50" class="form-input" value="{{ old('prenom',$client->prenom) }}"></div><div><label class="form-label">Nom *</label><input type="text" name="nom" maxlength="50" class="form-input" value="{{ old('nom',$client->nom) }}"></div></div></template>
                    <template x-if="tc==='entreprise'"><div><label class="form-label">Raison sociale *</label><input type="text" name="raison_sociale" maxlength="255" class="form-input" value="{{ old('raison_sociale',$client->raison_sociale) }}"><div class="grid grid-cols-2 gap-3 mt-3"><div><label class="form-label">RC</label><input type="text" name="numero_registre_commerce" maxlength="100" class="form-input" value="{{ old('numero_registre_commerce',$client->numero_registre_commerce) }}"></div><div><label class="form-label">Contact</label><input type="text" name="prenom" maxlength="50" class="form-input" value="{{ old('prenom',$client->prenom) }}"></div></div></div></template>
                    <div><label class="form-label">Téléphone *</label><input type="tel" name="telephone" maxlength="30" required class="form-input" value="{{ old('telephone',$client->telephone) }}"></div>
                    <div><label class="form-label">Email</label><input type="email" name="email" maxlength="255" class="form-input" value="{{ old('email',$client->email) }}"></div>
                    <template x-if="tc==='personne_physique'"><div><label class="form-label">Date naissance (JJ-MM)</label><input type="text" name="date_naissance" placeholder="JJ-MM" class="form-input w-28" value="{{ old('date_naissance',$client->date_naissance) }}"></div></template>
                    <div><label class="form-label">Adresse</label><input type="text" name="adresse" maxlength="255" class="form-input" value="{{ old('adresse',$client->adresse) }}"></div>
                    <div><label class="form-label">Pièce identité</label><input type="text" name="piece_identite" maxlength="100" class="form-input" value="{{ old('piece_identite',$client->piece_identite) }}"></div>
                    <div><label class="form-label">Notes</label><textarea name="notes" rows="2" maxlength="1000" class="form-input resize-none">{{ old('notes',$client->notes) }}</textarea></div>
                    <div class="flex gap-3 pt-2"><button class="btn-primary flex-1">Enregistrer</button><button type="button" @click="show=false" class="btn-outline">Annuler</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
</x-dashboard-layout>
