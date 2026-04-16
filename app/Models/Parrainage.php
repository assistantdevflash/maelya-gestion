<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Parrainage extends Model
{
    use HasUuids;

    protected $fillable = [
        'parrain_id', 'filleul_id',
        'jours_offerts_parrain', 'jours_offerts_filleul',
        'statut',
    ];

    protected $casts = [
        'jours_offerts_parrain' => 'integer',
        'jours_offerts_filleul' => 'integer',
    ];

    public function parrain()
    {
        return $this->belongsTo(User::class, 'parrain_id');
    }

    public function filleul()
    {
        return $this->belongsTo(User::class, 'filleul_id');
    }
}
