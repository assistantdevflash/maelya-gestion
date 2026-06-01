<?php

namespace App\Http\Controllers;

use App\Models\AvisClient;
use App\Models\Institut;
use Illuminate\Http\Request;

class AvisPublicController extends Controller
{
    public function show(string $token)
    {
        $avis = AvisClient::withoutGlobalScopes()->where('token', $token)->firstOrFail();
        if ($avis->repondu_le) {
            return view('public.avis.deja-repondu', compact('avis'));
        }
        $institut = Institut::find($avis->institut_id);
        return view('public.avis.formulaire', compact('avis', 'institut'));
    }

    public function submit(Request $request, string $token)
    {
        $avis = AvisClient::withoutGlobalScopes()->where('token', $token)->firstOrFail();
        if ($avis->repondu_le) {
            return redirect()->route('public.avis.show', $token);
        }
        $data = $request->validate([
            'note'        => ['required', 'integer', 'min:1', 'max:5'],
            'commentaire' => ['nullable', 'string', 'max:1000'],
        ]);
        $avis->forceFill([
            'note'        => $data['note'],
            'commentaire' => $data['commentaire'] ?? null,
            'statut'      => 'en_attente', // l'admin validera pour l'affichage public
            'repondu_le'  => now(),
        ])->save();

        return view('public.avis.merci', compact('avis'));
    }
}
