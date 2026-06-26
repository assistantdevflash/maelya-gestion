<?php

namespace App\Models;

use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PaiementCredit extends Model
{
    use HasUuids, BelongsToInstitut;

    protected $table = 'paiements_credit';

    public $timestamps = false;

    protected $fillable = [
        'credit_id', 'echeance_id', 'institut_id',
        'montant', 'mode_paiement', 'reference',
        'encaisse_par', 'notes', 'created_at',
    ];

    protected $casts = ['montant' => 'integer'];

    public function credit()    { return $this->belongsTo(Credit::class); }
    public function echeance()  { return $this->belongsTo(Echeance::class); }
    public function encaisseur() { return $this->belongsTo(User::class, 'encaisse_par'); }
}
