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

    /**
     * Libellé lisible d'un code de type.
     *
     * Repli sur une version humanisée pour les codes historiques qui n'existent
     * plus dans la table (ex. « onglerie » → « Onglerie »).
     */
    public static function libellePour(?string $code): string
    {
        if ($code === null || $code === '') {
            return '—';
        }

        $libelle = static::where('code', $code)->value('libelle');

        return $libelle ?? ucfirst(str_replace('_', ' ', $code));
    }
}
