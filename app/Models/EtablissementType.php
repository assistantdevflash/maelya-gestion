<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EtablissementType extends Model
{
    protected $fillable = [
        'code',
        'libelle',
        'actif',
        'position',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'position' => 'integer',
    ];

    /**
     * Scope : types actifs uniquement
     */
    public function scopeActifs($query)
    {
        return $query->where('actif', true);
    }

    /**
     * Scope : tri par position
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position')->orderBy('libelle');
    }
}
