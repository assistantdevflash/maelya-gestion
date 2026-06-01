<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AvisClient;
use Illuminate\Http\Request;

class AvisClientController extends Controller
{
    public function index(Request $request)
    {
        $statut = $request->input('statut');
        $avis = AvisClient::query()
            ->whereNotNull('repondu_le')
            ->when($statut, fn ($q) => $q->where('statut', $statut))
            ->orderByDesc('repondu_le')
            ->paginate(25)
            ->withQueryString();

        return view('dashboard.avis.index', compact('avis', 'statut'));
    }

    public function approuver(AvisClient $avis)
    {
        $avis->update(['statut' => 'approuve']);
        return back()->with('success', 'Avis approuvé et publié sur la vitrine.');
    }

    public function rejeter(AvisClient $avis)
    {
        $avis->update(['statut' => 'rejete']);
        return back()->with('success', 'Avis rejeté.');
    }
}
