<?php

namespace App\Models;

use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prestation extends Model
{
    use HasUuids, SoftDeletes, BelongsToInstitut;

    protected $fillable = [
        'institut_id', 'categorie_id', 'nom', 'prix', 'duree', 'description', 'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'prix' => 'integer',
        'duree' => 'integer',
    ];

    public function categorie()
    {
        return $this->belongsTo(CategoriePrestation::class, 'categorie_id');
    }

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function getPrixFormateAttribute(): string
    {
        return number_format($this->prix, 0, ',', ' ') . ' FCFA';
    }
}
