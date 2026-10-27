<?php

namespace App\Console\Commands;

use App\Models\Vente;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Répare l'historique des ventes à crédit dont `mode_paiement` n'a jamais été
 * enregistré comme « credit ».
 *
 * Avant l'ajout de la valeur à l'énumération, MySQL en mode permissif vidait
 * la colonne : ces lignes sont identifiables par `credit_statut` non nul.
 */
class ReparerModeCreditVentes extends Command
{
    protected $signature = 'ventes:reparer-mode-credit
        {--dry-run : Afficher ce qui serait corrigé sans écrire}
        {--limite=0 : Nombre maximum de lignes à corriger (0 = toutes)}
        {--force : Ne pas demander de confirmation}';

    protected $description = 'Remet mode_paiement = "credit" sur les ventes à crédit dont la colonne est vide.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limite = max(0, (int) $this->option('limite'));

        // ── 1. Lignes à corriger ────────────────────────────────────────────
        $aCorriger = DB::table('ventes')
            ->whereNotNull('credit_statut')
            ->where('mode_paiement', '!=', 'credit');

        $total = (clone $aCorriger)->count();

        if ($total === 0) {
            $this->info('Aucune vente à corriger : toutes les ventes à crédit ont déjà le bon mode de paiement.');
            $this->rapporterAnomalies();

            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[Simulation] ' : '') . "{$total} vente(s) à crédit avec un mode de paiement manquant.");

        // Aperçu
        $apercu = (clone $aCorriger)
            ->select('numero', 'created_at', 'total', 'montant_paye', 'credit_statut', 'mode_paiement')
            ->orderBy('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($v) => [
                $v->numero ?: '—',
                $v->created_at ? substr($v->created_at, 0, 10) : '—',
                number_format((int) $v->total, 0, ',', ' ') . ' F',
                number_format((int) $v->montant_paye, 0, ',', ' ') . ' F',
                $v->credit_statut,
                $v->mode_paiement === '' ? '(vide)' : ($v->mode_paiement ?? '(null)'),
            ])
            ->all();

        $this->table(['Numéro', 'Date', 'Total', 'Encaissé', 'Statut crédit', 'Mode actuel'], $apercu);

        $cible = $limite > 0 ? min($total, $limite) : $total;
        if ($limite > 0 && $limite < $total) {
            $this->warn("Limite appliquée : {$cible} ligne(s) traitée(s) sur {$total}.");
        }

        // ── 2. Confirmation ────────────────────────────────────────────────
        if (!$dryRun && !$this->option('force')) {
            if (!$this->confirm("Confirmer la correction de {$cible} vente(s) ?", true)) {
                $this->warn('Opération annulée.');

                return self::SUCCESS;
            }
        }

        if ($dryRun) {
            $this->warn('Aucune modification enregistrée (--dry-run). Relancez sans l\'option pour appliquer.');
            $this->rapporterAnomalies();

            return self::SUCCESS;
        }

        // ── 3. Correction par lots ─────────────────────────────────────────
        $corrigees = 0;
        $barre = $this->output->createProgressBar($cible);
        $barre->start();

        $requete = DB::table('ventes')
            ->whereNotNull('credit_statut')
            ->where('mode_paiement', '!=', 'credit')
            ->orderBy('id')
            ->select('id')
            ->limit($cible);

        foreach ($requete->cursor() as $ligne) {
            $corrigees += DB::table('ventes')
                ->where('id', $ligne->id)
                ->update(['mode_paiement' => 'credit']);

            $barre->advance();
        }

        $barre->finish();
        $this->newLine(2);

        $this->info("✓ {$corrigees} vente(s) corrigée(s).");
        Log::info("[ventes:reparer-mode-credit] {$corrigees} vente(s) corrigée(s).");

        $this->rapporterAnomalies();

        return self::SUCCESS;
    }

    /**
     * Signale les incohérences qui ne peuvent pas être corrigées automatiquement.
     */
    private function rapporterAnomalies(): void
    {
        // Ventes marquées crédit sans suivi de crédit associé
        $sansSuivi = DB::table('ventes')
            ->where('mode_paiement', 'credit')
            ->whereNull('credit_statut')
            ->count();

        // Ventes à crédit sans aucun encaissement : elles comptent pour 0 F
        $sansApport = DB::table('ventes')
            ->whereNotNull('credit_statut')
            ->where('mode_paiement', 'credit')
            ->where(fn ($q) => $q->whereNull('montant_paye')->orWhere('montant_paye', '<=', 0))
            ->count();

        $alertes = [];

        if ($sansSuivi > 0) {
            $alertes[] = "{$sansSuivi} vente(s) en mode « credit » sans credit_statut (à vérifier manuellement).";
        }
        if ($sansApport > 0) {
            $alertes[] = "{$sansApport} vente(s) à crédit sans aucun encaissement (comptent pour 0 F dans le chiffre d'affaires).";
        }

        if ($alertes) {
            $this->newLine();
            $this->warn('Points de vigilance :');
            foreach ($alertes as $alerte) {
                $this->line('  • ' . $alerte);
            }
        }
    }
}
