<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produit;
use Illuminate\Http\Request;

class ProduitApiController extends Controller
{
    public function index(Request $request)
    {
        $institutId = $request->user()->institut_id;
        $q = Produit::where('institut_id', $institutId)->where('actif', true);

        if ($request->boolean('alertes_seulement')) {
            $q->whereColumn('stock', '<=', 'seuil_alerte');
        }

        return response()->json($q->orderBy('nom')->paginate(min((int) $request->query('per_page', 25), 100)));
    }

    public function show(Request $request, Produit $produit)
    {
        abort_if($produit->institut_id !== $request->user()->institut_id, 404);
        return response()->json($produit);
    }
}
