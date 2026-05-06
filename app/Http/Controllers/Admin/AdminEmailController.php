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
        try {
            $historique = EmailCampagne::with('expediteur')
                ->orderByDesc('created_at')
                ->paginate(20);
        } catch (\Throwable $e) {
            Log::error('[AdminEmail] Table email_campagnes inaccessible : ' . $e->getMessage());
            $historique = new \Illuminate\Pagination\LengthAwarePaginator(collect(), 0, 20, 1);
        }

        return view('admin.emails.index', compact('historique'));
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
            'mode'               => ['required', 'in:tous,selection,un,personnalise'],
            'send_mode'          => ['required', 'in:email,both,push'],
            'sujet'              => ['required_unless:send_mode,push', 'nullable', 'string', 'max:255'],
            'corps'              => ['required_unless:send_mode,push', 'nullable', 'string'],
            'instituts'          => ['required_if:mode,selection', 'array'],
            'instituts.*'        => ['string', 'exists:instituts,id'],
            'institut_id'        => ['required_if:mode,un', 'nullable', 'string', 'exists:instituts,id'],
            'email_personnalise'  => ['required_if:mode,personnalise', 'nullable', 'email', 'max:255'],
            'nom_personnalise'    => ['nullable', 'string', 'max:100'],
            'push_titre'          => ['required_unless:send_mode,email', 'nullable', 'string', 'max:60'],
            'push_message'        => ['nullable', 'string', 'max:120'],
        ], [
            'sujet.required_unless'          => 'Le sujet est requis pour un envoi email.',
            'corps.required_unless'          => 'Le corps du message est requis pour un envoi email.',
            'push_titre.required_unless'     => 'Le titre de la notification est requis.',
            'instituts.required_if'          => 'Sélectionnez au moins un établissement.',
            'institut_id.required_if'        => 'Sélectionnez un établissement.',
            'email_personnalise.required_if' => 'Saisissez une adresse email.',
            'email_personnalise.email'       => 'L\'adresse email n\'est pas valide.',
        ]);

        $sendMode = $request->input('send_mode', 'email');
        $doEmail  = in_array($sendMode, ['email', 'both']);
        $doPush   = in_array($sendMode, ['both', 'push']);

        // Nettoyer le corps si Quill renvoie du HTML vide (seulement si email requis)
        $corps = null;
        if ($doEmail) {
            $corps = strip_tags($request->corps ?? '') === '' ? null : $request->corps;
            if (!$corps) {
                return back()->withErrors(['corps' => 'Le corps du message est vide.'])->withInput();
            }
        }

        $sujet       = $request->sujet ?? '';
        $mode        = $request->mode;
        $pushTitre   = $request->input('push_titre') ?: mb_substr($sujet, 0, 60);
        $pushMessage = $request->input('push_message') ?: ($corps ? mb_substr(strip_tags($corps), 0, 120) : '');
        // Mode push-only sur email personnalisé : impossible, on l'interdit côté frontend mais on sécurise côté serveur
        if ($sendMode === 'push' && $mode === 'personnalise') {
            return back()->withErrors(['send_mode' => 'La notification push n\'est pas disponible en mode email personnalisé.'])->withInput();
        }
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
                if ($doEmail) {
                    Mail::to($user->email)->send(new EmailManuel($sujet, $corps, $user));
                    $emailsEnvoyes[] = $user->email;
                    $envoyes++;
                }
                if ($doPush && ($user->id ?? null)) {
                    try {
                        app(\App\Services\PushNotificationService::class)->sendToUser(
                            $user,
                            $pushTitre,
                            $pushMessage,
                            '/dashboard'
                        );
                    } catch (\Throwable $e) { \Log::warning('[Push] ' . $e->getMessage()); }
                    if (!$doEmail) $envoyes++; // compter les envois push-only
                }
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
        try {
            EmailCampagne::create([
                'envoye_par'           => auth()->id(),
                'sujet'                => $sujet,
                'corps'                => $corps,
                'mode'                 => $mode,
                'destinataires_emails' => $emailsEnvoyes,
                'nb_envoyes'           => $envoyes,
                'nb_echecs'            => $echecs,
                'erreurs'              => $erreurs ? implode("\n", $erreurs) : null,
            ]);
        } catch (\Throwable $e) {
            Log::error('[AdminEmail] Impossible de sauvegarder la campagne : ' . $e->getMessage());
        }

        if ($envoyes === 0 && $echecs > 0) {
            return redirect()->route('admin.emails.index')
                ->with('error', "Aucun email n'a pu être envoyé. Consultez les logs. Erreur : " . $erreurs[0]);
        }

        $what = match($sendMode) {
            'both'  => 'Email + notification envoyé(s)',
            'push'  => 'Notification envoyée',
            default => 'Email envoyé',
        };
        $message = "{$what} à {$envoyes} destinataire(s) avec succès.";
        if ($echecs > 0) {
            $message .= " ({$echecs} échec(s))";
        }

        return redirect()->route('admin.emails.index')->with('success', $message);
    }
}
