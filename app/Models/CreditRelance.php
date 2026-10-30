<?php

namespace App\Models;

use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Journal des relances envoyées pour un crédit.
 * L'unicité (credit_id, palier, canal) évite d'envoyer deux fois la même relance.
 */
class CreditRelance extends Model
{
    use HasUuids, BelongsToInstitut;

    protected $table = 'credit_relances';

    protected $fillable = [
        'credit_id', 'institut_id', 'canal', 'palier', 'envoye_le',
    ];

    protected $casts = [
        'envoye_le' => 'datetime',
    ];

    public function credit()
    {
        return $this->belongsTo(Credit::class);
    }
}
