<?php
namespace App\Services;
use App\Models\Avoir;
use App\Models\Facture;
use App\Models\Institut;
use App\Models\Paiement;
use App\Models\Vente;
use App\Models\VenteItem;
use Illuminate\Support\Facades\DB;

class FactureService
{
    public static function genererNumero(string $institutId): string
    {
        // Séquence « journalière » (FAC-20261106-000001) ou « annuelle »
        // (FAC-2026-000001), réglable par établissement.
        $sequence = Institut::withoutGlobalScopes()->whereKey($institutId)->value('facturation_sequence')
            ?: 'journaliere';
        $date = $sequence === 'annuelle' ? now()->format('Y') : now()->format('Ymd');

        return DB::transaction(function () use ($institutId, $date) {
            $prefix = "FAC-{$date}-";

            // Le numéro est complété à 6 chiffres : l'ordre alphabétique suit
            // l'ordre numérique, ce qui évite les fonctions SQL propres à MySQL.
            $last = Facture::where('institut_id', $institutId)
                ->where('numero', 'like', $prefix . '%')
                ->orderByDesc('numero')
                ->lockForUpdate()
                ->first();

            $next = $last ? ((int) substr($last->numero, -6)) + 1 : 1;

            return sprintf('FAC-%s-%06d', $date, $next);
        });
    }

    public static function genererNumeroAvoir(string $institutId): string
    {
        return DB::transaction(function () use ($institutId) {
            $prefix = 'AV-' . now()->format('Y') . '-';

            $last = Avoir::withoutGlobalScopes()
                ->where('institut_id', $institutId)
                ->where('numero', 'like', $prefix . '%')
                ->orderByDesc('numero')
                ->lockForUpdate()
                ->first();

            $next = $last ? ((int) substr($last->numero, -6)) + 1 : 1;

            return sprintf('AV-%s-%06d', now()->format('Y'), $next);
        });
    }

    /**
     * Solde la facture : enregistre un paiement pour le reste dû, crée la vente
     * correspondante (avec ses lignes) et décrémente le stock des produits.
     */
    public static function marquerPayee(Facture $facture): Vente
    {
        return DB::transaction(function () use ($facture) {
            $reste = $facture->resteAPayer;

            // Seul le reste dû est encaissé : les paiements partiels déjà
            // enregistrés ont leur propre vente et ne doivent pas être comptés deux fois.
            $vente = Vente::create([
                'institut_id'   => $facture->institut_id,
                'client_id'     => $facture->client_id,
                'user_id'       => auth()->id(),
                'total'         => $reste,
                'montant_paye'  => $reste,
                'mode_paiement' => 'cash',
                'statut'        => 'validee',
            ]);

            foreach ($facture->items as $item) {
                VenteItem::create([
                    'vente_id'      => $vente->id,
                    'type'          => $item->produit_id ? 'produit' : 'prestation',
                    'item_id'       => $item->produit_id ?? $item->prestation_id,
                    'nom_snapshot'  => $item->designation,
                    'prix_snapshot' => $item->prix_unitaire,
                    'quantite'      => $item->quantite,
                    'sous_total'    => $item->total_ligne,
                ]);
                if ($item->produit_id && $item->produit) {
                    app(StockService::class)->sortir($item->produit, (int) $item->quantite, [
                        'type' => StockService::SORTIE_VENTE,
                        'note' => 'Facture ' . $facture->numero,
                    ]);
                }
            }

            // Trace du règlement : sans cela l'historique des paiements restait vide.
            Paiement::create([
                'facture_id'    => $facture->id,
                'vente_id'      => $vente->id,
                'user_id'       => auth()->id(),
                'montant'       => $reste,
                'mode_paiement' => 'especes',
                'reference'     => 'Solde facture ' . $facture->numero,
                'date_paiement' => now()->toDateString(),
            ]);

            $facture->update([
                'statut'       => 'payee',
                'montant_paye' => $facture->total_ttc,
                'vente_id'     => $vente->id,
            ]);

            return $vente;
        });
    }
}
