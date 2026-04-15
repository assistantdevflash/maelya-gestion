<?php

namespace App\Models;

use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MouvementStock extends Model
{
    use HasUuids, BelongsToInstitut;

    protected $table = 'mouvements_stock';

    protected $fillable = [
        'institut_id', 'produit_id', 'user_id', 'vente_id',
        'type', 'quantite', 'stock_avant', 'stock_apres', 'note',
    ];

    protected $casts = [
        'quantite' => 'integer',
        'stock_avant' => 'integer',
        'stock_apres' => 'integer',
    ];

    public function produit()
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vente()
    {
        return $this->belongsTo(Vente::class, 'vente_id');
    }
}
