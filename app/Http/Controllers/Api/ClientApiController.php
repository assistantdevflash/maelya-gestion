<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientApiController extends Controller
{
    public function index(Request $request)
    {
        $institutId = $request->user()->institut_id;
        $q = Client::where('institut_id', $institutId);

        if ($s = $request->query('q')) {
            $q->where(function ($w) use ($s) {
                $w->where('nom', 'like', "%$s%")
                  ->orWhere('prenom', 'like', "%$s%")
                  ->orWhere('telephone', 'like', "%$s%");
            });
        }

        return response()->json($q->paginate(min((int) $request->query('per_page', 25), 100)));
    }

    public function show(Request $request, Client $client)
    {
        abort_if($client->institut_id !== $request->user()->institut_id, 404);
        return response()->json($client->load('historiquePoints'));
    }
}
