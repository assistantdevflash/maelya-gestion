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

    /* Modules aperçu */
    .modules-grid { display: table; width: 100%; border-collapse: collapse; }
    .modules-row { display: table-row; }
    .module-cell { display: table-cell; padding: 4px 6px; border: 1px solid #e5e7eb; vertical-align: top; font-size: 9.5px; }
    .module-name { font-weight: 700; color: #374151; }
    .module-desc { color: #6b7280; font-size: 9px; }

    /* Plans */
    .plans-row td:first-child { font-weight: 700; }

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
    .s-parrainage { background: #d97706; }
</style>
</head>
<body>
<div class="page">

{{-- En-tête --}}
<div class="main-header">
    <div class="main-header-title">📱 Documentation Maëlya Gestion</div>
    <div class="main-header-sub">Guide complet — Propriétaires d'établissements</div>
    <div class="main-header-meta">maelyagestion.com · Support WhatsApp · Juin 2026 · 26 modules · v1</div>
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
            <div class="toc-cell"><strong>2.</strong> Tous les modules (26)</div>
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
            <li><strong>Ajouter vos produits</strong> (optionnel) → Produits → Nouveau produit avec prix et stock initial</li>
            <li><strong>Faire votre première vente</strong> → Caisse → sélectionner articles → mode de paiement → Valider</li>
            <li><strong>Inviter vos employées</strong> → Mon équipe → Inviter (si applicable)</li>
        </ol>
        <div class="tip">Commencez toujours par la Caisse — c'est le cœur de l'application. Tout se construit autour.</div>
    </div>
</div>

{{-- Section 2 : Tous les modules --}}
<div class="section page-break">
    <div class="section-header" style="background:#374151;">
        <span class="section-num">2</span>
        <span class="section-title">LES 26 MODULES — Vue d'ensemble complète</span>
    </div>
    <div class="section-body">
        <table>
            <thead>
                <tr><th>Module</th><th>Description</th><th>Plan minimum</th></tr>
            </thead>
            <tbody>
                <tr><td><strong>Caisse</strong></td><td>Enregistrement des ventes en quelques secondes. Cash, Wave, Orange Money, paiement mixte.</td><td>Basic</td></tr>
                <tr><td><strong>Brouillons caisse</strong></td><td>Sauvegarder une vente en cours, la reprendre plus tard.</td><td>Basic</td></tr>
                <tr><td><strong>Ticket de caisse</strong></td><td>Ticket numérique partageable, imprimable en PDF.</td><td>Premium</td></tr>
                <tr><td><strong>Facture PDF</strong></td><td>Facture numérotée téléchargeable pour chaque vente.</td><td>Premium</td></tr>
                <tr><td><strong>Avoir (remboursement)</strong></td><td>Créer un avoir partiel sur une vente → code de réduction réutilisable.</td><td>Basic</td></tr>
                <tr><td><strong>Sondage satisfaction</strong></td><td>Envoi d'un lien de sondage post-achat par email ou WhatsApp depuis le détail vente.</td><td>Basic</td></tr>
                <tr><td><strong>Tableau de bord</strong></td><td>CA jour/semaine/mois, meilleures prestations, alertes stock, activité récente.</td><td>Basic</td></tr>
                <tr><td><strong>Clients</strong></td><td>Fiches clients avec historique complet des visites, achats, points fidélité.</td><td>Basic</td></tr>
                <tr><td><strong>Galerie photos</strong></td><td>Photos avant/après par cliente dans son dossier. Portfolio des transformations.</td><td>Basic</td></tr>
                <tr><td><strong>Anniversaires clients</strong></td><td>Alerte automatique + envoi d'un code cadeau le jour J.</td><td>Basic</td></tr>
                <tr><td><strong>Agenda / RDV</strong></td><td>Planning des rendez-vous. Confirmation email automatique au client.</td><td>Basic</td></tr>
                <tr><td><strong>Calendrier RDV</strong></td><td>Vue calendrier interactive. Déplacement des RDV par glissement (drag & drop).</td><td>Basic</td></tr>
                <tr><td><strong>Réservation en ligne</strong></td><td>Les clients réservent depuis la vitrine 24h/24. Validation admin requise.</td><td>Premium</td></tr>
                <tr><td><strong>Prestations</strong></td><td>Catalogue des services avec prix. Catégorisation possible.</td><td>Basic</td></tr>
                <tr><td><strong>Produits</strong></td><td>Catalogue des produits vendables en salon. Prix, stock, catégorie.</td><td>Basic</td></tr>
                <tr><td><strong>Stock</strong></td><td>Suivi automatique. Mise à jour à chaque vente. Alerte rupture.</td><td>Premium</td></tr>
                <tr><td><strong>Fournisseurs</strong></td><td>Carnet de fournisseurs avec contacts et historique des commandes.</td><td>Premium</td></tr>
                <tr><td><strong>Bons de commande</strong></td><td>Commander chez les fournisseurs. Réceptionner = stock mis à jour auto.</td><td>Premium</td></tr>
                <tr><td><strong>Inventaire physique</strong></td><td>Compter le stock réel et détecter les écarts (pertes, vols, erreurs).</td><td>Premium</td></tr>
                <tr><td><strong>Fidélité</strong></td><td>Points par vente. Paliers de récompense. Codes cadeaux automatiques.</td><td>Premium</td></tr>
                <tr><td><strong>Codes de réduction</strong></td><td>Promotions en % ou montant fixe. Durée limitée et nombre d'utilisations.</td><td>Premium</td></tr>
                <tr><td><strong>Finances & Dépenses</strong></td><td>Enregistrer les charges, calculer le bénéfice net. Export PDF rapport mensuel.</td><td>Premium</td></tr>
                <tr><td><strong>Trésorerie prévisionnelle</strong></td><td>Projection entrées/sorties sur 7 à 90 jours. Graphique jour par jour.</td><td>Premium</td></tr>
                <tr><td><strong>Page vitrine</strong></td><td>Page web publique avec prestations, photos, horaires, avis. QR code téléchargeable.</td><td>Premium</td></tr>
                <tr><td><strong>Avis clients</strong></td><td>Modération des avis post-sondage. Les avis approuvés s'affichent sur la vitrine.</td><td>Basic</td></tr>
                <tr><td><strong>Notifications</strong></td><td>Centre d'alertes : RDV, stock, paiements, anniversaires, avis. Cloche en temps réel.</td><td>Basic</td></tr>
                <tr><td><strong>Mon équipe</strong></td><td>Comptes séparés pour les employées avec permissions limitées.</td><td>Premium</td></tr>
                <tr><td><strong>Multi-établissements</strong></td><td>Gérer plusieurs salons depuis un seul compte. Basculer entre les établissements.</td><td>Premium+</td></tr>
                <tr><td><strong>Comparatif instituts</strong></td><td>Comparer CA, clients, top prestations entre ses établissements.</td><td>Premium+</td></tr>
                <tr><td><strong>Parrainage</strong></td><td>Lien unique pour inviter d'autres gérantes. 1 filleul payant = 1 mois gratuit.</td><td>Basic</td></tr>
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
        <table class="plans-row">
            <thead><tr><th>Plan</th><th>Prix / mois</th><th>Établissements</th><th>Fonctionnalités incluses</th></tr></thead>
            <tbody>
                <tr><td>Essai gratuit</td><td>0 FCFA (14 jours)</td><td>1</td><td>Accès complet à tout — sans carte bancaire</td></tr>
                <tr><td>Basic</td><td>2 000 FCFA</td><td>1</td><td>Caisse, clients, RDV, prestations, avis, notifications</td></tr>
                <tr><td>Premium</td><td>4 900 FCFA</td><td>1</td><td>Basic + stock, fidélité, finances, équipe, vitrine, réservation</td></tr>
                <tr><td>Premium+</td><td>9 900 FCFA</td><td>Illimité</td><td>Premium + multi-établissements, comparatif</td></tr>
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
                <p>Caisse → sélectionner prestations/produits → choisir client (optionnel) → appliquer code réduction si besoin → choisir mode de paiement (Cash/Wave/OM/Mixte) → Valider.</p>
                <div class="tip">Un ticket est généré automatiquement et peut être partagé par WhatsApp.</div>
            </div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Envoyer un ticket au client</div>
            <div class="faq-a">Depuis le détail d'une vente (Ventes → cliquer la vente) : bouton WhatsApp (message pré-rempli avec lien ticket) ou PDF imprimable (Premium).</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Annuler une vente</div>
            <div class="faq-a">Dans le détail de la vente → bouton rouge "Annuler la vente" (Admin uniquement). Choisir un motif. Le stock est restauré et les points fidélité reversés.
                <div class="warn">Action irréversible. Préférez un avoir pour un remboursement partiel.</div>
            </div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Créer un avoir (remboursement partiel)</div>
            <div class="faq-a">Dans le détail d'une vente validée → "Créer un avoir" → saisir le montant et le motif. Un code de réduction est généré pour la prochaine visite.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Envoyer un sondage de satisfaction</div>
            <div class="faq-a">Dans le détail d'une vente validée → section "Sondage" → "Générer lien sondage" puis envoi par Email ou WhatsApp. Le client clique le lien et donne une note de 1 à 5 étoiles avec commentaire.</div>
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
            <div class="faq-a">Rendez-vous → Nouveau RDV → renseigner client, date/heure, prestations prévues. Un email de confirmation est envoyé automatiquement si le client a un email.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Calendrier interactif</div>
            <div class="faq-a">Onglet Calendrier dans Rendez-vous. Vue semaine ou mois. <strong>Glissez un RDV avec le doigt pour le déplacer</strong> (drag & drop). Cliquez pour voir le détail.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Réservation en ligne</div>
            <div class="faq-a">À activer dans Mes établissements. Les clients réservent depuis votre vitrine publique. La réservation apparaît dans l'agenda en "En attente" — vous confirmez ou refusez. Un email est envoyé automatiquement.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Marquer un RDV comme terminé</div>
            <div class="faq-a">Bouton "Terminé" → le RDV est clos + un sondage de satisfaction est créé et envoyé automatiquement au client.</div>
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
            <div class="faq-q">Fiche client</div>
            <div class="faq-a">Chaque client a une fiche avec : informations de contact, historique des ventes et RDV, points de fidélité accumulés, codes cadeaux utilisés, galerie photos avant/après.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Programme de fidélité</div>
            <div class="faq-a">Configurez les paliers dans Fidélité. Chaque vente génère des points. Quand une cliente atteint un palier, un code cadeau lui est envoyé automatiquement par email. Idem pour son anniversaire.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Galerie photos avant/après</div>
            <div class="faq-a">Dans la fiche client → onglet Galerie. Uploadez des photos pour chaque prestation. Visualisez les transformations, montrez les résultats pour fidéliser.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Gérer les avis clients</div>
            <div class="faq-a">Dans Avis clients (menu Admin). Approuver → l'avis avec sa note ⭐ s'affiche sur votre vitrine publique. Rejeter → non publié. Les avis approuvés rassurent les nouvelles clientes.</div>
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
            <div class="faq-a">Le stock est mis à jour automatiquement à chaque vente de produit. Vous recevez une notification quand un produit passe sous son seuil d'alerte. Définissez ce seuil dans la fiche produit.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Inventaire physique</div>
            <div class="faq-a">Stock → Inventaires → Nouvel inventaire. Saisissez les quantités comptées physiquement. L'application compare avec le stock théorique et affiche les écarts (pertes, erreurs, vols potentiels).</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Commander chez les fournisseurs</div>
            <div class="faq-a">Stock → Bons de commande → Nouveau bon. Sélectionnez fournisseur et produits. Quand la livraison arrive : Réceptionner → le stock est mis à jour automatiquement.</div>
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
            <div class="faq-q">Activer ma page publique</div>
            <div class="faq-a">Mes établissements → Modifier → activer "Page vitrine visible". Votre page affiche vos prestations, photos, horaires et avis clients approuvés. URL : maelyagestion.com/salon/votre-slug</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">QR code de la vitrine</div>
            <div class="faq-a">Mes établissements → Modifier → section Vitrine → Télécharger le QR code. Imprimez et affichez à l'entrée de votre salon. Les clients scannent et voient vos services.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Réservation en ligne 24h/24</div>
            <div class="faq-a">Activer "Réservation en ligne" dans les paramètres de l'établissement. Les clients choisissent une prestation et un créneau. Vous recevez une notification et validez depuis l'agenda.</div>
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
            <div class="faq-q">Inviter une employée</div>
            <div class="faq-a">Mon équipe → Inviter une employée → saisir son email. Elle reçoit un lien pour créer son compte. Elle peut encaisser des ventes mais ne voit pas les finances globales ni les paramètres.</div>
        </div>
        <table>
            <thead><tr><th>Fonctionnalité</th><th>Admin</th><th>Employée</th></tr></thead>
            <tbody>
                <tr><td>Encaisser des ventes</td><td>✓</td><td>✓</td></tr>
                <tr><td>Voir toutes les ventes</td><td>✓</td><td>Ses ventes uniquement</td></tr>
                <tr><td>Finances & dépenses</td><td>✓</td><td>✗</td></tr>
                <tr><td>Annuler une vente</td><td>✓</td><td>✗</td></tr>
                <tr><td>Paramètres salon</td><td>✓</td><td>✗</td></tr>
                <tr><td>Gestion clients/stock</td><td>✓</td><td>✗</td></tr>
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
                <tr><td>RDV confirmé / annulé</td><td>À chaque changement de statut</td></tr>
                <tr><td>Stock en alerte</td><td>Quand un produit passe sous le seuil d'alerte</td></tr>
                <tr><td>Anniversaire client</td><td>La veille de l'anniversaire d'une cliente</td></tr>
                <tr><td>Paiement abonnement</td><td>Confirmation de paiement reçue</td></tr>
                <tr><td>Nouvel avis client</td><td>Quand un sondage est répondu</td></tr>
                <tr><td>Bon de commande livré</td><td>Quand vous réceptionnez une commande</td></tr>
            </tbody>
        </table>
        <div class="tip">Cliquez sur la cloche en haut à droite → "Voir toutes les notifications" pour accéder à l'historique complet avec pagination.</div>
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
            <div class="faq-q">Gérer plusieurs salons</div>
            <div class="faq-a">Avec le plan Premium+, créez autant d'établissements que vous voulez depuis "Mes établissements". Basculez entre les salons via le sélecteur en haut de la barre latérale. Chaque salon a ses propres données.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Comparatif entre établissements</div>
            <div class="faq-a">Menu Comparatif instituts (visible uniquement si vous avez plusieurs établissements). Comparez CA, nombre de clients, ventes, top prestations sur une même période. Identifiez quel salon performe le mieux.</div>
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
            <div class="faq-a">Finances → onglet Dépenses. Enregistrez toutes vos charges (loyer, électricité, salaires, matériel…). Le bénéfice net = CA encaissé - total des dépenses. Export PDF mensuel disponible.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Trésorerie prévisionnelle</div>
            <div class="faq-a">
                Finances → onglet Trésorerie prévisionnelle. Choisissez l'horizon (7 à 90 jours) :
                <ul>
                    <li><strong>Entrées prévues</strong> : basées sur vos RDV confirmés et en attente à venir</li>
                    <li><strong>Sorties prévues</strong> : moyenne de vos dépenses sur les 90 derniers jours</li>
                    <li><strong>Solde prévisionnel</strong> : entrées - sorties attendues</li>
                </ul>
                Un graphique en courbes montre l'évolution jour par jour. Le tableau liste chaque RDV futur avec le montant estimé.
                <div class="tip">Utilisez cet outil en début de mois pour anticiper vos besoins de liquidités !</div>
            </div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Rapport par catégorie</div>
            <div class="faq-a">Finances → onglet Par catégorie. Voyez quelles catégories de prestations génèrent le plus de chiffre d'affaires. Orientez vos décisions commerciales en conséquence.</div>
        </div>
    </div>
</div>

{{-- Footer --}}
<div class="footer">
    Maëlya Gestion · maelyagestion.com · Support WhatsApp réponse &lt; 2h · Documentation complète v1 · Juin 2026 · 26 modules · Réservé aux propriétaires d'établissements
</div>

</div>
</body>
</html>
