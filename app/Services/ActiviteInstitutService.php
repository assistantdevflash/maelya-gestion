<?php

namespace App\Services;

use App\Models\Institut;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Mesure de l'activité d'un établissement.
 *
 * On enregistre la **dernière opération métier** (vente, RDV, client…),
 * et non la dernière requête HTTP : un utilisateur qui reste connecté sans
 * rien faire ne doit pas apparaître comme actif.
 */
class ActiviteInstitutService
{
    /** Seuil en dessous duquel une nouvelle écriture est ignorée (anti-rafale). */
    public const THROTTLE_SECONDES = 60;

    /** Libellés lisibles des types d'activité. */
    public const TYPES = [
        'connexion'     => 'Connexion',
        'vente'         => 'Vente',
        'rdv'           => 'Rendez-vous',
        'client'        => 'Client',
        'commande'      => 'Commande boutique',
        'facture'       => 'Facture',
        'devis'         => 'Devis',
        'credit'        => 'Crédit client',
        'depense'       => 'Dépense',
        'produit'       => 'Produit',
        'prestation'    => 'Prestation',
        'bon_commande'  => 'Bon de commande',
        'avis'          => 'Avis client',
        // Alias issus du nom de classe (Str::snake)
        'rendez_vous'   => 'Rendez-vous',
        'avis_client'   => 'Avis client',
    ];

    /** Un établissement est « actif » s'il a bougé dans les N derniers jours. */
    public const SEUIL_ACTIF_JOURS = 7;

    /** Au-delà, il est considéré comme dormant. */
    public const SEUIL_DORMANT_JOURS = 30;

    /**
     * Enregistre une activité pour un établissement.
     *
     * @param  Institut|string|null $institut Institut ou son identifiant
     * @param  string               $type     Clé de self::TYPES
     * @param  User|string|null     $user     Auteur ou son identifiant (défaut : utilisateur connecté)
     * @param  bool                 $forcer   Ignorer l'anti-rafale (tests, admin)
     */
    public function marquer(
        Institut|string|null $institut,
        string $type,
        User|string|null $user = null,
        bool $forcer = false
    ): void {
        $institutId = $institut instanceof Institut ? $institut->getKey() : $institut;

        if (!$institutId) {
            return;
        }

        $userId = $user instanceof User ? $user->getKey() : $user;

        // Anti-rafale : une importation de 500 produits ne doit pas produire
        // 500 UPDATE. Une écriture par minute et par établissement suffit.
        $cle = "activite_institut:{$institutId}";
        if (!$forcer) {
            if (Cache::has($cle)) {
                return;
            }
            Cache::put($cle, true, now()->addSeconds(self::THROTTLE_SECONDES));
        }

        Institut::whereKey($institutId)->update([
            'derniere_activite_le'      => now(),
            'derniere_activite_type'    => $type,
            'derniere_activite_user_id' => $userId ?? auth()->id(),
        ]);
    }

    /**
     * Niveau d'activité d'un établissement.
     *
     * @return array{code:string, label:string, jours:int|null}
     */
    public function niveau(?Institut $institut): array
    {
        $date = $institut?->derniere_activite_le;

        if (!$date) {
            return ['code' => 'jamais', 'label' => 'Jamais utilisé', 'jours' => null];
        }

        $jours = (int) $date->diffInDays(now());

        if ($jours <= self::SEUIL_ACTIF_JOURS) {
            return ['code' => 'actif', 'label' => 'Actif', 'jours' => $jours];
        }

        if ($jours <= self::SEUIL_DORMANT_JOURS) {
            return ['code' => 'recent', 'label' => 'Peu actif', 'jours' => $jours];
        }

        return ['code' => 'dormant', 'label' => 'Dormant', 'jours' => $jours];
    }

    /** Libellé du type d'activité enregistré. */
    public function libelleType(?string $type): string
    {
        if (!$type) {
            return '—';
        }

        return self::TYPES[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    /** Force la réévaluation immédiate (après une action groupée). */
    public function oublierThrottle(string $institutId): void
    {
        Cache::forget("activite_institut:{$institutId}");
    }
}
