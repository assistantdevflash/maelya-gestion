<?php

namespace App\Models;

use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class HistoriquePoints extends Model
{
    use HasUuids, BelongsToInstitut;

    protected $table = 'historique_points';

    protected $fillable = [
        'institut_id', 'client_id', 'vente_id', 'points', 'type', 'description',
    ];

    protected $casts = [
        'points' => 'integer',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function vente()
    {
        return $this->belongsTo(Vente::class, 'vente_id');
    }
}
