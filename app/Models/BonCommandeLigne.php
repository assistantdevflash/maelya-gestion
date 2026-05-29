<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BonCommandeLigne extends Model
{
    use HasUuids;

    protected $table = 'bon_commande_lignes';

    protected $fillable = [
        'bon_commande_id', 'produit_id', 'libelle',
        'quantite_commandee', 'quantite_recue', 'prix_unitaire', 'sous_total',
    ];

    protected $casts = [
        'quantite_commandee' => 'integer',
        'quantite_recue'     => 'integer',
        'prix_unitaire'      => 'integer',
        'sous_total'         => 'integer',
    ];

    public function bonCommande() { return $this->belongsTo(BonCommande::class); }
    public function produit()     { return $this->belongsTo(Produit::class); }
}
