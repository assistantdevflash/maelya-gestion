/**
 * Caisse POS – Alpine.js component
 *
 * Toute l'interactivité (recherche, filtres, panier, paiement) est gérée
 * côté client pour un rendu instantané. Seuls la recherche client, le code
 * promo et la validation de vente passent par Livewire (requêtes DB).
 */
export default function caisseApp({ prestations, produits, catPrestations, catProduits, allCatPrestations = [], allCatProduits = [], prefilledItems = [], prefilledPanier = [], routeBrouillonStore = '/dashboard/caisse/brouillons' }) {
    return {
        // ── Catalogue (chargé une seule fois) ──
        prestations,
        produits,
        catPrestations,
        catProduits,
        allCatPrestations,
        allCatProduits,
        routeBrouillonStore,

        // ── Pré-remplissage (ex: depuis un RDV) ──
        prefilledItems,
        prefilledPanier,

        init() {
            if (Array.isArray(this.prefilledPanier) && this.prefilledPanier.length) {
                const next = {};
                this.prefilledPanier.forEach(it => {
                    const key = `${it.type}_${it.id}`;
                    next[key] = {
                        type: it.type,
                        id: it.id,
                        nom: it.nom,
                        prix: parseInt(it.prix) || 0,
                        quantite: parseInt(it.quantite) || 1,
                    };
                });
                this.panier = { ...this.panier, ...next };
            }
            if (Array.isArray(this.prefilledItems) && this.prefilledItems.length) {
                const next = {};
                this.prefilledItems.forEach(p => {
                    const key = `prestation_${p.id}`;
                    if (next[key]) {
                        next[key].quantite++;
                    } else {
                        next[key] = { type: 'prestation', id: p.id, nom: p.nom, prix: p.prix, quantite: 1 };
                    }
                });
                this.panier = { ...this.panier, ...next };
            }
            // Écoute le scan code-barres produit
            this.$el.addEventListener('scanner-produit', (e) => {
                const d = e.detail;
                const key = `produit_${d.id}`;
                if (this.panier[key]) {
                    this.panier[key].quantite++;
                } else {
                    this.panier = { ...this.panier, [key]: { type: 'produit', id: d.id, nom: d.nom, prix: parseInt(d.prix) || 0, quantite: 1 } };
                }
            });
            // Fermer le modal nouveau client après création réussie
            this.$wire.on('client-added', () => {
                this.newClientOpen = false;
                document.body.classList.remove('overflow-hidden');
            });
            // Réinitialiser après vente à crédit
            this.$wire.on('reset-caisse-credit', () => {
                this.panier = {};
                this.search = '';
                this.creditApport = 0;
                this.modePaiement = 'cash';
                this.showCreditConfirmation = false;
            });
        },

        // ── État filtres ──
        search: '',
        onglet: 'prestations',
        categorieId: '',

        // ── Panier ──
        panier: {},

        // ── Paiement ──
        modePaiement: 'cash',
        referencePaiement: '',
        montantRemis: null,
        montantMixteCash: 0,
        montantMixteMobile: 0,
        montantMixteCartes: 0,
        pourboire: 0,

        // ── Code promo ──
        codePromo: null,
        codePromoInput: '',
        codePromoErreur: '',
        codePromoLoading: false,

        // ── UI ──
        showConfirmation: false,
        loading: false,
        enAttenteLoading: false,
        newClientOpen: false,

        // ── Vente rapide ──
        showVenteRapide: false,
        venteRapideNom: '',
        venteRapidePrix: null,
        venteRapideErreur: '',
        venteRapideCounter: 0,
        venteRapideType: '',
        venteRapideCategorieId: '',

        // ═══════════════════════════════════════
        //  Computed (getters réactifs Alpine)
        // ═══════════════════════════════════════

        get filteredItems() {
            let items = this.onglet === 'prestations' ? this.prestations : this.produits;
            if (this.search) {
                const s = this.search.toLowerCase();
                items = items.filter(i => i.nom.toLowerCase().includes(s));
            }
            if (this.categorieId) {
                items = items.filter(i => i.categorie_id === this.categorieId);
            }
            return items;
        },

        get categories() {
            return this.onglet === 'prestations' ? this.catPrestations : this.catProduits;
        },

        get panierKeys() {
            return Object.keys(this.panier);
        },

        get panierVide() {
            return this.panierKeys.length === 0;
        },

        get nbArticles() {
            return Object.values(this.panier).reduce((s, i) => s + i.quantite, 0);
        },

        get totalBrut() {
            return Object.values(this.panier).reduce((s, i) => s + i.prix * i.quantite, 0);
        },

        get remise() {
            return this.codePromo?.remise || 0;
        },

        get total() {
            return Math.max(0, this.totalBrut - this.remise);
        },

        get montantsSuggeres() {
            const t = this.total;
            if (t <= 0) return [];
            const sugg = [t];
            [50, 100, 500, 1000, 5000].forEach(step => {
                const r = Math.ceil(t / step) * step;
                if (r > t && !sugg.includes(r)) sugg.push(r);
            });
            sugg.sort((a, b) => a - b);
            return [...new Set(sugg)].slice(0, 5);
        },

        get monnaie() {
            if (!this.montantRemis) return 0;
            return Math.max(0, this.montantRemis - this.total);
        },

        get resteMixte() {
            if (this.modePaiement !== 'mixte') return 0;
            const alloue = (parseInt(this.montantMixteCash) || 0) + (parseInt(this.montantMixteMobile) || 0) + (parseInt(this.montantMixteCartes) || 0);
            return this.total - alloue;
        },

        get mixtePret() {
            return this.modePaiement !== 'mixte' || this.resteMixte === 0;
        },

        // Catégories pour la vente rapide (selon le type choisi — toutes, même vides)
        get venteRapideCategories() {
            if (this.venteRapideType === 'prestation') return this.allCatPrestations || this.catPrestations;
            if (this.venteRapideType === 'produit') return this.allCatProduits || this.catProduits;
            return [];
        },

        // ═══════════════════════════════════════
        //  Actions catalogue
        // ═══════════════════════════════════════

        changerOnglet(o) {
            this.onglet = o;
            this.categorieId = '';
            this.search = '';
        },

        // ═══════════════════════════════════════
        //  Vente rapide
        // ═══════════════════════════════════════

        toggleVenteRapide() {
            this.showVenteRapide = !this.showVenteRapide;
            this.venteRapideNom = '';
            this.venteRapidePrix = null;
            this.venteRapideErreur = '';
            this.venteRapideType = '';
            this.venteRapideCategorieId = '';
        },

        setVenteRapideType(type) {
            if (this.venteRapideType === type) {
                this.venteRapideType = '';
                this.venteRapideCategorieId = '';
            } else {
                this.venteRapideType = type;
                this.venteRapideCategorieId = '';
            }
        },

        setVenteRapideCategorie(id) {
            this.venteRapideCategorieId = this.venteRapideCategorieId === id ? '' : id;
        },

        ajouterVenteRapide() {
            if (!this.venteRapideNom.trim()) {
                this.venteRapideErreur = 'Le nom est requis.';
                return;
            }
            if (!this.venteRapidePrix || this.venteRapidePrix <= 0) {
                this.venteRapideErreur = 'Le prix doit être supérieur à 0.';
                return;
            }
            const key = `libre_${++this.venteRapideCounter}`;
            this.panier = {
                ...this.panier,
                [key]: {
                    type: 'libre',
                    id: key,
                    nom: this.venteRapideNom.trim(),
                    prix: Math.round(this.venteRapidePrix),
                    quantite: 1,
                    typeLibre: this.venteRapideType || null,
                    categorieId: this.venteRapideCategorieId || null,
                },
            };
            this.venteRapideNom = '';
            this.venteRapidePrix = null;
            this.venteRapideErreur = '';
            this.venteRapideType = '';
            this.venteRapideCategorieId = '';
            this.showVenteRapide = false;
        },

        // ═══════════════════════════════════════
        //  Actions panier (100 % client-side)
        // ═══════════════════════════════════════

        ajouterItem(item) {
            const type = this.onglet === 'prestations' ? 'prestation' : 'produit';
            const key = `${type}_${item.id}`;
            if (this.panier[key]) {
                this.panier[key].quantite++;
            } else {
                this.panier = {
                    ...this.panier,
                    [key]: { type, id: item.id, nom: item.nom, prix: item.prix, quantite: 1 },
                };
            }
        },

        quantiteDans(item) {
            const type = this.onglet === 'prestations' ? 'prestation' : 'produit';
            return this.panier[`${type}_${item.id}`]?.quantite || 0;
        },

        incrementer(key) {
            if (this.panier[key]) this.panier[key].quantite++;
        },

        decrementer(key) {
            if (this.panier[key]) {
                if (this.panier[key].quantite > 1) {
                    this.panier[key].quantite--;
                } else {
                    this.supprimerItem(key);
                }
            }
        },

        supprimerItem(key) {
            const { [key]: _, ...rest } = this.panier;
            this.panier = rest;
        },

        viderPanier() {
            this.panier = {};
            this.modePaiement = 'cash';
            this.referencePaiement = '';
            this.montantRemis = null;
            this.montantMixteCash = 0;
            this.montantMixteMobile = 0;
            this.montantMixteCartes = 0;
            this.showConfirmation = false;
            this.codePromo = null;
            this.codePromoInput = '';
            this.codePromoErreur = '';
        },

        // ═══════════════════════════════════════
        //  Code promo (Livewire)
        // ═══════════════════════════════════════

        async appliquerCode() {
            this.codePromoErreur = '';
            if (!this.codePromoInput.trim()) return;
            this.codePromoLoading = true;
            try {
                const result = await this.$wire.appliquerCode(
                    this.codePromoInput.trim(),
                    this.totalBrut,
                );
                this.codePromo = result.promo;
                this.codePromoErreur = result.erreur;
            } finally {
                this.codePromoLoading = false;
            }
        },

        retirerCode() {
            this.codePromo = null;
            this.codePromoInput = '';
            this.codePromoErreur = '';
        },

        // ═══════════════════════════════════════
        //  Confirmation & validation (Livewire)
        // ═══════════════════════════════════════

        ouvrirConfirmation() {
            if (this.panierVide) return;
            if (!this.montantRemis && this.modePaiement === 'cash') {
                this.montantRemis = this.total;
            }
            if (this.modePaiement === 'mixte' && !this.montantMixteCash && !this.montantMixteMobile && !this.montantMixteCartes) {
                this.montantMixteMobile = this.total;
            }
            this.showConfirmation = true;
        },

        fermerConfirmation() {
            this.showConfirmation = false;
        },

        async mettreEnAttente() {
            if (this.panierKeys.length === 0 || this.enAttenteLoading) return;
            this.enAttenteLoading = true;
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                const res = await fetch(this.routeBrouillonStore, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        panier: Object.values(this.panier),
                        client_id: this.$wire.clientId || null,
                        total_indicatif: this.totalBrut,
                    }),
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                // Reset panier and notify
                this.panier = {};
                this.codePromo = null;
                this.codePromoInput = '';
                this.pourboire = 0;
                this.modePaiement = 'cash';
                this.montantRemis = null;
                this.referencePaiement = '';
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Panier mis en attente.' } }));
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Erreur lors de la mise en attente.' } }));
            } finally {
                this.enAttenteLoading = false;
            }
        },

        async valider(imprimer = false) {
            if (this.panierVide || this.loading) return;
            this.loading = true;
            this.showConfirmation = false;
            try {
                await this.$wire.valider(
                    Object.values(this.panier),
                    this.modePaiement,
                    this.montantRemis,
                    this.referencePaiement,
                    this.codePromo?.id ?? null,
                    imprimer,
                    this.modePaiement === 'mixte' ? (parseInt(this.montantMixteCash) || 0) : null,
                    this.modePaiement === 'mixte' ? (parseInt(this.montantMixteMobile) || 0) : null,
                    this.modePaiement === 'mixte' ? (parseInt(this.montantMixteCartes) || 0) : null,
                    parseInt(this.pourboire) || 0,
                );
            } finally {
                this.loading = false;
            }
        },

        // ═══════════════════════════════════════
        //  Crédit
        // ═══════════════════════════════════════

        creditApport: 0,
        creditNbEcheances: 3,
        creditFrequence: 'mensuelle',
        showCreditConfirmation: false,

        async validerVenteCredit() {
            if (this.panierVide || this.loading) return;
            if (!this.$wire.clientId) {
                alert('Veuillez sélectionner un client pour la vente à crédit.');
                return;
            }
            const apport = parseInt(this.creditApport) || 0;
            const reste = this.total - apport;
            if (reste <= 0) {
                alert("Le reste à payer doit être supérieur à 0 pour un crédit.");
                return;
            }
            this.showCreditConfirmation = true;
        },

        async confirmerVenteCredit() {
            this.showCreditConfirmation = false;
            this.loading = true;
            const apport = parseInt(this.creditApport) || 0;
            try {
                await this.$wire.validerVenteCredit(
                    Object.values(this.panier),
                    apport,
                    this.creditNbEcheances,
                    this.creditFrequence,
                    this.codePromo?.id ?? null,
                );
            } finally {
                this.loading = false;
            }
        },

        // ═══════════════════════════════════════
        //  Helpers
        // ═══════════════════════════════════════

        formatNumber(n) {
            return new Intl.NumberFormat('fr-FR').format(n);
        },
    };
}
