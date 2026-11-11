/**
 * Composants Alpine de l'espace commercial.
 *
 * Les actions courantes (changer une étape du pipeline, enregistrer une
 * interaction, repousser une relance) passent par fetch : la page ne se
 * recharge plus, l'écran reste à sa place et le commercial enchaîne ses fiches
 * sans perdre le fil.
 */

/** Jeton CSRF de la page. */
function jeton() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

/** Petits messages éphémères en bas à droite. */
export function toasts() {
    return {
        liste: [],
        compteur: 0,

        ajouter(detail) {
            const id = ++this.compteur;

            this.liste.push({
                id,
                message: detail?.message || '',
                type: detail?.type || 'success',
            });

            setTimeout(() => {
                this.liste = this.liste.filter(t => t.id !== id);
            }, 3500);
        },
    };
}

/**
 * Carte prospect de la page « Prospection ».
 */
export function ficheProspect(config) {
    return {
        ouvert: false,

        statut: config.statut,
        libelleStatut: config.libelleStatut,
        relance: config.relance,
        niveau: config.niveau,
        couleurs: config.couleurs || {},

        historique: config.historique || [],
        nbInteractions: config.nbInteractions || 0,

        routeStatut: config.routeStatut,
        routeActivite: config.routeActivite,

        enCoursStatut: false,
        enCoursInteraction: false,

        jeton,

        async changerStatut(nouveau) {
            if (this.enCoursStatut || nouveau === this.statut) {
                return;
            }

            const precedent = this.statut;
            this.enCoursStatut = true;

            try {
                const reponse = await fetch(this.routeStatut, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': jeton(),
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ statut: nouveau }),
                });

                if (!reponse.ok) {
                    throw new Error('HTTP ' + reponse.status);
                }

                const data = await reponse.json();

                this.statut = data.statut;
                this.libelleStatut = data.libelle;
                this.relance = data.relance;
                this.niveau = data.niveau;

                window.dispatchEvent(new CustomEvent('commercial-toast', {
                    detail: { message: data.message },
                }));
            } catch (e) {
                // On remet la valeur précédente : le select reste cohérent.
                this.statut = precedent;

                window.dispatchEvent(new CustomEvent('commercial-toast', {
                    detail: { message: "Le statut n'a pas pu être enregistré.", type: 'error' },
                }));
            } finally {
                this.enCoursStatut = false;
            }
        },

        async ajouterInteraction(formulaire) {
            if (this.enCoursInteraction) {
                return;
            }

            this.enCoursInteraction = true;

            try {
                const reponse = await fetch(this.routeActivite, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': jeton(),
                    },
                    credentials: 'same-origin',
                    body: new FormData(formulaire),
                });

                if (!reponse.ok) {
                    throw new Error('HTTP ' + reponse.status);
                }

                const data = await reponse.json();

                this.historique.unshift(data.activite);
                this.historique = this.historique.slice(0, 8);
                this.nbInteractions = data.nb_interactions;

                if (data.relance !== undefined) {
                    this.relance = data.relance;
                    this.niveau = data.niveau;
                }

                const zone = formulaire.querySelector('[name="contenu"]');
                if (zone) zone.value = '';

                window.dispatchEvent(new CustomEvent('commercial-toast', {
                    detail: { message: data.message },
                }));
            } catch (e) {
                window.dispatchEvent(new CustomEvent('commercial-toast', {
                    detail: { message: "L'interaction n'a pas pu être enregistrée.", type: 'error' },
                }));
            } finally {
                this.enCoursInteraction = false;
            }
        },
    };
}

/**
 * Ligne de relance (page « Relances »).
 *
 * Après une relance faite ou une replanification, la ligne s'efface d'elle-même
 * au lieu de recharger toute la page.
 */
export function ligneRelance(config) {
    return {
        enCours: false,
        traitee: false,

        routeActivite: config.routeActivite || null,
        routeRelance: config.routeRelance || null,

        async marquerFaite(formulaire) {
            if (this.enCours || !this.routeActivite) return;

            this.enCours = true;

            try {
                const reponse = await fetch(this.routeActivite, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': jeton(),
                    },
                    credentials: 'same-origin',
                    body: new FormData(formulaire),
                });

                if (!reponse.ok) {
                    throw new Error('HTTP ' + reponse.status);
                }

                const data = await reponse.json();
                this.traitee = true;

                window.dispatchEvent(new CustomEvent('commercial-toast', {
                    detail: { message: data.message || 'Relance enregistrée.' },
                }));
            } catch (e) {
                window.dispatchEvent(new CustomEvent('commercial-toast', {
                    detail: { message: "La relance n'a pas pu être enregistrée.", type: 'error' },
                }));
            } finally {
                this.enCours = false;
            }
        },

        async planifier(formulaire) {
            if (this.enCours || !this.routeRelance) return;

            this.enCours = true;

            try {
                const donnees = new FormData(formulaire);
                const reponse = await fetch(this.routeRelance, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': jeton(),
                    },
                    credentials: 'same-origin',
                    body: donnees,
                });

                if (!reponse.ok) {
                    throw new Error('HTTP ' + reponse.status);
                }

                const data = await reponse.json();
                this.traitee = true;

                window.dispatchEvent(new CustomEvent('commercial-toast', {
                    detail: { message: data.message || 'Relance planifiée.' },
                }));
            } catch (e) {
                window.dispatchEvent(new CustomEvent('commercial-toast', {
                    detail: { message: "La relance n'a pas pu être planifiée.", type: 'error' },
                }));
            } finally {
                this.enCours = false;
            }
        },
    };
}
