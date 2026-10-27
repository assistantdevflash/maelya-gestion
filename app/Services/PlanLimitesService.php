<?php

namespace App\Services;

use App\Models\Institut;
use App\Models\PlanAbonnement;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Gestion des limites de plan lors d'une rétrogradation.
 *
 * Deux responsabilités :
 *  1. `analyser()`  — calcule ce qui serait perdu (lecture seule, aucune écriture).
 *  2. `appliquer()` — réconcilie réellement l'état du compte avec les quotas
 *     du plan (mise en sommeil / réveil des établissements et des employés).
 *
 * `appliquer()` est idempotent : il peut être rejoué après un upgrade, un
 * downgrade ou un changement de quota côté admin.
 */
class PlanLimitesService
{
    /**
     * Fonctionnalités différenciantes entre les plans.
     *
     * Sert uniquement à calculer ce qu'un utilisateur perd en rétrogradant :
     * une feature absente de cette liste n'est jamais signalée comme perdue.
     */
    public const FEATURES_CATALOGUE = [
        'dashboard',
        'mon_institut',
        'prestations',
        'caisse',
        'caisse_client',
        'caisse_code_promo',
        'caisse_impression',
        'historique',
        'historique_export_pdf',
        'clients',
        'codes_reduction',
        'fidelite',
        'finances',
        'stock',
        'produits',
        'equipe',
        'rdv',
        'parrainage',
        'boutique',
        'multi_instituts',
        'devis_factures',
        'credits',
        'vente_credit',
    ];

    /** Libellés lisibles pour l'affichage. */
    public const FEATURES_LIBELLES = [
        'multi_instituts'   => 'Multi-établissements',
        'devis_factures'    => 'Devis & factures',
        'credits'           => 'Crédits clients',
        'vente_credit'      => 'Vente à crédit & échéanciers',
        'finances'          => 'Rapports financiers',
        'fidelite'          => 'Programme de fidélité',
        'codes_reduction'   => 'Codes de réduction',
        'parrainage'        => 'Parrainage',
        'boutique'          => 'Boutique en ligne',
        'equipe'            => 'Gestion d\'équipe',
        'historique_export_pdf' => 'Export PDF de l\'historique',
        'caisse_impression' => 'Impression de tickets',
    ];

    // ─────────────────────────────────────────────────────────────────────
    // Lecture du plan
    // ─────────────────────────────────────────────────────────────────────

    /** Le slug du plan autorise-t-il cette fonctionnalité ? */
    public function planAutorise(PlanAbonnement $plan, string $feature): bool
    {
        $features = config("plans-features.plans.{$plan->slug}", []);

        return in_array('*', $features, true) || in_array($feature, $features, true);
    }

    /**
     * Fonctionnalités présentes dans l'ancien plan mais absentes du nouveau.
     *
     * @return array<int, string>
     */
    public function featuresPerdues(?PlanAbonnement $ancien, PlanAbonnement $nouveau): array
    {
        if (!$ancien || $ancien->id === $nouveau->id) {
            return [];
        }

        return array_values(array_filter(
            self::FEATURES_CATALOGUE,
            fn (string $feature) => $this->planAutorise($ancien, $feature)
                && !$this->planAutorise($nouveau, $feature)
        ));
    }

    /** Une rétrogradation = un plan moins cher que le plan de référence. */
    public function estRetrogradation(?PlanAbonnement $ancien, PlanAbonnement $nouveau): bool
    {
        if (!$ancien || $ancien->id === $nouveau->id) {
            return false;
        }

        return (int) $nouveau->prix < (int) $ancien->prix;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Analyse (aucune écriture)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Analyse l'impact d'un changement de plan sur le compte du propriétaire.
     *
     * @param  PlanAbonnement      $plan         Plan visé
     * @param  User                $proprietaire Propriétaire du compte
     * @param  PlanAbonnement|null $planActuel   Plan de référence (défaut : abonnement actif)
     * @return array<string, mixed>
     */
    public function analyser(PlanAbonnement $plan, User $proprietaire, ?PlanAbonnement $planActuel = null): array
    {
        $planActuel ??= $proprietaire->abonnementActif?->plan;

        $instituts = $this->institutsDuCompte($proprietaire);
        $institutsEnExces = $this->institutsEnExces($instituts, $plan->max_instituts);

        $employesEnExces = $this->employesEnExces($instituts, $plan->max_employes);
        $featuresPerdues = $this->featuresPerdues($planActuel, $plan);

        $impact = $institutsEnExces->isNotEmpty()
            || $employesEnExces->isNotEmpty()
            || !empty($featuresPerdues);

        return [
            'plan_slug'        => $plan->slug,
            'plan_nom'         => $plan->nom,
            'plan_actuel_slug' => $planActuel?->slug,
            'est_retrogradation' => $this->estRetrogradation($planActuel, $plan),
            'instituts' => [
                'total'     => $instituts->count(),
                'autorises' => $plan->max_instituts,
                'excedent'  => $institutsEnExces->count(),
                'liste'     => $institutsEnExces->map(fn (Institut $i) => [
                    'id'  => $i->id,
                    'nom' => $i->nom,
                ])->all(),
            ],
            'employes' => [
                // Seuls les employés des établissements éveillés consomment du quota
                'total'     => $instituts
                    ->reject(fn (Institut $i) => $i->en_sommeil)
                    ->sum(fn (Institut $i) => $this->employesDe($i)->count()),
                'autorises' => $plan->max_employes,
                'excedent'  => $employesEnExces->count(),
                'liste'     => $employesEnExces->map(fn (User $u) => [
                    'id'           => $u->id,
                    'nom'          => $u->nom_complet,
                    'institut_nom' => $u->institut?->nom,
                ])->all(),
            ],
            'features_perdues' => $featuresPerdues,
            'impact'           => $impact,
            // Un changement de plan n'est jamais bloqué côté serveur :
            // seule une confirmation explicite est exigée s'il y a un impact.
            'necessite_confirmation' => $impact,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Application (écriture) — idempotent
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Réconcilie le compte avec les quotas du plan.
     *
     * - Les instituts au-delà du quota passent en sommeil (le principal est
     *   toujours conservé), les autres sont réveillés.
     * - Les employés au-delà du quota, par établissement éveillé, passent en
     *   sommeil ; ceux qui rentrent à nouveau dans le quota sont réveillés.
     *
     * @return array{instituts_endormis:int, instituts_reveilles:int, employes_endormis:int, employes_reveilles:int}
     */
    public function appliquer(PlanAbonnement $plan, User $proprietaire): array
    {
        $resultat = [
            'instituts_endormis'   => 0,
            'instituts_reveilles'  => 0,
            'employes_endormis'    => 0,
            'employes_reveilles'   => 0,
        ];

        $instituts = $this->institutsDuCompte($proprietaire);
        $quotaInstituts = $plan->max_instituts;

        $motifInstitut = "Limite du plan {$plan->nom} : "
            . ($quotaInstituts === null
                ? 'aucun établissement autorisé'
                : \App\Helpers\TexteHelper::pluriel($quotaInstituts, 'établissement', 'établissements') . ' maximum.');

        foreach ($instituts as $index => $institut) {
            $doitDormir = $quotaInstituts !== null && $index >= $quotaInstituts;

            if ($doitDormir && !$institut->en_sommeil) {
                $institut->update(['en_sommeil' => true, 'sommeil_motif' => $motifInstitut]);
                $resultat['instituts_endormis']++;
            } elseif (!$doitDormir && $institut->en_sommeil) {
                $institut->update(['en_sommeil' => false, 'sommeil_motif' => null]);
                $resultat['instituts_reveilles']++;
            }
        }

        $quotaEmployes = $plan->max_employes;
        $motifEmploye = "Limite du plan {$plan->nom} : "
            . ($quotaEmployes === null
                ? 'aucun employé autorisé'
                : \App\Helpers\TexteHelper::pluriel($quotaEmployes, 'employé', 'employés') . ' maximum par établissement.');

        foreach ($instituts as $institut) {
            if ($institut->en_sommeil) {
                continue;
            }

            foreach ($this->employesDe($institut) as $index => $employe) {
                $doitDormir = $quotaEmployes !== null && $index >= $quotaEmployes;

                if ($doitDormir && !$employe->en_sommeil) {
                    $employe->forceFill(['en_sommeil' => true, 'sommeil_motif' => $motifEmploye])->save();
                    $resultat['employes_endormis']++;
                } elseif (!$doitDormir && $employe->en_sommeil) {
                    $employe->forceFill(['en_sommeil' => false, 'sommeil_motif' => null])->save();
                    $resultat['employes_reveilles']++;
                }
            }
        }

        return $resultat;
    }

    /**
     * Réveille un établissement si le quota du plan le permet encore.
     */
    public function reactiverInstitut(Institut $institut, User $proprietaire): bool
    {
        $plan = $proprietaire->abonnementActif?->plan;
        if (!$plan) {
            return false;
        }

        $actifs = $this->institutsDuCompte($proprietaire)->where('en_sommeil', false)->count();

        if ($plan->max_instituts !== null && $actifs >= $plan->max_instituts) {
            return false;
        }

        $institut->update(['en_sommeil' => false, 'sommeil_motif' => null]);

        return true;
    }

    /**
     * Réveille un employé si le quota de son établissement le permet encore.
     */
    public function reactiverEmploye(User $employe, User $proprietaire): bool
    {
        $plan = $proprietaire->abonnementActif?->plan;
        if (!$plan || !$employe->institut_id) {
            return false;
        }

        $institut = Institut::find($employe->institut_id);
        if (!$institut || $institut->en_sommeil) {
            return false;
        }

        $actifs = $this->employesDe($institut)->where('en_sommeil', false)->count();

        if ($plan->max_employes !== null && $actifs >= $plan->max_employes) {
            return false;
        }

        $employe->forceFill(['en_sommeil' => false, 'sommeil_motif' => null])->save();

        return true;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Tous les établissements du compte.
     *
     * Ordre : établissement principal en premier, puis par ancienneté —
     * c'est cet ordre qui décide lesquels passent en sommeil.
     *
     * @return Collection<int, Institut>
     */
    public function institutsDuCompte(User $proprietaire): Collection
    {
        $principalId = $proprietaire->institut_id;

        $instituts = Institut::where('proprietaire_id', $proprietaire->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        // Données legacy : l'établissement principal peut ne pas avoir de
        // proprietaire_id renseigné. On le rattache quand même au compte.
        if ($principalId && !$instituts->contains('id', $principalId)) {
            $principal = Institut::find($principalId);
            if ($principal) {
                $instituts->push($principal);
            }
        }

        return $instituts
            ->sortBy(fn (Institut $i) => $i->id === $principalId ? 0 : 1)
            ->values();
    }

    /**
     * Employés d'un établissement (hors propriétaire).
     *
     * @return Collection<int, User>
     */
    public function employesDe(Institut $institut): Collection
    {
        return User::where('institut_id', $institut->id)
            ->whereIn('role', ['employe', 'gerant', 'admin'])
            ->where('id', '!=', $institut->proprietaire_id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * Établissements qui dépasseraient le quota, dans l'ordre de mise en sommeil.
     *
     * @param  Collection<int, Institut> $instituts
     * @return Collection<int, Institut>
     */
    private function institutsEnExces(Collection $instituts, ?int $quota): Collection
    {
        if ($quota === null) {
            return collect();
        }

        return $instituts->slice($quota)->values();
    }

    /**
     * Employés qui dépasseraient le quota, établissement par établissement éveillé.
     *
     * @param  Collection<int, Institut> $instituts
     * @return Collection<int, User>
     */
    private function employesEnExces(Collection $instituts, ?int $quota): Collection
    {
        if ($quota === null) {
            return collect();
        }

        return $instituts
            ->reject(fn (Institut $i) => $i->en_sommeil)
            ->flatMap(fn (Institut $i) => $this->employesDe($i)->slice($quota))
            ->values();
    }
}
