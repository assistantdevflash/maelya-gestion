<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommandeItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'commande_id',
        'produit_id',
        'nom_snapshot',
        'prix_snapshot',
        'quantite',
        'sous_total',
    ];

    protected $casts = [
        'prix_snapshot' => 'decimal:2',
        'quantite' => 'integer',
        'sous_total' => 'decimal:2',
    ];

    /**
     * Relations
     */
    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }
}
