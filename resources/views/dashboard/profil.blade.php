<x-dashboard-layout>
    <div class="max-w-2xl mx-auto space-y-6" x-data="{ showPassword: false }">

        @if(session('success'))
            <div class="alert-success flex items-center gap-2">
                <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert-danger text-sm">
                @foreach($errors->all() as $e) <p>• {{ $e }}</p> @endforeach
            </div>
        @endif

        {{-- Carte profil hero --}}
        <div class="card overflow-hidden">
            <div class="h-28 relative" style="background: linear-gradient(135deg, #9333ea 0%, #ec4899 50%, #f97316 100%);">
                <div class="absolute inset-0 opacity-20" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23ffffff&quot; fill-opacity=&quot;0.15&quot;%3E%3Ccircle cx=&quot;30&quot; cy=&quot;30&quot; r=&quot;4&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>
            <div class="px-6 pb-6 pt-14 relative">
                <div class="absolute -top-10 left-6">
                    <div class="w-20 h-20 rounded-2xl bg-white shadow-lg border-4 border-white flex items-center justify-center shrink-0">
                        <span class="text-2xl font-bold" style="background: linear-gradient(135deg, #9333ea, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                            {{ strtoupper(substr(auth()->user()->prenom ?? auth()->user()->name, 0, 1)) }}{{ strtoupper(substr(auth()->user()->nom_famille ?? '', 0, 1)) }}
                        </span>
                    </div>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900">{{ auth()->user()->nom_complet }}</h1>
                    <div class="flex flex-wrap items-center gap-2 mt-1">
                        <span class="text-sm text-gray-500">{{ auth()->user()->email }}</span>
                        <span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full"
                              style="background: linear-gradient(135deg, #9333ea20, #ec489920); color: #9333ea;">
                            {{ match(auth()->user()->role) { 'super_admin' => 'Super Admin', 'admin' => 'Admin', 'employe' => 'Employé', default => auth()->user()->role } }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('dashboard.profil.update') }}" class="space-y-6">
            @csrf @method('PUT')

            {{-- Informations personnelles --}}
            <div class="card p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-9 h-9 rounded-xl bg-primary-50 flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-gray-900">Informations personnelles</h2>
                        <p class="text-xs text-gray-400">Vos coordonnées et identifiants</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">
                                <svg class="w-3.5 h-3.5 inline -mt-0.5 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                Prénom
                            </label>
                            <input type="text" name="prenom" maxlength="80"
                                   value="{{ old('prenom', auth()->user()->prenom) }}"
                                   placeholder="Votre prénom"
                                   class="form-input @error('prenom') border-red-400 @enderror">
                            @error('prenom') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                <svg class="w-3.5 h-3.5 inline -mt-0.5 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                Nom de famille
                            </label>
                            <input type="text" name="nom_famille" maxlength="80"
                                   value="{{ old('nom_famille', auth()->user()->nom_famille) }}"
                                   placeholder="Votre nom"
                                   class="form-input @error('nom_famille') border-red-400 @enderror">
                            @error('nom_famille') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <svg class="w-3.5 h-3.5 inline -mt-0.5 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Adresse email
                        </label>
                        <input type="email" name="email" required
                               value="{{ old('email', auth()->user()->email) }}"
                               placeholder="vous@exemple.com"
                               class="form-input @error('email') border-red-400 @enderror">
                        @error('email') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <svg class="w-3.5 h-3.5 inline -mt-0.5 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            Téléphone
                        </label>
                        <input type="tel" name="telephone" maxlength="20"
                               value="{{ old('telephone', auth()->user()->telephone) }}"
                               placeholder="+225 07 00 00 00 00"
                               class="form-input">
                    </div>
                </div>
            </div>

            {{-- Sécurité --}}
            <div class="card p-6">
                <button type="button" @click="showPassword = !showPassword" class="w-full flex items-center justify-between group">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center">
                            <svg class="w-4.5 h-4.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <div class="text-left">
                            <h2 class="text-sm font-bold text-gray-900">Sécurité</h2>
                            <p class="text-xs text-gray-400">Modifier votre mot de passe</p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="showPassword && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="showPassword" x-collapse x-cloak class="mt-5 pt-5 border-t border-gray-100 space-y-4">
                    <div class="form-group">
                        <label class="form-label">
                            <svg class="w-3.5 h-3.5 inline -mt-0.5 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                            Mot de passe actuel
                        </label>
                        <input type="password" name="password_actuel"
                               placeholder="••••••••"
                               class="form-input @error('password_actuel') border-red-400 @enderror">
                        @error('password_actuel') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Nouveau mot de passe</label>
                            <input type="password" name="password" minlength="8"
                                   placeholder="Min. 8 caractères"
                                   class="form-input @error('password') border-red-400 @enderror">
                            @error('password') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirmer</label>
                            <input type="password" name="password_confirmation"
                                   placeholder="Retapez le mot de passe"
                                   class="form-input">
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Laissez vide si vous ne souhaitez pas changer de mot de passe.
                    </p>
                </div>
            </div>

            {{-- Bouton enregistrer --}}
            <div class="flex justify-end">
                <button type="submit" class="btn-primary px-8 py-2.5 justify-center text-sm font-semibold shadow-lg shadow-primary-200/50 hover:shadow-primary-300/50 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Enregistrer les modifications
                </button>
            </div>
        </form>
    </div>
</x-dashboard-layout>
