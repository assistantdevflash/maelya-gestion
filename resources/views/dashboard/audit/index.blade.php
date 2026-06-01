<x-dashboard-layout>
    <x-slot name="title">Journal d'activité</x-slot>

    @php
        $actionLabels = [
            'created'   => ['label' => 'Création',     'class' => 'badge-success'],
            'updated'   => ['label' => 'Modification',  'class' => 'badge-info'],
            'deleted'   => ['label' => 'Suppression',   'class' => 'badge-danger'],
            'login'     => ['label' => 'Connexion',     'class' => 'badge-primary'],
            'logout'    => ['label' => 'Déconnexion',   'class' => 'badge-gray'],
            'validated' => ['label' => 'Validation',    'class' => 'badge-success'],
            'cancelled' => ['label' => 'Annulation',    'class' => 'badge-warning'],
            'sent'      => ['label' => 'Envoi',         'class' => 'badge-primary'],
            'paid'      => ['label' => 'Paiement',      'class' => 'badge-success'],
            'restored'  => ['label' => 'Restauration',  'class' => 'badge-warning'],
        ];
        $modelLabels = [
            'Vente'         => 'Vente',
            'Client'        => 'Client',
            'Rdv'           => 'Rendez-vous',
            'Prestation'    => 'Prestation',
            'Produit'       => 'Produit',
            'Abonnement'    => 'Abonnement',
            'Employe'       => 'Employé',
            'Institut'      => 'Institut',
            'User'          => 'Utilisateur',
            'BonCommande'   => 'Bon de commande',
            'Inventaire'    => 'Inventaire',
            'Depense'       => 'Dépense',
            'CodeReduction' => 'Code promo',
            'Fournisseur'   => 'Fournisseur',
            'PlanFidelite'  => 'Programme fidélité',
            'PointFinancier'=> 'Point financier',
            'CategoriePrestation' => 'Catégorie prestation',
            'CategorieProduit'    => 'Catégorie produit',
        ];
        $fieldLabels = [
            'statut'           => 'Statut',
            'montant'          => 'Montant',
            'total'            => 'Total',
            'nom'              => 'Nom',
            'prenom'           => 'Prénom',
            'nom_famille'      => 'Nom de famille',
            'email'            => 'E-mail',
            'telephone'        => 'Téléphone',
            'date'             => 'Date',
            'heure'            => 'Heure',
            'heure_debut'      => 'Heure de début',
            'heure_fin'        => 'Heure de fin',
            'notes'            => 'Notes',
            'motif_annulation' => 'Motif d\'annulation',
            'annulee_le'       => 'Annulée le',
            'annulee_par'      => 'Annulée par',
            'mode_paiement'    => 'Mode de paiement',
            'remise'           => 'Remise',
            'quantite'         => 'Quantité',
            'prix_unitaire'    => 'Prix unitaire',
            'prix'             => 'Prix',
            'duree'            => 'Durée (min)',
            'actif'            => 'Actif',
            'validated_at'     => 'Validé le',
            'created_at'       => 'Créé le',
            'updated_at'       => 'Modifié le',
            'deleted_at'       => 'Supprimé le',
            'expire_le'        => 'Expire le',
            'debut_le'         => 'Début le',
            'numero'           => 'Numéro',
            'titre'            => 'Titre',
            'description'      => 'Description',
            'adresse'          => 'Adresse',
            'type'             => 'Type',
            'categorie_id'     => 'Catégorie',
            'employe_id'       => 'Employé assigné',
            'client_id'        => 'Client',
            'institut_id'      => 'Institut',
            'password'         => '(mot de passe)',
            'remember_token'   => '(token session)',
            'two_factor_secret'=> '(2FA secret)',
        ];
        $hasFilter = request()->hasAny(['action', 'subject_type', 'q']);
    @endphp

    <div class="space-y-4" x-data="{
        submitForm(form) { form.submit(); }
    }">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-display font-bold text-gray-900">Journal d'activité</h1>
                <p class="text-sm text-gray-500 mt-1">Toutes les actions sensibles sur votre institut</p>
            </div>
            @if($hasFilter)
                <a href="{{ route('dashboard.audit.index') }}" class="btn-outline text-sm flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Effacer les filtres
                </a>
            @endif
        </div>

        <form method="GET" id="audit-filter-form" class="card p-4 grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="form-label">Action</label>
                <select name="action" class="form-select" onchange="document.getElementById('audit-filter-form').submit()">
                    <option value="">Toutes les actions</option>
                    @foreach($actions as $a)
                        <option value="{{ $a }}" @selected(request('action') === $a)>
                            {{ $actionLabels[$a]['label'] ?? ucfirst($a) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Type d'objet</label>
                <select name="subject_type" class="form-select" onchange="document.getElementById('audit-filter-form').submit()">
                    <option value="">Tous les types</option>
                    @foreach($subjects as $s)
                        <option value="{{ $s }}" @selected(request('subject_type') === $s)>
                            {{ $modelLabels[class_basename($s)] ?? class_basename($s) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div x-data="{ q: '{{ request('q') }}', timer: null }">
                <label class="form-label">Recherche</label>
                <input type="text" name="q" x-model="q"
                       @input="clearTimeout(timer); timer = setTimeout(() => $el.form.submit(), 600)"
                       placeholder="Libellé ou identifiant…"
                       class="form-input">
            </div>
        </form>

        <div class="card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Date</th>
                        <th class="px-4 py-3 text-left font-semibold">Utilisateur</th>
                        <th class="px-4 py-3 text-left font-semibold">Action</th>
                        <th class="px-4 py-3 text-left font-semibold">Objet concerné</th>
                        <th class="px-4 py-3 text-left font-semibold">Modifications</th>
                        <th class="px-4 py-3 text-left font-semibold">Adresse IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                        @php
                            $actionInfo = $actionLabels[$log->action] ?? ['label' => ucfirst($log->action), 'class' => 'badge-primary'];
                            $modelName  = $modelLabels[class_basename($log->subject_type ?? '')] ?? class_basename($log->subject_type ?? '');
                            $changes    = $log->changes;
                            $hasOldNew  = is_array($changes) && isset($changes['old'], $changes['new']);
                            $skipFields = ['created_at', 'updated_at', 'deleted_at', 'remember_token', 'two_factor_secret'];
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500">
                                {{ $log->created_at->format('d/m/Y') }}<br>
                                <span class="text-gray-400">{{ $log->created_at->format('H:i:s') }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs font-medium text-gray-700">
                                @if($log->user)
                                    {{ $log->user->prenom }} {{ $log->user->nom_famille }}
                                @else
                                    <span class="text-gray-400 italic">Système</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge {{ $actionInfo['class'] }}">{{ $actionInfo['label'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @if($modelName)
                                    <span class="font-medium text-gray-700">{{ $modelName }}</span>
                                @endif
                                @if($log->label)
                                    <br><span class="text-gray-500">{{ $log->label }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs max-w-sm">
                                @if($changes)
                                    <details>
                                        <summary class="cursor-pointer text-primary-600 hover:text-primary-700 font-medium select-none">
                                            ▶ Voir les détails
                                        </summary>
                                        <div class="mt-2 space-y-1.5 bg-gray-50 rounded-lg p-2.5 border border-gray-100">
                                            @if($hasOldNew)
                                                @php
                                                    $changedKeys = collect($changes['new'])
                                                        ->filter(fn($v, $k) => !in_array($k, $skipFields) && $changes['old'][$k] !== $v)
                                                        ->keys();
                                                @endphp
                                                @forelse($changedKeys as $key)
                                                    @php
                                                        $oldVal = $changes['old'][$key] ?? null;
                                                        $newVal = $changes['new'][$key] ?? null;
                                                        $label  = $fieldLabels[$key] ?? str_replace('_', ' ', ucfirst($key));
                                                        $fmt = fn($v) => is_bool($v) ? ($v ? 'Oui' : 'Non')
                                                            : (is_null($v) ? '—'
                                                            : (is_array($v) ? implode(', ', $v) : $v));
                                                    @endphp
                                                    <div class="flex items-start gap-1.5 text-[11px]">
                                                        <span class="text-gray-400 shrink-0 w-24 truncate" title="{{ $label }}">{{ $label }}</span>
                                                        <span class="text-red-400 line-through shrink-0 max-w-[80px] truncate" title="{{ $fmt($oldVal) }}">{{ $fmt($oldVal) }}</span>
                                                        <span class="text-gray-300">→</span>
                                                        <span class="text-emerald-600 font-medium shrink-0 max-w-[80px] truncate" title="{{ $fmt($newVal) }}">{{ $fmt($newVal) }}</span>
                                                    </div>
                                                @empty
                                                    <p class="text-[11px] text-gray-400 italic">Aucun champ modifié détecté</p>
                                                @endforelse
                                            @else
                                                @foreach($changes as $key => $val)
                                                    @if(!in_array($key, $skipFields))
                                                        @php
                                                            $label = $fieldLabels[$key] ?? str_replace('_', ' ', ucfirst($key));
                                                            $display = is_bool($val) ? ($val ? 'Oui' : 'Non')
                                                                : (is_null($val) ? '—'
                                                                : (is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : $val));
                                                        @endphp
                                                        <div class="flex gap-1.5 text-[11px]">
                                                            <span class="text-gray-400 shrink-0 w-24 truncate">{{ $label }}</span>
                                                            <span class="text-gray-700 font-medium truncate max-w-[140px]" title="{{ $display }}">{{ $display }}</span>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            @endif
                                        </div>
                                    </details>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-[11px] text-gray-400 font-mono">{{ $log->ip_address ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center">
                                <div class="text-gray-300 mb-2">
                                    <svg class="w-10 h-10 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <p class="text-sm text-gray-400">Aucune activité enregistrée</p>
                                @if($hasFilter)
                                    <a href="{{ route('dashboard.audit.index') }}" class="text-xs text-primary-500 hover:underline mt-1 inline-block">Effacer les filtres</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between">
            <p class="text-xs text-gray-400">
                {{ $logs->total() }} entrée{{ $logs->total() > 1 ? 's' : '' }} au total
            </p>
            {{ $logs->links() }}
        </div>
    </div>
</x-dashboard-layout>
