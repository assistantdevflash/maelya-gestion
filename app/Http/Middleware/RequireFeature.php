<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vérifie que l'utilisateur a accès à une fonctionnalité donnée selon son plan.
 * Usage : ->middleware('feature:clients') ou feature:clients,fidelite (toutes requises).
 *
 * Si l'accès est refusé :
 *  - GET → redirection vers la page d'upgrade avec ?feature=...
 *  - POST/PUT/DELETE → 403
 */
class RequireFeature
{
    public function handle(Request $request, Closure $next, string ...$features): Response
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        foreach ($features as $feature) {
            if (!$user->aFonctionnalite($feature)) {
                if (!in_array($request->method(), ['GET', 'HEAD'])) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'message' => "Cette fonctionnalité nécessite un plan supérieur.",
                            'feature' => $feature,
                        ], 403);
                    }
                    return back()->with('error', "Cette fonctionnalité nécessite un plan supérieur.");
                }
                return redirect()->route('abonnement.upgrade', ['feature' => $feature]);
            }
        }

        return $next($request);
    }
}
