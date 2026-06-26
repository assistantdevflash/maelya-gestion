<?php

namespace App\Models;

use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Echeance extends Model
{
    use HasUuids, BelongsToInstitut;

    protected $fillable = [
        'credit_id', 'institut_id', 'numero',
        'date_prevue', 'montant', 'montant_paye',
        'date_paiement', 'encaisse_par', 'statut',
    ];

    protected $casts = [
        'montant'       => 'integer',
        'montant_paye'  => 'integer',
        'date_prevue'   => 'date',
        'date_paiement' => 'date',
    ];

    public function credit()    { return $this->belongsTo(Credit::class); }
    public function encaisseur() { return $this->belongsTo(User::class, 'encaisse_par'); }
}
