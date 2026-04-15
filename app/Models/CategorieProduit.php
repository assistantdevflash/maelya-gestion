<?php

namespace App\Models;

use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CategorieProduit extends Model
{
    use HasUuids, BelongsToInstitut;

    protected $table = 'categories_produits';

    protected $fillable = ['institut_id', 'nom'];

    public function produits()
    {
        return $this->hasMany(Produit::class, 'categorie_id');
    }
}
