<?php

namespace App\Traits;

use App\Services\ActiviteInstitutService;

/**
 * Enregistre l'activité de l'établissement à chaque écriture métier.
 *
 * Le modèle peut surcharger `activiteType()` ; par défaut la clé est dérivée
 * du nom de la classe (Vente → « vente », RendezVous → « rendez_vous »).
 */
trait EnregistreActivite
{
    public static function bootEnregistreActivite(): void
    {
        static::created(function ($model) {
            $model->marquerActivite();
        });

        static::updated(function ($model) {
            $model->marquerActivite();
        });
    }

    protected function marquerActivite(): void
    {
        $institutId = $this->institut_id ?? null;

        if (!$institutId) {
            return;
        }

        try {
            // Auteur : la colonne user_id du modèle si elle existe, sinon la session
            app(ActiviteInstitutService::class)->marquer(
                $institutId,
                $this->activiteType(),
                $this->getAttribute('user_id') ?: null
            );
        } catch (\Throwable $e) {
            // Le suivi d'activité ne doit jamais faire échouer une opération métier.
            \Log::warning('[ActiviteInstitut] ' . $e->getMessage());
        }
    }

    protected function activiteType(): string
    {
        return \Illuminate\Support\Str::snake(class_basename($this));
    }
}
