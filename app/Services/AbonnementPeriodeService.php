<?php

namespace App\Services;

use App\Models\Abonnement;
use App\Models\PlanAbonnement;
use Illuminate\Support\Carbon;

/**
 * Dates de la nouvelle période d'un abonnement qui vient d'être payé.
 *
 * Renouvellement ou rétrogradation : la nouvelle période enchaîne sur la
 * date d'échéance en cours. Payer trois jours avant la fin ne doit pas
 * effacer ces trois jours.
 *
 * Mise à niveau : la période démarre tout de suite. C'est le prix de l'accès
 * immédiat aux fonctionnalités supérieures — on ne fait pas patienter un
 * client qui paie plus cher.
 */
class AbonnementPeriodeService
{
    /**
     * Abonnement du compte qui couvre la date du jour.
     *
     * On exclut les périodes déjà payées mais qui n'ont pas encore commencé :
     * un renouvellement enchaîné ne prend la main qu'à sa date de début.
     */
    public function enCoursPour(string $userId, ?string $exclureId = null): ?Abonnement
    {
        return Abonnement::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->when($exclureId, fn ($q) => $q->where('id', '!=', $exclureId))
            ->where('statut', 'actif')
            ->whereDate('expire_le', '>=', now()->toDateString())
            ->where(fn ($q) => $q->whereNull('debut_le')->orWhereDate('debut_le', '<=', now()->toDateString()))
            ->latest('expire_le')
            ->first();
    }

    public function abonnementEnCours(Abonnement $nouveau): ?Abonnement
    {
        return $this->enCoursPour($nouveau->user_id, $nouveau->id);
    }

    /** Une mise à niveau, c'est un plan plus haut dans la hiérarchie `ordre`. */
    public function estMiseANiveau(?Abonnement $ancien, PlanAbonnement $plan): bool
    {
        if (! $ancien || ! $ancien->plan) {
            return false;
        }

        return (int) $plan->ordre > (int) $ancien->plan->ordre;
    }

    /**
     * Calcule la période sans rien écrire en base.
     *
     * @return array{debut: Carbon, expire: Carbon, jours_reportes: int, mise_a_niveau: bool, ancien: ?Abonnement}
     */
    public function calculer(Abonnement $nouveau, PlanAbonnement $plan, int $jours): array
    {
        $debut      = now()->startOfDay();
        $ancien     = $this->abonnementEnCours($nouveau);
        $miseANiveau = $this->estMiseANiveau($ancien, $plan);
        $reportes   = 0;

        if ($ancien && ! $miseANiveau) {
            $echeance = $ancien->expire_le->copy()->startOfDay();

            if ($echeance->isAfter($debut)) {
                // `abs()` : le signe de diffInDays dépend de la version de Carbon.
                $reportes = (int) round(abs($debut->diffInDays($echeance)));
                $debut    = $echeance->copy()->addDay();
            }
        }

        return [
            'debut'          => $debut,
            'expire'         => $debut->copy()->addDays($jours),
            'jours_reportes' => $reportes,
            'mise_a_niveau'  => $miseANiveau,
            'ancien'         => $ancien,
        ];
    }

    /**
     * Active l'abonnement, clôture le précédent si nécessaire et renvoie la
     * période retenue.
     *
     * @param  array<string, mixed>  $attributs  colonnes supplémentaires
     * @return array{debut: Carbon, expire: Carbon, jours_reportes: int, mise_a_niveau: bool, ancien: ?Abonnement}
     */
    public function activer(Abonnement $abonnement, ?int $jours = null, array $attributs = []): array
    {
        $plan  = $abonnement->plan;
        $jours ??= $plan->joursPourPeriode($abonnement->periode);

        $periode = $this->calculer($abonnement, $plan, $jours);
        $debut   = $periode['debut'];

        $autres = Abonnement::withoutGlobalScopes()
            ->where('user_id', $abonnement->user_id)
            ->where('id', '!=', $abonnement->id)
            ->where('statut', 'actif');

        if ($debut->isSameDay(now())) {
            // Passation immédiate : les anciens abonnements n'ont plus lieu d'être.
            $autres->update(['statut' => 'expire']);
        } else {
            // La période en cours reste active jusqu'à son terme ; la tâche
            // planifiée la clôturera le jour venu. Seuls les abonnements déjà
            // périmés mais restés « actif » sont nettoyés ici.
            (clone $autres)
                ->where('expire_le', '<', now()->toDateString())
                ->update(['statut' => 'expire']);
        }

        $abonnement->update(array_merge($attributs, [
            'statut'    => 'actif',
            'debut_le'  => $debut->toDateString(),
            'expire_le' => $periode['expire']->toDateString(),
            'metadata'  => array_merge($abonnement->metadata ?? [], [
                'jours_reportes' => $periode['jours_reportes'],
                'mise_a_niveau'  => $periode['mise_a_niveau'],
            ]),
        ]));

        return $periode;
    }
}
