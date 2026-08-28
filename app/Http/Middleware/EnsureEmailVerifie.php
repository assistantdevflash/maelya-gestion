<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureEmailVerifie
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        // Uniquement pour les propriétaires d'établissements
        if (!$user || !in_array($user->role, ['admin', 'gerant'])) {
            return $next($request);
        }

        // Email déjà vérifié
        if ($user->email_verified_at) {
            return $next($request);
        }

        // Routes de vérification et déconnexion toujours accessibles
        if ($request->routeIs('verification.*', 'logout')) {
            return $next($request);
        }

        // Période de grâce de 3 jours : laisser passer
        if ($user->created_at->diffInDays(now()) < 3) {
            return $next($request);
        }

        // Délai dépassé : forcer la vérification
        return redirect()->route('verification.email')
            ->with('info', 'Vous devez vérifier votre adresse email pour continuer.');
    }
}
