<?php

namespace App\Services;

use App\Models\CommercialParrainage;
use App\Models\CommercialProfile;
use App\Models\Institut;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Rattachement d'un compte client à un commercial.
 *
 * Le parrainage porte sur le compte (le propriétaire), pas sur
 * l'établissement : les commissions sont calculées sur les abonnements, qui
 * appartiennent au compte. On enregistre l'établissement d'entrée pour que le
 * commercial retrouve celui qu'on lui a attribué.
 *
 * Logique partagée entre la liste des établissements et la fiche commercial.
 */
class CommercialAttributionService
{
    public const STATUT_OK               = 'ok';
    public const STATUT_DEJA             = 'deja';
    public const STATUT_SANS_PROPRIETAIRE = 'sans_proprietaire';
    public const STATUT_COMMERCIAL_INACTIF = 'commercial_inactif';
    public const STATUT_AUCUN_PARRAINAGE = 'aucun_parrainage';
    public const STATUT_RETIRE           = 'retire';

    /**
     * @return array{statut: string, parrainage: ?CommercialParrainage, profil: ?CommercialProfile, ancien: ?CommercialParrainage}
     */
    public function attribuer(Institut $institut, CommercialProfile $profil, ?User $auteur = null): array
    {
        $proprietaire = $institut->proprietaire;

        if (!$proprietaire) {
            return ['statut' => self::STATUT_SANS_PROPRIETAIRE, 'parrainage' => null, 'profil' => $profil, 'ancien' => null];
        }

        if (!$profil->user || !$profil->user->actif) {
            return ['statut' => self::STATUT_COMMERCIAL_INACTIF, 'parrainage' => null, 'profil' => $profil, 'ancien' => null];
        }

        $existant = CommercialParrainage::where('proprietaire_id', $proprietaire->id)->first();

        $config    = DB::table('commercial_config')->first();
        $dureeMois = max(1, (int) ($config?->duree_mois ?? 6));

        // Réattribuer le même commercial ne doit pas repousser la fin de la
        // période de commission : on garde l'échéance en cours.
        $memeCommercial = $existant
            && $existant->commercial_id === $profil->id
            && $existant->isActif();

        $expireLe = $memeCommercial
            ? $existant->expire_le->toDateString()
            : now()->addMonths($dureeMois)->toDateString();

        $parrainage = CommercialParrainage::updateOrCreate(
            ['proprietaire_id' => $proprietaire->id],
            [
                'commercial_id' => $profil->id,
                'institut_id'   => $institut->id,
                'expire_le'     => $expireLe,
                'source'        => 'manuel',
                'attribue_par'  => $auteur?->id,
            ]
        );

        if (!$memeCommercial && $profil->user) {
            try {
                NotificationService::notifyUser(
                    $profil->user,
                    'commercial_attribution',
                    '🏷️ Nouvel établissement attribué',
                    "« {$institut->nom} » vous a été rattaché. Les commissions s'appliquent jusqu'au "
                        . $parrainage->expire_le->format('d/m/Y') . '.',
                    '/commercial/parrainages'
                );
            } catch (\Throwable $e) {
                \Log::warning('[Notification attribution commercial] ' . $e->getMessage());
            }
        }

        return [
            'statut'     => $memeCommercial ? self::STATUT_DEJA : self::STATUT_OK,
            'parrainage' => $parrainage,
            'profil'     => $profil,
            'ancien'     => $existant,
        ];
    }

    /**
     * Retire le commercial d'un compte client.
     *
     * @return array{statut: string, ancien: ?CommercialParrainage}
     */
    public function retirer(Institut $institut): array
    {
        $proprietaire = $institut->proprietaire;

        if (!$proprietaire) {
            return ['statut' => self::STATUT_SANS_PROPRIETAIRE, 'ancien' => null];
        }

        $existant = CommercialParrainage::with('commercial.user')
            ->where('proprietaire_id', $proprietaire->id)
            ->first();

        if (!$existant) {
            return ['statut' => self::STATUT_AUCUN_PARRAINAGE, 'ancien' => null];
        }

        $existant->delete();

        return ['statut' => self::STATUT_RETIRE, 'ancien' => $existant];
    }
}
