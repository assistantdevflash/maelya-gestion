<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Mail\NouvelleDemandeAbonnement;
use App\Models\Abonnement;
use App\Models\Institut;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\PlanAbonnement;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\PaymentGatewayManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AbonnementController extends Controller
{
    public function expire()
    {
        return view('dashboard.abonnement.expire');
    }

    /**
     * Page d'invitation à passer au plan supérieur lorsqu'une fonctionnalité
     * n'est pas disponible dans le plan actuel.
     */
    public function upgrade(Request $request)
    {
        $feature = (string) $request->query('feature', '');
        $meta = config("plans-features.meta.$feature");

        // Si la feature n'existe pas dans la matrice, on tombe sur la liste des plans
        if (!$meta) {
            return redirect()->route('abonnement.plans');
        }

        $planRequis = PlanAbonnement::where('slug', $meta['plan_requis'])
            ->where('actif', true)
            ->first();

        $abonnementActif = Auth::user()->abonnementActif;

        return view('dashboard.abonnement.upgrade', compact(
            'feature', 'meta', 'planRequis', 'abonnementActif'
        ));
    }

    public function historique()
    {
        $user = Auth::user();

        $abonnements = Abonnement::where('user_id', $user->id)
            ->with('plan', 'validePar')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('dashboard.abonnement.historique', compact('abonnements'));
    }

    public function plans()
    {
        $plans = PlanAbonnement::where('actif', true)
            ->whereIn('slug', ['premium', 'premium-plus', 'ultra', 'entreprise'])
            ->orderBy('ordre')
            ->get();

        $user = Auth::user();
        // Pour un non-propriétaire, utiliser l'abonnement du propriétaire
        $institut = \App\Models\Institut::find($user->currentInstitutId());
        if ($institut && $institut->proprietaire_id !== $user->id) {
            $owner = \App\Models\User::find($institut->proprietaire_id);
            $abonnementActif = $owner?->abonnementActif;
        } else {
            $abonnementActif = $user->abonnementActif;
        }

        $demandeEnAttente = Abonnement::where('user_id', $user->id)
            ->where('statut', 'en_attente')
            ->with('plan')
            ->first();

        $abonnementSursis = $abonnementActif ? null : $user->abonnementEnSursis();

        return view('dashboard.abonnement.plans', compact('plans', 'abonnementActif', 'demandeEnAttente', 'abonnementSursis'));
    }

    /**
     * Affiche la page de souscription complète pour un plan donné.
     */
    public function showSouscrire(Request $request, PlanAbonnement $plan)
    {
        if (!$plan->actif || $plan->slug === 'essai') {
            return redirect()->route('abonnement.plans')
                ->with('error', "Ce plan n'est pas disponible.");
        }

        $periode = $request->query('periode', 'mensuel');
        if (!in_array($periode, ['mensuel', 'trimestre', 'semestre', 'annuel', 'triennal'])) {
            $periode = 'mensuel';
        }

        $user = Auth::user();
        $abonnementActif = $user->abonnementActif;

        $demandeEnAttente = Abonnement::where('user_id', $user->id)
            ->where('statut', 'en_attente')
            ->with('plan')
            ->first();

        // ── Facturation PAR établissement ─────────────────────────────────────
        // Au renouvellement, les boutiques actives des établissements sont
        // re-facturées automatiquement. On les transmet à la vue pour l'affichage.
        $estRenouvellement = (bool) $abonnementActif;
        $boutiquesActives = $user->mesInstituts()
            ->get()
            ->filter(fn ($i) => $i->hasBoutiqueOption())
            ->values();
        $nbBoutiquesActives = $boutiquesActives->count();

        // Prix pour la période sélectionnée
        $prixPlan       = $plan->prixEffectif($periode);
        $paymentMethods = PaymentMethod::active()->get();

        return view('dashboard.abonnement.souscrire', compact(
            'plan', 'periode', 'prixPlan',
            'abonnementActif', 'demandeEnAttente', 'user', 'paymentMethods',
            'estRenouvellement', 'boutiquesActives', 'nbBoutiquesActives'
        ));
    }

    public function souscrire(Request $request, PlanAbonnement $plan)
    {
        if (!$plan->actif || $plan->slug === 'essai') {
            return back()->with('error', "Ce plan n'est pas disponible.");
        }

        $request->validate([
            'periode'             => ['required', 'in:mensuel,trimestre,semestre,annuel,triennal'],
            'methode_paiement'    => ['nullable', 'string', 'in:geniuspay,bank_transfer'],
            'reference_transfert' => ['nullable', 'string', 'max:100'],
            'preuve_paiement'     => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp,pdf', 'max:10240'],
        ]);

        $methodCode = $request->input('methode_paiement', 'bank_transfer');
        $method     = PaymentMethod::where('code', $methodCode)->where('is_active', true)->first();

        // Forcer bank_transfer si GeniusPay non disponible
        if ($methodCode === 'geniuspay' && !$method) {
            $methodCode = 'bank_transfer';
        }

        // ── Flux GeniusPay ──────────────────────────────────────────────────
        if ($methodCode === 'geniuspay' && $method) {
            return $this->souscrireViaGeniusPay($request, $plan, $method);
        }

        // ── Flux transfert bancaire (existant inchangé) ─────────────────────
        if (!$request->reference_transfert && !$request->hasFile('preuve_paiement')) {
            return back()->with('error', 'Veuillez fournir au moins la référence du transfert ou le reçu de paiement.');
        }

        $user = Auth::user();

        $demandeExistante = Abonnement::where('user_id', $user->id)
            ->where('statut', 'en_attente')
            ->exists();

        if ($demandeExistante) {
            return back()->with('error', 'Vous avez déjà une demande en attente de validation.');
        }

        $preuvePath = $request->hasFile('preuve_paiement')
            ? $request->file('preuve_paiement')->store('preuves-paiement', 'public')
            : null;
        $montant = $plan->prixPourPeriode($request->periode);

        // Option boutique en ligne (add-on payant, facturation PAR établissement)
        $estRenouvellement = (bool) $user->abonnementActif;
        $nbMois = match ($request->periode) {
            'mensuel'  => 1,
            'semestre' => 6,
            'annuel'   => 12,
            'triennal' => 36,
            default    => 1,
        };
        $nbBoutiques = 0;
        if ($estRenouvellement) {
            // Renouvellement : re-facturer les boutiques actives
            $nbBoutiques = $user->mesInstituts()
                ->get()
                ->filter(fn ($i) => $i->hasBoutiqueOption())
                ->count();
        } elseif ($request->boolean('option_boutique')) {
            // Nouvelle souscription : une boutique (établissement principal)
            $nbBoutiques = 1;
        }
        $prixBoutique = 3900 * $nbMois * $nbBoutiques;

        Abonnement::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'montant' => $montant + $prixBoutique,
            'periode' => $request->periode,
            'statut' => 'en_attente',
            'reference_transfert' => $request->reference_transfert,
            'preuve_paiement' => $preuvePath,
            'metadata' => [
                'boutique' => $nbBoutiques > 0,
                'boutique_prix' => $nbBoutiques > 0 ? 3900 : 0,
                'nb_boutiques' => $nbBoutiques,
            ],
        ]);

        // Notifier tous les super-admins par email
        $superAdmins = User::where('role', 'super_admin')->get();
        $abonnement  = Abonnement::where('user_id', $user->id)
            ->where('statut', 'en_attente')
            ->with(['user', 'plan'])
            ->latest()
            ->first();

        foreach ($superAdmins as $admin) {
            Mail::to($admin->email)->send(new NouvelleDemandeAbonnement($abonnement));
        }
        \App\Services\NotificationService::notifyAdmins(
            'nouvelle_demande',
            '💳 Nouvelle demande — ' . ($abonnement?->plan?->nom ?? 'Plan'),
            ($user->prenom ?? $user->name) . ' attend la validation de son abonnement.',
            '/admin/abonnements?statut=en_attente'
        );
        try {
            app(\App\Services\PushNotificationService::class)->sendToAdmins(
                '💳 Nouvelle demande d\'abonnement',
                ($user->prenom ?? '') . ' (' . ($abonnement?->plan?->nom ?? 'Plan') . ') attend votre validation.',
                '/admin/abonnements?statut=en_attente'
            );
        } catch (\Throwable $e) { \Log::warning('[Push] ' . $e->getMessage()); }

        return redirect()->route('abonnement.plans')
            ->with('success', "Votre demande d'abonnement a été envoyée ! Elle sera validée sous 24h.");
    }

    /**
     * Ajouter l'option boutique à un abonnement existant
     */
    public function ajouterOptionBoutique(Request $request)
    {
        $user = Auth::user();
        $abo = $user->abonnementActif;

        // L'établissement concerné = établissement courant (session multi-instituts)
        $institut = Institut::find(session('current_institut_id', $user->institut_id));
        if (!$institut) {
            return back()->with('error', 'Établissement introuvable.');
        }

        // Vérifications de base
        if (!$abo || $abo->plan->slug === 'essai') {
            return back()->with('error', 'Action non disponible.');
        }

        if ($institut->hasBoutiqueOption()) {
            return back()->with('info', 'L\'option boutique est déjà activée sur cet établissement.');
        }

        // Vérifier qu'il n'y a pas déjà une demande en attente POUR CET ÉTABLISSEMENT
        $demandeExistante = Abonnement::where('user_id', $user->id)
            ->where('statut', 'en_attente')
            ->whereJsonContains('metadata->type', 'ajout_option_boutique')
            ->whereJsonContains('metadata->institut_id', $institut->id)
            ->exists();

        if ($demandeExistante) {
            return back()->with('error', 'Vous avez déjà une demande d\'ajout d\'option boutique en attente pour cet établissement.');
        }

        // Validation des données de paiement
        $request->validate([
            'methode_paiement'    => 'nullable|string|in:geniuspay,bank_transfer',
            'reference_transfert' => 'nullable|string|max:255',
            'preuve_paiement'     => 'nullable|file|mimes:jpeg,jpg,png,webp,pdf|max:10240',
        ]);

        $methodCode = $request->input('methode_paiement', 'bank_transfer');
        $method     = PaymentMethod::where('code', $methodCode)->where('is_active', true)->first();

        // ── Flux GeniusPay pour boutique ─────────────────────────────────────
        if ($methodCode === 'geniuspay' && $method) {
            return $this->activerBoutiqueViaGeniusPay($abo, $method, $institut);
        }

        // Au moins une preuve de paiement
        if (!$request->reference_transfert && !$request->hasFile('preuve_paiement')) {
            return back()->with('error', 'Veuillez fournir une référence de transfert ou un reçu de paiement.');
        }

        // Upload du reçu si présent
        $preuvePath = null;
        if ($request->hasFile('preuve_paiement')) {
            $preuvePath = $request->file('preuve_paiement')->store('abonnements/preuves', 'public');
        }

        // Calculer le prorata pour le reste de la période
        $joursRestants = max(1, $abo->joursRestants());
        $prixJournalier = 3900 / 30;
        $montantProrata = (int) round($prixJournalier * $joursRestants);

        // Créer une demande d'ajout d'option (en_attente)
        $nouvelAbo = Abonnement::create([
            'user_id'    => $user->id,
            'plan_id'    => $abo->plan_id,
            'montant'    => $montantProrata,
            'periode'    => 'option_boutique',
            'statut'     => 'en_attente',
            'reference_transfert' => $request->reference_transfert,
            'preuve_paiement' => $preuvePath,
            'metadata'   => [
                'type' => 'ajout_option_boutique',
                'abonnement_source_id' => $abo->id,
                'institut_id' => $institut->id,
                'boutique' => true,
                'boutique_prix' => 3900,
                'jours_restants' => $joursRestants,
            ],
        ]);

        // Notifier les super-admins
        $superAdmins = User::where('role', 'super_admin')->get();
        foreach ($superAdmins as $admin) {
            Mail::to($admin->email)->send(new \App\Mail\NouvelleDemandeAbonnement($nouvelAbo));
        }

        NotificationService::notifyAdmins(
            'nouvelle_demande',
            '🛍️ Ajout option boutique',
            ($user->prenom ?? $user->name) . ' demande à ajouter l\'option boutique en ligne (prorata ' . number_format($montantProrata, 0, ',', ' ') . ' F).',
            '/admin/abonnements?statut=en_attente'
        );

        try {
            app(\App\Services\PushNotificationService::class)->sendToAdmins(
                '🛍️ Ajout option boutique',
                ($user->prenom ?? '') . ' demande l\'option boutique (' . number_format($montantProrata, 0, ',', ' ') . ' F prorata).',
                '/admin/abonnements?statut=en_attente'
            );
        } catch (\Throwable $e) { \Log::warning('[Push] ' . $e->getMessage()); }

        return redirect()->route('dashboard.boutique.config.index')
            ->with('success', 'Demande d\'ajout de l\'option boutique envoyée ! Montant prorata : ' . number_format($montantProrata, 0, ',', ' ') . ' FCFA. Vous serez notifié dès la validation.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Méthodes privées GeniusPay
    // ─────────────────────────────────────────────────────────────────────────

    private function souscrireViaGeniusPay(Request $request, PlanAbonnement $plan, PaymentMethod $method)
    {
        $user    = Auth::user();
        $periode = $request->input('periode', 'mensuel');
        $montant = $plan->prixPourPeriode($periode);
        $nbMois  = match ($periode) { 'trimestre' => 3, 'semestre' => 6, 'annuel' => 12, 'triennal' => 36, default => 1 };

        // ── Option boutique (facturation PAR établissement) ──────────────────
        // Renouvellement : re-facturer automatiquement les boutiques actives
        $estRenouvellement = (bool) $user->abonnementActif;
        $nbBoutiques = 0;

        if ($estRenouvellement) {
            $nbBoutiques = $user->mesInstituts()
                ->get()
                ->filter(fn ($i) => $i->hasBoutiqueOption())
                ->count();
        } elseif ($request->boolean('option_boutique')) {
            // Nouvelle souscription : une boutique (établissement principal)
            $nbBoutiques = 1;
        }

        if ($nbBoutiques > 0) {
            $montant += 3900 * $nbMois * $nbBoutiques;
        }

        // Créer l'abonnement en_attente (sera activé auto par le webhook)
        $abonnement = Abonnement::create([
            'user_id'  => $user->id,
            'plan_id'  => $plan->id,
            'montant'  => $montant,
            'periode'  => $periode,
            'statut'   => 'en_attente',
            'metadata' => [
                'boutique'       => $nbBoutiques > 0,
                'boutique_prix'  => $nbBoutiques > 0 ? 3900 : 0,
                'nb_boutiques'   => $nbBoutiques,
                'payment_method' => 'geniuspay',
            ],
        ]);

        // Créer la transaction de paiement
        $transaction = PaymentTransaction::create([
            'id'                  => (string) \Illuminate\Support\Str::uuid(),
            'reference'           => PaymentTransaction::generateReference(),
            'user_id'             => $user->id,
            'institut_id'         => $user->institut_id,
            'abonnement_id'       => $abonnement->id,
            'type'                => $estRenouvellement ? 'renouvellement' : 'abonnement',
            'amount'              => $montant,
            'net_amount'          => $montant,
            'currency'            => 'XOF',
            'payment_method_id'   => $method->id,
            'payment_method_code' => 'geniuspay',
            'status'              => 'pending',
            'metadata'            => [
                'plan_nom'     => $plan->nom,
                'periode'      => $periode,
                'nb_boutiques' => $nbBoutiques,
            ],
        ]);

        try {
            $result = app(PaymentGatewayManager::class)->initiate($transaction, $method);
            return redirect($result['checkout_url']);
        } catch (\Throwable $e) {
            $transaction->update(['status' => 'failed']);
            $abonnement->delete();
            Log::error('[GeniusPay souscrire] ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la redirection vers GeniusPay. Veuillez réessayer ou utiliser le transfert bancaire.');
        }
    }

    private function activerBoutiqueViaGeniusPay(Abonnement $abo, PaymentMethod $method, ?Institut $institut = null)
    {
        $user           = Auth::user();
        $institut       = $institut ?? Institut::find(session('current_institut_id', $user->institut_id));
        $joursRestants  = max(1, $abo->joursRestants());
        $montantProrata = (int) round(3900 / 30 * $joursRestants);

        $transaction = PaymentTransaction::create([
            'id'                  => (string) \Illuminate\Support\Str::uuid(),
            'reference'           => PaymentTransaction::generateReference(),
            'user_id'             => $user->id,
            'institut_id'         => $institut?->id ?? $user->institut_id,
            'abonnement_id'       => $abo->id,
            'type'                => 'boutique_activation',
            'amount'              => $montantProrata,
            'net_amount'          => $montantProrata,
            'currency'            => 'XOF',
            'payment_method_id'   => $method->id,
            'payment_method_code' => 'geniuspay',
            'status'              => 'pending',
            'metadata'            => [
                'abonnement_source_id' => $abo->id,
                'institut_id'          => $institut?->id,
                'jours_restants'       => $joursRestants,
            ],
        ]);

        try {
            $result = app(PaymentGatewayManager::class)->initiate($transaction, $method);
            return redirect($result['checkout_url']);
        } catch (\Throwable $e) {
            $transaction->update(['status' => 'failed']);
            Log::error('[GeniusPay boutique] ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la redirection vers GeniusPay. Veuillez réessayer ou utiliser le transfert bancaire.');
        }
    }
}

