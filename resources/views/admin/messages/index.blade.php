@extends('layouts.admin')
@section('page-title', 'Messages')

@section('content')
<div class="space-y-6">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="page-title">Messages de contact</h1>
            <p class="page-subtitle">
                {{ \App\Helpers\TexteHelper::pluriel($total, 'message', 'messages') }} au total ·
                <span class="{{ $nonLus > 0 ? 'text-primary-600 dark:text-primary-400 font-semibold' : '' }}">{{ \App\Helpers\TexteHelper::pluriel($nonLus, 'non lu', 'non lus') }}</span>
                @if($spam > 0)
                    · <span class="text-gray-400">{{ \App\Helpers\TexteHelper::pluriel($spam, 'indésirable', 'indésirables') }}</span>
                @endif
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($nonLus > 0)
            <form method="POST" action="{{ route('admin.messages.tout-lire') }}">
                @csrf @method('PATCH')
                <button class="btn-secondary text-sm">Tout marquer lu</button>
            </form>
            @endif
            @if($spam > 0)
            <form method="POST" action="{{ route('admin.messages.vider-spam') }}"
                  onsubmit="return confirm('Supprimer définitivement les {{ \App\Helpers\TexteHelper::pluriel($spam, 'message indésirable', 'messages indésirables') }} ?')">
                @csrf @method('DELETE')
                <button class="btn-secondary text-sm text-red-600 dark:text-red-400">Vider les indésirables</button>
            </form>
            @endif
        </div>
    </div>

    {{-- Filtres --}}
    <form method="GET" id="msg-filter" class="flex flex-wrap gap-3">
        <input type="text" name="q" value="{{ $search }}" placeholder="Rechercher un nom, un email, un contenu…"
               class="form-input max-w-sm">
        <select name="statut" class="form-input max-w-[200px]">
            <option value="tous"    @selected($statut === 'tous')>Tous les messages</option>
            <option value="non_lus" @selected($statut === 'non_lus')>Non lus</option>
            <option value="lus"     @selected($statut === 'lus')>Lus</option>
            <option value="spam"    @selected($statut === 'spam')>Indésirables</option>
        </select>
        <button type="submit" class="btn-primary text-sm">Filtrer</button>
        @if($search !== '' || $statut !== 'tous')
            <a href="{{ route('admin.messages.index') }}" class="btn-secondary text-sm">Réinitialiser</a>
        @endif
    </form>

    <div class="card overflow-hidden">

        {{-- Mobile : cartes --}}
        <div class="sm:hidden divide-y divide-gray-100 dark:divide-slate-700">
            @forelse($messages as $msg)
            <a href="{{ route('admin.messages.show', $msg) }}" class="block p-4 hover:bg-gray-50 dark:hover:bg-slate-800/60 transition">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-full flex-shrink-0 flex items-center justify-center text-xs font-bold
                                bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-gray-200">
                        {{ $msg->initiales }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-sm truncate {{ $msg->lu || $msg->est_spam ? 'font-medium text-gray-700 dark:text-gray-300' : 'font-bold text-gray-900 dark:text-white' }}">{{ $msg->nom }}</p>
                                <p class="text-xs text-gray-400 truncate">{{ $msg->email }}</p>
                            </div>
                            @if($msg->est_spam)
                                <span class="badge text-xs flex-shrink-0 bg-gray-100 text-gray-500 dark:bg-slate-700 dark:text-gray-400">Indésirable</span>
                            @elseif(!$msg->lu)
                                <span class="badge text-xs flex-shrink-0 bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-300">Non lu</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">{{ $msg->apercu }}</p>
                        <p class="text-xs text-gray-400 mt-1.5">{{ $msg->created_at->format('d/m/Y à H:i') }}</p>
                    </div>
                </div>
            </a>
            @empty
            <div class="p-10 text-center text-gray-400 text-sm">Aucun message.</div>
            @endforelse
        </div>

        {{-- Desktop : tableau --}}
        <div class="hidden sm:block overflow-x-auto">
        <table class="table-auto w-full">
            <thead>
            <tr>
                <th class="w-8"></th>
                <th>Expéditeur</th>
                <th>Aperçu</th>
                <th>Reçu le</th>
                <th>Statut</th>
                <th class="text-right">Actions</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
            @forelse($messages as $msg)
            <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/60 transition">
                <td class="pl-4">
                    @unless($msg->lu || $msg->est_spam)
                    <span class="inline-block w-2 h-2 rounded-full bg-primary-500" title="Non lu"></span>
                    @endunless
                </td>
                <td>
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center text-xs font-bold
                                    bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-gray-200">
                            {{ $msg->initiales }}
                        </div>
                        <div class="min-w-0">
                            <div class="truncate {{ $msg->lu || $msg->est_spam ? 'font-medium text-gray-700 dark:text-gray-300' : 'font-bold text-gray-900 dark:text-white' }}">{{ $msg->nom }}</div>
                            <div class="text-xs text-gray-400 truncate">{{ $msg->email }}</div>
                        </div>
                    </div>
                </td>
                <td class="text-sm text-gray-500 dark:text-gray-400 max-w-md">
                    <span class="line-clamp-1">{{ $msg->apercu }}</span>
                </td>
                <td class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                    {{ $msg->created_at->format('d/m/Y') }}<br>
                    <span class="text-xs text-gray-400">{{ $msg->created_at->format('H:i') }}</span>
                </td>
                <td>
                    @if($msg->est_spam)
                        <span class="badge text-xs bg-gray-100 text-gray-500 dark:bg-slate-700 dark:text-gray-400">Indésirable</span>
                    @elseif($msg->lu)
                        <span class="badge text-xs bg-gray-100 text-gray-500 dark:bg-slate-700 dark:text-gray-400">Lu</span>
                    @else
                        <span class="badge text-xs bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-300">Non lu</span>
                    @endif
                </td>
                <td>
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('admin.messages.show', $msg) }}" class="btn-outline btn-sm text-xs">Lire</a>
                        <form action="{{ route('admin.messages.destroy', $msg) }}" method="POST">
                            @csrf @method('DELETE')
                            <button type="button" class="text-xs text-red-600 dark:text-red-400 hover:underline px-2"
                                    onclick="if(confirm('Supprimer définitivement ce message ?')) this.closest('form').submit()">Supprimer</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center py-10 text-gray-400">Aucun message.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="pt-2">
        {{ $messages->links() }}
    </div>
</div>
@endsection
