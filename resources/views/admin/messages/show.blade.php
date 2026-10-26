@extends('layouts.admin')
@section('page-title', 'Message de ' . $message->nom)

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- Fil d'ariane + navigation entre messages --}}
    <div class="flex items-center justify-between gap-3">
        <a href="{{ route('admin.messages.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Messages
        </a>
        <div class="flex items-center gap-2">
            @if($precedent)
            <a href="{{ route('admin.messages.show', $precedent) }}" class="btn-secondary btn-sm text-xs">← Précédent</a>
            @endif
            @if($suivant)
            <a href="{{ route('admin.messages.show', $suivant) }}" class="btn-secondary btn-sm text-xs">Suivant →</a>
            @endif
        </div>
    </div>

    @if($message->est_spam)
    <div class="card p-4 border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-900/60">
        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Message classé comme indésirable</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Score {{ $message->spam_score }} · {{ $message->spam_raison ?? 'Aucun détail' }}
        </p>
        <form method="POST" action="{{ route('admin.messages.lire', $message) }}" class="mt-3">
            @csrf @method('PATCH')
            <button class="btn-secondary text-xs">Ce n'est pas du spam</button>
        </form>
    </div>
    @endif

    {{-- En-tête du message --}}
    <div class="card p-6 space-y-4">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-full flex-shrink-0 flex items-center justify-center text-sm font-bold
                        bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-gray-200">
                {{ $message->initiales }}
            </div>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white truncate">{{ $message->nom }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 break-all">{{ $message->email }}</p>
                @if($message->telephone)
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $message->telephone }}</p>
                @endif
            </div>
            <div class="text-right flex-shrink-0">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $message->created_at->format('d/m/Y') }}</p>
                <p class="text-xs text-gray-400">{{ $message->created_at->format('H:i') }}</p>
            </div>
        </div>

        {{-- Contenu --}}
        <div class="rounded-xl p-4 text-sm leading-relaxed whitespace-pre-line
                    bg-gray-50 text-gray-700 dark:bg-slate-900/60 dark:text-gray-200
                    border border-gray-200 dark:border-slate-700">{{ $message->message }}</div>

        {{-- Actions --}}
        <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-gray-100 dark:border-slate-700">
            <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: votre message sur Maëlya Gestion') }}&body={{ rawurlencode("\n\n---\nVotre message du " . $message->created_at->format('d/m/Y H:i') . " :\n" . $message->message) }}"
               class="btn-primary text-sm">Répondre par email</a>

            @if($message->telephone)
            @php
                $tel = preg_replace('/[^0-9]/', '', $message->telephone);
                if ($tel && str_starts_with($tel, '0')) {
                    $tel = '225' . $tel;
                }
            @endphp
            <a href="https://wa.me/{{ $tel }}" target="_blank" rel="noopener" class="btn-secondary text-sm">WhatsApp</a>
            @endif

            @if(!$message->lu)
            <form method="POST" action="{{ route('admin.messages.lire', $message) }}">
                @csrf @method('PATCH')
                <button class="btn-secondary text-sm">Marquer comme lu</button>
            </form>
            @endif

            <form method="POST" action="{{ route('admin.messages.destroy', $message) }}" class="ml-auto"
                  onsubmit="return confirm('Supprimer définitivement ce message ?')">
                @csrf @method('DELETE')
                <button class="text-sm text-red-600 dark:text-red-400 hover:underline px-2">Supprimer</button>
            </form>
        </div>
    </div>

    {{-- Métadonnées techniques --}}
    <div class="card p-5">
        <h2 class="font-bold text-sm text-gray-700 dark:text-gray-200 mb-3">Informations techniques</h2>
        <dl class="grid sm:grid-cols-2 gap-3 text-sm">
            <div>
                <dt class="text-xs text-gray-400">Adresse IP</dt>
                <dd class="font-mono text-gray-700 dark:text-gray-300">{{ $message->ip_address ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400">Statut</dt>
                <dd class="text-gray-700 dark:text-gray-300">
                    {{ $message->est_spam ? 'Indésirable' : ($message->lu ? 'Lu' : 'Non lu') }}
                </dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-xs text-gray-400">Navigateur</dt>
                <dd class="text-gray-600 dark:text-gray-400 break-all text-xs">{{ $message->user_agent ?? '—' }}</dd>
            </div>
        </dl>
    </div>
</div>
@endsection
