<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\CommercialCommissionPayee;
use App\Models\CommercialCommission;
use App\Models\CommercialProfile;
use App\Models\Institut;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class AdminCommercialController extends Controller
{
    // ── Liste des commerciaux ─────────────────────────────────────────────────
    public function index(Request $request)
    {
        $recherche = $request->input('q');

        $commerciaux = User::where('role', 'commercial')
            // Les compteurs sont agrégés en SQL : la vue les lisait ligne par
            // ligne (4 requêtes par commercial, en double dans les deux rendus
            // mobile et desktop).
            ->with(['commercialProfile' => fn ($p) => $p
                ->withCount([
                    'parrainages',
                    'parrainages as parrainages_actifs_count' => fn ($q) => $q->actifs(),
                ])
                ->withSum('commissions as commissions_total', 'montant')
                ->withSum(['commissions as commissions_payees' => fn ($q) => $q->where('statut', 'payee')], 'montant')
            ])
            ->when($recherche, fn ($q, $s) => $q->where(fn ($u) => $u
                ->where('prenom', 'like', "%{$s}%")
                ->orWhere('nom_famille', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")
                ->orWhereHas('commercialProfile', fn ($p) => $p->where('code', 'like', "%{$s}%"))
            ))
            ->when($request->statut, fn ($q, $statut) => $q->where('actif', $statut === 'actif'))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $config = DB::table('commercial_config')->first();

        $payees    = (int) CommercialCommission::where('statut', 'payee')->sum('montant');
        $enAttente = (int) CommercialCommission::where('statut', 'en_attente')->sum('montant');

        $stats = [
            'total'      => User::where('role', 'commercial')->count(),
            'actifs'     => User::where('role', 'commercial')->where('actif', true)->count(),
            'payees'     => $payees,
            'en_attente' => $enAttente,
            'du'         => $payees + $enAttente,
        ];

        return view('admin.commerciaux.index', compact('commerciaux', 'config', 'stats'));
    }

    // ── Détail d'un commercial ────────────────────────────────────────────────
    public function show(User $commercial)
    {
        abort_unless($commercial->role === 'commercial', 404);

        $profil = $commercial->commercialProfile()->firstOrFail();

        // Listes bornées : `$profil->parrainages` chargeait tout l'historique
        // du commercial, sans limite ni pagination.
        $parrainages = $profil->parrainages()
            ->with(['institut', 'proprietaire.institut', 'attribuePar'])
            ->withSum('commissions as commissions_total', 'montant')
            ->latest()
            ->limit(20)
            ->get();

        $commissions = $profil->commissions()
            ->with(['parrainage.institut', 'parrainage.proprietaire.institut', 'abonnement.plan', 'payePar'])
            ->latest()
            ->limit(30)
            ->get();

        $totaux = [
            'parrainages'      => $profil->parrainages()->count(),
            'parrainages_actifs' => $profil->parrainages()->actifs()->count(),
            'payees'           => (int) $profil->commissions()->where('statut', 'payee')->sum('montant'),
            'en_attente'       => $profil->totalEnAttente(),
            'commissions'      => $profil->commissions()->count(),
        ];

        // Établissements rattachés à ce commercial, pour l'attribution directe.
        $etablissementsDisponibles = Institut::with('proprietaire')
            ->whereNotNull('proprietaire_id')
            ->orderBy('nom')
            ->get(['id', 'nom', 'ville', 'proprietaire_id']);

        return view('admin.commerciaux.show', compact(
            'commercial', 'profil', 'parrainages', 'commissions', 'totaux', 'etablissementsDisponibles'
        ));
    }

    // ── Créer un commercial ───────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'prenom'      => ['required', 'string', 'max:50'],
            'nom_famille' => ['required', 'string', 'max:50'],
            'email'       => ['required', 'email', 'unique:users,email'],
            'telephone'   => ['nullable', 'string', 'max:30'],
            'password'    => ['required', Rules\Password::min(8)],
            'notes'       => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($request) {
            $user = User::create([
                'prenom'      => $request->prenom,
                'nom_famille' => $request->nom_famille,
                'name'        => $request->prenom . ' ' . $request->nom_famille,
                'email'       => $request->email,
                'telephone'   => $request->telephone,
                'password'    => Hash::make($request->password),
                'role'        => 'commercial',
                'actif'       => true,
            ]);

            CommercialProfile::create([
                'user_id'   => $user->id,
                'code'      => $this->generateCode($request->prenom, $request->nom_famille),
                'telephone' => $request->telephone,
                'notes'     => $request->notes,
            ]);
        });

        return back()->with('success', 'Commercial créé avec succès.');
    }

    // ── Mettre à jour ─────────────────────────────────────────────────────────
    public function update(Request $request, User $commercial)
    {
        abort_unless($commercial->role === 'commercial', 404);

        $request->validate([
            'prenom'      => ['required', 'string', 'max:50'],
            'nom_famille' => ['required', 'string', 'max:50'],
            'telephone'   => ['nullable', 'string', 'max:30'],
            'notes'       => ['nullable', 'string', 'max:500'],
        ]);

        $commercial->update([
            'prenom'      => $request->prenom,
            'nom_famille' => $request->nom_famille,
            'name'        => $request->prenom . ' ' . $request->nom_famille,
            'telephone'   => $request->telephone,
        ]);

        $commercial->commercialProfile?->update([
            'telephone' => $request->telephone,
            'notes'     => $request->notes,
        ]);

        return back()->with('success', 'Commercial mis à jour.');
    }

    // ── Activer/Désactiver ────────────────────────────────────────────────────
    public function toggle(User $commercial)
    {
        abort_unless($commercial->role === 'commercial', 404);

        $commercial->update(['actif' => !$commercial->actif]);

        $message = $commercial->actif
            ? 'Commercial activé : il peut se connecter et percevoir ses commissions.'
            : 'Commercial désactivé : accès bloqué et commissions gelées. Les parrainages existants sont conservés.';

        return back()->with('success', $message);
    }

    // ── Supprimer ─────────────────────────────────────────────────────────────
    public function destroy(User $commercial)
    {
        abort_unless($commercial->role === 'commercial', 404);

        // La suppression cascade sur les commissions (clé étrangère) : effacer
        // l'historique financier d'un commercial n'est jamais anodin.
        $commissions = CommercialCommission::where('commercial_id', $commercial->commercialProfile?->id);

        if ($commissions->exists()) {
            $total = (int) $commissions->sum('montant');

            return back()->with('error', sprintf(
                'Suppression refusée : ce commercial a %s de commissions (%s). Désactivez-le pour lui couper l\'accès sans perdre l\'historique.',
                number_format($total, 0, ',', ' ') . ' FCFA',
                \App\Helpers\TexteHelper::pluriel($commissions->count(), 'commission', 'commissions')
            ));
        }

        $nom = $commercial->nom_complet;
        $commercial->delete();

        return redirect()->route('admin.commerciaux.index')
            ->with('success', "Le compte de {$nom} a été supprimé.");
    }

    // ── Attribuer un établissement depuis la fiche du commercial ──────────────
    public function attribuerEtablissement(Request $request, User $commercial)
    {
        abort_unless($commercial->role === 'commercial', 404);

        $donnees = $request->validate([
            'institut_id' => ['required', 'uuid', 'exists:instituts,id'],
        ]);

        $profil = $commercial->commercialProfile()->firstOrFail();
        $institut = Institut::findOrFail($donnees['institut_id']);

        $resultat = app(\App\Services\CommercialAttributionService::class)
            ->attribuer($institut, $profil, Auth::user());

        return match ($resultat['statut']) {
            \App\Services\CommercialAttributionService::STATUT_SANS_PROPRIETAIRE
                => back()->with('error', "« {$institut->nom} » n'a pas de propriétaire : rattachement impossible."),

            \App\Services\CommercialAttributionService::STATUT_COMMERCIAL_INACTIF
                => back()->with('error', 'Ce commercial est désactivé : réactivez-le d\'abord.'),

            \App\Services\CommercialAttributionService::STATUT_DEJA
                => back()->with('error', "« {$institut->nom} » est déjà rattaché à ce commercial."),

            default => back()->with('success', sprintf(
                '« %s » est désormais rattaché à ce commercial jusqu\'au %s.',
                $institut->nom,
                $resultat['parrainage']->expire_le->format('d/m/Y')
            )),
        };
    }

    // ── Marquer commission payée ──────────────────────────────────────────────
    public function payerCommission(Request $request, CommercialCommission $commission)
    {
        if ($commission->statut === 'payee') {
            return back()->with('error', 'Cette commission est déjà marquée comme payée.');
        }

        $donnees = $request->validate([
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $commission->update([
            'statut'         => 'payee',
            'payee_le'       => now(),
            'paye_par'       => Auth::id(),
            'notes_paiement' => $donnees['note'] ?? null,
        ]);

        // Notifier le commercial par email + push
        $commission->load(['commercial.user', 'abonnement.user', 'abonnement.plan']);
        $commercialUser = $commission->commercial?->user;
        if ($commercialUser) {
            try {
                Mail::to($commercialUser->email)->send(new CommercialCommissionPayee($commercialUser, $commission));
            } catch (\Throwable $e) { \Log::warning('[Mail Commercial] ' . $e->getMessage()); }
            try {
                app(\App\Services\PushNotificationService::class)->sendToUser(
                    $commercialUser,
                    '💳 Commission versée',
                    'Votre commission de ' . number_format($commission->montant, 0, ',', ' ') . ' FCFA a été versée.',
                    '/commercial/commissions'
                );
            } catch (\Throwable $e) { \Log::warning('[Push Commercial] ' . $e->getMessage()); }
            \App\Services\NotificationService::notifyUser(
                $commercialUser,
                'commission_payee',
                '💳 Commission de ' . number_format($commission->montant, 0, ',', ' ') . ' FCFA versée',
                'Votre commission a été marquée comme payée.',
                '/commercial/commissions'
            );
        }

        return back()->with('success', 'Commission marquée comme payée.');
    }

    // ── Annuler paiement ──────────────────────────────────────────────────────
    public function annulerPaiement(Request $request, CommercialCommission $commission)
    {
        if ($commission->statut !== 'payee') {
            return back()->with('error', 'Cette commission n\'est pas marquée comme payée.');
        }

        $donnees = $request->validate([
            'motif' => ['nullable', 'string', 'max:255'],
        ]);

        $commission->update([
            'statut'         => 'en_attente',
            'payee_le'       => null,
            'paye_par'       => null,
            'notes_paiement' => $donnees['motif'] ?? null,
        ]);

        return back()->with('success', 'Paiement annulé : la commission repasse en attente.');
    }

    // ── Sauvegarder la config ─────────────────────────────────────────────────
    public function updateConfig(Request $request)
    {
        $request->validate([
            'taux'       => ['required', 'integer', 'min:1', 'max:100'],
            'duree_mois' => ['required', 'integer', 'min:1', 'max:60'],
        ]);

        // `update()` sur une table vide n'écrit rien et n'échoue pas :
        // l'admin croyait avoir enregistré.
        if (!DB::table('commercial_config')->exists()) {
            DB::table('commercial_config')->insert([
                'taux'       => $request->taux,
                'duree_mois' => $request->duree_mois,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('commercial_config')->update([
                'taux'       => $request->taux,
                'duree_mois' => $request->duree_mois,
                'updated_at' => now(),
            ]);
        }

        return back()->with('success', 'Configuration mise à jour.');
    }

    // ── Générer un code unique ────────────────────────────────────────────────
    private function generateCode(string $prenom, string $nom): string
    {
        // Les accents étaient supprimés par le filtre : « Élise » donnait une
        // base vide, donc des codes à une seule lettre.
        $base = Str::ascii(mb_substr($prenom, 0, 2) . mb_substr($nom, 0, 2));
        $base = strtoupper(preg_replace('/[^A-Za-z]/', '', $base) ?: 'COM');
        $base = mb_substr($base, 0, 4);

        for ($i = 0; $i < 20; $i++) {
            $code = $base . random_int(1000, 9999);
            if (!CommercialProfile::where('code', $code)->exists()) {
                return $code;
            }
        }

        // Repli : on garantit l'unicité même après 20 collisions.
        do {
            $code = strtoupper(Str::random(6));
        } while (CommercialProfile::where('code', $code)->exists());

        return $code;
    }
}
