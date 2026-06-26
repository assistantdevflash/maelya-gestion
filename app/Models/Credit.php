<?php

namespace App\Models;

use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    use HasUuids, BelongsToInstitut;

    protected $fillable = [
        'vente_id', 'client_id', 'institut_id',
        'montant_total', 'apport_initial', 'reste_a_payer',
        'nb_echeances', 'frequence', 'statut',
        'date_debut', 'date_fin_prevue', 'notes',
    ];

    protected $casts = [
        'montant_total'   => 'integer',
        'apport_initial'  => 'integer',
        'reste_a_payer'   => 'integer',
        'date_debut'      => 'date',
        'date_fin_prevue' => 'date',
    ];

    public function vente()     { return $this->belongsTo(Vente::class); }
    public function client()    { return $this->belongsTo(Client::class); }
    public function echeances() { return $this->hasMany(Echeance::class)->orderBy('numero'); }
    public function paiements() { return $this->hasMany(PaiementCredit::class)->latest(); }
}
