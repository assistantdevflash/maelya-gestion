<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Une interaction avec un prospect : appel, visite, message, note…
 * C'est le journal de bord du commercial.
 */
class CommercialActivite extends Model
{
    use HasUuids;

    protected $table = 'commercial_activites';

    public const TYPES = [
        'appel'    => 'Appel',
        'visite'   => 'Visite',
        'whatsapp' => 'WhatsApp',
        'email'    => 'E-mail',
        'relance'  => 'Relance',
        'note'     => 'Note',
    ];

    protected $fillable = ['prospect_id', 'commercial_id', 'type', 'contenu', 'fait_le'];

    protected $casts = ['fait_le' => 'date'];

    public function prospect()
    {
        return $this->belongsTo(CommercialProspect::class, 'prospect_id');
    }

    public function commercial()
    {
        return $this->belongsTo(CommercialProfile::class, 'commercial_id');
    }

    public function libelleType(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }
}
