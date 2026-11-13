<?php

namespace App\Services;

use App\Models\Institut;
use App\Models\MouvementStock;
use App\Models\Produit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Porte d'entrée UNIQUE de toute modification de stock.
 *
 * Avant ce service, huit endroits du code faisaient `increment`/`decrement`
 * directement. Cinq d'entre eux ne créaient aucun mouvement : l'historique
 * était donc incomplet et un écart d'inventaire devenait inexplicable.
 *
 * Règles appliquées ici :
 *  - la ligne produit est verrouillée pendant l'opération (pas de perte de
 *    mise à jour quand deux ventes tombent en même temps) ;
 *  - chaque écriture génère un mouvement avec les valeurs RÉELLES avant/après
 *    (l'ancien code enregistrait `max(0, …)`, ce qui masquait les stocks
 *    négatifs) ;
 *  - une sortie au-delà du disponible est refusée, sauf réglage explicite de
 *    l'établissement.
 */
class StockService
{
    // Catégories imposées par l'enum de la table (ne pas inventer de valeur).
    public const ENTREE           = 'entree';
    public const SORTIE_VENTE     = 'sortie_vente';
    public const CORRECTION       = 'correction';
    public const ANNULATION_VENTE = 'annulation_vente';

    // Motifs métier, stockés dans la colonne `motif`.
    public const MOTIF_RESERVATION = 'reservation';
    public const MOTIF_LIBERATION  = 'liberation';
    public const MOTIF_FACTURE     = 'facture';
    public const MOTIF_BON_COMMANDE = 'bon_commande';
    public const MOTIF_INVENTAIRE  = 'inventaire';

    /** Réglage lu une seule fois par requête (évite un N+1 par ligne vendue). */
    private array $cacheNegatifAutorise = [];

    private function negatifAutorise(string $institutId): bool
    {
        return $this->cacheNegatifAutorise[$institutId] ??=
            (bool) Institut::withoutGlobalScopes()->whereKey($institutId)->value('stock_negatif_autorise');
    }

    /**
     * Ajoute du stock (réapprovisionnement, réception, retour).
     *
     * @param  array{type?: string, note?: ?string, prix_unitaire?: ?int, vente_id?: ?string, recalculer_cmp?: bool}  $options
     */
    public function entrer(Produit $produit, int $quantite, array $options = []): ?MouvementStock
    {
        if ($quantite <= 0) {
            return null;
        }

        return DB::transaction(function () use ($produit, $quantite, $options) {
            $verrouille = $this->verrouiller($produit);

            $stockAvant = (int) $verrouille->stock;
            $prixUnit   = isset($options['prix_unitaire']) ? (int) $options['prix_unitaire'] : null;

            // CMP recalculé avant l'incrément (il a besoin du stock courant)
            $recalculer = $options['recalculer_cmp'] ?? ($prixUnit !== null && $prixUnit > 0);
            if ($recalculer && $prixUnit !== null && $prixUnit > 0) {
                $verrouille->recalculerCmp($quantite, $prixUnit);
            }

            $verrouille->increment('stock', $quantite);

            return $this->tracer($verrouille, [
                'type'         => $options['type'] ?? self::ENTREE,
                'motif'        => $options['motif'] ?? null,
                'quantite'     => $quantite,
                'prix_unitaire' => $prixUnit ?: null,
                'vente_id'     => $options['vente_id'] ?? null,
                'stock_avant'  => $stockAvant,
                'stock_apres'  => $stockAvant + $quantite,
                'note'         => $options['note'] ?? null,
            ]);
        });
    }

    /**
     * Retire du stock (vente, réservation de commande).
     *
     * @param  array{type?: string, note?: ?string, vente_id?: ?string, autoriser_negatif?: bool}  $options
     *
     * @throws StockInsuffisantException si le stock ne couvre pas la demande
     */
    public function sortir(Produit $produit, int $quantite, array $options = []): ?MouvementStock
    {
        if ($quantite <= 0) {
            return null;
        }

        return DB::transaction(function () use ($produit, $quantite, $options) {
            $verrouille = $this->verrouiller($produit);

            $stockAvant = (int) $verrouille->stock;
            $autoriserNegatif = $options['autoriser_negatif']
                ?? $this->negatifAutorise($verrouille->institut_id);

            if (! $autoriserNegatif && $stockAvant < $quantite) {
                throw new StockInsuffisantException(
                    "Stock insuffisant pour {$verrouille->nom} "
                    . "(disponible : {$stockAvant}, demandé : {$quantite})."
                );
            }

            $verrouille->decrement('stock', $quantite);

            return $this->tracer($verrouille, [
                'type'        => $options['type'] ?? self::SORTIE_VENTE,
                'motif'       => $options['motif'] ?? null,
                'quantite'    => $quantite,
                'vente_id'    => $options['vente_id'] ?? null,
                // Coût unitaire figé au moment de la sortie : c'est lui qui
                // permet de calculer la marge réelle des ventes.
                'prix_unitaire' => $options['prix_unitaire'] ?? null,
                'stock_avant' => $stockAvant,
                'stock_apres' => $stockAvant - $quantite,
                'note'        => $options['note'] ?? null,
            ]);
        });
    }

    /**
     * Force le stock à une valeur cible (correction manuelle, inventaire).
     *
     * Retourne null si la valeur est déjà la bonne : rien à tracer.
     *
     * @param  array{type?: string, note?: ?string}  $options
     */
    public function ajuster(Produit $produit, int $stockCible, array $options = []): ?MouvementStock
    {
        if ($stockCible < 0) {
            $stockCible = 0;
        }

        return DB::transaction(function () use ($produit, $stockCible, $options) {
            $verrouille = $this->verrouiller($produit);

            $stockAvant = (int) $verrouille->stock;
            $ecart      = $stockCible - $stockAvant;

            if ($ecart === 0) {
                return null;
            }

            $verrouille->update(['stock' => $stockCible]);

            return $this->tracer($verrouille, [
                'type'        => $options['type'] ?? self::CORRECTION,
                'motif'       => $options['motif'] ?? null,
                'quantite'    => abs($ecart),
                'stock_avant' => $stockAvant,
                'stock_apres' => $stockCible,
                'note'        => $options['note'] ?? null,
            ]);
        });
    }

    /**
     * Recharge la ligne avec un verrou d'écriture.
     *
     * `withoutGlobalScopes` : le service est aussi appelé depuis la boutique
     * publique, où aucun établissement n'est « courant ».
     */
    private function verrouiller(Produit $produit): Produit
    {
        return Produit::withoutGlobalScopes()
            ->lockForUpdate()
            ->findOrFail($produit->id);
    }

    private function tracer(Produit $produit, array $valeurs): MouvementStock
    {
        return MouvementStock::withoutGlobalScopes()->create([
            'institut_id'   => $produit->institut_id,
            'produit_id'    => $produit->id,
            'user_id'       => Auth::id(),
            'vente_id'      => $valeurs['vente_id'] ?? null,
            'type'          => $valeurs['type'],
            'motif'         => $valeurs['motif'] ?? null,
            'quantite'      => $valeurs['quantite'],
            'prix_unitaire' => $valeurs['prix_unitaire'] ?? null,
            'stock_avant'   => $valeurs['stock_avant'],
            'stock_apres'   => $valeurs['stock_apres'],
            'note'          => $valeurs['note'] ?? null,
        ]);
    }
}
