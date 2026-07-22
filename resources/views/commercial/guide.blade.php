@extends('layouts.commercial')
@section('title', 'Guide Porte-à-Porte')

@section('content')

<style>
/* --- Bandeau parrainage dark mode --- */
.dark .ref-banner { background:linear-gradient(135deg,#292000,#1f1500) !important; border-color:rgba(245,158,11,.3) !important; }
.dark .ref-banner-header { background:rgba(245,158,11,.08) !important; border-bottom-color:rgba(245,158,11,.15) !important; }
.dark .ref-banner-header span { color:#fcd34d !important; }
.dark .ref-banner code { color:#fde68a !important; }

/* --- En-tête accordéon --- */
.guide-btn { width:100%; display:flex; align-items:center; gap:12px; padding:16px 20px; text-align:left; transition:background .15s; }
.guide-btn:hover { background: rgba(0,0,0,.04); }
.dark .guide-btn:hover { background: rgba(255,255,255,.05); }
.guide-title { font-weight:600; font-size:.8125rem; flex:1; color:#111827; }
.dark .guide-title { color:#f9fafb; }

/* --- Corps du contenu --- */
.guide-content { padding:16px 20px 20px; border-top:1px solid #f3f4f6; font-size:.8125rem; line-height:1.65; color:#374151; }
.dark .guide-content { border-top-color:rgba(255,255,255,.07); color:#d1d5db; }

.guide-content p { margin:0 0 10px; }
.guide-content ul, .guide-content ol { margin:6px 0 12px 20px; }
.guide-content li { margin-bottom:3px; }
.guide-content strong { font-weight:700; color:#111827; }
.dark .guide-content strong { color:#f9fafb; }
.guide-content em { color:#6b7280; font-style:italic; }

/* Blockquotes (scripts verbatim) */
.guide-content blockquote {
    margin:8px 0 12px;
    padding:10px 14px;
    border-left:4px solid #9333ea;
    background:#faf5ff;
    border-radius:0 8px 8px 0;
    color:#4c1d95;
    font-style:normal;
}
.dark .guide-content blockquote {
    background:rgba(147,51,234,.12);
    border-left-color:#a855f7;
    color:#d8b4fe;
}

/* Tableaux */
.guide-content table { width:100%; border-collapse:collapse; font-size:.75rem; margin:0; }
.guide-content th { padding:6px 10px; background:#f5f3ff; color:#6d28d9; font-weight:700; text-align:left; border:1px solid #ede9fe; white-space:nowrap; }
.dark .guide-content th { background:rgba(109,40,217,.2); color:#c4b5fd; border-color:rgba(139,92,246,.25); }
.guide-content td { padding:5px 10px; border:1px solid #e5e7eb; vertical-align:top; }
.dark .guide-content td { border-color:rgba(255,255,255,.08); }
.guide-content tr:nth-child(even) td { background:#fafafa; }
.dark .guide-content tr:nth-child(even) td { background:rgba(255,255,255,.03); }

/* Scroll horizontal tableaux sur mobile */
.table-scroll { overflow-x:auto; -webkit-overflow-scrolling:touch; margin:8px 0 14px; border-radius:6px; }
.table-scroll table { min-width:100%; }

/* Touch feedback */
.guide-btn:active { background:rgba(0,0,0,.07); }
.dark .guide-btn:active { background:rgba(255,255,255,.08); }
.guide-btn, .guide-copy-btn { -webkit-tap-highlight-color:transparent; touch-action:manipulation; }

/* Responsive mobile */
@media (max-width:640px) {
    .guide-btn { padding:13px 14px; gap:10px; min-height:52px; }
    .guide-title { font-size:.8rem; line-height:1.35; }
    .guide-content { padding:13px 14px 16px; font-size:.8rem; }
    .guide-content blockquote { padding:8px 10px; font-size:.8rem; }
    .guide-content th, .guide-content td { padding:5px 7px; font-size:.7rem; }
    .guide-content ul, .guide-content ol { margin-left:16px; }
    .ref-banner-body { flex-direction:column !important; align-items:stretch !important; gap:8px !important; }
    .guide-copy-btn { align-self:flex-end; }
}

/* Safe-area insets (iPhone à encoche) */
@supports (padding: max(0px)) {
    .guide-content { padding-left:max(14px, calc(14px + env(safe-area-inset-left))); padding-right:max(14px, calc(14px + env(safe-area-inset-right))); }
}
</style>

{{-- En-tête page --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">🚶 Guide Porte-à-Porte</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Votre guide terrain complet pour prospecter les établissements</p>
    </div>
    <a href="{{ route('commercial.guide.pdf') }}"
       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white shadow-lg transition-opacity hover:opacity-90 flex-shrink-0"
       style="background: linear-gradient(135deg, #9333ea, #ec4899);">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        Télécharger PDF
    </a>
</div>

{{-- Lien de parrainage --}}
@if(Auth::user()->commercialProfile)
@php $refLink = 'https://maelyagestion.com/inscription?ref=' . Auth::user()->commercialProfile->code; @endphp
<div class="ref-banner rounded-xl mb-6 overflow-hidden border border-amber-300 dark:border-amber-600/50"
     style="background:linear-gradient(135deg,#fffbeb,#fef3c7);"
     x-data="{ copied: false }">
    <div class="ref-banner-header px-4 py-2 flex items-center gap-2 border-b border-amber-200 dark:border-amber-600/40"
         style="background:rgba(245,158,11,.15);">
        <svg class="w-4 h-4 flex-shrink-0" style="color:#d97706;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
        </svg>
        <span class="text-xs font-bold tracking-wide uppercase" style="color:#92400e;">Votre lien de parrainage — À partager après chaque démo !</span>
    </div>
    <div class="ref-banner-body px-4 py-3 flex items-center gap-3">
        <code class="flex-1 text-sm font-mono font-semibold break-all" style="color:#78350f;">{{ $refLink }}</code>
        <button type="button"
                @click="navigator.clipboard.writeText('{{ $refLink }}'); copied = true; setTimeout(() => copied = false, 2000)"
                class="guide-copy-btn flex-shrink-0 flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold transition-all duration-200"
                :style="copied
                    ? 'background:#d1fae5; color:#065f46; border:1px solid #6ee7b7;'
                    : 'background:#fef3c7; color:#92400e; border:1px solid #fcd34d; cursor:pointer;'"
                :title="copied ? 'Copié !' : 'Copier le lien'">
            <svg x-show="!copied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            <svg x-show="copied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span x-text="copied ? 'Copié !' : 'Copier'"></span>
        </button>
    </div>
</div>
@endif

@php
$sections = [

['num'=>1, 'titre'=>'AVANT DE PARTIR &mdash; Préparer sa journée', 'html'=>'
<p><strong>Ce qu\'il faut avoir avec soi :</strong></p>
<ul>
<li>Téléphone chargé avec un <strong>compte démo Maëlya actif</strong> (3 prestations, 2 produits, 5 clients)</li>
<li>Flyers A5 avec QR code vers maelyagestion.com</li>
<li>Cartes de visite avec votre numéro WhatsApp et votre code parrainage</li>
<li>Fichier de suivi pour noter les contacts du jour</li>
</ul>
<p><strong>Meilleur moment pour visiter :</strong> 09h30–11h30 | 15h00–17h00 | Éviter vendredi et samedi (jours d\'affluence)</p>
<p><strong>Ciblage par quartier (tourner chaque semaine) :</strong><br>
Cocody/Riviera → Marcory/Zone 4 → Plateau/Adjamé → Yopougon → Treichville</p>
'],

['num'=>2, 'titre'=>'APPROCHE &mdash; Comment entrer dans l\'établissement', 'html'=>'
<p><strong>Les 3 règles d\'or :</strong></p>
<ol>
<li>Sourire et se présenter immédiatement — pas de mystère</li>
<li>Ne jamais interrompre un client en service — attendre ou revenir</li>
<li>Poser une question, ne pas réciter un discours — créer le dialogue</li>
</ol>
<p><strong>Script d\'entrée (30 secondes) :</strong></p>
<blockquote>«&nbsp;Bonjour ! Je m\'appelle [Prénom], je travaille pour Maëlya Gestion — c\'est une application ivoirienne pour aider les établissements à gérer leurs ventes et leurs clients depuis le téléphone. Est-ce que vous avez 2 petites minutes pour que je vous montre comment ça marche ?&nbsp;»</blockquote>
<p><strong>Si la personne dit «&nbsp;je suis occupé(e)&nbsp;» :</strong></p>
<blockquote>«&nbsp;Pas de souci ! Je peux repasser dans 30 minutes ou demain matin — ça vous arrange mieux quand ?&nbsp;»</blockquote>
<p><strong>Si la personne dit «&nbsp;j\'ai pas besoin&nbsp;» :</strong></p>
<blockquote>«&nbsp;Pas de problème. Juste une curiosité : vous suivez comment vos ventes en ce moment ? Cahier, téléphone, ou de tête ?&nbsp;» — Cette question relance presque toujours la conversation.</blockquote>
'],

['num'=>3, 'titre'=>'PITCH PRINCIPAL &mdash; La démonstration en 5 minutes', 'html'=>'
<p><em>Sortir le téléphone et montrer en direct. Ne pas parler dans le vide.</em></p>
<p><strong>Étape 1 — Identifier le besoin (1 min)</strong></p>
<table><thead><tr><th>Si l\'établissement a des employés</th><th>Si le gérant est seul</th><th>S\'il a plusieurs établissements</th></tr></thead>
<tbody><tr>
<td>«&nbsp;Vous savez combien chaque employé a encaissé aujourd\'hui ?&nbsp;»</td>
<td>«&nbsp;Vous notez vos ventes comment en ce moment ?&nbsp;»</td>
<td>«&nbsp;Vous êtes sur place tout le temps pour surveiller ?&nbsp;»</td>
</tr></tbody></table>
<blockquote>Laisser répondre. Écouter. Puis : <strong>«&nbsp;C\'est exactement pour ça que Maëlya a été créé. Laissez-moi vous montrer.&nbsp;»</strong></blockquote>
<p><strong>Étape 2 — Montrer la Caisse (1 min)</strong></p>
<blockquote>«&nbsp;Chaque vente est enregistrée en 10 secondes. Plus de cahier, plus de calculatrice. Le téléphone fait tout : sélection des prestations, prix automatique, mode de paiement (espèces, Wave, Orange Money, mixte), validation.&nbsp;»</blockquote>
<p><strong>Étape 3 — Montrer le Tableau de bord (30 sec)</strong></p>
<blockquote>«&nbsp;Là vous voyez en temps réel combien vous avez fait aujourd\'hui, ce mois-ci. Même si vous êtes à la maison, vous savez ce qui se passe dans votre établissement.&nbsp;»</blockquote>
<p><strong>Étape 4 — Montrer 1 ou 2 fonctionnalités selon le profil (2 min)</strong></p>
<ul>
<li><strong>Avec employés → Équipe :</strong> «&nbsp;Chaque employé a ses identifiants. Vous voyez combien chacun a fait. Fini les discussions sur les recettes.&nbsp;»</li>
<li><strong>Beaucoup de RDV → Agenda & Calendrier :</strong> «&nbsp;Email de confirmation automatique. Planning visuel avec calendrier. Déplacez un RDV en le glissant. Encaissement direct depuis le RDV.&nbsp;»</li>
<li><strong>Vente de produits → Stock :</strong> «&nbsp;Stock mis à jour automatiquement. Alerte quand un produit est presque épuisé. Commandes fournisseurs et inventaires physiques.&nbsp;»</li>
<li><strong>Fidélisation → Fidélité :</strong> «&nbsp;Programme de points automatique. Code cadeau quand le client atteint un palier. Anniversaires automatiques.&nbsp;»</li>
<li><strong>Rentabilité → Finances :</strong> «&nbsp;Bénéfice net calculé automatiquement (CA − dépenses). Trésorerie prévisionnelle sur 90 jours basée sur les RDV.&nbsp;»</li>
<li><strong>Visibilité → Vitrine + Réservation :</strong> «&nbsp;Page web publique. QR code à coller à l\'entrée. Les clients réservent directement depuis la page, même quand vous êtes fermé.&nbsp;»</li>
<li><strong>Portfolio → Galerie photos :</strong> «&nbsp;Chaque client a sa galerie avant/après dans son dossier. Montrez vos résultats, attirez de nouveaux clients.&nbsp;»</li>
</ul>
<p><strong>Étape 5 — Clôturer et proposer l\'essai (30 sec)</strong></p>
<blockquote>«&nbsp;C\'est tout. Simple, rapide, depuis votre téléphone. L\'essai est gratuit pendant 14 jours — vous avez accès à tout, sans carte bancaire. Est-ce que je peux vous aider à créer votre compte là maintenant ? Ça prend 2 minutes.&nbsp;»</blockquote>
'],

['num'=>4, 'titre'=>'LES FONCTIONNALITÉS &mdash; Mémo complet', 'html'=>'
<table><thead><tr><th>Module</th><th>Ce que ça fait</th><th>Argument clé</th></tr></thead>
<tbody>
<tr><td><strong>Scanner code-barres</strong></td><td>Scan caméra ou scanner externe USB/Bluetooth (essai + Premium+)</td><td>«&nbsp;Ajoutez les produits en un éclair&nbsp;»</td></tr>
<tr><td><strong>Caisse</strong></td><td>Ventes en 10s, espèces/Wave/OM/carte/mixte/crédit</td><td>«&nbsp;Plus jamais de calcul à la main&nbsp;»</td></tr>
<tr><td><strong>Ticket de caisse</strong></td><td>Numérique (WhatsApp) ou PDF imprimable</td><td>«&nbsp;Professionnel comme une caisse de supermarché&nbsp;»</td></tr>
<tr><td><strong>Facture PDF</strong></td><td>Facture numérotée pour chaque vente</td><td>«&nbsp;Vos clients ont une vraie facture&nbsp;»</td></tr>
<tr><td><strong>Devis & Factures</strong></td><td>Devis pro → facture en 1 clic, suivi paiements, PDF, WhatsApp</td><td>«&nbsp;Vos devis inspirent confiance&nbsp;»</td></tr>
<tr><td><strong>Boutique en ligne</strong></td><td>Page web publique, commandes 24h/24, gestion livraisons</td><td>«&nbsp;Vendez même quand le salon est fermé&nbsp;»</td></tr>
<tr><td><strong>Brouillons caisse</strong></td><td>Sauvegarder une vente en cours</td><td>«&nbsp;Reprenez où vous en étiez&nbsp;»</td></tr>
<tr><td><strong>Tableau de bord</strong></td><td>CA jour/mois, alertes stocks, anniversaires</td><td>«&nbsp;Tout d\'un coup d\'œil&nbsp;»</td></tr>
<tr><td><strong>Clients</strong></td><td>Fiche + historique complet</td><td>«&nbsp;Vous connaissez vos clients mieux qu\'eux-mêmes&nbsp;»</td></tr>
<tr><td><strong>Anniversaires</strong></td><td>Alerte J-1 + code cadeau automatique</td><td>«&nbsp;Vos clients se sentent valorisés&nbsp;»</td></tr>
<tr><td><strong>Agenda / RDV</strong></td><td>Planning + email confirmation automatique + encaissement</td><td>«&nbsp;Fini les oublis et les no-shows&nbsp;»</td></tr>
<tr><td><strong>Calendrier RDV</strong></td><td>Vue calendrier, déplacer les RDV en glissant (drag & drop)</td><td>«&nbsp;Gérez votre agenda comme un pro&nbsp;»</td></tr>
<tr><td><strong>Réservation en ligne</strong></td><td>Les clients réservent 24h/24 depuis votre vitrine</td><td>«&nbsp;Recevez des réservations même quand vous dormez&nbsp;»</td></tr>
<tr><td><strong>Prestations</strong></td><td>Catalogue services avec prix et durée</td><td>«&nbsp;Plus d\'erreur de prix&nbsp;»</td></tr>
<tr><td><strong>Produits</strong></td><td>Catalogue produits vendables avec stock</td><td>«&nbsp;Vendez aussi vos produits&nbsp;»</td></tr>
<tr><td><strong>Stocks</strong></td><td>Mise à jour auto + alerte seuil + CMP</td><td>«&nbsp;Ne tombez plus en rupture&nbsp;»</td></tr>
<tr><td><strong>Fidélité</strong></td><td>Points automatiques + codes cadeaux aux paliers</td><td>«&nbsp;Vos clients reviennent plus souvent&nbsp;»</td></tr>
<tr><td><strong>Codes de réduction</strong></td><td>Promos % ou montant fixe, validité, limites</td><td>«&nbsp;Boostez vos ventes&nbsp;»</td></tr>
<tr><td><strong>Avoirs</strong></td><td>Remboursement partiel → code réutilisable</td><td>«&nbsp;Gardez le client, même après un problème&nbsp;»</td></tr>
<tr><td><strong>Finances</strong></td><td>Dépenses, bénéfice net, export PDF, graphiques</td><td>«&nbsp;Vous savez enfin combien vous gagnez vraiment&nbsp;»</td></tr>
<tr><td><strong>Trésorerie prévisionnelle</strong></td><td>Projection entrées/sorties sur 7 à 90 jours</td><td>«&nbsp;Anticipez vos fins de mois difficiles&nbsp;»</td></tr>
<tr><td><strong>Mon équipe</strong></td><td>Comptes séparés par employé, permissions limitées</td><td>«&nbsp;Chacun encaisse, vous contrôlez&nbsp;»</td></tr>
<tr><td><strong>Page vitrine</strong></td><td>Page web publique + QR code téléchargeable</td><td>«&nbsp;Vos clients voient vos prix avant d\'appeler&nbsp;»</td></tr>
<tr><td><strong>Galerie photos</strong></td><td>Photos avant/après par client dans son dossier</td><td>«&nbsp;Montrez vos résultats, attirez de nouveaux clients&nbsp;»</td></tr>
<tr><td><strong>Bons de commande</strong></td><td>Commander chez vos fournisseurs, suivre la réception</td><td>«&nbsp;Zéro paperasse pour vos achats&nbsp;»</td></tr>
<tr><td><strong>Inventaire physique</strong></td><td>Compter votre stock, détecter les écarts et pertes</td><td>«&nbsp;Détectez les vols et les erreurs&nbsp;»</td></tr>
<tr><td><strong>Sondage satisfaction</strong></td><td>Lien envoyé par email ou WhatsApp après vente</td><td>«&nbsp;Vos clients notent, vous progressez&nbsp;»</td></tr>
<tr><td><strong>Avis clients</strong></td><td>Modération, affichage des étoiles sur la vitrine</td><td>«&nbsp;Les avis rassurent les nouveaux clients&nbsp;»</td></tr>
<tr><td><strong>Notifications</strong></td><td>Alertes stock, RDV, paiements, anniversaires, push</td><td>«&nbsp;Ne ratez plus aucun événement important&nbsp;»</td></tr>
<tr><td><strong>Multi-établissements</strong></td><td>Gérer plusieurs établissements, bascule instantanée</td><td>«&nbsp;Surveillez tout depuis votre téléphone&nbsp;»</td></tr>
<tr><td><strong>Comparatif</strong></td><td>Comparer CA, clients, top prestations entre établissements</td><td>«&nbsp;Quel établissement performe le mieux ?&nbsp;»</td></tr>
<tr><td><strong>Vente à crédit</strong></td><td>Échéancier, suivi paiements, rappels auto, fiche PDF</td><td>«&nbsp;Vendez même à ceux qui n\'ont pas tout de suite&nbsp;»</td></tr>
<tr><td><strong>Parrainage</strong></td><td>Inviter = jours gratuits pour les deux parties</td><td>«&nbsp;Faites-vous parrainer, payez moins cher&nbsp;»</td></tr>
</tbody></table>
'],

['num'=>5, 'titre'=>'LES TARIFS &mdash; Quoi dire', 'html'=>'
<blockquote>«&nbsp;Maëlya a 5 formules. Tout le monde commence par l\'essai gratuit de 14 jours.&nbsp;»</blockquote>
<table><thead><tr><th>Plan</th><th>Prix/mois</th><th>Pour qui</th><th>Ce qu\'on dit</th></tr></thead>
<tbody>
<tr><td><strong>Essai</strong></td><td>Gratuit 14j</td><td>Tout le monde</td><td>«&nbsp;Vous testez tout, sans rien payer, sans engagement&nbsp;»</td></tr>
<tr><td><strong>Premium</strong></td><td>4 900 FCFA</td><td>Établissement avec 1 à 3 employés</td><td>«&nbsp;Tout inclus : caisse, stock, fidélité, finances, équipe, vitrine&nbsp;»</td></tr>
<tr><td><strong>Premium+</strong></td><td>9 900 FCFA</td><td>Jusqu\'à 3 établissements, 10 employés max par établissement</td><td>«&nbsp;Pour gérer plusieurs établissements, avec vente à crédit et scanner code-barres&nbsp;»</td></tr>
<tr><td><strong>Ultra</strong></td><td>24 900 FCFA</td><td>Établissements illimités, employés illimités</td><td>«&nbsp;Pour les grandes chaînes, contrôle total sur tous les établissements&nbsp;»</td></tr>
</tbody></table>
<p><strong>Paiement :</strong> Orange Money ou Wave — Pas de carte bancaire, pas de compte bancaire nécessaire.</p>
'],

['num'=>6, 'titre'=>'RÉPONSES AUX OBJECTIONS', 'html'=>'
<p><strong>«&nbsp;C\'est trop cher&nbsp;»</strong></p>
<blockquote>«&nbsp;2 000 FCFA par mois, c\'est 67 FCFA par jour — moins qu\'un café. Et si ça vous fait encaisser ne serait-ce qu\'une prestation de plus par semaine, c\'est rentable. Commencez par l\'essai gratuit 14 jours, vous décidez après.&nbsp;»</blockquote>
<p><strong>«&nbsp;Je ne suis pas fort(e) en technologie&nbsp;»</strong></p>
<blockquote>«&nbsp;Si vous savez utiliser WhatsApp, vous saurez utiliser Maëlya. On vous aide à configurer le compte ici maintenant, et notre support WhatsApp répond en moins de 2h.&nbsp;»</blockquote>
<p><strong>«&nbsp;J\'ai déjà un cahier, ça marche&nbsp;»</strong></p>
<blockquote>«&nbsp;Un cahier ne vous dit pas combien vous avez fait ce mois-ci d\'un coup d\'œil. Il ne vous alerte pas quand un produit est épuisé. Il ne retient pas les anniversaires de vos clients. Il ne vous montre pas votre bénéfice net. Maëlya fait tout ça automatiquement.&nbsp;»</blockquote>
<p><strong>«&nbsp;Je vais en parler à mon associé(e)&nbsp;»</strong></p>
<blockquote>«&nbsp;Je peux vous envoyer une vidéo de présentation de 2 minutes sur WhatsApp maintenant — vous la lui montrez ce soir. Vous avez votre numéro disponible ?&nbsp;»</blockquote>
<p><strong>«&nbsp;C\'est quoi Maëlya, c\'est ivoirien ?&nbsp;»</strong></p>
<blockquote>«&nbsp;Oui, créé en Côte d\'Ivoire, pour les établissements ivoiriens. Support en français, paiement Orange Money et Wave, prix en FCFA.&nbsp;»</blockquote>
<p><strong>«&nbsp;Je vais réfléchir&nbsp;»</strong></p>
<blockquote>«&nbsp;L\'essai est gratuit, vous ne risquez rien. Je vous crée le compte maintenant et vous avez 14 jours — si ça ne vous convient pas, zéro engagement. On le fait maintenant ?&nbsp;»</blockquote>
'],

['num'=>7, 'titre'=>'SUIVI &mdash; Ce qu\'on fait après la visite', 'html'=>'
<p><strong>Noter dans votre fichier de suivi :</strong> Nom de l\'établissement · Nom du responsable · Numéro WhatsApp · 🔥 Chaud / 🟠 Tiède / ❄️ Froid · Date de la visite.</p>
<p><strong>Message WhatsApp J+1 (si pas inscrit) :</strong></p>
<blockquote>«&nbsp;Bonjour [Prénom] 👋 C\'est [Votre prénom] de Maëlya Gestion. On s\'est rencontré hier à votre établissement. Voici le lien : maelyagestion.com — ça prend 2 minutes. Je reste disponible si vous avez une question 😊&nbsp;»</blockquote>
<p><strong>Relance J+4 (si pas de réponse) :</strong></p>
<blockquote>«&nbsp;Bonjour ! Vous avez eu le temps de jeter un œil à Maëlya ? Je peux passer faire une démo rapide de 5 min à votre convenance cette semaine.&nbsp;»</blockquote>
<p><strong>Relance J+10 (si toujours rien) :</strong></p>
<blockquote>«&nbsp;Bonjour [Prénom] ! On offre l\'essai gratuit 14 jours — sans carte bancaire, sans engagement. C\'est le bon moment pour tester avant le week-end 😊&nbsp;»</blockquote>
<p>Après J+14 sans réponse → Marquer ❄️ Froid, revenir dans 1 mois.</p>
'],

['num'=>8, 'titre'=>'TOP 5 ARGUMENTS &mdash; À retenir absolument', 'html'=>'
<ol>
<li><strong>Caisse en 10 secondes</strong> → Plus rapide que noter sur un cahier</li>
<li><strong>Ventes en temps réel</strong> → Même quand vous n\'êtes pas sur place</li>
<li><strong>Vos employés encaissent, vous contrôlez</strong> → Fini les pertes inexpliquées</li>
<li><strong>Réservation en ligne 24h/24</strong> → Les clients réservent même quand vous êtes fermé</li>
<li><strong>Essai gratuit 14 jours, paiement Wave/OM</strong> → Aucun risque, aucune complication</li>
<li><em>Bonus :</em> galerie photos avant/après, calendrier interactif, inventaire physique, sondage satisfaction, trésorerie prévisionnelle — tout depuis votre téléphone</li>
</ol>
'],

['num'=>9, 'titre'=>'PROFILS ET ARGUMENTS PRIORITAIRES', 'html'=>'
<table><thead><tr><th>Type d\'établissement</th><th>Argument n°1</th><th>Argument n°2</th><th>Module à montrer</th></tr></thead>
<tbody>
<tr><td>Coiffure avec employés</td><td>Contrôle des ventes par employé</td><td>Calendrier RDV drag & drop</td><td>Équipe + Calendrier</td></tr>
<tr><td>Onglerie / Nail bar solo</td><td>Réservation en ligne 24h/24</td><td>Galerie photos avant/après</td><td>Vitrine + Galerie</td></tr>
<tr><td>Institut de beauté</td><td>Galerie avant/après + réservation</td><td>Agenda calendrier + fidélité</td><td>Galerie + Vitrine + RDV</td></tr>
<tr><td>Barbershop</td><td>Caisse professionnelle + équipe</td><td>Tickets imprimables</td><td>Caisse + Tickets</td></tr>
<tr><td>Spa / Multi-établissements</td><td>Comparatif multi-établissements (Premium+)</td><td>Contrôle à distance temps réel</td><td>Comparatif + Dashboard</td></tr>
<tr><td>Établissement avec boutique</td><td>Inventaire physique + bons commande</td><td>Alertes rupture stock</td><td>Inventaire + Fournisseurs</td></tr>
</tbody></table>
'],

];
@endphp

{{-- Accordéon --}}
<div class="space-y-2" x-data="{ open: 1 }">
    @foreach($sections as $s)
    <div class="rounded-2xl overflow-hidden border border-gray-200 dark:border-white/[0.08] bg-white dark:bg-gray-800/60">

        <button type="button"
                class="guide-btn"
                @click="open = (open === {{ $s['num'] }} ? null : {{ $s['num'] }})">
            <span class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                  style="background:linear-gradient(135deg,#9333ea,#ec4899);">{{ $s['num'] }}</span>
            <span class="guide-title">{!! $s['titre'] !!}</span>
            <svg class="w-4 h-4 flex-shrink-0 transition-transform duration-200"
                 style="color:#9ca3af;"
                 :style="open === {{ $s['num'] }} ? 'transform:rotate(180deg)' : ''"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="open === {{ $s['num'] }}" x-collapse>
            <div class="guide-content">{!! $s['html'] !!}</div>
        </div>

    </div>
    @endforeach
</div>

<p class="mt-6 text-center text-xs text-gray-400 dark:text-gray-600 pb-safe">
    Maëlya Gestion · maelyagestion.com · Support WhatsApp : réponse &lt; 2h · Document terrain v4 — Juin 2026 · 26 modules
</p>

<script>
/* Wrap tables pour scroll horizontal mobile */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.guide-content table').forEach(function (table) {
        if (table.parentNode.classList.contains('table-scroll')) return;
        var wrap = document.createElement('div');
        wrap.className = 'table-scroll';
        table.parentNode.insertBefore(wrap, table);
        wrap.appendChild(table);
    });
    /* Re-wrapper quand un accordéon s'ouvre (Alpine x-collapse) */
    document.querySelectorAll('[x-collapse]').forEach(function (el) {
        el.addEventListener('transitionend', function () {
            el.querySelectorAll('.guide-content table').forEach(function (table) {
                if (table.parentNode.classList.contains('table-scroll')) return;
                var wrap = document.createElement('div');
                wrap.className = 'table-scroll';
                table.parentNode.insertBefore(wrap, table);
                wrap.appendChild(table);
            });
        }, { once: false });
    });
});
</script>

@endsection
