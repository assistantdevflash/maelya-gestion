<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Prospect démarché par un commercial.
 *
 * Le cycle de vie suit un pipeline simple :
 *   a_contacter → contacte → interesse → essai → converti
 * avec une sortie possible vers « perdu » à tout moment.
 */
class CommercialProspect extends Model
{
    use HasUuids;

    protected $table = 'commercial_prospects';

    public const STATUTS = [
        'a_contacter' => 'À contacter',
        'contacte'    => 'Contacté',
        'interesse'   => 'Intéressé',
        'essai'       => 'En essai',
        'converti'    => 'Converti',
        'perdu'       => 'Perdu',
    ];

    public const STATUTS_ACTIFS = ['a_contacter', 'contacte', 'interesse', 'essai'];

    public const SOURCES = [
        'porte_a_porte'  => 'Porte-à-porte',
        'recommandation' => 'Recommandation',
        'reseaux'        => 'Réseaux sociaux',
        'appel'          => 'Appel entrant',
        'autre'          => 'Autre',
    ];

    public const PRIORITES = [
        'haute'   => 'Haute',
        'normale' => 'Normale',
        'basse'   => 'Basse',
    ];

    protected $fillable = [
        'commercial_id', 'nom_etablissement', 'type_activite', 'ville', 'quartier',
        'contact_nom', 'contact_telephone', 'contact_email',
        'statut', 'source', 'priorite',
        'prochaine_relance_le', 'derniere_interaction_le',
        'notes', 'proprietaire_id', 'converti_le',
    ];

    protected $casts = [
        'prochaine_relance_le'    => 'date',
        'derniere_interaction_le' => 'date',
        'converti_le'             => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────────────

    public function commercial()
    {
        return $this->belongsTo(CommercialProfile::class, 'commercial_id');
    }

    public function activites()
    {
        return $this->hasMany(CommercialActivite::class, 'prospect_id')->orderByDesc('fait_le');
    }

    public function proprietaire()
    {
        return $this->belongsTo(User::class, 'proprietaire_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────

    public function scopeEnCours(Builder $q): Builder
    {
        return $q->whereIn('statut', self::STATUTS_ACTIFS);
    }

    /** Relance prévue aujourd'hui ou déjà dépassée. */
    public function scopeARelancer(Builder $q): Builder
    {
        return $q->enCours()
            ->whereNotNull('prochaine_relance_le')
            ->whereDate('prochaine_relance_le', '<=', today());
    }

    public function scopeEnRetard(Builder $q): Builder
    {
        return $q->enCours()
            ->whereNotNull('prochaine_relance_le')
            ->whereDate('prochaine_relance_le', '<', today());
    }

    public function scopeCetteSemaine(Builder $q): Builder
    {
        return $q->enCours()
            ->whereNotNull('prochaine_relance_le')
            ->whereDate('prochaine_relance_le', '>', today())
            ->whereDate('prochaine_relance_le', '<=', today()->addDays(7));
    }

    // ── Aides ────────────────────────────────────────────────────────────

    /**
     * Types d'activité proposés : ce sont ceux de la plateforme
     * (table `etablissement_types`), pour qu'un prospect se décrive avec le
     * même vocabulaire que lors de la création de son établissement.
     *
     * @return array<string, string> code => libellé
     */
    public static function typesActivite(): array
    {
        return EtablissementType::actifs()->ordered()->pluck('libelle', 'code')->all();
    }

    public function libelleTypeActivite(): string
    {
        return EtablissementType::libellePour($this->type_activite);
    }

    public function libelleStatut(): string
    {
        return self::STATUTS[$this->statut] ?? ucfirst((string) $this->statut);
    }

    public function estActif(): bool
    {
        return in_array($this->statut, self::STATUTS_ACTIFS, true);
    }

    public function estEnRetard(): bool
    {
        return $this->estActif()
            && $this->prochaine_relance_le !== null
            && $this->prochaine_relance_le->isPast()
            && ! $this->prochaine_relance_le->isToday();
    }

    public function relanceAujourdhui(): bool
    {
        return $this->prochaine_relance_le !== null
            && $this->prochaine_relance_le->isToday();
    }

    /** Jours restants avant la relance (négatif si en retard). */
    public function joursAvantRelance(): ?int
    {
        if ($this->prochaine_relance_le === null) {
            return null;
        }

        return (int) today()->diffInDays($this->prochaine_relance_le->startOfDay(), false);
    }
}
