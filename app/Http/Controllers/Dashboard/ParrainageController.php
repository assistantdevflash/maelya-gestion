<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Parrainage;
use Illuminate\Support\Facades\Auth;

class ParrainageController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $parrainages = Parrainage::where('parrain_id', $user->id)
            ->with('filleul.institut')
            ->latest()
            ->get();

        $stats = [
            'total_filleuls' => $parrainages->count(),
            'valides' => $parrainages->where('statut', 'valide')->count(),
            'en_attente' => $parrainages->where('statut', 'en_attente')->count(),
            'jours_gagnes' => $parrainages->where('statut', 'valide')->sum('jours_offerts_parrain'),
        ];

        $parrainageActif = $user->isParrainageActif();

        return view('dashboard.parrainage.index', compact('user', 'parrainages', 'stats', 'parrainageActif'));
    }
}
