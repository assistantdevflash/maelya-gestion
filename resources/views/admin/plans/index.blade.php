@extends('layouts.admin')
@section('page-title', "Plans d'abonnement")

@section('content')
<div x-data="plansManager()" class="space-y-6">

    {{-- En-tête --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="page-title">Plans d'abonnement</h1>
            <p class="page-subtitle">Gérez les formules proposées aux instituts.</p>
        </div>
        <button @click="openCreate()" class="btn-primary inline-flex items-center justify-center gap-2 sm:w-auto">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            <span>Nouveau plan</span>
        </button>
    </div>

    {{-- Liste des plans --}}
    @forelse($plans as $plan)
        @php $offrePlan = $plan->meilleureOffre(); @endphp
        
        {{-- Card responsive pour chaque plan --}}
        <div class="card hover:shadow-lg transition-shadow duration-200 {{ $plan->mis_en_avant ? 'ring-2 ring-amber-400 dark:ring-amber-500' : '' }}">
            <div class="p-6">
                {{-- En-tête du plan --}}
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-5">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 flex-wrap mb-2">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $plan->nom }}</h3>
                            
                            {{-- Badge Recommandé --}}
                            @if($plan->mis_en_avant)
                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-full bg-gradient-to-r from-amber-400 to-amber-500 text-white shadow-md">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    Recommandé
                                </span>
                            @endif
                            
                            {{-- Badge Offre promo --}}
                            @if($offrePlan)
                                <span class="inline-flex items-center gap-1 text-xs font-semibold px-3 py-1 rounded-full bg-gradient-to-r {{ $offrePlan->badge_class }} text-white shadow-md">
                                    🎉 {{ $offrePlan->badge_texte }}
                                </span>
                            @endif
                            
                            {{-- Badge Statut --}}
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-full {{ $plan->actif ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/90 dark:text-white' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $plan->actif ? 'bg-emerald-500 dark:bg-white' : 'bg-gray-400' }}"></span>
                                {{ $plan->actif ? 'Actif' : 'Inactif' }}
                            </span>
                        </div>
                        
                        @if($plan->description)
                            <p class="text-sm text-gray-600 dark:text-slate-300 mb-2">{{ $plan->description }}</p>
                        @endif
                        
                        <div class="inline-flex items-center gap-1.5 text-xs text-gray-400 dark:text-slate-400 font-mono">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            {{ $plan->slug }}
                        </div>
                    </div>
                    
                    {{-- Prix principal --}}
                    <div class="bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-600/30 dark:to-primary-700/30 dark:border-2 dark:border-primary-500/50 rounded-xl px-5 py-4 text-center min-w-[160px]">
                        @if($offrePlan)
                            <div class="text-sm text-gray-500 dark:text-slate-200 line-through mb-1">
                                {{ number_format($plan->prix, 0, ',', ' ') }} FCFA
                            </div>
                            <div class="text-3xl font-bold text-primary-600 dark:text-primary-200">
                                {{ number_format($plan->prixEffectif(), 0, ',', ' ') }}
                            </div>
                            <div class="text-xs text-emerald-600 dark:text-emerald-300 font-semibold mt-1">
                                {{ $offrePlan->reduction_texte }}
                            </div>
                            <div class="text-[10px] text-gray-400 dark:text-slate-300 mt-1">jusqu'au {{ $offrePlan->date_fin->format('d/m/Y') }}</div>
                        @else
                            <div class="text-3xl font-bold text-primary-600 dark:text-primary-200">
                                {{ number_format($plan->prix, 0, ',', ' ') }}
                            </div>
                        @endif
                        <div class="text-xs text-gray-500 dark:text-slate-200 font-medium mt-1">FCFA / mois</div>
                    </div>
                </div>

                {{-- Contenu principal --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {{-- Limites --}}
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold text-gray-500 dark:text-slate-300 uppercase tracking-wider mb-3">Limites</h4>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $plan->max_employes ?? '∞' }}</div>
                                <div class="text-xs text-gray-500 dark:text-slate-400">Employés</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $plan->max_instituts ?? '∞' }}</div>
                                <div class="text-xs text-gray-500 dark:text-slate-400">Instituts</div>
                            </div>
                        </div>
                    </div>

                    {{-- Tarifs par période --}}
                    <div class="lg:col-span-2 space-y-3">
                        <h4 class="text-xs font-bold text-gray-500 dark:text-slate-300 uppercase tracking-wider mb-3">Tarifs par période</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div class="bg-amber-50 dark:bg-amber-900/30 border-2 border-amber-200 dark:border-amber-700/60 rounded-lg p-3 text-center">
                                <div class="text-xs font-semibold text-amber-700 dark:text-amber-300 mb-1">3 mois</div>
                                <div class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($plan->prixPourPeriode('trimestre'), 0, ',', ' ') }}</div>
                                <div class="text-[10px] text-emerald-600 dark:text-emerald-300 font-semibold mt-1">-5%</div>
                            </div>
                            <div class="bg-sky-50 dark:bg-sky-900/30 border-2 border-sky-200 dark:border-sky-700/60 rounded-lg p-3 text-center">
                                <div class="text-xs font-semibold text-sky-700 dark:text-sky-300 mb-1">6 mois</div>
                                <div class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($plan->prixPourPeriode('semestre'), 0, ',', ' ') }}</div>
                                <div class="text-[10px] text-emerald-600 dark:text-emerald-300 font-semibold mt-1">-10%</div>
                            </div>
                            <div class="bg-indigo-50 dark:bg-indigo-900/30 border-2 border-indigo-200 dark:border-indigo-700/60 rounded-lg p-3 text-center">
                                <div class="text-xs font-semibold text-indigo-700 dark:text-indigo-300 mb-1">1 an</div>
                                <div class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($plan->prixPourPeriode('annuel'), 0, ',', ' ') }}</div>
                                <div class="text-[10px] text-emerald-600 dark:text-emerald-300 font-semibold mt-1">-15%</div>
                            </div>
                            <div class="bg-emerald-50 dark:bg-emerald-900/30 border-2 border-emerald-200 dark:border-emerald-700/60 rounded-lg p-3 text-center">
                                <div class="text-xs font-semibold text-emerald-700 dark:text-emerald-300 mb-1">3 ans</div>
                                <div class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($plan->prixPourPeriode('triennal'), 0, ',', ' ') }}</div>
                                <div class="text-[10px] text-emerald-600 dark:text-emerald-300 font-semibold mt-1">-20%</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 mt-6 pt-5 border-t border-gray-100 dark:border-slate-700">
                    @if(!$plan->mis_en_avant)
                    <form action="{{ route('admin.plans.featurer', $plan) }}" method="POST" class="flex-1 sm:flex-initial">
                        @csrf
                        <button type="submit" title="Mettre en avant"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium rounded-lg border-2 border-amber-300 text-amber-700 bg-amber-50 hover:bg-amber-100 dark:bg-amber-900/40 dark:border-amber-600 dark:text-amber-300 dark:hover:bg-amber-900/60 transition-all duration-200 shadow-sm hover:shadow">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <span>Mettre en avant</span>
                        </button>
                    </form>
                    @endif
                    
                    <div class="flex-1 flex items-center gap-2">
                        <button @click='openEdit(@json($plan))'
                            class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium rounded-lg border-2 border-primary-200 text-primary-700 bg-primary-50 hover:bg-primary-100 dark:bg-primary-900/40 dark:border-primary-600 dark:text-primary-300 dark:hover:bg-primary-900/60 transition-all duration-200 shadow-sm hover:shadow">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            <span>Modifier</span>
                        </button>
                        
                        <form id="form-plan-{{ $plan->id }}" action="{{ route('admin.plans.destroy', $plan) }}" method="POST" class="flex-1 sm:flex-initial">
                            @csrf @method('DELETE')
                            <button type="button" 
                                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium rounded-lg border-2 border-red-200 text-red-600 bg-red-50 hover:bg-red-100 dark:bg-red-900/40 dark:border-red-600 dark:text-red-300 dark:hover:bg-red-900/60 transition-all duration-200 shadow-sm hover:shadow"
                                    onclick="window.dispatchEvent(new CustomEvent('confirm-action',{detail:{formId:'form-plan-{{ $plan->id }}',title:'Désactiver ce plan',message:'Ce plan ne sera plus disponible à la souscription.',confirmLabel:'Désactiver',confirmClass:'!bg-red-600 hover:!bg-red-700',danger:true}}))">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                                <span>Désactiver</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="card">
            <div class="p-16 text-center">
                <div class="w-20 h-20 rounded-full bg-gray-100 dark:bg-slate-700 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-gray-400 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Aucun plan d'abonnement</h3>
                <p class="text-sm text-gray-500 dark:text-slate-300 mb-6">Commencez par créer votre premier plan.</p>
                <button @click="openCreate()" class="btn-primary inline-flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    <span>Créer un plan</span>
                </button>
            </div>
        </div>
    @endforelse

    {{-- Modal créer / modifier --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
         @keydown.escape.window="open = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div @click.outside="open = false" 
             class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            
            {{-- En-tête du modal --}}
            <div class="sticky top-0 bg-white dark:bg-slate-800 border-b border-gray-100 dark:border-slate-700 px-6 py-4 flex items-center justify-between rounded-t-2xl">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                    </div>
                    <span x-text="editing ? 'Modifier le plan' : 'Nouveau plan'"></span>
                </h2>
                <button @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form :action="editing ? '{{ route('admin.plans.index') }}/' + form.id : '{{ route('admin.plans.store') }}'" method="POST" class="p-6 space-y-6">
                @csrf
                <template x-if="editing">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                {{-- Informations générales --}}
                <div class="space-y-4">
                    <h3 class="text-sm font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Informations générales
                    </h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">
                                Nom du plan <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="nom" x-model="form.nom" 
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" 
                                   placeholder="Ex: Premium" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">
                                Slug <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="slug" x-model="form.slug" 
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white font-mono text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" 
                                   placeholder="premium" required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Description</label>
                        <textarea name="description" x-model="form.description" rows="3" 
                                  class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all resize-none" 
                                  placeholder="Description courte du plan..."></textarea>
                    </div>
                </div>

                {{-- Tarification --}}
                <div class="space-y-4">
                    <h3 class="text-sm font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Tarification
                    </h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">
                                Prix mensuel (FCFA) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="number" name="prix" x-model="form.prix" min="0" step="100"
                                       class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" 
                                       placeholder="5000" required>
                                <div class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 dark:text-slate-400 font-semibold">FCFA</div>
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">Les réductions automatiques s'appliquent : 3 mois (-5%), 6 mois (-10%), 1 an (-15%), 3 ans (-20%)</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">
                                Ordre d'affichage
                            </label>
                            <input type="number" name="ordre" x-model="form.ordre" min="0" 
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" 
                                   placeholder="0">
                        </div>
                    </div>
                </div>

                {{-- Limites --}}
                <div class="space-y-4">
                    <h3 class="text-sm font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        Limites d'utilisation
                    </h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">
                                Maximum d'employés
                            </label>
                            <input type="number" name="max_employes" x-model="form.max_employes" min="0" 
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" 
                                   placeholder="Illimité">
                            <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">Laisser vide pour illimité</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">
                                Maximum d'instituts
                            </label>
                            <input type="number" name="max_instituts" x-model="form.max_instituts" min="1" 
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" 
                                   placeholder="Illimité">
                            <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">Laisser vide pour illimité</p>
                        </div>
                    </div>
                </div>

                {{-- Note offres promo --}}
                <div class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/30 dark:to-orange-900/30 border-2 border-amber-200 dark:border-amber-700/60 rounded-xl p-4">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-800/50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-amber-600 dark:text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-amber-900 dark:text-amber-200 mb-1">Offres promotionnelles</h4>
                            <p class="text-xs text-amber-700 dark:text-amber-300">
                                Les offres promotionnelles se gèrent depuis la page
                                <a href="{{ route('admin.offres.index') }}" class="underline font-semibold hover:text-amber-900 dark:hover:text-amber-100">Offres promo</a>.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Options --}}
                <div class="space-y-3">
                    <h3 class="text-sm font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                        </svg>
                        Options
                    </h3>
                    
                    <div class="space-y-3">
                        <label class="flex items-center gap-3 p-3 rounded-lg border-2 border-gray-200 dark:border-slate-600 hover:border-primary-300 dark:hover:border-primary-600 cursor-pointer transition-all group">
                            <input type="checkbox" name="mis_en_avant" value="1" :checked="form.mis_en_avant" 
                                   class="w-5 h-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500 focus:ring-offset-0">
                            <div class="flex-1">
                                <div class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400">Mettre en avant</div>
                                <div class="text-xs text-gray-500 dark:text-slate-400">Affiche un badge "Recommandé" sur ce plan</div>
                            </div>
                        </label>
                        
                        <label class="flex items-center gap-3 p-3 rounded-lg border-2 border-gray-200 dark:border-slate-600 hover:border-emerald-300 dark:hover:border-emerald-600 cursor-pointer transition-all group">
                            <input type="checkbox" name="actif" value="1" :checked="form.actif" 
                                   class="w-5 h-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 focus:ring-offset-0">
                            <div class="flex-1">
                                <div class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400">Plan actif</div>
                                <div class="text-xs text-gray-500 dark:text-slate-400">Rendre ce plan disponible à la souscription</div>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-6 border-t border-gray-100 dark:border-slate-700">
                    <button type="button" @click="open = false" 
                            class="px-6 py-2.5 text-sm font-medium rounded-lg border-2 border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-all">
                        Annuler
                    </button>
                    <button type="submit"
                            class="px-6 py-2.5 text-sm font-semibold rounded-lg bg-gradient-to-r from-primary-500 to-primary-600 text-white hover:from-primary-600 hover:to-primary-700 shadow-lg hover:shadow-xl transition-all inline-flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span x-text="editing ? 'Enregistrer les modifications' : 'Créer le plan'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function plansManager() {
    return {
        open: false,
        editing: false,
        form: { id: null, nom: '', slug: '', prix: '', max_employes: '', max_instituts: '', description: '', mis_en_avant: false, actif: true, ordre: 0 },
        openCreate() { this.editing = false; this.form = { id: null, nom: '', slug: '', prix: '', max_employes: '', max_instituts: '', description: '', mis_en_avant: false, actif: true, ordre: 0 }; this.open = true; },
        openEdit(plan) { this.editing = true; this.form = { ...plan, mis_en_avant: !!plan.mis_en_avant, actif: !!plan.actif }; this.open = true; }
    }
}
</script>
@endpush
@endsection
