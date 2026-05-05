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
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\Commercial\CommercialController;
use App\Http\Controllers\Auth\InscriptionController;
use Illuminate\Support\Facades\Route;

// ─── Landing Page ─────────────────────────────────────────────────────────────
Route::get('/', [LandingController::class, 'index'])->name('home');

// ─── Push Notifications ───────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::post('/push/subscribe',   [PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::post('/push/unsubscribe', [PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');
});
Route::get('/a-propos', [LandingController::class, 'apropos'])->name('about');
Route::get('/faq', [LandingController::class, 'faq'])->name('faq');
Route::get('/contact', [LandingController::class, 'contact'])->name('contact');
Route::post('/contact', [LandingController::class, 'sendContact'])->name('contact.send')->middleware('throttle:contact');
Route::get('/mentions-legales', [LandingController::class, 'mentionsLegales'])->name('mentions');
Route::get('/sitemap.xml', [LandingController::class, 'sitemap'])->name('sitemap');

// ─── Inscription personnalisée (remplace Breeze) ─────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/inscription', [InscriptionController::class, 'index'])->name('inscription');
    Route::post('/inscription', [InscriptionController::class, 'store'])->name('inscription.store');
});

// ─── Dashboard Institut ───────────────────────────────────────────────────────
Route::middleware(['auth', 'abonnement.actif'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('index');

    // Caisse & Ventes (tous les rôles)
    Route::get('caisse', [VenteController::class, 'caisse'])->name('caisse');
    Route::post('ventes', [VenteController::class, 'store'])->name('ventes.store');
    Route::get('ventes', [VenteController::class, 'index'])->name('ventes.index');
    Route::get('ventes/{vente}', [VenteController::class, 'show'])->name('ventes.show');
    Route::post('ventes/{vente}/annuler', [VenteController::class, 'annuler'])->name('ventes.annuler');
    Route::get('ventes/{vente}/ticket-pdf', [VenteController::class, 'ticketPdf'])
        ->middleware('feature:caisse_impression')
        ->name('ventes.ticket-pdf');

    // Validation code réduction (caisse - feature: caisse_code_promo)
    Route::post('codes-reduction/valider', [CodeReductionController::class, 'valider'])
        ->middleware('feature:caisse_code_promo')
        ->name('codes-reduction.valider');

    // Stock consultation (tous les rôles)
    Route::get('stock', [StockController::class, 'index'])->name('stock.index');

    // Profil (tous les rôles)
    Route::get('profil', [ProfilController::class, 'edit'])->name('profil.edit');
    Route::put('profil', [ProfilController::class, 'update'])->name('profil.update');

    // ── Admin uniquement ──────────────────────────────────────────────
    Route::middleware('role:admin')->group(function () {
        // Codes de réduction (feature: codes_reduction)
        Route::middleware('feature:codes_reduction')->group(function () {
            Route::get('codes-reduction', [CodeReductionController::class, 'index'])->name('codes-reduction.index');
            Route::post('codes-reduction', [CodeReductionController::class, 'store'])->name('codes-reduction.store');
            Route::get('codes-reduction/{codeReduction}/print', [CodeReductionController::class, 'print'])->name('codes-reduction.print');
            Route::patch('codes-reduction/{codeReduction}/toggle', [CodeReductionController::class, 'toggle'])->name('codes-reduction.toggle');
            Route::delete('codes-reduction/{codeReduction}', [CodeReductionController::class, 'destroy'])->name('codes-reduction.destroy');
        });

        // Clients (feature: clients)
        Route::middleware('feature:clients')->group(function () {
            Route::resource('clients', ClientController::class)->except(['show', 'edit']);
            Route::get('clients/{client}', [ClientController::class, 'show'])->name('clients.show');
            Route::post('clients/{client}/archiver', [ClientController::class, 'archiver'])->name('clients.archiver');
            Route::post('clients/{client}/cadeau-anniversaire', [ClientController::class, 'cadeauAnniversaire'])->name('clients.cadeau-anniversaire');
        });

        // Prestations (Basic + Premium)
        Route::resource('prestations', PrestationController::class)->except(['show']);
        Route::resource('categories-prestations', CategoriePrestationController::class)->except(['show', 'edit']);
        Route::patch('prestations/{prestation}/toggle', [PrestationController::class, 'toggle'])->name('prestations.toggle');

        // Produits + Stock (feature: produits / stock)
        Route::middleware('feature:produits')->group(function () {
            Route::resource('produits', ProduitController::class)->except(['show']);
            Route::resource('categories-produits', CategorieProduitController::class)->except(['show', 'create', 'edit']);
        });
        Route::middleware('feature:stock')->group(function () {
            Route::post('stock/{produit}/entree', [StockController::class, 'entree'])->name('stock.entree');
            Route::post('stock/{produit}/correction', [StockController::class, 'correction'])->name('stock.correction');
        });

        // Finances (feature: finances)
        Route::middleware('feature:finances')->group(function () {
            Route::get('finances', [FinanceController::class, 'index'])->name('finances.index');
            Route::resource('depenses', FinanceController::class)->only(['store', 'update', 'destroy']);
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
        Route::middleware('feature:multi_instituts')->group(function () {
            Route::post('mes-instituts', [MesInstitutsController::class, 'store'])->name('mes-instituts.store');
            Route::post('mes-instituts/{institut}/switch', [MesInstitutsController::class, 'switch'])->name('mes-instituts.switch');
        });

        // Parrainage (Basic + Premium)
        Route::get('parrainage', [ParrainageController::class, 'index'])->name('parrainage.index');

        // Fidélité (feature: fidelite)
        Route::middleware('feature:fidelite')->group(function () {
            Route::get('fidelite', [FideliteController::class, 'index'])->name('fidelite.index');
            Route::post('fidelite/configurer', [FideliteController::class, 'configurer'])->name('fidelite.configurer');
            Route::post('fidelite/{client}/recompenser', [FideliteController::class, 'recompenser'])->name('fidelite.recompenser');
            Route::post('fidelite/{client}/ajuster', [FideliteController::class, 'ajuster'])->name('fidelite.ajuster');
            Route::get('fidelite/imprimer-code/{codeReduction}', [FideliteController::class, 'imprimerCode'])->name('fidelite.imprimer-code');
        });
    });
});

// ─── Abonnement (accessible même sans abonnement actif) ───────────────────────
Route::middleware('auth')->prefix('abonnement')->name('abonnement.')->group(function () {
    Route::get('/expire', [AbonnementController::class, 'expire'])->name('expire');
    Route::get('/plans', [AbonnementController::class, 'plans'])->name('plans');
    Route::get('/upgrade', [AbonnementController::class, 'upgrade'])->name('upgrade');
    Route::get('/historique', [AbonnementController::class, 'historique'])->name('historique');
    Route::post('/souscrire/{plan}', [AbonnementController::class, 'souscrire'])->name('souscrire');
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
});

require __DIR__.'/auth.php';

