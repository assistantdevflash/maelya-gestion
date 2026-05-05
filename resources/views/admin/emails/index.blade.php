@extends('layouts.admin')
@section('page-title', 'Emails envoyés')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="page-title">Emails envoyés</h1>
            <p class="page-subtitle">Historique de tous les emails envoyés depuis l'interface d'administration.</p>
        </div>
        <a href="{{ route('admin.emails.composer') }}"
           style="background: linear-gradient(135deg, #9333ea, #ec4899);"
           class="inline-flex items-center gap-2 px-5 py-2.5 text-white font-semibold text-sm rounded-xl shadow-lg hover:opacity-90 transition-all active:scale-95">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="22" y1="2" x2="11" y2="13"/>
                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
            </svg>
            Composer un email
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

    <div class="card-admin overflow-hidden">
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

</div>
@endsection
