<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\HistoriquePoints;
use App\Models\ProgrammeFidelite;
use App\Models\Vente;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rattrape les points de fidélité jamais attribués.
 *
 * Pendant des mois, seules les ventes comptant passaient par le calcul des
 * points : une vente à crédit n'en rapportait aucun. Cette commande retrouve
 * les ventes sans ligne « gain » dans l'historique et leur applique le barème
 * **actuel** de l'établissement.
 *
 * Précautions :
 *  • simulation par défaut — rien n'est écrit sans --apply ;
 *  • idempotente : une vente qui a déjà une ligne « gain » est ignorée ;
 *  • les ventes annulées et celles sans client sont écartées ;
 *  • les points ne peuvent pas être recalculés avec un barème historique
 *    (il n'est pas conservé) : le barème d'aujourd'hui s'applique.
 */
class RattraperPointsFidelite extends Command
{
    protected $signature = 'fidelite:rattraper-points
                            {--depuis=2026-01-01 : Date de départ (2026-01-01 ou 01/01/2026)}
                            {--mode=credit : credit = ventes à crédit seulement | tous = toute vente sans points}
                            {--institut= : Ne traiter qu\'un établissement (identifiant)}
                            {--apply : Écrire les points (sinon simple simulation)}';

    protected $description = 'Attribue les points de fidélité manquants aux ventes passées.';

    public function handle(): int
    {
        $depuis = $this->analyserDate((string) $this->option('depuis'));

        if (! $depuis) {
            $this->error('Date de départ illisible. Utilisez 2026-01-01 ou 01/01/2026.');

            return self::FAILURE;
        }

        $mode  = $this->option('mode') === 'tous' ? 'tous' : 'credit';
        $ecrire = (bool) $this->option('apply');

        $this->line('');
        $this->info($ecrire ? 'Rattrapage des points — écriture activée' : 'Rattrapage des points — simulation');
        $this->line('Depuis le : <comment>' . $depuis->format('d/m/Y') . '</comment>');
        $this->line('Périmètre : <comment>' . ($mode === 'credit' ? 'ventes à crédit' : 'toutes les ventes') . '</comment>');
        $this->line('');

        // Barème en vigueur, par établissement (une seule requête).
        $programmes = ProgrammeFidelite::withoutGlobalScopes()
            ->when($this->option('institut'), fn ($q, $id) => $q->where('institut_id', $id))
            ->get()
            ->keyBy('institut_id');

        $ventes = Vente::withoutGlobalScopes()
            ->where('statut', '!=', 'annulee')
            ->whereNotNull('client_id')
            ->where('created_at', '>=', $depuis->copy()->startOfDay())
            ->when($mode === 'credit', fn ($q) => $q->where('mode_paiement', 'credit'))
            ->when($this->option('institut'), fn ($q, $id) => $q->where('institut_id', $id))
            // Une vente déjà créditée ne doit jamais l'être une seconde fois.
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('historique_points')
                  ->whereColumn('historique_points.vente_id', 'ventes.id')
                  ->where('historique_points.type', 'gain');
            });

        $totalVentes = (clone $ventes)->count();

        if ($totalVentes === 0) {
            $this->info('Aucune vente à traiter : rien à rattraper.');
            $this->line('');

            return self::SUCCESS;
        }

        $parInstitut = [];
        $pointsTotal = 0;
        $traitees    = 0;
        $ignorees    = 0;

        $ventes->with('client')->chunkById(200, function ($lot) use (
            $programmes, $ecrire, &$parInstitut, &$pointsTotal, &$traitees, &$ignorees
        ) {
            foreach ($lot as $vente) {
                $programme = $programmes[$vente->institut_id] ?? null;

                if (! $programme) {
                    $ignorees++;
                    continue;
                }

                $points = $programme->calculerPoints((int) $vente->total);

                if ($points <= 0) {
                    $ignorees++;
                    continue;
                }

                if (! isset($parInstitut[$vente->institut_id])) {
                    $parInstitut[$vente->institut_id] = [
                        'institut' => $vente->institut_id,
                        'ventes'   => 0,
                        'points'   => 0,
                        'clients'  => [],
                        'actif'    => (bool) $programme->actif,
                    ];
                }

                $cle = $vente->institut_id;

                $parInstitut[$cle]['ventes']++;
                $parInstitut[$cle]['points'] += $points;
                $parInstitut[$cle]['clients'][$vente->client_id] = true;
                $pointsTotal += $points;
                $traitees++;

                if (! $ecrire) {
                    continue;
                }

                DB::transaction(function () use ($vente, $points) {
                    $client = Client::withoutGlobalScopes()->find($vente->client_id);

                    if (! $client) {
                        return;
                    }

                    $client->increment('points_fidelite', $points);

                    HistoriquePoints::create([
                        'institut_id' => $vente->institut_id,
                        'client_id'   => $client->id,
                        'vente_id'    => $vente->id,
                        'points'      => $points,
                        'type'        => 'gain',
                        'description' => "+{$points} pts (vente #{$vente->numero}) — rattrapage",
                    ]);
                });
            }
        });

        // Récapitulatif par établissement.
        $noms = \App\Models\Institut::withoutGlobalScopes()
            ->whereIn('id', array_keys($parInstitut))
            ->pluck('nom', 'id');

        $this->table(
            ['Établissement', 'Programme', 'Ventes', 'Points', 'Clients'],
            collect($parInstitut)->map(fn ($ligne) => [
                $noms[$ligne['institut']] ?? $ligne['institut'],
                $ligne['actif'] ? 'actif' : 'en pause',
                $ligne['ventes'],
                $ligne['points'],
                count($ligne['clients']),
            ])->all()
        );

        $this->line('');
        $this->line('Ventes analysées   : <comment>' . $totalVentes . '</comment>');
        $this->line('Ventes traitées    : <comment>' . $traitees . '</comment>');
        $this->line('Ventes ignorées    : <comment>' . $ignorees . '</comment> (aucun programme ou montant sous la tranche)');
        $this->line('Points à attribuer : <comment>' . number_format($pointsTotal, 0, ',', ' ') . '</comment>');
        $this->line('');

        if (! $ecrire) {
            $this->warn('Simulation terminée. Ajoutez --apply pour écrire les points.');
        } else {
            $this->info('Points attribués. Relancez la commande : elle ne traitera plus rien.');
        }

        $this->line('');

        return self::SUCCESS;
    }

    /**
     * Accepte 2026-01-01 comme 01/01/2026.
     */
    private function analyserDate(string $valeur): ?Carbon
    {
        $valeur = trim($valeur);

        if ($valeur === '') {
            return null;
        }

        if (preg_match('#^\d{2}/\d{2}/\d{4}$#', $valeur)) {
            $date = Carbon::createFromFormat('d/m/Y', $valeur);

            return $date && $date->format('d/m/Y') === $valeur ? $date->startOfDay() : null;
        }

        try {
            return Carbon::parse($valeur)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
