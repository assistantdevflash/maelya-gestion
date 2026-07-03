<x-dashboard-layout>
    <div class="space-y-4">
        <div>
            <div class="flex items-center justify-between gap-2">
                <h1 class="page-title">Caisse</h1>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <a href="{{ route('dashboard.caisse.brouillons.index') }}" class="btn-outline text-xs sm:text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        Brouillons
                    </a>
                    <a href="{{ route('dashboard.ventes.index') }}" class="btn-outline text-xs sm:text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Historique
                    </a>
                </div>
            </div>
            <p class="page-subtitle">Enregistrez rapidement vos ventes.</p>
        </div>

        {{-- Scanner QR carte fidélité --}}
        <div x-data="scannerFidelite()" x-init="init()" class="flex flex-wrap items-center gap-2">
            <button type="button" @click="open()" class="btn-outline text-xs sm:text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm14 0h2v2h-2v-2zm-4 0h2v2h-2v-2zm0 4h2v2h-2v-2zm4 0h2v2h-2v-2z"/>
                </svg>
                <span>Scanner carte fidélité</span>
            </button>
            <p x-show="status" x-text="status" class="text-xs"
               :class="error ? 'text-red-600' : 'text-emerald-600'"></p>

            {{-- Modal scan --}}
            <div x-show="modalOpen" x-cloak
                 class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4"
                 @keydown.escape.window="close()">
                <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-md w-full p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-gray-800 dark:text-slate-100">Scanner QR fidélité</h3>
                        <button @click="close()" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                    <template x-if="hasCamera">
                        <div>
                            <video x-ref="video" autoplay playsinline class="w-full rounded-lg bg-black"></video>
                            <p class="text-xs text-gray-500 mt-2">Placez le QR de la carte de fidélité face à la caméra.</p>
                        </div>
                    </template>
                    <template x-if="!hasCamera">
                        <p class="text-sm text-red-600">Votre navigateur ne supporte pas le scan de QR. Collez le lien ci-dessous.</p>
                    </template>
                    <div>
                        <label class="text-xs text-gray-500 dark:text-slate-400">Ou collez le lien / token</label>
                        <input x-model="manualInput" type="text" placeholder="https://.../carte/XXX ou token"
                               class="form-input mt-1 w-full">
                        <button @click="resolve(manualInput)" class="btn-primary w-full mt-2">Valider</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Message de succès après impression --}}
        @if(request('vente_ok'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 x-init="setTimeout(() => show = false, 5000)"
                 class="flex items-center gap-3 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 rounded-xl px-4 py-3 text-sm font-medium">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Vente enregistrée et ticket envoyé à l'impression.
                <button @click="show = false" class="ml-auto text-emerald-400 hover:text-emerald-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        {{-- Composant Livewire --}}
        <div
            x-data="{}"
            @valider-vente.window="
                const data = $event.detail[0];
                const form = document.getElementById('form-vente');
                document.getElementById('panier-json').value = JSON.stringify(data.panier);
                document.getElementById('client-id').value = data.client_id ?? '';
                document.getElementById('mode-paiement').value = data.mode_paiement;
                document.getElementById('reference-paiement').value = data.reference_paiement ?? '';
                document.getElementById('total').value = data.total;
                document.getElementById('remise').value = data.remise ?? 0;
                document.getElementById('code-reduction-id').value = data.code_reduction_id ?? '';
                document.getElementById('imprimer').value = data.imprimer ? '1' : '';
                document.getElementById('montant-cash').value = data.montant_cash ?? '';
                document.getElementById('montant-mobile').value = data.montant_mobile ?? '';
                document.getElementById('montant-carte').value = data.montant_carte ?? '';
                document.getElementById('pourboire').value = data.pourboire ?? 0;
                // Toujours soumettre dans la même fenêtre
                form.target = '_self';
                form.submit();
            "
        >
            @livewire('caisse', ['client' => request('client'), 'rdv' => request('rdv'), 'brouillon' => request('brouillon')])
        </div>

        {{-- Formulaire caché pour soumettre via POST classique --}}
        <form id="form-vente" method="POST" action="{{ route('dashboard.ventes.store') }}" class="hidden">
            @csrf
            <input type="hidden" id="panier-json" name="panier_json">
            <input type="hidden" id="client-id" name="client_id">
            <input type="hidden" id="mode-paiement" name="mode_paiement">
            <input type="hidden" id="reference-paiement" name="reference_paiement">
            <input type="hidden" id="total" name="total">
            <input type="hidden" id="remise" name="remise" value="0">
            <input type="hidden" id="code-reduction-id" name="code_reduction_id">
            <input type="hidden" id="imprimer" name="imprimer">
            <input type="hidden" id="montant-cash" name="montant_cash">
            <input type="hidden" id="montant-mobile" name="montant_mobile">
            <input type="hidden" id="montant-carte" name="montant_carte">
            <input type="hidden" id="pourboire" name="pourboire" value="0">
        </form>
    </div>

    <script>
    function scannerFidelite() {
        return {
            modalOpen: false,
            hasCamera: false,
            manualInput: '',
            status: '',
            error: false,
            stream: null,
            detector: null,
            scanInterval: null,
            init() {
                // Vérification lazy — on ne bloque pas sur l'état init
            },
            async open() {
                this.modalOpen = true;
                this.status = '';
                this.error = false;
                this.manualInput = '';
                // Vérifie la disponibilité au moment de l'ouverture
                this.hasCamera = 'BarcodeDetector' in window && !!navigator.mediaDevices?.getUserMedia;
                if (!this.hasCamera) return;
                try {
                    this.detector = new BarcodeDetector({ formats: ['qr_code'] });
                    this.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                    await this.$nextTick();
                    this.$refs.video.srcObject = this.stream;
                    this.scanInterval = setInterval(() => this.scan(), 600);
                } catch (e) {
                    this.hasCamera = false;
                    this.status = "Caméra inaccessible : " + e.message;
                    this.error = true;
                }
            },
            async scan() {
                if (!this.$refs.video || this.$refs.video.readyState < 2) return;
                try {
                    const codes = await this.detector.detect(this.$refs.video);
                    if (codes.length) {
                        this.resolve(codes[0].rawValue);
                    }
                } catch (e) {}
            },
            close() {
                this.modalOpen = false;
                if (this.scanInterval) { clearInterval(this.scanInterval); this.scanInterval = null; }
                if (this.stream) { this.stream.getTracks().forEach(t => t.stop()); this.stream = null; }
            },
            async resolve(value) {
                value = (value || '').trim();
                if (!value) return;
                this.status = 'Recherche...';
                this.error = false;
                try {
                    const url = "{{ route('dashboard.clients.fidelite.recherche') }}?token=" + encodeURIComponent(value);
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) {
                        this.status = 'Aucun client trouvé pour ce QR.';
                        this.error = true;
                        this.close();
                        return;
                    }
                    const data = await res.json();
                    if (data.found) {
                        this.status = 'Client : ' + data.nom + ' (' + data.points + ' pts)';
                        this.error = false;
                        // Notifie le composant Livewire Caisse
                        Livewire.dispatch('client-scanne-qr', { id: String(data.id) });
                        this.close();
                    } else {
                        this.status = 'Aucun client trouvé.';
                        this.error = true;
                        this.close();
                    }
                } catch (e) {
                    this.status = 'Erreur : ' + e.message;
                    this.error = true;
                    this.close();
                }
            }
        }
    }

    // Détecter si on doit lancer l'impression automatiquement
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const printVenteId = urlParams.get('print');
        
        if (printVenteId) {
            // Construire l'URL du ticket PDF
            const ticketUrl = '{{ route('dashboard.ventes.ticket-pdf', ':id') }}'.replace(':id', printVenteId);
            
            // Lancer l'impression automatiquement
            setTimeout(function() {
                printPDF(ticketUrl);
            }, 500); // Petit délai pour laisser la page se charger complètement
            
            // Nettoyer l'URL (retirer le paramètre print)
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    });
    </script>
</x-dashboard-layout>
