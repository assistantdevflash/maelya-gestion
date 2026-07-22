<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Documentation Maëlya Gestion — Guide Complet</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10.5px; color: #1f2937; line-height: 1.55; }
    .page { padding: 22px 26px; }

    /* En-tête principal */
    .main-header { padding: 20px 22px; margin-bottom: 18px; border-radius: 10px; background: #9333ea; }
    .main-header-title { font-size: 20px; font-weight: 800; color: #ffffff; margin-bottom: 3px; }
    .main-header-sub { font-size: 10px; color: rgba(255,255,255,0.8); }
    .main-header-meta { font-size: 9px; color: rgba(255,255,255,0.6); margin-top: 6px; }

    /* Table des matières */
    .toc { margin-bottom: 20px; padding: 12px 14px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb; }
    .toc-title { font-size: 11px; font-weight: 700; color: #374151; margin-bottom: 8px; }
    .toc-grid { display: table; width: 100%; }
    .toc-row { display: table-row; }
    .toc-cell { display: table-cell; padding: 2px 6px 2px 0; font-size: 9.5px; color: #6b7280; width: 50%; }
    .toc-cell strong { color: #374151; }

    /* Sections */
    .section { margin-bottom: 18px; page-break-inside: avoid; }
    .section-header { display: block; overflow: hidden; padding: 7px 12px; border-radius: 8px; margin-bottom: 10px; }
    .section-num { width: 22px; height: 22px; border-radius: 6px; background: rgba(255,255,255,0.25); color: #fff; font-size: 10px; font-weight: 700; display: block; text-align: center; line-height: 22px; float: left; margin-right: 9px; margin-top: 1px; }
    .section-title { font-size: 12px; font-weight: 700; color: #ffffff; display: block; overflow: hidden; line-height: 24px; }
    .section-body { padding: 0 4px; }

    /* Sous-sections FAQ */
    .faq-item { margin-bottom: 12px; }
    .faq-q { font-size: 10.5px; font-weight: 700; color: #1f2937; margin-bottom: 4px; padding-left: 8px; border-left: 3px solid #9333ea; }
    .faq-a { font-size: 10px; color: #374151; padding-left: 11px; }

    /* Tableaux */
    table { width: 100%; border-collapse: collapse; margin: 6px 0; font-size: 9.5px; }
    th { background: #f3e8ff; color: #6b21a8; font-weight: 700; padding: 5px 7px; border: 1px solid #e9d5ff; text-align: left; }
    td { padding: 4px 7px; border: 1px solid #e5e7eb; vertical-align: top; }
    tr:nth-child(even) td { background: #fafafa; }

    /* Listes */
    ul, ol { margin: 4px 0 6px 16px; }
    li { margin-bottom: 2px; }
    strong { font-weight: 700; }
    em { color: #6b7280; }

    /* Tip / Warn */
    .tip { background: #f0fdf4; border-left: 3px solid #22c55e; padding: 6px 10px; border-radius: 0 6px 6px 0; margin: 5px 0; font-size: 9.5px; color: #166534; }
    .warn { background: #fffbeb; border-left: 3px solid #f59e0b; padding: 6px 10px; border-radius: 0 6px 6px 0; margin: 5px 0; font-size: 9.5px; color: #92400e; }

    /* Page break */
    .page-break { page-break-before: always; }

    /* Footer */
    .footer { margin-top: 24px; border-top: 1px solid #e5e7eb; padding-top: 8px; text-align: center; font-size: 9px; color: #9ca3af; }

    /* Couleurs sections */
    .s-demarrage { background: #f59e0b; }
    .s-caisse { background: #3b82f6; }
    .s-rdv { background: #8b5cf6; }
    .s-clients { background: #ec4899; }
    .s-finances { background: #10b981; }
    .s-stock { background: #f97316; }
    .s-vitrine { background: #06b6d4; }
    .s-equipe { background: #6366f1; }
    .s-notifs { background: #ef4444; }
    .s-multi { background: #9333ea; }
    .s-credits { background: #0891b2; }
    .s-parrainage { background: #d97706; }
</style>
</head>
<body>
<div class="page">

{{-- En-tête --}}
<div class="main-header">
    <div class="main-header-title">📱 Documentation Maëlya Gestion</div>
    <div class="main-header-sub">Guide complet — Gestionnaires d'établissements</div>
    <div class="main-header-meta">maelyagestion.com · Support WhatsApp · Juillet 2026 · 29 modules · v2</div>
</div>

{{-- Table des matières --}}
<div class="toc">
    <div class="toc-title">📋 Sommaire</div>
    <div class="toc-grid">
        <div class="toc-row">
            <div class="toc-cell"><strong>1.</strong> Prise en main rapide</div>
            <div class="toc-cell"><strong>7.</strong> Stock, Produits & Inventaire</div>
        </div>
        <div class="toc-row">
            <div class="toc-cell"><strong>2.</strong> Tous les modules (29)</div>
            <div class="toc-cell"><strong>8.</strong> Vitrine & Réservation en ligne</div>
        </div>
        <div class="toc-row">
            <div class="toc-cell"><strong>3.</strong> Plans & Tarifs</div>
            <div class="toc-cell"><strong>9.</strong> Équipe & Accès</div>
        </div>
        <div class="toc-row">
            <div class="toc-cell"><strong>4.</strong> Caisse & Ventes</div>
            <div class="toc-cell"><strong>10.</strong> Notifications</div>
        </div>
        <div class="toc-row">
            <div class="toc-cell"><strong>5.</strong> Rendez-vous & Agenda</div>
            <div class="toc-cell"><strong>11.</strong> Multi-établissements</div>
        </div>
        <div class="toc-row">
            <div class="toc-cell"><strong>6.</strong> Clients, Fidélité & Avis</div>
            <div class="toc-cell"><strong>12.</strong> Finances & Trésorerie</div>
        </div>
    </div>
</div>

{{-- Section 1 : Prise en main --}}
<div class="section">
    <div class="section-header s-demarrage">
        <span class="section-num">1</span>
        <span class="section-title">PRISE EN MAIN RAPIDE — Démarrer en 10 minutes</span>
    </div>
    <div class="section-body">
        <p><strong>Les 5 étapes essentielles :</strong></p>
        <ol>
            <li><strong>Configurer votre établissement</strong> → Mes établissements → Modifier (nom, logo, adresse, téléphone, horaires)</li>
            <li><strong>Ajouter vos prestations</strong> → Prestations → Nouvelle prestation (nom + prix)</li>
            <li><strong>Ajouter vos produits</strong> (optionnel) → Produits → Nouveau produit avec prix de vente et stock initial</li>
            <li><strong>Faire votre première vente</strong> → Caisse → sélectionner articles → mode de paiement → Valider</li>
            <li><strong>Ajouter votre équipe</strong> → Mon équipe → Inviter (si applicable)</li>
        </ol>
        <div class="tip">Commencez toujours par la Caisse — c'est le cœur de l'application. Tout se construit autour.</div>
    </div>
</div>

{{-- Section 2 : Tous les modules --}}
<div class="section page-break">
    <div class="section-header" style="background:#374151;">
        <span class="section-num">2</span>
        <span class="section-title">LES 29 MODULES — Vue d'ensemble complète</span>
    </div>
    <div class="section-body">
        <table>
            <thead>
                <tr><th>Module</th><th>Description</th><th>Plan minimum</th></tr>
            </thead>
            <tbody>
                <tr><td><strong>Scan code-barres</strong></td><td>Scan caméra intégré ou scanner externe USB/Bluetooth pour ajouter des produits au panier.</td><td>Essai / Premium+</td></tr>
                <tr><td><strong>Caisse</strong></td><td>Enregistrement des ventes en quelques secondes. Espèces, Wave, Orange Money, Carte, Mixte, Crédit.</td><td>Premium</td></tr>
                <tr><td><strong>Brouillons caisse</strong></td><td>Sauvegarder une vente en cours et la reprendre plus tard.</td><td>Premium</td></tr>
                <tr><td><strong>Ticket de caisse</strong></td><td>Ticket numérique partageable par WhatsApp, imprimable en PDF (A4).</td><td>Premium</td></tr>
                <tr><td><strong>Facture PDF</strong></td><td>Facture numérotée téléchargeable pour chaque vente.</td><td>Premium</td></tr>
                <tr><td><strong>Devis</strong></td><td>Création de devis personnalisés. Prestations/produits, remises, TVA. PDF pro partageable WhatsApp/Email. Transformation en facture.</td><td>Premium+</td></tr>
                <tr><td><strong>Factures</strong></td><td>Gestion des factures avec suivi des paiements. Paiements partiels, marquage payé, historique. Génération comptable automatique.</td><td>Premium+</td></tr>
                <tr><td><strong>Boutique en ligne</strong></td><td>Page web publique pour vendre vos produits 24h/24. Gestion des commandes (accepter, préparer, livrer). Frais et zones de livraison.</td><td>Premium (option 3 900 F)</td></tr>
                <tr><td><strong>Avoir (remboursement)</strong></td><td>Créer un avoir partiel sur une vente → code de réduction réutilisable.</td><td>Premium</td></tr>
                <tr><td><strong>Sondage satisfaction</strong></td><td>Envoi d'un lien de sondage post-achat par email ou WhatsApp. Note 1-5 étoiles + commentaire.</td><td>Premium</td></tr>
                <tr><td><strong>Tableau de bord</strong></td><td>CA jour/mois, nombre de ventes, nouveaux clients, alertes stock, anniversaires, graphique 30 jours.</td><td>Premium</td></tr>
                <tr><td><strong>Recherche globale</strong></td><td>Recherche simultanée dans clients, ventes, prestations, produits et RDV.</td><td>Premium</td></tr>
                <tr><td><strong>Clients</strong></td><td>Fiches clients avec historique complet des ventes, RDV, points fidélité, galerie photos.</td><td>Premium</td></tr>
                <tr><td><strong>Galerie photos</strong></td><td>Photos avant/après par client dans son dossier. Portfolio des résultats.</td><td>Premium</td></tr>
                <tr><td><strong>Anniversaires clients</strong></td><td>Alerte automatique + envoi d'un code cadeau le jour J.</td><td>Premium</td></tr>
                <tr><td><strong>Agenda / RDV</strong></td><td>Planning des rendez-vous avec prestations multiples. Confirmation email automatique au client.</td><td>Premium</td></tr>
                <tr><td><strong>Calendrier RDV</strong></td><td>Vue calendrier interactive (mois/semaine/jour/liste). Déplacement des RDV par glissement (drag & drop).</td><td>Premium</td></tr>
                <tr><td><strong>Réservation en ligne</strong></td><td>Les clients réservent depuis la vitrine publique 24h/24. Validation par l'administrateur.</td><td>Premium</td></tr>
                <tr><td><strong>Prestations</strong></td><td>Catalogue des services avec prix et durée. Catégorisation possible.</td><td>Premium</td></tr>
                <tr><td><strong>Produits</strong></td><td>Catalogue des produits vendables. Prix de vente, prix d'achat, stock, code-barre.</td><td>Premium</td></tr>
                <tr><td><strong>Stock</strong></td><td>Suivi automatique à chaque vente. Mouvements typés. Alerte seuil critique. Coût moyen pondéré.</td><td>Premium</td></tr>
                <tr><td><strong>Fournisseurs</strong></td><td>Carnet de fournisseurs avec contacts et historique des commandes.</td><td>Premium</td></tr>
                <tr><td><strong>Bons de commande</strong></td><td>Commander chez les fournisseurs. Réception partielle ou totale → stock mis à jour automatiquement.</td><td>Premium</td></tr>
                <tr><td><strong>Inventaire physique</strong></td><td>Compter le stock réel, détecter les écarts (pertes, vols, erreurs). Validation avec mise à jour.</td><td>Premium</td></tr>
                <tr><td><strong>Fidélité</strong></td><td>Points par tranche de vente. Paliers de récompense configurables. Codes cadeaux automatiques.</td><td>Premium</td></tr>
                <tr><td><strong>Codes de réduction</strong></td><td>Promotions en % ou montant fixe. Validité, limite d'usage, nominatif ou global.</td><td>Premium</td></tr>
                <tr><td><strong>Finances & Dépenses</strong></td><td>Enregistrer les charges par catégorie. Calcul automatique du bénéfice net. Export PDF et CSV.</td><td>Premium</td></tr>
                <tr><td><strong>Trésorerie prévisionnelle</strong></td><td>Projection entrées/sorties sur 7 à 90 jours. Basée sur les RDV futurs. Graphique jour par jour.</td><td>Premium</td></tr>
                <tr><td><strong>Page vitrine</strong></td><td>Page web publique (maelyagestion.com/e/slug). Prestations, photos, horaires, avis. QR code.</td><td>Premium</td></tr>
                <tr><td><strong>Avis clients</strong></td><td>Modération des avis post-sondage. Approuver → affiché sur vitrine. Rejeter → non publié.</td><td>Premium</td></tr>
                <tr><td><strong>Notifications</strong></td><td>Centre d'alertes en temps réel. Push web + email. RDV, stock, paiements, anniversaires, avis.</td><td>Premium</td></tr>
                <tr><td><strong>Mon équipe</strong></td><td>Comptes séparés pour les employés. Rôle limité (caisse, clients, RDV). Suivi par employé.</td><td>Premium</td></tr>
                <tr><td><strong>Multi-établissements</strong></td><td>Gérer plusieurs établissements depuis un seul compte. Données indépendantes par établissement.</td><td>Premium+</td></tr>
                <tr><td><strong>Comparatif</strong></td><td>Comparer CA, clients, top prestations entre ses établissements sur une même période.</td><td>Premium+</td></tr>
                <tr><td><strong>Vente à crédit</strong></td><td>Vente avec apport initial et échéancier. Suivi des paiements, rappels auto, fiche PDF.</td><td>Premium+</td></tr>
                <tr><td><strong>Parrainage</strong></td><td>Lien unique pour inviter d'autres propriétaires. Jours gratuits pour le parrain et le filleul.</td><td>Premium</td></tr>
            </tbody>
        </table>
    </div>
</div>

{{-- Section 3 : Plans --}}
<div class="section">
    <div class="section-header" style="background:#1d4ed8;">
        <span class="section-num">3</span>
        <span class="section-title">PLANS & TARIFS</span>
    </div>
    <div class="section-body">
        <table>
            <thead><tr><th>Plan</th><th>Prix / mois</th><th>Établissements</th><th>Fonctionnalités incluses</th></tr></thead>
            <tbody>
                <tr><td>Essai gratuit</td><td>0 FCFA (14 jours)</td><td>1</td><td>Accès complet à tout — sans carte bancaire, sans engagement</td></tr>
                <tr><td>Premium</td><td>4 900 FCFA</td><td>1</td><td>Caisse, clients, RDV, prestations, stock, produits, fidélité, finances, équipe, vitrine, réservation, codes réduction</td></tr>
                <tr><td>Premium+</td><td>9 900 FCFA</td><td>3 max</td><td>Premium + multi-établissements (3 max), 10 employés max, vente à crédit, comparatif, scanner</td></tr>
                <tr><td>Ultra</td><td>24 900 FCFA</td><td>Illimité</td><td>Premium+ + établissements illimités, employés illimités, support prioritaire</td></tr>
            </tbody>
        </table>
        <p style="margin-top:6px;"><strong>Paiement :</strong> Orange Money ou Wave. Pas de carte bancaire ni de compte bancaire requis.</p>
    </div>
</div>

{{-- Section 4 : Caisse --}}
<div class="section page-break">
    <div class="section-header s-caisse">
        <span class="section-num">4</span>
        <span class="section-title">CAISSE & VENTES</span>
    </div>
    <div class="section-body">
        <div class="faq-item">
            <div class="faq-q">Enregistrer une vente</div>
            <div class="faq-a">
                <p>Caisse → sélectionner prestations/produits dans le catalogue → associer un client (optionnel, recherche rapide) → appliquer code réduction si besoin → choisir mode de paiement (Espèces/Wave/OM/Carte/Mixte/Crédit) → Valider.</p>
                <div class="tip">Un numéro de vente unique est généré automatiquement (format V-XXXXXXXX).</div>
            </div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Modes de paiement disponibles</div>
            <div class="faq-a">Espèces · Mobile Money (Wave, Orange Money) · Carte bancaire · Mixte (combinaison) · Crédit (vente à crédit avec échéancier, Premium+).</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Envoyer un ticket au client</div>
            <div class="faq-a">Depuis le détail d'une vente : bouton WhatsApp (message pré-rempli avec lien ticket), ticket PDF imprimable (Premium) ou facture PDF numérotée (Premium).</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Annuler une vente</div>
            <div class="faq-a">Détail vente → bouton rouge "Annuler la vente" (Admin uniquement). Choisir un motif. Le stock est restauré et les points fidélité sont reversés.
                <div class="warn">Action irréversible. Préférez un avoir pour un remboursement partiel.</div>
            </div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Créer un avoir (remboursement partiel)</div>
            <div class="faq-a">Détail vente validée → "Créer un avoir" → saisir montant et motif. Un code de réduction est généré automatiquement, réutilisable lors d'une prochaine vente.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Envoyer un sondage de satisfaction</div>
            <div class="faq-a">Détail vente validée → section Sondage → "Générer lien sondage" → envoi par Email ou WhatsApp. Le client reçoit un lien unique pour noter de 1 à 5 étoiles avec commentaire.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Brouillons de caisse</div>
            <div class="faq-a">Caisse → onglet Brouillons. Sauvegarder une vente en cours et la reprendre plus tard. Utile si vous devez interrompre une transaction.</div>
        </div>
    </div>
</div>

{{-- Section 5 : RDV --}}
<div class="section">
    <div class="section-header s-rdv">
        <span class="section-num">5</span>
        <span class="section-title">RENDEZ-VOUS & AGENDA</span>
    </div>
    <div class="section-body">
        <div class="faq-item">
            <div class="faq-q">Créer un rendez-vous</div>
            <div class="faq-a">Rendez-vous → Nouveau RDV → renseigner le client, la date/heure, la durée, les prestations prévues et l'employé assigné (optionnel). Un email de confirmation est envoyé automatiquement si le client a un email.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Calendrier interactif</div>
            <div class="faq-a">Onglet Calendrier dans Rendez-vous. Vues mois, semaine, jour et liste. <strong>Glissez-déposez un RDV</strong> pour le déplacer (drag & drop). Cliquez pour voir ou modifier le détail. Encaissement possible directement depuis un RDV.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Réservation en ligne</div>
            <div class="faq-a">À activer dans Mes établissements. Les clients réservent depuis votre vitrine publique. La réservation apparaît dans l'agenda en "En attente" — vous confirmez ou refusez. Email automatique à chaque changement de statut.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Marquer un RDV comme terminé</div>
            <div class="faq-a">Bouton "Terminé" → le RDV est clos, un sondage de satisfaction est créé et envoyé automatiquement au client par email.</div>
        </div>
    </div>
</div>

{{-- Section 6 : Clients --}}
<div class="section page-break">
    <div class="section-header s-clients">
        <span class="section-num">6</span>
        <span class="section-title">CLIENTS, FIDÉLITÉ & AVIS</span>
    </div>
    <div class="section-body">
        <div class="faq-item">
            <div class="faq-q">Fiche client complète</div>
            <div class="faq-a">Chaque client a une fiche avec : coordonnées, historique des ventes et RDV, points de fidélité et historique détaillé, codes cadeaux reçus, galerie photos avant/après, crédits en cours.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Programme de fidélité</div>
            <div class="faq-a">Configurez dans Fidélité : tranche FCFA donnant droit à des points, seuil de récompense, type et valeur du cadeau. Chaque vente génère des points automatiquement. Un code cadeau est généré quand un client atteint un palier et le jour de son anniversaire.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Galerie photos avant/après</div>
            <div class="faq-a">Dans la fiche client → onglet Galerie. Téléchargez des photos par prestation. Visualisez en plein écran. Portfolio de vos résultats pour montrer vos transformations.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Carte de fidélité publique</div>
            <div class="faq-a">Chaque client a un token unique et un lien partageable. Téléchargez la carte PDF ou partagez le lien. Le client peut scanner un QR code pour voir son solde de points.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Gérer les avis clients</div>
            <div class="faq-a">Dans Avis clients (menu Admin). Approuver → l'avis avec sa note ⭐ s'affiche sur votre vitrine publique. Rejeter → non publié. Les avis approuvés rassurent les nouveaux clients.</div>
        </div>
    </div>
</div>

{{-- Section 7 : Stock --}}
<div class="section">
    <div class="section-header s-stock">
        <span class="section-num">7</span>
        <span class="section-title">STOCK, PRODUITS & INVENTAIRE</span>
    </div>
    <div class="section-body">
        <div class="faq-item">
            <div class="faq-q">Suivi automatique du stock</div>
            <div class="faq-a">Le stock est mis à jour automatiquement à chaque vente (sortie) et réception (entrée). Chaque produit a un seuil d'alerte configurable. Les mouvements sont tracés (vente, entrée, correction, annulation). Le coût moyen pondéré (CMP) est recalculé à chaque entrée.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Inventaire physique</div>
            <div class="faq-a">Stock → Inventaires → Nouvel inventaire. Saisissez les quantités comptées physiquement. L'application compare avec le stock théorique et affiche les écarts (positifs/négatifs) avec leur valeur. Validez pour mettre à jour les stocks.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Bons de commande fournisseurs</div>
            <div class="faq-a">Bons de commande → Nouveau bon → sélectionnez fournisseur et produits. Statuts : Brouillon → Envoyé → Reçu partiel → Reçu. À réception, le stock est mis à jour et le CMP recalculé automatiquement.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Marges et valorisation</div>
            <div class="faq-a">Le coût moyen pondéré permet de calculer la marge unitaire et le % de marge pour chaque produit. La valorisation totale du stock est visible dans Finances.</div>
        </div>
    </div>
</div>

{{-- Section 8 : Vitrine --}}
<div class="section page-break">
    <div class="section-header s-vitrine">
        <span class="section-num">8</span>
        <span class="section-title">VITRINE & RÉSERVATION EN LIGNE</span>
    </div>
    <div class="section-body">
        <div class="faq-item">
            <div class="faq-q">Activer la page vitrine publique</div>
            <div class="faq-a">Mes établissements → Modifier → activer "Page vitrine visible". Votre page est accessible à maelyagestion.com/e/votre-slug. Elle affiche vos prestations, photos, horaires et les avis approuvés.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">QR code de la vitrine</div>
            <div class="faq-a">Mes établissements → Modifier → section Vitrine → Télécharger le QR code (image PNG). Imprimez-le et affichez-le à l'entrée de votre établissement.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Réservation en ligne 24h/24</div>
            <div class="faq-a">Activer "Réservation en ligne" dans les paramètres. Les clients choisissent une prestation et un créneau depuis votre vitrine. Vous recevez une notification et validez depuis l'agenda.</div>
        </div>
    </div>
</div>

{{-- Section 9 : Équipe --}}
<div class="section">
    <div class="section-header s-equipe">
        <span class="section-num">9</span>
        <span class="section-title">ÉQUIPE & ACCÈS</span>
    </div>
    <div class="section-body">
        <div class="faq-item">
            <div class="faq-q">Ajouter un employé</div>
            <div class="faq-a">Mon équipe → Inviter → saisir l'email. La personne reçoit un lien pour créer son compte. Elle peut encaisser, voir les clients et RDV, gérer le stock, mais n'accède pas aux finances ni aux paramètres.</div>
        </div>
        <table>
            <thead><tr><th>Fonctionnalité</th><th>Admin</th><th>Employé</th></tr></thead>
            <tbody>
                <tr><td>Encaisser des ventes</td><td>✓</td><td>✓</td></tr>
                <tr><td>Voir toutes les ventes</td><td>✓</td><td>Ses ventes uniquement</td></tr>
                <tr><td>Accéder aux clients et RDV</td><td>✓</td><td>✓</td></tr>
                <tr><td>Gérer le stock (entrées, corrections)</td><td>✓</td><td>✓</td></tr>
                <tr><td>Finances & dépenses</td><td>✓</td><td>✗</td></tr>
                <tr><td>Annuler une vente / Créer un avoir</td><td>✓</td><td>✗</td></tr>
                <tr><td>Modifier paramètres établissement</td><td>✓</td><td>✗</td></tr>
                <tr><td>Gérer codes réduction et avoirs</td><td>✓</td><td>✗</td></tr>
            </tbody>
        </table>
    </div>
</div>

{{-- Section 10 : Notifications --}}
<div class="section">
    <div class="section-header s-notifs">
        <span class="section-num">10</span>
        <span class="section-title">NOTIFICATIONS</span>
    </div>
    <div class="section-body">
        <p>Vous êtes notifié automatiquement pour :</p>
        <table>
            <thead><tr><th>Événement</th><th>Quand</th></tr></thead>
            <tbody>
                <tr><td>Nouvelle réservation en ligne</td><td>Dès qu'un client réserve depuis votre vitrine</td></tr>
                <tr><td>RDV confirmé / annulé / reprogrammé</td><td>À chaque changement de statut</td></tr>
                <tr><td>Stock en alerte</td><td>Quand un produit passe sous le seuil d'alerte</td></tr>
                <tr><td>Anniversaire client</td><td>La veille de l'anniversaire</td></tr>
                <tr><td>Paiement abonnement</td><td>Confirmation de paiement reçue</td></tr>
                <tr><td>Nouvel avis client</td><td>Quand un sondage est répondu</td></tr>
                <tr><td>Bon de commande livré</td><td>Quand vous réceptionnez une commande</td></tr>
            </tbody>
        </table>
        <div class="tip">Cloche en haut à droite → "Voir toutes les notifications" pour l'historique complet avec pagination. Notifications push web disponibles (nécessite l'autorisation du navigateur).</div>
    </div>
</div>

{{-- Section 11 : Multi-établissements --}}
<div class="section page-break">
    <div class="section-header s-multi">
        <span class="section-num">11</span>
        <span class="section-title">MULTI-ÉTABLISSEMENTS (Premium+)</span>
    </div>
    <div class="section-body">
        <div class="faq-item">
            <div class="faq-q">Gérer plusieurs établissements</div>
            <div class="faq-a">Avec le plan Premium+, créez autant d'établissements que vous voulez depuis "Mes établissements". Basculez via le sélecteur en haut de la barre latérale. Chaque établissement a ses propres données (ventes, clients, stock, équipe).</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Comparatif entre établissements</div>
            <div class="faq-a">Menu Comparatif (visible si vous avez plusieurs établissements). Comparez CA, nombre de clients, ventes, top prestations sur une même période. Identifiez quel établissement performe le mieux.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Vente à crédit (Premium+)</div>
            <div class="faq-a">Mode Crédit à la caisse. Apport initial optionnel, échéancier hebdomadaire ou mensuel. Suivez les crédits en cours, enregistrez les paiements, téléchargez la fiche PDF. Rappels automatiques avant chaque échéance.</div>
        </div>
    </div>
</div>

{{-- Section 12 : Finances --}}
<div class="section">
    <div class="section-header s-finances">
        <span class="section-num">12</span>
        <span class="section-title">FINANCES & TRÉSORERIE PRÉVISIONNELLE</span>
    </div>
    <div class="section-body">
        <div class="faq-item">
            <div class="faq-q">Suivre les dépenses et le bénéfice net</div>
            <div class="faq-a">Finances → onglet Dépenses. Enregistrez toutes vos charges par catégorie (loyer, électricité, salaires, matériel…). Le bénéfice net = CA encaissé − total des dépenses. Export PDF mensuel et exports CSV disponibles.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Trésorerie prévisionnelle</div>
            <div class="faq-a">
                Finances → onglet Trésorerie prévisionnelle. Choisissez l'horizon (7 à 90 jours) :
                <ul>
                    <li><strong>Entrées prévues</strong> : basées sur vos RDV confirmés et en attente à venir, avec montant estimé</li>
                    <li><strong>Sorties prévues</strong> : moyenne quotidienne de vos dépenses sur 90 jours</li>
                    <li><strong>Solde prévisionnel</strong> : entrées − sorties attendues, jour par jour</li>
                </ul>
                Graphique en courbes et tableau détaillé des RDV futurs.
                <div class="tip">Utilisez cet outil en début de mois pour anticiper vos besoins de liquidités !</div>
            </div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Rapport par catégorie</div>
            <div class="faq-a">Finances → onglet Par catégorie. Visualisez quelles catégories de prestations génèrent le plus de CA. Orientez vos décisions commerciales.</div>
        </div>
    </div>
</div>

{{-- Footer --}}
<div class="footer">
    Maëlya Gestion · maelyagestion.com · Support WhatsApp &lt; 2h · Documentation complète v2 · Juillet 2026 · 29 modules
</div>

</div>
</body>
</html>
