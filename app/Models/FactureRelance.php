<?php

namespace App\Models;

use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Journal des relances envoyées pour une facture impayée.
 * L'unicité (facture_id, palier, canal) évite d'envoyer deux fois la même relance.
 */
class FactureRelance extends Model
{
    use HasUuids, BelongsToInstitut;

    protected $table = 'facture_relances';

    protected $fillable = [
        'facture_id', 'institut_id', 'canal', 'palier', 'envoye_le',
    ];

    protected $casts = [
        'envoye_le' => 'datetime',
    ];

    public function facture()
    {
        return $this->belongsTo(Facture::class);
    }
}
