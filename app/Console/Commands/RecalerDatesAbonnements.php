<?php

namespace App\Console\Commands;

use App\Models\Abonnement;
use App\Models\Institut;
use App\Models\User;
use App\Services\AbonnementPeriodeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Recalcule les dates des renouvellements enchaînés.
 *
 * Pendant un temps, payer avant l'échéance faisait repartir la nouvelle
 * période du jour du paiement : les jours restants étaient perdus, et
 * l'ancien abonnement était clôturé sur-le-champ.
 *
 * La commande ne touche que les cas où la nouvelle période commence avant
 * la fin de la précédente (chevauchement = jours perdus) et ignore les mises
 * à niveau, dont le départ immédiat est voulu.
 *
 * Simulation par défaut — rien n'est écrit sans --apply.
 */
class RecalerDatesAbonnements extends Command
{
    protected $signature = 'abonnements:recaler-dates
                            {--jours=30 : Fenêtre analysée, en jours, sur la date de création}
                            {--email= : Ne traiter qu\'un seul compte}
                            {--apply : Écrire les corrections (sinon simple simulation)}';

    protected $description = 'Restaure les jours perdus lors des renouvellements et rétrogradations anticipés.';

    public function handle(AbonnementPeriodeService $periodes): int
    {
        $fenetre = max(1, (int) $this->option('jours'));
        $ecrire  = (bool) $this->option('apply');

        $requete = Abonnement::withoutGlobalScopes()
            ->with(['plan', 'user'])
            ->whereIn('statut', ['actif', 'expire'])
            ->whereNotNull('debut_le')
            ->where('created_at', '>=', now()->subDays($fenetre)->startOfDay())
            ->orderBy('user_id')
            ->orderBy('debut_le')
            ->orderBy('created_at');

        if ($email = $this->option('email')) {
            $user = User::where('email', $email)->first();
            if (! $user) {
                $this->error("Aucun compte pour {$email}.");

                return self::FAILURE;
            }
            $requete->where('user_id', $user->id);
        }

        $abonnements = $requete->get()->groupBy('user_id');

        if ($abonnements->isEmpty()) {
            $this->info("Aucun abonnement sur les {$fenetre} derniers jours.");

            return self::SUCCESS;
        }

        $lignes    = [];
        $reaffiches = [];

        foreach ($abonnements as $serie) {
            $precedent = null;

            foreach ($serie as $abonnement) {
                $plan = $abonnement->plan;

                // Sans plan ou sans échéance, aucune période à recalculer.
                if (! $plan || ! $abonnement->expire_le) {
                    $precedent = $abonnement;

                    continue;
                }

                $jours = $plan->joursPourPeriode($abonnement->periode);

                $estUneMiseANiveau = $periodes->estMiseANiveau($precedent, $plan);

                // Rien à faire si la période ne chevauche pas la précédente
                // (renouvellement après échéance) ou s'il s'agit d'une mise
                // à niveau, dont le départ immédiat est voulu.
                $chevauche = $precedent
                    && $precedent->expire_le
                    && ! $estUneMiseANiveau
                    && $abonnement->debut_le->lessThanOrEqualTo($precedent->expire_le);

                if (! $chevauche) {
                    $precedent = $abonnement;

                    continue;
                }

                $debutAttendu = $precedent->expire_le->copy()->addDay();
                $finAttendue  = $debutAttendu->copy()->addDays($jours);
                $reportes     = (int) round(abs($abonnement->debut_le->diffInDays($precedent->expire_le)));

                $lignes[] = [
                    'abonnement' => $abonnement,
                    'client'     => $abonnement->user?->email ?? $abonnement->user_id,
                    'avant'      => $abonnement->debut_le->format('d/m/Y') . ' → ' . $abonnement->expire_le->format('d/m/Y'),
                    'apres'      => $debutAttendu->format('d/m/Y') . ' → ' . $finAttendue->format('d/m/Y'),
                    'jours'      => $reportes,
                    'precedent'  => $precedent,
                    'debut'      => $debutAttendu,
                    'fin'        => $finAttendue,
                ];

                // Le précédent redevient actif s'il court encore : sans cela,
                // la période corrigée laisserait un trou d'accès.
                if ($precedent->statut === 'expire' && $precedent->expire_le->gte(now()->startOfDay())) {
                    $reaffiches[$precedent->id] = true;
                }

                if ($ecrire) {
                    $this->appliquer($abonnement, $debutAttendu, $finAttendue, $reportes);
                }

                // La chaîne continue avec les dates corrigées.
                $abonnement->debut_le  = $debutAttendu->copy();
                $abonnement->expire_le = $finAttendue->copy();
                $precedent             = $abonnement;
            }
        }

        if ($lignes === []) {
            $this->info('Aucun renouvellement enchaîné à corriger. Les dates sont cohérentes.');

            return self::SUCCESS;
        }

        $this->table(
            ['Client', 'Avant', 'Après', 'Jours récupérés'],
            array_map(fn ($l) => [$l['client'], $l['avant'], $l['apres'], $l['jours']], $lignes)
        );

        $this->newLine();
        $this->line(count($lignes) . ' abonnement(s) concerné(s), '
            . array_sum(array_column($lignes, 'jours')) . ' jour(s) au total.');

        if ($reaffiches !== []) {
            $this->line(count($reaffiches) . ' abonnement(s) précédent(s) réactivé(s) pour éviter un trou d\'accès.');
            if ($ecrire) {
                Abonnement::withoutGlobalScopes()
                    ->whereIn('id', array_keys($reaffiches))
                    ->update(['statut' => 'actif']);
            }
        }

        if (! $ecrire) {
            $this->newLine();
            $this->warn('Simulation. Relancez avec --apply pour écrire les corrections.');
        } else {
            $this->newLine();
            $this->info('Corrections appliquées.');
        }

        return self::SUCCESS;
    }

    private function appliquer(Abonnement $abonnement, $debut, $fin, int $reportes): void
    {
        $ancienneFin = $abonnement->expire_le?->toDateString();

        $abonnement->update([
            'debut_le'  => $debut->toDateString(),
            'expire_le' => $fin->toDateString(),
            'metadata'  => array_merge($abonnement->metadata ?? [], [
                'jours_reportes' => $reportes,
                'recalcule_le'   => now()->toDateString(),
            ]),
        ]);

        // L'option boutique suit l'échéance de l'abonnement qui la porte :
        // une date de fin avancée sans elle laisserait la boutique s'éteindre
        // avant l'abonnement.
        if ($ancienneFin) {
            $instituts = Institut::withoutGlobalScopes()
                ->whereIn('id', $abonnement->user?->mesInstituts()->pluck('id') ?? [])
                ->whereDate('boutique_option_expire_le', $ancienneFin)
                ->get();

            foreach ($instituts as $institut) {
                $institut->boutique_option_expire_le = $fin->toDateString();
                $institut->save();
            }
        }
    }
}
