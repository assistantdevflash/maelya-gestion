<?php

namespace App\Services;

use App\Models\Institut;
use App\Models\RendezVous;
use Illuminate\Support\Carbon;

/**
 * Règles de disponibilité des rendez-vous.
 *
 * Centralise deux contrôles qui manquaient totalement :
 *  - le chevauchement de créneaux (deux RDV au même moment pour la même
 *    personne) ;
 *  - les horaires d'ouverture de l'établissement.
 *
 * Utilisé par le contrôleur interne (création, édition, glisser-déposer) ET
 * par la réservation publique de la vitrine.
 */
class RdvService
{
    /** Jours ISO-8601 (1 = lundi … 7 = dimanche). */
    public const JOURS = [
        1 => 'lundi',
        2 => 'mardi',
        3 => 'mercredi',
        4 => 'jeudi',
        5 => 'vendredi',
        6 => 'samedi',
        7 => 'dimanche',
    ];

    /**
     * Cherche un RDV qui chevauche le créneau demandé.
     *
     * Le conflit est évalué « par ressource » : même employé, ou bien deux RDV
     * non assignés entre eux. Un RDV annulé ne bloque rien.
     */
    public function conflit(
        string $institutId,
        ?string $employeId,
        Carbon $debut,
        int $dureeMinutes,
        ?string $excludeRdvId = null
    ): ?RendezVous {
        $fin = $debut->copy()->addMinutes(max(5, $dureeMinutes));

        // Fenêtre large (±1 jour) : couvre un RDV qui déborderait sur minuit.
        $candidats = RendezVous::where('institut_id', $institutId)
            ->where('statut', '!=', 'annule')
            ->whereBetween('debut_le', [
                $debut->copy()->subDay()->startOfDay(),
                $fin->copy()->addDay()->endOfDay(),
            ])
            ->when(
                $employeId,
                fn ($q) => $q->where('employe_id', $employeId),
                fn ($q) => $q->whereNull('employe_id')
            )
            ->when($excludeRdvId, fn ($q) => $q->where('id', '!=', $excludeRdvId))
            ->orderBy('debut_le')
            ->get();

        foreach ($candidats as $rdv) {
            $rdvDebut = $rdv->debut_le;
            if (! $rdvDebut) {
                continue;
            }
            $rdvFin = $rdvDebut->copy()->addMinutes($rdv->duree_minutes ?: 30);

            if ($debut->lt($rdvFin) && $fin->gt($rdvDebut)) {
                return $rdv;
            }
        }

        return null;
    }

    /**
     * Vérifie que le créneau tombe dans les horaires d'ouverture.
     *
     * Retourne un message d'erreur, ou null si tout va bien. Si l'établissement
     * n'a pas renseigné d'horaires, aucune restriction n'est appliquée.
     */
    public function horsHoraires(?Institut $institut, Carbon $debut, int $dureeMinutes): ?string
    {
        if (! $institut || ! $institut->heure_ouverture || ! $institut->heure_fermeture) {
            return null;
        }

        $jours = $this->joursOuverture($institut);
        $iso  = (int) $debut->isoWeekday();

        if (! in_array($iso, $jours, true)) {
            return 'L\'établissement est fermé le ' . self::JOURS[$iso] . '.';
        }

        $ouv = Carbon::parse($debut->toDateString() . ' ' . $institut->heure_ouverture);
        $fer = Carbon::parse($debut->toDateString() . ' ' . $institut->heure_fermeture);

        // Fermeture après minuit (ex. 09:00 → 01:00)
        if ($fer->lessThanOrEqualTo($ouv)) {
            $fer->addDay();
        }

        $fin = $debut->copy()->addMinutes(max(5, $dureeMinutes));

        if ($debut->lt($ouv) || $fin->gt($fer)) {
            return 'Hors horaires d\'ouverture (' . $ouv->format('H:i') . ' – ' . $fer->format('H:i') . ').';
        }

        return null;
    }

    /**
     * Message lisible décrivant un conflit, prêt à être affiché.
     */
    public function messageConflit(RendezVous $rdv): string
    {
        $debut = $rdv->debut_le?->format('H\hi') ?? '—';
        $fin   = $rdv->debut_le?->copy()->addMinutes($rdv->duree_minutes ?: 30)->format('H\hi') ?? '—';
        $qui   = $rdv->client_nom ?: 'un autre client';
        $avec  = $rdv->employe_id ? ' pour ce collaborateur' : '';

        return "Créneau déjà occupé{$avec} : {$qui} de {$debut} à {$fin}.";
    }

    /** Jours d'ouverture normalisés (défaut : lundi → samedi). */
    public function joursOuverture(Institut $institut): array
    {
        $jours = $institut->jours_ouverture;

        if (! is_array($jours) || empty($jours)) {
            return [1, 2, 3, 4, 5, 6];
        }

        return array_values(array_map('intval', $jours));
    }
}
