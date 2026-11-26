<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Abonnement;
use App\Models\CommercialParrainage;
use App\Models\CommercialProfile;
use App\Models\EtablissementType;
use App\Models\Institut;
use App\Models\PlanAbonnement;
use App\Models\User;
use App\Services\CommercialAttributionService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminInstitutController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('q');
        $planFilter = $request->input('plan');

        $paginator = Institut::with(['proprietaire.abonnementActif.plan'])
            ->withCount('users')
            ->when($search, fn($q) => $q->where('nom', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('ville', 'like', "%{$search}%"))
            ->when($request->status, fn($q, $s) => $q->where('actif', $s === 'actif'))
            ->when($request->type, fn($q, $t) => $q->where('type', $t))
            ->when($planFilter, function ($q, $plan) {
                // Sous-requête SQL : évite de charger en mémoire la liste
                // complète des abonnés actifs avant de filtrer.
                $abonnesActifs = Abonnement::where('statut', 'actif')->select('user_id');

                if ($plan === '__aucun__') {
                    return $q->whereNotIn('proprietaire_id', $abonnesActifs);
                }

                return $q->whereIn('proprietaire_id', $abonnesActifs->whereHas(
                    'plan',
                    fn ($p) => $p->where('slug', $plan)
                ));
            })
            ->when($request->activite, function ($q, $activite) {
                $seuilActif   = \App\Services\ActiviteInstitutService::SEUIL_ACTIF_JOURS;
                $seuilDormant = \App\Services\ActiviteInstitutService::SEUIL_DORMANT_JOURS;

                return match ($activite) {
                    'actif' => $q->where('derniere_activite_le', '>=', now()->subDays($seuilActif)),
                    'inactif' => $q->where(fn ($x) => $x->whereNull('derniere_activite_le')
                        ->orWhere('derniere_activite_le', '<', now()->subDays($seuilDormant))),
                    'jamais' => $q->whereNull('derniere_activite_le'),
                    default  => $q,
                };
            })
            ->when($request->parrainage, function ($q, $filtre) {
                $parraines = CommercialParrainage::select('proprietaire_id');

                return $filtre === 'avec'
                    ? $q->whereIn('proprietaire_id', $parraines)
                    : $q->where(fn ($x) => $x->whereNotIn('proprietaire_id', $parraines)
                        ->orWhereNull('proprietaire_id'));
            })
            ->latest()
            ->paginate(50)
            ->withQueryString();

        $grouped = $paginator->getCollection()->groupBy('proprietaire_id');
        $plans   = PlanAbonnement::where('actif', true)->orderBy('ordre')->get();

        // Types réellement utilisés par au moins un établissement :
        // on n'encombre pas le filtre avec les types jamais choisis.
        $codesTypes = Institut::query()
            ->select('type')
            ->whereNotNull('type')
            ->where('type', '!=', '')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');

        // Un seul SELECT pour tous les libellés (évite une requête par type)
        $libellesConnus = EtablissementType::whereIn('code', $codesTypes)->pluck('libelle', 'code');

        $typeLabels = $codesTypes
            ->mapWithKeys(fn ($code) => [
                $code => $libellesConnus[$code] ?? ucfirst(str_replace('_', ' ', $code)),
            ])
            ->sort()
            ->all();

        // ── Parrainage commercial ─────────────────────────────────────────────
        // Le parrainage porte sur le compte (propriétaire) : toutes les
        // lignes d'un même propriétaire portent donc la même information.
        $commerciaux = CommercialProfile::with('user')
            ->whereHas('user', fn ($u) => $u->where('actif', true))
            ->get()
            ->sortBy(fn ($c) => $c->user?->nom_complet)
            ->values();

        $parrainages = CommercialParrainage::with('commercial.user', 'institut')
            ->whereIn('proprietaire_id', $grouped->keys()->filter()->all())
            ->get()
            ->keyBy('proprietaire_id');

        return view('admin.instituts.index', compact(
            'paginator', 'grouped', 'search', 'plans', 'typeLabels',
            'commerciaux', 'parrainages'
        ));
    }

    public function show(Institut $institut)
    {
        $institut->load('users', 'proprietaire');
        // L'owner EST le proprietaire de l'institut (fonctionne pour primaire et secondaire)
        $owner = $institut->proprietaire;
        $abonnementActif  = $owner?->abonnementActif;
        $abonnementSursis = (!$abonnementActif && $owner) ? $owner->abonnementEnSursis() : null;
        $plans = PlanAbonnement::where('actif', true)->orderBy('ordre')->get();
        $historique = $owner ? Abonnement::where('user_id', $owner->id)->with('plan')->latest()->get() : collect();

        // ─── KPIs / Statistiques ───────────────────────────────────────────────
        $now = now();
        $debutMois = $now->copy()->startOfMonth();
        $debutJour = $now->copy()->startOfDay();

        // Comptes bruts (sans période)
        $totalProduits    = $institut->produits()->count();
        $totalPrestations = $institut->prestations()->count();
        $totalClients     = $institut->clients()->count();

        // Clients par période
        $clientsMois = $institut->clients()->where('created_at', '>=', $debutMois)->count();
        $clientsJour = $institut->clients()->where('created_at', '>=', $debutJour)->count();

        // Ventes : agrégation conditionnelle en 1 requête (hors crédits non payés)
        $statsVentes = $institut->ventes()->where('statut', 'validee')
            ->where(function ($q) {
                $q->where('mode_paiement', '!=', 'credit')
                  ->orWhere('montant_paye', '>', 0);
            })
            ->selectRaw("
                COUNT(*) as nb_total,
                COALESCE(SUM(CASE WHEN mode_paiement = 'credit' THEN montant_paye ELSE total END), 0) as ca_total,
                COUNT(CASE WHEN created_at >= ? THEN 1 END) as nb_mois,
                COALESCE(SUM(CASE WHEN created_at >= ? AND (mode_paiement != 'credit' OR montant_paye > 0) THEN (CASE WHEN mode_paiement = 'credit' THEN montant_paye ELSE total END) ELSE 0 END), 0) as ca_mois,
                COUNT(CASE WHEN created_at >= ? THEN 1 END) as nb_jour,
                COALESCE(SUM(CASE WHEN created_at >= ? AND (mode_paiement != 'credit' OR montant_paye > 0) THEN (CASE WHEN mode_paiement = 'credit' THEN montant_paye ELSE total END) ELSE 0 END), 0) as ca_jour
            ", [$debutMois, $debutMois, $debutJour, $debutJour])
            ->first();

        // Commandes boutique (livrées uniquement)
        $boutiqueActive = (bool) $institut->boutique_active;

        $statsCommandes = $institut->commandes()->where('statut', 'livree')
            ->selectRaw("
                COUNT(*) as nb_total,
                COALESCE(SUM(total), 0) as ca_total,
                COUNT(CASE WHEN created_at >= ? THEN 1 END) as nb_mois,
                COALESCE(SUM(CASE WHEN created_at >= ? THEN total ELSE 0 END), 0) as ca_mois,
                COUNT(CASE WHEN created_at >= ? THEN 1 END) as nb_jour,
                COALESCE(SUM(CASE WHEN created_at >= ? THEN total ELSE 0 END), 0) as ca_jour
            ", [$debutMois, $debutMois, $debutJour, $debutJour])
            ->first();

        // Commandes en ligne (toutes)
        $totalCommandes = $institut->commandes()->count();
        $commandesMois = $institut->commandes()->where('created_at', '>=', $debutMois)->count();
        $commandesJour = $institut->commandes()->where('created_at', '>=', $debutJour)->count();

        // Factures
        $totalFactures = $institut->factures()->count();
        $facturesMois = $institut->factures()->where('created_at', '>=', $debutMois)->count();
        $facturesJour = $institut->factures()->where('created_at', '>=', $debutJour)->count();

        // Devis
        $totalDevis = $institut->devis()->count();
        $devisMois = $institut->devis()->where('created_at', '>=', $debutMois)->count();
        $devisJour = $institut->devis()->where('created_at', '>=', $debutJour)->count();

        // RDV
        $totalRdv = $institut->rendezVous()->count();
        $rdvMois = $institut->rendezVous()->where('created_at', '>=', $debutMois)->count();
        $rdvJour = $institut->rendezVous()->where('created_at', '>=', $debutJour)->count();

        // Crédits clients
        $totalCredits = $institut->credits()->count();
        $creditsMois = $institut->credits()->where('created_at', '>=', $debutMois)->count();
        $creditsJour = $institut->credits()->where('created_at', '>=', $debutJour)->count();

        // ─── Activité ──────────────────────────────────────────────────────────
        $serviceActivite  = app(\App\Services\ActiviteInstitutService::class);
        $niveauActivite   = $serviceActivite->niveau($institut);
        $libelleActivite  = $serviceActivite->libelleType($institut->derniere_activite_type);
        $auteurActivite   = $institut->derniere_activite_user_id
            ? User::find($institut->derniere_activite_user_id)
            : null;

        return view('admin.instituts.show', compact(
            'institut', 'owner', 'abonnementActif', 'abonnementSursis', 'plans', 'historique',
            'totalProduits', 'totalPrestations', 'totalClients',
            'clientsMois', 'clientsJour',
            'statsVentes', 'statsCommandes', 'boutiqueActive',
            'totalCommandes', 'commandesMois', 'commandesJour',
            'totalFactures', 'facturesMois', 'facturesJour',
            'totalDevis', 'devisMois', 'devisJour',
            'totalRdv', 'rdvMois', 'rdvJour',
            'totalCredits', 'creditsMois', 'creditsJour',
            'niveauActivite', 'libelleActivite', 'auteurActivite',
        ));
    }

    public function update(Request $request, Institut $institut)
    {
        $request->validate(['actif' => ['required', 'boolean']]);
        $institut->update(['actif' => $request->actif]);
        return back()->with('success', 'Institut mis à jour.');
    }

    public function toggle(Institut $institut)
    {
        $institut->update(['actif' => !$institut->actif]);
        return back()->with('success', $institut->actif ? 'Institut activé.' : 'Institut désactivé.');
    }

    /**
     * Rattache (ou retire) le compte client d'un établissement à un commercial.
     *
     * Le parrainage commercial porte sur le compte (le propriétaire), pas sur
     * l'établissement : les commissions sont calculées à partir des
     * abonnements, qui appartiennent au compte. On enregistre donc
     * l'établissement qui a servi de point d'entrée, pour que le commercial
     * retrouve bien celui qu'on lui a attribué.
     */
    public function attribuerCommercial(Request $request, Institut $institut)
    {
        $donnees = $request->validate([
            'commercial_id' => ['nullable', 'uuid', 'exists:commercial_profiles,id'],
        ]);

        $service = app(CommercialAttributionService::class);

        // ── Retrait ───────────────────────────────────────────────────────────
        if (blank($donnees['commercial_id'] ?? null)) {
            $resultat = $service->retirer($institut);

            return match ($resultat['statut']) {
                CommercialAttributionService::STATUT_SANS_PROPRIETAIRE
                    => back()->with('error', "Cet établissement n'a pas de propriétaire : aucune attribution possible."),
                CommercialAttributionService::STATUT_AUCUN_PARRAINAGE
                    => back()->with('error', 'Ce compte n\'était parrainé par aucun commercial.'),
                default => back()->with('success', sprintf(
                    '« %s » n\'est plus rattaché à %s.',
                    $institut->nom,
                    $resultat['ancien']?->commercial?->user?->nom_complet ?? 'son commercial'
                )),
            };
        }

        // ── Attribution ───────────────────────────────────────────────────────
        $profil = CommercialProfile::with('user')->findOrFail($donnees['commercial_id']);
        $resultat = $service->attribuer($institut, $profil, Auth::user());

        return match ($resultat['statut']) {
            CommercialAttributionService::STATUT_SANS_PROPRIETAIRE
                => back()->with('error', "Cet établissement n'a pas de propriétaire : impossible de le rattacher à un commercial."),

            CommercialAttributionService::STATUT_COMMERCIAL_INACTIF
                => back()->with('error', 'Ce commercial est désactivé : réactivez-le avant de lui attribuer un établissement.'),

            CommercialAttributionService::STATUT_DEJA
                => back()->with('error', "« {$institut->nom} » est déjà rattaché à {$profil->user->nom_complet}."),

            default => back()->with('success', "« {$institut->nom} » est désormais rattaché à "
                . "{$profil->user->nom_complet} jusqu'au "
                . $resultat['parrainage']->expire_le->format('d/m/Y') . '.'),
        };
    }

    public function offrirAbonnement(Request $request, Institut $institut)
    {
        $request->validate([
            'plan_id'         => ['required', 'uuid'],
            'jours'           => ['required', 'integer', 'min:1', 'max:1095'],
            'option_boutique' => ['nullable', 'boolean'],
        ]);

        $jours = (int) $request->jours; // Cast explicite en int pour Carbon

        $owner = $institut->users->firstWhere('role', 'admin');
        if (!$owner) {
            return back()->with('error', 'Aucun propriétaire trouvé pour cet institut.');
        }

        $plan = PlanAbonnement::findOrFail($request->plan_id);

        // Mêmes règles que pour un paiement : un renouvellement ou une
        // rétrogradation enchaîne sur l'échéance en cours, une mise à niveau
        // démarre tout de suite.
        $abonnementOffert = Abonnement::create([
            'user_id' => $owner->id,
            'plan_id' => $plan->id,
            'montant' => 0,
            'periode' => 'mensuel',
            'reference_transfert' => 'OFFERT-' . strtoupper(substr(md5(uniqid()), 0, 8)),
            'statut' => 'en_attente',
            'notes_admin' => 'Abonnement offert par l\'administrateur.',
        ]);

        $periode  = app(\App\Services\AbonnementPeriodeService::class)
            ->activer($abonnementOffert, $jours, ['valide_par' => auth()->id()]);
        $expireLe = $periode['expire']->toDateString();

        // ── Option boutique en ligne (add-on, facturation par établissement) ──
        $avecBoutique = $request->boolean('option_boutique');

        if ($avecBoutique) {
            $institut->setBoutiqueOption(true, $expireLe, 3900);
        } else {
            $institut->setBoutiqueOption(false);
        }
        $institut->save();

        // Le cache d'accès boutique doit refléter le changement immédiatement
        $owner->forgetBoutiqueAccessCache($institut->id);

        // ── Réconcilier les quotas du plan attribué ──────────────────────────
        try {
            app(\App\Services\PlanLimitesService::class)->appliquer($plan, $owner);
        } catch (\Throwable $e) {
            \Log::warning('[PlanLimites] ' . $e->getMessage());
        }

        $message = "Accès offert pour {$jours} jours.";
        if ($avecBoutique) {
            $message .= ' Option boutique en ligne activée jusqu\'au '
                . \Illuminate\Support\Carbon::parse($expireLe)->format('d/m/Y') . '.';
        }

        return back()->with('success', $message);
    }

    public function destroy(Institut $institut)
    {
        $nom = $institut->nom;

        DB::transaction(function () use ($institut) {
            $userIds = $institut->users()->pluck('id');

            // Supprimer les abonnements des utilisateurs liés
            Abonnement::whereIn('user_id', $userIds)->delete();

            // Supprimer les utilisateurs liés
            User::whereIn('id', $userIds)->delete();

            // Supprimer prestations/produits AVANT leurs catégories (FK bloque la cascade)
            \App\Models\Prestation::where('institut_id', $institut->id)->delete();
            \App\Models\Produit::where('institut_id', $institut->id)->delete();

            // Supprimer l'institut (FK en cascade couvrent le reste)
            $institut->delete();
        });

        return redirect()->route('admin.instituts.index')
            ->with('success', "Établissement « {$nom} » supprimé définitivement.");
    }
}
