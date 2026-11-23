<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\EmailManuel;
use App\Models\EmailCampagne;
use App\Models\Institut;
use App\Models\AnnonceAdmin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Emails, notifications push et bannières depuis l'espace super-admin.
 *
 * L'envoi de masse se fait par tranches successives appelées en AJAX :
 * la campagne est créée avant le premier message, sa progression est suivie
 * en direct, et un envoi interrompu peut être repris. Cela évite de bloquer
 * une requête HTTP le temps de trente envois SMTP — et de perdre la trace
 * de ce qui est parti si elle expire.
 */
class AdminEmailController extends Controller
{
    /** Nombre de destinataires traités par requête. */
    public const TAILLE_LOT = 5;

    public function index(Request $request)
    {
        try {
            $historique = EmailCampagne::with('expediteur')
                ->orderByDesc('created_at')
                ->paginate(20);
        } catch (\Throwable $e) {
            Log::error('[AdminEmail] Table email_campagnes inaccessible : ' . $e->getMessage());
            $historique = new \Illuminate\Pagination\LengthAwarePaginator(collect(), 0, 20, 1);
        }

        try {
            $bannieres = AnnonceAdmin::with(['expediteur', 'lecteurs'])
                ->withCount('lecteurs')
                ->orderByDesc('created_at')
                ->paginate(20);
        } catch (\Throwable $e) {
            Log::error('[AdminEmail] Table annonces_admin inaccessible : ' . $e->getMessage());
            $bannieres = new \Illuminate\Pagination\LengthAwarePaginator(collect(), 0, 20, 1);
        }

        // L'onglet demandé par les redirections (après une action sur une
        // bannière) était ignoré : la vue le code en dur à « emails ».
        $onglet = in_array($request->query('tab'), ['emails', 'bannieres'], true)
            ? $request->query('tab')
            : 'emails';

        return view('admin.emails.index', compact('historique', 'bannieres', 'onglet'));
    }

    public function composer(Request $request)
    {
        $instituts = Institut::with('proprietaire')
            ->whereHas('proprietaire')
            ->orderBy('nom')
            ->get();

        // Adresse du propriétaire par établissement : permet de compter en
        // direct les doublons d'une sélection (un propriétaire peut gérer
        // plusieurs établissements).
        $emailsParInstitut = $instituts->mapWithKeys(fn ($i) => [
            $i->id => mb_strtolower((string) $i->proprietaire?->email),
        ]);

        // Réutilisation d'une campagne passée : on repart du sujet et du corps.
        $brouillon = null;
        if ($request->filled('depuis')) {
            $brouillon = EmailCampagne::find($request->query('depuis'));
        }

        $tailleLot = (int) config('admin.emails.taille_lot', self::TAILLE_LOT);

        return view('admin.emails.composer', compact('instituts', 'emailsParInstitut', 'brouillon', 'tailleLot'));
    }

    /**
     * Crée la campagne et renvoie la liste de ses cibles.
     * Aucun message n'est envoyé ici : les tranches suivent.
     */
    public function demarrer(Request $request)
    {
        $canal = (string) $request->input('send_mode', 'email');

        $regles = [
            'mode'      => ['required', 'in:tous,selection,un,personnalise'],
            'send_mode' => ['required', 'in:email,both,push,banner'],
        ];

        if (in_array($canal, ['email', 'both'], true)) {
            $regles['sujet'] = ['required', 'string', 'max:255'];
            $regles['corps'] = ['required', 'string'];
        }

        if (in_array($canal, ['push', 'both'], true)) {
            $regles['push_titre']   = ['required', 'string', 'max:60'];
            $regles['push_message'] = ['nullable', 'string', 'max:120'];
        }

        if ($canal === 'banner') {
            $regles['banner_titre']   = ['required', 'string', 'max:60'];
            $regles['banner_message'] = ['required', 'string', 'max:500'];
            $regles['banner_type']    = ['nullable', 'in:info,success,warning,danger'];
            $regles['banner_duree']   = ['nullable', 'integer', 'min:1', 'max:365'];
        }

        $regles += match ($request->input('mode')) {
            'selection'    => ['instituts' => ['required', 'array', 'min:1'], 'instituts.*' => ['string', 'exists:instituts,id']],
            'un'           => ['institut_id' => ['required', 'string', 'exists:instituts,id']],
            'personnalise' => ['email_personnalise' => ['required', 'email', 'max:255'], 'nom_personnalise' => ['nullable', 'string', 'max:100']],
            default        => [],
        };

        $donnees = $request->validate($regles, [
            'sujet.required'          => 'Le sujet est requis pour un envoi par email.',
            'corps.required'          => 'Le corps du message est requis pour un envoi par email.',
            'push_titre.required'     => 'Le titre de la notification est requis.',
            'banner_titre.required'   => 'Le titre de la bannière est requis.',
            'banner_message.required' => 'Le message de la bannière est requis.',
            'instituts.required'      => 'Sélectionnez au moins un établissement.',
            'institut_id.required'    => 'Sélectionnez un établissement.',
            'email_personnalise.required' => 'Saisissez une adresse email.',
        ]);

        // ── Bannière : création immédiate, rien à envoyer ──────────────────
        if ($canal === 'banner') {
            if ($request->input('mode') === 'personnalise') {
                return response()->json([
                    'ok'     => false,
                    'erreur' => 'Les bannières ne sont pas disponibles en mode « email personnalisé ».',
                ], 422);
            }

            $resultat = $this->creerBanniere($request);

            return response()->json([
                'ok'       => true,
                'redirect' => route('admin.emails.index', ['tab' => 'bannieres']),
                'message'  => $resultat['message'],
            ]);
        }

        if ($request->input('mode') === 'personnalise' && in_array($canal, ['push', 'both'], true)) {
            return response()->json([
                'ok'     => false,
                'erreur' => 'La notification push n\'est pas disponible en mode « email personnalisé » : il n\'y a pas de compte associé.',
            ], 422);
        }

        $cibles = $this->cibles($request);

        if ($cibles === []) {
            return response()->json([
                'ok'     => false,
                'erreur' => 'Aucun destinataire valide pour cette sélection.',
            ], 422);
        }

        $corps = in_array($canal, ['email', 'both'], true)
            ? (strip_tags((string) ($donnees['corps'] ?? '')) === '' ? null : $donnees['corps'])
            : null;

        if (in_array($canal, ['email', 'both'], true) && ! $corps) {
            return response()->json([
                'ok'     => false,
                'erreur' => 'Le corps du message est vide.',
            ], 422);
        }

        $titrePush   = $donnees['push_titre'] ?? null;
        $messagePush = $donnees['push_message'] ?? null;

        if ($titrePush) {
            $messagePush = $messagePush ?: mb_substr(strip_tags((string) $corps), 0, 120);
        }

        $campagne = EmailCampagne::create([
            'envoye_par'           => auth()->id(),
            'sujet'                => $donnees['sujet'] ?? ($titrePush ?: ''),
            'corps'                => $corps,
            'mode'                 => $donnees['mode'],
            'send_mode'            => $canal,
            'statut'               => 'en_cours',
            'nb_total'             => count($cibles),
            'nb_envoyes'           => 0,
            'nb_echecs'            => 0,
            'destinataires_emails' => [],
            'cibles'               => $cibles,
        ]);

        return response()->json([
            'ok'          => true,
            'campagne_id' => $campagne->id,
            'total'       => $campagne->nb_total,
            'canal'       => $campagne->canal_libelle,
            'destinataires' => array_column($cibles, 'email'),
            'lot'         => (int) config('admin.emails.taille_lot', self::TAILLE_LOT),
        ]);
    }

    /**
     * Traite la tranche suivante de la campagne.
     */
    public function lot(EmailCampagne $campagne)
    {
        $campagne->refresh();

        if ($campagne->nb_restants <= 0) {
            $this->cloturer($campagne);

            return response()->json($this->etatCampagne($campagne, [], []));
        }

        $tranche = $campagne->prochaineTranche((int) config('admin.emails.taille_lot', self::TAILLE_LOT));

        $doEmail = in_array($campagne->send_mode, ['email', 'both', null], true);
        $doPush  = in_array($campagne->send_mode, ['push', 'both'], true);

        $envoyes = 0;
        $echecs  = 0;
        $erreurs = [];
        $emailsOk = [];

        foreach ($tranche as $cible) {
            $email  = (string) ($cible['email'] ?? '');
            $prenom = (string) ($cible['prenom'] ?? '');
            $userId = $cible['user_id'] ?? null;

            if ($email === '') {
                $echecs++;
                $erreurs[] = 'Adresse manquante dans la cible.';

                continue;
            }

            try {
                if ($doEmail) {
                    Mail::to($email)->send(new EmailManuel(
                        (string) $campagne->sujet,
                        (string) $campagne->corps,
                        $prenom
                    ));
                }

                if ($doPush && $userId) {
                    $utilisateur = User::find($userId);
                    if ($utilisateur) {
                        app(\App\Services\PushNotificationService::class)->sendToUser(
                            $utilisateur,
                            (string) ($campagne->sujet ?: 'Maëlya Gestion'),
                            mb_substr(strip_tags((string) $campagne->corps), 0, 120),
                            '/dashboard'
                        );
                    }
                }

                $envoyes++;
                $emailsOk[] = $email;
            } catch (\Throwable $e) {
                $echecs++;
                $erreurs[] = $email . ' : ' . $e->getMessage();
                Log::error('[AdminEmail] Échec envoi à ' . $email, ['error' => $e->getMessage()]);
            }
        }

        if ($envoyes > 0) {
            $campagne->increment('nb_envoyes', $envoyes);
        }
        if ($echecs > 0) {
            $campagne->increment('nb_echecs', $echecs);
        }

        $campagne->refresh();

        $campagne->update([
            'destinataires_emails' => array_values(array_merge($campagne->destinataires_emails ?? [], $emailsOk)),
            'erreurs'              => trim(
                ($campagne->erreurs ? $campagne->erreurs . "\n" : '') . implode("\n", $erreurs)
            ) ?: null,
        ]);

        if ($campagne->nb_restants <= 0) {
            $this->cloturer($campagne);
        }

        return response()->json($this->etatCampagne($campagne, $emailsOk, $erreurs));
    }

    /**
     * Envoi de contrôle à l'adresse du super-admin connecté.
     */
    public function tester(Request $request)
    {
        $donnees = $request->validate([
            'sujet' => ['required', 'string', 'max:255'],
            'corps' => ['required', 'string'],
        ], [
            'sujet.required' => 'Renseignez un sujet avant d\'envoyer le test.',
            'corps.required' => 'Renseignez un message avant d\'envoyer le test.',
        ]);

        $destinataire = auth()->user();

        try {
            Mail::to($destinataire->email)->send(new EmailManuel(
                '[TEST] ' . $donnees['sujet'],
                $donnees['corps'],
                $destinataire->prenom ?: $destinataire->name
            ));
        } catch (\Throwable $e) {
            Log::error('[AdminEmail] Test impossible : ' . $e->getMessage());

            return response()->json([
                'ok'     => false,
                'erreur' => 'Envoi du test impossible : ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'ok'     => true,
            'message'=> 'Test envoyé à ' . $destinataire->email . '.',
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────

    /**
     * Construit la liste des destinataires, dédoublonnée par adresse.
     *
     * Un propriétaire qui gère plusieurs établissements ne doit recevoir
     * qu'un seul message : sans `unique`, il recevait l'email autant de fois
     * qu'il a d'établissements.
     *
     * @return array<int, array{email: string, prenom: string, user_id: ?string}>
     */
    private function cibles(Request $request): array
    {
        $mode = (string) $request->input('mode');

        if ($mode === 'personnalise') {
            $email = (string) $request->input('email_personnalise');

            $cibles = collect([[
                'email'   => $email,
                'prenom'  => (string) ($request->input('nom_personnalise') ?: explode('@', $email)[0]),
                'user_id' => null,
            ]]);
        } else {
            $requete = Institut::with('proprietaire')->whereHas('proprietaire');

            if ($mode === 'selection') {
                $requete->whereIn('id', (array) $request->input('instituts', []));
            } elseif ($mode === 'un') {
                $requete->where('id', $request->input('institut_id'));
            }

            $cibles = $requete->get()
                ->map(fn ($institut) => $institut->proprietaire)
                ->filter()
                ->map(fn (User $proprietaire) => [
                    'email'   => (string) $proprietaire->email,
                    'prenom'  => (string) ($proprietaire->prenom ?: $proprietaire->name),
                    'user_id' => $proprietaire->id,
                ]);
        }

        return $cibles
            ->filter(fn ($c) => filled($c['email']))
            ->unique(fn ($c) => mb_strtolower($c['email']))
            ->values()
            ->all();
    }

    private function cloturer(EmailCampagne $campagne): void
    {
        $campagne->refresh();

        $statut = 'termine';
        if ($campagne->nb_envoyes === 0 && $campagne->nb_echecs > 0) {
            $statut = 'echec';
        } elseif ($campagne->nb_echecs > 0) {
            $statut = 'partial';
        }

        $campagne->update(['statut' => $statut]);
    }

    /**
     * @param  array<int, string>  $emailsOk
     * @param  array<int, string>  $erreurs
     * @return array<string, mixed>
     */
    private function etatCampagne(EmailCampagne $campagne, array $emailsOk, array $erreurs): array
    {
        $campagne->refresh();

        return [
            'ok'         => true,
            'envoyes'    => $campagne->nb_envoyes,
            'echecs'     => $campagne->nb_echecs,
            'total'      => $campagne->nb_total,
            'restants'   => $campagne->nb_restants,
            'progression'=> $campagne->progression,
            'statut'     => $campagne->statut,
            'statut_libelle' => $campagne->statut_libelle,
            'termine'    => $campagne->nb_restants <= 0,
            'emails_ok'  => $emailsOk,
            'erreurs'    => $erreurs,
        ];
    }

    /**
     * Crée une bannière administrateur.
     *
     * @return array{nb_cibles: int, message: string}
     */
    private function creerBanniere(Request $request): array
    {
        $mode = (string) $request->input('mode');
        $institutIds = [];

        if ($mode === 'tous') {
            $cible = 'tous';
        } elseif ($mode === 'selection') {
            $cible = 'selection';
            $institutIds = (array) $request->input('instituts', []);
        } else {
            $cible = 'un';
            $institutIds = [$request->input('institut_id')];
        }

        $expireLe = $request->filled('banner_duree')
            ? now()->addDays((int) $request->input('banner_duree'))
            : null;

        AnnonceAdmin::create([
            'expediteur_id' => auth()->id(),
            'titre'         => $request->input('banner_titre'),
            'message'       => $request->input('banner_message'),
            'type'          => $request->input('banner_type') ?: 'info',
            'cible'         => $cible,
            'instituts_ids' => $institutIds,
            'actif'         => true,
            'expire_le'     => $expireLe,
        ]);

        $nbCibles = $cible === 'tous' ? Institut::count() : count($institutIds);

        $duree = $request->filled('banner_duree')
            ? ' (expire dans ' . \App\Helpers\TexteHelper::pluriel((int) $request->input('banner_duree'), 'jour', 'jours') . ')'
            : ' (sans expiration)';

        return [
            'nb_cibles' => $nbCibles,
            'message'   => 'Bannière créée et visible pour '
                . \App\Helpers\TexteHelper::pluriel($nbCibles, 'établissement', 'établissements')
                . $duree . '.',
        ];
    }

    /**
     * Marquer une bannière comme lue (côté établissement, AJAX)
     */
    public function marquerBanniereLue(Request $request, AnnonceAdmin $annonce)
    {
        $annonce->marquerCommeLue(auth()->user());

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    /**
     * Activer / désactiver une bannière
     */
    public function toggleBanniere(AnnonceAdmin $annonce)
    {
        $annonce->update(['actif' => ! $annonce->actif]);

        $label = $annonce->actif ? 'activée' : 'désactivée';

        return redirect()->route('admin.emails.index', ['tab' => 'bannieres'])
            ->with('success', "Bannière {$label} avec succès.");
    }

    /**
     * Supprimer une bannière
     */
    public function deleteBanniere(AnnonceAdmin $annonce)
    {
        $annonce->lecteurs()->detach();
        $annonce->delete();

        return redirect()->route('admin.emails.index', ['tab' => 'bannieres'])
            ->with('success', 'Bannière supprimée.');
    }
}
