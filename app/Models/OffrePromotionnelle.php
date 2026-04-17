<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class OffrePromotionnelle extends Model
{
    use HasUuids;

    protected $table = 'offres_promotionnelles';

    protected $fillable = [
        'nom', 'description', 'type_reduction', 'valeur_reduction',
        'date_debut', 'date_fin', 'actif',
        'plans_concernes', 'periodes_concernees',
        'badge_texte', 'badge_couleur', 'priorite',
        'notifier_jusqu_au',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'valeur_reduction' => 'integer',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'plans_concernes' => 'array',
        'periodes_concernees' => 'array',
        'priorite' => 'integer',
        'notifier_jusqu_au' => 'date',
    ];

    /**
     * L'offre est-elle active aujourd'hui ?
     */
    public function estActive(): bool
    {
        return $this->actif
            && $this->date_debut->lte(now()->startOfDay())
            && $this->date_fin->gte(now()->startOfDay());
    }

    /**
     * L'offre s'applique-t-elle à ce plan ?
     */
    public function appliquableAuPlan(PlanAbonnement $plan): bool
    {
        if (empty($this->plans_concernes)) {
            return true; // null/empty = tous les plans
        }

        return in_array($plan->id, $this->plans_concernes);
    }

    /**
     * L'offre s'applique-t-elle à cette période ?
     */
    public function appliquableAPeriode(string $periode): bool
    {
        if (empty($this->periodes_concernees)) {
            return true; // null/empty = toutes les périodes
        }

        return in_array($periode, $this->periodes_concernees);
    }

    /**
     * Calcule le prix réduit à partir d'un prix de base.
     */
    public function calculerPrix(int $prixBase): int
    {
        return match ($this->type_reduction) {
            'pourcentage' => (int) round($prixBase * (1 - $this->valeur_reduction / 100)),
            'montant_fixe' => max(0, $prixBase - $this->valeur_reduction),
        };
    }

    /**
     * Texte lisible de la réduction.
     */
    public function getReductionTexteAttribute(): string
    {
        return match ($this->type_reduction) {
            'pourcentage' => "-{$this->valeur_reduction}%",
            'montant_fixe' => "-" . number_format($this->valeur_reduction, 0, ',', ' ') . " FCFA",
        };
    }

    /**
     * Scope : offres actives aujourd'hui.
     */
    public function scopeActives($query)
    {
        return $query->where('actif', true)
            ->whereDate('date_debut', '<=', now()->toDateString())
            ->whereDate('date_fin', '>=', now()->toDateString());
    }

    /**
     * Scope : offres à notifier (actives + notifier_jusqu_au >= aujourd'hui).
     */
    public function scopeANotifier($query)
    {
        return $query->actives()
            ->whereDate('notifier_jusqu_au', '>=', now()->toDateString());
    }

    /**
     * Les couleurs disponibles pour les badges.
     */
    public static function couleursDisponibles(): array
    {
        return [
            'amber' => 'Orange / Ambre',
            'emerald' => 'Vert',
            'rose' => 'Rose',
            'blue' => 'Bleu',
            'purple' => 'Violet',
            'red' => 'Rouge',
            'indigo' => 'Indigo',
            'cyan' => 'Cyan',
        ];
    }

    /**
     * Classes CSS du badge selon la couleur.
     */
    public function getBadgeClassAttribute(): string
    {
        return match ($this->badge_couleur) {
            'emerald' => 'from-emerald-400 to-green-500',
            'rose' => 'from-rose-400 to-pink-500',
            'blue' => 'from-blue-400 to-indigo-500',
            'purple' => 'from-purple-400 to-violet-500',
            'red' => 'from-red-400 to-orange-500',
            'indigo' => 'from-indigo-400 to-blue-500',
            'cyan' => 'from-cyan-400 to-teal-500',
            default => 'from-amber-400 to-orange-500', // amber
        };
    }
}
