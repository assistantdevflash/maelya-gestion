<x-dashboard-layout>
    <div class="space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-display font-bold text-gray-900 tracking-tight">Employés</h1>
                <p class="text-sm text-gray-500 mt-1">Gérez l'accès de votre équipe.
                    @if($maxEmployes !== null)
                        <span class="ml-2 inline-flex items-center gap-1 text-xs font-medium {{ $limitAtteinte ? 'text-red-500' : 'text-gray-400' }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
                            {{ $nbEmployes }} / {{ $maxEmployes }} employé(s)
                        </span>
                    @endif
                </p>
            </div>
            @if($limitAtteinte)
                <a href="{{ route('abonnement.plans') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold text-white shadow-lg transition-all hover:shadow-xl hover:brightness-110"
                   style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                    Passer au plan supérieur
                </a>
            @else
                <button x-data @click="$dispatch('open-employe')" class="btn-primary group">
                    <svg class="w-4 h-4 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Ajouter un employé
                </button>
            @endif
        </div>

        @if($limitAtteinte)
            <div class="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-800">
                <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.41 0z"/></svg>
                <span>Limite atteinte — votre plan autorise <strong>{{ $maxEmployes }} employé(s)</strong>. <a href="{{ route('abonnement.plans') }}" class="underline font-semibold">Passez au plan supérieur</a> pour en ajouter davantage.</span>
            </div>
        @endif

        @if($employes->count() > 0)
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Employé</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 hidden sm:table-cell">Email</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 hidden md:table-cell">Rôle</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600">Statut</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($employes as $employe)
                        <tr class="hover:bg-gray-50 group transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 bg-gradient-to-br from-primary-400 to-secondary-400 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                        {{ strtoupper(substr($employe->prenom ?? $employe->name, 0, 1)) }}{{ strtoupper(substr($employe->nom_famille ?? '', 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ $employe->nom_complet }}</p>
                                        @if($employe->telephone)
                                            <p class="text-xs text-gray-400">{{ $employe->telephone }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-500 hidden sm:table-cell">{{ $employe->email }}</td>
                            <td class="px-4 py-3 hidden md:table-cell">
                                <span class="badge {{ $employe->role === 'admin' ? 'badge-primary' : 'badge-secondary' }} text-xs">
                                    {{ $employe->role === 'admin' ? 'Admin' : 'Employé' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="badge {{ $employe->actif ? 'badge-success' : 'bg-gray-100 text-gray-500' }} text-xs">
                                    {{ $employe->actif ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button x-data @click="$dispatch('edit-employe', {
                                        id: '{{ $employe->id }}',
                                        prenom: '{{ addslashes($employe->prenom ?? '') }}',
                                        nom_famille: '{{ addslashes($employe->nom_famille ?? '') }}',
                                        email: '{{ addslashes($employe->email) }}',
                                        telephone: '{{ addslashes($employe->telephone ?? '') }}',
                                        role: '{{ $employe->role }}'
                                    })" class="btn-icon" title="Modifier">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <form method="POST" action="{{ route('dashboard.employes.toggle', $employe) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn-icon {{ $employe->actif ? 'text-amber-500' : 'text-emerald-500' }}"
                                                title="{{ $employe->actif ? 'Désactiver' : 'Activer' }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $employe->actif ? 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' }}"/>
                                            </svg>
                                        </button>
                                    </form>
                                    <form id="delete-employe-{{ $employe->id }}" method="POST" action="{{ route('dashboard.employes.destroy', $employe) }}">
                                        @csrf @method('DELETE')
                                    </form>
                                    <button x-data @click="$dispatch('confirm-delete', { formId: 'delete-employe-{{ $employe->id }}', title: 'Supprimer cet employé ?', message: '{{ addslashes($employe->nom_complet) }} sera définitivement supprimé(e).' })" class="btn-icon text-red-400 hover:text-red-600" title="Supprimer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <div class="card p-12 text-center">
            <div class="text-4xl mb-3">👩‍💼</div>
            <p class="font-semibold text-gray-900 mb-1">Aucun employé</p>
            <p class="text-sm text-gray-500 mb-4">Ajoutez des membres à votre équipe.</p>
            @if(!$limitAtteinte)
                <button x-data @click="$dispatch('open-employe')" class="btn-primary">Ajouter un employé</button>
            @endif
        </div>
        @endif
    </div>

    {{-- Modal Employé (création / édition) --}}
    <div x-data="{
            show: false,
            isEdit: false,
            formAction: '{{ route('dashboard.employes.store') }}',
            form: { prenom: '', nom_famille: '', email: '', telephone: '', role: 'employe', password: '', password_confirmation: '' },
            resetForm() {
                this.isEdit = false;
                this.formAction = '{{ route('dashboard.employes.store') }}';
                this.form = { prenom: '', nom_famille: '', email: '', telephone: '', role: 'employe', password: '', password_confirmation: '' };
            },
            init() {
                window.addEventListener('open-employe', () => { this.resetForm(); this.show = true; });
                window.addEventListener('edit-employe', (e) => {
                    this.isEdit = true;
                    this.formAction = '{{ url('dashboard/employes') }}/' + e.detail.id;
                    this.form = {
                        prenom: e.detail.prenom,
                        nom_famille: e.detail.nom_famille,
                        email: e.detail.email,
                        telephone: e.detail.telephone || '',
                        role: e.detail.role,
                        password: '',
                        password_confirmation: ''
                    };
                    this.show = true;
                });
            }
         }"
         x-show="show"
         x-cloak
         class="modal-backdrop"
         @keydown.escape.window="show = false">
        <div class="modal max-w-lg" x-transition @click.stop>
            <div class="modal-header">
                <h3 class="modal-title" x-text="isEdit ? 'Modifier l\'employé' : 'Nouvel employé'"></h3>
                <button @click="show = false" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <form :action="formAction" method="POST" class="space-y-4">
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Prénom *</label>
                            <input type="text" name="prenom" required maxlength="80"
                                   x-model="form.prenom" class="form-input"
                                   placeholder="Marie">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nom *</label>
                            <input type="text" name="nom_famille" required maxlength="80"
                                   x-model="form.nom_famille" class="form-input"
                                   placeholder="Koné">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" required maxlength="150"
                               x-model="form.email" class="form-input"
                               placeholder="marie@votresalon.ci">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Téléphone</label>
                        <input type="tel" name="telephone" maxlength="20"
                               x-model="form.telephone" class="form-input" placeholder="+225...">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Rôle *</label>
                        <select name="role" required x-model="form.role" class="form-select">
                            <option value="employe">Employé (accès limité)</option>
                            <option value="admin">Admin (accès complet)</option>
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Les employés peuvent vendre et voir les stocks. Les admins gèrent tout.</p>
                    </div>

                    <template x-if="!isEdit">
                        <div class="space-y-4">
                            <div class="form-group">
                                <label class="form-label">Mot de passe initial *</label>
                                <input type="password" name="password" minlength="8"
                                       x-model="form.password" class="form-input"
                                       placeholder="Minimum 8 caractères" x-bind:required="!isEdit">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Confirmer le mot de passe *</label>
                                <input type="password" name="password_confirmation"
                                       x-model="form.password_confirmation" class="form-input" x-bind:required="!isEdit">
                            </div>
                        </div>
                    </template>

                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="show = false" class="btn btn-outline flex-1 justify-center">Annuler</button>
                        <button type="submit" class="btn-primary flex-1 justify-center">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-dashboard-layout>
