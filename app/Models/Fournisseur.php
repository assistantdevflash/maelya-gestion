<?php

namespace App\Models;

use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Fournisseur extends Model
{
    use HasUuids, BelongsToInstitut;

    protected $table = 'fournisseurs';

    protected $fillable = [
        'institut_id', 'nom', 'telephone', 'email', 'adresse',
        'contact_principal', 'notes', 'actif',
    ];

    protected $casts = ['actif' => 'boolean'];

    public function bonsCommande()
    {
        return $this->hasMany(BonCommande::class);
    }
}
