/**
 * Caisse POS – Alpine.js component
 *
 * Toute l'interactivité (recherche, filtres, panier, paiement) est gérée
 * côté client pour un rendu instantané. Seuls la recherche client, le code
 * promo et la validation de vente passent par Livewire (requêtes DB).
 */
export default function caisseApp({ prestations, produits, catPrestations, catProduits, allCatPrestations = [], allCatProduits = [], prefilledItems = [], prefilledPanier = [], routeBrouillonStore = '/dashboard/caisse/brouillons', routeDerniereVenteJson = '/dashboard/ventes/derniere-json', routeAnnulerDerniere = '/dashboard/ventes/annuler-derniere', mode = 'complet', institutId = null }) {
    return {
        // ── Catalogue (chargé une seule fois) ──
        prestations,
        produits,
        catPrestations,
        catProduits,
        allCatPrestations,
        allCatProduits,
        routeBrouillonStore,
        routeDerniereVenteJson,
        routeAnnulerDerniere,
        mode,

        // ── Pré-remplissage (ex: depuis un RDV) ──
        prefilledItems,
        prefilledPanier,

        init() {
            this.restaurerPanier();
            this.$watch('panier', () => this.sauvegarderPanier(), { deep: true });
            this.installerRaccourcis();

            // Mode simplifié : produits uniquement, pas d'onglet prestations.
            if (this.mode === 'simple') {
                this.onglet = 'produits';
            }
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

        /**
         * Raccourcis clavier du poste de caisse.
         *
         *   /       → place le curseur dans la recherche
         *   Entrée  → ouvre le paiement (hors champ de saisie)
         *   Échap   → ferme la modale courante, sinon quitte le champ
         *
         * Les traitements sont ignorés dès qu'un champ est en cours de saisie
         * pour ne pas perturber la saisie du montant remis.
         */
        installerRaccourcis() {
            this._raccourcis = (e) => {
                const cible = e.target;
                const dansChamp = !!cible && (
                    cible.tagName === 'INPUT'
                    || cible.tagName === 'TEXTAREA'
                    || cible.tagName === 'SELECT'
                    || cible.isContentEditable
                );

                if (e.key === 'Escape') {
                    if (this.showConfirmation) { this.fermerConfirmation(); return; }
                    if (this.showVenteRapide) { this.toggleVenteRapide(); return; }
                    if (this.newClientOpen) { this.newClientOpen = false; return; }
                    if (this.mobileCartOpen) { this.mobileCartOpen = false; return; }
                    if (dansChamp) { cible.blur(); }
                    return;
                }

                if (dansChamp || e.ctrlKey || e.metaKey || e.altKey) return;

                if (e.key === '/') {
                    const champ = document.querySelector('[data-recherche]');
                    if (champ) {
                        e.preventDefault();
                        champ.focus();
                        champ.select();
                    }
                    return;
                }

                if (e.key === 'Enter' && !this.panierVide && !this.showConfirmation) {
                    e.preventDefault();
                    this.ouvrirConfirmation();
                }
            };

            window.addEventListener('keydown', this._raccourcis);
            if (typeof this.$cleanup === 'function') {
                this.$cleanup(() => window.removeEventListener('keydown', this._raccourcis));
            }
        },

        // ── État filtres ──
        search: '',
        // Mode simple : uniquement des produits (aucun onglet prestations).
        onglet: mode === 'simple' ? 'produits' : 'prestations',
        categorieId: '',

        /**
         * Mode courant : 'complet' (défaut) ou 'simple'.
         * ⚠️ La valeur provient de l'option `mode` passée par la vue Livewire ;
         * ne PAS redéclarer `mode` ici (une seconde clé écraserait la première).
         */

        get simple() {
            return this.mode === 'simple';
        },

        // ── Panier ──
        panier: {},

        // Le panier est conservé dans le navigateur : une erreur de validation
        // (rupture de stock) ou un rechargement ne doit pas faire perdre la
        // sélection en cours.
        institutId,
        get clePanier() {
            return 'caisse_panier_' + (this.institutId || 'local');
        },
        animatingItems: {}, // {key: 'add'|'remove'}

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

        // ── Annulation de la dernière vente ──
        annulationOuverte: false,
        annulationChargement: false,
        annulationEnvoi: false,
        annulationErreur: '',
        derniereVente: null,

        // ── UI ──
        showConfirmation: false,
        loading: false,
        // Vrai si l'encaissement en cours demande aussi l'impression du
        // ticket : permet d'animer le bon bouton.
        impressionDemandee: false,
        enAttenteLoading: false,
        newClientOpen: false,
        mobileCartOpen: false,
        toastMessage: '',
        toastVisible: false,

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

        /**
         * Mode simple : résultats affichés sous forme de liste. On n'affiche
         * rien tant que la recherche est vide (pas de grille catalogue).
         */
        get resultatsRecherche() {
            const q = (this.search || '').trim();
            if (q.length < 1) return [];
            return this.filteredItems.slice(0, 40);
        },

        get categories() {
            return this.onglet === 'prestations' ? this.catPrestations : this.catProduits;
        },

        get panierKeys() {
            return Object.keys(this.panier).reverse();
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

        /** Libellé du bouton d'encaissement (adapté au mode). */
        get libelleEncaisser() {
            const montant = this.formatNumber(this.total) + ' F';
            return this.simple ? 'Valider le paiement · ' + montant : 'Encaisser ' + montant;
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
                    // Caisse simplifiée : article libre typé « produit », sans catégorie
                    typeLibre: this.simple ? 'produit' : (this.venteRapideType || null),
                    categorieId: this.simple ? null : (this.venteRapideCategorieId || null),
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

        /**
         * Un produit sans stock ne peut pas être vendu. Le serveur le
         * refuserait de toute façon ; on l'empêche ici pour éviter au caissier
         * de composer un panier qui échouera à la validation.
         */
        enRupture(item) {
            return this.onglet === 'produits' && item.stock !== null
                && item.stock !== undefined && Number(item.stock) <= 0;
        },

        ajouterItem(item) {
            if (this.onglet === 'produits' && this.enRupture(item)) {
                this.showToast(`⛔ ${item.nom} est en rupture de stock`);
                return;
            }

            this.ajouterAuPanier(
                this.onglet === 'prestations' ? 'prestation' : 'produit',
                item
            );
        },

        /**
         * Ajoute un article au panier en imposant son type.
         *
         * Utilisé par les raccourcis « derniers vendus », où le type ne dépend
         * pas de l'onglet affiché.
         */
        ajouterAuPanier(type, item) {
            if (type === 'produit' && item.stock !== null
                && item.stock !== undefined && Number(item.stock) <= 0) {
                this.showToast(`⛔ ${item.nom} est en rupture de stock`);
                return;
            }

            const key = `${type}_${item.id}`;
            const existait = !!this.panier[key];
            if (existait) {
                this.panier[key].quantite++;
                // Petit flash pour indiquer l'ajout
                this.animatingItems[key] = 'flash';
                setTimeout(() => {
                    delete this.animatingItems[key];
                    this.animatingItems = { ...this.animatingItems };
                }, 300);
                this.showToast(`${item.nom} (×${this.panier[key].quantite})`);
            } else {
                this.panier = {
                    ...this.panier,
                    [key]: { type, id: item.id, nom: item.nom, prix: item.prix, quantite: 1 },
                };
                // Animation d'entrée
                this.animatingItems[key] = 'add';
                setTimeout(() => {
                    delete this.animatingItems[key];
                    this.animatingItems = { ...this.animatingItems };
                }, 300);
                this.showToast(`✓ ${item.nom} ajouté`);
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
            const item = this.panier[key];
            if (item) {
                this.showToast(`✗ ${item.nom} retiré`);
            }
            // Animation de sortie avant suppression
            this.animatingItems[key] = 'remove';
            this.animatingItems = { ...this.animatingItems };
            setTimeout(() => {
                const { [key]: _, ...rest } = this.panier;
                this.panier = rest;
                delete this.animatingItems[key];
                this.animatingItems = { ...this.animatingItems };
            }, 200);
        },

        showToast(message) {
            this.toastMessage = message;
            this.toastVisible = true;
            setTimeout(() => {
                this.toastVisible = false;
            }, 2000);
        },

        toggleMobileCart() {
            this.mobileCartOpen = !this.mobileCartOpen;
            if (this.mobileCartOpen) {
                document.body.classList.add('overflow-hidden');
            } else {
                document.body.classList.remove('overflow-hidden');
            }
        },

        restaurerPanier() {
            try {
                const brut = localStorage.getItem(this.clePanier);
                if (! brut) return;

                const sauve = JSON.parse(brut);
                if (sauve && typeof sauve === 'object' && Object.keys(sauve).length) {
                    this.panier = sauve;
                }
            } catch (e) {
                // Données illisibles : on repart d'un panier vide
            }
        },

        sauvegarderPanier() {
            try {
                if (Object.keys(this.panier).length === 0) {
                    localStorage.removeItem(this.clePanier);
                    return;
                }

                localStorage.setItem(this.clePanier, JSON.stringify(this.panier));
            } catch (e) {
                // Stockage plein ou désactivé : la caisse reste utilisable
            }
        },

        oublierPanier() {
            try {
                localStorage.removeItem(this.clePanier);
            } catch (e) {}
        },

        viderPanier() {
            this.oublierPanier();
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
                
                // Afficher un toast pour indiquer le résultat
                if (result.promo) {
                    this.showToast(`✅ Code promo ${result.promo.code} appliqué !`);
                } else if (result.erreur) {
                    this.showToast(`❌ ${result.erreur}`);
                }
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
            // Pendant l'enregistrement, la vente est déjà partie au serveur :
            // fermer la fenêtre n'aurait plus de sens.
            if (this.loading) return;
            this.showConfirmation = false;
        },

        // ═══════════════════════════════════════
        //  Annulation de la dernière vente
        // ═══════════════════════════════════════

        /**
         * Ouvre la fenêtre de confirmation en chargeant le détail de la
         * dernière vente : le caissier doit voir ce qu'il annule avant de
         * valider.
         */
        async ouvrirAnnulationDerniere() {
            this.annulationOuverte = true;
            this.annulationErreur = '';
            this.annulationChargement = true;
            this.derniereVente = null;

            try {
                const r = await fetch(this.routeDerniereVenteJson, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (r.status === 403) {
                    this.annulationErreur = 'Seul un administrateur peut annuler une vente.';
                    return;
                }

                if (!r.ok) {
                    throw new Error('HTTP ' + r.status);
                }

                const data = await r.json();
                this.derniereVente = data.vente || null;
                if (!this.derniereVente) {
                    this.annulationErreur = data.message
                        || 'Aucune vente encaissée dans les dernières 24 heures.';
                }
            } catch (e) {
                this.annulationErreur = 'Impossible de charger la dernière vente. Vérifiez votre connexion.';
            } finally {
                this.annulationChargement = false;
            }
        },

        fermerAnnulation() {
            if (this.annulationEnvoi) return;
            this.annulationOuverte = false;
            this.annulationErreur = '';
            this.derniereVente = null;
        },

        async confirmerAnnulationDerniere() {
            if (!this.derniereVente || this.annulationEnvoi) return;

            this.annulationEnvoi = true;
            this.annulationErreur = '';

            try {
                const jeton = document.querySelector('meta[name="csrf-token"]')?.content;
                const r = await fetch(this.routeAnnulerDerniere, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': jeton,
                    },
                    credentials: 'same-origin',
                    body: new URLSearchParams({
                        motif_annulation: 'Annulation rapide depuis la caisse',
                    }),
                });

                if (r.status === 403) {
                    this.annulationErreur = 'Seul un administrateur peut annuler une vente.';
                    return;
                }

                if (!r.ok) {
                    throw new Error('HTTP ' + r.status);
                }

                // La vente est annulée côté serveur : le stock et les points
                // sont rétablis, on recharge pour repartir d'un état sûr.
                window.location.reload();
            } catch (e) {
                this.annulationErreur = "L'annulation n'a pas pu être enregistrée. Réessayez.";
            } finally {
                this.annulationEnvoi = false;
            }
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

            // La modale reste ouverte pendant l'enregistrement : c'est là que
            // le spinner et le libellé « Encaissement… » sont visibles. Elle
            // disparaît avec le rechargement de la page après la vente.
            this.loading = true;
            this.impressionDemandee = imprimer;

            // Filet de sécurité : si la navigation n'a pas lieu (erreur
            // serveur, coupure réseau), on rend la main au caissier.
            clearTimeout(this._minuteurValidation);
            this._minuteurValidation = setTimeout(() => {
                this.loading = false;
                this.impressionDemandee = false;
            }, 20000);

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
            } catch (e) {
                clearTimeout(this._minuteurValidation);
                this.loading = false;
                this.impressionDemandee = false;
                this.showToast("⚠️ La vente n'a pas pu être enregistrée.");
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
            if (this.loading) return;

            this.showCreditConfirmation = false;
            this.loading = true;

            clearTimeout(this._minuteurValidation);
            this._minuteurValidation = setTimeout(() => {
                this.loading = false;
            }, 20000);
            const apport = parseInt(this.creditApport) || 0;
            try {
                await this.$wire.validerVenteCredit(
                    Object.values(this.panier),
                    apport,
                    this.creditNbEcheances,
                    this.creditFrequence,
                    this.codePromo?.id ?? null,
                );
                // Fermer le drawer mobile après validation
                this.mobileCartOpen = false;
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

/**
 * Sélecteur de client des écrans de caisse.
 *
 * Auparavant, les 200 premiers clients de l'établissement étaient injectés
 * dans la page et filtrés en local : au-delà de 200 clients, les suivants
 * étaient invisibles à la caisse. Ce composant garde une petite liste de
 * départ pour l'affichage immédiat et interroge le serveur dès 2 caractères.
 */
export function selecteurClient({ initial = [], url = '/dashboard/clients/recherche-json' } = {}) {
    return {
        url,
        clients: initial,
        search: '',
        open: false,
        chargement: false,
        // Vrai pendant l'aller-retour Livewire de `selectClient` : la
        // sélection d'un client re-rend la carte client, il faut le montrer.
        selectionEnCours: false,
        timer: null,

        get filtered() {
            if (this.search.length < 2) {
                return this.clients.slice(0, 8);
            }
            const q = this.search.toLowerCase();
            return this.clients.filter(c => c.search.includes(q)).slice(0, 15);
        },

        onInput() {
            this.open = true;
            clearTimeout(this.timer);

            const q = this.search.trim();
            if (q.length < 2) {
                this.clients = initial;
                return;
            }

            this.timer = setTimeout(() => this.rechercher(q), 250);
        },

        async rechercher(q) {
            this.chargement = true;
            try {
                const r = await fetch(`${this.url}?q=${encodeURIComponent(q)}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                if (!r.ok) {
                    throw new Error('HTTP ' + r.status);
                }
                this.clients = await r.json();
            } catch (e) {
                // En cas de coupure réseau on garde la liste précédente.
            } finally {
                this.chargement = false;
            }
        },

        async choose(id) {
            if (this.selectionEnCours) return;

            this.open = false;
            this.search = '';
            this.clients = initial;

            const composant = this.$wire;
            if (!composant) return;

            this.selectionEnCours = true;

            try {
                await composant.selectClient(id);
            } catch (e) {
                // Livewire signale l'erreur : on laisse la carte inchangée.
            } finally {
                this.selectionEnCours = false;
            }
        },
    };
}
