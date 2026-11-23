<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Type d'établissement géré depuis l'espace super admin.
 *
 * Cette liste pilote plusieurs écrans (inscription, filtres) **et** le
 * référencement : chaque type actif obtient sa page publique
 * « Logiciel de gestion pour … ». Ajouter, renommer ou désactiver un type
 * depuis l'administration se répercute donc immédiatement sur le site.
 */
class EtablissementType extends Model
{
    /**
     * Codes fourre-tout : utiles à l'inscription, inutiles au référencement
     * (« logiciel de gestion pour autre » n'est recherché par personne).
     */
    public const CODES_GENERIQUES = ['autre', 'autre_activite', 'other', 'divers'];

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
     * Identifiant lisible utilisé dans les URL de référencement.
     *
     * Le code en base utilise des soulignés (`salon_coiffure`) ; les URL
     * préfèrent les tirets (`salon-coiffure`).
     */
    public function getSlugAttribute(): string
    {
        return str_replace('_', '-', (string) $this->code);
    }

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
     * Scope : types destinés au référencement.
     *
     * On écarte les codes génériques et on exige un code non vide : sans code,
     * il n'y a pas d'URL stable.
     */
    public function scopePourSeo($query)
    {
        return $query->actifs()
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->whereNotIn('code', self::CODES_GENERIQUES)
            ->ordered();
    }

    /**
     * Types publiés sur le site public.
     *
     * Volontairement sans cache : la liste pèse quelques dizaines de lignes,
     * et un cache rendrait une modification faite depuis l'administration
     * invisible pendant des heures.
     */
    public static function pourSeo(): Collection
    {
        return static::query()->pourSeo()->get();
    }

    /**
     * Retrouve un type publié à partir du segment d'URL.
     */
    public static function trouverParSlug(?string $slug): ?self
    {
        if ($slug === null || $slug === '') {
            return null;
        }

        return static::pourSeo()->first(fn (self $type) => $type->slug === $slug);
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
