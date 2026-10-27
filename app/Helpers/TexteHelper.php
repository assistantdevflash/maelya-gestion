<?php

namespace App\Helpers;

/**
 * Accords grammaticaux pour les textes affichés.
 *
 * Évite les « 1 établissement(s) » qui alourdissent l'interface.
 */
class TexteHelper
{
    /**
     * Nom accordé au nombre.
     *
     *   pluriel(1, 'employé')  → « 1 employé »
     *   pluriel(3, 'employé')  → « 3 employés »
     */
    public static function pluriel(int $nombre, string $singulier, ?string $pluriel = null): string
    {
        $pluriel ??= $singulier . 's';

        return $nombre . ' ' . ($nombre > 1 ? $pluriel : $singulier);
    }

    /**
     * Nom ET verbe accordés au nombre.
     *
     *   accord(1, 'établissement', 'établissements', 'passera', 'passeront')
     *       → « 1 établissement passera »
     *   accord(2, …) → « 2 établissements passeront »
     */
    public static function accord(
        int $nombre,
        string $singulier,
        string $pluriel,
        string $verbeSingulier,
        string $verbePluriel
    ): string {
        return self::pluriel($nombre, $singulier, $pluriel)
            . ' ' . ($nombre > 1 ? $verbePluriel : $verbeSingulier);
    }

    /**
     * Verbe seul accordé au nombre (« passera » / « passeront »).
     */
    public static function verbe(int $nombre, string $singulier, string $pluriel): string
    {
        return $nombre > 1 ? $pluriel : $singulier;
    }
}
