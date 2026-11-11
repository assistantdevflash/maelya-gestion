<?php

namespace App\Services;

use App\Models\Abonnement;
use App\Models\CommercialProspect;
use App\Models\CommercialProfile;
use Illuminate\Support\Collection;

/**
 * Calcule les indicateurs et les files de travail de l'espace commercial.
 *
 * Objectif : que le commercial sache chaque matin
 *   1. quels prospects relancer aujourd'hui (ou en retard) ;
 *   2. quels filleuls vont voir leur abonnement expirer — donc à recontacter
 *      avant qu'ils ne partent, et pour continuer à toucher la commission ;
 *   3. où en est son pipeline et combien cela peut rapporter.
 */
class ProspectionService
{
    /**
     * Seuils d'alerte avant l'échéance d'un abonnement, en jours.
     * L'ordre décroissant sert à classer les urgences.
     */
    public const SEUILS_ECHEANCE = [30, 15, 7, 3];

    /**
     * Prospects à relancer : en retard puis à faire aujourd'hui.
     */
    public function relancesDues(CommercialProfile $profil): Collection
    {
        return $profil->prospects()
            ->aRelancer()
            ->withCount('activites')
            ->orderBy('prochaine_relance_le')
            ->orderByRaw("CASE priorite WHEN 'haute' THEN 0 WHEN 'normale' THEN 1 ELSE 2 END")
            ->get();
    }

    /**
     * Relances prévues dans les 7 prochains jours (hors celles dues
     * aujourd'hui, déjà listées plus haut).
     */
    public function relancesAVenir(CommercialProfile $profil): Collection
    {
        return $profil->prospects()
            ->cetteSemaine()
            ->orderBy('prochaine_relance_le')
            ->get();
    }

    /**
     * Prospects actifs sans aucune relance planifiée : ce sont eux qui
     * disparaissent des radars.
     */
    public function prospectsSansRelance(CommercialProfile $profil): Collection
    {
        return $profil->prospects()
            ->enCours()
            ->whereNull('prochaine_relance_le')
            ->orderByDesc('priorite')
            ->latest('created_at')
            ->get();
    }

    /**
     * Abonnements des filleuls qui arrivent à échéance (ou sont déjà expirés).
     *
     * On ne garde que l'abonnement le plus lointain par propriétaire, sinon un
     * historique de renouvellements ferait apparaître plusieurs lignes pour un
     * même établissement.
     *
     * @return Collection<int, array{abonnement: Abonnement, jours: int, niveau: string}>
     */
    public function echeancesAbonnements(CommercialProfile $profil, int $seuilJours = 30): Collection
    {
        $proprietaires = $profil->parrainages()->pluck('proprietaire_id');

        if ($proprietaires->isEmpty()) {
            return collect();
        }

        $abonnements = Abonnement::query()
            ->whereIn('user_id', $proprietaires)
            ->whereIn('statut', ['actif', 'expire'])
            ->whereNotNull('expire_le')
            ->with(['user.institut', 'plan'])
            ->orderByDesc('expire_le')
            ->get()
            // Un seul abonnement par établissement : le plus récent.
            ->unique('user_id');

        return $abonnements
            ->map(function (Abonnement $abonnement) {
                $restants = (int) today()->diffInDays($abonnement->expire_le->startOfDay(), false);

                return [
                    'abonnement' => $abonnement,
                    'jours'      => $restants,
                    'niveau'     => $this->niveauUrgence($restants),
                ];
            })
            ->filter(fn (array $ligne) => $ligne['jours'] <= $seuilJours)
            ->sortBy('jours')
            ->values();
    }

    private function niveauUrgence(int $jours): string
    {
        if ($jours < 0) {
            return 'expire';
        }
        if ($jours <= 3) {
            return 'critique';
        }
        if ($jours <= 7) {
            return 'urgent';
        }
        if ($jours <= 15) {
            return 'proche';
        }

        return 'a_surveiller';
    }

    /**
     * Indicateurs du pipeline de prospection.
     *
     * @return array<string, mixed>
     */
    public function statistiques(CommercialProfile $profil): array
    {
        $parStatut = $profil->prospects()
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut')
            ->all();

        $total     = (int) array_sum($parStatut);
        $convertis = (int) ($parStatut['converti'] ?? 0);
        $perdus    = (int) ($parStatut['perdu'] ?? 0);
        $enCours   = 0;
        foreach (CommercialProspect::STATUTS_ACTIFS as $statut) {
            $enCours += (int) ($parStatut[$statut] ?? 0);
        }

        $decides = $convertis + $perdus;

        return [
            'total'            => $total,
            'en_cours'         => $enCours,
            'convertis'        => $convertis,
            'perdus'           => $perdus,
            'par_statut'       => $parStatut,
            'taux_conversion'  => $decides > 0 ? (int) round($convertis * 100 / $decides) : 0,
            'relances_dues'    => $profil->prospects()->aRelancer()->count(),
            'relances_retard'  => $profil->prospects()->enRetard()->count(),
            'sans_relance'     => $profil->prospects()->enCours()->whereNull('prochaine_relance_le')->count(),
            'nouveaux_30j'     => $profil->prospects()->where('created_at', '>=', now()->subDays(30))->count(),
            'convertis_30j'    => $profil->prospects()->where('statut', 'converti')
                ->where('converti_le', '>=', now()->subDays(30))->count(),
        ];
    }
}
