<?php

namespace App\Services;

use App\Models\Commande;
use App\Models\Produit;
use App\Models\Vente;
use App\Models\VenteItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Règles métier des commandes boutique.
 *
 * Le stock est réservé dès l'acceptation de la commande : sans cela, deux
 * clients pouvaient commander le même dernier article, et le stock n'était
 * décrémenté qu'à l'encaissement (donc après la livraison).
 */
class CommandeService
{
    /**
     * Sort le stock du magasin pour cette commande.
     *
     * @throws StockInsuffisantException si un article n'est plus disponible
     */
    public function reserverStock(Commande $commande): void
    {
        if ($commande->stockReserve()) {
            return;
        }

        DB::transaction(function () use ($commande) {
            $commande->loadMissing('items.produit');

            foreach ($commande->items as $item) {
                if (! $item->produit) {
                    continue;
                }

                if ($item->produit->stock < $item->quantite) {
                    throw new StockInsuffisantException(
                        "Stock insuffisant pour {$item->produit->nom} "
                        . "(disponible : {$item->produit->stock}, demandé : {$item->quantite})."
                    );
                }
            }

            foreach ($commande->items as $item) {
                if ($item->produit) {
                    $item->produit->decrement('stock', $item->quantite);
                }
            }

            $commande->update(['stock_decremente_at' => now()]);
        });
    }

    /** Remet en stock les articles d'une commande annulée ou refusée. */
    public function libererStock(Commande $commande): void
    {
        if (! $commande->stockReserve()) {
            return;
        }

        DB::transaction(function () use ($commande) {
            $commande->loadMissing('items');

            foreach ($commande->items as $item) {
                if ($item->produit_id) {
                    Produit::where('id', $item->produit_id)
                        ->increment('stock', $item->quantite);
                }
            }

            $commande->update(['stock_decremente_at' => null]);
        });
    }

    /**
     * Enregistre l'encaissement : crée la vente, ses lignes et met la commande
     * à jour. Le stock n'est décrémenté ici que pour les commandes plus
     * anciennes, dont la réservation n'existait pas encore.
     */
    public function encaisser(Commande $commande, ?string $mode = null): Vente
    {
        if ($commande->payee) {
            throw new \RuntimeException('Cette commande est déjà payée.');
        }

        return DB::transaction(function () use ($commande, $mode) {
            $modeVente = match ($mode ?: $commande->mode_paiement) {
                'mobile_money', 'en_ligne' => 'mobile_money',
                'mixte'                    => 'mixte',
                default                    => 'cash',
            };

            $vente = Vente::create([
                'institut_id'    => $commande->institut_id,
                'client_id'      => $commande->client_id,
                'user_id'        => Auth::id(),
                'total'          => $commande->total,
                'montant_paye'   => $commande->total,
                'mode_paiement'  => $modeVente,
                'montant_cash'   => $modeVente === 'mobile_money' ? 0 : $commande->total,
                'montant_mobile' => $modeVente === 'mobile_money' ? $commande->total : 0,
                'statut'         => 'validee',
                'remise'         => 0,
                'pourboire'      => 0,
            ]);

            $stockDejaSorti = $commande->stockReserve();

            foreach ($commande->items as $item) {
                VenteItem::create([
                    'vente_id'      => $vente->id,
                    'type'          => 'produit',
                    'item_id'       => $item->produit_id,
                    'nom_snapshot'  => $item->nom_snapshot,
                    'prix_snapshot' => $item->prix_snapshot,
                    'quantite'      => $item->quantite,
                    'sous_total'    => $item->sous_total,
                ]);

                // Commandes antérieures à la réservation : le stock n'est pas
                // encore sorti, on le décrémente maintenant.
                if (! $stockDejaSorti && $item->produit) {
                    $item->produit->decrement('stock', $item->quantite);
                }
            }

            $commande->update([
                'payee'               => true,
                'payee_at'            => now(),
                'vente_id'            => $vente->id,
                'stock_decremente_at' => $commande->stock_decremente_at ?: now(),
            ]);

            return $vente;
        });
    }

    /** Annule ou refuse la commande et libère les articles réservés. */
    public function annuler(Commande $commande, string $statut, ?string $motif = null, string $par = 'admin'): void
    {
        DB::transaction(function () use ($commande, $statut, $motif, $par) {
            $commande->changerStatut($statut);
            $commande->update([
                'annulee_par'      => $par,
                'motif_annulation' => $motif,
            ]);

            $this->libererStock($commande);
        });
    }
}
