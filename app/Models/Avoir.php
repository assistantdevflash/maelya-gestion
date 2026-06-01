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
        'institut_id', 'vente_id', 'client_id', 'user_id',
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
}
