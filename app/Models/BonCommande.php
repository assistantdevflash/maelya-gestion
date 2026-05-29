<?php

namespace App\Models;

use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BonCommande extends Model
{
    use HasUuids, BelongsToInstitut;

    protected $table = 'bons_commande';

    protected $fillable = [
        'institut_id', 'fournisseur_id', 'user_id', 'numero',
        'date_commande', 'date_livraison_prevue', 'statut', 'total_ht', 'notes',
    ];

    protected $casts = [
        'date_commande'         => 'date',
        'date_livraison_prevue' => 'date',
        'total_ht'              => 'integer',
    ];

    public function fournisseur()  { return $this->belongsTo(Fournisseur::class); }
    public function user()         { return $this->belongsTo(User::class); }
    public function lignes()       { return $this->hasMany(BonCommandeLigne::class); }

    protected static function booted()
    {
        static::creating(function ($bc) {
            if (empty($bc->numero)) {
                $bc->numero = 'BC-' . strtoupper(Str::random(8));
            }
        });
    }

    public function getStatutLabelAttribute(): string
    {
        return match($this->statut) {
            'brouillon'    => 'Brouillon',
            'envoye'       => 'Envoyé',
            'recu_partiel' => 'Reçu partiellement',
            'recu'         => 'Reçu',
            'annule'       => 'Annulé',
            default        => $this->statut,
        };
    }
}
