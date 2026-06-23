<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Route model binding pour le paramètre {offre}
        Route::model('offre', \App\Models\OffrePromotionnelle::class);

        // Badge anniversaire dans la sidebar pour toutes les vues dashboard (cache 1h)
        View::composer('layouts.dashboard', function ($view) {
            if (auth()->check()) {
                $institutId = session('current_institut_id', auth()->user()->institut_id);
                $nb = \Illuminate\Support\Facades\Cache::remember(
                    'anniv_count_' . $institutId,
                    now()->addHour(),
                    fn () => \App\Models\Client::where('actif', true)
                        ->where('date_naissance', now()->format('m-d'))
                        ->count()
                );
                $view->with('sidebarNbAnniversaires', $nb);
            }
        });

        // Anti-spam : max 3 envois par IP par heure sur le formulaire de contact
        RateLimiter::for('contact', function (Request $request) {
            return Limit::perHour(3)->by($request->ip())
                ->response(function () {
                    return redirect()->route('contact')
                        ->withErrors(['email' => 'Trop de tentatives. Réessayez dans 1 heure.']);
                });
        });
    }
}
