<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Institut;
use App\Models\Vente;

class CarteFideliteController extends Controller
{
    public function show(string $token)
    {
        $client = Client::withoutGlobalScopes()->where('fidelite_token', $token)->firstOrFail();
        $institut = Institut::find($client->institut_id);

        $derniereVisites = Vente::withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->where('statut', 'validee')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'total', 'created_at']);

        return view('public.carte-fidelite', compact('client', 'institut', 'derniereVisites'));
    }
}
