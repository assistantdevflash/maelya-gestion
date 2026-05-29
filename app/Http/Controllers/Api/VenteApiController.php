<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vente;
use Illuminate\Http\Request;

class VenteApiController extends Controller
{
    public function index(Request $request)
    {
        $institutId = $request->user()->institut_id;
        $q = Vente::where('institut_id', $institutId)->latest();

        if ($d = $request->query('date_debut')) $q->whereDate('created_at', '>=', $d);
        if ($d = $request->query('date_fin'))   $q->whereDate('created_at', '<=', $d);
        if ($s = $request->query('statut'))      $q->where('statut', $s);

        return response()->json($q->with('client:id,nom,prenom')->paginate(min((int) $request->query('per_page', 25), 100)));
    }

    public function show(Request $request, Vente $vente)
    {
        abort_if($vente->institut_id !== $request->user()->institut_id, 404);
        return response()->json($vente->load('items', 'paiements', 'client'));
    }
}
