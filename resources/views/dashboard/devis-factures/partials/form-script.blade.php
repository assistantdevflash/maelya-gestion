{{--
    Composant Alpine commun aux formulaires Devis et Factures.

    Il remplace les trois copies qui existaient (devisForm sur la création de
    devis, devisForm sur la création de facture, lignesManager sur l'édition) :
    un correctif sur les lignes n'était appliqué qu'à une partie d'entre elles.

    Points d'entrée :
      - clients     : liste des clients sélectionnables (null si non pertinent)
      - catalogue   : prestations + produits
      - initial     : données de pré-remplissage (duplication, vente, old input)
      - prixDisplay : affiche le prix formaté pendant la saisie
      - cle         : identifiant du brouillon automatique (localStorage)

    Le brouillon est sauvegardé pendant la frappe et effacé à l'envoi du
    formulaire ; il est ignoré au-delà de 24 h.
--}}
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('documentForm', (config = {}) => ({
        // ─── Configuration ────────────────────────────────────────────
        cle: config.cle || 'document-form',
        prixDisplay: config.prixDisplay !== false,
        clientsList: config.clients || [],
        catalogue: config.catalogue || [],
        brouillonRestaure: false,

        // ─── Lignes ──────────────────────────────────────────────────
        lignes: [],
        tva: config.initial?.tva_taux ?? 0,
        tvaApplicable: !!config.initial?.tva_applicable,
        remiseGlobaleType: config.initial?.remise_globale_type || '',
        remiseGlobaleValeur: config.initial?.remise_globale_valeur || 0,

        /** Nouvelle ligne vierge. */
        ligneVide() {
            return {
                designation: '', quantite: 1, prix_unitaire: 0, prix_display: '0',
                remise_type: '', remise_valeur: 0, tva_taux: null,
                pickerOpen: false, pickerSearch: '',
                produit_id: null, prestation_id: null,
            };
        },

        /** Ajoute une ligne vierge à la fin. */
        ajouterLigne() {
            this.lignes.push(this.ligneVide());
        },

        /** Normalise une ligne venue du serveur (duplication, ancienne saisie). */
        ligneDepuis(donnees) {
            return Object.assign(this.ligneVide(), donnees, {
                prix_display: this.formatPrixInput(donnees.prix_unitaire || 0),
                pickerOpen: false,
                pickerSearch: '',
            });
        },

        init() {
            const source = config.initial || this.lireBrouillon();

            if (source && Array.isArray(source.lignes) && source.lignes.length) {
                this.lignes = source.lignes.map(l => this.ligneDepuis(l));
                this.brouillonRestaure = !config.initial;
            } else {
                this.lignes = [this.ligneVide()];
            }

            if (source) {
                if (source.tva_applicable !== undefined) this.tvaApplicable = !!source.tva_applicable;
                if (source.tva_taux !== undefined && source.tva_taux !== null) this.tva = Number(source.tva_taux);
                if (source.remise_globale_type) this.remiseGlobaleType = source.remise_globale_type;
                if (source.remise_globale_valeur) this.remiseGlobaleValeur = Number(source.remise_globale_valeur);
            }

            // Sauvegarde pendant la frappe
            this.$watch('lignes', () => this.sauvegarderBrouillon());
            this.$watch('tvaApplicable', () => this.sauvegarderBrouillon());
            this.$watch('tva', () => this.sauvegarderBrouillon());
            this.$watch('remiseGlobaleType', () => this.sauvegarderBrouillon());
            this.$watch('remiseGlobaleValeur', () => this.sauvegarderBrouillon());

            if (this.$el.tagName === 'FORM' || this.$el.querySelector('form')) {
                const formulaire = this.$el.tagName === 'FORM' ? this.$el : this.$el.querySelector('form');
                formulaire.addEventListener('submit', () => this.effacerBrouillon());
            }
        },

        // ─── Brouillon automatique ────────────────────────────────────
        sauvegarderBrouillon() {
            try {
                if (!this.lignes.some(l => l.designation || l.prix_unitaire > 0)) return;
                localStorage.setItem('maelya-brouillon-' + this.cle, JSON.stringify({
                    lignes: this.lignes.map(l => ({
                        designation: l.designation, quantite: l.quantite,
                        prix_unitaire: l.prix_unitaire, remise_type: l.remise_type,
                        remise_valeur: l.remise_valeur, tva_taux: l.tva_taux,
                        produit_id: l.produit_id, prestation_id: l.prestation_id,
                    })),
                    tva: this.tva,
                    tva_applicable: this.tvaApplicable,
                    remise_globale_type: this.remiseGlobaleType,
                    remise_globale_valeur: this.remiseGlobaleValeur,
                    horodatage: Date.now(),
                }));
            } catch (e) { /* stockage indisponible : on ignore */ }
        },

        lireBrouillon() {
            try {
                const brut = localStorage.getItem('maelya-brouillon-' + this.cle);
                if (!brut) return null;
                const donnees = JSON.parse(brut);
                // Au-delà de 24 h, le brouillon est périmé
                if (!donnees.horodatage || Date.now() - donnees.horodatage > 86400000) {
                    this.effacerBrouillon();
                    return null;
                }
                return donnees;
            } catch (e) { return null; }
        },

        effacerBrouillon() {
            try { localStorage.removeItem('maelya-brouillon-' + this.cle); } catch (e) {}
        },

        // ─── Catalogue ───────────────────────────────────────────────
        catalogueFiltered(ligne) {
            const q = (ligne.pickerSearch || '').toLowerCase().trim();
            if (q.length < 1) return this.catalogue.slice(0, 10);
            return this.catalogue.filter(c => (c.search || '').includes(q)).slice(0, 10);
        },

        choisirCatalogue(ligne, item) {
            // L'identifiant du catalogue est mémorisé : sans lui, ni le stock ni
            // la ventilation par produit ne peuvent être mis à jour.
            const m = String(item.id || '').match(/^(prod|p)_(.+)$/);
            ligne.produit_id = m && m[1] === 'prod' ? m[2] : null;
            ligne.prestation_id = m && m[1] === 'p' ? m[2] : null;
            ligne.designation = item.designation;
            ligne.prix_unitaire = item.prix;
            ligne.prix_display = this.formatPrixInput(item.prix);
            ligne.pickerOpen = false;
            ligne.pickerSearch = '';
        },

        // ─── Prix ────────────────────────────────────────────────────
        updatePrix(ligne, value) {
            const nombre = parseFloat(String(value).replace(/\s/g, '').replace(',', '.')) || 0;
            ligne.prix_unitaire = nombre;
            ligne.prix_display = this.formatPrixInput(nombre);
        },

        formatPrixInput(value) {
            return new Intl.NumberFormat('fr-FR').format(value || 0);
        },

        formatPrix(v) { return new Intl.NumberFormat('fr-FR').format(v || 0); },

        format(v) { return this.formatPrix(v); },

        // ─── Totaux ──────────────────────────────────────────────────
        get sousTotal() {
            return this.lignes.reduce((s, l) => s + (l.prix_unitaire || 0) * (l.quantite || 1), 0);
        },

        get remiseGlobale() {
            if (!this.remiseGlobaleType || !this.remiseGlobaleValeur) return 0;
            if (this.remiseGlobaleType === 'pourcentage') {
                return Math.round(this.sousTotal * this.remiseGlobaleValeur / 100);
            }
            return Number(this.remiseGlobaleValeur);
        },

        get totalHT() { return Math.max(0, this.sousTotal - this.remiseGlobale); },

        get totalTTC() { return Math.round(this.totalHT * (1 + (this.tva || 0) / 100)); },

        get canSubmit() {
            if (this.clientsList.length && !this.clientChoisi) return false;
            return this.lignes.some(l => l.designation.trim() && l.quantite > 0 && l.prix_unitaire > 0);
        },

        // ─── Client ──────────────────────────────────────────────────
        clientChoisi: config.initial?.client || null,
        clientChoisiId: config.initial?.client_id || '',

        selectClient(client) {
            this.clientChoisi = client;
            this.clientChoisiId = client.id;
        },

        retirerClient() {
            this.clientChoisi = null;
            this.clientChoisiId = '';
        },

        // ─── Création rapide d'un client ─────────────────────────────
        newClientOpen: false,
        newClientSaving: false,
        newClientError: '',
        newClient: { type_client: 'personne_physique', prenom: '', nom: '', raison_sociale: '', telephone: '', email: '', date_naissance_mois: '', date_naissance_jour: '', notes: '', adresse: '', piece_identite: '' },

        async creerClient() {
            this.newClientError = '';
            const nc = this.newClient;

            if (nc.type_client === 'personne_physique') {
                if (! nc.prenom.trim() || ! nc.nom.trim()) { this.newClientError = 'Prénom et nom sont requis.'; return; }
            } else if (! nc.raison_sociale.trim()) { this.newClientError = 'La raison sociale est requise.'; return; }
            if (! nc.telephone.trim()) { this.newClientError = 'Le téléphone est requis.'; return; }

            this.newClientSaving = true;
            try {
                const res = await fetch('{{ route('dashboard.clients.quick-store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        type_client: nc.type_client,
                        prenom: nc.prenom || null,
                        nom: nc.nom || null,
                        raison_sociale: nc.raison_sociale || null,
                        telephone: nc.telephone,
                        email: nc.email || null,
                        date_naissance: nc.date_naissance_mois && nc.date_naissance_jour ? nc.date_naissance_mois + '-' + nc.date_naissance_jour : null,
                        notes: nc.notes || null,
                        adresse: nc.adresse || null,
                        piece_identite: nc.piece_identite || null,
                    }),
                });

                if (! res.ok) {
                    const err = await res.json().catch(() => ({}));
                    this.newClientError = err.message || 'Erreur lors de la création.';
                    return;
                }

                const client = await res.json();
                this.clientsList.unshift(client);
                this.selectClient(client);
                this.newClient = { type_client: 'personne_physique', prenom: '', nom: '', raison_sociale: '', telephone: '', email: '', date_naissance_mois: '', date_naissance_jour: '', notes: '', adresse: '', piece_identite: '' };
                this.newClientOpen = false;
                document.body.classList.remove('overflow-hidden');
            } catch (e) {
                this.newClientError = 'Erreur réseau. Réessayez.';
            } finally {
                this.newClientSaving = false;
            }
        },
    }));
});
</script>
@endpush
