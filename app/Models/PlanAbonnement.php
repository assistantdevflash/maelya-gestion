<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PlanAbonnement extends Model
{
    use HasUuids;

    protected $table = 'plans_abonnement';

    protected $fillable = [
        'nom', 'slug', 'duree_type', 'duree_jours', 'prix',
        'prix_lancement', 'fin_offre_lancement',
        'max_employes', 'max_instituts',
        'economie_pct', 'description', 'actif', 'mis_en_avant', 'ordre',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'mis_en_avant' => 'boolean',
        'prix' => 'integer',
        'prix_lancement' => 'integer',
        'fin_offre_lancement' => 'date',
        'duree_jours' => 'integer',
        'economie_pct' => 'integer',
        'max_employes' => 'integer',
        'max_instituts' => 'integer',
        'ordre' => 'integer',
    ];

    /**
     * Retourne la meilleure offre promotionnelle active pour ce plan et cette période.
     */
    public function meilleureOffre(?string $periode = null): ?OffrePromotionnelle
    {
        $offres = OffrePromotionnelle::actives()
            ->orderByDesc('priorite')
            ->get();

        $meilleureOffre = null;
        $meilleurPrix = $this->prix;

        foreach ($offres as $offre) {
            if (!$offre->appliquableAuPlan($this)) {
                continue;
            }
            if ($periode && !$offre->appliquableAPeriode($periode)) {
                continue;
            }

            $prixReduit = $offre->calculerPrix($this->prix);
            if ($prixReduit < $meilleurPrix) {
                $meilleurPrix = $prixReduit;
                $meilleureOffre = $offre;
            }
        }

        return $meilleureOffre;
    }

    /**
     * Vérifie si une offre promotionnelle est active pour ce plan.
     */
    public function aUneOffreActive(?string $periode = null): bool
    {
        return $this->meilleureOffre($periode) !== null;
    }

    /**
     * Calcule le prix total pour une période donnée avec réduction dégressive.
     */
    public function prixPourPeriode(string $periode): int
    {
        $base = $this->prixEffectif($periode);
        return match ($periode) {
            'annuel'   => (int) round($base * 12 * 0.90),
            'triennal' => (int) round($base * 36 * 0.80),
            default    => $base,
        };
    }

    /**
     * Prix effectif = prix avec meilleure offre active, sinon prix normal.
     */
    public function prixEffectif(?string $periode = null): int
    {
        // Nouveau système : offres promotionnelles
        $offre = $this->meilleureOffre($periode);
        if ($offre) {
            return $offre->calculerPrix($this->prix);
        }

        return $this->prix;
    }

    public function offreLancementActive(): bool
    {
        return $this->prix_lancement
            && $this->fin_offre_lancement
            && $this->fin_offre_lancement->gte(now()->startOfDay());
    }

    /**
     * Retourne le pourcentage d'économie pour une période.
     */
    public function economiePeriode(string $periode): int
    {
        return match ($periode) {
            'annuel'   => 10,
            'triennal' => 20,
            default    => 0,
        };
    }

    /**
     * Nombre de jours pour une période donnée.
     */
    public function joursPourPeriode(string $periode): int
    {
        return match ($periode) {
            'annuel'   => 365,
            'triennal' => 1095,
            default    => 30, // mensuel
        };
    }

    public function abonnements()
    {
        return $this->hasMany(Abonnement::class, 'plan_id');
    }

    public function getPrixFormatteAttribute(): string
    {
        return number_format($this->prix, 0, ',', ' ') . ' FCFA';
    }
}
