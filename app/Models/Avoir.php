<?php

namespace App\Models;

use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Avoir extends Model
{
    use HasUuids, BelongsToInstitut;

    protected $table = 'avoirs';

    protected $fillable = [
        'institut_id', 'vente_id', 'facture_id', 'client_id', 'user_id',
        'code_reduction_id', 'numero', 'montant', 'motif', 'statut',
    ];

    protected $casts = [
        'montant' => 'integer',
    ];

    public function institut()
    {
        return $this->belongsTo(Institut::class, 'institut_id');
    }

    public function vente()
    {
        return $this->belongsTo(Vente::class, 'vente_id');
    }

    public function facture()
    {
        return $this->belongsTo(Facture::class, 'facture_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function codeReduction()
    {
        return $this->belongsTo(CodeReduction::class, 'code_reduction_id');
    }

    /**
     * Statut réel de l'avoir.
     *
     * Un avoir « émis » dont le code a déjà servi est en réalité utilisé.
     * La règle vivait dans la vue ; elle est ici pour être testable et
     * partagée par les exports.
     */
    public function statutEffectif(): string
    {
        if ($this->statut === 'emis'
            && $this->codeReduction
            && $this->codeReduction->nb_utilisations > 0) {
            return 'utilise';
        }

        return $this->statut;
    }

    public function estUtilise(): bool
    {
        return $this->statutEffectif() === 'utilise';
    }
}
