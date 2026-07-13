<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ProduitImage extends Model
{
    use HasUuids;

    protected $fillable = ['produit_id', 'chemin', 'ordre', 'is_principale'];

    protected $casts = ['is_principale' => 'boolean'];

    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->chemin);
    }
}
