<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Mail\NouvelleDemandeAbonnement;
use App\Models\Abonnement;
use App\Models\PlanAbonnement;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $abonnementActif = $user->abonnementActif;

        $demandeEnAttente = Abonnement::where('user_id', $user->id)
            ->where('statut', 'en_attente')
            ->with('plan')
            ->first();

        return view('dashboard.abonnement.plans', compact('plans', 'abonnementActif', 'demandeEnAttente'));
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
        if (!in_array($periode, ['mensuel', 'annuel', 'triennal'])) {
            $periode = 'mensuel';
        }

        $user = Auth::user();
        $abonnementActif = $user->abonnementActif;

        $demandeEnAttente = Abonnement::where('user_id', $user->id)
            ->where('statut', 'en_attente')
            ->with('plan')
            ->first();

        // Prix pour la période sélectionnée
        $prixPlan = $plan->prixEffectif($periode);

        return view('dashboard.abonnement.souscrire', compact(
            'plan', 'periode', 'prixPlan',
            'abonnementActif', 'demandeEnAttente', 'user'
        ));
    }

    public function souscrire(Request $request, PlanAbonnement $plan)
    {
        if (!$plan->actif || $plan->slug === 'essai') {
            return back()->with('error', "Ce plan n'est pas disponible.");
        }

        $request->validate([
            'periode' => ['required', 'in:mensuel,annuel,triennal'],
            'reference_transfert' => ['nullable', 'string', 'max:100'],
            'preuve_paiement' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp,pdf', 'max:10240'],
        ]);

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

        // Option boutique en ligne (add-on payant)
        $optionBoutique = $request->boolean('option_boutique');
        $prixBoutique = 0;
        if ($optionBoutique) {
            $nbMois = match ($request->periode) {
                'mensuel'  => 1,
                'annuel'   => 12,
                'triennal' => 36,
            };
            $prixBoutique = 3900 * $nbMois;
        }

        Abonnement::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'montant' => $montant + $prixBoutique,
            'periode' => $request->periode,
            'statut' => 'en_attente',
            'reference_transfert' => $request->reference_transfert,
            'preuve_paiement' => $preuvePath,
            'metadata' => [
                'boutique' => $optionBoutique,
                'boutique_prix' => $optionBoutique ? 3900 : 0,
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

        // Vérifications de base
        if (!$abo || $abo->plan->slug === 'essai') {
            return back()->with('error', 'Action non disponible.');
        }

        if ($abo->hasBoutique()) {
            return back()->with('info', 'L\'option boutique est déjà activée sur votre abonnement.');
        }

        // Vérifier qu'il n'y a pas déjà une demande en attente
        $demandeExistante = Abonnement::where('user_id', $user->id)
            ->where('statut', 'en_attente')
            ->whereJsonContains('metadata->type', 'ajout_option_boutique')
            ->exists();

        if ($demandeExistante) {
            return back()->with('error', 'Vous avez déjà une demande d\'ajout d\'option boutique en attente.');
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
            'reference_transfert' => null,
            'preuve_paiement' => null,
            'metadata'   => [
                'type' => 'ajout_option_boutique',
                'abonnement_source_id' => $abo->id,
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

        return redirect()->route('abonnement.plans')
            ->with('success', 'Demande d\'ajout de l\'option boutique envoyée ! Montant prorata : ' . number_format($montantProrata, 0, ',', ' ') . ' FCFA.');
    }
}
