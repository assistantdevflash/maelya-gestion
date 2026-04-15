<x-dashboard-layout>
    <div class="max-w-xl space-y-5">
        <div>
            <h1 class="text-2xl font-display font-bold text-gray-900 tracking-tight">Nouveau client</h1>
            <p class="text-sm text-gray-500 mt-1">Enregistrez une nouvelle cliente dans votre base.</p>
        </div>

        <div class="card p-6">
            <form method="POST" action="{{ route('dashboard.clients.store') }}" class="space-y-4">
                @csrf

                @if($errors->any())
                    <div class="alert-danger text-sm">
                        @foreach($errors->all() as $e) <p>• {{ $e }}</p> @endforeach
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Prénom *</label>
                        <input type="text" name="prenom" required maxlength="80"
                               value="{{ old('prenom') }}" class="form-input @error('prenom') border-red-400 @enderror"
                               placeholder="Fatou">
                        @error('prenom') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nom *</label>
                        <input type="text" name="nom" required maxlength="80"
                               value="{{ old('nom') }}" class="form-input @error('nom') border-red-400 @enderror"
                               placeholder="Traoré">
                        @error('nom') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Téléphone</label>
                    <input type="tel" name="telephone" maxlength="20"
                           value="{{ old('telephone') }}" class="form-input"
                           placeholder="+225 07 00 00 00 00">
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" maxlength="150"
                           value="{{ old('email') }}" class="form-input"
                           placeholder="fatou@exemple.com">
                </div>

                <div class="form-group">
                    <label class="form-label">Date de naissance</label>
                    <input type="date" name="date_naissance"
                           value="{{ old('date_naissance') }}" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" rows="3" maxlength="1000"
                              class="form-textarea"
                              placeholder="Préférences, remarques...">{{ old('notes') }}</textarea>
                </div>

                <div class="flex gap-3 pt-2">
                    <a href="{{ route('dashboard.clients.index') }}" class="btn btn-outline flex-1 justify-center">Annuler</a>
                    <button type="submit" class="btn-primary flex-1 justify-center">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</x-dashboard-layout>
