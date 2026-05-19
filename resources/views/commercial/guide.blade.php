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

['num'=>1, 'titre'=>'AVANT DE PARTIR &mdash; Pr&eacute;parer sa journ&eacute;e', 'html'=>'
<p><strong>Ce qu\'il faut avoir avec soi :</strong></p>
<ul>
<li>T&eacute;l&eacute;phone charg&eacute; avec un <strong>compte d&eacute;mo Ma&euml;lya actif</strong> (3 prestations, 2 produits, 5 clients)</li>
<li>Flyers A5 avec QR code vers maelyagestion.com</li>
<li>Cartes de visite avec votre num&eacute;ro WhatsApp et votre code parrainage</li>
<li>Google Sheet pour noter les contacts du jour</li>
</ul>
<p><strong>Meilleur moment pour visiter :</strong> 09h30&ndash;11h30 | 15h00&ndash;17h00 | &Eacute;viter vendredi et samedi</p>
<p><strong>Ciblage par quartier (tourner chaque semaine) :</strong><br>
Cocody/Riviera &rarr; Marcory/Zone 4 &rarr; Plateau/Adjam&eacute; &rarr; Yopougon &rarr; Treichville</p>
'],

['num'=>2, 'titre'=>'APPROCHE &mdash; Comment entrer dans le salon', 'html'=>'
<p><strong>Les 3 r&egrave;gles d\'or :</strong></p>
<ol>
<li>Sourire et se pr&eacute;senter imm&eacute;diatement &mdash; pas de myst&egrave;re</li>
<li>Ne jamais interrompre une cliente en service &mdash; attendre ou revenir</li>
<li>Poser une question, ne pas r&eacute;citer un discours &mdash; cr&eacute;er le dialogue</li>
</ol>
<p><strong>Script d\'entr&eacute;e (30 secondes) :</strong></p>
<blockquote>&laquo;&nbsp;Bonjour Madame&nbsp;! Je m\'appelle [Pr&eacute;nom], je travaille pour Ma&euml;lya Gestion &mdash; c\'est une application ivoirienne pour aider les salons &agrave; g&eacute;rer leurs ventes et leurs clientes depuis le t&eacute;l&eacute;phone. Est-ce que vous avez 2 petites minutes pour que je vous montre comment &ccedil;a marche&nbsp;?&nbsp;&raquo;</blockquote>
<p><strong>Si elle dit &laquo;&nbsp;je suis occup&eacute;e&nbsp;&raquo; :</strong></p>
<blockquote>&laquo;&nbsp;Pas de souci&nbsp;! Je peux repasser dans 30 minutes ou demain matin &mdash; &ccedil;a vous arrange mieux quand&nbsp;?&nbsp;&raquo;</blockquote>
<p><strong>Si elle dit &laquo;&nbsp;j\'ai pas besoin&nbsp;&raquo; :</strong></p>
<blockquote>&laquo;&nbsp;Pas de probl&egrave;me. Juste une curiosit&eacute;&nbsp;: vous suivez comment vos ventes en ce moment&nbsp;? Cahier, t&eacute;l&eacute;phone, ou de t&ecirc;te&nbsp;?&nbsp;&raquo; &mdash; Cette question relance presque toujours la conversation.</blockquote>
'],

['num'=>3, 'titre'=>'PITCH PRINCIPAL &mdash; La d&eacute;monstration en 5 minutes', 'html'=>'
<p><em>Sortir le t&eacute;l&eacute;phone et montrer en direct. Ne pas parler dans le vide.</em></p>
<p><strong>&Eacute;tape 1 &mdash; Identifier la douleur (1 min)</strong></p>
<table><thead><tr><th>Si elle a des employ&eacute;es</th><th>Si elle est seule</th><th>Si elle a plusieurs salons</th></tr></thead>
<tbody><tr>
<td>&laquo;&nbsp;Vous savez combien chaque employ&eacute;e a encaiss&eacute; aujourd\'hui&nbsp;?&nbsp;&raquo;</td>
<td>&laquo;&nbsp;Vous notez vos ventes comment en ce moment&nbsp;?&nbsp;&raquo;</td>
<td>&laquo;&nbsp;Vous &ecirc;tes sur place tout le temps pour surveiller&nbsp;?&nbsp;&raquo;</td>
</tr></tbody></table>
<blockquote>Laisser r&eacute;pondre. &Eacute;couter. Puis : <strong>&laquo;&nbsp;C\'est exactement pour &ccedil;a que Ma&euml;lya a &eacute;t&eacute; cr&eacute;&eacute;. Laissez-moi vous montrer.&nbsp;&raquo;</strong></blockquote>
<p><strong>&Eacute;tape 2 &mdash; Montrer la Caisse (1 min)</strong></p>
<blockquote>&laquo;&nbsp;Chaque vente est enregistr&eacute;e en 10 secondes. Plus de cahier, plus de calculatrice. Le t&eacute;l&eacute;phone fait tout.&nbsp;&raquo;</blockquote>
<p><strong>&Eacute;tape 3 &mdash; Montrer le Tableau de bord (30 sec)</strong></p>
<blockquote>&laquo;&nbsp;L&agrave; vous voyez en temps r&eacute;el combien vous avez fait aujourd\'hui, ce mois-ci. M&ecirc;me si vous &ecirc;tes &agrave; la maison, vous savez ce qui se passe dans votre salon.&nbsp;&raquo;</blockquote>
<p><strong>&Eacute;tape 4 &mdash; Montrer 1 ou 2 fonctionnalit&eacute;s selon son profil (2 min)</strong></p>
<ul>
<li><strong>Employ&eacute;es &rarr; &Eacute;quipe :</strong> &laquo;&nbsp;Chaque employ&eacute;e a ses identifiants. Vous voyez combien elle a fait. Fini les disputes sur les recettes.&nbsp;&raquo;</li>
<li><strong>Beaucoup de RDV &rarr; Agenda :</strong> &laquo;&nbsp;Email de confirmation automatique. Plus de rendez-vous oubli&eacute;s.&nbsp;&raquo;</li>
<li><strong>Vente de produits &rarr; Stock :</strong> &laquo;&nbsp;Stock mis &agrave; jour automatiquement. Alerte quand un produit est presque &eacute;puis&eacute;.&nbsp;&raquo;</li>
<li><strong>Fid&eacute;lisation &rarr; Fid&eacute;lit&eacute; :</strong> &laquo;&nbsp;Programme de points. Code cadeau automatique. &Ccedil;a les fait revenir.&nbsp;&raquo;</li>
<li><strong>B&eacute;n&eacute;fices &rarr; Finances :</strong> &laquo;&nbsp;B&eacute;n&eacute;fice net calcul&eacute; automatiquement. Export PDF pour votre comptable.&nbsp;&raquo;</li>
<li><strong>Visibilit&eacute; &rarr; Page Vitrine :</strong> &laquo;&nbsp;Activez votre page publique. QR code &agrave; coller &agrave; l\'entr&eacute;e du salon.&nbsp;&raquo;</li>
</ul>
<p><strong>&Eacute;tape 5 &mdash; Cl&ocirc;turer et proposer l\'essai (30 sec)</strong></p>
<blockquote>&laquo;&nbsp;C\'est tout. Simple, rapide, depuis votre t&eacute;l&eacute;phone. L\'essai est gratuit pendant 14 jours &mdash; vous avez acc&egrave;s &agrave; tout, sans carte bancaire. Est-ce que je peux vous aider &agrave; cr&eacute;er votre compte l&agrave; maintenant&nbsp;? &Ccedil;a prend 2 minutes.&nbsp;&raquo;</blockquote>
'],

['num'=>4, 'titre'=>'LES FONCTIONNALIT&Eacute;S &mdash; M&eacute;mo complet', 'html'=>'
<table><thead><tr><th>Module</th><th>Ce que &ccedil;a fait</th><th>Argument cl&eacute;</th></tr></thead>
<tbody>
<tr><td><strong>Caisse</strong></td><td>Ventes en 10s, cash/Wave/OM/mixte</td><td>&laquo;&nbsp;Plus jamais de calcul &agrave; la main&nbsp;&raquo;</td></tr>
<tr><td><strong>Ticket de caisse</strong></td><td>Num&eacute;rique ou imprimable</td><td>&laquo;&nbsp;Professionnel comme une vraie caisse&nbsp;&raquo;</td></tr>
<tr><td><strong>Tableau de bord</strong></td><td>CA jour/mois, alertes stocks</td><td>&laquo;&nbsp;Tout d\'un coup d\'&oelig;il&nbsp;&raquo;</td></tr>
<tr><td><strong>Clients</strong></td><td>Fiche + historique visites</td><td>&laquo;&nbsp;Vous connaissez vos clientes mieux qu\'elles-m&ecirc;mes&nbsp;&raquo;</td></tr>
<tr><td><strong>Anniversaires</strong></td><td>Alerte + code cadeau automatique</td><td>&laquo;&nbsp;Vos clientes se sentent choy&eacute;es&nbsp;&raquo;</td></tr>
<tr><td><strong>Agenda / RDV</strong></td><td>Planning + email confirmation</td><td>&laquo;&nbsp;Fini les oublis et les no-shows&nbsp;&raquo;</td></tr>
<tr><td><strong>Prestations</strong></td><td>Catalogue services avec prix</td><td>&laquo;&nbsp;Plus d\'erreur de prix&nbsp;&raquo;</td></tr>
<tr><td><strong>Produits</strong></td><td>Catalogue produits vendables</td><td>&laquo;&nbsp;Vous vendez aussi les produits&nbsp;&raquo;</td></tr>
<tr><td><strong>Stocks</strong></td><td>Mise &agrave; jour auto + alerte rupture</td><td>&laquo;&nbsp;Ne tombez plus en rupture&nbsp;&raquo;</td></tr>
<tr><td><strong>Fid&eacute;lit&eacute;</strong></td><td>Points + code cadeau automatique</td><td>&laquo;&nbsp;Vos clientes reviennent&nbsp;&raquo;</td></tr>
<tr><td><strong>Codes de r&eacute;duction</strong></td><td>Promos % ou montant fixe</td><td>&laquo;&nbsp;Boostez les ventes&nbsp;&raquo;</td></tr>
<tr><td><strong>Finances</strong></td><td>D&eacute;penses, b&eacute;n&eacute;fice net, export PDF</td><td>&laquo;&nbsp;Vous savez enfin combien vous gagnez vraiment&nbsp;&raquo;</td></tr>
<tr><td><strong>Mon &eacute;quipe</strong></td><td>Comptes s&eacute;par&eacute;s employ&eacute;es</td><td>&laquo;&nbsp;Chacune encaisse, vous contr&ocirc;lez&nbsp;&raquo;</td></tr>
<tr><td><strong>Page vitrine</strong></td><td>Page web publique + QR code</td><td>&laquo;&nbsp;Vos clientes trouvent vos prix avant d\'appeler&nbsp;&raquo;</td></tr>
<tr><td><strong>Multi-&eacute;tablissements</strong></td><td>G&eacute;rer plusieurs salons</td><td>&laquo;&nbsp;Vous surveillez tout depuis votre t&eacute;l&eacute;phone&nbsp;&raquo;</td></tr>
<tr><td><strong>Parrainage</strong></td><td>Inviter = mois gratuits</td><td>&laquo;&nbsp;Faites-vous parrainer, payez moins cher&nbsp;&raquo;</td></tr>
</tbody></table>
'],

['num'=>5, 'titre'=>'LES TARIFS &mdash; Quoi dire', 'html'=>'
<blockquote>&laquo;&nbsp;Ma&euml;lya a 4 formules. Tout le monde commence par l\'essai gratuit de 14 jours.&nbsp;&raquo;</blockquote>
<table><thead><tr><th>Plan</th><th>Prix/mois</th><th>Pour qui</th><th>Ce qu\'on dit</th></tr></thead>
<tbody>
<tr><td><strong>Essai</strong></td><td>Gratuit 14j</td><td>Tout le monde</td><td>&laquo;&nbsp;Vous testez tout, sans rien payer&nbsp;&raquo;</td></tr>
<tr><td><strong>Basic</strong></td><td>2 000 FCFA</td><td>Solo, sans employ&eacute;es</td><td>&laquo;&nbsp;Moins cher qu\'un caf&eacute; par jour&nbsp;&raquo;</td></tr>
<tr><td><strong>Premium</strong></td><td>4 900 FCFA</td><td>Salon avec 1-3 employ&eacute;es</td><td>&laquo;&nbsp;Tout inclus : clients, stock, fid&eacute;lit&eacute;, finances&nbsp;&raquo;</td></tr>
<tr><td><strong>Premium+</strong></td><td>9 900 FCFA</td><td>Multi-salons</td><td>&laquo;&nbsp;Pour les propri&eacute;taires de plusieurs &eacute;tablissements&nbsp;&raquo;</td></tr>
</tbody></table>
<p><strong>Paiement :</strong> Orange Money ou Wave &mdash; Pas de carte bancaire, pas de compte bancaire n&eacute;cessaire.</p>
'],

['num'=>6, 'titre'=>'R&Eacute;PONSES AUX OBJECTIONS', 'html'=>'
<p><strong>&laquo;&nbsp;C\'est trop cher&nbsp;&raquo;</strong></p>
<blockquote>&laquo;&nbsp;2 000 FCFA par mois, c\'est 67 FCFA par jour &mdash; moins qu\'un caf&eacute;. Et si &ccedil;a vous fait encaisser ne serait-ce qu\'une prestation de plus par semaine, c\'est rentable. Commencez par l\'essai gratuit 14 jours, vous d&eacute;cidez apr&egrave;s.&nbsp;&raquo;</blockquote>
<p><strong>&laquo;&nbsp;Je ne suis pas forte en technologie&nbsp;&raquo;</strong></p>
<blockquote>&laquo;&nbsp;Si vous savez utiliser WhatsApp, vous saurez utiliser Ma&euml;lya. On vous aide &agrave; configurer le compte ici maintenant, et notre support WhatsApp r&eacute;pond en moins de 2h.&nbsp;&raquo;</blockquote>
<p><strong>&laquo;&nbsp;J\'ai d&eacute;j&agrave; un cahier, &ccedil;a marche&nbsp;&raquo;</strong></p>
<blockquote>&laquo;&nbsp;Un cahier ne vous dit pas combien vous avez fait ce mois-ci d\'un coup d\'&oelig;il. Il ne vous alerte pas quand un produit est &eacute;puis&eacute;. Il ne retient pas les anniversaires de vos clientes. Ma&euml;lya fait tout &ccedil;a automatiquement.&nbsp;&raquo;</blockquote>
<p><strong>&laquo;&nbsp;Je vais en parler &agrave; mon mari / associ&eacute;e&nbsp;&raquo;</strong></p>
<blockquote>&laquo;&nbsp;Je peux vous envoyer une vid&eacute;o de pr&eacute;sentation de 2 minutes sur WhatsApp maintenant &mdash; vous la lui montrez ce soir. Vous avez votre num&eacute;ro disponible&nbsp;?&nbsp;&raquo;</blockquote>
<p><strong>&laquo;&nbsp;C\'est quoi Ma&euml;lya, c\'est ivoirien&nbsp;?&nbsp;&raquo;</strong></p>
<blockquote>&laquo;&nbsp;Oui, cr&eacute;&eacute;e en C&ocirc;te d\'Ivoire, pour les salons ivoiriens. Support en fran&ccedil;ais, paiement Orange Money et Wave, prix en FCFA.&nbsp;&raquo;</blockquote>
<p><strong>&laquo;&nbsp;Je vais r&eacute;fl&eacute;chir&nbsp;&raquo;</strong></p>
<blockquote>&laquo;&nbsp;L\'essai est gratuit, vous ne risquez rien. Je vous cr&eacute;e le compte maintenant et vous avez 14 jours &mdash; si non, z&eacute;ro engagement. On le fait maintenant&nbsp;?&nbsp;&raquo;</blockquote>
'],

['num'=>7, 'titre'=>'SUIVI &mdash; Ce qu\'on fait apr&egrave;s la visite', 'html'=>'
<p><strong>Noter dans le Google Sheet :</strong> Nom du salon &middot; Nom de la g&eacute;rante &middot; Num&eacute;ro WhatsApp &middot; &#x1F525; Chaud / &#x1F7E0; Ti&egrave;de / &#x274C; Froid &middot; Date de la visite.</p>
<p><strong>Message WhatsApp J+1 (si pas inscrite) :</strong></p>
<blockquote>&laquo;&nbsp;Bonjour [Pr&eacute;nom] &#x1F44B; C\'est [Votre pr&eacute;nom] de Ma&euml;lya Gestion. On s\'est rencontr&eacute; hier au salon. Voici le lien : maelyagestion.com &mdash; &ccedil;a prend 2 minutes. Je reste disponible si vous avez une question &#x1F60A;&nbsp;&raquo;</blockquote>
<p><strong>Relance J+4 (si pas de r&eacute;ponse) :</strong></p>
<blockquote>&laquo;&nbsp;Bonjour&nbsp;! Vous avez eu le temps de jeter un &oelig;il &agrave; Ma&euml;lya&nbsp;? Je peux passer faire une d&eacute;mo rapide de 5 min &agrave; votre convenance cette semaine.&nbsp;&raquo;</blockquote>
<p><strong>Relance J+10 (si toujours rien) :</strong></p>
<blockquote>&laquo;&nbsp;Bonjour [Pr&eacute;nom]&nbsp;! On offre l\'essai gratuit 14 jours &mdash; sans carte bancaire, sans engagement. C\'est le bon moment pour tester avant le week-end &#x1F60A;&nbsp;&raquo;</blockquote>
<p>Apr&egrave;s J+14 sans r&eacute;ponse &rarr; Marquer &#x274C; Froid, revenir dans 1 mois.</p>
'],

['num'=>8, 'titre'=>'TOP 5 ARGUMENTS &mdash; &Agrave; retenir absolument', 'html'=>'
<ol>
<li><strong>Caisse en 10 secondes</strong> &rarr; Plus rapide que noter sur un cahier</li>
<li><strong>Ventes en temps r&eacute;el</strong> &rarr; M&ecirc;me quand vous n\'&ecirc;tes pas l&agrave;</li>
<li><strong>Vos employ&eacute;es encaissent, vous contr&ocirc;lez</strong> &rarr; Fini les pertes inexplliqu&eacute;es</li>
<li><strong>Essai gratuit 14 jours, paiement Wave/OM</strong> &rarr; Aucun risque, aucune complication</li>
<li><strong>Page vitrine avec QR code</strong> &rarr; Vos clientes voient vos prix sur internet</li>
</ol>
'],

['num'=>9, 'titre'=>'PROFILS ET ARGUMENTS PRIORITAIRES', 'html'=>'
<table><thead><tr><th>Type de salon</th><th>Argument n&deg;1</th><th>Argument n&deg;2</th><th>Module &agrave; montrer</th></tr></thead>
<tbody>
<tr><td>Salon de coiffure avec employ&eacute;es</td><td>Contr&ocirc;le des ventes par employ&eacute;e</td><td>Tableau de bord temps r&eacute;el</td><td>&Eacute;quipe + Dashboard</td></tr>
<tr><td>Nail bar solo</td><td>Caisse rapide + clients fid&egrave;les</td><td>Page vitrine QR code</td><td>Caisse + Vitrine</td></tr>
<tr><td>Institut de beaut&eacute;</td><td>Agenda RDV + fid&eacute;lit&eacute;</td><td>Finances &amp; b&eacute;n&eacute;fice net</td><td>RDV + Fid&eacute;lit&eacute;</td></tr>
<tr><td>Barbershop</td><td>Caisse professionnelle + &eacute;quipe</td><td>Tickets imprimables</td><td>Caisse + Tickets</td></tr>
<tr><td>Spa / multi-salons</td><td>Multi-&eacute;tablissements</td><td>Contr&ocirc;le &agrave; distance</td><td>Dashboard complet</td></tr>
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
    Ma&euml;lya Gestion &middot; maelyagestion.com &middot; Support WhatsApp : r&eacute;ponse &lt; 2h &middot; Document terrain v2 &mdash; Mai 2026
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
