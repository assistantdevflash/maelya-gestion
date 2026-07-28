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

        // ── Abonnement : vérifier le cache session en priorité ────────────
        $aboStatus = session('abo_status');
        if ($aboStatus !== null && ($aboStatus['user_id'] ?? null) === $user->id) {
            // Cache corrompu (ancienne version) → ignorer
            if (!isset($aboStatus['en_sursis'])) {
                session()->forget('abo_status');
            }
            // Si le cache dit que l'abonnement est expiré/sursis, vérifier si un
            // nouvel abonnement a été activé entre-temps (ex: renouvellement).
            elseif (!empty($aboStatus['en_sursis'])) {
                $abonnement = $this->isNotOwner($user)
                    ? $this->getOwnerAbonnement($user)
                    : $user->abonnementActif;
                if ($abonnement) {
                    // Un abonnement actif existe → invalider le cache
                    session()->forget('abo_status');
                    // Continuer sans le cache (passera par la vérification DB ci-dessous)
                } else {
                    // Recalculer les jours de sursis depuis la DB (le cache peut être stale)
                    $dernier = ($this->isNotOwner($user) ? $abonnementUser : $user)
                        ->abonnements()->whereIn('statut', ['actif','expire'])->latest('expire_le')->first();
                    $aboStatus['sursis_jours'] = $dernier?->joursDepuisExpiration() ?? 999;
                    // Mettre à jour le cache
                    $this->cacheAboStatus($aboStatus);
                    return $this->applyAboStatus($request, $next, $aboStatus);
                }
            } else {
                return $this->applyAboStatus($request, $next, $aboStatus);
            }
        }

        // Pour les non-propriétaires (employé, gérant, admin secondaire),
        // vérifier l'abonnement du propriétaire de l'institut.
        $abonnementUser = $user;
        if ($this->isNotOwner($user)) {
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

        // Stocker le statut en session pour les prochaines requêtes
        $this->cacheAboStatus($aboStatusData = ['user_id' => $user->id]);

        // Abonnement actif valide
        if ($abonnement) {
            view()->share('enSursis', false);
            $aboStatusData['en_sursis'] = false;
            if ($abonnement->joursRestants() <= 7) {
                $aboStatusData['expire_bientot'] = $abonnement->joursRestants();
                session()->flash('abonnement_expire_bientot', $abonnement->joursRestants());
            }
            $this->cacheAboStatus($aboStatusData);
            return $next($request);
        }

        // ── Période de sursis (expiré depuis ≤ 2 jours) ────────────────────────
        // Pendant le sursis, tout fonctionne normalement. L'alerte est juste visuelle.
        if ($abonnementSursis) {
            view()->share('enSursis', true);
            view()->share('sursisJours', $abonnementSursis->joursDepuisExpiration());
            $aboStatusData['en_sursis'] = true;
            $aboStatusData['sursis_jours'] = $abonnementSursis->joursDepuisExpiration();
            $this->cacheAboStatus($aboStatusData);
            return $next($request);
        }

        // ── Aucun abonnement ni sursis ──────────────────────────────────────────
        // Vérifier s'il existe un historique d'abonnement (compte déjà souscrit)
        // On inclut 'expire' car abonnements:expirer met le statut à jour en base.
        $aDejaEuAbonnement = $abonnementUser->abonnements()->whereIn('statut', ['actif', 'expire'])->exists();

        if (!$aDejaEuAbonnement) {
            // Nouveau compte sans aucun abonnement → redirection vers les plans
            view()->share('enSursis', false);
            if ($request->routeIs('abonnement.*')) {
                return $next($request);
            }
            return redirect()->route('abonnement.expire');
        }

        // Abonnement expiré (au-delà du sursis, ou fin d'essai) → lecture seule
        $dernierAbonnement = $abonnementUser->abonnements()
            ->whereIn('statut', ['actif', 'expire'])
            ->latest('expire_le')
            ->first();

        view()->share('enSursis', true);
        view()->share('sursisJours', $dernierAbonnement?->joursDepuisExpiration() ?? 0);
        $aboStatusData['en_sursis'] = true;
        $aboStatusData['sursis_jours'] = $dernierAbonnement?->joursDepuisExpiration() ?? 0;
        $this->cacheAboStatus($aboStatusData);

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

    /**
     * Applique le statut d'abonnement mis en cache.
     */
    private function applyAboStatus(Request $request, Closure $next, array $aboStatus): Response
    {
        $enSursis = $aboStatus['en_sursis'] ?? false;
        $sursisJours = $aboStatus['sursis_jours'] ?? 0;

        view()->share('enSursis', $enSursis);
        if ($sursisJours > 0) {
            view()->share('sursisJours', $sursisJours);
        }
        if (!empty($aboStatus['expire_bientot'])) {
            session()->flash('abonnement_expire_bientot', $aboStatus['expire_bientot']);
        }
        // Bloquer les mutations uniquement après le sursis (J+3 et plus)
        $apresSursis = $enSursis && $sursisJours > 2;
        if ($apresSursis && !$request->routeIs('abonnement.*')) {
            if (!in_array($request->method(), ['GET', 'HEAD'])) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Accès restreint. Renouvelez votre abonnement.'], 403);
                }
                return back()->with('error', 'Votre abonnement a expiré.');
            }
        }
        return $next($request);
    }

    /**
     * Récupère l'abonnement actif du propriétaire pour un employé.
     */
    private function getOwnerAbonnement($user)
    {
        $institut = \App\Models\Institut::find($user->currentInstitutId());
        $owner = $institut?->proprietaire_id
            ? \App\Models\User::find($institut->proprietaire_id)
            : null;
        return $owner?->abonnementActif;
    }

    /**
     * L'utilisateur n'est PAS le propriétaire de l'institut courant.
     * (employé, gérant, ou admin secondaire)
     */
    private function isNotOwner($user): bool
    {
        $institut = \App\Models\Institut::find($user->currentInstitutId());
        return $institut && $institut->proprietaire_id !== $user->id;
    }

    /**
     * Stocke le statut d'abonnement en session pour éviter les requêtes DB
     * sur les requêtes suivantes.
     */
    private function cacheAboStatus(array $data): void
    {
        session(['abo_status' => $data]);
    }
}

