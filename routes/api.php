<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientApiController;
use App\Http\Controllers\Api\ProduitApiController;
use App\Http\Controllers\Api\VenteApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Maëlya — Auth via Sanctum personal access tokens
|--------------------------------------------------------------------------
| POST   /api/login      → renvoie { token, user }
| POST   /api/logout     → révoque le token courant
| GET    /api/me         → infos de l'utilisateur connecté
|
| Toutes les autres routes nécessitent : Authorization: Bearer <token>
*/

Route::post('/login',  [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/me',      [AuthController::class, 'me'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn(Request $r) => $r->user());

    Route::get('clients',          [ClientApiController::class, 'index']);
    Route::get('clients/{client}', [ClientApiController::class, 'show']);

    Route::get('produits',          [ProduitApiController::class, 'index']);
    Route::get('produits/{produit}', [ProduitApiController::class, 'show']);

    Route::get('ventes',         [VenteApiController::class, 'index']);
    Route::get('ventes/{vente}', [VenteApiController::class, 'show']);
});
