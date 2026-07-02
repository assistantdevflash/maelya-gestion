<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Guide Porte-à-Porte — Maëlya Gestion</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1f2937; line-height: 1.5; }
    .page { padding: 24px 28px; }

    /* En-tête */
    .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #9333ea; padding-bottom: 12px; margin-bottom: 18px; }
    .header-title { font-size: 18px; font-weight: 800; color: #1f2937; }
    .header-sub { font-size: 9px; color: #6b7280; margin-top: 2px; }
    .header-logo { text-align: right; font-size: 9px; color: #6b7280; }
    .header-logo strong { display: block; font-size: 13px; color: #9333ea; font-weight: 700; }

    /* Sections */
    .section { margin-bottom: 16px; page-break-inside: avoid; }
    .section-title { font-size: 12px; font-weight: 700; color: #ffffff; background: linear-gradient(135deg, #9333ea, #ec4899); padding: 6px 12px; border-radius: 6px; margin-bottom: 8px; }
    .section-num { display: inline-block; background: rgba(255,255,255,0.25); border-radius: 4px; padding: 1px 6px; margin-right: 6px; font-size: 10px; }
    .section-body { padding: 0 4px; }

    /* Texte */
    p { margin-bottom: 6px; }
    ul, ol { margin: 4px 0 8px 18px; }
    li { margin-bottom: 2px; }
    strong { font-weight: 700; }
    em { font-style: italic; color: #6b7280; }

    /* Citation / script */
    .quote { background: #f3e8ff; border-left: 4px solid #9333ea; padding: 8px 12px; border-radius: 0 6px 6px 0; margin: 6px 0; font-style: normal; color: #3b0764; }

    /* Tableaux */
    table { width: 100%; border-collapse: collapse; margin: 6px 0; font-size: 10px; }
    th { background: #f3e8ff; color: #6b21a8; font-weight: 700; padding: 5px 7px; border: 1px solid #e9d5ff; text-align: left; }
    td { padding: 4px 7px; border: 1px solid #e5e7eb; vertical-align: top; }
    tr:nth-child(even) td { background: #fafafa; }

    /* Alerte */
    .alert { background: #fffbeb; border: 1px solid #fcd34d; border-radius: 6px; padding: 8px 12px; margin-bottom: 14px; font-size: 10px; color: #92400e; }

    /* Footer */
    .footer { margin-top: 20px; border-top: 1px solid #e5e7eb; padding-top: 8px; text-align: center; font-size: 9px; color: #9ca3af; }

    /* Top 5 */
    .top5 li { font-size: 11.5px; margin-bottom: 5px; }

    .page-break { page-break-before: always; }
</style>
</head>
<body>
<div class="page">

    {{-- En-tête --}}
    <div class="header">
        <div>
            <div class="header-title">🚶 Guide Porte-à-Porte</div>
            <div class="header-sub">Document terrain · Commerciaux Maëlya Gestion · Juin 2026 · v4</div>
        </div>
        <div class="header-logo">
            <strong>Maëlya Gestion</strong>
            maelyagestion.com
        </div>
    </div>

    {{-- Rappel --}}
    <div class="alert">
        <strong>À avoir avec soi :</strong> téléphone chargé avec compte démo · flyers A5 · cartes de visite avec code parrainage ·
        Meilleurs horaires : 09h30–11h30 et 15h00–17h00 · Éviter vendredi et samedi
    </div>

    {{-- Section 1 --}}
    <div class="section">
        <div class="section-title"><span class="section-num">1</span> AVANT DE PARTIR — Préparer sa journée</div>
        <div class="section-body">
            <p><strong>Ce qu'il faut avoir :</strong> téléphone démo (3 prestations, 2 produits, 5 clients) · flyers A5 · cartes de visite avec code parrainage · fichier de suivi pour noter les contacts</p>
            <p><strong>Ciblage par quartier (tourner chaque semaine) :</strong><br>
            Cocody/Riviera → Marcory/Zone 4 → Plateau/Adjamé → Yopougon → Treichville</p>
        </div>
    </div>

    {{-- Section 2 --}}
    <div class="section">
        <div class="section-title"><span class="section-num">2</span> APPROCHE — Comment entrer dans l'établissement</div>
        <div class="section-body">
            <p><strong>Les 3 règles d'or :</strong> 1) Sourire et se présenter immédiatement · 2) Ne jamais interrompre un client en service · 3) Poser une question, ne pas réciter un discours</p>
            <p><strong>Script d'entrée (30 secondes) :</strong></p>
            <div class="quote">« Bonjour ! Je m'appelle [Prénom], je travaille pour Maëlya Gestion — c'est une application ivoirienne pour aider les établissements à gérer leurs ventes et leurs clients depuis le téléphone. Est-ce que vous avez 2 petites minutes pour que je vous montre comment ça marche ? »</div>
            <p><strong>Si "je suis occupé(e)" :</strong></p>
            <div class="quote">« Pas de souci ! Je peux repasser dans 30 minutes ou demain matin — ça vous arrange mieux quand ? »</div>
            <p><strong>Si "j'ai pas besoin" :</strong></p>
            <div class="quote">« Juste une curiosité : vous suivez comment vos ventes en ce moment ? Cahier, téléphone, ou de tête ? »</div>
        </div>
    </div>

    {{-- Section 3 --}}
    <div class="section">
        <div class="section-title"><span class="section-num">3</span> PITCH PRINCIPAL — La démonstration en 5 minutes</div>
        <div class="section-body">
            <table>
                <thead><tr><th>Étape</th><th>Durée</th><th>Ce qu'on montre / dit</th></tr></thead>
                <tbody>
                <tr><td><strong>1. Identifier le besoin</strong></td><td>1 min</td><td>
                    Si employés : « Vous savez combien chaque employé a encaissé aujourd'hui ? »<br>
                    Si seul : « Vous notez vos ventes comment ? »<br>
                    Si multi-établissements : « Vous êtes sur place tout le temps ? »<br>
                    → <em>« C'est exactement pour ça que Maëlya a été créé. »</em>
                </td></tr>
                <tr><td><strong>2. Caisse</strong></td><td>1 min</td><td>Sélectionner une prestation → prix automatique → Wave/OM → valider → ticket<br><em>« 10 secondes. Plus de cahier, plus de calculatrice. »</em></td></tr>
                <tr><td><strong>3. Tableau de bord</strong></td><td>30 sec</td><td>CA jour/mois en gros chiffres<br><em>« Même si vous êtes à la maison, vous savez ce qui se passe. »</em></td></tr>
                <tr><td><strong>4. Fonctionnalité ciblée</strong></td><td>2 min</td><td>
                    Employés → Équipe · RDV → Agenda & Calendrier drag&drop · Produits → Stock & Inventaire<br>
                    Fidélisation → Fidélité · Bénéfices → Finances · Web → Vitrine + Réservation · Portfolio → Galerie photos
                </td></tr>
                <tr><td><strong>5. Clôture essai</strong></td><td>30 sec</td><td><div class="quote" style="margin:0;">« Essai gratuit 14 jours, accès complet, sans carte bancaire. On crée votre compte maintenant ? »</div></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Section 4 --}}
    <div class="section page-break">
        <div class="section-title"><span class="section-num">4</span> LES FONCTIONNALITÉS — Mémo complet</div>
        <div class="section-body">
            <table>
                <thead><tr><th>Module</th><th>Ce que ça fait</th><th>Argument clé</th></tr></thead>
                <tbody>
                <tr><td><strong>Scanner code-barres</strong></td><td>Scan caméra ou scanner externe USB/Bluetooth</td><td>« Ajoutez les produits en un éclair »</td></tr>
                <tr><td><strong>Caisse</strong></td><td>Ventes en 10s, espèces/Wave/OM/carte/mixte/crédit</td><td>« Plus jamais de calcul à la main »</td></tr>
                <tr><td><strong>Ticket de caisse</strong></td><td>Numérique (WhatsApp) ou PDF imprimable</td><td>« Professionnel comme une caisse de supermarché »</td></tr>
                <tr><td><strong>Facture PDF</strong></td><td>Facture numérotée pour chaque vente</td><td>« Vos clients ont une vraie facture »</td></tr>
                <tr><td><strong>Tableau de bord</strong></td><td>CA jour/mois, alertes stocks, anniversaires, graphiques</td><td>« Tout d'un coup d'œil »</td></tr>
                <tr><td><strong>Clients</strong></td><td>Fiche complète + historique ventes, RDV, photos</td><td>« Vous connaissez vos clients mieux qu'eux »</td></tr>
                <tr><td><strong>Anniversaires</strong></td><td>Alerte J-1 + code cadeau automatique le jour J</td><td>« Vos clients se sentent valorisés »</td></tr>
                <tr><td><strong>Agenda / RDV</strong></td><td>Planning + email confirmation auto + encaissement</td><td>« Fini les oublis et les no-shows »</td></tr>
                <tr><td><strong>Calendrier RDV</strong></td><td>Vue calendrier, déplacer RDV en glissant (drag & drop)</td><td>« Agenda professionnel, flexible »</td></tr>
                <tr><td><strong>Réservation en ligne</strong></td><td>Clients réservent depuis la vitrine, 24h/24</td><td>« Réservations même quand vous dormez »</td></tr>
                <tr><td><strong>Prestations</strong></td><td>Catalogue services avec prix et durée</td><td>« Plus d'erreur de prix »</td></tr>
                <tr><td><strong>Produits</strong></td><td>Catalogue produits vendables avec stock</td><td>« Vendez aussi les produits »</td></tr>
                <tr><td><strong>Stocks</strong></td><td>Mise à jour auto + alerte seuil + coût moyen pondéré</td><td>« Ne tombez plus en rupture »</td></tr>
                <tr><td><strong>Fidélité</strong></td><td>Points automatiques + code cadeau aux paliers</td><td>« Vos clients reviennent »</td></tr>
                <tr><td><strong>Codes de réduction</strong></td><td>Promos % ou montant fixe, validité, limites d'usage</td><td>« Boostez les ventes »</td></tr>
                <tr><td><strong>Avoirs</strong></td><td>Remboursement partiel → code réutilisable</td><td>« Gardez le client, même après un souci »</td></tr>
                <tr><td><strong>Finances</strong></td><td>Dépenses, bénéfice net, export PDF, graphiques</td><td>« Vous savez combien vous gagnez vraiment »</td></tr>
                <tr><td><strong>Trésorerie prévisionnelle</strong></td><td>Projection entrées/sorties sur 7 à 90 jours</td><td>« Anticipez vos fins de mois »</td></tr>
                <tr><td><strong>Mon équipe</strong></td><td>Comptes séparés par employé, permissions limitées</td><td>« Chacun encaisse, vous contrôlez »</td></tr>
                <tr><td><strong>Page vitrine</strong></td><td>Page web publique + QR code téléchargeable</td><td>« Vos clients voient vos prix en ligne »</td></tr>
                <tr><td><strong>Galerie photos</strong></td><td>Photos avant/après par client dans son dossier</td><td>« Portfolio, attirez de nouveaux clients »</td></tr>
                <tr><td><strong>Bons de commande</strong></td><td>Commander fournisseurs, suivre réception</td><td>« Zéro paperasse »</td></tr>
                <tr><td><strong>Inventaire physique</strong></td><td>Compter stock, détecter écarts et pertes</td><td>« Détectez les vols et erreurs »</td></tr>
                <tr><td><strong>Sondage satisfaction</strong></td><td>Lien envoyé par email ou WhatsApp après vente</td><td>« Vos clients notent, vous progressez »</td></tr>
                <tr><td><strong>Avis clients</strong></td><td>Modération des avis, affichage étoiles sur vitrine</td><td>« Les étoiles rassurent les nouveaux clients »</td></tr>
                <tr><td><strong>Notifications</strong></td><td>Centre d'alertes (stock, RDV, paiements, anniversaires)</td><td>« Ne ratez plus aucun événement important »</td></tr>
                <tr><td><strong>Multi-établissements</strong></td><td>Gérer plusieurs établissements depuis un compte</td><td>« Surveillez tout depuis votre téléphone »</td></tr>
                <tr><td><strong>Comparatif</strong></td><td>Comparer CA/clients/prestations entre établissements</td><td>« Quel établissement performe le mieux ? »</td></tr>
                <tr><td><strong>Vente à crédit</strong></td><td>Échéancier, suivi paiements, rappels auto, fiche PDF</td><td>« Vendez à crédit en toute sécurité »</td></tr>
                <tr><td><strong>Parrainage</strong></td><td>Inviter = jours gratuits pour les deux parties</td><td>« Faites-vous parrainer, payez moins cher »</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Section 5 --}}
    <div class="section">
        <div class="section-title"><span class="section-num">5</span> LES TARIFS — Quoi dire</div>
        <div class="section-body">
            <table>
                <thead><tr><th>Plan</th><th>Prix/mois</th><th>Pour qui</th><th>Ce qu'on dit</th></tr></thead>
                <tbody>
                <tr><td><strong>Essai</strong></td><td>Gratuit 14j</td><td>Tout le monde</td><td>« Vous testez tout, sans rien payer, sans engagement »</td></tr>
                <tr><td><strong>Premium</strong></td><td>4 900 FCFA</td><td>Établissement avec 1 à 3 employés</td><td>« Tout inclus : caisse, stock, fidélité, finances, équipe, vitrine »</td></tr>
                <tr><td><strong>Premium+</strong></td><td>9 900 FCFA</td><td>Jusqu'à 3 établissements, 10 employés max/établissement</td><td>« Pour gérer plusieurs établissements, avec vente à crédit et scanner code-barres »</td></tr>
                <tr><td><strong>Ultra</strong></td><td>24 900 FCFA</td><td>Établissements illimités, employés illimités</td><td>« Pour les grandes chaînes, contrôle total sur tous les établissements »</td></tr>
                </tbody>
            </table>
            <p style="margin-top:6px;"><strong>Paiement :</strong> Orange Money ou Wave. Pas de carte bancaire, pas de compte bancaire nécessaire.</p>
        </div>
    </div>

    {{-- Section 6 --}}
    <div class="section">
        <div class="section-title"><span class="section-num">6</span> RÉPONSES AUX OBJECTIONS</div>
        <div class="section-body">
            <p><strong>« C'est trop cher »</strong></p>
            <div class="quote">« 2 000 FCFA par mois, c'est 67 FCFA par jour — moins qu'un café. Commencez par l'essai gratuit 14 jours, vous décidez après. »</div>
            <p><strong>« Je ne suis pas fort(e) en technologie »</strong></p>
            <div class="quote">« Si vous savez utiliser WhatsApp, vous saurez utiliser Maëlya. On vous aide à configurer le compte ici maintenant. Notre support WhatsApp répond en moins de 2h. »</div>
            <p><strong>« J'ai déjà un cahier, ça marche »</strong></p>
            <div class="quote">« Un cahier ne vous dit pas combien vous avez fait ce mois-ci. Il ne vous alerte pas quand un produit est épuisé. Il ne retient pas les anniversaires de vos clients. Maëlya fait tout ça automatiquement. »</div>
            <p><strong>« Je vais en parler à mon associé(e) »</strong></p>
            <div class="quote">« Je peux vous envoyer une vidéo de 2 minutes sur WhatsApp maintenant. Vous avez votre numéro disponible ? »</div>
            <p><strong>« Je vais réfléchir »</strong></p>
            <div class="quote">« L'essai est gratuit, vous ne risquez rien. Je vous crée le compte maintenant et vous avez 14 jours — si non, zéro engagement. On le fait maintenant ? »</div>
        </div>
    </div>

    {{-- Section 7 --}}
    <div class="section">
        <div class="section-title"><span class="section-num">7</span> SUIVI — Ce qu'on fait après la visite</div>
        <div class="section-body">
            <p><strong>Fichier de suivi :</strong> Nom de l'établissement · Nom du responsable · WhatsApp · 🔥 Chaud / 🟠 Tiède / ❄️ Froid · Date visite</p>
            <p><strong>J+1 :</strong></p>
            <div class="quote">« Bonjour [Prénom] 👋 C'est [Prénom] de Maëlya Gestion. On s'est rencontré hier. Voici le lien : maelyagestion.com — ça prend 2 minutes 😊 »</div>
            <p><strong>J+4 (si pas de réponse) :</strong></p>
            <div class="quote">« Bonjour ! Vous avez eu le temps de jeter un œil à Maëlya ? Je peux faire une démo rapide de 5 min cette semaine. »</div>
            <p><strong>J+10 (si toujours rien) :</strong></p>
            <div class="quote">« Bonjour [Prénom] ! Essai gratuit 14 jours, sans carte bancaire. C'est le bon moment pour tester avant le week-end 😊 »</div>
            <p>Après J+14 sans réponse → ❄️ Froid, revenir dans 1 mois.</p>
        </div>
    </div>

    {{-- Section 8 --}}
    <div class="section">
        <div class="section-title"><span class="section-num">8</span> TOP 5 ARGUMENTS — À retenir absolument</div>
        <div class="section-body">
            <ol class="top5">
                <li><strong>Caisse en 10 secondes</strong> → Plus rapide que noter sur un cahier</li>
                <li><strong>Ventes en temps réel</strong> → Même quand vous n'êtes pas sur place</li>
                <li><strong>Vos employés encaissent, vous contrôlez</strong> → Fini les pertes inexpliquées</li>
                <li><strong>Réservation en ligne 24h/24</strong> → Les clients réservent même quand vous êtes fermé</li>
                <li><strong>Essai gratuit 14 jours, paiement Wave/OM</strong> → Aucun risque, aucune complication</li>
                <li><em>Bonus :</em> galerie photos avant/après, calendrier interactif, inventaire physique, sondage satisfaction, trésorerie prévisionnelle</li>
            </ol>
        </div>
    </div>

    {{-- Section 9 --}}
    <div class="section">
        <div class="section-title"><span class="section-num">9</span> PROFILS ET ARGUMENTS PRIORITAIRES</div>
        <div class="section-body">
            <table>
                <thead><tr><th>Type d'établissement</th><th>Argument n°1</th><th>Argument n°2</th><th>Module à montrer</th></tr></thead>
                <tbody>
                <tr><td>Coiffure avec employés</td><td>Contrôle des ventes par employé</td><td>Calendrier RDV drag&drop</td><td>Équipe + Calendrier</td></tr>
                <tr><td>Onglerie / Nail bar solo</td><td>Réservation en ligne 24h/24</td><td>Galerie photos avant/après</td><td>Vitrine + Galerie</td></tr>
                <tr><td>Institut de beauté</td><td>Galerie avant/après + réservation</td><td>Agenda calendrier + fidélité</td><td>Galerie + Vitrine + RDV</td></tr>
                <tr><td>Barbershop</td><td>Caisse professionnelle + équipe</td><td>Tickets imprimables</td><td>Caisse + Tickets</td></tr>
                <tr><td>Spa / Multi-établissements</td><td>Comparatif multi-établissements (Premium+)</td><td>Contrôle à distance temps réel</td><td>Comparatif + Dashboard</td></tr>
                <tr><td>Établissement avec boutique</td><td>Inventaire physique + bons commande</td><td>Alertes rupture stock</td><td>Inventaire + Fournisseurs</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="footer">
        Maëlya Gestion — maelyagestion.com — Support WhatsApp : réponse &lt; 2h — Document terrain v4 — Juin 2026 — 26 modules
    </div>

</div>
</body>
</html>
