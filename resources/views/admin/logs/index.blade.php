@extends('layouts.admin')
@section('page-title', 'Logs Laravel')

@push('styles')
<style>
    #log-output {
        font-family: 'Menlo', 'Consolas', 'Monaco', monospace;
        font-size: 12px;
        line-height: 1.6;
        white-space: pre-wrap;
        word-break: break-all;
    }
    .log-error   { color: #f87171; }
    .log-warning { color: #fbbf24; }
    .log-info    { color: #60a5fa; }
    .log-debug   { color: #9ca3af; }
    .log-date    { color: #a78bfa; font-weight: 600; }
</style>
@endpush

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="page-title">Logs Laravel</h1>
            <p class="page-subtitle">200 dernières lignes de <code class="text-xs bg-gray-100 dark:bg-white/10 px-1.5 py-0.5 rounded">storage/logs/laravel.log</code></p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="copyLogs()" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/10 transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                <span id="copy-label">Copier tout</span>
            </button>
            <a href="{{ route('admin.logs.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/10 transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Rafraîchir
            </a>
            <form method="POST" action="{{ route('admin.logs.clear') }}" onsubmit="return confirm('Vider le fichier de log ?')">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/40 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Vider
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
    <div class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-2xl text-green-800 dark:text-green-300 text-sm font-medium">
        <svg class="w-5 h-5 flex-shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Filtres rapides --}}
    <div class="flex flex-wrap gap-2">
        <button onclick="filtrer('')" class="filtre-btn active px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors border-gray-300 dark:border-white/20 bg-white dark:bg-white/5 text-gray-700 dark:text-gray-300 hover:bg-gray-50">
            Tout afficher
        </button>
        <button onclick="filtrer('ERROR')" class="filtre-btn px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 hover:bg-red-100">
            Erreurs uniquement
        </button>
        <button onclick="filtrer('WARNING')" class="filtre-btn px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 hover:bg-amber-100">
            Warnings
        </button>
        <button onclick="filtrer('AdminEmail')" class="filtre-btn px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors border-purple-200 dark:border-purple-800 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300 hover:bg-purple-100">
            Emails admin
        </button>
    </div>

    {{-- Zone de log --}}
    <div class="card-admin overflow-hidden">
        @if(empty(trim($contenu)))
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <div class="w-16 h-16 rounded-2xl bg-green-50 dark:bg-green-900/20 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Aucune entrée dans le log</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Le fichier de log est vide ou inexistant.</p>
        </div>
        @else
        <div class="p-4 border-b border-gray-100 dark:border-white/5 flex items-center justify-between">
            <span class="text-xs text-gray-400">Affichage des 200 dernières lignes</span>
            <label class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 cursor-pointer">
                <input type="checkbox" id="auto-scroll" checked class="rounded text-purple-600">
                Défiler jusqu'en bas
            </label>
        </div>
        <div id="log-container" class="h-[600px] overflow-y-auto bg-gray-950 dark:bg-black/40 p-5">
            <div id="log-output" class="text-gray-300"></div>
        </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
const rawLog = @json($contenu);
const lines  = rawLog.split('\n');

function coloriserLigne(ligne) {
    if (ligne.match(/\[\d{4}-\d{2}-\d{2}/)) {
        ligne = ligne.replace(/(\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\])/g, '<span class="log-date">$1</span>');
    }
    if (ligne.includes('.ERROR') || ligne.includes('ERROR:') || ligne.includes('Exception') || ligne.includes('FAIL')) {
        return '<span class="log-error">' + escHtml(ligne) + '</span>';
    }
    if (ligne.includes('.WARNING') || ligne.includes('WARNING:')) {
        return '<span class="log-warning">' + escHtml(ligne) + '</span>';
    }
    if (ligne.includes('.INFO') || ligne.includes('INFO:')) {
        return '<span class="log-info">' + escHtml(ligne) + '</span>';
    }
    if (ligne.includes('.DEBUG') || ligne.includes('DEBUG:')) {
        return '<span class="log-debug">' + escHtml(ligne) + '</span>';
    }
    return '<span class="text-gray-400">' + escHtml(ligne) + '</span>';
}

function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function afficherLignes(filtre) {
    const out = document.getElementById('log-output');
    if (!out) return;
    const filtrees = filtre
        ? lines.filter(l => l.toUpperCase().includes(filtre.toUpperCase()))
        : lines;
    out.innerHTML = filtrees.map(coloriserLigne).join('\n');
    if (document.getElementById('auto-scroll')?.checked) {
        const container = document.getElementById('log-container');
        if (container) container.scrollTop = container.scrollHeight;
    }
}

function filtrer(mot) {
    document.querySelectorAll('.filtre-btn').forEach(b => b.classList.remove('ring-2','ring-purple-400'));
    event.target.classList.add('ring-2','ring-purple-400');
    afficherLignes(mot);
}

function copyLogs() {
    navigator.clipboard.writeText(rawLog).then(() => {
        const label = document.getElementById('copy-label');
        label.textContent = '✓ Copié !';
        setTimeout(() => label.textContent = 'Copier tout', 2000);
    });
}

document.addEventListener('DOMContentLoaded', () => afficherLignes(''));
</script>
@endpush
