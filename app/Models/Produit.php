<?php

namespace App\Models;

use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Produit extends Model
{
    use HasUuids, SoftDeletes, BelongsToInstitut;

    protected $fillable = [
        'institut_id', 'categorie_id', 'nom', 'reference',
        'prix_achat', 'prix_vente', 'stock', 'seuil_alerte', 'unite', 'description', 'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'prix_achat' => 'integer',
        'prix_vente' => 'integer',
        'stock' => 'integer',
        'seuil_alerte' => 'integer',
    ];

    public function categorie()
    {
        return $this->belongsTo(CategorieProduit::class, 'categorie_id');
    }

    public function mouvements()
    {
        return $this->hasMany(MouvementStock::class, 'produit_id');
    }

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function scopeEnAlerte($query)
    {
        return $query->whereColumn('stock', '<=', 'seuil_alerte');
    }

    public function isEnAlerte(): bool
    {
        return $this->stock <= $this->seuil_alerte;
    }

    public function getPrixVenteFormatteAttribute(): string
    {
        return number_format($this->prix_vente, 0, ',', ' ') . ' FCFA';
    }
}
