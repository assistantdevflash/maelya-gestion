<x-dashboard-layout>
    <div class="space-y-5">

        {{-- En-tête --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-display font-bold text-gray-900 tracking-tight">Codes de réduction</h1>
                <p class="text-sm text-gray-500 mt-1">Créez des codes promo applicables à la caisse.</p>
            </div>
            <button x-data @click="$dispatch('open-code-modal')" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Créer un code
            </button>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="card p-5 flex items-center gap-4">
                <div class="w-10 h-10 rounded-xl bg-primary-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Total codes</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
                </div>
            </div>
            <div class="card p-5 flex items-center gap-4">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Codes actifs</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['actifs'] }}</p>
                </div>
            </div>
            <div class="card p-5 flex items-center gap-4">
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Utilisations totales</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['utilisations'] }}</p>
                </div>
            </div>
        </div>

        {{-- Liste des codes --}}
        @if($codes->count() > 0)
        <div class="card overflow-hidden">
            <div class="divide-y divide-gray-50">
                @foreach($codes as $code)
                @php $statut = $code->statut(); @endphp
                <div class="flex items-center gap-4 px-5 py-4 hover:bg-gray-50/50 transition-colors group">
                    {{-- Icône type --}}
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 font-bold text-sm"
                         style="background: linear-gradient(135deg, rgba(147,51,234,0.1), rgba(236,72,153,0.1)); color: #9333ea;">
                        {{ $code->type === 'pourcentage' ? '%' : '₣' }}
                    </div>

                    {{-- Infos --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-bold text-gray-900 font-mono tracking-wide">{{ $code->code }}</span>
                            {{-- Badge statut --}}
                            @if($statut === 'actif')
                                <span class="badge badge-success text-xs">Actif</span>
                            @elseif($statut === 'epuise')
                                <span class="badge badge-warning text-xs">Épuisé</span>
                            @elseif($statut === 'expire')
                                <span class="badge badge-danger text-xs">Expiré</span>
                            @else
                                <span class="badge text-xs bg-gray-100 text-gray-500">Inactif</span>
                            @endif
                            {{-- Valeur --}}
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                                  style="background: rgba(147,51,234,0.08); color: #9333ea;">
                                {{ $code->type === 'pourcentage' ? '-'.$code->valeur.'%' : '-'.number_format($code->valeur, 0, ',', ' ').' F' }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5">
                            @if($code->client)
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-primary-600 mr-1.5">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    {{ $code->client->prenom }} {{ $code->client->nom }}
                                </span>&nbsp;·&nbsp;
                            @endif
                            @if($code->montant_minimum)
                                Min. {{ number_format($code->montant_minimum, 0, ',', ' ') }} FCFA &nbsp;&middot;&nbsp;
                            @endif
                            {{ $code->nb_utilisations }} utilisation{{ $code->nb_utilisations > 1 ? 's' : '' }}
                            @if($code->limite_utilisation)
                                / {{ $code->limite_utilisation }}
                            @endif
                            @if($code->date_fin)
                                &nbsp;&middot;&nbsp; Expire le {{ $code->date_fin->format('d/m/Y') }}
                            @endif
                        </p>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2">
                        {{-- Copier --}}
                        <button type="button" x-data="{ copied: false }" @click="navigator.clipboard.writeText('{{ $code->code }}'); copied = true; setTimeout(() => copied = false, 1500)"
                                class="btn-icon text-gray-400 hover:text-primary-600" title="Copier le code">
                            <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            <svg x-show="copied" x-cloak class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </button>
                        {{-- Imprimer --}}
                        <button type="button" @click="$dispatch('print-code', {
                            code: '{{ $code->code }}',
                            type: '{{ $code->type }}',
                            valeur: '{{ $code->type === "pourcentage" ? $code->valeur."%" : number_format($code->valeur, 0, ",", " ")." FCFA" }}',
                            client: '{{ $code->client ? $code->client->prenom." ".$code->client->nom : "Tous les clients" }}',
                            montant_minimum: '{{ $code->montant_minimum ? number_format($code->montant_minimum, 0, ",", " ")." FCFA" : "Aucun" }}',
                            date_debut: '{{ $code->date_debut ? $code->date_debut->format("d/m/Y") : "-" }}',
                            date_fin: '{{ $code->date_fin ? $code->date_fin->format("d/m/Y") : "Illimitée" }}',
                            limite: '{{ $code->limite_utilisation ?? "Illimitée" }}',
                            description: '{{ addslashes($code->description ?? "") }}'
                        })" class="btn-icon text-gray-400 hover:text-primary-600" title="Imprimer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                        </button>
                        {{-- Toggle actif --}}
                        <form method="POST" action="{{ route('dashboard.codes-reduction.toggle', $code) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn-icon {{ $code->actif ? 'text-emerald-500' : 'text-gray-300' }}"
                                    title="{{ $code->actif ? 'Désactiver' : 'Activer' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </form>
                        {{-- Supprimer --}}
                        <form id="del-code-{{ $code->id }}" method="POST" action="{{ route('dashboard.codes-reduction.destroy', $code) }}">
                            @csrf @method('DELETE')
                        </form>
                        <button x-data @click="$dispatch('confirm-delete', { formId: 'del-code-{{ $code->id }}', title: 'Supprimer ce code ?', message: 'Le code {{ $code->code }} sera définitivement supprimé.' })"
                                class="btn-icon text-red-400 hover:text-red-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @else
        <div class="card p-12 text-center">
            <div class="w-14 h-14 rounded-2xl bg-primary-50 flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
            </div>
            <p class="font-semibold text-gray-900 mb-1">Aucun code de réduction</p>
            <p class="text-sm text-gray-500 mb-4">Créez des codes promo pour fidéliser vos clientes.</p>
            <button x-data @click="$dispatch('open-code-modal')" class="btn-primary">Créer un code</button>
        </div>
        @endif
    </div>

    {{-- ═══ Modal création ═══ --}}
    <div x-data="{
            show: false,
            type: 'pourcentage',
            init() { window.addEventListener('open-code-modal', () => { this.show = true; }); }
         }"
         x-show="show"
         x-cloak
         class="modal-backdrop"
         @keydown.escape.window="show = false">
        <div class="modal max-w-xl" x-transition @click.stop>
            <div class="modal-header">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, rgba(147,51,234,0.1), rgba(236,72,153,0.1));">
                        <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                    </div>
                    <h3 class="modal-title">Nouveau code de réduction</h3>
                </div>
                <button @click="show = false" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('dashboard.codes-reduction.store') }}" class="space-y-4">
                    @csrf

                    {{-- Ligne 1 : Code + Type --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div class="form-group mb-0" x-data="{
                            generateCode() {
                                const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
                                let result = '';
                                for (let i = 0; i < 8; i++) result += chars.charAt(Math.floor(Math.random() * chars.length));
                                this.$refs.codeInput.value = result;
                            }
                        }">
                            <label class="form-label">Code *</label>
                            <div class="flex gap-2">
                                <input type="text" name="code" required maxlength="50"
                                       x-ref="codeInput"
                                       class="form-input font-mono uppercase tracking-widest flex-1"
                                       placeholder="Ex: BIENVENUE10"
                                       oninput="this.value=this.value.toUpperCase()"
                                       value="{{ old('code') }}">
                                <button type="button" @click="generateCode()"
                                        class="px-3 py-2 rounded-xl border-2 border-gray-200 hover:border-primary-400 hover:bg-primary-50 text-gray-500 hover:text-primary-600 transition-all flex-shrink-0"
                                        title="Générer un code aléatoire">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                </button>
                            </div>
                            @error('code')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Type *</label>
                            <div class="flex gap-2 mt-0.5">
                                <button type="button" @click="type = 'pourcentage'"
                                        :class="type === 'pourcentage' ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-gray-200 text-gray-500'"
                                        class="flex-1 py-2 px-2 rounded-xl text-xs font-semibold border-2 transition-all text-center">
                                    % Pourcent.
                                </button>
                                <button type="button" @click="type = 'montant_fixe'"
                                        :class="type === 'montant_fixe' ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-gray-200 text-gray-500'"
                                        class="flex-1 py-2 px-2 rounded-xl text-xs font-semibold border-2 transition-all text-center">
                                    ₣ Fixe
                                </button>
                            </div>
                            <input type="hidden" name="type" :value="type">
                        </div>
                    </div>

                    {{-- Ligne 2 : Valeur + Montant minimum --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div class="form-group mb-0">
                            <label class="form-label">
                                Valeur *
                                <span class="text-gray-400 font-normal text-xs" x-text="type === 'pourcentage' ? '(1–100%)' : '(FCFA)'"></span>
                            </label>
                            <input type="number" name="valeur" required min="1"
                                   :max="type === 'pourcentage' ? 100 : 9999999"
                                   class="form-input"
                                   placeholder="Ex: 15"
                                   value="{{ old('valeur') }}">
                            @error('valeur')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Montant minimum <span class="text-gray-400 font-normal text-xs">(optionnel)</span></label>
                            <input type="number" name="montant_minimum" min="0" step="500" class="form-input"
                                   placeholder="Ex: 5000" value="{{ old('montant_minimum') }}">
                        </div>
                    </div>

                    {{-- Ligne 3 : Dates --}}
                    <div class="grid grid-cols-2 gap-3" x-data="{ date_debut: '{{ old('date_debut') }}' }">
                        <div class="form-group mb-0">
                            <label class="form-label">Date de début <span class="text-gray-400 font-normal text-xs">(optionnel)</span></label>
                            <input type="date" name="date_debut" x-model="date_debut" class="form-input" value="{{ old('date_debut') }}">
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Date de fin <span class="text-gray-400 font-normal text-xs">(optionnel)</span></label>
                            <input type="date" name="date_fin" :min="date_debut" class="form-input" value="{{ old('date_fin') }}">
                        </div>
                    </div>

                    {{-- Ligne 4 : Client + Limite --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div class="form-group mb-0">
                            <label class="form-label">Client <span class="text-gray-400 font-normal text-xs">(optionnel)</span></label>
                            <select name="client_id" class="form-select">
                                <option value="">Tous les clients</option>
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}" {{ old('client_id') === $c->id ? 'selected' : '' }}>
                                        {{ $c->prenom }} {{ $c->nom }}
                                    </option>
                                @endforeach
                            </select>
                            @error('client_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Limite d'utilisation <span class="text-gray-400 font-normal text-xs">(optionnel)</span></label>
                            <input type="number" name="limite_utilisation" min="1" class="form-input"
                                   placeholder="Illimité si vide" value="{{ old('limite_utilisation') }}">
                        </div>
                    </div>

                    {{-- Description pleine largeur --}}
                    <div class="form-group mb-0">
                        <label class="form-label">Description <span class="text-gray-400 font-normal text-xs">(optionnel)</span></label>
                        <input type="text" name="description" maxlength="255" class="form-input"
                               placeholder="Ex: Réduction de bienvenue pour nouvelles clientes" value="{{ old('description') }}">
                    </div>

                    <div class="flex gap-3 pt-1">
                        <button type="button" @click="show = false" class="btn btn-outline flex-1 justify-center">Annuler</button>
                        <button type="submit" class="btn-primary flex-1 justify-center">Créer le code</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ═══ Zone d'impression (cachée) ═══ --}}
    <div x-data x-on:print-code.window="
        const d = $event.detail;
        const el = $refs.printArea;
        el.querySelector('.print-code').textContent = d.code;
        el.querySelector('.print-valeur').textContent = d.type === 'pourcentage' ? '-' + d.valeur : '-' + d.valeur;
        el.querySelector('.print-client').textContent = d.client;
        el.querySelector('.print-minimum').textContent = d.montant_minimum;
        el.querySelector('.print-debut').textContent = d.date_debut;
        el.querySelector('.print-fin').textContent = d.date_fin;
        el.querySelector('.print-limite').textContent = d.limite;
        el.querySelector('.print-description').textContent = d.description || '—';
        const pw = window.open('', '_blank', 'width=320,height=500');
        pw.document.write(el.innerHTML);
        pw.document.close();
        pw.focus();
        pw.print();
        pw.close();
    ">
        <template x-ref="printArea">
            <html>
            <head>
                <style>
                    * { margin: 0; padding: 0; box-sizing: border-box; }
                    body { font-family: 'Courier New', monospace; width: 280px; padding: 15px; font-size: 12px; color: #111; }
                    .header { text-align: center; border-bottom: 2px dashed #999; padding-bottom: 10px; margin-bottom: 10px; }
                    .header h1 { font-size: 16px; margin-bottom: 2px; }
                    .header p { font-size: 10px; color: #666; }
                    .code-box { text-align: center; margin: 12px 0; padding: 10px; border: 2px dashed #9333ea; border-radius: 8px; }
                    .code-box .print-code { font-size: 24px; font-weight: bold; letter-spacing: 3px; color: #9333ea; }
                    .code-box .print-valeur { display: block; font-size: 18px; font-weight: bold; margin-top: 4px; color: #ec4899; }
                    .details { margin: 10px 0; }
                    .details .row { display: flex; justify-content: space-between; padding: 3px 0; border-bottom: 1px dotted #ddd; font-size: 11px; }
                    .details .row .label { color: #666; }
                    .details .row .value { font-weight: bold; text-align: right; max-width: 55%; }
                    .desc { margin-top: 8px; padding: 6px; background: #f9f9f9; border-radius: 4px; font-size: 10px; color: #555; text-align: center; font-style: italic; }
                    .footer { text-align: center; margin-top: 12px; padding-top: 8px; border-top: 2px dashed #999; font-size: 9px; color: #999; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>Maëlya Gestion</h1>
                    <p>Code de réduction</p>
                </div>
                <div class="code-box">
                    <span class="print-code"></span>
                    <span class="print-valeur"></span>
                </div>
                <div class="details">
                    <div class="row"><span class="label">Client</span><span class="value print-client"></span></div>
                    <div class="row"><span class="label">Montant min.</span><span class="value print-minimum"></span></div>
                    <div class="row"><span class="label">Valide du</span><span class="value print-debut"></span></div>
                    <div class="row"><span class="label">Valide jusqu'au</span><span class="value print-fin"></span></div>
                    <div class="row"><span class="label">Utilisations max.</span><span class="value print-limite"></span></div>
                </div>
                <div class="desc">
                    <span class="print-description"></span>
                </div>
                <div class="footer">
                    <p>Présentez ce coupon lors de votre passage en caisse.</p>
                    <p style="margin-top: 3px;">Merci pour votre fidélité ❤</p>
                </div>
            </body>
            </html>
        </template>
    </div>

    @if($errors->any())
    <script>
        document.addEventListener('alpine:init', () => {
            setTimeout(() => window.dispatchEvent(new CustomEvent('open-code-modal')), 100);
        });
    </script>
    @endif
</x-dashboard-layout>
