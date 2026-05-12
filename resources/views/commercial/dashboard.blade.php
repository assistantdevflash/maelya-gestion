@extends('layouts.commercial')
@section('title', 'Tableau de bord')

@section('content')
{{-- En-tête --}}
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Bonjour {{ Auth::user()->prenom }} 👋</h1>
    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Votre espace commercial Maëlya Gestion</p>
</div>

{{-- Carte code de parrainage --}}
<div class="rounded-2xl p-5 mb-6 border border-purple-200 dark:border-purple-800"
     style="background: linear-gradient(135deg, rgba(147,51,234,0.06), rgba(236,72,153,0.04));">
    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Votre lien de parrainage</p>

    @php $lienParrainage = url('/inscription') . '?ref=' . $profil->code; @endphp

    <div x-data="{ copiedLink: false, copiedCode: false }">
        {{-- Lien complet --}}
        <div class="flex items-center gap-2 mb-3">
            <div class="flex-1 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 font-mono text-xs text-gray-600 dark:text-gray-400 truncate">
                {{ $lienParrainage }}
            </div>
            <button @click="navigator.clipboard.writeText('{{ $lienParrainage }}'); copiedLink=true; setTimeout(()=>copiedLink=false,2000)"
                    class="shrink-0 flex items-center gap-1.5 px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <template x-if="!copiedLink">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                </template>
                <template x-if="copiedLink">
                    <svg class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </template>
                <span x-text="copiedLink ? 'Copié !' : 'Copier'"></span>
            </button>
        </div>

        {{-- Code court --}}
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-500 dark:text-gray-400">Ou code court :</span>
            <button @click="navigator.clipboard.writeText('{{ $profil->code }}'); copiedCode=true; setTimeout(()=>copiedCode=false,2000)"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-white text-sm font-mono font-bold transition-all"
                    style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                {{ $profil->code }}
                <template x-if="!copiedCode">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                </template>
                <template x-if="copiedCode">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </template>
            </button>
        </div>

        @if($config)
        <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
            Vous touchez <strong class="text-gray-600 dark:text-gray-300">{{ $config->taux }}%</strong> de commission
            pendant <strong class="text-gray-600 dark:text-gray-300">{{ $config->duree_mois }} mois</strong>
            sur chaque abonnement payant des établissements que vous parrainez.
        </p>
        @endif
    </div>
</div>

{{-- KPIs --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700">
        <p class="text-xs text-gray-500 dark:text-gray-400">Parrainages</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $profil->parrainages_count }}</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700">
        <p class="text-xs text-gray-500 dark:text-gray-400">Commissions</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $profil->commissions_count }}</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700">
        <p class="text-xs text-gray-500 dark:text-gray-400">Total reçu</p>
        <p class="text-xl font-bold text-green-600 dark:text-green-400 mt-1">{{ number_format($totalGagne, 0, ',', ' ') }} FCFA</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700">
        <p class="text-xs text-gray-500 dark:text-gray-400">À recevoir</p>
        <p class="text-xl font-bold text-purple-600 dark:text-purple-400 mt-1">{{ number_format($totalEnAttente, 0, ',', ' ') }} FCFA</p>
    </div>
</div>

{{-- Derniers parrainages --}}
@if($derniersParrainages->isNotEmpty())
<div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 mb-4">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700">
        <h2 class="font-semibold text-sm text-gray-900 dark:text-white">Derniers parrainages</h2>
        <a href="{{ route('commercial.parrainages') }}" class="text-xs text-purple-600 dark:text-purple-400 hover:underline">Tout voir</a>
    </div>
    <div class="divide-y divide-gray-100 dark:divide-gray-700">
        @foreach($derniersParrainages as $p)
        <div class="flex items-center justify-between px-5 py-3">
            <div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $p->proprietaire?->nom_complet }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $p->proprietaire?->institut?->nom ?? '—' }}</p>
            </div>
            <div class="text-right">
                @if($p->isActif())
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 text-[11px] font-medium">Actif</span>
                @else
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-500 text-[11px] font-medium">Expiré</span>
                @endif
                <p class="text-[11px] text-gray-400 mt-0.5">Jusqu'au {{ $p->expire_le->format('d/m/Y') }}</p>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Dernières commissions --}}
@if($dernieresCommissions->isNotEmpty())
<div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700">
        <h2 class="font-semibold text-sm text-gray-900 dark:text-white">Dernières commissions</h2>
        <a href="{{ route('commercial.commissions') }}" class="text-xs text-purple-600 dark:text-purple-400 hover:underline">Tout voir</a>
    </div>
    <div class="divide-y divide-gray-100 dark:divide-gray-700">
        @foreach($dernieresCommissions as $c)
        <div class="flex items-center justify-between px-5 py-3">
            <div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $c->parrainage?->proprietaire?->nom_complet }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $c->abonnement?->plan?->nom ?? '—' }} — {{ $c->taux }}%</p>
            </div>
            <div class="text-right">
                <p class="text-sm font-bold {{ $c->statut === 'payee' ? 'text-green-600 dark:text-green-400' : 'text-purple-600 dark:text-purple-400' }}">
                    {{ $c->montant_formatte }}
                </p>
                <span class="text-[11px] {{ $c->statut === 'payee' ? 'text-green-500' : 'text-gray-400' }}">
                    {{ $c->statut === 'payee' ? 'Payée' : 'En attente' }}
                </span>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@if($derniersParrainages->isEmpty() && $dernieresCommissions->isEmpty())
<div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-12 text-center">
    <div class="w-14 h-14 rounded-2xl mx-auto mb-4 flex items-center justify-center"
         style="background: linear-gradient(135deg, rgba(147,51,234,0.12), rgba(236,72,153,0.08));">
        <svg class="w-7 h-7 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
    </div>
    <p class="font-semibold text-gray-700 dark:text-gray-300">Partagez votre code pour commencer</p>
    <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Chaque inscription via votre code vous génère des commissions.</p>
</div>
@endif
@endsection
