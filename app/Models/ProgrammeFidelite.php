<?php

namespace App\Models;

use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ProgrammeFidelite extends Model
{
    use HasUuids, BelongsToInstitut;

    protected $table = 'programme_fidelite';

    protected $fillable = [
        'institut_id', 'actif', 'tranche_fcfa', 'points_par_tranche',
        'seuil_recompense', 'type_recompense', 'valeur_recompense',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'tranche_fcfa' => 'integer',
        'points_par_tranche' => 'integer',
        'seuil_recompense' => 'integer',
        'valeur_recompense' => 'integer',
    ];

    public function institut()
    {
        return $this->belongsTo(Institut::class, 'institut_id');
    }

    public function calculerPoints(int $montant): int
    {
        if ($this->tranche_fcfa <= 0) return 0;
        return (int) floor($montant / $this->tranche_fcfa) * $this->points_par_tranche;
    }
}
