<?php

namespace App\Console\Commands;

use App\Services\ActiviteInstitutService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Amorce `derniere_activite_le` pour les établissements créés avant la mise en
 * place du suivi d'activité.
 *
 * La date est déduite de la donnée métier la plus récente (vente, RDV, client…).
 * Un établissement sans aucune donnée reste à NULL → « Jamais utilisé », ce qui
 * est précisément le signal recherché.
 */
class InitialiserActiviteInstituts extends Command
{
    protected $signature = 'activite:initialiser
        {--force : Écraser aussi les établissements déjà renseignés}
        {--dry-run : Simuler sans écrire en base}
        {--institut= : Limiter à un identifiant d\'établissement}';

    protected $description = 'Calcule la date de dernière activité des établissements à partir de leurs données existantes.';

    /**
     * Tables métier prises en compte, avec la colonne d'auteur éventuelle.
     *
     * @var array<string, array{table: string, user: string|null}>
     */
    private const SOURCES = [
        'vente'        => ['table' => 'ventes',        'user' => 'user_id'],
        'rdv'          => ['table' => 'rendez_vous',   'user' => null],
        'client'       => ['table' => 'clients',       'user' => null],
        'commande'     => ['table' => 'commandes',     'user' => null],
        'facture'      => ['table' => 'factures',      'user' => 'user_id'],
        'devis'        => ['table' => 'devis',         'user' => 'user_id'],
        'credit'       => ['table' => 'credits',       'user' => null],
        'depense'      => ['table' => 'depenses',      'user' => 'user_id'],
        'produit'      => ['table' => 'produits',      'user' => null],
        'prestation'   => ['table' => 'prestations',   'user' => null],
        'bon_commande' => ['table' => 'bons_commande', 'user' => 'user_id'],
    ];

    public function handle(ActiviteInstitutService $service): int
    {
        $forcer  = (bool) $this->option('force');
        $dryRun  = (bool) $this->option('dry-run');
        $cible   = $this->option('institut');

        // ── 1. Dernière date connue, par établissement et par source ────────
        /** @var array<string, array{date: string, type: string}> $dernieres */
        $dernieres = [];

        foreach (self::SOURCES as $type => $source) {
            if (!Schema::hasTable($source['table'])) {
                continue;
            }

            $colonnes   = Schema::getColumnListing($source['table']);
            $softDelete = in_array('deleted_at', $colonnes, true);

            $requete = DB::table($source['table'])
                ->selectRaw('institut_id, MAX(updated_at) as derniere')
                ->whereNotNull('institut_id')
                ->groupBy('institut_id');

            if ($softDelete) {
                $requete->whereNull('deleted_at');
            }
            if ($cible) {
                $requete->where('institut_id', $cible);
            }

            foreach ($requete->get() as $ligne) {
                $id     = $ligne->institut_id;
                $date   = (string) $ligne->derniere;

                if ($date === '') {
                    continue;
                }

                if (!isset($dernieres[$id]) || $date > $dernieres[$id]['date']) {
                    $dernieres[$id] = ['date' => $date, 'type' => $type];
                }
            }
        }

        // ── 2. Écriture ─────────────────────────────────────────────────────
        $requeteInstituts = DB::table('instituts')->select('id', 'nom', 'derniere_activite_le');

        if ($cible) {
            $requeteInstituts->where('id', $cible);
        }
        if (!$forcer) {
            $requeteInstituts->whereNull('derniere_activite_le');
        }

        $instituts = $requeteInstituts->get();

        $lignes   = [];
        $misAJour = 0;
        $ignores  = 0;

        foreach ($instituts as $institut) {
            if (!isset($dernieres[$institut->id])) {
                $ignores++;
                continue;
            }

            $date = $dernieres[$institut->id]['date'];
            $type = $dernieres[$institut->id]['type'];

            if ($dryRun) {
                $lignes[] = [$institut->nom, $date, $service->libelleType($type), 'simulé'];
                $misAJour++;
                continue;
            }

            DB::table('instituts')->where('id', $institut->id)->update([
                'derniere_activite_le'      => $date,
                'derniere_activite_type'    => $type,
                'derniere_activite_user_id' => $this->auteurDe($type, $institut->id, $date),
            ]);

            // Éviter qu'un marquage immédiat ne soit bloqué par l'anti-rafale
            $service->oublierThrottle($institut->id);

            $lignes[] = [$institut->nom, $date, $service->libelleType($type), 'mis à jour'];
            $misAJour++;
        }

        if (!empty($lignes)) {
            $this->table(['Établissement', 'Dernière activité', 'Type', 'Statut'], $lignes);
        }

        if ($dryRun) {
            $this->warn("Simulation : {$misAJour} établissement(s) seraient mis à jour, {$ignores} sans donnée métier.");
            return self::SUCCESS;
        }

        $this->info("✓ {$misAJour} établissement(s) mis à jour, {$ignores} sans donnée métier.");

        return self::SUCCESS;
    }

    /**
     * Retrouve l'auteur de la ligne métier la plus récente, si la table en a un.
     */
    private function auteurDe(string $type, string $institutId, string $date): ?string
    {
        $source = self::SOURCES[$type] ?? null;

        if (!$source || !$source['user']) {
            return null;
        }

        $valeur = DB::table($source['table'])
            ->where('institut_id', $institutId)
            ->where('updated_at', $date)
            ->value($source['user']);

        return $valeur ? (string) $valeur : null;
    }
}
