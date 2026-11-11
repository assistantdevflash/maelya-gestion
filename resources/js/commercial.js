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

/**
 * Message d'erreur adapté au code HTTP renvoyé.
 * 419 = jeton CSRF absent ou périmé (session expirée).
 */
function messageErreur(reponse, defaut) {
    if (reponse && reponse.status === 419) {
        return 'Session expirée : rechargez la page (Ctrl+F5).';
    }
    if (reponse && reponse.status === 422) {
        return 'Certaines informations sont incomplètes.';
    }
    if (reponse && reponse.status === 403) {
        return "Vous n'avez pas accès à cette action.";
    }

    return defaut;
}

/** Affiche un message dans le coin de l'écran. */
function notifier(message, type = 'success') {
    window.dispatchEvent(new CustomEvent('commercial-toast', {
        detail: { message, type },
    }));
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
        // Dernier statut réellement enregistré : sert de point de retour si
        // l'enregistrement échoue.
        statutConfirme: config.statut,
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
            if (this.enCoursStatut) {
                // Un enregistrement est déjà en cours : on rétablit ce que
                // l'utilisateur voit pour éviter un état incohérent.
                this.statut = this.statutConfirme;
                return;
            }

            if (nouveau === this.statutConfirme) {
                return;
            }

            const precedent = this.statutConfirme;

            if (! jeton()) {
                this.statut = precedent;
                notifier('Session expirée : rechargez la page (Ctrl+F5).', 'error');
                return;
            }

            this.enCoursStatut = true;

            let reponse = null;

            try {
                reponse = await fetch(this.routeStatut, {
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

                this.statutConfirme = data.statut;
                this.statut = data.statut;
                this.libelleStatut = data.libelle;
                this.relance = data.relance;
                this.niveau = data.niveau;

                notifier(data.message);
            } catch (e) {
                // Le select revient sur le statut réellement enregistré.
                this.statut = precedent;

                notifier(
                    messageErreur(reponse, "Le statut n'a pas pu être enregistré."),
                    'error'
                );
            } finally {
                this.enCoursStatut = false;
            }
        },

        async ajouterInteraction(formulaire) {
            if (this.enCoursInteraction) {
                return;
            }

            if (! jeton()) {
                notifier('Session expirée : rechargez la page (Ctrl+F5).', 'error');
                return;
            }

            this.enCoursInteraction = true;
            let reponse = null;

            try {
                reponse = await fetch(this.routeActivite, {
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
                notifier(
                    messageErreur(reponse, "L'interaction n'a pas pu être enregistrée."),
                    'error'
                );
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

            if (! jeton()) {
                notifier('Session expirée : rechargez la page (Ctrl+F5).', 'error');
                return;
            }

            this.enCours = true;
            let reponse = null;

            try {
                reponse = await fetch(this.routeActivite, {
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
                notifier(
                    messageErreur(reponse, "La relance n'a pas pu être enregistrée."),
                    'error'
                );
            } finally {
                this.enCours = false;
            }
        },

        async planifier(formulaire) {
            if (this.enCours || !this.routeRelance) return;

            if (! jeton()) {
                notifier('Session expirée : rechargez la page (Ctrl+F5).', 'error');
                return;
            }

            this.enCours = true;
            let reponse = null;

            try {
                const donnees = new FormData(formulaire);
                reponse = await fetch(this.routeRelance, {
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
                notifier(
                    messageErreur(reponse, "La relance n'a pas pu être planifiée."),
                    'error'
                );
            } finally {
                this.enCours = false;
            }
        },
    };
}
