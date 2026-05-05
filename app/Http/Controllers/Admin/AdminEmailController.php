<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\EmailManuel;
use App\Models\EmailCampagne;
use App\Models\Institut;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AdminEmailController extends Controller
{
    public function index()
    {
        $instituts = Institut::with('proprietaire')
            ->whereHas('proprietaire')
            ->orderBy('nom')
            ->get();

        $historique = EmailCampagne::with('expediteur')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.emails.index', compact('instituts', 'historique'));
    }

    public function composer()
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
            'mode'             => ['required', 'in:tous,selection,un,personnalise'],
            'sujet'            => ['required', 'string', 'max:255'],
            'corps'            => ['required', 'string'],
            'instituts'        => ['required_if:mode,selection', 'array'],
            'instituts.*'      => ['integer', 'exists:instituts,id'],
            'institut_id'      => ['required_if:mode,un', 'nullable', 'integer', 'exists:instituts,id'],
            'email_personnalise' => ['required_if:mode,personnalise', 'nullable', 'email', 'max:255'],
            'nom_personnalise'  => ['nullable', 'string', 'max:100'],
        ], [
            'sujet.required'              => 'Le sujet est requis.',
            'corps.required'              => 'Le corps du message est requis.',
            'instituts.required_if'       => 'Sélectionnez au moins un établissement.',
            'institut_id.required_if'     => 'Sélectionnez un établissement.',
            'email_personnalise.required_if' => 'Saisissez une adresse email.',
            'email_personnalise.email'    => 'L\'adresse email n\'est pas valide.',
        ]);

        // Nettoyer le corps si Quill renvoie du HTML vide
        $corps = strip_tags($request->corps) === '' ? null : $request->corps;
        if (!$corps) {
            return back()->withErrors(['corps' => 'Le corps du message est vide.'])->withInput();
        }

        $sujet = $request->sujet;
        $mode  = $request->mode;
        $destinataires = collect();

        if ($mode === 'tous') {
            $destinataires = Institut::with('proprietaire')
                ->whereHas('proprietaire')
                ->get()
                ->map(fn($i) => $i->proprietaire)
                ->filter();

        } elseif ($mode === 'selection') {
            $destinataires = Institut::with('proprietaire')
                ->whereIn('id', $request->instituts)
                ->whereHas('proprietaire')
                ->get()
                ->map(fn($i) => $i->proprietaire)
                ->filter();

        } elseif ($mode === 'un') {
            $institut = Institut::with('proprietaire')->findOrFail($request->institut_id);
            if ($institut->proprietaire) {
                $destinataires = collect([$institut->proprietaire]);
            }

        } elseif ($mode === 'personnalise') {
            // Créer un User factice avec juste l'email et le prénom
            $fake = new User();
            $fake->email  = $request->email_personnalise;
            $fake->prenom = $request->nom_personnalise ?: explode('@', $request->email_personnalise)[0];
            $fake->nom    = '';
            $destinataires = collect([$fake]);
        }

        $envoyes = 0;
        $echecs  = 0;
        $erreurs = [];
        $emailsEnvoyes = [];

        foreach ($destinataires as $user) {
            try {
                Mail::to($user->email)->send(new EmailManuel($sujet, $corps, $user));
                $emailsEnvoyes[] = $user->email;
                $envoyes++;
            } catch (\Exception $e) {
                $echecs++;
                $erreurs[] = $user->email . ' : ' . $e->getMessage();
                Log::error('[AdminEmail] Échec envoi à ' . $user->email, [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        // Sauvegarder la campagne dans l'historique
        EmailCampagne::create([
            'envoye_par'          => auth()->id(),
            'sujet'               => $sujet,
            'corps'               => $corps,
            'mode'                => $mode,
            'destinataires_emails' => $emailsEnvoyes,
            'nb_envoyes'          => $envoyes,
            'nb_echecs'           => $echecs,
            'erreurs'             => $erreurs ? implode("\n", $erreurs) : null,
        ]);

        if ($envoyes === 0 && $echecs > 0) {
            return redirect()->route('admin.emails.index')
                ->with('error', "Aucun email n'a pu être envoyé. Consultez les logs. Erreur : " . $erreurs[0]);
        }

        $message = "Email envoyé à {$envoyes} destinataire(s) avec succès.";
        if ($echecs > 0) {
            $message .= " ({$echecs} échec(s))";
        }

        return redirect()->route('admin.emails.index')->with('success', $message);
    }
}
