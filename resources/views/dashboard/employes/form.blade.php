<x-dashboard-layout>
    <div class="max-w-xl space-y-5">
        <div>
            <h1 class="text-2xl font-display font-bold text-gray-900 tracking-tight">{{ isset($employe) ? 'Modifier '.$employe->nom_complet : 'Ajouter une employée' }}</h1>
        </div>

        <div class="card p-6">
            <form method="POST"
                  action="{{ isset($employe) ? route('dashboard.employes.update', $employe) : route('dashboard.employes.store') }}"
                  class="space-y-4">
                @csrf
                @if(isset($employe)) @method('PUT') @endif

                @if($errors->any())
                    <div class="alert-danger text-sm">
                        @foreach($errors->all() as $e) <p>• {{ $e }}</p> @endforeach
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Prénom *</label>
                        <input type="text" name="prenom" required maxlength="80"
                               value="{{ old('prenom', $employe->prenom ?? '') }}"
                               class="form-input @error('prenom') border-red-400 @enderror"
                               placeholder="Marie">
                        @error('prenom') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nom *</label>
                        <input type="text" name="nom_famille" required maxlength="80"
                               value="{{ old('nom_famille', $employe->nom_famille ?? '') }}"
                               class="form-input @error('nom_famille') border-red-400 @enderror"
                               placeholder="Koné">
                        @error('nom_famille') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" required maxlength="150"
                           value="{{ old('email', $employe->email ?? '') }}"
                           class="form-input @error('email') border-red-400 @enderror"
                           placeholder="marie@votresalon.ci">
                    @error('email') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Téléphone</label>
                    <input type="tel" name="telephone" maxlength="20"
                           value="{{ old('telephone', $employe->telephone ?? '') }}"
                           class="form-input" placeholder="+225...">
                </div>

                <div class="form-group">
                    <label class="form-label">Rôle *</label>
                    <select name="role" required class="form-select">
                        <option value="employe" {{ old('role', $employe->role ?? 'employe') === 'employe' ? 'selected' : '' }}>Employée (accès limité)</option>
                        <option value="admin" {{ old('role', $employe->role ?? '') === 'admin' ? 'selected' : '' }}>Admin (accès complet)</option>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Les employées peuvent vendre et voir les stocks. Les admins gèrent tout.</p>
                </div>

                @if(!isset($employe))
                <div class="form-group">
                    <label class="form-label">Mot de passe initial *</label>
                    <input type="password" name="password" required minlength="8"
                           class="form-input @error('password') border-red-400 @enderror"
                           placeholder="Minimum 8 caractères">
                    @error('password') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Confirmer le mot de passe *</label>
                    <input type="password" name="password_confirmation" required class="form-input">
                </div>
                @endif

                <div class="flex gap-3 pt-2">
                    <a href="{{ route('dashboard.employes.index') }}" class="btn btn-outline flex-1 justify-center">Annuler</a>
                    <button type="submit" class="btn-primary flex-1 justify-center">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</x-dashboard-layout>
