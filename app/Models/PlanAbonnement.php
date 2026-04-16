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
     * Calcule le prix total pour une période donnée avec réduction dégressive.
     */
    public function prixPourPeriode(string $periode): int
    {
        $base = $this->prixEffectif();
        return match ($periode) {
            'annuel'   => (int) round($base * 12 * 0.90),
            'triennal' => (int) round($base * 36 * 0.80),
            default    => $base,
        };
    }

    /**
     * Prix effectif = prix_lancement si l'offre est active, sinon prix normal.
     */
    public function prixEffectif(): int
    {
        if ($this->prix_lancement
            && $this->fin_offre_lancement
            && $this->fin_offre_lancement->isFuture()) {
            return $this->prix_lancement;
        }
        return $this->prix;
    }

    public function offreLancementActive(): bool
    {
        return $this->prix_lancement
            && $this->fin_offre_lancement
            && $this->fin_offre_lancement->isFuture();
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
