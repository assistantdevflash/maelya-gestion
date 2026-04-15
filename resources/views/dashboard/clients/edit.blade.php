<x-dashboard-layout>
    <div class="max-w-xl space-y-5">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard.clients.index') }}" class="btn-icon text-gray-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-display font-bold text-gray-900 tracking-tight">Modifier {{ $client->nom_complet }}</h1>
            </div>
        </div>

        <div class="card p-6">
            <form method="POST" action="{{ route('dashboard.clients.update', $client) }}" class="space-y-4">
                @csrf @method('PUT')

                @if($errors->any())
                    <div class="alert-danger text-sm">
                        @foreach($errors->all() as $e) <p>• {{ $e }}</p> @endforeach
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Prénom *</label>
                        <input type="text" name="prenom" required maxlength="80"
                               value="{{ old('prenom', $client->prenom) }}"
                               class="form-input @error('prenom') border-red-400 @enderror">
                        @error('prenom') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nom *</label>
                        <input type="text" name="nom" required maxlength="80"
                               value="{{ old('nom', $client->nom) }}"
                               class="form-input @error('nom') border-red-400 @enderror">
                        @error('nom') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Téléphone</label>
                    <input type="tel" name="telephone" maxlength="20"
                           value="{{ old('telephone', $client->telephone) }}" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" maxlength="150"
                           value="{{ old('email', $client->email) }}" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Date de naissance</label>
                    <input type="date" name="date_naissance"
                           value="{{ old('date_naissance', $client->date_naissance?->format('Y-m-d')) }}"
                           class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" rows="3" maxlength="1000"
                              class="form-textarea">{{ old('notes', $client->notes) }}</textarea>
                </div>

                <div class="flex gap-3 pt-2">
                    <a href="{{ route('dashboard.clients.show', $client) }}" class="btn btn-outline flex-1 justify-center">Annuler</a>
                    <button type="submit" class="btn-primary flex-1 justify-center">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</x-dashboard-layout>
