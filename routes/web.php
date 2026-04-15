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
use App\Http\Controllers\Dashboard\ProfilController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminInstitutController;
use App\Http\Controllers\Admin\AdminAbonnementController;
use App\Http\Controllers\Admin\AdminPlanController;
use App\Http\Controllers\Admin\AdminConfigController;
use App\Http\Controllers\Admin\AdminMessageController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Auth\InscriptionController;
use Illuminate\Support\Facades\Route;

// ─── Landing Page ─────────────────────────────────────────────────────────────
Route::get('/', [LandingController::class, 'index'])->name('home');
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
    Route::get('ventes/{vente}/ticket-pdf', [VenteController::class, 'ticketPdf'])->name('ventes.ticket-pdf');

    // Validation code réduction (caisse - tous rôles)
    Route::post('codes-reduction/valider', [CodeReductionController::class, 'valider'])->name('codes-reduction.valider');

    // Stock consultation (tous les rôles)
    Route::get('stock', [StockController::class, 'index'])->name('stock.index');

    // Profil (tous les rôles)
    Route::get('profil', [ProfilController::class, 'edit'])->name('profil.edit');
    Route::put('profil', [ProfilController::class, 'update'])->name('profil.update');

    // ── Admin uniquement ──────────────────────────────────────────────
    Route::middleware('role:admin')->group(function () {
        // Codes de réduction
        Route::get('codes-reduction', [CodeReductionController::class, 'index'])->name('codes-reduction.index');
        Route::post('codes-reduction', [CodeReductionController::class, 'store'])->name('codes-reduction.store');
        Route::patch('codes-reduction/{codeReduction}/toggle', [CodeReductionController::class, 'toggle'])->name('codes-reduction.toggle');
        Route::delete('codes-reduction/{codeReduction}', [CodeReductionController::class, 'destroy'])->name('codes-reduction.destroy');

        // Clients
        Route::resource('clients', ClientController::class)->except(['show']);
        Route::get('clients/{client}', [ClientController::class, 'show'])->name('clients.show');
        Route::post('clients/{client}/archiver', [ClientController::class, 'archiver'])->name('clients.archiver');

        // Prestations
        Route::resource('prestations', PrestationController::class)->except(['show']);
        Route::resource('categories-prestations', CategoriePrestationController::class)->except(['show', 'edit']);
        Route::patch('prestations/{prestation}/toggle', [PrestationController::class, 'toggle'])->name('prestations.toggle');

        // Produits
        Route::resource('produits', ProduitController::class)->except(['show']);
        Route::resource('categories-produits', CategorieProduitController::class)->except(['show', 'create', 'edit']);
        Route::post('stock/{produit}/entree', [StockController::class, 'entree'])->name('stock.entree');
        Route::post('stock/{produit}/correction', [StockController::class, 'correction'])->name('stock.correction');

        // Finances
        Route::get('finances', [FinanceController::class, 'index'])->name('finances.index');
        Route::resource('depenses', FinanceController::class)->only(['store', 'update', 'destroy']);
        Route::get('finances/rapport', [FinanceController::class, 'rapport'])->name('finances.rapport');
        Route::get('finances/export-ventes', [FinanceController::class, 'exportVentes'])->name('finances.export-ventes');
        Route::get('finances/export-depenses', [FinanceController::class, 'exportDepenses'])->name('finances.export-depenses');
        Route::get('finances/export-pdf', [FinanceController::class, 'exportPdf'])->name('finances.export-pdf');

        // Employés
        Route::resource('employes', EmployeController::class)->except(['show']);
        Route::patch('employes/{employe}/toggle', [EmployeController::class, 'toggle'])->name('employes.toggle');

        // Mes instituts (plan Entreprise)
        Route::get('mes-instituts', [MesInstitutsController::class, 'index'])->name('mes-instituts.index');
        Route::post('mes-instituts', [MesInstitutsController::class, 'store'])->name('mes-instituts.store');
        Route::put('mes-instituts/{institut}', [MesInstitutsController::class, 'update'])->name('mes-instituts.update');
        Route::post('mes-instituts/{institut}/switch', [MesInstitutsController::class, 'switch'])->name('mes-instituts.switch');
    });
});

// ─── Abonnement (accessible même sans abonnement actif) ───────────────────────
Route::middleware('auth')->prefix('abonnement')->name('abonnement.')->group(function () {
    Route::get('/expire', [AbonnementController::class, 'expire'])->name('expire');
    Route::get('/plans', [AbonnementController::class, 'plans'])->name('plans');
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
});

require __DIR__.'/auth.php';

