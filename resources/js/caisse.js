/**
 * Caisse POS – Alpine.js component
 *
 * Toute l'interactivité (recherche, filtres, panier, paiement) est gérée
 * côté client pour un rendu instantané. Seuls la recherche client, le code
 * promo et la validation de vente passent par Livewire (requêtes DB).
 */
export default function caisseApp({ prestations, produits, catPrestations, catProduits }) {
    return {
        // ── Catalogue (chargé une seule fois) ──
        prestations,
        produits,
        catPrestations,
        catProduits,

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

        // ── Code promo ──
        codePromo: null,
        codePromoInput: '',
        codePromoErreur: '',

        // ── UI ──
        showConfirmation: false,
        loading: false,

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

        // ═══════════════════════════════════════
        //  Actions catalogue
        // ═══════════════════════════════════════

        changerOnglet(o) {
            this.onglet = o;
            this.categorieId = '';
            this.search = '';
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
            const result = await this.$wire.appliquerCode(
                this.codePromoInput.trim(),
                this.totalBrut,
            );
            this.codePromo = result.promo;
            this.codePromoErreur = result.erreur;
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
