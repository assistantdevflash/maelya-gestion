<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Institut;
use App\Models\Vente;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Chiffres du comparatif multi-établissements.
 *
 * Deux principes :
 *   1. Le CA suit la trésorerie réelle — pour une vente à crédit, on ne
 *      compte que ce qui a été encaissé (montant_paye), comme dans
 *      l'historique des ventes.
 *   2. On interroge la base par lots groupés sur l'ensemble des
 *      établissements, et non établissement par établissement.
 */
class ComparatifInstitutsService
{
    /**
     * Calcule la période demandée et la période précédente équivalente.
     *
     * @return array{debut: Carbon, fin: Carbon, libelle: string, prec_debut: Carbon, prec_fin: Carbon}
     */
    public function periode(string $choix, ?string $du = null, ?string $au = null): array
    {
        $fin   = now()->endOfDay();
        $debut = now()->startOfMonth();
        $libelle = 'Ce mois-ci';

        switch ($choix) {
            case 'hier':
                $debut = today()->subDay()->startOfDay();
                $fin   = today()->subDay()->endOfDay();
                $libelle = 'Hier';
                break;

            case '7j':
                $debut = today()->subDays(6)->startOfDay();
                $libelle = '7 derniers jours';
                break;

            case '30j':
                $debut = today()->subDays(29)->startOfDay();
                $libelle = '30 derniers jours';
                break;

            case 'mois_dernier':
                $debut = now()->subMonthNoOverflow()->startOfMonth();
                $fin   = now()->subMonthNoOverflow()->endOfMonth();
                $libelle = 'Mois dernier';
                break;

            case 'annee':
                $debut = now()->startOfYear();
                $libelle = 'Année ' . now()->year;
                break;

            case 'perso':
                $debut = $du ? Carbon::parse($du)->startOfDay() : now()->startOfMonth();
                $fin   = $au ? Carbon::parse($au)->endOfDay() : now()->endOfDay();
                $libelle = $debut->format('d/m/Y') . ' → ' . $fin->format('d/m/Y');
                break;

            default:
                $libelle = 'Ce mois-ci';
        }

        // Période précédente : même durée, juste avant.
        $duree      = $debut->diffInSeconds($fin) + 1;
        $precFin    = $debut->copy()->subSecond();
        $precDebut  = $precFin->copy()->subSeconds($duree - 1);

        return [
            'debut'      => $debut,
            'fin'        => $fin,
            'libelle'    => $libelle,
            'prec_debut' => $precDebut,
            'prec_fin'   => $precFin,
        ];
    }

    /**
     * Statistiques de chaque établissement sur la période.
     *
     * @param  Collection<int, Institut>  $instituts
     * @return array<int, array<string, mixed>>
     */
    public function stats(Collection $instituts, Carbon $debut, Carbon $fin, ?Carbon $precDebut = null, ?Carbon $precFin = null): array
    {
        $ids = $instituts->pluck('id')->all();

        if ($ids === []) {
            return [];
        }

        // ── Ventes (période courante) ──
        $parInstitut = Vente::withoutGlobalScopes()
            ->whereIn('institut_id', $ids)
            ->whereBetween('created_at', [$debut, $fin])
            ->groupBy('institut_id')
            ->selectRaw("
                institut_id,
                COUNT(*) as nb_total,
                SUM(CASE WHEN statut = 'annulee' THEN 1 ELSE 0 END) as nb_annulees,
                SUM(CASE WHEN statut != 'annulee'
                         THEN CASE WHEN mode_paiement = 'credit' THEN COALESCE(montant_paye, 0) ELSE total END
                         ELSE 0 END) as ca,
                SUM(CASE WHEN statut != 'annulee' THEN COALESCE(remise, 0) ELSE 0 END) as remises,
                SUM(CASE WHEN statut != 'annulee' THEN 1 ELSE 0 END) as nb_valides
            ")
            ->get()
            ->keyBy('institut_id');

        // ── Ventes à crédit : part du chiffre d'affaires ──
        $credits = Vente::withoutGlobalScopes()
            ->whereIn('institut_id', $ids)
            ->whereBetween('created_at', [$debut, $fin])
            ->where('statut', '!=', 'annulee')
            ->where('mode_paiement', 'credit')
            ->groupBy('institut_id')
            ->selectRaw('institut_id, COUNT(*) as nb')
            ->pluck('nb', 'institut_id');

        // ── Modes de paiement ──
        $modes = Vente::withoutGlobalScopes()
            ->whereIn('institut_id', $ids)
            ->whereBetween('created_at', [$debut, $fin])
            ->where('statut', '!=', 'annulee')
            ->groupBy('institut_id', 'mode_paiement')
            ->selectRaw('institut_id, mode_paiement, COUNT(*) as nb, SUM(CASE WHEN mode_paiement = \'credit\' THEN COALESCE(montant_paye, 0) ELSE total END) as montant')
            ->get()
            ->groupBy('institut_id');

        // ── Période précédente (pour l'évolution) ──
        $precedent = collect();
        if ($precDebut && $precFin) {
            $precedent = Vente::withoutGlobalScopes()
                ->whereIn('institut_id', $ids)
                ->whereBetween('created_at', [$precDebut, $precFin])
                ->where('statut', '!=', 'annulee')
                ->groupBy('institut_id')
                ->selectRaw("
                    institut_id,
                    SUM(CASE WHEN mode_paiement = 'credit' THEN COALESCE(montant_paye, 0) ELSE total END) as ca,
                    COUNT(*) as nb
                ")
                ->get()
                ->keyBy('institut_id');
        }

        // ── Meilleures prestations (3 par établissement) ──
        $tops = DB::table('vente_items')
            ->join('ventes', 'ventes.id', '=', 'vente_items.vente_id')
            ->whereIn('ventes.institut_id', $ids)
            ->where('ventes.statut', '!=', 'annulee')
            ->whereBetween('ventes.created_at', [$debut, $fin])
            ->where('vente_items.type', 'prestation')
            ->selectRaw('ventes.institut_id, vente_items.nom_snapshot as nom, SUM(vente_items.quantite) as qte, SUM(vente_items.sous_total) as ca')
            ->groupBy('ventes.institut_id', 'vente_items.nom_snapshot')
            ->orderByDesc('qte')
            ->get()
            ->groupBy('institut_id')
            ->map(fn ($lignes) => $lignes->take(3)->values());

        // ── Produits vendus (quantité) ──
        $produits = DB::table('vente_items')
            ->join('ventes', 'ventes.id', '=', 'vente_items.vente_id')
            ->whereIn('ventes.institut_id', $ids)
            ->where('ventes.statut', '!=', 'annulee')
            ->whereBetween('ventes.created_at', [$debut, $fin])
            ->where('vente_items.type', 'produit')
            ->groupBy('ventes.institut_id')
            ->selectRaw('ventes.institut_id, SUM(vente_items.quantite) as qte, SUM(vente_items.sous_total) as ca')
            ->get()
            ->keyBy('institut_id');

        // ── Clients : total actif et nouveaux sur la période ──
        $clients = Client::withoutGlobalScopes()
            ->whereIn('institut_id', $ids)
            ->where('actif', true)
            ->groupBy('institut_id')
            ->selectRaw('institut_id, COUNT(*) as total')
            ->pluck('total', 'institut_id');

        $nouveauxClients = Client::withoutGlobalScopes()
            ->whereIn('institut_id', $ids)
            ->whereBetween('created_at', [$debut, $fin])
            ->groupBy('institut_id')
            ->selectRaw('institut_id, COUNT(*) as total')
            ->pluck('total', 'institut_id');

        $stats = [];

        foreach ($instituts as $institut) {
            $ligne     = $parInstitut->get($institut->id);
            $ca        = (int) ($ligne->ca ?? 0);
            $nbValides = (int) ($ligne->nb_valides ?? 0);
            $nbTotal   = (int) ($ligne->nb_total ?? 0);
            $nbAnnul   = (int) ($ligne->nb_annulees ?? 0);

            $caPrec = (int) ($precedent->get($institut->id)->ca ?? 0);

            $stats[] = [
                'institut'        => $institut,
                'ca'              => $ca,
                'ca_precedent'    => $caPrec,
                'evolution'       => $caPrec > 0
                    ? round(($ca - $caPrec) * 100 / $caPrec, 1)
                    : null,
                'nb_ventes'       => $nbValides,
                'panier_moyen'    => $nbValides > 0 ? (int) round($ca / $nbValides) : 0,
                'nb_annulees'     => $nbAnnul,
                'taux_annulation' => $nbTotal > 0 ? round($nbAnnul * 100 / $nbTotal, 1) : 0.0,
                'remises'         => (int) ($ligne->remises ?? 0),
                'part_credit'     => $nbValides > 0
                    ? round(((int) ($credits[$institut->id] ?? 0)) * 100 / $nbValides, 1)
                    : 0.0,
                'modes'           => $modes->get($institut->id, collect()),
                'top_prestations' => $tops->get($institut->id, collect()),
                'produits_qte'    => (int) ($produits->get($institut->id)->qte ?? 0),
                'produits_ca'     => (int) ($produits->get($institut->id)->ca ?? 0),
                'nb_clients'      => (int) ($clients[$institut->id] ?? 0),
                'nb_nouveaux'     => (int) ($nouveauxClients[$institut->id] ?? 0),
            ];
        }

        return $stats;
    }
}
