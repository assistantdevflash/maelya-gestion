<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\EmailManuel;
use App\Models\Institut;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AdminEmailController extends Controller
{
    public function index()
    {
        $instituts = Institut::with('proprietaire')
            ->whereHas('proprietaire')
            ->orderBy('nom')
            ->get();

        return view('admin.emails.composer', compact('instituts'));
    }

    public function send(Request $request)
    {
        $request->validate([
            'mode'      => ['required', 'in:tous,selection,un'],
            'sujet'     => ['required', 'string', 'max:255'],
            'corps'     => ['required', 'string'],
            'instituts' => ['required_if:mode,selection', 'array'],
            'instituts.*' => ['integer', 'exists:instituts,id'],
            'institut_id' => ['required_if:mode,un', 'nullable', 'integer', 'exists:instituts,id'],
        ], [
            'sujet.required'     => 'Le sujet est requis.',
            'corps.required'     => 'Le corps du message est requis.',
            'instituts.required_if' => 'Sélectionnez au moins un établissement.',
            'institut_id.required_if' => 'Sélectionnez un établissement.',
        ]);

        $destinataires = collect();

        if ($request->mode === 'tous') {
            $destinataires = Institut::with('proprietaire')
                ->whereHas('proprietaire')
                ->get()
                ->map(fn($i) => $i->proprietaire)
                ->filter();

        } elseif ($request->mode === 'selection') {
            $destinataires = Institut::with('proprietaire')
                ->whereIn('id', $request->instituts)
                ->whereHas('proprietaire')
                ->get()
                ->map(fn($i) => $i->proprietaire)
                ->filter();

        } elseif ($request->mode === 'un') {
            $institut = Institut::with('proprietaire')->findOrFail($request->institut_id);
            if ($institut->proprietaire) {
                $destinataires = collect([$institut->proprietaire]);
            }
        }

        $envoyes = 0;
        foreach ($destinataires as $user) {
            Mail::to($user->email)->send(new EmailManuel($request->sujet, $request->corps, $user));
            $envoyes++;
        }

        return redirect()->route('admin.emails.index')
            ->with('success', "Email envoyé à {$envoyes} établissement(s) avec succès.");
    }
}
