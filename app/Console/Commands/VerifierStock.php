<?php

namespace App\Console\Commands;

use App\Models\Institut;
use App\Models\MouvementStock;
use App\Models\Produit;
use App\Services\StockService;
use Illuminate\Console\Command;

/**
 * Contrôle d'intégrité du stock.
 *
 * Jusqu'ici, cinq flux modifiaient `produits.stock` sans créer de mouvement
 * (facture payée, réservation de commande, annulation de facture…). Les fiches
 * produits créées avant cette correction peuvent donc avoir un stock qui ne
 * correspond à aucun mouvement.
 *
 * Cette commande compare le stock affiché au `stock_apres` du dernier
 * mouvement et signale les divergences. `--corriger` crée un mouvement de
 * réconciliation : aucun stock n'est modifié, c'est la TRAÇABILITÉ qui est
 * rétablie.
 */
class VerifierStock extends Command
{
    protected $signature = 'stock:verifier
                            {--institut= : Limiter à un établissement (id)}
                            {--corriger : Crée un mouvement de réconciliation pour les écarts}';

    protected $description = "Vérifie la cohérence entre le stock affiché et l'historique des mouvements.";

    public function handle(): int
    {
        $query = Produit::withoutGlobalScopes()
            ->when($this->option('institut'), fn ($q, $id) => $q->where('institut_id', $id))
            ->orderBy('nom');

        $total      = 0;
        $divergents = 0;
        $corriges   = 0;
        $lignes     = [];

        $query->chunk(200, function ($produits) use (&$total, &$divergents, &$corriges, &$lignes) {
            $ids = $produits->pluck('id');

            // Dernier mouvement de chaque produit, en une requête
            $derniers = MouvementStock::withoutGlobalScopes()
                ->whereIn('produit_id', $ids)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get(['id', 'produit_id', 'stock_apres', 'created_at'])
                ->unique('produit_id')
                ->keyBy('produit_id');

            foreach ($produits as $produit) {
                $total++;
                $dernier = $derniers->get($produit->id);

                $stockRefer = $dernier ? (int) $dernier->stock_apres : null;
                $stockAffiche = (int) $produit->stock;

                // Un produit sans aucun mouvement est normal s'il vient d'être
                // créé (stock saisi à la création) : on ne le signale pas.
                if ($stockRefer === null) {
                    continue;
                }

                if ($stockRefer === $stockAffiche) {
                    continue;
                }

                $divergents++;
                $lignes[] = [
                    $produit->nom,
                    $stockAffiche,
                    $stockRefer,
                    $dernier->created_at?->format('d/m/Y'),
                ];

                if ($this->option('corriger')) {
                    // On ne touche PAS au stock affiché : c'est le gérant qui
                    // fait foi. On reconstitue le mouvement manquant, de sorte
                    // que `dernier.stock_apres` corresponde de nouveau au stock
                    // réellement en rayon.
                    MouvementStock::withoutGlobalScopes()->create([
                        'institut_id' => $produit->institut_id,
                        'produit_id'  => $produit->id,
                        'user_id'     => null,
                        'type'        => StockService::CORRECTION,
                        'motif'       => 'reconciliation',
                        'quantite'    => abs($stockAffiche - $stockRefer),
                        'stock_avant' => $stockRefer,
                        'stock_apres' => $stockAffiche,
                        'note'        => 'Réconciliation automatique (stock:verifier)',
                    ]);
                    $corriges++;
                }
            }
        });

        if ($divergents === 0) {
            $this->info("Aucun écart : {$total} produit(s) contrôlé(s).");

            return self::SUCCESS;
        }

        $this->warn("{$divergents} produit(s) sur {$total} ont un stock incohérent avec l'historique :");
        $this->table(['Produit', 'Stock affiché', 'Dernier mouvement', 'Date'], $lignes);

        if ($this->option('corriger')) {
            $this->info("{$corriges} mouvement(s) de réconciliation créé(s). Aucun stock n'a été modifié.");
        } else {
            $this->line('Relancez avec --corriger pour créer les mouvements manquants.');
        }

        return self::SUCCESS;
    }
}
