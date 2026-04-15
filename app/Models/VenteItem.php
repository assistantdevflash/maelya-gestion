<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class VenteItem extends Model
{
    use HasUuids;

    protected $table = 'vente_items';

    protected $fillable = [
        'vente_id', 'type', 'item_id', 'nom_snapshot', 'prix_snapshot', 'quantite', 'sous_total',
    ];

    protected $casts = [
        'prix_snapshot' => 'integer',
        'sous_total' => 'integer',
        'quantite' => 'integer',
    ];

    public function vente()
    {
        return $this->belongsTo(Vente::class, 'vente_id');
    }

    public function getSousTotalFormatteAttribute(): string
    {
        return number_format($this->sous_total, 0, ',', ' ') . ' FCFA';
    }
}
