<x-dashboard-layout>
<div class="space-y-8">
    <div>
        <h1 class="text-3xl font-display font-bold text-gray-900 dark:text-white tracking-tight">Marketing</h1>
        <p class="text-gray-500 dark:text-slate-400 mt-2">Configurez vos pixels publicitaires pour suivre les performances de votre boutique</p>
    </div>

    @if(session('success'))
    <div class="bg-emerald-50 dark:bg-emerald-950/40 border-2 border-emerald-200 dark:border-emerald-800/40 rounded-2xl p-5 flex items-start gap-4">
        <div class="w-10 h-10 bg-emerald-500 dark:bg-emerald-600 rounded-xl flex items-center justify-center text-white flex-shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <p class="text-emerald-800 dark:text-emerald-200 font-medium pt-1.5">{{ session('success') }}</p>
    </div>
    @endif

    {{-- Stats rapides --}}
    @if($institut->facebook_pixel_id)
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-100 dark:border-slate-700 text-center">
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats->get('PageView', 0) }}</p>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">Pages vues</p>
        </div>
        <div class="bg-blue-50 dark:bg-blue-500/20 rounded-xl p-4 border border-blue-100 dark:border-blue-500/30 text-center">
            <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $stats->get('ViewContent', 0) }}</p>
            <p class="text-[11px] text-blue-600 dark:text-blue-400">Produits vus</p>
        </div>
        <div class="bg-amber-50 dark:bg-amber-500/20 rounded-xl p-4 border border-amber-100 dark:border-amber-500/30 text-center">
            <p class="text-2xl font-bold text-amber-700 dark:text-amber-300">{{ $stats->get('AddToCart', 0) }}</p>
            <p class="text-[11px] text-amber-600 dark:text-amber-400">Ajouts panier</p>
        </div>
        <div class="bg-emerald-50 dark:bg-emerald-500/20 rounded-xl p-4 border border-emerald-100 dark:border-emerald-500/30 text-center">
            <p class="text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ $stats->get('Purchase', 0) }}</p>
            <p class="text-[11px] text-emerald-600 dark:text-emerald-400">Achats</p>
        </div>
    </div>
    @endif

    {{-- Meta (Facebook & Instagram) --}}
    <div class="card">
        <div class="card-header">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Meta (Facebook & Instagram)</h2>
            </div>
            @if($institut->facebook_pixel_id)
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Connecté
                    </span>
                    @if($institut->facebook_connected_at)
                    <span class="text-xs text-gray-400 dark:text-gray-500">depuis le {{ $institut->facebook_connected_at->format('d/m/Y') }}</span>
                    @endif
                    @if($institut->facebook_pixel_name)
                    <span class="text-xs text-gray-400 dark:text-gray-500">· {{ $institut->facebook_pixel_name }}</span>
                    @endif
                </div>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-400">
                    <span class="w-2 h-2 rounded-full bg-gray-400"></span> Non connecté
                </span>
            @endif
        </div>
        <div class="card-body space-y-4">
            <form method="POST" action="{{ route('dashboard.boutique.config.facebook.save') }}" class="space-y-4" autocomplete="off">
                @csrf
                {{-- Champs factices pour empêcher l'autofill du navigateur --}}
                <input type="text" name="fake_username" autocomplete="off" style="display:none" aria-hidden="true">
                <input type="password" name="fake_password" autocomplete="off" style="display:none" aria-hidden="true">
                <div>
                    <label class="form-label">Pixel ID</label>
                    <input type="text" name="facebook_pixel_id" maxlength="255" class="form-input" placeholder="123456789012345" value="{{ $institut->facebook_pixel_id }}" autocomplete="off">
                    <p class="text-xs text-gray-400 mt-1">Identifiant unique de votre pixel Meta.</p>
                </div>
                <div x-data="{ showToken: false }">
                    <label class="form-label">Access Token (Conversions API) <span class="text-gray-400 font-normal">(optionnel)</span></label>
                    <div class="relative">
                        <input :type="showToken ? 'text' : 'password'" name="facebook_access_token" maxlength="500" class="form-input pr-10" placeholder="EAA..." autocomplete="new-password" value="{{ old('facebook_access_token') }}">
                        @if($institut->facebook_access_token)
                        <button type="button" @click="showToken = !showToken" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="Afficher/Masquer le token">
                            <svg x-show="!showToken" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg x-show="showToken" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243a9.97 9.97 0 01-3.435 2.058M15 12a3 3 0 01-3-3m0 0V4m0 0L9 6m6-2l3 3"/></svg>
                        </button>
                        @endif
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Requis uniquement pour le suivi des achats côté serveur (CAPI).</p>
                </div>
                <div>
                    <label class="form-label">Code de test <span class="text-gray-400 font-normal">(optionnel)</span></label>
                    <input type="text" name="facebook_test_code" maxlength="100" class="form-input" placeholder="TEST12345" value="{{ $institut->facebook_test_code }}" autocomplete="off">
                    <p class="text-xs text-gray-400 mt-1">Code fourni par Meta Events Manager pour tester votre pixel.</p>
                </div>
                <div>
                    <label class="form-label">Nom du pixel <span class="text-gray-400 font-normal">(optionnel)</span></label>
                    <input type="text" name="facebook_pixel_name" maxlength="255" class="form-input" placeholder="Pixel boutique" value="{{ $institut->facebook_pixel_name }}" autocomplete="off">
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn-primary">💾 {{ $institut->facebook_pixel_id ? 'Mettre à jour' : 'Connecter' }}</button>
                    @if($institut->facebook_pixel_id)
                    <button type="button"
                            onclick="window.dispatchEvent(new CustomEvent('confirm-action',{detail:{formId:'form-disconnect-fb',title:'Déconnecter Facebook',message:'Le pixel ne recevra plus d\\'événements.',confirmLabel:'Déconnecter',danger:true}}))"
                            class="btn-outline text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10">🔌 Déconnecter</button>
                    <form id="form-disconnect-fb" method="POST" action="{{ route('dashboard.boutique.config.facebook.disconnect') }}" class="hidden">@csrf @method('DELETE')</form>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Guide --}}
    <div class="card bg-blue-50/50 dark:bg-blue-950/20 border-blue-200 dark:border-blue-800">
        <div class="card-body">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-3">💡 Comment connecter votre Pixel Meta</h3>
            <ol class="space-y-2 text-sm text-gray-600 dark:text-gray-300 list-decimal list-inside">
                <li>Allez dans <a href="https://business.facebook.com/settings/pixels" target="_blank" class="text-blue-600 dark:text-blue-400 underline">Meta Business Suite → Sources de données → Pixels</a></li>
                <li>Créez un nouveau pixel ou sélectionnez-en un existant</li>
                <li>Copiez le <strong>Pixel ID</strong> (série de chiffres)</li>
                <li>Dans <em>Paramètres → Conversions API</em>, générez un <strong>Access Token</strong></li>
                <li>Collez ces deux valeurs ci-dessus et enregistrez</li>
                <li>Optionnel : utilisez le code de test pour vérifier que tout fonctionne</li>
            </ol>
        </div>
    </div>

    {{-- Prochainement --}}
    <div class="grid sm:grid-cols-2 gap-4">
        <div class="card opacity-50">
            <div class="card-body text-center py-8">
                <span class="text-3xl">📊</span>
                <h3 class="font-semibold text-gray-500 mt-2">Google Analytics</h3>
                <p class="text-xs text-gray-400 mt-1">Prochainement</p>
            </div>
        </div>
        <div class="card opacity-50">
            <div class="card-body text-center py-8">
                <span class="text-3xl">🎵</span>
                <h3 class="font-semibold text-gray-500 mt-2">TikTok Pixel</h3>
                <p class="text-xs text-gray-400 mt-1">Prochainement</p>
            </div>
        </div>
    </div>
</div>
</x-dashboard-layout>
