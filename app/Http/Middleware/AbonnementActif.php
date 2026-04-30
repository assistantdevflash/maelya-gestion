<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AbonnementActif
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Super admin : appartient à l'espace /admin, pas au dashboard établissement
        if ($user->isSuperAdmin()) {
            if (str_starts_with($request->route()?->getName() ?? '', 'dashboard.')) {
                return redirect()->route('admin.dashboard');
            }
            view()->share('enSursis', false);
            return $next($request);
        }

        // Commercial : appartient à son espace /commercial
        if ($user->isCommercial()) {
            if (!str_starts_with($request->route()?->getName() ?? '', 'commercial.')) {
                return redirect()->route('commercial.dashboard');
            }
            return $next($request);
        }

        // Vérifier que l'utilisateur est actif
        if (!$user->actif) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Votre compte a été désactivé. Contactez le support.');
        }

        // Vérifier que l'institut est actif
        if ($user->institut_id) {
            $institut = $user->institut;
            if (!$institut || !$institut->actif) {
                auth()->logout();
                return redirect()->route('login')->with('error', 'Votre compte a été désactivé. Contactez le support.');
            }
        }

        // Pour les employés, vérifier l'abonnement du propriétaire (via proprietaire_id de l'institut)
        $abonnementUser = $user; // utilisateur référent pour l'historique d'abonnement
        if ($user->isEmploye()) {
            $institut = \App\Models\Institut::find($user->currentInstitutId());
            $owner = $institut?->proprietaire_id
                ? \App\Models\User::find($institut->proprietaire_id)
                : null;
            $abonnement = $owner?->abonnementActif;
            $abonnementSursis = $abonnement ? null : $owner?->abonnementEnSursis();
            if ($owner) {
                $abonnementUser = $owner;
            }
        } else {
            $abonnement = $user->abonnementActif;
            $abonnementSursis = $abonnement ? null : $user->abonnementEnSursis();
        }

        // Abonnement actif valide
        if ($abonnement) {
            view()->share('enSursis', false);
            if ($abonnement->joursRestants() <= 7) {
                session()->flash('abonnement_expire_bientot', $abonnement->joursRestants());
            }
            return $next($request);
        }

        // ── Période de sursis (expiré depuis ≤ 2 jours) ────────────────────────
        if ($abonnementSursis) {
            view()->share('enSursis', true);
            view()->share('sursisJours', $abonnementSursis->joursDepuisExpiration());

            // Les routes abonnement restent toujours accessibles (pour pouvoir renouveler)
            if ($request->routeIs('abonnement.*')) {
                return $next($request);
            }

            // Bloquer toutes les mutations (POST, PUT, PATCH, DELETE)
            if (!in_array($request->method(), ['GET', 'HEAD'])) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Accès restreint. Renouvelez votre abonnement pour enregistrer des données.',
                    ], 403);
                }
                return back()->with('error', 'Votre abonnement a expiré. Renouvelez-le pour enregistrer des données.');
            }

            return $next($request);
        }

        // ── Aucun abonnement ni sursis ──────────────────────────────────────────
        // Vérifier s'il existe un historique d'abonnement (compte déjà souscrit)
        $aDejaEuAbonnement = $abonnementUser->abonnements()->where('statut', 'actif')->exists();

        if (!$aDejaEuAbonnement) {
            // Nouveau compte sans aucun abonnement → redirection vers les plans
            view()->share('enSursis', false);
            if ($request->routeIs('abonnement.*')) {
                return $next($request);
            }
            return redirect()->route('abonnement.expire');
        }

        // Abonnement expiré (au-delà du sursis) → lecture seule
        $dernierAbonnement = $abonnementUser->abonnements()
            ->where('statut', 'actif')
            ->latest('expire_le')
            ->first();

        view()->share('enSursis', true);
        view()->share('sursisJours', $dernierAbonnement?->joursDepuisExpiration() ?? 0);

        if ($request->routeIs('abonnement.*')) {
            return $next($request);
        }

        // Bloquer toutes les mutations (POST, PUT, PATCH, DELETE)
        if (!in_array($request->method(), ['GET', 'HEAD'])) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Accès restreint. Renouvelez votre abonnement pour enregistrer des données.',
                ], 403);
            }
            return back()->with('error', 'Votre abonnement a expiré. Renouvelez-le pour enregistrer des données.');
        }

        return $next($request);
    }
}

