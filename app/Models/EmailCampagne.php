<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailCampagne extends Model
{
    protected $fillable = [
        'envoye_par',
        'sujet',
        'corps',
        'mode',
        'send_mode',
        'statut',
        'destinataires_emails',
        'nb_total',
        'nb_envoyes',
        'nb_echecs',
        'erreurs',
        'cibles',
    ];

    protected $casts = [
        'destinataires_emails' => 'array',
        'cibles'               => 'array',
        'nb_total'             => 'integer',
        'nb_envoyes'           => 'integer',
        'nb_echecs'            => 'integer',
    ];

    public function expediteur()
    {
        return $this->belongsTo(User::class, 'envoye_par', 'id');
    }

    public function getModeLibelleAttribute(): string
    {
        return match($this->mode) {
            'tous'         => 'Tous les établissements',
            'selection'    => 'Sélection multiple',
            'un'           => 'Un établissement',
            'personnalise' => 'Email personnalisé',
            default        => (string) $this->mode,
        };
    }

    public function getCanalLibelleAttribute(): string
    {
        return match($this->send_mode) {
            'both'   => 'Email + push',
            'push'   => 'Push seule',
            'email'  => 'Email seul',
            'banner' => 'Bannière',
            default  => 'Email',
        };
    }

    public function getStatutLibelleAttribute(): string
    {
        return match($this->statut) {
            'en_cours' => 'En cours',
            'partial'  => 'Interrompu',
            'echec'    => 'Échec',
            default    => 'Terminé',
        };
    }

    /** Nombre de cibles déjà traitées (envoyées ou échouées). */
    public function getNbTraitesAttribute(): int
    {
        return $this->nb_envoyes + $this->nb_echecs;
    }

    /** Nombre de cibles restant à traiter. */
    public function getNbRestantsAttribute(): int
    {
        return max(0, $this->nb_total - $this->nb_traites);
    }

    public function getProgressionAttribute(): int
    {
        if ($this->nb_total <= 0) {
            return 100;
        }

        return (int) round($this->nb_traites * 100 / $this->nb_total);
    }

    public function estEnCours(): bool
    {
        return $this->statut === 'en_cours';
    }

    /**
     * Prochaine tranche de cibles à traiter, dans l'ordre initial.
     *
     * Les cibles déjà comptées (envoyées ou en échec) sont sautées : l'index
     * d'avancement est la somme des deux compteurs.
     *
     * @return array<int, array<string, mixed>>
     */
    public function prochaineTranche(int $taille): array
    {
        return array_slice($this->cibles ?? [], $this->nb_traites, $taille);
    }
}
