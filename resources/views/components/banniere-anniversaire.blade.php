{{--
  Bannière anniversaire dismissable.
  Usage: <x-banniere-anniversaire :clients="$anniversairesAujourdhui" />
  Paramètres :
    - $clients : Collection de clients fêtant leur anniversaire
--}}
@props(['clients'])

@if($clients->count() > 0)
<div
    x-data="{
        open: true,
        dismissed: [],
        giftClient: null,
        giftType: 'pourcentage',
        giftValeur: 15,
        init() {
            const key = 'anniv_dismissed_{{ now()->format('Y-m-d') }}';
            const stored = JSON.parse(localStorage.getItem(key) || '[]');
            this.dismissed = stored;
        },
        isDismissed(id) { return this.dismissed.includes(id); },
        dismiss(id) {
            this.dismissed.push(id);
            const key = 'anniv_dismissed_{{ now()->format('Y-m-d') }}';
            localStorage.setItem(key, JSON.stringify(this.dismissed));
        },
        allDismissed() {
            return @js($clients->pluck('id')->values()).every(id => this.dismissed.includes(id));
        }
    }"
    x-show="!allDismissed()"
    x-cloak
    class="space-y-2 mb-5"
>
    @foreach($clients as $client)
    <div
        x-show="!isDismissed('{{ $client->id }}')"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="flex items-center gap-3 px-4 py-3 rounded-2xl border border-pink-200 dark:border-pink-800/30 bg-gradient-to-r from-pink-50 to-violet-50 dark:from-pink-950/40 dark:to-violet-950/40 shadow-sm"
    >
        <span class="text-2xl flex-shrink-0">🎂</span>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-bold text-gray-900 dark:text-gray-100">
                Anniversaire de <a href="{{ route('dashboard.clients.show', $client) }}" class="text-primary-600 dark:text-primary-400 hover:underline">{{ $client->nom_complet }}</a> !
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Aujourd'hui, {{ now()->isoFormat('D MMMM') }}</p>
        </div>
        <button
            @click="giftClient = '{{ $client->id }}'; giftClientNom = '{{ addslashes($client->nom_complet) }}'"
            class="flex-shrink-0 px-3 py-1.5 text-xs font-bold rounded-xl text-white transition-all hover:shadow-md"
            style="background: linear-gradient(135deg, #9333ea, #ec4899);"
        >
            🎁 Offrir un cadeau
        </button>
        <button @click="dismiss('{{ $client->id }}')" class="flex-shrink-0 p-1.5 rounded-lg text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-white/60 dark:hover:bg-white/10 transition-all" title="Fermer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
    @endforeach

    {{-- Modal Offrir un cadeau --}}
    <template x-if="giftClient">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background: rgba(0,0,0,0.4);"
         @click.self="giftClient = null">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4" @click.stop>
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-900">🎁 Cadeau d'anniversaire</h3>
                <button @click="giftClient = null" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <p class="text-sm text-gray-500">
                Créer un code de réduction d'anniversaire pour <span class="font-bold text-gray-900" x-text="giftClientNom"></span>.
                Valable 30 jours, usage unique.
            </p>

            <template x-for="client in @js($clients->values())" :key="client.id">
            <form :action="'/dashboard/clients/' + giftClient + '/cadeau-anniversaire'"
                  x-show="giftClient === client.id"
                  method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Type de remise</label>
                        <select name="type" x-model="giftType" class="form-input">
                            <option value="pourcentage">Pourcentage (%)</option>
                            <option value="montant_fixe">Montant fixe (F)</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">
                            Valeur <span x-text="giftType === 'pourcentage' ? '%' : 'FCFA'"></span>
                        </label>
                        <input type="number" name="valeur" x-model.number="giftValeur"
                               min="1" :max="giftType === 'pourcentage' ? 100 : 999999"
                               class="form-input" required>
                    </div>
                </div>
                <button type="submit"
                        class="w-full py-3 text-sm font-bold rounded-xl text-white transition-all hover:shadow-lg"
                        style="background: linear-gradient(135deg, #9333ea, #ec4899);">
                    🎁 Créer le code cadeau
                </button>
            </form>
            </template>
        </div>
    </div>
    </template>
</div>
@endif
