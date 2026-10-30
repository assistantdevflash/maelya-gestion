<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class InstitutScope implements Scope
{
    /**
     * Routes publiques sur lesquelles le filtre multi-tenant ne doit JAMAIS
     * s'appliquer : boutique, vitrine, avis, liens partageables, paiements…
     *
     * Sans cette exclusion, un visiteur connecté (ou un gérant qui a basculé
     * sur un autre établissement) voyait la boutique PUBLIQUE filtrée par SON
     * établissement courant → liste de produits vide de façon intermittente.
     */
    private const ROUTES_PUBLIQUES = [
        'shop.',
        'vitrine.',
        'public.',
        'fb.event.',
        'payment.',
        'webhooks.',
        // Liens partagés par le gérant : suivi de crédit + fiche PDF signée.
        // Sans ça, un gérant connecté qui ouvre le lien d'un client d'un autre
        // établissement voyait un échéancier vide (relations filtrées).
        'credit.',
    ];

    public function apply(Builder $builder, Model $model): void
    {
        if (!Auth::check() || Auth::user()->role === 'super_admin') {
            return;
        }

        if ($this->estRequetePublique()) {
            return;
        }

        $institutId = session('current_institut_id', Auth::user()->institut_id);
        if ($institutId) {
            $builder->where($model->getTable() . '.institut_id', $institutId);
        }
    }

    private function estRequetePublique(): bool
    {
        $route = request()->route();
        $name  = $route?->getName();

        if (!$name) {
            return false;
        }

        foreach (self::ROUTES_PUBLIQUES as $prefixe) {
            if (str_starts_with($name, $prefixe)) {
                return true;
            }
        }

        return false;
    }
}
