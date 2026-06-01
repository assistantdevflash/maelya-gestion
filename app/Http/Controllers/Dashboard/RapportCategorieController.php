<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\VenteItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RapportCategorieController extends Controller
{
    private function institutId(): string
    {
        return session('current_institut_id', Auth::user()->institut_id);
    }

    public function index(Request $request)
    {
        $debut = Carbon::parse($request->input('debut', now()->startOfMonth()->toDateString()))->startOfDay();
        $fin   = Carbon::parse($request->input('fin', now()->endOfMonth()->toDateString()))->endOfDay();

        $institutId = $this->institutId();

        // ── Catégories de prestations ──
        $prestations = VenteItem::query()
            ->select([
                'categories_prestations.nom as categorie_nom',
                DB::raw('SUM(vente_items.quantite) as quantite'),
                DB::raw('SUM(vente_items.sous_total) as chiffre_affaires'),
            ])
            ->join('ventes', 'ventes.id', '=', 'vente_items.vente_id')
            ->join('prestations', 'prestations.id', '=', 'vente_items.item_id')
            ->leftJoin('categories_prestations', 'categories_prestations.id', '=', 'prestations.categorie_id')
            ->where('vente_items.type', 'prestation')
            ->where('ventes.institut_id', $institutId)
            ->where('ventes.statut', 'validee')
            ->whereBetween('ventes.created_at', [$debut, $fin])
            ->groupBy('categories_prestations.nom')
            ->orderByDesc('chiffre_affaires')
            ->get();

        // ── Catégories de produits ──
        $produits = VenteItem::query()
            ->select([
                'categories_produits.nom as categorie_nom',
                DB::raw('SUM(vente_items.quantite) as quantite'),
                DB::raw('SUM(vente_items.sous_total) as chiffre_affaires'),
            ])
            ->join('ventes', 'ventes.id', '=', 'vente_items.vente_id')
            ->join('produits', 'produits.id', '=', 'vente_items.item_id')
            ->leftJoin('categories_produits', 'categories_produits.id', '=', 'produits.categorie_id')
            ->where('vente_items.type', 'produit')
            ->where('ventes.institut_id', $institutId)
            ->where('ventes.statut', 'validee')
            ->whereBetween('ventes.created_at', [$debut, $fin])
            ->groupBy('categories_produits.nom')
            ->orderByDesc('chiffre_affaires')
            ->get();

        $totalPrestations = (int) $prestations->sum('chiffre_affaires');
        $totalProduits    = (int) $produits->sum('chiffre_affaires');

        return view('dashboard.rapports.categories', compact(
            'prestations', 'produits', 'totalPrestations', 'totalProduits', 'debut', 'fin'
        ));
    }
}
