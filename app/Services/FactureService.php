<?php
namespace App\Services;
use App\Models\Facture;
use App\Models\Vente;
use App\Models\VenteItem;
use Illuminate\Support\Facades\DB;

class FactureService
{
    public static function genererNumero(): string
    {
        return DB::transaction(function () {
            $date = now()->format('Ymd');
            $last = Facture::where('numero', 'like', "FAC-{$date}-%")
                ->orderByRaw('CAST(SUBSTRING_INDEX(numero, \'-\', -1) AS UNSIGNED) DESC')
                ->lockForUpdate()->first();
            $next = $last ? ((int)substr($last->numero, -6)) + 1 : 1;
            return sprintf('FAC-%s-%06d', $date, $next);
        });
    }

    public static function marquerPayee(Facture $facture): Vente
    {
        return DB::transaction(function () use ($facture) {
            $vente = Vente::create([
                'institut_id' => $facture->institut_id,
                'client_id' => $facture->client_id,
                'user_id' => auth()->id(),
                'total' => $facture->total_ttc,
                'montant_paye' => $facture->total_ttc,
                'mode_paiement' => 'cash',
                'statut' => 'validee',
            ]);
            foreach ($facture->items as $item) {
                VenteItem::create([
                    'vente_id' => $vente->id,
                    'type' => $item->produit_id ? 'produit' : 'prestation',
                    'item_id' => $item->produit_id ?? $item->prestation_id,
                    'nom_snapshot' => $item->designation,
                    'prix_snapshot' => $item->prix_unitaire,
                    'quantite' => $item->quantite,
                    'sous_total' => $item->total_ligne,
                ]);
                if ($item->produit_id && $item->produit) {
                    $item->produit->decrement('stock', $item->quantite);
                }
            }
            $facture->update(['statut' => 'payee', 'montant_paye' => $facture->total_ttc, 'vente_id' => $vente->id]);
            return $vente;
        });
    }
}
