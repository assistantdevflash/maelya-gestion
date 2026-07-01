<x-dashboard-layout>

<style>
.faq-btn { width:100%; display:flex; align-items:center; gap:12px; padding:16px 20px; text-align:left; transition:background .15s; cursor:pointer; background:transparent; border:none; }
.faq-btn:hover { background:rgba(0,0,0,.03); }
.dark .faq-btn:hover { background:rgba(255,255,255,.04); }
.faq-question { font-weight:600; font-size:.875rem; flex:1; color:#111827; text-align:left; }
.dark .faq-question { color:#f9fafb; }
.faq-answer { padding:4px 20px 20px 52px; font-size:.875rem; line-height:1.7; color:#374151; }
.dark .faq-answer { color:#d1d5db; }
.faq-answer p { margin:0 0 10px; }
.faq-answer ul, .faq-answer ol { margin:6px 0 12px 20px; }
.faq-answer li { margin-bottom:4px; }
.faq-answer strong { color:#111827; font-weight:700; }
.dark .faq-answer strong { color:#f9fafb; }
.faq-answer .tip { background:#f0fdf4; border-left:3px solid #22c55e; padding:8px 12px; border-radius:0 8px 8px 0; margin:8px 0; }
.dark .faq-answer .tip { background:rgba(34,197,94,.1); }
.faq-answer .warn { background:#fffbeb; border-left:3px solid #f59e0b; padding:8px 12px; border-radius:0 8px 8px 0; margin:8px 0; }
.dark .faq-answer .warn { background:rgba(245,158,11,.1); }
.faq-cat-badge { display:inline-flex; align-items:center; gap:6px; padding:4px 12px; border-radius:999px; font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; }
@media (max-width:640px) {
    .faq-btn { padding:13px 14px; }
    .faq-question { font-size:.8125rem; }
    .faq-answer { padding:4px 14px 16px 44px; font-size:.8125rem; }
}
</style>

{{-- En-tête --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Aide & FAQ</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Toutes les réponses à vos questions sur Maëlya Gestion</p>
    </div>
    <a href="{{ route('dashboard.faq.pdf') }}"
       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white shadow-lg transition-opacity hover:opacity-90 flex-shrink-0"
       style="background: linear-gradient(135deg, #9333ea, #ec4899);">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        Télécharger la documentation PDF
    </a>
</div>

{{-- Barre de recherche --}}
<div class="relative mb-6" x-data="{ q: '' }">
    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
    </svg>
    <input type="text" x-model="q" @input="filterFaq(q)"
           placeholder="Rechercher dans la FAQ..."
           class="w-full pl-11 pr-4 py-3 rounded-xl border border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-400">
</div>

@php
$categories = [
    [
        'id'     => 'demarrage',
        'icon'   => 'M13 10V3L4 14h7v7l9-11h-7z',
        'color'  => '#f59e0b',
        'bg'     => '#fffbeb',
        'label'  => 'Prise en main',
        'faqs'   => [
            [
                'q' => 'Comment démarrer rapidement avec Maëlya ?',
                'a' => '<p>Voici les 5 étapes pour être opérationnel en quelques minutes :</p>
                <ol>
                    <li><strong>Configurez votre établissement</strong> → Mes établissements → Modifier (nom, logo, adresse, téléphone, horaires)</li>
                    <li><strong>Ajoutez vos prestations</strong> → Prestations → Nouvelle prestation (nom + prix)</li>
                    <li><strong>Ajoutez vos produits</strong> (optionnel) → Produits → Nouveau produit (nom, prix de vente, stock initial)</li>
                    <li><strong>Faites votre première vente</strong> → Caisse → sélectionnez les articles → choisissez le mode de paiement → Valider</li>
                    <li><strong>Ajoutez votre équipe</strong> (si applicable) → Mon équipe → Inviter</li>
                </ol>
                <div class="tip">Commencez par la Caisse — c\'est le cœur de l\'application !</div>'
            ],
            [
                'q' => 'Comment configurer mon établissement (logo, nom, adresse) ?',
                'a' => '<p>Allez dans <strong>Mes établissements</strong> depuis le menu. Cliquez sur votre établissement puis <strong>Modifier</strong>. Vous pouvez y renseigner :</p>
                <ul>
                    <li>Nom de l\'établissement et description</li>
                    <li>Logo (cliquez sur l\'icône d\'upload)</li>
                    <li>Adresse, téléphone, email</li>
                    <li>Horaires d\'ouverture</li>
                    <li>Activer/désactiver la page vitrine publique et la réservation en ligne</li>
                </ul>
                <div class="tip">Votre logo et vos informations apparaissent sur les tickets, factures et votre page vitrine publique.</div>'
            ],
            [
                'q' => 'Comment changer de plan / m\'abonner ?',
                'a' => '<p>Allez dans <strong>Abonnement</strong> dans le menu. Choisissez votre plan et réglez par <strong>Orange Money ou Wave</strong>. Aucune carte bancaire nécessaire.</p>
                <ul>
                    <li><strong>Essai gratuit</strong> (14 jours) : accès complet à toutes les fonctionnalités, sans engagement</li>
                    <li><strong>Basic</strong> (2 000 FCFA/mois) : caisse, clients, RDV, prestations, parrainage, notifications</li>
                    <li><strong>Premium</strong> (4 900 FCFA/mois) : tout Basic + stock, produits, fidélité, finances, équipe, vitrine, réservation en ligne, codes de réduction</li>
                    <li><strong>Premium+</strong> (9 900 FCFA/mois) : tout Premium + multi-établissements, vente à crédit, comparatif, scanner code-barres (caméra + scanner externe)</li>
                </ul>'
            ],
        ]
    ],
    [
        'id'     => 'caisse',
        'icon'   => 'M3 3h2l.4 2M7 13h10l4-8H5.4m1.6 8a2 2 0 100 4 2 2 0 000-4zm10 0a2 2 0 100 4 2 2 0 000-4z',
        'color'  => '#3b82f6',
        'bg'     => '#eff6ff',
        'label'  => 'Caisse & Ventes',
        'faqs'   => [
            [
                'q' => 'Comment enregistrer une vente ?',
                'a' => '<p>Allez dans <strong>Caisse</strong> :</p>
                <ol>
                    <li>Sélectionnez les prestations ou produits dans le catalogue</li>
                    <li>Associez un client (optionnel — recherche rapide ou création à la volée)</li>
                    <li>Appliquez un code de réduction si besoin</li>
                    <li>Choisissez le mode de paiement : <strong>Espèces, Wave, Orange Money, Carte, Mixte</strong> ou <strong>Crédit</strong></li>
                    <li>Cliquez <strong>Valider la vente</strong></li>
                </ol>
                <div class="tip">Un numéro de vente unique est généré automatiquement (format V-XXXXXXXX). Le ticket est partageable par WhatsApp.</div>'
            ],
            [
                'q' => 'Comment envoyer un ticket de caisse au client ?',
                'a' => '<p>Depuis le <strong>détail d\'une vente</strong> (Ventes → cliquer sur une vente) :</p>
                <ul>
                    <li><strong>Par WhatsApp</strong> : le bouton "Envoyer ticket par WhatsApp" crée un message pré-rempli avec le lien du ticket. Disponible si le client a un numéro de téléphone.</li>
                    <li><strong>Ticket PDF imprimable</strong> : bouton "Télécharger le ticket" — format A4 avec logo et détail du panier (disponible avec le plan Premium).</li>
                    <li><strong>Facture PDF</strong> : bouton "Télécharger la facture" avec numéro de facture auto-généré (Premium).</li>
                </ul>'
            ],
            [
                'q' => 'Comment annuler une vente ?',
                'a' => '<p>Dans le <strong>détail de la vente</strong>, cliquez sur <strong>Annuler la vente</strong> (bouton rouge, réservé aux administrateurs). Vous devez choisir un motif d\'annulation. Le stock des produits est restauré automatiquement et les points de fidélité sont reversés.</p>
                <div class="warn">Cette action est irréversible. Préférez créer un avoir si vous souhaitez un remboursement partiel.</div>'
            ],
            [
                'q' => 'Comment créer un avoir (remboursement partiel) ?',
                'a' => '<p>Dans le <strong>détail d\'une vente validée</strong>, cliquez sur <strong>Créer un avoir</strong>. Saisissez le montant et le motif. Un code de réduction est généré automatiquement, utilisable par le client lors d\'une prochaine vente.</p>'
            ],
            [
                'q' => 'Comment envoyer un sondage de satisfaction après une vente ?',
                'a' => '<p>Dans le <strong>détail d\'une vente validée</strong>, section "Sondage satisfaction" :</p>
                <ul>
                    <li>Si aucun lien n\'existe : cliquez <strong>Générer lien sondage</strong></li>
                    <li>Puis envoyez par <strong>Email</strong> (si le client a une adresse email) ou par <strong>WhatsApp</strong></li>
                    <li>Le client reçoit un lien unique pour noter son expérience de 1 à 5 étoiles et laisser un commentaire</li>
                </ul>
                <div class="tip">Pour les rendez-vous, le sondage est créé et envoyé automatiquement quand vous marquez le RDV comme "Terminé".</div>'
            ],
            [
                'q' => 'Comment utiliser les brouillons de caisse ?',
                'a' => '<p>Sur la page <strong>Caisse</strong>, cliquez sur <strong>Brouillons</strong>. Vous pouvez sauvegarder une vente en cours et la reprendre plus tard — utile si vous devez interrompre une transaction ou si un client hésite.</p>'
            ],
            [
                'q' => 'Comment scanner les codes-barres des produits ?',
                'a' => '<p>Disponible pendant l\'<strong>essai gratuit</strong> et avec le plan <strong>Premium+</strong>. Deux options :</p>
                <ul>
                    <li><strong>Caméra du téléphone</strong> : dans l\'onglet Produits, cliquez sur "Scanner" → visez le code-barres → le produit s\'ajoute automatiquement au panier</li>
                    <li><strong>Scanner externe USB/Bluetooth</strong> : branchez votre scanner, placez le curseur dans le champ "Scanner externe ou saisir code-barres", scannez → le produit s\'ajoute automatiquement</li>
                </ul>
                <div class="tip">Les scanners externes fonctionnent comme un clavier : ils tapent le code et appuient sur Entrée automatiquement.</div>'
            ],
            [
                'q' => 'Quels modes de paiement sont disponibles ?',
                'a' => '<p>La caisse accepte 5 modes de paiement :</p>
                <ul>
                    <li><strong>Espèces</strong> : paiement en cash</li>
                    <li><strong>Mobile Money</strong> : Wave ou Orange Money</li>
                    <li><strong>Carte bancaire</strong> : paiement par carte</li>
                    <li><strong>Mixte</strong> : combinaison espèces + mobile money + carte</li>
                    <li><strong>Crédit</strong> : vente à crédit avec apport initial et échéancier (Premium+)</li>
                </ul>'
            ],
        ]
    ],
    [
        'id'     => 'rdv',
        'icon'   => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'color'  => '#8b5cf6',
        'bg'     => '#f5f3ff',
        'label'  => 'Rendez-vous & Agenda',
        'faqs'   => [
            [
                'q' => 'Comment créer un rendez-vous ?',
                'a' => '<p>Allez dans <strong>Rendez-vous</strong> → <strong>Nouveau RDV</strong>. Renseignez :</p>
                <ul>
                    <li>Client (existant dans la liste ou à créer)</li>
                    <li>Date et heure de début</li>
                    <li>Durée estimée</li>
                    <li>Prestations prévues</li>
                    <li>Employé assigné (optionnel)</li>
                    <li>Notes éventuelles</li>
                </ul>
                <div class="tip">Un email de confirmation est envoyé automatiquement au client s\'il a une adresse email renseignée.</div>'
            ],
            [
                'q' => 'Comment utiliser le calendrier interactif ?',
                'a' => '<p>Dans <strong>Rendez-vous</strong>, cliquez sur l\'onglet <strong>Calendrier</strong>. Vous visualisez tous vos RDV en vue semaine, mois, jour ou liste. Vous pouvez <strong>déplacer un rendez-vous en le glissant-déposant</strong> (drag & drop) vers un autre créneau. Cliquez sur un RDV pour voir ou modifier son détail.</p>'
            ],
            [
                'q' => 'Comment marquer un RDV comme terminé ?',
                'a' => '<p>Dans la liste des rendez-vous ou dans le détail d\'un RDV, cliquez sur le bouton <strong>Terminé</strong>. Cela :</p>
                <ol>
                    <li>Marque le RDV comme terminé dans le planning</li>
                    <li>Crée automatiquement un lien de sondage de satisfaction</li>
                    <li>Envoie automatiquement l\'email de sondage au client (s\'il a un email)</li>
                </ol>
                <div class="tip">Vous pouvez aussi <strong>encaisser directement</strong> depuis un RDV : le panier est pré-rempli avec les prestations du rendez-vous.</div>'
            ],
            [
                'q' => 'Comment fonctionnent les réservations en ligne ?',
                'a' => '<p>Les clients peuvent réserver depuis votre <strong>page vitrine publique</strong> (si activée). La réservation arrive dans votre agenda avec le statut "En attente de confirmation". Vous recevez une notification et pouvez <strong>confirmer ou refuser</strong> la réservation. Un email est envoyé automatiquement au client à chaque changement de statut.</p>'
            ],
        ]
    ],
    [
        'id'     => 'clients',
        'icon'   => 'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75M13 7a4 4 0 11-8 0 4 4 0 018 0z',
        'color'  => '#ec4899',
        'bg'     => '#fdf2f8',
        'label'  => 'Clients & Fidélité',
        'faqs'   => [
            [
                'q' => 'Comment ajouter un client ?',
                'a' => '<p>Allez dans <strong>Clients</strong> → <strong>Nouveau client</strong>. Renseignez le prénom, nom, téléphone, email et date d\'anniversaire. La date d\'anniversaire permet d\'envoyer automatiquement un code cadeau le jour J.</p>'
            ],
            [
                'q' => 'Que contient la fiche d\'un client ?',
                'a' => '<p>Chaque client possède une fiche complète avec :</p>
                <ul>
                    <li>Informations de contact (nom, téléphone, email, adresse)</li>
                    <li>Historique complet des ventes et rendez-vous</li>
                    <li>Points de fidélité accumulés et historique détaillé</li>
                    <li>Codes cadeaux reçus et utilisés</li>
                    <li>Galerie photos avant/après</li>
                    <li>Crédits en cours (si applicable)</li>
                </ul>'
            ],
            [
                'q' => 'Comment fonctionne le programme de fidélité ?',
                'a' => '<p>Chaque vente génère automatiquement des <strong>points de fidélité</strong> pour le client. Depuis <strong>Fidélité</strong>, vous configurez :</p>
                <ul>
                    <li><strong>La tranche FCFA</strong> : montant de vente qui donne droit à des points (ex: 1 000 FCFA = X points)</li>
                    <li><strong>Le seuil de récompense</strong> : nombre de points pour déclencher un cadeau</li>
                    <li><strong>Le type et la valeur de la récompense</strong> : pourcentage de réduction ou montant fixe</li>
                </ul>
                <div class="tip">Un code cadeau est généré automatiquement quand un client atteint un palier, et aussi le jour de son anniversaire !</div>'
            ],
            [
                'q' => 'Comment utiliser la galerie photos avant/après ?',
                'a' => '<p>Dans la <strong>fiche d\'un client</strong>, cliquez sur l\'onglet <strong>Galerie photos</strong>. Vous pouvez télécharger des photos avant/après pour chaque prestation réalisée. Les photos sont classées par date et vous pouvez les visualiser en plein écran. C\'est votre portfolio pour montrer vos résultats.</p>'
            ],
            [
                'q' => 'Comment gérer les avis clients ?',
                'a' => '<p>Dans <strong>Avis clients</strong> (menu accessible aux administrateurs), vous voyez tous les avis soumis via les sondages de satisfaction. Vous pouvez :</p>
                <ul>
                    <li><strong>Approuver</strong> : l\'avis et sa note en étoiles apparaissent sur votre page vitrine publique</li>
                    <li><strong>Rejeter</strong> : l\'avis n\'est pas publié</li>
                </ul>
                <div class="tip">Les avis approuvés avec les étoiles rassurent les nouveaux clients qui visitent votre page publique.</div>'
            ],
            [
                'q' => 'Comment partager la carte de fidélité d\'un client ?',
                'a' => '<p>Chaque client possède un <strong>token de fidélité unique</strong> (lien partageable). Depuis la fiche client, vous pouvez télécharger la carte au format PDF ou copier le lien public. Le client peut scanner un QR code pour consulter son solde de points.</p>'
            ],
        ]
    ],
    [
        'id'     => 'finances',
        'icon'   => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        'color'  => '#10b981',
        'bg'     => '#ecfdf5',
        'label'  => 'Finances & Trésorerie',
        'faqs'   => [
            [
                'q' => 'Comment suivre mes dépenses et mon bénéfice net ?',
                'a' => '<p>Allez dans <strong>Finances</strong>. L\'onglet <strong>Dépenses</strong> vous permet d\'enregistrer toutes vos charges (loyer, électricité, salaires, matériel, etc.) par catégorie et par date. Le tableau de bord financier calcule automatiquement votre <strong>bénéfice net = chiffre d\'affaires encaissé − total des dépenses</strong>.</p>
                <div class="tip">Vous pouvez exporter un rapport PDF mensuel ou annuel pour votre comptable, ainsi que des exports CSV des ventes et des dépenses.</div>'
            ],
            [
                'q' => 'Comment fonctionne la Trésorerie prévisionnelle ?',
                'a' => '<p>Dans <strong>Finances</strong>, cliquez sur l\'onglet <strong>Trésorerie prévisionnelle</strong>. Choisissez un horizon (7, 14, 30, 60 ou 90 jours) :</p>
                <ul>
                    <li><strong>Entrées prévues</strong> : basées sur vos rendez-vous confirmés et en attente à venir, avec le montant estimé des prestations</li>
                    <li><strong>Sorties prévues</strong> : basées sur la moyenne quotidienne de vos dépenses des 90 derniers jours</li>
                    <li><strong>Solde prévisionnel</strong> : différence entre les entrées et les sorties attendues, jour par jour</li>
                </ul>
                <p>Un graphique en courbes montre l\'évolution projetée et un tableau liste tous les RDV futurs avec leur montant estimé.</p>
                <div class="tip">Utilisez cet onglet en début de mois pour anticiper vos besoins de trésorerie !</div>'
            ],
            [
                'q' => 'Comment analyser mes ventes par catégorie ?',
                'a' => '<p>Allez dans <strong>Finances</strong>, onglet <strong>Par catégorie</strong>. Vous visualisez quelles catégories de prestations génèrent le plus de chiffre d\'affaires sur la période choisie. Utile pour orienter votre stratégie commerciale et identifier vos services les plus rentables.</p>'
            ],
        ]
    ],
    [
        'id'     => 'stock',
        'icon'   => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        'color'  => '#f97316',
        'bg'     => '#fff7ed',
        'label'  => 'Stock & Produits',
        'faqs'   => [
            [
                'q' => 'Comment gérer mon stock de produits ?',
                'a' => '<p>Dans <strong>Stock</strong>, vous visualisez le stock actuel de chaque produit. Le stock est <strong>mis à jour automatiquement</strong> à chaque vente (sortie) et à chaque réception de commande (entrée). Chaque produit a un <strong>seuil d\'alerte</strong> configurable : vous recevez une notification quand le stock passe sous ce seuil.</p>'
            ],
            [
                'q' => 'Comment faire un inventaire physique ?',
                'a' => '<p>Dans <strong>Stock</strong> → <strong>Inventaires</strong> → <strong>Nouvel inventaire</strong>. Saisissez les quantités réelles que vous comptez physiquement pour chaque produit. L\'application compare automatiquement avec le stock théorique et affiche les <strong>écarts</strong> (positifs ou négatifs), ainsi que leur valeur. Vous pouvez ensuite valider l\'inventaire pour mettre à jour les stocks.</p>
                <div class="tip">Les écarts négatifs peuvent indiquer des pertes, vols, erreurs de caisse ou produits périmés.</div>'
            ],
            [
                'q' => 'Comment commander chez mes fournisseurs ?',
                'a' => '<p>Dans <strong>Bons de commande</strong> → <strong>Nouveau bon</strong>. Sélectionnez le fournisseur (préalablement enregistré dans la liste des fournisseurs), puis ajoutez les produits et quantités à commander. Le bon passe par les statuts : Brouillon → Envoyé → Reçu partiel → Reçu. Quand la livraison arrive, cliquez <strong>Réceptionner</strong> : le stock est mis à jour automatiquement et le coût moyen pondéré (CMP) est recalculé.</p>'
            ],
            [
                'q' => 'Comment fonctionne le coût moyen pondéré (CMP) ?',
                'a' => '<p>À chaque entrée de stock, le <strong>coût moyen pondéré</strong> du produit est recalculé automatiquement : ((stock actuel × CMP actuel) + (quantité entrée × prix unitaire)) ÷ (stock actuel + quantité entrée). Cela permet de toujours avoir une valorisation précise de votre stock et de calculer vos marges réelles. La marge unitaire et le pourcentage de marge sont affichés dans la fiche produit.</p>'
            ],
        ]
    ],
    [
        'id'     => 'vitrine',
        'icon'   => 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9',
        'color'  => '#06b6d4',
        'bg'     => '#ecfeff',
        'label'  => 'Vitrine & Réservation',
        'faqs'   => [
            [
                'q' => 'Comment activer ma page vitrine publique ?',
                'a' => '<p>Dans <strong>Mes établissements</strong> → Modifier → activez <strong>"Page vitrine visible"</strong>. Votre page est accessible à l\'adresse <strong>maelyagestion.com/e/votre-slug</strong>. Elle affiche automatiquement vos prestations, photos, horaires et les avis clients approuvés.</p>
                <div class="tip">Téléchargez le QR code de votre vitrine depuis la page de configuration et affichez-le à l\'entrée de votre établissement pour que les clients scannent et découvrent vos services.</div>'
            ],
            [
                'q' => 'Comment télécharger le QR code de ma vitrine ?',
                'a' => '<p>Dans <strong>Mes établissements</strong> → Modifier → section Vitrine, cliquez sur <strong>Télécharger le QR code</strong>. C\'est une image PNG à imprimer et afficher dans votre établissement.</p>'
            ],
            [
                'q' => 'Comment activer la réservation en ligne ?',
                'a' => '<p>Dans <strong>Mes établissements</strong> → Modifier → activez <strong>"Réservation en ligne"</strong>. Les clients peuvent alors prendre rendez-vous directement depuis votre page vitrine, 24h/24. Ils choisissent une prestation et un créneau disponible. Vous recevez une notification pour chaque nouvelle réservation et pouvez la confirmer ou la refuser depuis l\'agenda.</p>'
            ],
        ]
    ],
    [
        'id'     => 'equipe',
        'icon'   => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z',
        'color'  => '#6366f1',
        'bg'     => '#eef2ff',
        'label'  => 'Équipe & Accès',
        'faqs'   => [
            [
                'q' => 'Comment ajouter un employé ?',
                'a' => '<p>Dans <strong>Mon équipe</strong> → <strong>Inviter</strong>. Saisissez l\'email de la personne. Elle reçoit un lien pour créer son compte avec ses propres identifiants. Elle pourra encaisser des ventes et accéder aux clients et RDV, mais ne verra pas les données financières globales ni ne pourra modifier les paramètres de l\'établissement.</p>
                <div class="tip">Chaque employé a ses propres identifiants. Vous voyez leurs ventes séparément dans l\'historique et le tableau de bord.</div>'
            ],
            [
                'q' => 'Quelle est la différence entre Admin et Employé ?',
                'a' => '<table style="font-size:.8rem;margin:8px 0;">
                    <thead><tr><th style="padding:6px 10px;background:#f3f4f6;">Fonctionnalité</th><th style="padding:6px 10px;background:#f3f4f6;">Admin</th><th style="padding:6px 10px;background:#f3f4f6;">Employé</th></tr></thead>
                    <tbody>
                        <tr><td style="padding:5px 10px;border:1px solid #e5e7eb;">Encaisser des ventes</td><td style="padding:5px 10px;border:1px solid #e5e7eb;">✅</td><td style="padding:5px 10px;border:1px solid #e5e7eb;">✅</td></tr>
                        <tr><td style="padding:5px 10px;border:1px solid #e5e7eb;">Voir toutes les ventes</td><td style="padding:5px 10px;border:1px solid #e5e7eb;">✅</td><td style="padding:5px 10px;border:1px solid #e5e7eb;">Ses ventes uniquement</td></tr>
                        <tr><td style="padding:5px 10px;border:1px solid #e5e7eb;">Accéder aux clients et RDV</td><td style="padding:5px 10px;border:1px solid #e5e7eb;">✅</td><td style="padding:5px 10px;border:1px solid #e5e7eb;">✅</td></tr>
                        <tr><td style="padding:5px 10px;border:1px solid #e5e7eb;">Gérer le stock (entrées, corrections)</td><td style="padding:5px 10px;border:1px solid #e5e7eb;">✅</td><td style="padding:5px 10px;border:1px solid #e5e7eb;">✅</td></tr>
                        <tr><td style="padding:5px 10px;border:1px solid #e5e7eb;">Finances & dépenses</td><td style="padding:5px 10px;border:1px solid #e5e7eb;">✅</td><td style="padding:5px 10px;border:1px solid #e5e7eb;">❌</td></tr>
                        <tr><td style="padding:5px 10px;border:1px solid #e5e7eb;">Annuler une vente / Créer un avoir</td><td style="padding:5px 10px;border:1px solid #e5e7eb;">✅</td><td style="padding:5px 10px;border:1px solid #e5e7eb;">❌</td></tr>
                        <tr><td style="padding:5px 10px;border:1px solid #e5e7eb;">Modifier les paramètres établissement</td><td style="padding:5px 10px;border:1px solid #e5e7eb;">✅</td><td style="padding:5px 10px;border:1px solid #e5e7eb;">❌</td></tr>
                        <tr><td style="padding:5px 10px;border:1px solid #e5e7eb;">Gérer les codes de réduction et avoirs</td><td style="padding:5px 10px;border:1px solid #e5e7eb;">✅</td><td style="padding:5px 10px;border:1px solid #e5e7eb;">❌</td></tr>
                    </tbody>
                </table>'
            ],
        ]
    ],
    [
        'id'     => 'notifications',
        'icon'   => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
        'color'  => '#ef4444',
        'bg'     => '#fef2f2',
        'label'  => 'Notifications',
        'faqs'   => [
            [
                'q' => 'Quelles sont les notifications disponibles ?',
                'a' => '<p>Maëlya vous notifie automatiquement pour :</p>
                <ul>
                    <li>Nouvelle réservation en ligne reçue</li>
                    <li>RDV confirmé, annulé ou reprogrammé</li>
                    <li>Stock sous le seuil d\'alerte</li>
                    <li>Paiement d\'abonnement reçu ou en retard</li>
                    <li>Anniversaire d\'un client (la veille)</li>
                    <li>Nouvel avis client reçu</li>
                    <li>Bon de commande livré</li>
                </ul>
                <p>Les notifications sont disponibles <strong>en temps réel dans l\'application</strong> (cloche en haut à droite) et par <strong>email</strong>.</p>'
            ],
            [
                'q' => 'Comment accéder à toutes mes notifications ?',
                'a' => '<p>Cliquez sur l\'icône <strong>cloche en haut à droite</strong> de votre tableau de bord. Un panneau latéral affiche les 20 notifications les plus récentes. En bas, cliquez <strong>"Voir toutes les notifications"</strong> pour accéder à la page complète avec pagination et un bouton "Tout marquer comme lu".</p>'
            ],
            [
                'q' => 'Comment fonctionnent les notifications push ?',
                'a' => '<p>Les notifications push web vous alertent même lorsque vous n\'êtes pas sur l\'application. Lors de votre première connexion, votre navigateur vous demande l\'autorisation d\'afficher des notifications. Vous pouvez vous désabonner à tout moment depuis votre profil.</p>'
            ],
        ]
    ],
    [
        'id'     => 'multi',
        'icon'   => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 00-1-1h-2a1 1 0 00-1 1v5m4 0H9',
        'color'  => '#9333ea',
        'bg'     => '#faf5ff',
        'label'  => 'Multi-établissements',
        'faqs'   => [
            [
                'q' => 'Comment gérer plusieurs établissements avec un seul compte ?',
                'a' => '<p>Avec le plan <strong>Premium+</strong>, vous pouvez créer plusieurs établissements depuis <strong>Mes établissements</strong> → <strong>Nouvel établissement</strong>. Chaque établissement a ses propres données (ventes, clients, stock, prestations, équipe). Basculez entre les établissements depuis le sélecteur en haut de la barre latérale. Les données sont totalement indépendantes.</p>'
            ],
            [
                'q' => 'Comment comparer les performances de mes établissements ?',
                'a' => '<p>Dans <strong>Comparatif</strong> (menu accessible uniquement si vous avez plusieurs établissements en Premium+), vous pouvez comparer sur une même période :</p>
                <ul>
                    <li>Chiffre d\'affaires par établissement</li>
                    <li>Nombre de clients et de ventes</li>
                    <li>Top prestations par établissement</li>
                    <li>Évolution mois par mois</li>
                </ul>'
            ],
        ]
    ],
    [
        'id'     => 'credits',
        'icon'   => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
        'color'  => '#0891b2',
        'bg'     => '#ecfeff',
        'label'  => 'Vente à crédit',
        'faqs'   => [
            [
                'q' => 'Comment faire une vente à crédit ?',
                'a' => '<p>Disponible avec le plan <strong>Premium+</strong>. Dans la caisse, sélectionnez le mode de paiement <strong>Crédit</strong>. Saisissez l\'apport initial (optionnel), le nombre d\'échéances et la fréquence (hebdomadaire ou mensuelle). Le système génère automatiquement un échéancier avec les dates et montants de chaque versement.</p>'
            ],
            [
                'q' => 'Comment suivre les crédits en cours ?',
                'a' => '<p>Dans <strong>Crédits</strong>, vous visualisez tous les crédits avec leur statut (en cours, terminé, en retard). Pour chaque crédit, vous pouvez :</p>
                <ul>
                    <li>Voir l\'échéancier complet avec les dates de paiement</li>
                    <li>Enregistrer un paiement partiel</li>
                    <li>Télécharger la fiche de crédit en PDF (partageable par WhatsApp)</li>
                    <li>Recevoir des rappels automatiques J-1 avant chaque échéance</li>
                </ul>
                <div class="tip">Les crédits en retard sont détectés automatiquement et signalés dans le tableau de bord.</div>'
            ],
        ]
    ],
    [
        'id'     => 'parrainage',
        'icon'   => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197',
        'color'  => '#d97706',
        'bg'     => '#fffbeb',
        'label'  => 'Parrainage',
        'faqs'   => [
            [
                'q' => 'Comment fonctionne le parrainage ?',
                'a' => '<p>Dans <strong>Parrainage</strong>, vous trouvez votre <strong>lien de parrainage unique</strong>. Quand quelqu\'un s\'inscrit avec votre lien et souscrit un plan payant :</p>
                <ul>
                    <li>Vous gagnez <strong>des jours gratuits</strong> sur votre abonnement</li>
                    <li>Le filleul bénéficie également de jours offerts sur son abonnement</li>
                </ul>
                <div class="tip">Partagez votre lien sur WhatsApp, les réseaux sociaux ou en personne à d\'autres propriétaires d\'établissement !</div>'
            ],
        ]
    ],
    [
        'id'     => 'recherche',
        'icon'   => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
        'color'  => '#84cc16',
        'bg'     => '#f7fee7',
        'label'  => 'Recherche & Codes promo',
        'faqs'   => [
            [
                'q' => 'Comment utiliser la recherche globale ?',
                'a' => '<p>Depuis le tableau de bord, utilisez la <strong>barre de recherche globale</strong>. Elle recherche simultanément dans vos clients, ventes, prestations, produits et rendez-vous. Idéal pour retrouver rapidement une information sans naviguer dans les menus.</p>'
            ],
            [
                'q' => 'Comment créer un code de réduction ?',
                'a' => '<p>Dans <strong>Codes de réduction</strong> (menu accessible aux administrateurs avec le plan Premium) :</p>
                <ul>
                    <li><strong>Type</strong> : pourcentage (%) ou montant fixe (FCFA)</li>
                    <li><strong>Code</strong> : personnalisé ou généré automatiquement</li>
                    <li><strong>Validité</strong> : dates de début et de fin</li>
                    <li><strong>Limite</strong> : nombre maximum d\'utilisations (illimité si non défini)</li>
                    <li><strong>Client</strong> : nominatif (lié à un client) ou global (utilisable par tous)</li>
                </ul>
                <div class="tip">Les codes sont validés en temps réel à la caisse : périmés, épuisés ou inactifs sont automatiquement refusés.</div>'
            ],
        ]
    ],
];
@endphp

{{-- Stats rapides --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    @php
        $totalFaqs = collect($categories)->sum(fn($c) => count($c['faqs']));
    @endphp
    <div class="card p-4 text-center">
        <p class="text-2xl font-bold" style="background: linear-gradient(135deg, #9333ea, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">26</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Modules</p>
    </div>
    <div class="card p-4 text-center">
        <p class="text-2xl font-bold text-blue-600">{{ $totalFaqs }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Questions répondues</p>
    </div>
    <div class="card p-4 text-center">
        <p class="text-2xl font-bold text-emerald-600">{{ count($categories) }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Catégories</p>
    </div>
    <div class="card p-4 text-center">
        <p class="text-2xl font-bold text-amber-600">24h</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Support WhatsApp</p>
    </div>
</div>

{{-- FAQ par catégorie --}}
<div class="space-y-5" id="faq-container">
    @foreach($categories as $cat)
    <div class="faq-category" data-cat="{{ $cat['id'] }}">
        {{-- En-tête catégorie --}}
        <div class="flex items-center gap-2 mb-2">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                 style="background: {{ $cat['bg'] }}; color: {{ $cat['color'] }};">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $cat['icon'] }}"/>
                </svg>
            </div>
            <h2 class="font-bold text-gray-900 dark:text-white text-sm tracking-wide">{{ $cat['label'] }}</h2>
            <span class="text-xs text-gray-400">({{ count($cat['faqs']) }} questions)</span>
        </div>

        {{-- Questions --}}
        <div class="space-y-1.5" x-data="{ open: null }">
            @foreach($cat['faqs'] as $i => $faq)
            <div class="faq-item rounded-xl border border-gray-200 dark:border-white/[0.08] bg-white dark:bg-gray-800/60 overflow-hidden"
                 data-text="{{ strtolower($faq['q']) }} {{ strtolower(strip_tags($faq['a'])) }}">
                <button type="button"
                        class="faq-btn"
                        @click="open = (open === {{ $i }} ? null : {{ $i }})">
                    <span class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold flex-shrink-0"
                          style="background: {{ $cat['bg'] }}; color: {{ $cat['color'] }};">?</span>
                    <span class="faq-question">{{ $faq['q'] }}</span>
                    <svg class="w-4 h-4 flex-shrink-0 transition-transform duration-200"
                         style="color:#9ca3af;"
                         :style="open === {{ $i }} ? 'transform:rotate(180deg)' : ''"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === {{ $i }}" x-collapse>
                    <div class="faq-answer">{!! $faq['a'] !!}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach
</div>

{{-- Bloc support --}}
<div class="mt-8 rounded-2xl p-6 text-center" style="background: linear-gradient(135deg, rgba(147,51,234,0.07), rgba(236,72,153,0.07)); border: 1px solid rgba(147,51,234,0.15);">
    <div class="w-12 h-12 rounded-2xl mx-auto mb-3 flex items-center justify-center text-2xl" style="background: linear-gradient(135deg, #9333ea, #ec4899);">
        💬
    </div>
    <h3 class="font-bold text-gray-900 dark:text-white mb-1">Vous n'avez pas trouvé votre réponse ?</h3>
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Notre équipe support répond sur WhatsApp en moins de 2h.</p>
    <a href="https://wa.me/2250709874067?text=Bonjour+j%27ai+besoin+d%27aide+avec+Maëlya+Gestion"
       target="_blank" rel="noopener"
       class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-semibold text-white transition-opacity hover:opacity-90"
       style="background: linear-gradient(135deg, #9333ea, #ec4899);">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path d="M20.52 3.48A11.94 11.94 0 0012 0C5.37 0 0 5.37 0 12a11.93 11.93 0 001.64 6.06L0 24l6.18-1.62A11.93 11.93 0 0012 24c6.63 0 12-5.37 12-12 0-3.2-1.25-6.21-3.48-8.52z"/>
        </svg>
        Contacter le support WhatsApp
    </a>
</div>

<p class="mt-6 text-center text-xs text-gray-400 dark:text-gray-600 pb-safe">
    Maëlya Gestion · maelyagestion.com · Documentation v1 — Juin 2026
</p>

<script>
function filterFaq(q) {
    const query = q.toLowerCase().trim();
    document.querySelectorAll('.faq-item').forEach(function(item) {
        const text = item.dataset.text || '';
        const visible = !query || text.includes(query);
        item.style.display = visible ? '' : 'none';
    });
    document.querySelectorAll('.faq-category').forEach(function(cat) {
        const hasVisible = Array.from(cat.querySelectorAll('.faq-item')).some(i => i.style.display !== 'none');
        cat.style.display = hasVisible ? '' : 'none';
    });
}
</script>

</x-dashboard-layout>
