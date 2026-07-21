<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DevisItem extends Model
{
    use HasUuids;

    protected $table = 'devis_items';

    protected $fillable = [
        'devis_id', 'produit_id', 'prestation_id',
        'designation', 'quantite', 'prix_unitaire',
        'remise_type', 'remise_valeur', 'tva_taux', 'total_ligne', 'ordre',
    ];

    protected $casts = [
        'quantite' => 'integer',
        'prix_unitaire' => 'integer',
        'remise_valeur' => 'integer',
        'tva_taux' => 'decimal:2',
        'total_ligne' => 'integer',
        'ordre' => 'integer',
    ];

    public function devis(): BelongsTo { return $this->belongsTo(Devis::class); }
    public function produit(): BelongsTo { return $this->belongsTo(Produit::class); }
    public function prestation(): BelongsTo { return $this->belongsTo(Prestation::class); }
}
