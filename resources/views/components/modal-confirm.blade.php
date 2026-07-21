<div id="{{ $id }}" class="hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4"
     onclick="if(event.target===this) this.classList.add('hidden')">
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" onclick="event.stopPropagation()">
        <div class="p-6 text-center">
            @if(($danger ?? false))
            <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </div>
            @else
            <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            @endif
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ $title }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">{!! $message !!}</p>
        </div>
        <div class="grid grid-cols-2 divide-x divide-gray-100 dark:divide-slate-700 border-t border-gray-100 dark:border-slate-700">
            <button onclick="document.getElementById('{{ $id }}').classList.add('hidden')"
                    class="py-3.5 text-sm font-semibold text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-slate-800 transition">
                Annuler
            </button>
            @if(isset($action))
            <form method="POST" action="{{ $action }}">
                @csrf
                @if(($method ?? 'POST') !== 'POST') @method($method) @endif
                <button class="w-full py-3.5 text-sm font-semibold {{ ($danger ?? false) ? 'text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20' : 'text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20' }} transition">
                    {{ $confirm ?? 'Confirmer' }}
                </button>
            </form>
            @elseif(isset($href))
            <a href="{{ $href }}"
               class="flex items-center justify-center py-3.5 text-sm font-semibold text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition">
                {{ $confirm ?? 'Confirmer' }}
            </a>
            @endif
        </div>
    </div>
</div>
