<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InventaireLigne extends Model
{
    use HasUuids;

    protected $fillable = [
        'inventaire_id', 'produit_id',
        'stock_theorique', 'stock_compte', 'ecart', 'valeur_ecart',
    ];

    protected $casts = [
        'stock_theorique' => 'integer',
        'stock_compte'    => 'integer',
        'ecart'           => 'integer',
        'valeur_ecart'    => 'integer',
    ];

    public function inventaire() { return $this->belongsTo(Inventaire::class); }
    public function produit()    { return $this->belongsTo(Produit::class); }
}
