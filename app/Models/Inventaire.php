<?php

namespace App\Models;

use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Inventaire extends Model
{
    use HasUuids, BelongsToInstitut;

    protected $fillable = [
        'institut_id', 'user_id', 'date_inventaire', 'statut',
        'total_ecart_valeur', 'notes',
    ];

    protected $casts = [
        'date_inventaire'    => 'date',
        'total_ecart_valeur' => 'integer',
    ];

    public function user()   { return $this->belongsTo(User::class); }
    public function lignes() { return $this->hasMany(InventaireLigne::class); }
}
