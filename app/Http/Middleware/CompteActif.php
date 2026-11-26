<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloque un compte désactivé par un super admin.
 *
 * `Auth::attempt` ne filtre pas sur `users.actif`, et l'espace commercial
 * n'utilise pas `AbonnementActif` (pensé pour les propriétaires
 * d'établissement). Sans ce garde-fou, « Désactiver un commercial » ne
 * bloquait que l'inscription avec son code : il pouvait continuer à se
 * connecter, prospecter et encaisser ses commissions.
 */
class CompteActif
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->actif) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('error', 'Votre compte a été désactivé. Contactez le support Maëlya Gestion.');
        }

        return $next($request);
    }
}
