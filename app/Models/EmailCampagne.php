<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailCampagne extends Model
{
    protected $fillable = [
        'envoye_par',
        'sujet',
        'corps',
        'mode',
        'destinataires_emails',
        'nb_envoyes',
        'nb_echecs',
        'erreurs',
    ];

    protected $casts = [
        'destinataires_emails' => 'array',
    ];

    public function expediteur()
    {
        return $this->belongsTo(User::class, 'envoye_par');
    }

    public function getModeLibelleAttribute(): string
    {
        return match($this->mode) {
            'tous'         => 'Tous les établissements',
            'selection'    => 'Sélection multiple',
            'un'           => 'Un établissement',
            'personnalise' => 'Email personnalisé',
            default        => $this->mode,
        };
    }
}
