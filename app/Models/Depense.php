<?php

namespace App\Models;

use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Depense extends Model
{
    use HasUuids, BelongsToInstitut;

    protected $fillable = [
        'institut_id', 'user_id', 'description', 'categorie',
        'montant', 'date', 'justificatif', 'notes',
    ];

    protected $casts = [
        'montant' => 'integer',
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getMontantFormatteAttribute(): string
    {
        return number_format($this->montant, 0, ',', ' ') . ' FCFA';
    }

    public static function categorieLabel(string $categorie): string
    {
        return match($categorie) {
            'loyer' => 'Loyer',
            'salaires' => 'Salaires',
            'fournitures' => 'Fournitures',
            'produits' => 'Produits',
            'equipement' => 'Équipement',
            'marketing' => 'Marketing',
            default => 'Autres',
        };
    }
}
