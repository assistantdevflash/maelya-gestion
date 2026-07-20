<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\Dashboard\CodeReductionController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\ClientController;
use App\Http\Controllers\Dashboard\PrestationController;
use App\Http\Controllers\Dashboard\CategoriePrestationController;
use App\Http\Controllers\Dashboard\CategorieProduitController;
use App\Http\Controllers\Dashboard\ProduitController;
use App\Http\Controllers\Dashboard\VenteController;
use App\Http\Controllers\Dashboard\StockController;
use App\Http\Controllers\Dashboard\FinanceController;
use App\Http\Controllers\Dashboard\AbonnementController;
use App\Http\Controllers\Dashboard\EmployeController;
use App\Http\Controllers\Dashboard\MesInstitutsController;
use App\Http\Controllers\Dashboard\ParrainageController;
use App\Http\Controllers\Dashboard\FideliteController;
use App\Http\Controllers\Dashboard\ProfilController;
use App\Http\Controllers\Dashboard\AuditLogController;
use App\Http\Controllers\Dashboard\RdvController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminInstitutController;
use App\Http\Controllers\Admin\AdminAbonnementController;
use App\Http\Controllers\Admin\AdminPlanController;
use App\Http\Controllers\Admin\AdminConfigController;
use App\Http\Controllers\Admin\AdminMessageController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminFinanceController;
use App\Http\Controllers\Admin\AdminOffreController;
use App\Http\Controllers\Admin\AdminCommercialController;
use App\Http\Controllers\Admin\AdminEmailController;
use App\Http\Controllers\Admin\AdminLogsController;
use App\Http\Controllers\Admin\AdminPushDebugController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\Commercial\CommercialController;
use App\Http\Controllers\Auth\InscriptionController;
use App\Http\Controllers\VitrineController;
use App\Http\Controllers\BoutiqueController;
use App\Http\Controllers\Dashboard\BoutiqueConfigController;
use App\Http\Controllers\Dashboard\CommandeController;
use Illuminate\Support\Facades\Route;

// ─── Landing Page ─────────────────────────────────────────────────────────────
Route::get('/', [LandingController::class, 'index'])->name('home');

// ─── Push Notifications ───────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::post('/push/subscribe',   [PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::post('/push/unsubscribe', [PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/tout-lire', [\App\Http\Controllers\NotificationController::class, 'toutLire'])->name('notifications.tout-lire');
});
Route::get('/a-propos', [LandingController::class, 'apropos'])->name('about');
Route::get('/faq', [LandingController::class, 'faq'])->name('faq');
Route::get('/contact', [LandingController::class, 'contact'])->name('contact');
Route::post('/contact', [LandingController::class, 'sendContact'])->name('contact.send')->middleware('throttle:contact');
Route::get('/mentions-legales', [LandingController::class, 'mentionsLegales'])->name('mentions');
Route::get('/sitemap.xml', [LandingController::class, 'sitemap'])->name('sitemap');

// ─── Vitrine publique des établissements ──────────────────────────────────────
Route::get('/e/{slug}', [VitrineController::class, 'show'])->name('vitrine.show');
Route::post('/e/{slug}/reserver', [VitrineController::class, 'reserver'])->name('vitrine.reserver');

// ─── Boutique en ligne publique ──────────────────────────────────────────────
Route::prefix('shop')->name('shop.')->group(function () {
    Route::get('/{slug}', [BoutiqueController::class, 'index'])->name('index');
    Route::get('/{slug}/produit/{id}', [BoutiqueController::class, 'produit'])->name('produit');
    Route::get('/{slug}/commander', [BoutiqueController::class, 'commanderForm'])->name('commander.form');
    Route::post('/{slug}/commander', [BoutiqueController::class, 'commander'])
        ->middleware('throttle:10,1')
        ->name('commander');
    Route::get('/{slug}/commande/{numero}', [BoutiqueController::class, 'suivreCommande'])->name('suivi');
});

// ─── Avis client public (sondage post-visite) ────────────────────────────────
Route::get('/avis/{token}', [\App\Http\Controllers\AvisPublicController::class, 'show'])
    ->middleware('throttle:30,1')
    ->name('public.avis.show');
Route::post('/avis/{token}', [\App\Http\Controllers\AvisPublicController::class, 'submit'])
    ->middleware('throttle:10,1')
    ->name('public.avis.submit');

// ─── Carte fidélité publique (QR partageable) ────────────────────────────────
Route::get('/carte/{token}', [\App\Http\Controllers\CarteFideliteController::class, 'show'])
    ->middleware('throttle:60,1')
    ->name('public.carte-fidelite');

// ─── Ticket PDF public (lien partageable, accès par UUID) ─────────────────────
Route::get('/ticket/{id}', [VenteController::class, 'ticketPdfPublic'])
    ->middleware('throttle:30,1')
    ->name('ticket.public');

// ─── Fiche crédit PDF publique (lien partageable, accès par UUID) ────────────
Route::get('/fiche-credit/{id}', [\App\Http\Controllers\Dashboard\CreditController::class, 'fichePdfPublic'])
    ->middleware('throttle:30,1')
    ->name('credit.fiche.public');

// ─── Inscription personnalisée (remplace Breeze) ─────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/inscription', [InscriptionController::class, 'index'])->name('inscription');
    Route::post('/inscription', [InscriptionController::class, 'store'])->name('inscription.store');
});

// ─── Dashboard Institut ───────────────────────────────────────────────────────
Route::middleware(['auth', 'abonnement.actif'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('index');

    // Recherche globale
    Route::get('search', \App\Http\Controllers\Dashboard\SearchController::class)->name('search');

    // Caisse & Ventes (tous les rôles)
    Route::get('caisse', [VenteController::class, 'caisse'])->name('caisse');
    Route::get('caisse/brouillons', [\App\Http\Controllers\Dashboard\CaisseBrouillonController::class, 'index'])->name('caisse.brouillons.index');
    Route::post('caisse/brouillons', [\App\Http\Controllers\Dashboard\CaisseBrouillonController::class, 'store'])->name('caisse.brouillons.store');
    Route::delete('caisse/brouillons/{brouillon}', [\App\Http\Controllers\Dashboard\CaisseBrouillonController::class, 'destroy'])->name('caisse.brouillons.destroy');
    Route::post('ventes', [VenteController::class, 'store'])->name('ventes.store');
    Route::get('ventes', [VenteController::class, 'index'])->name('ventes.index');
    Route::get('ventes/{vente}', [VenteController::class, 'show'])->name('ventes.show');
    Route::post('ventes/{vente}/annuler', [VenteController::class, 'annuler'])->name('ventes.annuler');
    Route::get('ventes/{vente}/ticket-pdf', [VenteController::class, 'ticketPdf'])
        ->middleware('feature:caisse_impression')
        ->name('ventes.ticket-pdf');
    Route::get('ventes/{vente}/facture-pdf', [VenteController::class, 'facturePdf'])
        ->middleware('feature:caisse_impression')
        ->name('ventes.facture-pdf');
    Route::post('ventes/{vente}/sondage/generer', [VenteController::class, 'genererSondage'])
        ->name('ventes.sondage.generer');
    Route::post('ventes/{vente}/sondage/envoyer-email', [VenteController::class, 'envoyerSondageEmail'])
        ->name('ventes.sondage.envoyer-email');

    // Validation code réduction (caisse - feature: caisse_code_promo)
    Route::post('codes-reduction/valider', [CodeReductionController::class, 'valider'])
        ->middleware('feature:caisse_code_promo')
        ->name('codes-reduction.valider');

    // Stock consultation (tous les rôles)
    Route::get('stock', [StockController::class, 'index'])->name('stock.index');

    // Boutique en ligne (admin uniquement pour la config, tous pour la consultation)
    Route::prefix('boutique')->name('boutique.')->group(function () {
        Route::middleware('role:admin')->group(function () {
            Route::get('config', [BoutiqueConfigController::class, 'index'])->name('config.index');
            Route::post('config', [BoutiqueConfigController::class, 'update'])->name('config.update');
        });

        Route::get('commandes', [CommandeController::class, 'index'])->name('commandes.index');
        Route::get('commandes/export', [CommandeController::class, 'export'])->name('commandes.export');
        Route::get('commandes/count-nouvelles', [CommandeController::class, 'countNouvelles'])->name('commandes.count-nouvelles');
        Route::get('commandes/{commande}', [CommandeController::class, 'show'])->name('commandes.show');
        Route::get('commandes/{commande}/facture', [CommandeController::class, 'facturePdf'])->name('commandes.facture');

        Route::middleware('role:admin')->group(function () {
            Route::post('commandes/{commande}/statut', [CommandeController::class, 'updateStatut'])->name('commandes.statut');
            Route::post('commandes/{commande}/payer', [CommandeController::class, 'marquerPayee'])->name('commandes.payer');
            Route::post('commandes/{commande}/notes', [CommandeController::class, 'updateNotes'])->name('commandes.notes');
            Route::delete('commandes/{commande}', [CommandeController::class, 'destroy'])->name('commandes.destroy');
        });
    });

    // Crédits clients & échéanciers (essai 14j + Premium+ uniquement)
    Route::middleware('feature:credits')->group(function () {
        Route::get('credits', [\App\Http\Controllers\Dashboard\CreditController::class, 'index'])->name('credits.index');
        Route::get('credits/{credit}/fiche-pdf', [\App\Http\Controllers\Dashboard\CreditController::class, 'fichePdf'])->name('credits.fiche-pdf');
        Route::get('credits/{credit}', [\App\Http\Controllers\Dashboard\CreditController::class, 'show'])->name('credits.show');
        Route::post('credits/{credit}/payer', [\App\Http\Controllers\Dashboard\CreditController::class, 'payer'])->name('credits.payer');
    });

    // Profil (tous les rôles)
    Route::get('profil', [ProfilController::class, 'edit'])->name('profil.edit');
    Route::put('profil', [ProfilController::class, 'update'])->name('profil.update');

    // ── Admin uniquement ──────────────────────────────────────────────
    Route::middleware('role:admin')->group(function () {
        // Journal d'activité — intégré dans la page profil
        Route::get('audit', fn() => redirect()->route('dashboard.profil.edit'))->name('audit.index');

        // Comparatif multi-instituts (feature: multi_instituts)
        Route::get('comparatif', [\App\Http\Controllers\Dashboard\ComparatifInstitutsController::class, 'index'])
            ->middleware('feature:multi_instituts')->name('comparatif.index');

        // Fournisseurs (admin)
        Route::resource('fournisseurs', \App\Http\Controllers\Dashboard\FournisseurController::class)
            ->except(['create', 'show', 'edit']);

        // Bons de commande — actions sensibles (admin)
        Route::post('bons-commande/{bonsCommande}/envoyer', [\App\Http\Controllers\Dashboard\BonCommandeController::class, 'envoyer'])
            ->name('bons-commande.envoyer');
        Route::post('bons-commande/{bonsCommande}/annuler', [\App\Http\Controllers\Dashboard\BonCommandeController::class, 'annuler'])
            ->name('bons-commande.annuler');

        // Inventaires — validation (admin)
        Route::post('inventaires/{inventaire}/valider', [\App\Http\Controllers\Dashboard\InventaireController::class, 'valider'])
            ->name('inventaires.valider');

        // Codes de réduction (feature: codes_reduction)
        Route::middleware('feature:codes_reduction')->group(function () {
            Route::get('codes-reduction', [CodeReductionController::class, 'index'])->name('codes-reduction.index');
            Route::post('codes-reduction', [CodeReductionController::class, 'store'])->name('codes-reduction.store');
            Route::get('codes-reduction/{codeReduction}/print', [CodeReductionController::class, 'print'])->name('codes-reduction.print');
            Route::patch('codes-reduction/{codeReduction}/toggle', [CodeReductionController::class, 'toggle'])->name('codes-reduction.toggle');
            Route::delete('codes-reduction/{codeReduction}', [CodeReductionController::class, 'destroy'])->name('codes-reduction.destroy');
        });

        // Avoirs — redirigé vers la page Remises & Avoirs (onglet avoirs)
        Route::get('avoirs', fn() => redirect()->route('dashboard.codes-reduction.index'))->name('avoirs.index');
        Route::post('ventes/{vente}/avoirs', [\App\Http\Controllers\Dashboard\AvoirController::class, 'store'])->name('ventes.avoirs.store');
        Route::patch('avoirs/{avoir}/marquer-utilise', [\App\Http\Controllers\Dashboard\AvoirController::class, 'marquerUtilise'])->name('avoirs.marquer-utilise');

        // Avis clients (modération)
        Route::get('avis', [\App\Http\Controllers\Dashboard\AvisClientController::class, 'index'])->name('avis.index');
        Route::post('avis/{avis}/approuver', [\App\Http\Controllers\Dashboard\AvisClientController::class, 'approuver'])->name('avis.approuver');
        Route::post('avis/{avis}/rejeter', [\App\Http\Controllers\Dashboard\AvisClientController::class, 'rejeter'])->name('avis.rejeter');

        // Prestations (Basic + Premium)
        Route::resource('prestations', PrestationController::class)->except(['show']);
        Route::resource('categories-prestations', CategoriePrestationController::class)->except(['show', 'edit']);
        Route::patch('prestations/{prestation}/toggle', [PrestationController::class, 'toggle'])->name('prestations.toggle');

        // Produits + Stock (feature: produits / stock)
        Route::middleware('feature:produits')->group(function () {
            Route::resource('produits', ProduitController::class)->except(['show']);
            Route::post('produits/{produit}/toggle-visible', [ProduitController::class, 'toggleVisible'])->name('produits.toggle-visible');
            Route::post('produits/{produit}/toggle-featured', [ProduitController::class, 'toggleFeatured'])->name('produits.toggle-featured');
            // Images produit
            Route::post('produits/{produit}/images', [\App\Http\Controllers\Dashboard\ProduitImageController::class, 'store'])->name('produits.images.store');
            Route::get('produits/{produit}/images-json', [\App\Http\Controllers\Dashboard\ProduitImageController::class, 'indexJson'])->name('produits.images.json');
            Route::delete('produits/{produit}/images/{image}', [\App\Http\Controllers\Dashboard\ProduitImageController::class, 'destroy'])->name('produits.images.destroy');
            Route::post('produits/{produit}/images/{image}/principale', [\App\Http\Controllers\Dashboard\ProduitImageController::class, 'setPrincipale'])->name('produits.images.principale');
            Route::resource('categories-produits', CategorieProduitController::class)->except(['show', 'create', 'edit']);
        });

        // Scan code-barres (essai + premium-plus uniquement)
        Route::get('produits-scan/recherche', [ProduitController::class, 'rechercheParCodeBarre'])
            ->middleware('feature:scan_code_barre')
            ->name('produits.scan');

        // Finances (feature: finances) — dashboard complet admin
        Route::middleware('feature:finances')->group(function () {
            Route::get('finances', [FinanceController::class, 'index'])->name('finances.index');
            Route::get('finances/rapport', [FinanceController::class, 'rapport'])->name('finances.rapport');
            Route::get('finances/export-ventes', [FinanceController::class, 'exportVentes'])->name('finances.export-ventes');
            Route::get('finances/export-depenses', [FinanceController::class, 'exportDepenses'])->name('finances.export-depenses');
            Route::get('finances/export-pdf', [FinanceController::class, 'exportPdf'])->name('finances.export-pdf');
        });

        // Employés (feature: equipe)
        Route::middleware('feature:equipe')->group(function () {
            Route::resource('employes', EmployeController::class)->except(['show']);
            Route::patch('employes/{employe}/toggle', [EmployeController::class, 'toggle'])->name('employes.toggle');
        });

        // Mes instituts : paramètres OK pour tous, création/switch = multi_instituts
        Route::get('mes-instituts', [MesInstitutsController::class, 'index'])->name('mes-instituts.index');
        Route::put('mes-instituts/{institut}', [MesInstitutsController::class, 'update'])->name('mes-instituts.update');
        Route::post('mes-instituts/{institut}/logo', [MesInstitutsController::class, 'updateLogo'])->name('mes-instituts.logo');
        Route::patch('mes-instituts/{institut}/vitrine', [MesInstitutsController::class, 'toggleVitrine'])->name('mes-instituts.vitrine');
        Route::patch('mes-instituts/{institut}/reservation', [MesInstitutsController::class, 'toggleReservation'])->name('mes-instituts.reservation');
        Route::middleware('feature:multi_instituts')->group(function () {
            Route::post('mes-instituts', [MesInstitutsController::class, 'store'])->name('mes-instituts.store');
            Route::post('mes-instituts/{institut}/switch', [MesInstitutsController::class, 'switch'])->name('mes-instituts.switch');
        });

        // Parrainage (Basic + Premium)
        Route::get('parrainage', [ParrainageController::class, 'index'])->name('parrainage.index');

        // FAQ & documentation
        Route::get('faq', [DashboardController::class, 'faq'])->name('faq');
        Route::get('faq/pdf', [DashboardController::class, 'faqPdf'])->name('faq.pdf');

        // Fidélité (feature: fidelite)
        Route::middleware('feature:fidelite')->group(function () {
            Route::get('fidelite', [FideliteController::class, 'index'])->name('fidelite.index');
            Route::post('fidelite/configurer', [FideliteController::class, 'configurer'])->name('fidelite.configurer');
            Route::post('fidelite/{client}/recompenser', [FideliteController::class, 'recompenser'])->name('fidelite.recompenser');
            Route::post('fidelite/{client}/ajuster', [FideliteController::class, 'ajuster'])->name('fidelite.ajuster');
            Route::get('fidelite/imprimer-code/{codeReduction}', [FideliteController::class, 'imprimerCode'])->name('fidelite.imprimer-code');
        });
    });

    // ── Clients & RDV (accessibles aux employés) ──────────────────────────
    Route::middleware('feature:clients')->group(function () {
        Route::resource('clients', ClientController::class)->except(['show', 'edit']);
        Route::get('clients-fidelite/recherche', [ClientController::class, 'rechercheParTokenFidelite'])->name('clients.fidelite.recherche');
        Route::get('clients/{client}', [ClientController::class, 'show'])->name('clients.show');
        Route::post('clients/{client}/archiver', [ClientController::class, 'archiver'])->name('clients.archiver');
        Route::post('clients/{client}/cadeau-anniversaire', [ClientController::class, 'cadeauAnniversaire'])->name('clients.cadeau-anniversaire');
        Route::post('clients/{client}/fidelite/regenerer', [ClientController::class, 'regenererTokenFidelite'])->name('clients.fidelite.regenerer');
        Route::get('clients/{client}/fidelite/pdf', [ClientController::class, 'carteFidelitePdf'])->name('clients.fidelite.pdf');
        Route::post('clients/{client}/photos', [\App\Http\Controllers\Dashboard\ClientPhotoController::class, 'store'])->name('clients.photos.store');
        Route::delete('clients/{client}/photos/{photo}', [\App\Http\Controllers\Dashboard\ClientPhotoController::class, 'destroy'])->name('clients.photos.destroy');
    });

    Route::middleware('feature:rdv')->group(function () {
        Route::get('rdv', [RdvController::class, 'index'])->name('rdv.index');
        Route::get('rdv/calendrier', [RdvController::class, 'calendrier'])->name('rdv.calendrier');
        Route::get('rdv/calendrier/events', [RdvController::class, 'events'])->name('rdv.events');
        Route::post('rdv/{rdv}/move', [RdvController::class, 'move'])->name('rdv.move');
        Route::get('rdv/create', [RdvController::class, 'create'])->name('rdv.create');
        Route::post('rdv', [RdvController::class, 'store'])->name('rdv.store');
        Route::get('rdv/{rdv}', [RdvController::class, 'show'])->name('rdv.show');
        Route::get('rdv/{rdv}/edit', [RdvController::class, 'edit'])->name('rdv.edit');
        Route::patch('rdv/{rdv}', [RdvController::class, 'update'])->name('rdv.update');
        Route::post('rdv/{rdv}/annuler', [RdvController::class, 'annuler'])->name('rdv.annuler');
        Route::post('rdv/{rdv}/terminer', [RdvController::class, 'terminer'])->name('rdv.terminer');
    });

    // ── Stock (accessibles aux employés) ──────────────────────────────────
    Route::middleware('feature:stock')->group(function () {
        Route::post('stock/{produit}/entree', [StockController::class, 'entree'])->name('stock.entree');
        Route::post('stock/{produit}/correction', [StockController::class, 'correction'])->name('stock.correction');
    });

    // ── Dépenses (accessibles aux employés, feature: finances) ────────────
    Route::middleware('feature:finances')->group(function () {
        Route::get('depenses', [FinanceController::class, 'depenses'])->name('depenses.index');
        Route::resource('depenses', FinanceController::class)->only(['store', 'update', 'destroy']);
    });

    // ── Bons de commande & Inventaires (accessibles aux employés) ──────────
    Route::resource('bons-commande', \App\Http\Controllers\Dashboard\BonCommandeController::class)
        ->except(['edit', 'update']);
    Route::get('bons-commande/{bonsCommande}/pdf', [\App\Http\Controllers\Dashboard\BonCommandeController::class, 'pdf'])
        ->name('bons-commande.pdf');
    Route::post('bons-commande/{bonsCommande}/envoyer-email', [\App\Http\Controllers\Dashboard\BonCommandeController::class, 'envoyerEmail'])
        ->name('bons-commande.envoyer-email');
    Route::post('bons-commande/{bonsCommande}/recevoir', [\App\Http\Controllers\Dashboard\BonCommandeController::class, 'recevoir'])
        ->name('bons-commande.recevoir');

    Route::resource('inventaires', \App\Http\Controllers\Dashboard\InventaireController::class)
        ->except(['edit', 'update']);
});

// ─── Abonnement (accessible même sans abonnement actif) ───────────────────────
Route::middleware('auth')->prefix('abonnement')->name('abonnement.')->group(function () {
    Route::get('/expire', [AbonnementController::class, 'expire'])->name('expire');
    Route::get('/plans', [AbonnementController::class, 'plans'])->name('plans');
    Route::get('/upgrade', [AbonnementController::class, 'upgrade'])->name('upgrade');
    Route::get('/historique', [AbonnementController::class, 'historique'])->name('historique');
    Route::get('/souscrire/{plan}', [AbonnementController::class, 'showSouscrire'])->name('souscrire.show');
    Route::post('/souscrire/{plan}', [AbonnementController::class, 'souscrire'])->name('souscrire');
    Route::post('/ajouter-option-boutique', [AbonnementController::class, 'ajouterOptionBoutique'])->name('ajouter-boutique');
});

// ─── Webhooks ─────────────────────────────────────────────────────────────────
// (Réservé pour usage futur)

// ─── Espace Super-Admin ───────────────────────────────────────────────────────
Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::resource('instituts', AdminInstitutController::class)->only(['index', 'show', 'update']);
    Route::patch('instituts/{institut}/toggle', [AdminInstitutController::class, 'toggle'])->name('instituts.toggle');
    Route::post('instituts/{institut}/offrir-abonnement', [AdminInstitutController::class, 'offrirAbonnement'])->name('instituts.offrir');
    Route::delete('instituts/{institut}', [AdminInstitutController::class, 'destroy'])->name('instituts.destroy');
    Route::resource('abonnements', AdminAbonnementController::class)->only(['index', 'show']);
    Route::patch('abonnements/{abonnement}/valider', [AdminAbonnementController::class, 'valider'])->name('abonnements.valider');
    Route::patch('abonnements/{abonnement}/rejeter', [AdminAbonnementController::class, 'rejeter'])->name('abonnements.rejeter');
    Route::patch('abonnements/{abonnement}/prolonger', [AdminAbonnementController::class, 'prolongerEssai'])->name('abonnements.prolonger');
    Route::resource('plans', AdminPlanController::class);
    Route::post('plans/{plan}/mettre-en-avant', [AdminPlanController::class, 'featurer'])->name('plans.featurer');
    Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
    Route::post('users', [AdminUserController::class, 'store'])->name('users.store');
    Route::patch('users/{user}/toggle', [AdminUserController::class, 'toggleActif'])->name('users.toggle');
    Route::get('config', [AdminConfigController::class, 'edit'])->name('config.edit');
    Route::put('config', [AdminConfigController::class, 'update'])->name('config.update');
    Route::get('messages', [AdminMessageController::class, 'index'])->name('messages.index');
    Route::patch('messages/{message}/lire', [AdminMessageController::class, 'marquerLu'])->name('messages.lire');
    Route::delete('messages/{message}', [AdminMessageController::class, 'destroy'])->name('messages.destroy');
    Route::get('emails', [AdminEmailController::class, 'index'])->name('emails.index');
    Route::get('emails/composer', [AdminEmailController::class, 'composer'])->name('emails.composer');
    Route::post('emails', [AdminEmailController::class, 'send'])->name('emails.send');
    Route::get('logs', [AdminLogsController::class, 'index'])->name('logs.index');
    Route::post('logs/clear', [AdminLogsController::class, 'clear'])->name('logs.clear');
    Route::get('push-debug', [AdminPushDebugController::class, 'index'])->name('push.debug');
    Route::post('push-debug/test', [AdminPushDebugController::class, 'sendTest'])->name('push.debug.test');
    Route::get('finance', [AdminFinanceController::class, 'index'])->name('finance.index');
    Route::get('offres', [AdminOffreController::class, 'index'])->name('offres.index');
    Route::post('offres', [AdminOffreController::class, 'store'])->name('offres.store');
    Route::get('offres/{offre}', fn () => redirect()->route('admin.offres.index'));
    Route::put('offres/{offre}', [AdminOffreController::class, 'update'])->name('offres.update');
    Route::patch('offres/{offre}/toggle', [AdminOffreController::class, 'toggleActif'])->name('offres.toggle');
    Route::delete('offres/{offre}', [AdminOffreController::class, 'destroy'])->name('offres.destroy');

    // ─── Commerciaux ──────────────────────────────────────────────────────────
    Route::patch('commerciaux/config', [AdminCommercialController::class, 'updateConfig'])->name('commerciaux.config');
    Route::patch('commerciaux/commissions/{commission}/payer', [AdminCommercialController::class, 'payerCommission'])->name('commerciaux.commissions.payer');
    Route::patch('commerciaux/commissions/{commission}/annuler', [AdminCommercialController::class, 'annulerPaiement'])->name('commerciaux.commissions.annuler');
    Route::get('commerciaux', [AdminCommercialController::class, 'index'])->name('commerciaux.index');
    Route::post('commerciaux', [AdminCommercialController::class, 'store'])->name('commerciaux.store');
    Route::get('commerciaux/{commercial}', [AdminCommercialController::class, 'show'])->name('commerciaux.show');
    Route::patch('commerciaux/{commercial}', [AdminCommercialController::class, 'update'])->name('commerciaux.update');
    Route::patch('commerciaux/{commercial}/toggle', [AdminCommercialController::class, 'toggle'])->name('commerciaux.toggle');
    Route::delete('commerciaux/{commercial}', [AdminCommercialController::class, 'destroy'])->name('commerciaux.destroy');
});

// ─── Espace Commercial ────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:commercial'])->prefix('commercial')->name('commercial.')->group(function () {
    Route::get('/', [CommercialController::class, 'dashboard'])->name('dashboard');
    Route::get('/parrainages', [CommercialController::class, 'parrainages'])->name('parrainages');
    Route::get('/commissions', [CommercialController::class, 'commissions'])->name('commissions');
    Route::get('/guide', [CommercialController::class, 'guide'])->name('guide');
    Route::get('/guide/pdf', [CommercialController::class, 'guidePdf'])->name('guide.pdf');
    Route::get('/profil', [CommercialController::class, 'profil'])->name('profil');
    Route::post('/profil', [CommercialController::class, 'updateProfil'])->name('profil.update');
    Route::post('/profil/password', [CommercialController::class, 'updatePassword'])->name('profil.password');
});

require __DIR__.'/auth.php';

