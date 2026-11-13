<?php

namespace App\Services;

use App\Models\Depense;
use App\Models\Vente;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Calculs financiers de l'établissement.
 *
 * Le résultat net suit une chaîne explicite :
 *   CA encaissé − coût des marchandises vendues = marge brute
 *   marge brute − dépenses d'exploitation          = résultat net
 *
 * Le coût des marchandises est lu sur les mouvements de stock (le prix
 * unitaire y est figé au moment de la vente), et non sur le CMP actuel :
 * c'est le coût réel de ce qui a été vendu sur la période.
 */
class FinanceService
{
    /**
     * Indicateurs de résultat sur une période.
     *
     * @return array<string, mixed>
     */
    public function resultat(string $institutId, CarbonInterface $debut, CarbonInterface $fin): array
    {
        $ca = (float) (Vente::withoutGlobalScopes()
            ->where('institut_id', $institutId)
            ->where('statut', 'validee')
            ->whereBetween('created_at', [$debut, $fin])
            ->selectRaw("SUM(CASE WHEN mode_paiement = 'credit' THEN COALESCE(montant_paye, 0) ELSE total END) as ca")
            ->value('ca') ?? 0);

        // Toutes les ventes validées comptent, y compris les crédits non encore
        // réglés : ce sont de vraies ventes, même si l'argent n'est pas rentré.
        $nbVentes = Vente::withoutGlobalScopes()
            ->where('institut_id', $institutId)
            ->where('statut', 'validee')
            ->whereBetween('created_at', [$debut, $fin])
            ->count();

        $couts = $this->coutMarchandises($institutId, $debut, $fin);

        $depenses = (float) Depense::withoutGlobalScopes()
            ->where('institut_id', $institutId)
            ->whereBetween('date', [$debut->startOfDay(), $fin->endOfDay()])
            ->sum('montant');

        $margeBrute = $ca - $couts;
        $resultat   = $margeBrute - $depenses;

        return [
            'ca'                 => (int) round($ca),
            'couts_marchandises' => (int) round($couts),
            'marge_brute'        => (int) round($margeBrute),
            'taux_marge_brute'   => $ca > 0 ? round($margeBrute * 100 / $ca, 1) : 0.0,
            'depenses'           => (int) round($depenses),
            'resultat'           => (int) round($resultat),
            'marge_nette'        => $ca > 0 ? round($resultat * 100 / $ca, 1) : 0.0,
            'nb_ventes'          => $nbVentes,
            'panier_moyen'       => $nbVentes > 0 ? (int) round($ca / $nbVentes) : 0,
            // Seuil de rentabilité : CA à réaliser pour couvrir les charges,
            // compte tenu du taux de marge brute constaté.
            'seuil_rentabilite'  => $margeBrute > 0
                ? (int) round($depenses * $ca / $margeBrute)
                : null,
        ];
    }

    /**
     * Coût des marchandises vendues sur la période.
     *
     * Somme des sorties de stock liées à une vente validée, valorisées au prix
     * unitaire enregistré au moment du mouvement.
     */
    public function coutMarchandises(string $institutId, CarbonInterface $debut, CarbonInterface $fin): float
    {
        // Repli sur le coût moyen pondéré du produit pour les mouvements
        // antérieurs à l'enregistrement du prix unitaire.
        return (float) DB::table('mouvements_stock')
            ->join('ventes', 'ventes.id', '=', 'mouvements_stock.vente_id')
            ->leftJoin('produits', 'produits.id', '=', 'mouvements_stock.produit_id')
            ->where('ventes.institut_id', $institutId)
            ->where('ventes.statut', 'validee')
            ->whereBetween('ventes.created_at', [$debut, $fin])
            ->where('mouvements_stock.type', StockService::SORTIE_VENTE)
            ->selectRaw('COALESCE(SUM(mouvements_stock.quantite * COALESCE(
                    mouvements_stock.prix_unitaire,
                    produits.cout_moyen_pondere,
                    produits.prix_achat,
                    0
                )), 0) as cout')
            ->value('cout');
    }

    /**
     * Encours de crédit client : argent dû mais pas encore encaissé.
     *
     * @return array{encours: int, en_retard: int, nb_credits: int}
     */
    public function encoursCredit(string $institutId): array
    {
        // Calcul en PHP : GREATEST n'existe pas en SQLite et MAX(a,b) n'est pas
        // scalaire en MySQL. Le volume reste faible (échéances impayées).
        $echeances = DB::table('credits')
            ->join('echeances', 'echeances.credit_id', '=', 'credits.id')
            ->where('credits.institut_id', $institutId)
            ->whereIn('echeances.statut', ['en_attente', 'retard'])
            ->get([
                'credits.id as credit_id',
                'echeances.montant',
                'echeances.montant_paye',
                'echeances.date_prevue',
            ]);

        $encours  = 0;
        $retard   = 0;
        $credits  = [];

        foreach ($echeances as $echeance) {
            $du = max(0, (int) $echeance->montant - (int) $echeance->montant_paye);

            if ($du === 0) {
                continue;
            }

            $encours += $du;
            $credits[$echeance->credit_id] = true;

            if ($echeance->date_prevue && \Carbon\Carbon::parse($echeance->date_prevue)->isPast()) {
                $retard += $du;
            }
        }

        return [
            'encours'    => $encours,
            'en_retard'  => $retard,
            'nb_credits' => count($credits),
        ];
    }

    /**
     * Dépenses de la période sans aucun justificatif.
     *
     * Ni référence écrite, ni pièce jointe. La colonne `justificatif` seule
     * ne suffisait pas : aucun formulaire ne l'alimentait, donc le compteur
     * valait toujours le nombre total de dépenses.
     */
    public function depensesSansJustificatif(string $institutId, CarbonInterface $debut, CarbonInterface $fin): int
    {
        return Depense::withoutGlobalScopes()
            ->where('institut_id', $institutId)
            ->whereBetween('date', [$debut->startOfDay(), $fin->endOfDay()])
            ->where(function ($q) {
                $q->whereNull('justificatif')->orWhere('justificatif', '');
            })
            ->where(function ($q) {
                $q->whereNull('justificatif_fichier')->orWhere('justificatif_fichier', '');
            })
            ->count();
    }
}
