@extends('layouts.admin')
@section('page-title', 'Messages envoyés')

@section('content')
<div class="space-y-6" x-data="{ tab: 'emails' }">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex-1 min-w-0">
            <h1 class="page-title">Messages envoyés</h1>
            <p class="page-subtitle hidden sm:block">Historique des emails, notifications push et bannières envoyés depuis l'administration.</p>
        </div>
        <a href="{{ route('admin.emails.composer') }}"
           style="background: linear-gradient(135deg, #9333ea, #ec4899);"
           class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-white font-semibold text-sm rounded-xl shadow-lg hover:opacity-90 transition-all active:scale-95 flex-shrink-0">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="22" y1="2" x2="11" y2="13"/>
                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
            </svg>
            Composer un message
        </a>
    </div>

    @if(session('success'))
    <div class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-2xl text-green-800 dark:text-green-300 text-sm font-medium">
        <svg class="w-5 h-5 flex-shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="flex items-start gap-3 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl text-red-800 dark:text-red-300 text-sm">
        <svg class="w-5 h-5 flex-shrink-0 text-red-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    {{-- Onglets --}}
    <div class="card-admin p-1">
        <div class="flex gap-1">
            <button type="button" @click="tab = 'emails'"
                    :class="tab === 'emails' ? 'bg-white dark:bg-white/10 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                    class="flex-1 px-3 py-2.5 text-sm font-semibold rounded-lg transition-all">
                📧 <span class="hidden xs:inline">Emails &amp; Push</span><span class="xs:hidden">Emails</span>
                <span class="ml-1 text-xs opacity-60">({{ $historique->total() }})</span>
            </button>
            <button type="button" @click="tab = 'bannieres'"
                    :class="tab === 'bannieres' ? 'bg-white dark:bg-white/10 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                    class="flex-1 px-3 py-2.5 text-sm font-semibold rounded-lg transition-all">
                🏴 Bannières
                <span class="ml-1 text-xs opacity-60">({{ $bannieres->total() }})</span>
            </button>
        </div>
    </div>

    {{-- ═══════════════════ EMAILS & PUSH ═══════════════════ --}}
    <div x-show="tab === 'emails'" x-cloak class="card-admin overflow-hidden">
        @if($historique->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 text-center px-4">
            <div class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-white/5 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <p class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Aucun email envoyé</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Les emails que vous enverrez apparaîtront ici.</p>
            <a href="{{ route('admin.emails.composer') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-200 transition-colors">
                Envoyer le premier email →
            </a>
        </div>
        @else

        {{-- Mobile : cartes --}}
        <div class="sm:hidden divide-y divide-gray-100 dark:divide-white/5">
            @foreach($historique as $campagne)
            <div class="p-4" x-data="{ open: false }">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs text-gray-400 mb-0.5">{{ $campagne->created_at->format('d/m/Y H:i') }}</p>
                        <p class="font-semibold text-sm text-gray-900 dark:text-white truncate">{{ $campagne->sujet }}</p>
                        <div class="flex items-center gap-2 mt-1.5">
                            @php $modeColors = ['tous'=>'bg-blue-50 text-blue-700','selection'=>'bg-purple-50 text-purple-700','un'=>'bg-gray-100 text-gray-700','personnalise'=>'bg-pink-50 text-pink-700']; @endphp
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $modeColors[$campagne->mode] ?? 'bg-gray-100 text-gray-600' }}">{{ $campagne->mode_libelle }}</span>
                            <span class="text-xs text-gray-500">{{ $campagne->nb_envoyes }} envoyé(s)</span>
                            @if($campagne->nb_echecs > 0)<span class="text-xs text-red-500">{{ $campagne->nb_echecs }} échec(s)</span>@endif
                        </div>
                    </div>
                    <button type="button" @click="open = !open"
                            class="flex-shrink-0 p-2 rounded-xl text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/40 transition-colors">
                        <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                </div>
                <div x-show="open" x-cloak x-transition class="mt-3 space-y-3 text-xs">
                    <div>
                        <p class="font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Destinataires ({{ count($campagne->destinataires_emails) }})</p>
                        <div class="flex flex-wrap gap-1">
                            @foreach($campagne->destinataires_emails as $email)
                            <span class="px-2 py-0.5 rounded-full bg-gray-100 dark:bg-white/10 text-gray-600 dark:text-gray-300 break-all">{{ $email }}</span>
                            @endforeach
                        </div>
                    </div>
                    @if($campagne->erreurs)
                    <div>
                        <p class="font-semibold text-red-500 mb-1">Erreurs</p>
                        <pre class="text-red-600 dark:text-red-400 whitespace-pre-wrap font-mono bg-red-50 dark:bg-red-900/10 rounded-lg p-2 text-[10px] max-h-28 overflow-y-auto">{{ $campagne->erreurs }}</pre>
                    </div>
                    @endif
                    <div>
                        <p class="font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Aperçu</p>
                        <div class="bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl p-3 max-h-32 overflow-y-auto text-gray-700 dark:text-gray-300">{!! $campagne->corps !!}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Desktop : table --}}
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-white/5">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sujet</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Mode</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Envoyés</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Échecs</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Par</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-white/5">
                    @foreach($historique as $campagne)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors group" x-data="{ open: false }">
                        <td class="px-5 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">{{ $campagne->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-4"><p class="font-medium text-gray-900 dark:text-white truncate max-w-xs">{{ $campagne->sujet }}</p></td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            @php $modeColors = ['tous'=>'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300','selection'=>'bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300','un'=>'bg-gray-100 dark:bg-white/10 text-gray-700 dark:text-gray-300','personnalise'=>'bg-pink-50 dark:bg-pink-900/20 text-pink-700 dark:text-pink-300']; @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $modeColors[$campagne->mode] ?? 'bg-gray-100 text-gray-600' }}">{{ $campagne->mode_libelle }}</span>
                        </td>
                        <td class="px-5 py-4 text-center"><span class="font-semibold {{ $campagne->nb_envoyes > 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }}">{{ $campagne->nb_envoyes }}</span></td>
                        <td class="px-5 py-4 text-center">
                            @if($campagne->nb_echecs > 0)<span class="font-semibold text-red-600 dark:text-red-400">{{ $campagne->nb_echecs }}</span>
                            @else<span class="text-gray-300 dark:text-gray-600">—</span>@endif
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400 text-xs">{{ $campagne->expediteur?->prenom }} {{ $campagne->expediteur?->nom }}</td>
                        <td class="px-5 py-4 text-right">
                            <button type="button" @click="open = !open"
                                    class="text-xs font-medium text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-200 transition-colors opacity-0 group-hover:opacity-100">
                                Détails
                            </button>
                        </td>
                    </tr>
                    <tr x-show="open" x-cloak class="bg-gray-50 dark:bg-white/[0.02]">
                        <td colspan="7" class="px-5 py-4">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 text-xs">
                                <div>
                                    <p class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Destinataires ({{ count($campagne->destinataires_emails) }})</p>
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($campagne->destinataires_emails as $email)
                                        <span class="px-2 py-0.5 rounded-full bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 text-gray-600 dark:text-gray-300">{{ $email }}</span>
                                        @endforeach
                                    </div>
                                </div>
                                @if($campagne->erreurs)
                                <div>
                                    <p class="font-semibold text-red-600 dark:text-red-400 mb-2">Erreurs</p>
                                    <pre class="text-red-600 dark:text-red-400 whitespace-pre-wrap font-mono bg-red-50 dark:bg-red-900/10 rounded-lg p-3 text-[11px] max-h-32 overflow-y-auto">{{ $campagne->erreurs }}</pre>
                                </div>
                                @endif
                                <div class="lg:col-span-2">
                                    <p class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Aperçu du message</p>
                                    <div class="bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl p-4 prose prose-sm dark:prose-invert max-w-none max-h-40 overflow-y-auto text-xs text-gray-700 dark:text-gray-300">{!! $campagne->corps !!}</div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($historique->hasPages())
        <div class="px-5 py-4 border-t border-gray-100 dark:border-white/5">{{ $historique->links() }}</div>
        @endif
        @endif
    </div>

    {{-- ═══════════════════ BANNIÈRES ═══════════════════ --}}
    <div x-show="tab === 'bannieres'" x-cloak class="card-admin overflow-hidden">
        @if($bannieres->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 text-center px-4">
            <div class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-white/5 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                </svg>
            </div>
            <p class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Aucune bannière envoyée</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Les bannières que vous enverrez apparaîtront ici.</p>
            <a href="{{ route('admin.emails.composer') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-200 transition-colors">
                Envoyer la première bannière →
            </a>
        </div>
        @else

        {{-- Mobile : cartes --}}
        <div class="sm:hidden divide-y divide-gray-100 dark:divide-white/5">
            @foreach($bannieres as $banniere)
            @php
                $typeColors = ['info'=>'bg-blue-50 text-blue-700','success'=>'bg-green-50 text-green-700','warning'=>'bg-orange-50 text-orange-700','danger'=>'bg-red-50 text-red-700'];
                $typeLabels = ['info'=>'Info','success'=>'Succès','warning'=>'Attention','danger'=>'Urgent'];
                $cibleLabels = ['tous'=>'Tous','selection'=>'Sélection','un'=>'1 établissement'];
            @endphp
            <div class="p-4" x-data="{ open: false }">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs text-gray-400 mb-0.5">{{ $banniere->created_at->format('d/m/Y H:i') }}</p>
                        <p class="font-semibold text-sm text-gray-900 dark:text-white">{{ $banniere->titre }}</p>
                        <div class="flex items-center flex-wrap gap-2 mt-1.5">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $typeColors[$banniere->type] ?? 'bg-gray-100 text-gray-600' }}">{{ $typeLabels[$banniere->type] ?? $banniere->type }}</span>
                            <span class="text-xs text-gray-500">{{ $cibleLabels[$banniere->cible] ?? $banniere->cible }}</span>
                            <span class="text-xs {{ $banniere->lecteurs_count > 0 ? 'text-green-600' : 'text-gray-400' }}">{{ $banniere->lecteurs_count }} lecture(s)</span>
                            @if($banniere->actif && (!$banniere->expire_le || $banniere->expire_le->isFuture()))
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700">● Active</span>
                            @else
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Inactive</span>
                            @endif
                        </div>
                    </div>
                    <button type="button" @click="open = !open"
                            class="flex-shrink-0 p-2 rounded-xl text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/40 transition-colors">
                        <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                </div>
                <div x-show="open" x-cloak x-transition class="mt-3 space-y-3 text-xs">
                    <div>
                        <p class="font-semibold text-gray-600 dark:text-gray-400 mb-1">Message</p>
                        <div class="bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl p-3 text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $banniere->message }}</div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl p-3">
                            <p class="text-gray-500 dark:text-gray-400 mb-0.5">Lectures</p>
                            <p class="font-bold text-gray-900 dark:text-white text-base">{{ $banniere->lecteurs_count }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl p-3">
                            <p class="text-gray-500 dark:text-gray-400 mb-0.5">Expire</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $banniere->expire_le ? $banniere->expire_le->format('d/m/Y') : '∞' }}</p>
                        </div>
                    </div>
                    @if($banniere->lecteurs->isNotEmpty())
                    <div>
                        <p class="font-semibold text-gray-600 dark:text-gray-400 mb-1">Lu par</p>
                        <div class="flex flex-wrap gap-1">
                            @foreach($banniere->lecteurs->take(10) as $lecteur)
                            <span class="px-2 py-0.5 rounded-full bg-gray-100 dark:bg-white/10 text-gray-600 dark:text-gray-300">{{ $lecteur->prenom }} {{ $lecteur->nom }}</span>
                            @endforeach
                            @if($banniere->lecteurs->count() > 10)
                            <span class="text-gray-400">+{{ $banniere->lecteurs->count() - 10 }} autres</span>
                            @endif
                        </div>
                    </div>
                    @endif
                    {{-- Actions --}}
                    <div class="flex items-center gap-2 pt-1 border-t border-gray-200 dark:border-white/10">
                        <form method="POST" action="{{ route('admin.emails.toggle-banniere', $banniere) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition-colors
                                {{ $banniere->actif ? 'border-orange-300 text-orange-600 hover:bg-orange-50' : 'border-green-300 text-green-600 hover:bg-green-50' }}">
                                {{ $banniere->actif ? '⏸ Désactiver' : '▶ Activer' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.emails.delete-banniere', $banniere) }}"
                              onsubmit="return confirm('Supprimer cette bannière ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-semibold border border-red-300 text-red-600 hover:bg-red-50 transition-colors">
                                🗑 Supprimer
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Desktop : table --}}
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-white/5">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Titre</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cible</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Lectures</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Statut</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Par</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-white/5">
                    @foreach($bannieres as $banniere)
                    @php
                        $typeColors = ['info'=>'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300','success'=>'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300','warning'=>'bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300','danger'=>'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300'];
                        $typeLabels = ['info'=>'Info','success'=>'Succès','warning'=>'Attention','danger'=>'Urgent'];
                        $cibleColors = ['tous'=>'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300','selection'=>'bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300','un'=>'bg-gray-100 dark:bg-white/10 text-gray-700 dark:text-gray-300'];
                        $cibleLabels = ['tous'=>'Tous','selection'=>'Sélection','un'=>'1 établissement'];
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors group" x-data="{ open: false }">
                        <td class="px-5 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">{{ $banniere->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-4"><p class="font-medium text-gray-900 dark:text-white truncate max-w-xs">{{ $banniere->titre }}</p></td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $typeColors[$banniere->type] ?? 'bg-gray-100 text-gray-600' }}">{{ $typeLabels[$banniere->type] ?? $banniere->type }}</span>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $cibleColors[$banniere->cible] ?? 'bg-gray-100 text-gray-600' }}">{{ $cibleLabels[$banniere->cible] ?? $banniere->cible }}</span>
                        </td>
                        <td class="px-5 py-4 text-center">
                            <span class="font-semibold {{ $banniere->lecteurs_count > 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }}">{{ $banniere->lecteurs_count }}</span>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            @if($banniere->actif && (!$banniere->expire_le || $banniere->expire_le->isFuture()))
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300">● Active</span>
                            @elseif($banniere->expire_le && $banniere->expire_le->isPast())
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-white/10 text-gray-500 dark:text-gray-400">Expirée</span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-white/10 text-gray-500 dark:text-gray-400">Inactive</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400 text-xs">{{ $banniere->expediteur?->prenom }} {{ $banniere->expediteur?->nom }}</td>
                        <td class="px-5 py-4 text-right">
                            <button type="button" @click="open = !open"
                                    class="text-xs font-medium text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-200 transition-colors opacity-0 group-hover:opacity-100">
                                Détails
                            </button>
                        </td>
                    </tr>
                    <tr x-show="open" x-cloak class="bg-gray-50 dark:bg-white/[0.02]">
                        <td colspan="8" class="px-5 py-4">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 text-xs">
                                <div class="lg:col-span-2">
                                    <p class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Message</p>
                                    <div class="bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl p-4 text-xs text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $banniere->message }}</div>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Statistiques</p>
                                    <div class="space-y-2 bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl p-3">
                                        <div class="flex justify-between">
                                            <span class="text-gray-500 dark:text-gray-400">Lectures :</span>
                                            <span class="font-semibold text-gray-900 dark:text-white">{{ $banniere->lecteurs_count }}</span>
                                        </div>
                                        @if($banniere->expire_le)
                                        <div class="flex justify-between">
                                            <span class="text-gray-500 dark:text-gray-400">Expire le :</span>
                                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $banniere->expire_le->format('d/m/Y') }}</span>
                                        </div>
                                        @endif
                                        <div class="flex justify-between">
                                            <span class="text-gray-500 dark:text-gray-400">Créée le :</span>
                                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $banniere->created_at->format('d/m/Y à H:i') }}</span>
                                        </div>
                                    </div>
                                </div>
                                @if($banniere->lecteurs->isNotEmpty())
                                <div>
                                    <p class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Lu par ({{ $banniere->lecteurs->count() }})</p>
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($banniere->lecteurs->take(20) as $lecteur)
                                        <span class="px-2 py-0.5 rounded-full bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 text-gray-600 dark:text-gray-300">{{ $lecteur->prenom }} {{ $lecteur->nom }}</span>
                                        @endforeach
                                        @if($banniere->lecteurs->count() > 20)
                                        <span class="px-2 py-0.5 text-gray-500 dark:text-gray-400">+{{ $banniere->lecteurs->count() - 20 }} autres</span>
                                        @endif
                                    </div>
                                </div>
                                @endif
                                {{-- Actions admin --}}
                                <div class="lg:col-span-2 flex items-center gap-3 pt-1 border-t border-gray-200 dark:border-white/10">
                                    <form method="POST" action="{{ route('admin.emails.toggle-banniere', $banniere) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold border transition-colors
                                            {{ $banniere->actif ? 'border-orange-300 dark:border-orange-700 text-orange-600 dark:text-orange-400 hover:bg-orange-50 dark:hover:bg-orange-900/20' : 'border-green-300 dark:border-green-700 text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20' }}">
                                            {{ $banniere->actif ? '⏸ Désactiver' : '▶ Activer' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.emails.delete-banniere', $banniere) }}"
                                          onsubmit="return confirm('Supprimer définitivement cette bannière ?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                            🗑 Supprimer
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($bannieres->hasPages())
        <div class="px-5 py-4 border-t border-gray-100 dark:border-white/5">{{ $bannieres->links() }}</div>
        @endif
        @endif
    </div>

</div>
@endsection


@section('content')
<div class="space-y-6" x-data="{ tab: 'emails' }">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="page-title">Messages envoyés</h1>
            <p class="page-subtitle">Historique des emails, notifications push et bannières envoyés depuis l'administration.</p>
        </div>
        <a href="{{ route('admin.emails.composer') }}"
           style="background: linear-gradient(135deg, #9333ea, #ec4899);"
           class="inline-flex items-center gap-2 px-5 py-2.5 text-white font-semibold text-sm rounded-xl shadow-lg hover:opacity-90 transition-all active:scale-95">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="22" y1="2" x2="11" y2="13"/>
                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
            </svg>
            Composer un message
        </a>
    </div>

    @if(session('success'))
    <div class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-2xl text-green-800 dark:text-green-300 text-sm font-medium">
        <svg class="w-5 h-5 flex-shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="flex items-start gap-3 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl text-red-800 dark:text-red-300 text-sm">
        <svg class="w-5 h-5 flex-shrink-0 text-red-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    {{-- Onglets --}}
    <div class="card-admin p-1">
        <div class="flex gap-1">
            <button type="button" @click="tab = 'emails'" 
                    :class="tab === 'emails' ? 'bg-white dark:bg-white/10 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                    class="flex-1 px-4 py-2.5 text-sm font-semibold rounded-lg transition-all">
                📧 Emails & Push
                <span class="ml-1 text-xs opacity-60">({{ $historique->total() }})</span>
            </button>
            <button type="button" @click="tab = 'bannieres'" 
                    :class="tab === 'bannieres' ? 'bg-white dark:bg-white/10 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                    class="flex-1 px-4 py-2.5 text-sm font-semibold rounded-lg transition-all">
                🏴 Bannières
                <span class="ml-1 text-xs opacity-60">({{ $bannieres->total() }})</span>
            </button>
        </div>
    </div>

    {{-- Contenu Emails & Push --}}
    <div x-show="tab === 'emails'" x-cloak class="card-admin overflow-hidden">
        @if($historique->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 text-center px-4">
            <div class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-white/5 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <p class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Aucun email envoyé</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Les emails que vous enverrez apparaîtront ici.</p>
            <a href="{{ route('admin.emails.composer') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-200 transition-colors">
                Envoyer le premier email →
            </a>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-white/5">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sujet</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Mode</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Envoyés</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Échecs</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Par</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-white/5">
                    @foreach($historique as $campagne)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors group" x-data="{ open: false }">
                        <td class="px-5 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                            {{ $campagne->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-5 py-4">
                            <p class="font-medium text-gray-900 dark:text-white truncate max-w-xs">{{ $campagne->sujet }}</p>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            @php
                            $modeColors = [
                                'tous'         => 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300',
                                'selection'    => 'bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300',
                                'un'           => 'bg-gray-100 dark:bg-white/10 text-gray-700 dark:text-gray-300',
                                'personnalise' => 'bg-pink-50 dark:bg-pink-900/20 text-pink-700 dark:text-pink-300',
                            ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $modeColors[$campagne->mode] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ $campagne->mode_libelle }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-center">
                            <span class="font-semibold {{ $campagne->nb_envoyes > 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }}">
                                {{ $campagne->nb_envoyes }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-center">
                            @if($campagne->nb_echecs > 0)
                            <span class="font-semibold text-red-600 dark:text-red-400">{{ $campagne->nb_echecs }}</span>
                            @else
                            <span class="text-gray-300 dark:text-gray-600">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400 text-xs">
                            {{ $campagne->expediteur?->prenom }} {{ $campagne->expediteur?->nom }}
                        </td>
                        <td class="px-5 py-4 text-right">
                            <button type="button" @click="open = !open"
                                    class="text-xs font-medium text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-200 transition-colors opacity-0 group-hover:opacity-100">
                                Détails
                            </button>
                        </td>
                    </tr>
                    {{-- Ligne de détails dépliable --}}
                    <tr x-show="open" x-cloak class="bg-gray-50 dark:bg-white/[0.02]">
                        <td colspan="7" class="px-5 py-4">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 text-xs">
                                {{-- Destinataires --}}
                                <div>
                                    <p class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Destinataires ({{ count($campagne->destinataires_emails) }})</p>
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($campagne->destinataires_emails as $email)
                                        <span class="px-2 py-0.5 rounded-full bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 text-gray-600 dark:text-gray-300">{{ $email }}</span>
                                        @endforeach
                                    </div>
                                </div>
                                {{-- Erreurs --}}
                                @if($campagne->erreurs)
                                <div>
                                    <p class="font-semibold text-red-600 dark:text-red-400 mb-2">Erreurs</p>
                                    <pre class="text-red-600 dark:text-red-400 whitespace-pre-wrap font-mono bg-red-50 dark:bg-red-900/10 rounded-lg p-3 text-[11px] max-h-32 overflow-y-auto">{{ $campagne->erreurs }}</pre>
                                </div>
                                @endif
                                {{-- Aperçu corps --}}
                                <div class="lg:col-span-2">
                                    <p class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Aperçu du message</p>
                                    <div class="bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl p-4 prose prose-sm dark:prose-invert max-w-none max-h-40 overflow-y-auto text-xs text-gray-700 dark:text-gray-300">
                                        {!! $campagne->corps !!}
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($historique->hasPages())
        <div class="px-5 py-4 border-t border-gray-100 dark:border-white/5">
            {{ $historique->links() }}
        </div>
        @endif
        @endif
    </div>

    {{-- Contenu Bannières --}}
    <div x-show="tab === 'bannieres'" x-cloak class="card-admin overflow-hidden">
        @if($bannieres->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 text-center px-4">
            <div class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-white/5 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                </svg>
            </div>
            <p class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Aucune bannière envoyée</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Les bannières que vous enverrez apparaîtront ici.</p>
            <a href="{{ route('admin.emails.composer') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-200 transition-colors">
                Envoyer la première bannière →
            </a>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-white/5">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Titre</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cible</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Lectures</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Statut</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Par</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-white/5">
                    @foreach($bannieres as $banniere)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors group" x-data="{ open: false }">
                        <td class="px-5 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                            {{ $banniere->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-5 py-4">
                            <p class="font-medium text-gray-900 dark:text-white truncate max-w-xs">{{ $banniere->titre }}</p>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            @php
                            $typeColors = [
                                'info'    => 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300',
                                'success' => 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300',
                                'warning' => 'bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300',
                                'danger'  => 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300',
                            ];
                            $typeLabels = [
                                'info'    => 'Info',
                                'success' => 'Succès',
                                'warning' => 'Attention',
                                'danger'  => 'Urgent',
                            ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $typeColors[$banniere->type] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ $typeLabels[$banniere->type] ?? $banniere->type }}
                            </span>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            @php
                            $cibleColors = [
                                'tous'      => 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300',
                                'selection' => 'bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300',
                                'un'        => 'bg-gray-100 dark:bg-white/10 text-gray-700 dark:text-gray-300',
                            ];
                            $cibleLabels = [
                                'tous'      => 'Tous',
                                'selection' => 'Sélection',
                                'un'        => 'Un établissement',
                            ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $cibleColors[$banniere->cible] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ $cibleLabels[$banniere->cible] ?? $banniere->cible }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-center">
                            <span class="font-semibold {{ $banniere->lecteurs_count > 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }}">
                                {{ $banniere->lecteurs_count }}
                            </span>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            @if($banniere->actif && (!$banniere->expire_le || $banniere->expire_le->isFuture()))
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300">
                                ● Active
                            </span>
                            @elseif($banniere->expire_le && $banniere->expire_le->isPast())
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-white/10 text-gray-500 dark:text-gray-400">
                                Expirée
                            </span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-white/10 text-gray-500 dark:text-gray-400">
                                Inactive
                            </span>
                            @endif
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400 text-xs">
                            {{ $banniere->expediteur?->prenom }} {{ $banniere->expediteur?->nom }}
                        </td>
                        <td class="px-5 py-4 text-right">
                            <button type="button" @click="open = !open"
                                    class="text-xs font-medium text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-200 transition-colors opacity-0 group-hover:opacity-100">
                                Détails
                            </button>
                        </td>
                    </tr>
                    {{-- Ligne de détails dépliable --}}
                    <tr x-show="open" x-cloak class="bg-gray-50 dark:bg-white/[0.02]">
                        <td colspan="8" class="px-5 py-4">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 text-xs">
                                {{-- Message --}}
                                <div class="lg:col-span-2">
                                    <p class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Message</p>
                                    <div class="bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl p-4 text-xs text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $banniere->message }}</div>
                                </div>
                                {{-- Statistiques --}}
                                <div>
                                    <p class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Statistiques</p>
                                    <div class="space-y-2 bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl p-3">
                                        <div class="flex justify-between">
                                            <span class="text-gray-500 dark:text-gray-400">Lectures :</span>
                                            <span class="font-semibold text-gray-900 dark:text-white">{{ $banniere->lecteurs_count }}</span>
                                        </div>
                                        @if($banniere->expire_le)
                                        <div class="flex justify-between">
                                            <span class="text-gray-500 dark:text-gray-400">Expire le :</span>
                                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $banniere->expire_le->format('d/m/Y') }}</span>
                                        </div>
                                        @endif
                                        <div class="flex justify-between">
                                            <span class="text-gray-500 dark:text-gray-400">Créée le :</span>
                                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $banniere->created_at->format('d/m/Y à H:i') }}</span>
                                        </div>
                                    </div>
                                </div>
                                {{-- Lecteurs --}}
                                @if($banniere->lecteurs->isNotEmpty())
                                <div>
                                    <p class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Lu par ({{ $banniere->lecteurs->count() }})</p>
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($banniere->lecteurs->take(20) as $lecteur)
                                        <span class="px-2 py-0.5 rounded-full bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 text-gray-600 dark:text-gray-300">
                                            {{ $lecteur->prenom }} {{ $lecteur->nom }}
                                        </span>
                                        @endforeach
                                        @if($banniere->lecteurs->count() > 20)
                                        <span class="px-2 py-0.5 text-gray-500 dark:text-gray-400">
                                            +{{ $banniere->lecteurs->count() - 20 }} autres
                                        </span>
                                        @endif
                                    </div>
                                </div>
                                @endif
                                {{-- Actions admin --}}
                                <div class="lg:col-span-2 flex items-center gap-3 pt-2 border-t border-gray-200 dark:border-white/10">
                                    <form method="POST" action="{{ route('admin.emails.toggle-banniere', $banniere) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold border transition-colors
                                            {{ $banniere->actif ? 'border-orange-300 dark:border-orange-700 text-orange-600 dark:text-orange-400 hover:bg-orange-50 dark:hover:bg-orange-900/20' : 'border-green-300 dark:border-green-700 text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20' }}">
                                            {{ $banniere->actif ? '⏸ Désactiver' : '▶ Activer' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.emails.delete-banniere', $banniere) }}"
                                          onsubmit="return confirm('Supprimer définitivement cette bannière ?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                            🗑 Supprimer
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($bannieres->hasPages())
        <div class="px-5 py-4 border-t border-gray-100 dark:border-white/5">
            {{ $bannieres->links() }}
        </div>
        @endif
        @endif
    </div>

</div>
@endsection
