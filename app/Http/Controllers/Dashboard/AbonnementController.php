<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Mail\NouvelleDemandeAbonnement;
use App\Models\Abonnement;
use App\Models\PlanAbonnement;
use App\Models\User;
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
}
