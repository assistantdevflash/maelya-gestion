<?php

namespace App\Models;

use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CodeReduction extends Model
{
    use HasUuids, BelongsToInstitut;

    protected $table = 'codes_reduction';

    protected $fillable = [
        'institut_id', 'client_id', 'code', 'description', 'type', 'valeur',
        'montant_minimum', 'date_debut', 'date_fin',
        'limite_utilisation', 'nb_utilisations', 'actif',
    ];

    protected $casts = [
        'valeur'            => 'integer',
        'montant_minimum'   => 'integer',
        'limite_utilisation'=> 'integer',
        'nb_utilisations'   => 'integer',
        'actif'             => 'boolean',
        'date_debut'        => 'date',
        'date_fin'          => 'date',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function institut()
    {
        return $this->belongsTo(Institut::class, 'institut_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    // ── État du code ─────────────────────────────────────────────────────────

    public function estEpuise(): bool
    {
        return $this->limite_utilisation !== null
            && $this->nb_utilisations >= $this->limite_utilisation;
    }

    public function estExpire(): bool
    {
        // Un code qui expire aujourd'hui reste valable toute la journée.
        return $this->date_fin !== null && $this->date_fin->copy()->endOfDay()->isPast();
    }

    public function estPasEncoreValide(): bool
    {
        return $this->date_debut !== null && $this->date_debut->isFuture();
    }

    /**
     * Retourne 'actif' | 'programme' | 'epuise' | 'expire' | 'inactif'.
     *
     * « Programmé » distingue un code dont la date de début est à venir d'un
     * code réellement désactivé : les deux étaient affichés « Inactif ».
     */
    public function statut(): string
    {
        if (!$this->actif)         return 'inactif';
        if ($this->estEpuise())    return 'epuise';
        if ($this->estExpire())    return 'expire';
        if ($this->estPasEncoreValide()) return 'programme';
        return 'actif';
    }

    // ── Validation & Application ──────────────────────────────────────────────

    /**
     * Vérifie si le code peut être appliqué au total donné.
     * Retourne null si valide, ou un message d'erreur.
     * @param int $total
     * @param string|null $clientId  L'ID du client sélectionné à la caisse
     */
    public function validerPourTotal(int $total, ?string $clientId = null): ?string
    {
        if (!$this->actif)               return 'Ce code de réduction est inactif.';
        if ($this->estPasEncoreValide()) return 'Ce code n\'est pas encore valide.';
        if ($this->estExpire())          return 'Ce code de réduction a expiré.';
        if ($this->estEpuise())          return 'Ce code de réduction est épuisé.';
        if ($this->client_id !== null) {
            if ($clientId === null || $clientId !== $this->client_id) {
                return 'Ce code de réduction est réservé à un client spécifique.';
            }
        }
        if ($this->montant_minimum && $total < $this->montant_minimum) {
            return 'Ce code nécessite un minimum de ' . number_format($this->montant_minimum, 0, ',', ' ') . ' FCFA.';
        }
        return null;
    }

    /**
     * Calcule le montant de la remise pour ce total.
     */
    public function calculerRemise(int $total): int
    {
        if ($this->type === 'pourcentage') {
            return (int) round($total * $this->valeur / 100);
        }
        return min($this->valeur, $total);
    }
}
