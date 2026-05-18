@extends('layouts.commercial')
@section('title', 'Guide Porte-à-Porte')

@section('content')

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">🚶 Guide Porte-à-Porte</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Votre guide terrain complet pour prospecter les salons</p>
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

@if(Auth::user()->commercialProfile)
<div class="rounded-xl p-4 mb-6 flex items-start gap-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/40">
    <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <p class="text-sm text-amber-800 dark:text-amber-300">
        <strong>Votre lien de parrainage :</strong>
        maelyagestion.com/inscription?ref={{ Auth::user()->commercialProfile->code }}
        — À partager après chaque démo !
    </p>
</div>
@endif

@php
$sections = [
  ['num'=>1,'titre'=>'AVANT DE PARTIR — Préparer sa journée','html'=>'
<p><strong>Ce qu\'il faut avoir avec soi :</strong></p>
<ul>
<li>Téléphone chargé avec un <strong>compte démo Maëlya actif</strong> (3 prestations, 2 produits, 5 clients)</li>
<li>Flyers A5 avec QR code vers maelyagestion.com</li>
<li>Cartes de visite avec votre numéro WhatsApp et votre code parrainage</li>
<li>Google Sheet pour noter les contacts du jour</li>
</ul>
<p><strong>Meilleur moment pour visiter :</strong> 09h30–11h30 | 15h00–17h00 | Éviter vendredi et samedi</p>
<p><strong>Ciblage par quartier (tourner chaque semaine) :</strong><br>Cocody/Riviera → Marcory/Zone 4 → Plateau/Adjamé → Yopougon → Treichville</p>
'],
  ['num'=>2,'titre'=>'APPROCHE — Comment entrer dans le salon','html'=>'
<p><strong>Les 3 règles d\'or :</strong></p>
<ol>
<li>Sourire et se présenter immédiatement — pas de mystère</li>
<li>Ne jamais interrompre une cliente en service — attendre ou revenir</li>
<li>Poser une question, ne pas réciter un discours — créer le dialogue</li>
</ol>
<p><strong>Script d\'entrée (30 secondes) :</strong></p>
<blockquote>« Bonjour Madame ! Je m\'appelle [Prénom], je travaille pour Maëlya Gestion — c\'est une application ivoirienne pour aider les salons à gérer leurs ventes et leurs clientes depuis le téléphone. Est-ce que vous avez 2 petites minutes pour que je vous montre comment ça marche ? »</blockquote>
<p><strong>Si elle dit "je suis occupée" :</strong></p>
<blockquote>« Pas de souci ! Je vous laisse notre flyer, je peux repasser dans 30 minutes ou demain matin — ça vous arrange mieux quand ? »</blockquote>
<p><strong>Si elle dit "j\'ai pas besoin" :</strong></p>
<blockquote>« Pas de problème. Juste une curiosité : vous suivez comment vos ventes en ce moment ? Cahier, téléphone, ou de tête ? » — Cette question relance presque toujours la conversation.</blockquote>
'],
  ['num'=>3,'titre'=>'PITCH PRINCIPAL — La démonstration en 5 minutes','html'=>'
<p><em>Sortir le téléphone et montrer en direct. Ne pas parler dans le vide.</em></p>
<p><strong>Étape 1 — Identifier la douleur (1 min)</strong></p>
<table><thead><tr><th>Si elle a des employées</th><th>Si elle est seule</th><th>Si elle a plusieurs salons</th></tr></thead>
<tbody><tr><td>« Vous savez combien chaque employée a encaissé aujourd\'hui ? »</td><td>« Vous notez vos ventes comment en ce moment ? »</td><td>« Vous êtes sur place tout le temps pour surveiller ? »</td></tr></tbody></table>
<blockquote>Laisser répondre. Écouter. Puis : <strong>« C\'est exactement pour ça que Maëlya a été créé. Laissez-moi vous montrer. »</strong></blockquote>
<p><strong>Étape 2 — Montrer la Caisse (1 min)</strong></p>
<blockquote>« Chaque vente est enregistrée en 10 secondes. Plus de cahier, plus de calculatrice. Le téléphone fait tout. »</blockquote>
<p><strong>Étape 3 — Montrer le Tableau de bord (30 sec)</strong></p>
<blockquote>« Là vous voyez en temps réel combien vous avez fait aujourd\'hui, ce mois-ci. Même si vous êtes à la maison, vous savez ce qui se passe dans votre salon. »</blockquote>
<p><strong>Étape 4 — Montrer 1 ou 2 fonctionnalités selon son profil (2 min)</strong></p>
<ul>
<li><strong>Employées → Équipe :</strong> « Chaque employée a ses identifiants. Vous voyez combien chaque employée a fait. Fini les disputes sur les recettes. »</li>
<li><strong>Beaucoup de RDV → Agenda :</strong> « Email de confirmation automatique. Plus de rendez-vous oubliés. »</li>
<li><strong>Vente de produits → Stock :</strong> « Stock mis à jour automatiquement. Alerte quand un produit est presque épuisé. »</li>
<li><strong>Fidélisation → Fidélité :</strong> « Programme de points. Code cadeau automatique. Ça les fait revenir. »</li>
<li><strong>Bénéfices → Finances :</strong> « Bénéfice net calculé automatiquement. Export PDF pour votre comptable. »</li>
<li><strong>Visibilité → Page Vitrine :</strong> « Activez votre page publique. QR code à coller à l\'entrée du salon. »</li>
</ul>
<p><strong>Étape 5 — Clôturer et proposer l\'essai (30 sec)</strong></p>
<blockquote>« C\'est tout. Simple, rapide, depuis votre téléphone. L\'essai est gratuit pendant 14 jours — vous avez accès à tout, sans carte bancaire. Est-ce que je peux vous aider à créer votre compte là maintenant ? Ça prend 2 minutes. »</blockquote>
'],
  ['num'=>4,'titre'=>'LES FONCTIONNALITÉS — Mémo complet','html'=>'
<table><thead><tr><th>Module</th><th>Ce que ça fait</th><th>Argument clé</th></tr></thead>
<tbody>
<tr><td><strong>Caisse</strong></td><td>Ventes en 10s, cash/Wave/OM/mixte</td><td>« Plus jamais de calcul à la main »</td></tr>
<tr><td><strong>Ticket de caisse</strong></td><td>Numérique ou imprimable</td><td>« Professionnel comme une vraie caisse »</td></tr>
<tr><td><strong>Tableau de bord</strong></td><td>CA jour/mois, alertes stocks</td><td>« Tout d\'un coup d\'œil »</td></tr>
<tr><td><strong>Clients</strong></td><td>Fiche + historique visites</td><td>« Vous connaissez vos clientes mieux qu\'elles-mêmes »</td></tr>
<tr><td><strong>Anniversaires</strong></td><td>Alerte + code cadeau automatique</td><td>« Vos clientes se sentent choyées »</td></tr>
<tr><td><strong>Agenda / RDV</strong></td><td>Planning + email confirmation</td><td>« Fini les oublis et les no-shows »</td></tr>
<tr><td><strong>Prestations</strong></td><td>Catalogue services avec prix</td><td>« Plus d\'erreur de prix »</td></tr>
<tr><td><strong>Produits</strong></td><td>Catalogue produits vendables</td><td>« Vous vendez aussi les produits »</td></tr>
<tr><td><strong>Stocks</strong></td><td>Mise à jour auto + alerte rupture</td><td>« Ne tombez plus en rupture »</td></tr>
<tr><td><strong>Fidélité</strong></td><td>Points + code cadeau automatique</td><td>« Vos clientes reviennent »</td></tr>
<tr><td><strong>Codes de réduction</strong></td><td>Promos % ou montant fixe</td><td>« Boostez les ventes »</td></tr>
<tr><td><strong>Finances</strong></td><td>Dépenses, bénéfice net, export PDF</td><td>« Vous savez enfin combien vous gagnez vraiment »</td></tr>
<tr><td><strong>Mon équipe</strong></td><td>Comptes séparés employées</td><td>« Chacune encaisse, vous contrôlez »</td></tr>
<tr><td><strong>Page vitrine</strong></td><td>Page web publique + QR code</td><td>« Vos clientes trouvent vos prix avant d\'appeler »</td></tr>
<tr><td><strong>Multi-établissements</strong></td><td>Gérer plusieurs salons</td><td>« Vous surveillez tout depuis votre téléphone »</td></tr>
<tr><td><strong>Parrainage</strong></td><td>Inviter = mois gratuits</td><td>« Faites-vous parrainer, payez moins cher »</td></tr>
</tbody></table>
'],
  ['num'=>5,'titre'=>'LES TARIFS — Quoi dire','html'=>'
<blockquote>« Maëlya a 4 formules. Tout le monde commence par l\'essai gratuit de 14 jours. »</blockquote>
<table><thead><tr><th>Plan</th><th>Prix/mois</th><th>Pour qui</th><th>Ce qu\'on dit</th></tr></thead>
<tbody>
<tr><td><strong>Essai</strong></td><td>Gratuit 14j</td><td>Tout le monde</td><td>« Vous testez tout, sans rien payer »</td></tr>
<tr><td><strong>Basic</strong></td><td>2 000 FCFA</td><td>Solo, sans employées</td><td>« Moins cher qu\'un café par jour »</td></tr>
<tr><td><strong>Premium</strong></td><td>4 900 FCFA</td><td>Salon avec 1-3 employées</td><td>« Tout inclus : clients, stock, fidélité, finances »</td></tr>
<tr><td><strong>Premium+</strong></td><td>9 900 FCFA</td><td>Multi-salons</td><td>« Pour les propriétaires de plusieurs établissements »</td></tr>
</tbody></table>
<p><strong>Le paiement se fait par Orange Money ou Wave. Pas de carte bancaire, pas de compte bancaire nécessaire.</strong></p>
'],
  ['num'=>6,'titre'=>'RÉPONSES AUX OBJECTIONS','html'=>'
<p><strong>« C\'est trop cher »</strong></p>
<blockquote>« 2 000 FCFA par mois, c\'est 67 FCFA par jour — moins qu\'un café. Et si ça vous fait encaisser ne serait-ce qu\'une prestation de plus par semaine grâce à la fidélité, c\'est rentable. Commencez par l\'essai gratuit 14 jours, vous décidez après. »</blockquote>
<p><strong>« Je ne suis pas forte en technologie »</strong></p>
<blockquote>« Si vous savez utiliser WhatsApp, vous saurez utiliser Maëlya. On vous aide à configurer le compte ici maintenant, et notre support WhatsApp répond en moins de 2h. »</blockquote>
<p><strong>« J\'ai déjà un cahier, ça marche »</strong></p>
<blockquote>« Un cahier ne vous dit pas combien vous avez fait ce mois-ci d\'un coup d\'œil. Il ne vous alerte pas quand un produit est épuisé. Il ne retient pas les anniversaires de vos clientes. Maëlya fait tout ça automatiquement. »</blockquote>
<p><strong>« Je vais en parler à mon mari / associée »</strong></p>
<blockquote>« Je peux vous envoyer une vidéo de présentation de 2 minutes sur WhatsApp maintenant — vous la lui montrez ce soir. Vous avez votre numéro disponible ? »</blockquote>
<p><strong>« C\'est quoi Maëlya, c\'est ivoirien ? »</strong></p>
<blockquote>« Oui, créée en Côte d\'Ivoire, pour les salons ivoiriens. Support en français, paiement Orange Money et Wave, prix en FCFA. »</blockquote>
<p><strong>« Je vais réfléchir »</strong></p>
<blockquote>« L\'essai est gratuit, vous ne risquez rien à essayer. Je vous crée le compte maintenant et vous avez 14 jours pour voir si ça vous convient — si non, vous supprimez et zéro engagement. On le fait maintenant ? »</blockquote>
'],
  ['num'=>7,'titre'=>'SUIVI — Ce qu\'on fait après la visite','html'=>'
<p><strong>Noter dans le Google Sheet :</strong> Nom du salon, nom de la gérante, numéro WhatsApp, niveau d\'intérêt (🔥 Chaud / 🟠 Tiède / ❄️ Froid), date de la visite.</p>
<p><strong>Message WhatsApp J+1 (si pas inscrite) :</strong></p>
<blockquote>« Bonjour [Prénom] 👋 C\'est [Votre prénom] de Maëlya Gestion. On s\'est rencontré hier au salon. Voici le lien pour créer votre compte gratuit : maelyagestion.com — ça prend 2 minutes. Je reste disponible si vous avez une question 😊 »</blockquote>
<p><strong>Relance J+4 (si pas de réponse) :</strong></p>
<blockquote>« Bonjour ! Vous avez eu le temps de jeter un œil à Maëlya ? Je peux passer faire une démo rapide de 5 min à votre convenance cette semaine. »</blockquote>
<p><strong>Relance J+10 (si toujours rien) :</strong></p>
<blockquote>« Bonjour [Prénom] ! On offre l\'essai gratuit 14 jours — sans carte bancaire, sans engagement. C\'est le bon moment pour tester avant le week-end 😊 »</blockquote>
<p>Après J+14 sans réponse → Marquer ❄️ Froid, revenir dans 1 mois.</p>
'],
  ['num'=>8,'titre'=>'TOP 5 ARGUMENTS — À retenir absolument','html'=>'
<ol>
<li><strong>Caisse en 10 secondes</strong> → Plus rapide que noter sur un cahier</li>
<li><strong>Ventes en temps réel</strong> → Même quand vous n\'êtes pas là</li>
<li><strong>Vos employées encaissent, vous contrôlez</strong> → Fini les pertes inexpliquées</li>
<li><strong>Essai gratuit 14 jours, paiement Wave/OM</strong> → Aucun risque, aucune complication</li>
<li><strong>Page vitrine avec QR code</strong> → Vos clientes voient vos prix sur internet</li>
</ol>
'],
  ['num'=>9,'titre'=>'PROFILS ET ARGUMENTS PRIORITAIRES','html'=>'
<table><thead><tr><th>Type de salon</th><th>Argument n°1</th><th>Argument n°2</th><th>Module à montrer</th></tr></thead>
<tbody>
<tr><td>Salon de coiffure avec employées</td><td>Contrôle des ventes par employée</td><td>Tableau de bord temps réel</td><td>Équipe + Dashboard</td></tr>
<tr><td>Nail bar solo</td><td>Caisse rapide + clients fidèles</td><td>Page vitrine QR code</td><td>Caisse + Vitrine</td></tr>
<tr><td>Institut de beauté</td><td>Agenda RDV + fidélité</td><td>Finances & bénéfice net</td><td>RDV + Fidélité</td></tr>
<tr><td>Barbershop</td><td>Caisse professionnelle + équipe</td><td>Tickets imprimables</td><td>Caisse + Tickets</td></tr>
<tr><td>Spa / multi-salons</td><td>Multi-établissements</td><td>Contrôle à distance</td><td>Dashboard complet</td></tr>
</tbody></table>
'],
];
@endphp

<div class="space-y-3" x-data="{ open: 1 }">
    @foreach($sections as $s)
    <div class="rounded-2xl border border-gray-200 dark:border-gray-700/60 bg-white dark:bg-gray-800/50 overflow-hidden">
        <button @click="open = (open === {{ $s['num'] }} ? null : {{ $s['num'] }})"
                class="w-full flex items-center gap-3 px-5 py-4 text-left hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
            <span class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                  style="background: linear-gradient(135deg, #9333ea, #ec4899);">{{ $s['num'] }}</span>
            <span class="font-semibold text-sm text-gray-900 dark:text-white flex-1">{{ $s['titre'] }}</span>
            <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0"
                 :class="open === {{ $s['num'] }} ? 'rotate-180' : ''"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="open === {{ $s['num'] }}"
             x-collapse
             class="px-5 pb-5 border-t border-gray-100 dark:border-gray-700/40 pt-4
                    prose prose-sm dark:prose-invert max-w-none
                    prose-blockquote:border-l-4 prose-blockquote:border-purple-400 prose-blockquote:bg-purple-50 dark:prose-blockquote:bg-purple-950/30 prose-blockquote:rounded-r-lg prose-blockquote:px-4 prose-blockquote:py-2 prose-blockquote:not-italic
                    prose-table:text-xs prose-th:bg-gray-50 dark:prose-th:bg-gray-700/50">
            {!! $s['html'] !!}
        </div>
    </div>
    @endforeach
</div>

<p class="mt-6 text-center text-xs text-gray-400 dark:text-gray-600">
    Maëlya Gestion &middot; maelyagestion.com &middot; Support WhatsApp : réponse &lt; 2h &middot; Document terrain v2 — Mai 2026
</p>

@endsection
