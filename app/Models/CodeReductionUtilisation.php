<?php

namespace App\Models;

use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Une ligne d'historique : le code a servi sur une vente.
 *
 * Conserve le code en clair au moment de l'usage, pour que l'historique reste
 * lisible même si le code est renommé ou supprimé plus tard.
 */
class CodeReductionUtilisation extends Model
{
    use HasUuids, BelongsToInstitut;

    protected $table = 'code_reduction_utilisations';

    protected $fillable = [
        'code_reduction_id', 'institut_id', 'vente_id', 'client_id',
        'code', 'montant_remise',
    ];

    protected $casts = [
        'montant_remise' => 'integer',
    ];

    public function codeReduction()
    {
        return $this->belongsTo(CodeReduction::class, 'code_reduction_id');
    }

    public function vente()
    {
        return $this->belongsTo(Vente::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
