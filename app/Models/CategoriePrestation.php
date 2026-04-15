<?php

namespace App\Models;

use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CategoriePrestation extends Model
{
    use HasUuids, BelongsToInstitut;

    protected $table = 'categories_prestations';

    protected $fillable = ['institut_id', 'nom', 'ordre'];

    public function prestations()
    {
        return $this->hasMany(Prestation::class, 'categorie_id');
    }
}
